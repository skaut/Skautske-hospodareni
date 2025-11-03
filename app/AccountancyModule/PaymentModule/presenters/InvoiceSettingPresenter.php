<?php

declare(strict_types=1);

namespace App\AccountancyModule\PaymentModule;

use Component\Forms\BaseForm;
use Nette\Forms\Controls\SubmitButton;
use Nette\Utils\ArrayHash;
use Throwable;
use Utility\Ares\ViAresParser;

use function dumpe;

class InvoiceSettingPresenter extends BasePresenter
{
    public function actionDefault(): void
    {
        dumpe($this->unitService->getOfficialUnit());
    }

    public function renderPdf(): void
    {
        // Toto pole můžete předat do Latte šablony, např.: $latte->render('faktura.latte', $data);

        $data = [
            // Data dodavatele (proměnná {$supplier->...})
            'supplier' => [
                'name' => 'Webwings s.r.o.', // [cite: 6]
                'street' => 'Husova 594/6', // [cite: 7]
                'cityZip' => '60200 Brno', // [cite: 8]
                'country' => 'Česká republika', // [cite: 9]
                'ic' => '29309743', // [cite: 10]

                'mobil' => '+420776036874', // [cite: 11]
                'email' => 'obchod@webwings.cz', // [cite: 11]
                'www' => 'www.webwings.cz', // [cite: 11]

                'bankName' => 'Fio banka, a.s.', // [cite: 17]
                'bankAccount' => '2300228890/2010', // [cite: 18]
                'iban' => 'CZ5420100000002300228890', // [cite: 19]
                'bic' => 'FIOBCZPPXXX', // [cite: 20]

                'vatStatusText' => 'Neplátce DPH', // [cite: 29]
            ],

            // Data odběratele (proměnná {$customer->...})
            'customer' => [
                'name' => 'CALP CONCEPT a.s.', // [cite: 4, 35]
                'street' => 'Příkop 843/4', // [cite: 5, 36]
                'cityZip' => '60200 Brno', // [cite: 5, 36]
                'country' => 'Česká republika', // [cite: 5, 37]
                'ic' => '29290066', // [cite: 34]
                'dic' => 'CZ29290066', // [cite: 34]
            ],

            // Data faktury (proměnná {$invoice->...})
            'invoice' => [
                'number' => 'VF1-0035/2012', // [cite: 33]
                'variableSymbol' => '100352012', // [cite: 21]
                'constantSymbol' => '0008', // [cite: 23]
                'specificSymbol' => '', // [cite: 24] (V PDF nebylo uvedeno)
                'paymentMethod' => 'Hotově', // [cite: 26]

                // Pro datumy je nejlepší použít ISO formát, Latte filtr |date je pak správně naformátuje
                'dateIssued' => '2012-09-24', // [cite: 40]
                'dateDue' => '2012-10-08', // [cite: 40]

                'contractNumber' => '', // [cite: 32] (V PDF nebylo uvedeno)
                'orderNumber' => '', // [cite: 32] (V PDF nebylo uvedeno)
                'projectNumber' => '', // [cite: 32] (V PDF nebylo uvedeno)

                'shippingMethod' => '', // [cite: 27] (V PDF nebylo uvedeno)
                'destination' => '', // [cite: 31] (V PDF nebylo uvedeno)

                // Položky faktury (smyčka {foreach $invoice->items...})
                'items' => [
                    [
                        'description' => 'Zpracování projektu ""Obrana řidiče"" fáze: závěrečné nasazeni projektu', // [cite: 28]
                        'quantity' => 1.00, // [cite: 28]
                        'unit' => 'ks', // (V PDF nebylo explicitně uvedeno[cite: 28], 'ks' je běžný zástupný symbol)
                        'unitPrice' => 55000.00, // [cite: 28]
                        'totalPrice' => 55000.00, // [cite: 28]
                    ],
                    // Zde by mohly být další položky
                ],

                // Celkové částky
                'totalAmount' => 55000.00, // [cite: 38]
                'deposits' => 0.00, // [cite: 38]
                'amountDue' => 55000.00, // [cite: 38]
            ],

            // Uživatel, který tiskne (proměnná {$user->...})
            'user' => [
                'name' => 'Martin Bargl', // [cite: 45]
            ],
        ];

        $param = ArrayHash::from($data);

        $this->template->setParameters(
            (array) $param,
        );
    }

    public function createComponentForm(): BaseForm
    {
        $form = new BaseForm();
        $unit = $this->unitService->getOfficialUnit();
        $form->addSelect('unitId', 'Jednotka', [$unit->getDisplayName()])->setDisabled(true);
        $form->addText('companyNumber', 'ICO');
        $form['companyNumber']->setDefaultValue($unit->getIc());
        $form->addSubmit('getContactInfo', 'Získat data z registru')->onClick[] = [$this, 'handleGetContactInfo'];
        $form['getContactInfo']->setHtmlAttribute('class', 'btn btn-primary ajax');
        $form['getContactInfo']->setValidationScope([$form['companyNumber']]);
        $form->addText('name', 'Název')->addRule(BaseForm::FILLED, 'Nazev musi byt vyplněn');
        $form->addText('address', 'Adresa')->addRule(BaseForm::FILLED, 'Adresa');
        $form->addText('city', 'Město')->addRule(BaseForm::Filled, 'Město');
        $form->addText('zipcode', 'PSC');
        $form->addText('vatNumber', 'DIC');
        $form->addCheckbox('vatPayer', 'Platce DPH');
        $form->addSubmit('send', 'Odeslat');
        $form->onSuccess[] = [$this, 'handleSend'];

        return $form;
    }

    public function handleSend(BaseForm $form): void
    {
        // dumpe('form',$form->isSubmitted()->name);
    }

    public function handleGetContactInfo(SubmitButton $button): void
    {
        $values = $button->getForm()->getValues();

        try {
            $companyInfo = (new ViAresParser())->getAres($values->companyNumber);
        } catch (Throwable $e) {
            dumpe($e);
        }

        $this['form']->setValues($companyInfo->toArray());

        if ($this->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }
    }
}
