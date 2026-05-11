<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

require_once '../../app/services/CoinService.php';

$data = CoinService::getMarketData();

echo json_encode(
    $data,
    JSON_UNESCAPED_UNICODE |
    JSON_UNESCAPED_SLASHES
);