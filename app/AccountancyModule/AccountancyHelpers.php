<?php
/**
 * @author sinacek
 */
abstract class AccountancyHelpers extends Object {
    
    public static function eventLabel($s){
        if($s == "draft")
            return '<span class="label label-success">Rozpracováno</span>';
        elseif($s == "closed"){
            return '<span class="label label-info">Uzavřeno</span>';
        } else {
            return '<span class="label label-inverse">Zrušeno</span>';
        }
        //draft, closed, cancelled
        
    }

//    public static function datNar($s) {
//        $y = substr($s, 0, 2);
//        $m = substr($s, 2, 2);
//        $d = substr($s, 4, 2);
//        if($m > 50)
//            $m = ($m-50);
////        if($y < 30)
////            $y +=2000;
////        else
////            $y +=1900;
//
//        return self::fDatNar($d)."/". self::fDatNar($m)."/".self::fDatNar($y);
//    }
//
//    private  static function fDatNar($i) {
//    $i = (int) $i;
//    if($i< 10)
//        return "0".$i;
//    else
//        return $i;
//    }
//
//    public static function getNameOfOddily($in, $selected = false){
//        $oddily = Environment::getApplication()->getPresenter()->oddily;
//        $s = "";
//        foreach ($in as $key => $value)
//            if((!$selected || $value) && array_key_exists($key, $oddily))
//                    $s .= $oddily[$key].", ";
//        return substr($s, 0, -2);
//    }
//
//
//
//    public static function paragonType($string = "") {
//        switch ($string){
//        case "prijem":
//            $r = "pp";
//            break;
//        case "potraviny":
//            $r = "t";
//            break;
//        case "jizdne":
//            $r = "j";
//            break;
//        case "najem":
//            $r = "n";
//            break;
//        case "drobnosti":
//            $r = "m";
//            break;
//        case "sluzby":
//            $r = "s";
//            break;
//        case "prevod":
//            $r = "pr";
//            break;
//        default :
//            $r="un";
//        }
//        return $r;
//    }


    //return category of paragon
    public static function pCat($string = "") {
        //@todo dodělat
        return $string;
        
        switch ($string){
        case "pp":
            $r = "příjem";
            break;
        case "t":
            $r = "potraviny";
            break;
        case "j":
            $r = "jizdné";
            break;
        case "n":
            $r = "nájem";
            break;
        case "m":
            $r = "materiál";
            break;
        case "s":
            $r = "služby";
            break;
        case "pr":
            $r = "převod";
            break;
        default :
            $r="un";
        }
        return $r;
    }

    /**
     * převádí zadané číslo na solvní řetězec
     * @param int $price
     * @return string 
     */
    public static function priceToString($price) {
        //@todo ošetření správného tvaru
        
        $_jednotky = array(
                0  => "",1 => "jedna",2 => "dva",3 => "tři",4 => "čtyři", 5 => "pět", 6 => "šest",7 => "sedm", 8 => "osm", 9 => "devět",10 => "deset",
                11 => "jedenáct", 12 => "dvanáct",13 => "třináct",14 => "čtrnáct",15 => "patnáct",16 => "šestnáct",17 => "sedmnáct",18 => "osmnáct",19 => "devatenáct",
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
                5 => "pětset", 6 => "šestset",7 => "sedmset",8 => "osmset",9 => "devětset",
        );
        $_tisice = array(
                0 => "", 1 => "jedentisíc", 2 => "dvatisíce", 3 => "třitisíce", 4 => "čtyřitisíce",
        );

        $string = "";
        $parts = explode(".", $price, 2); //0-pred 1-za desitou čárkou
        $numbers = array_reverse(str_split($parts[0]));
        
        if(count($numbers) >6 )
            return "PŘÍLIŠ VYSOKÉ ČÍSLO";
        
        for($i = count($numbers); $i<6; ++$i){ //doplnění nezaplněných řádu
            $numbers[$i] = 0;
        }
        
        //tisice    
        $nTisice = (int)($numbers[5].$numbers[4].$numbers[3]);
        if($nTisice <= 4){
            $string .= $_tisice[$numbers[3]];
        } elseif($nTisice < 20) {
            $string .= $_jednotky[(int)($numbers[4].$numbers[3])]."tisíc";
        } elseif( $nTisice < 100) {
            $string .= $_desitky[$numbers[4]].$_jednotky[$numbers[3]]."tisíc";
        } else {
            $string .= $_sta[$numbers[5]].$_desitky[$numbers[4]].$_jednotky[$numbers[3]]."tisíc";
        }
        
        
        //sta
        $string .= $_sta[$numbers[2]];
        
        //desitky a jednotky
        $nDesitky = (int)($numbers[1].$numbers[0]);
        if($nDesitky < 20)
            $string .= $_jednotky[$nDesitky];
        else
            $string .= $_desitky[$numbers[1]].$_jednotky[$numbers[0]];

        //desetinná část
        if(isset($parts[1])) {
            if(strlen($parts[1]) < 2)
                $parts[1] .= "0";
            $string .= ".".$parts[1];
        }
        return ucfirst($string);
        //return mb_convert_case(mb_substr($string,0,1),MB_CASE_UPPER,"UTF-8").mb_substr($string,1);
    }

}
