<?php

$config = require(__DIR__ . '/../config/db.php');

$pdo = new PDO($config['dns'], $config['username'], $config['password']);

// индексы для users
$pdo->exec("ALTER TABLE `users` ADD INDEX `confirmed_validts` (`confirmed`, `validts`)");

// поля для статуса отправки
$pdo->exec("ALTER TABLE `users` ADD `notification_status` VARCHAR(13) DEFAULT NULL");

// на самом деле можно было все очереди класть в одну таблицу
$sql = "CREATE table notification_queue(
     id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
     username VARCHAR( 255 ),
     email VARCHAR( 255 ) NOT NULL, 
     send_at int NOT NULL,
     FOREIGN KEY (`username`) REFERENCES `users` (`username`) 
     )
    DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$pdo->exec($sql);

$sql = "CREATE table check_email_queue(
     id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
     username VARCHAR( 255 ) ,
     email VARCHAR( 255 ) NOT NULL,
     FOREIGN KEY (`username`) REFERENCES `users` (`username`) 
     )
    DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$pdo->exec($sql);
