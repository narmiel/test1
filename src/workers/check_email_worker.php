<?php
$config = require(__DIR__ . '/../external.php');

checkEmailWorkerHandle();

function checkEmailWorkerHandle()
{
    $config = require(__DIR__ . '/../../config/db.php');
    $pdo = new PDO($config['dns'], $config['username'], $config['password']);

    while (true) {
        $pdo->exec('START TRANSACTION');
        $stmt = $pdo->prepare('SELECT `id`, `username`, `email` FROM `check_email_queue` LIMIT 1 FOR UPDATE SKIP LOCKED');
        $stmt->execute();
        $checkEmailQueue = $stmt->fetch();

        if ($checkEmailQueue) {
            $sql = "DELETE FROM `check_email_queue` WHERE `id` = :id";
            $pdo->prepare($sql)->execute([':id' => $checkEmailQueue['id']]);

            $checkEmail = check_email($checkEmailQueue['email']);

            $sql = "UPDATE `users` SET `checked` = :checked, `valid` = :valid, `notification_status` = :notification_status WHERE `username` = :username";
            $pdo->prepare($sql)->execute([':checked' => 1, ':valid' => $checkEmail, ':notification_status' => null, ':username' => $checkEmailQueue['username']]);
        }

        $pdo->exec('COMMIT');

        usleep(1000000);
    }
}
