<?php

const STATUS_CHECKING_EMAIL = 1;
const STATUS_3DAY_LEFT_SENT = 2;
const STATUS_1DAY_LEFT_SENT = 3;

$config = require(__DIR__ . '/../../config/db.php');
$pdo = new PDO($config['dns'], $config['username'], $config['password']);

$currentTimestamp = (new DateTime())->getTimestamp();

$from = (new DateTime())->modify('+1 days -1 hour')->getTimestamp();
$to = (new DateTime())->modify('+1 days')->getTimestamp();

$pdo->exec('START TRANSACTION');
$stmt = $pdo->prepare("SELECT `valid`, `validts`, `username`, `email` FROM `users` WHERE confirmed = 1 
                                AND (`validts` > :from and validts < :to)
                                AND (`valid` = :valid OR `checked` = :checked) 
                                AND (`notification_status` = :status) limit 100 FOR UPDATE SKIP LOCKED");
$stmt->execute([':from' => $from, ':to' => $to, ':valid' => 1, ':checked' => 0, ':status' => STATUS_3DAY_LEFT_SENT]);
$notifications = $stmt->fetchAll();

foreach ($notifications as $notification) {
    if ($notification['valid']) {
        queueNotify($notification, $pdo, STATUS_1DAY_LEFT_SENT, $currentTimestamp);
    } else {
        queueEmailCheck($notification, $pdo);
    }
}
$pdo->exec('COMMIT');

$from = (new DateTime())->modify('+3 days -1 hour')->getTimestamp();
$to = (new DateTime())->modify('+3 days')->getTimestamp();

$pdo->exec('START TRANSACTION');
$stmt = $pdo->prepare("SELECT `valid`, `validts`, `username`, `email` FROM `users` WHERE confirmed = 1 
                                AND (`validts` > :from and validts < :to)
                                AND (`valid` = :valid OR `checked` = :checked) 
                                AND (`notification_status` is NULL) limit 100 FOR UPDATE SKIP LOCKED");
$stmt->execute([':from' => $from, ':to' => $to, ':valid' => 1, ':checked' => 0]);
$notifications = $stmt->fetchAll();

foreach ($notifications as $notification) {
    if ($notification['valid']) {
        queueNotify($notification, $pdo, STATUS_3DAY_LEFT_SENT, $currentTimestamp);
    } else {
        queueEmailCheck($notification, $pdo);
    }
}
$pdo->exec('COMMIT');


function queueNotify(array $notification, PDO $pdo, string $status, int $timestamp): void
{
    $sql = "UPDATE `users` SET `notification_status` = :status WHERE `username` = :username";
    $pdo->prepare($sql)->execute([':username' => $notification['username'], ':status' => $status]);

    $sql = 'INSERT INTO notification_queue (username, email, send_at) VALUES (:username, :email, :send_at)';
    $pdo->prepare($sql)->execute([':username' => $notification['username'], ':email' => $notification['email'], ':send_at' => $timestamp]);
}

function queueEmailCheck(array $notification, PDO $pdo): void
{
    $sql = "UPDATE `users` SET `notification_status` = :status WHERE `username` = :username";
    $pdo->prepare($sql)->execute([':username' => $notification['username'], ':status' => STATUS_CHECKING_EMAIL]);

    $sql = 'INSERT INTO `check_email_queue` (`username`, `email`) VALUES (:username, :email)';
    $pdo->prepare($sql)->execute([':username' => $notification['username'], ':email' => $notification['email']]);
}
