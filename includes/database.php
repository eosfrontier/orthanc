<?php

class db {
    public static $conn;
}

  db::$conn = new PDO('mysql:host=*;dbname=*;charset=utf8mb4', '*', '*');
?>