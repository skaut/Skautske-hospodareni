<?php

use Nette\Object;

/**
 * @author sinacek
 */
abstract class AccountancyHelpers extends Object {

    /**
     * loader na všechny helpery
     * @param type $helper
     * @return type 
     */
    public static function loader($helper) {
        if (method_exists(__CLASS__, $helper)) {
            return callback(__CLASS__, $helper);
        }
    }
    
    /*
     * zobrazení stavu ve formě ikony
     */
    public static function eventStateLabel($s) {
        if ($s == "draft")
            return '<span class="label label-warning hidden-phone">Rozpracováno</span>'
            .'<span class="label label-warning visible-phone">Rozprac.</span>';
        elseif ($s == "closed") {
            return '<span class="label label-success">Uzavřeno</span>';
        } else {
            return '<span class="label label-inverse">Zrušeno</span>';
        }
        //draft, closed, cancelled
    }

    /*
     * zobrazuje popisky stavů u táborů
     */
    public static function campStateLabel($s) {
        switch ($s) {
            case "draft":
                return '<span class="label label-warning">Rozpracováno</span>';
            case "approvedParent":
                return '<span class="label label-info">Schválený střediskem</span>';
            case "approvedLeader":
                return '<span class="label label-info">Schválený vedoucím</span>';
            case "real":
                return '<span class="label label-success">Skutečnost odevzdána</span>';
            default:
                return '<span class="label label-inverse">Zrušený</span>';
                break;
        }
    }

    /**
     * 
     * @param type $s - NULL|DibiDateTime
     * @return string 
     */
    public static function commandState($s) {
        switch ($s) {
            case NULL:
                return '<span class="label label-warning hidden-phone">Rozpracovaný</span>'
            .'<span class="label label-warning visible-phone">Rozpr.</span>';
            default :
                return '<span class="label label-success" title="Uzavřeno dne: '. $s->format("j.n.Y H:i:s").'">Uzavřený</span>';
                break;
        }
    }

    /**
     * formátuje číslo na částku
     * @param type $price
     * http://prirucka.ujc.cas.cz/?id=786
     * @return int 
     */
    public static function price($price, $full = true) {
        if ($price == NULL)
            return 0;
        if ($price == "&nbsp;")
            return $price;
        $decimals = $full ? 2 : 0;
        //if (stripos($price, "."))
            return number_format($price, $decimals, ",", " ");
        //return $price;
    }
    
    public static function postCode($oldPsc) {
        $psc = preg_replace("[^0-9]", "", $oldPsc);
        if(strlen($psc) == 5)
            return substr($psc, 0, 3) . " ". substr($psc, -2);
        return $oldPsc;
//        number_format($psc, 0, "", " ");
    }

    /**
     * převádí zadané číslo na slovní řetězec
     * @param int $price
     * @return string 
     */
    public static function priceToString($price) {
        //@todo ošetření správného tvaru

        $_jednotky = array(
            0 => "", 1 => "jedna", 2 => "dva", 3 => "tři", 4 => "čtyři", 5 => "pět", 6 => "šest", 7 => "sedm", 8 => "osm", 9 => "devět", 10 => "deset",
            11 => "jedenáct", 12 => "dvanáct", 13 => "třináct", 14 => "čtrnáct", 15 => "patnáct", 16 => "šestnáct", 17 => "sedmnáct", 18 => "osmnáct", 19 => "devatenáct",
        );

        $_desitky = array(
            2 => "dvacet",
            3 => "třicet",
            4 => "čtyřicet",
            5 => "padesát",
            6 => "šedesát",
            7 => "sedmdesát",
            8 => "osmdesát",
            9 => "devadesát",
        );
        $_sta = array(
            0 => "", 1 => "jednosto", 2 => "dvěstě", 3 => "třista", 4 => "čtyřista",
            5 => "pětset", 6 => "šestset", 7 => "sedmset", 8 => "osmset", 9 => "devětset",
        );
        $_tisice = array(
            0 => "", 1 => "jedentisíc", 2 => "dvatisíce", 3 => "třitisíce", 4 => "čtyřitisíce",
        );

        $string = "";
        $parts = explode(".", $price, 2); //0-pred 1-za desitou čárkou
        $numbers = array_reverse(str_split($parts[0]));

        if (count($numbers) > 6)
            return "PŘÍLIŠ VYSOKÉ ČÍSLO";

        for ($i = count($numbers); $i < 6; ++$i) { //doplnění nezaplněných řádu
            $numbers[$i] = 0;
        }

        //tisice    
        $nTisice = (int) ($numbers[5] . $numbers[4] . $numbers[3]);
        if ($nTisice <= 4) {
            $string .= $_tisice[$numbers[3]];
        } elseif ($nTisice < 20) {
            $string .= $_jednotky[(int) ($numbers[4] . $numbers[3])] . "tisíc";
        } elseif ($nTisice < 100) {
            $string .= $_desitky[$numbers[4]] . $_jednotky[$numbers[3]] . "tisíc";
        } else {
            $string .= $_sta[$numbers[5]] . $_desitky[$numbers[4]] . $_jednotky[$numbers[3]] . "tisíc";
        }


        //sta
        $string .= $_sta[$numbers[2]];

        //desitky a jednotky
        $nDesitky = (int) ($numbers[1] . $numbers[0]);
        if ($nDesitky < 20)
            $string .= $_jednotky[$nDesitky];
        else
            $string .= $_desitky[$numbers[1]] . $_jednotky[$numbers[0]];

//         //desetinná část
//        if (isset($parts[1])) {
//            if (strlen($parts[1]) < 2)
//                $parts[1] .= "0";
//            $string .= "," . $parts[1];
//        }
//        return ucfirst($string);
        return mb_convert_case(mb_substr($string,0,1),MB_CASE_UPPER,"UTF-8").mb_substr($string,1);
    }

}
