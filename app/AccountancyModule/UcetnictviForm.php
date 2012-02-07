<?php

abstract class UcetnictviForm extends Component implements IFormControl {

    //comapare 2 dates
    static function isAfter($to, $from) {
            $to = $to->value;//DatePicker to DateTime53
            if (strtotime($to->format('Y-m-d H:i:s')) < strtotime($from->format('Y-m-d H:i:s')))
                return false;
            return true;
        }

    //->addRule("UcetnictviForm::isInList", "Vedoucí akce musí být vybrán z nabídky", $this->umodel);
    public static function isInList(IFormControl $control, UserService $uservice) {
        if($uservice->get($control->value))
            return true;
        return false;
    }

}