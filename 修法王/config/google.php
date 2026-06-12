<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

function getGoogleClient(): Google\Client {
    static $client = null;
    if ($client !== null) return $client;

    $client = new Google\Client();
    $client->setAuthConfig(GOOGLE_CREDENTIALS_PATH);
    $client->setScopes([
        Google\Service\Sheets::SPREADSHEETS,
        Google\Service\Drive::DRIVE,
    ]);
    return $client;
}

function getSheetsService(): Google\Service\Sheets {
    static $service = null;
    if ($service !== null) return $service;
    $service = new Google\Service\Sheets(getGoogleClient());
    return $service;
}

function getDriveService(): Google\Service\Drive {
    static $service = null;
    if ($service !== null) return $service;
    $service = new Google\Service\Drive(getGoogleClient());
    return $service;
}
