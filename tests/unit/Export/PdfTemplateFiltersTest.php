<?php

declare(strict_types=1);

namespace App\Model\Export;

use App\Helpers\AccountancyLatteExtension;
use Codeception\Test\Unit;
use Latte\Essential\CoreExtension;

use function array_keys;
use function array_merge;
use function basename;
use function file_get_contents;
use function glob;
use function in_array;
use function preg_match_all;
use function sprintf;

/**
 * Hlídá, že každý Latte filtr použitý v PDF/tiskových šablonách je v enginu zaregistrovaný – a to
 * ve správné velikosti písmen. Latte 3 je totiž u filtrů case-sensitive a nezaregistrovaný filtr
 * spadne až za běhu při generování PDF (Latte lint ani PHPStan ho nezachytí). Právě tak unikly do
 * produkce chyby `postCode` (chybějící registrace) a `pricetostring` vs `priceToString` (velikost).
 */
final class PdfTemplateFiltersTest extends Unit
{
    private const TemplateDir = __DIR__.'/../../../app';

    /** Globy adresářů se šablonami tiskových výstupů (viz .docs/vystupy.md). */
    private const TemplateGlobs = [
        '/Model/Export/templates/*.latte',
        '/Model/Cashbook/ReadModel/QueryHandlers/Pdf/templates/*.latte',
        '/Presentation/Travel/Command/ex.command.latte',
        '/Presentation/Travel/Contract/ex.contract.*.latte',
    ];

    /** Jazykové konstrukce zapisované jako filtr, které nejsou v getFilters(). */
    private const LanguageModifiers = ['noescape'];

    public function testTemplatesAreDiscovered(): void
    {
        self::assertNotEmpty($this->collectTemplates(), 'Nenalezeny žádné PDF šablony – zkontroluj cesty v TemplateGlobs.');
    }

    /**
     * @dataProvider providePdfTemplates
     */
    public function testAllUsedFiltersAreRegistered(string $templateFile): void
    {
        $available = $this->availableFilters();

        $content = file_get_contents($templateFile);
        self::assertIsString($content);

        // jeden pipe (ne logický operátor ||) následovaný názvem filtru
        preg_match_all('~(?<![|])\|(?![|])\s*([A-Za-z][A-Za-z0-9]*)~', $content, $matches);

        foreach ($matches[1] as $filter) {
            self::assertTrue(
                in_array($filter, $available, true),
                sprintf(
                    'Filtr "%s" použitý v šabloně %s není zaregistrovaný (Latte 3 rozlišuje velikost písmen).',
                    $filter,
                    basename($templateFile),
                ),
            );
        }
    }

    /**
     * @return list<array{0: string}>
     */
    public function providePdfTemplates(): array
    {
        $data = [];

        foreach ($this->collectTemplates() as $file) {
            $data[] = [$file];
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    private function collectTemplates(): array
    {
        $files = [];

        foreach (self::TemplateGlobs as $glob) {
            foreach (glob(self::TemplateDir.$glob) ?: [] as $file) {
                $files[] = $file;
            }
        }

        return $files;
    }

    /**
     * @return list<string>
     */
    private function availableFilters(): array
    {
        return array_merge(
            array_keys((new CoreExtension())->getFilters()),
            array_keys((new AccountancyLatteExtension())->getFilters()),
            self::LanguageModifiers,
        );
    }
}
