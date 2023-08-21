<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$jira_api_token = $_ENV['JIRA_API_TOKEN'];
$jira_email = $_ENV['JIRA_EMAIL_ADDRESS'];

$client = new GuzzleHttp\Client();

if ( ! isset( $argv[1])) {
    echo 'Please supply idea ID';
    return;
}

$issue_id = $argv[1];

$response = $client->request('GET', 'https://wpengine.atlassian.net/rest/api/latest/issue/' . $issue_id . '?expand=names', [
    'auth' => [$jira_email, $jira_api_token],
    'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'],
]);

$data = json_decode( $response->getBody()->getContents() );

print_r( $data );