<?php

class DataMapper
{

    function getTableData($db, $table, $relatedTables = [])
    {

        $resp = $this->generateQuarry($db, $table);

        $joins = trim($resp['joins'], " ");
        $columns = $resp['columns'];
        $columnStr = "";

        foreach ($columns as $key => $value) {
            $aliase = $value['aliase'];
            $column = $value['column'];
            $columnStr .= ", $column as $aliase";
        }

        $columnStr = trim($columnStr, ", ");
        $sql = "SELECT $columnStr FROM $db.$table $joins";
        $result = runSQL($sql);
        $data = [];


        foreach ($result as $row) {
            $row = $this->cleanRow($row);
            $newRow = [];

            foreach ($row as $key => $value) {
                $splitCol = explode("$", $key);
                $splitColCount = count($splitCol);

                $splitColStr = "";

                for ($i = 0; $i < $splitColCount; $i++) {
                    $splitColStr .= "['{$splitCol[$i]}']";
                }

                $newRow = $this->insertUsingKeys($newRow, $splitCol, $value);
            }

            $data[] = $newRow;
        }

        return $data;
    }

    /* ----------------------------------- -- ----------------------------------- */

    private function generateQuarry($db, $table)
    {

        $relationships = $this->getRelationships($db, $table);
        $columns = getDBColumns("$db.$table");

        $returnCols =  [];
        $returnJoins = "";


        foreach ($columns as $key => $value) {

            $found = null;


            foreach ($relationships as $relationship) {
                if ($value['Field'] == $relationship['COLUMN_NAME']) {
                    $found = $relationship;
                    break;
                }
            }

            if (!$found) {
                $returnCols[] = [
                    "aliase" => $value['Field'],
                    "column" => "$db.$table.{$value['Field']}"
                ];
            } else {
                $ref = $this->generateQuarry($db, $found['REFERENCED_TABLE_NAME']);
                $refCol = $ref['columns'];

                $returnJoins .= " LEFT JOIN $db.{$found['REFERENCED_TABLE_NAME']} ON $db.{$found['REFERENCED_TABLE_NAME']}.{$found['REFERENCED_COLUMN_NAME']} = $db.$table.{$found['COLUMN_NAME']}";
                $returnJoins .= $ref['joins'];

                foreach ($refCol as $col) {

                    // echo "<pre>{$value['Field']}".print_r($col, true)."</pre>";

                    $returnCols[] =  [
                        "aliase" => $value['Field'] . "$" . $col['aliase'],
                        "column" => $col["column"]
                    ];
                }
            }
        }

        return [
            "columns" => $returnCols,
            "joins" => $returnJoins
        ];
    }

    private function getRelationships($db, $table)
    {
        $sql = "SELECT
			TABLE_SCHEMA,
			TABLE_NAME,
			COLUMN_NAME,
			REFERENCED_TABLE_NAME,
			REFERENCED_COLUMN_NAME
		FROM
			INFORMATION_SCHEMA.KEY_COLUMN_USAGE
		WHERE
			TABLE_SCHEMA = '$db' AND TABLE_NAME = '$table' AND CONSTRAINT_NAME LIKE '%fk_%';";

        $result = runSQL($sql);

        $relationships = [];

        foreach ($result as $row) {
            $relationships[] = $this->cleanRow($row);
        }

        return $relationships;
    }

    private function insertUsingKeys($arr, $keys, $value)
    {
        // we're modifying a copy of $arr, but here
        // we obtain a reference to it. we move the
        // reference in order to set the values.
        $a = &$arr;

        while (count($keys) > 0) {
            // get next first key
            $k = array_shift($keys);

            // if $a isn't an array already, make it one
            if (!is_array($a)) {
                $a = array();
            }

            // move the reference deeper
            $a = &$a[$k];
        }
        $a = $value;

        // return a copy of $arr with the value set
        return $arr;
    }
    
    private function cleanRow($row)
    {
        foreach ($row as $Key => $Value) {
            if (is_numeric($Key)) {
                unset($row[$Key]);
            }
            if ($Key == 'password') {
                unset($row[$Key]);
            }
        }

        return $row;
    }
}
