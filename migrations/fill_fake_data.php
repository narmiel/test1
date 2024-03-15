<?php

require_once 'vendor/autoload.php';

$config = require(__DIR__ . '/../config/db.php');

$pdo = new PDO($config['dns'], $config['username'], $config['password']);

$faker = Faker\Factory::create();

for ($i = 0; $i < 1000; $i++) {
    $insertData = [];
    $values = '';
    for ($k = 0; $k < 5000; $k++) {
        $values .= sprintf('("%s", "%s", %d, %d, %d, %d),',
            $faker->unique()->name,
            $faker->email,
            $faker->boolean(85) ? 0 : $faker->dateTimeBetween('now', '+ 1 years')->getTimestamp(),
            $faker->boolean(15) ?: 0,
            $faker->boolean() ?: 0,
            $faker->boolean() ?: 0
        );
    }

    $values = substr($values, 0, strlen($values) - 1);
    $sql = "INSERT INTO `users` (username, email, validts, confirmed, checked, valid) VALUES " . $values;

    $pdo->query($sql);
}
