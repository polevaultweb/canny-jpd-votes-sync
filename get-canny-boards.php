<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$canny_api_key = $_ENV['CANNY_API_KEY'];

$client = new GuzzleHttp\Client();

$response = $client->post('https://canny.io/api/v1/boards/list', [
    'headers' => [
        'Content-Type' => 'application/json'
    ],
    'json' => [
        'apiKey' => $canny_api_key
    ]
]);

$data = json_decode( $response->getBody()->getContents() );

print_r( $data );