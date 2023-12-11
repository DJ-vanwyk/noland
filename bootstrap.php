<?php
/* ----------------------------------- ENV ---------------------------------- */

$env = parse_ini_file(".env");
$_ENV = array_merge($_ENV, $env);

/* -------------------------------- Includes -------------------------------- */

require 'lib/database/includes.php';

include 'lib/modules/DataMapper_class.php';

include 'lib/modules/rest/includes.php';

/* -------------------------------- Database -------------------------------- */

$db = new DB($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV["DB_USER"], $_ENV["DB_PASSWORD"]);
