<?php

function runSQL( $sql,  $variables = [])
{
    if (isset($GLOBALS['db']) == false) {
        throw new Exception("Database not initialized");
    }

    $sql = str_replace(["\t", "\n"], ' ', $sql);

    $statementType = strtoupper(explode(" ", $sql)[0]);

    $stmt = $GLOBALS['db']->getConnection()->prepare($sql);
    $stmt->execute($variables);

    if ($statementType == "SELECT" || $statementType == "SHOW" || $statementType == "DESCRIBE") {
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $result;
    } else if ($statementType == "INSERT") {
        return $GLOBALS['db']->getConnection()->lastInsertId();
    } else {
        return $stmt->rowCount();
    }
}

function hasDatabase($dbName)
{
    $sql = "SELECT * FROM information_schema.schemata WHERE SCHEMA_NAME = :db";
    $variables = [
        ":db" => $dbName
    ];
    $results = runSQL($sql, $variables);
    return count($results) > 0;
}

function hasTable($tableName)
{
    $sql = "SELECT * FROM information_schema.tables WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table";
    $variables = [
        ":db" => $_ENV['DB_NAME'],
        ":table" => $tableName
    ];
    $results = runSQL($sql, $variables);
    return count($results) > 0;
}

function hasColumn($tableName, $columnName)
{
    $sql = "SELECT * FROM information_schema.columns WHERE table_schema = :db AND table_name = :table AND column_name = :column";
    $variables = [
        ":db" => $_ENV['DB_NAME'],
        ":table" => $tableName,
        ":column" => $columnName
    ];
    $results = runSQL($sql, $variables);
    return count($results) > 0;
}

function getDBColumns($table)
{
    $sql = "DESCRIBE $table";
    return runSQL($sql);
}