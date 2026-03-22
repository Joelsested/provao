<?php
session_start();

header('Content-Type: application/json');

$sessionData = [
 'session_id' => session_id(),
 'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
 'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
 'session_data' => $_SESSION
];

echo json_encode($sessionData, JSON_PRETTY_PRINT);
