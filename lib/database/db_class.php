<?php

class DB
{
    private $conn;

    public function __construct($host, $db, $user, $pass)
    {
        $this->conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        // Settings
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function getConnection()
    {
        return $this->conn;
    }

}