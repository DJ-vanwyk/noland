<?php

require './bootstrap.php';

echo "<h1>Test</h1>";

$dataMapper = new DataMapper();
// $result = getDBColumns("contacts");

function getPathSegments($path)
{
    $path = trim($path, "/");
    $segments = explode("/", $path);
    return $segments;
}

$segments = getPathSegments($_SERVER['PATH_INFO']);

$results = $dataMapper->getTableData($segments[1], $segments[2]);

echo "<pre>";
echo json_encode($results, JSON_PRETTY_PRINT);
echo "</pre>";

