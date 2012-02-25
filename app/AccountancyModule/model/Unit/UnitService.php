<?php

class UnitService extends BaseService {

    public function __construct() {
        $this->table = new UnitTable();
    }

    public function create($id) {
        $unit = $this->getDetail($id);
        return $this->table->create($unit, $user);
    }

    public function getDetail($id = NULL) {
        if ($id == NULL) {
            $id = $this->skautIS->getUnitId();
        }

        try {
            return $this->skautIS->org->UnitDetail(array("ID" => $id));
        } catch (SoapFault $exc) {
            throw new BadRequestException("Nemáte oprávnění pro získání informací o jednotce.");
        }
    }

    public function isCreated($id) {
        return $this->table->get($id);
    }

    public function getCaterories() {
        //@todo předělat na db
        $in = array(
            "pp" => "Příjmový",
            "di" => "Dotace", //Dotace In => di
        );
        $out = array(
            "t" => "Potraviny",
            "j" => "Jízdné",
            "n" => "Nájemné",
            "m" => "Materiál (Drobné vybavení)",
            "s" => "Služby",
            "pr" => "Převod do odd. pokladny",
            "do" => "Dotace",
            "un" => "Neurčeno",
        );
        return array("in"=>$in, "out"=>$out);
    }

}