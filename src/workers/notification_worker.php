<?php
$config = require(__DIR__ . '/../external.php');

notificationWorkerHandle();

function notificationWorkerHandle()
{
    $configDB = require(__DIR__ . '/../../config/db.php');
    $configApp = require(__DIR__ . '/../../config/app.php');

    $pdo = new PDO($configDB['dns'], $configDB['username'], $configDB['password']);

    $emailText = "%s, your subscription is expiring soon";

    while (true) {
        $timestamp = time();

        $pdo->exec('START TRANSACTION');
        $stmt = $pdo->prepare('SELECT `id`, `email`, `username` FROM `notification_queue` WHERE `send_at` <= :sendAt LIMIT 1 FOR UPDATE SKIP LOCKED');
        $stmt->execute([':sendAt' => $timestamp]);
        $notificationQueue = $stmt->fetch();

        if ($notificationQueue) {
            $sql = "DELETE FROM `notification_queue` WHERE `id` = :id";
            $pdo->prepare($sql)->execute([':id' => $notificationQueue['id']]);

            send_email($configApp['mailing']['from'], $notificationQueue['email'], sprintf($emailText, $notificationQueue['username']));
        }

        $pdo->exec('COMMIT');

        usleep(1000000);
    }
}
