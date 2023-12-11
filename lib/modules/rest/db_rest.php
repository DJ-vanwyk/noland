<?php

class DBRestClass
{

    function __construct()
    {
        switch ($_SERVER["REQUEST_METHOD"]) {
            case 'GET':
                $this->get();
                break;
            case 'POST':
                $this->create();
                break;
            case 'PATCH':
                $this->update();
                break;
            case 'DELETE':
                $this->delete();
                break;
            default:
                throw new Exception("Unsupported Request Method");
                break;
        }
    }

    function get()
    {
        $json = json_decode($_GET['where'], true);

        function getPathSegments($path)
        {
            $path = trim($path, "/");
            $segments = explode("/", $path);
            return $segments;
        }

        $segments = getPathSegments($_SERVER['PATH_INFO']);
        $jsonWhere = $this->JsonToWhere($json);
        $where = "WHERE ";
        $where .= $jsonWhere['where'];
        $results = $this->getTableData($segments[1], $segments[2], $where, $jsonWhere['variables']);

        echo json_encode($results);
    }

    function create()
    {
    }

    function update()
    {
    }

    function delete()
    {
    }

    /* --------------------------------- Extras --------------------------------- */

    function getTableData($db, $table, $where = "", $var = [])
    {
        if (!hasDatabase($db))
            throw new Exception("Database $db, does not exist");

        if (!hasTable($table))
            throw new Exception("Table $table, does not exist in $db");

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
        $sql = "SELECT $columnStr FROM $db.$table $joins $where";
        $result = runSQL($sql, $var);
        $data = [];


        foreach ($result as $row) {
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
                    "column" => "`$db`.`$table`.`{$value['Field']}`"
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
        $sql = "SELECT TABLE_SCHEMA,
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
            $relationships[] = $row;
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

    function JsonToWhere($json)
    {

        $where = "";
        $variables = [];

        if (!isset($json["type"]))
            throw new Exception("Invalid where clause, no 'type' has been set");


        if ($json["type"] == 'condition') {

            if (!isset($json["operator"]))
                throw new Exception("Invalid where clause, no 'operator' has been set");

            if (!isset($json["db"]))
                throw new Exception("Invalid where clause, no 'db' has been set");

            if (!isset($json["table"]))
                throw new Exception("Invalid where clause, no 'table' has been set");

            if (!isset($json["column"]))
                throw new Exception("Invalid where clause, no 'column' has been set");

            if (!hasTable($json["table"]))
                throw new Exception("Invalid where clause, table {$json["table"]} does not exist");

            if (!hasColumn($json["table"], $json["column"]))
                throw new Exception("Invalid where clause, column {$json["column"]} does not exist in table {$json["table"]}");


            $where .= "`{$json["db"]}`.`{$json["table"]}`.`{$json["column"]}`";

            switch ($json["operator"]) {
                case '=':
                    $where .= ' =';
                    break;
                case '>':
                    $where .= ' >';
                    break;
                case '<':
                    $where .= ' <';
                    break;
                case '>=':
                    $where .= ' >=';
                    break;
                case '<=':
                    $where .= ' <=';
                    break;
                case '<>':
                    $where .= ' <>';
                    break;
                case '!=':
                    $where .= ' <>';
                    break;
                case 'like':
                    $where .= ' LIKE';
                    break;
                default:
                    throw new Exception("Invalid where clause, invalid operator");
                    break;
            }

            $where .= " ?";
            $variables = [...$variables, $json['value']];

            return [
                "where" => $where,
                "variables" => $variables
            ];
        }

        $conjunction = "";

        if ($json["type"] == 'and') {
            $conjunction = "and";
        }

        if ($json["type"] == 'or') {
            $conjunction = "or";
        }

        if ($conjunction !== '') {
            $conjunctionValue = $json["value"];

            foreach ($conjunctionValue as $key => $value) {
                $result = $this->JsonToWhere($value);
                $where .= "({$result['where']}) $conjunction ";
                $variables = [...$variables, ...$result['variables']];
            }

            $where = trim($where, " AND ");

            return [
                "where" => $where,
                "variables" => $variables
            ];
        }

        throw new Exception("Invalid Where clause, type is invalid");
    }
}
