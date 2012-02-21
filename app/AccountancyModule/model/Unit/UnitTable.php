<?php

class UnitTable extends BaseTable {
    
    public function get($id) {
        return dibi::fetch("SELECT * FROM [" . self::TABLE_UNITS . "]  WHERE id = %s LIMIT 1", $id);
    }
    
    public function create($data) {
        //dump($data);die();
        
        $date = date("Y-m-d");
        $ins = array(
            "id"=>$data->ID,
            "name"=>$data->SortName,
            "created" => $date,
                );
        return dibi::query("INSERT INTO [" . self::TABLE_UNITS . "] %v", $ins);
    }
    
    
    
}