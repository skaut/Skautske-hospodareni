<?php

/**
 * @author sinacek
 */
class Accountancy_ExportPresenter extends Accountancy_BasePresenter {
    
    function startup() {
        parent::startup();
        
    }
    
    
    
//    function actionVyuctovani() {
//        if ($this->service->getParagony()->isInMinus()) {
//            $this->flashMessage("Máte zápornou hodnotu v pokladní knize. Upravte ji a pak ji mužete exportovat.", "fail");
//            $this->redirect("Default:akce");
//        }
//        $paragony = $this->service->getParagony();
//
//        $categoryPrices = $paragony->getCategoriesPrice();
//        if ($categoryPrices["un"] && !Environment::getVariable("ucet_undef", true)) {
//            $this->flashMessage("Nejde vytvořit vyučtování. Máte paragon s neurčeným typem.", "fail");
//            $this->redirect("Default:akce");
//        }
//        //dump($this->service->getParagony());
//        $akceName = $this->service->getAkce()->name;
//        $prefix = ($akceName != "") ? Strings::webalize(Strings::truncate($akceName, 10)) : date("j-n-Y");
//        $filename = $prefix . "-vyuctovani-akce.pdf";
//
//        $template = $this->template;
//        $template->setFile(dirname(__FILE__) . '/../templates/Export/vyuctovani.latte');
//
//        $template->leader = $this->service->getAkce()->leader;
//        $template->vyprava = $this->service->getAkce();
//        $from = $this->service->getAkce()->from->getTimestamp();
//        $to = $this->service->getAkce()->to->getTimestamp();
//
//        $template->nameOJ = Environment::getVariable("ucet_shortName", "");
//        $template->oddily = $this->oddily;
//        $template->actionName = $this->service->getAkce()->name;
//        $template->from = $from;
//        $template->to = $to;
//        $template->daysOfAction = ($to - $from) / 86400 + 1; //60 * 60 * 24 + 1
//        $template->place = ($place = $this->service->getAkce()->place) ? $place : "&nbsp;";
//        $template->ucastniciCnt = $this->service->getUcastnici()->getCount();
//        $template->ucastniciCnt26 = $this->service->getUcastnici()->getCount(26, $to);
//
//        $categoryPrices = $paragony->getCategoriesPrice();
//        $template->cat = $categoryPrices;
//
//        $template->list = $this->service->getUcastnici()->getAll();
//
//        $files = new FileService();
//        $files->makePdf($template, $filename);
//    }
//    /**
//     * vygeneruje pdf s hromadným příjmovým dokladem
//     */
//    function actionHpd() {
//        $akceName = $this->service->getAkce()->name;
//        $prefix = ($akceName != "") ? Strings::webalize(Strings::truncate($akceName, 20)) : date("j-n-Y");
//        $filename = $prefix."-hpd.pdf";
//
//        $template = $this->template;
//        $template->registerHelper('priceToString', 'UcetnictviHelpers::priceToString');
//        $template->setFile(dirname(__FILE__) . '/../templates/Ucastnik/hpd.export.latte');
//
//        $template->totalPrice = $this->totalIn();
//        $template->date       = $this->ucastnici->getDate();
//        $template->prijal     = $this->ucastnici->getPrijal();
//        $template->pokladnik  = $this->ucastnici->getPokladnik();
//        $template->schvalil   = Environment::getVariable('ucas_schvalil', "");
//        $template->organizace = Environment::getVariable('ucet_organizace', "");
//        $template->list       = $this->ucastnici->getAll();
//
//        $files = new FileService();
//        $files->makePdf($template, $filename);
//    }
//
//    /**
//     * vygeneruje pdfko se seznam účastníků
//     */
//    function actionSeznamUcastniku() {
//        $akceName = $this->service->getAkce()->name;
//        $prefix = ($akceName != "") ? Strings::webalize(Strings::truncate($akceName, 20)) : date("j-n-Y");
//        $filename = $prefix."-seznam-ucastniku.pdf";
//
//        $template = $this->template;
//        $template->setFile(dirname(__FILE__) . '/../templates/Ucastnik/seznamUcastniku.export.latte');
//        $template->registerHelper('datNar', 'UcetnictviHelpers::datNar');
//        $template->nazevAkce = $akceName;
//        $template->list = $this->Uservice->getByIDs(array_keys($this->ucastnici->getAll()));
//        $files = new FileService();
//        $files->makePdf($template, $filename);
//    }
//    function actionExport() {
//        $list = $this->paragony->getAll(); //IGNORUJE pouzeVydaje
//        //$list = $this->paragony->getAll(TRUE);
//        if (empty($list)) {
//            $this->flashMessage("Nejsou žádné paragony k exportování.", "fail");
//            $this->redirect("Default:akce");
//        }
//
//        $akceName = $this->service->getAkce()->name;
//        $prefix = ($akceName != "") ? Strings::webalize(Strings::truncate($akceName, 20)) : date("j-n-Y");
//        $filename = $prefix . "-paragony.pdf";
//        $template = $this->template;
//        $template->registerHelper('priceToString', 'UcetnictviHelpers::priceToString');
//        $template->registerHelper('pCat', 'UcetnictviHelpers::pCat');
//        $template->setFile(dirname(__FILE__) . '/../templates/Paragon/export.latte');
//        
//        foreach ($list as $value)
//            if ($value->type != "pp")
//                $newList[] = $value;
//        $this->template->list = $newList;
//        $this->template->organizace = Environment::getVariable("par_organizace", "");
//        $this->template->parPrefix = $this->service->getAkce()->parPrefix;
//        $files = new FileService();
//        $files->makePdf($template, $filename);
//    }
//
//    function actionPkniha() {
//        if ($this->paragony->isInMinus()) {
//            $this->flashMessage("Máte zápornou hodnotu v pokladní knize. Upravte ji a pak ji mužete exportovat.", "fail");
//            $this->redirect("Default:akce");
//        }
//
//        $categoryPrices = $this->paragony->getCategoriesPrice();
//        if ($categoryPrices["un"] && !Environment::getVariable("ucet_undef", true)) {
//            $this->flashMessage("Nejde vytvořit vyučtování. Máte paragon s neurčeným typem.", "fail");
//            $this->redirect("Default:akce");
//        }
//
//        $vypravaName = $this->service->getAkce()->name;
//        $prefix = ($vypravaName != "") ? Strings::webalize(Strings::truncate($vypravaName, 20)) : date("j-n-Y");
//        $filename = $prefix . "-pokladni-kniha.pdf";
//        $template = $this->template;
//        $template->setFile(dirname(__FILE__) . '/../templates/Paragon/pkniha.export.latte');
//
//        $template->list = $this->paragony->getAll();
//        $template->parPrefix = $this->service->getAkce()->parPrefix;
//        $template->akceName = $this->service->getAkce()->name;
//        $template->pokladnik = $this->service->getUcastnici()->getPokladnik();
//
//        $files = new FileService();
//        $files->makePdf($template, $filename);
//
//        if ($this->isAjax()) {
//            $this->invalidateControl("flashmesages");
//        } else {
//            $this->redirect('this');
//        }
//    }

 
    
}
