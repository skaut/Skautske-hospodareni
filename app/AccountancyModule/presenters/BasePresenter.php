<?php

/**
 * @author sinacek
 */

class Accountancy_BasePresenter extends BasePresenter {
    
    protected $service;

    protected function startup() {
        parent::startup();

        if (!$this->user->isAllowed("ucetnictvi", "view")) {
            $this->accessFail();
        }

//        $dataStorage = new Ucetnictvi_BaseStorage();
//        $this->categoriesIn = $dataStorage->getParagonCategoriesIn();
//        $this->categoriesOut = $dataStorage->getParagonCategoriesOut();
//        $this->oddily = $dataStorage->getOddily();
//
//        $this->template->registerHelper('oddily', 'UcetnictviHelpers::getNameOfOddily'); //v before render nefunguje pro action to pdf
    }

    function beforeRender() {
        parent::beforeRender();
        $this->template->registerHelper('priceToString', 'UcetnictviHelpers::priceToString');
        $this->template->registerHelper('datNar', 'UcetnictviHelpers::datNar');
        //$this->template->registerHelper('pCat', 'UcetnictviHelpers::pCat');
        //dump($this->model->vyprava->getId());
    }

//    public function getCategories() {
//        return array_merge($this->categoriesIn, $this->categoriesOut);
//    }
//
//    /**
//     * @param string $d
//     * @return timestamp
//     * @deprecated
//     */
//    static function dateToTime($d) {
//        $a = explode("-", $d);
//        return mktime(0, 0, 0, $a[1], $a[2], $a[0]);
//    }
// 
//    /**
//     * ulozi akci
//     */
//    function handleSave() {
//        $this->saveAkce();
//        $this->redirect("this");
//    }
//
//    /**
//     * resetuje akci na defaultni nastaveni
//     */
//    function handleClear() {
//        $this->service->clear();
//        $this->flashMessage("Neuložené informace byly smazány.");
//        $this->redirect('default');
//    }
//
//    /**
//     * ulozi aktualni akci a odemkne zamek
//     */
//    function handleSaveUnlock() {
//        $this->saveAkce();
//        $this->service->unlock();
//        $this->service->clear();
//        $this->redirect("default");
//    }
//
//    protected function saveAkce($isFm = true) {
//        $ret = $this->service->save();
//        $fmstatus = $fm = "";
//        if ($isFm) {
//            switch ($ret) {
//                case "insert":
//                    $fm = "Výprava byla úspěšně uložena.";
//                    break;
//                case "update":
//                    $fm = "Výprava byla úspěšně upravena.";
//                    break;
//                case "noinsert":
//                    $fm = "Výpravu se nepodařilo uložit";
//                    $fmstatus = "fail";
//                    break;
//                case "noupdate":
//                    $fm = "Výprava nebyla změněna";
//                    break;
//                case "noaccess":
//                    $fm = "Nemáte právo upravovat tento záznam";
//                    $fmstatus = "fail";
//                    break;
//            }
//        }
//        $this->flashMessage($fm, $fmstatus);
//    }

    //vrati routy pro modull
    static function createRoutes($router, $prefix ="") {
//
//        $router[] = new Route($prefix . 'Ucetnictvi/<presenter>/<action>', array(
//                    'module' => "Ucetnictvi",
//                    'presenter' => 'Default',
//                    'action' => 'default',
//                ));
    }

}
