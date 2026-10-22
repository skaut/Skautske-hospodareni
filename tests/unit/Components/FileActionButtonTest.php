<?php

declare(strict_types=1);

namespace App\Components;

use Codeception\Test\Unit;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Latte\Engine;
use Nette\Forms\Form;

use function libxml_use_internal_errors;

use const LIBXML_NOERROR;

/**
 * Renderuje sdílenou šablonu FileActionButton reálným Latte enginem a kontroluje výsledné ovládání.
 */
final class FileActionButtonTest extends Unit
{
    private const Template = __DIR__.'/../../../app/Components/templates/FileActionButton.latte';

    public function testPdfLinkOffersDownloadAndOpenInNewTab(): void
    {
        $xpath = $this->render([
            'label' => 'Stáhnout PDF',
            'downloadUrl' => '/cestovni-prikazy/print/1?download=1',
            'openUrl' => '/cestovni-prikazy/print/1',
            'dataTest' => 'command-pdf',
        ]);

        $download = $this->single($xpath, '//a[@data-test="command-pdf"]');
        self::assertSame('/cestovni-prikazy/print/1?download=1', $download->getAttribute('href'));
        self::assertFalse($download->hasAttribute('target'));
        self::assertStringContainsString('Stáhnout PDF', $download->textContent);

        $toggle = $this->single($xpath, '//button[@data-test="command-pdf-toggle"]');
        self::assertSame('dropdown', $toggle->getAttribute('data-bs-toggle'));

        $open = $this->single($xpath, '//a[@data-test="command-pdf-open"]');
        self::assertSame('/cestovni-prikazy/print/1', $open->getAttribute('href'));
        self::assertSame('_blank', $open->getAttribute('target'));
        self::assertSame('noopener noreferrer', $open->getAttribute('rel'));
        self::assertStringContainsString('Otevřít v nové kartě', $open->textContent);
    }

    public function testOtherFormatsOfferOnlyDownload(): void
    {
        $xpath = $this->render([
            'label' => 'Stáhnout Excel',
            'downloadUrl' => '/ucastnici/export-excel/1',
            'dataTest' => 'participants-export-excel',
        ]);

        self::assertSame('/ucastnici/export-excel/1', $this->single($xpath, '//a[@data-test="participants-export-excel"]')->getAttribute('href'));
        self::assertSame(0, $this->countNodes($xpath, '//*[@data-bs-toggle="dropdown"]'));
        self::assertSame(0, $this->countNodes($xpath, '//*[@target="_blank"]'));
    }

    public function testCompactModeKeepsAccessibleLabel(): void
    {
        $xpath = $this->render([
            'label' => 'Stáhnout PDF dokladu',
            'downloadUrl' => '/doklady/1?download=1',
            'openUrl' => '/doklady/1',
            'compact' => true,
            'dataTest' => 'chit-pdf-1',
        ]);

        $download = $this->single($xpath, '//a[@data-test="chit-pdf-1"]');
        self::assertSame('Stáhnout PDF dokladu', $download->getAttribute('aria-label'));
        self::assertSame('Stáhnout PDF dokladu', $download->getAttribute('title'));
        self::assertStringNotContainsString('Stáhnout PDF dokladu', $download->textContent);
    }

    public function testDisabledPdfHasNoReachableLinks(): void
    {
        $xpath = $this->render([
            'label' => 'Stáhnout PDF',
            'downloadUrl' => '/smlouvy/print/1?download=1',
            'openUrl' => '/smlouvy/print/1',
            'disabled' => true,
            'dataTest' => 'contract-pdf',
        ]);

        $download = $this->single($xpath, '//a[@data-test="contract-pdf"]');
        self::assertFalse($download->hasAttribute('href'));
        self::assertSame('true', $download->getAttribute('aria-disabled'));
        self::assertStringContainsString('disabled', $download->getAttribute('class'));
        self::assertTrue($this->single($xpath, '//button[@data-test="contract-pdf-toggle"]')->hasAttribute('disabled'));
    }

    public function testFormButtonsDownloadByDefaultAndOpenInNewTab(): void
    {
        $form = new Form();
        $form->addSubmit('massPrintSend');
        $form->addSubmit('massOpenPrintSend');

        $xpath = $this->render([
            'label' => 'Stáhnout vybrané doklady PDF',
            'downloadButton' => $form['massPrintSend'],
            'openButton' => $form['massOpenPrintSend'],
            'compact' => true,
            'dataTest' => 'chits-mass-pdf',
        ]);

        $download = $this->single($xpath, '//button[@data-test="chits-mass-pdf"]');
        self::assertSame('submit', $download->getAttribute('type'));
        self::assertSame('massPrintSend', $download->getAttribute('name'));
        self::assertFalse($download->hasAttribute('formtarget'));

        $open = $this->single($xpath, '//button[@data-test="chits-mass-pdf-open"]');
        self::assertSame('submit', $open->getAttribute('type'));
        self::assertSame('massOpenPrintSend', $open->getAttribute('name'));
        self::assertSame('_blank', $open->getAttribute('formtarget'));
    }

    /** @param array<string, mixed> $parameters */
    private function render(array $parameters): DOMXPath
    {
        $html = (new Engine())->renderToString(self::Template, $parameters);

        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_NOERROR);

        return new DOMXPath($document);
    }

    private function countNodes(DOMXPath $xpath, string $query): int
    {
        $nodes = $xpath->query($query);
        self::assertNotFalse($nodes, $query);

        return $nodes->length;
    }

    private function single(DOMXPath $xpath, string $query): DOMElement
    {
        $nodes = $xpath->query($query);
        self::assertNotFalse($nodes, $query);
        self::assertSame(1, $nodes->length, $query);

        $node = $nodes->item(0);
        self::assertInstanceOf(DOMElement::class, $node);

        return $node;
    }
}
