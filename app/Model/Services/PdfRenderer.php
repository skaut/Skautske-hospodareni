<?php

declare(strict_types=1);

namespace App\Model\Services;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use RuntimeException;

use function base64_encode;
use function file_exists;
use function file_get_contents;
use function header;
use function is_file;
use function is_string;
use function ltrim;
use function parse_url;
use function pathinfo;
use function preg_replace_callback;
use function rtrim;
use function sprintf;
use function str_starts_with;
use function stripos;
use function strtolower;
use function substr;

use const PATHINFO_EXTENSION;
use const PHP_URL_PATH;

/**
 * Renderuje HTML do PDF přes Gotenberg (headless Chromium). Nahrazuje mpdf.
 *
 * Gotenberg běží jako samostatný interní kontejner a nevidí na filesystem ani URL aplikace, proto se
 * před odesláním všechny lokální obrázky (`<img src>`) vloží inline jako base64 `data:` URI. HTML je
 * tak soběstačné a citlivá uživatelská data (loga/razítka faktur) jdou přímo do interního Gotenbergu –
 * nikdy se nevystavují přes veřejnou URL.
 */
class PdfRenderer
{
    private const MimeTypes = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
    ];

    public function __construct(
        private string $gotenbergUrl,
        private string $wwwDir,
        private ClientInterface $client,
    ) {
    }

    /**
     * Renders PDF to output stream (inline in browser).
     *
     * @param bool $landscape TRUE for landscape, FALSE for portrait mode
     */
    public function render(string $template, string $filename, bool $landscape = false): void
    {
        $pdf = $this->renderToString($template, $landscape);

        header('Content-Type: application/pdf');
        header(sprintf('Content-Disposition: inline; filename="%s"', $filename));
        header('Cache-Control: no-store, max-age=0');
        echo $pdf;
    }

    /**
     * Emuluje chování mpdf (`shrink_tables_to_fit`), na které jsou staré PDF šablony laděné: přeširoký
     * obsah (fixní `width:800px` apod.) se v mpdf zužoval na šířku stránky, kdežto Chromium ho nechá
     * přetéct. `max-width` je samostatná vlastnost, takže fixní `width` v šablonách není nutné měnit.
     */
    private const NormalizeCss = '<style>'
        .'html,body{margin:0}'
        // !important, aby normalizace přebila vlastní max-width šablon (faktura má .invoice-box{max-width:800px},
        // což je širší než tisková plocha A4 → jinak ořez vpravo). Emuluje mpdf shrink-to-fit.
        // box-sizing:border-box, aby se do 100 % započítal i vlastní padding kontejneru (jinak width:100%+padding přeteče).
        // calc(100% - 2px): necháme 2px vpravo, aby obvodová 1px čára rámečku (např. cestovní příkaz) neležela
        // přesně na hraně tiskové plochy a nevykreslila se tenčí/oříznutá.
        .'body>*{max-width:calc(100% - 2px)!important;box-sizing:border-box}'
        .'table{max-width:100%}'
        .'img{max-width:100%}'
        // HTML atribut border="1" renderuje Chromium jako slabé nejednotné "inset" okraje buněk; mpdf
        // ho kreslil jako plné jednotné 1px černé čáry. Sjednotíme na collapsed solid, ať PDF sedí.
        // Necháme kousek místa vpravo: u border-collapse leží obvodová čára na hraně (půl ven), a když
        // tabulka vyplní 100 %, pravá půlka přeteče za tiskovou plochu a ořízne se.
        .'table[border]{border-collapse:collapse;border:0;max-width:calc(100% - 2px)}'
        .'table[border] th,table[border] td{border:1px solid #000;padding:2px 5px}'
        .'</style>';

    public function renderToString(string $template, bool $landscape = false): string
    {
        $html = $this->injectNormalizeCss($this->inlineImages($template));

        try {
            $response = $this->client->request('POST', rtrim($this->gotenbergUrl, '/').'/forms/chromium/convert/html', [
                'multipart' => [
                    ['name' => 'files', 'contents' => $html, 'filename' => 'index.html'],
                    ['name' => 'paperWidth', 'contents' => '8.27'],
                    ['name' => 'paperHeight', 'contents' => '11.69'],
                    ['name' => 'marginTop', 'contents' => '0.4'],
                    ['name' => 'marginBottom', 'contents' => '0.4'],
                    ['name' => 'marginLeft', 'contents' => '0.4'],
                    ['name' => 'marginRight', 'contents' => '0.4'],
                    ['name' => 'landscape', 'contents' => $landscape ? 'true' : 'false'],
                    ['name' => 'printBackground', 'contents' => 'true'],
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Failed to render PDF via Gotenberg.', 0, $e);
        }

        return (string) $response->getBody();
    }

    /**
     * Vkládá normalizační `<style>` do `<head>`, aby u plnohodnotných HTML dokumentů (faktura má vlastní
     * `<!DOCTYPE html>`) zůstal zachován standards mode – prepend před `<!DOCTYPE>` by prohlížeč přepnul
     * do quirks mode a rozhodil box model. Staré reportové šablony jsou fragmenty bez `<head>`, u těch
     * prepend nevadí.
     */
    private function injectNormalizeCss(string $html): string
    {
        $pos = stripos($html, '</head>');

        if ($pos !== false) {
            return substr($html, 0, $pos).self::NormalizeCss.substr($html, $pos);
        }

        return self::NormalizeCss.$html;
    }

    private function inlineImages(string $html): string
    {
        return (string) preg_replace_callback(
            '~(<img\b[^>]*?\bsrc=)(["\'])(?<src>.*?)\2~i',
            function (array $matches): string {
                $dataUri = $this->toDataUri($matches['src']);

                return $dataUri === null ? $matches[0] : $matches[1].$matches[2].$dataUri.$matches[2];
            },
            $html,
        );
    }

    private function toDataUri(string $src): ?string
    {
        if (str_starts_with($src, 'data:')) {
            return null; // already inlined
        }

        $path = $this->resolveLocalPath($src);

        if ($path === null) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = self::MimeTypes[$extension] ?? null;

        if ($mime === null) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        return sprintf('data:%s;base64,%s', $mime, base64_encode($contents));
    }

    /**
     * Resolves an <img src> value (local absolute path or app URL) to an existing local file, or null.
     */
    private function resolveLocalPath(string $src): ?string
    {
        if (is_file($src)) {
            return $src; // absolute filesystem path (e.g. uploaded invoice logo/stamp)
        }

        $urlPath = parse_url($src, PHP_URL_PATH);

        if (! is_string($urlPath) || $urlPath === '') {
            return null;
        }

        $candidate = rtrim($this->wwwDir, '/').'/'.ltrim($urlPath, '/');

        return file_exists($candidate) && is_file($candidate) ? $candidate : null;
    }
}
