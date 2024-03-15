<?php

$config = require(__DIR__ . '/../config/db.php');

$pdo = new PDO($config['dns'], $config['username'], $config['password']);

// предположим эта таблица уже была, и у нее нет индексов кроме PRIMARY KEY
$sql = "CREATE table users(
     username VARCHAR( 255 ) PRIMARY KEY,
     email VARCHAR( 255 ) NOT NULL, 
     validts int NOT NULL,
     confirmed bool NOT NULL, 
     checked bool NOT NULL, 
     valid bool NOT NULL) 
    DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$pdo->exec($sql);
