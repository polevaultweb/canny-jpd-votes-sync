<?php 

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$jira_subdomain = $_ENV['JIRA_SUBDOMAIN'];
$jira_api_token = $_ENV['JIRA_API_TOKEN'];
$jira_email = $_ENV['JIRA_EMAIL_ADDRESS'];
$jira_custom_field_id = $_ENV['JIRA_CUSTOM_FIELD_ID'];
$jira_jpd_prefix = $_ENV['JIRA_PROJECT_PREFIX'];

$canny_api_key = $_ENV['CANNY_API_KEY'];
$canny_board_id = $_ENV['CANNY_BOARD_ID'];

$client = new GuzzleHttp\Client();

$response = $client->post('https://canny.io/api/v1/posts/list', [
    'headers' => [
        'Content-Type' => 'application/json'
    ],
    'json' => [
        'apiKey' => $canny_api_key,
        'boardID' => $canny_board_id,
        'limit' => 1000,
    ]
]);

// TODO pagination

$data = json_decode( $response->getBody()->getContents() );

$total = 0;
$total_votes = 0;

foreach ($data->posts as $post) {
    if (empty($post->jira->linkedIssues)) {
        continue;
    }

    $votes = $post->score;

    $total_votes = $total_votes + $votes;

    foreach ($post->jira->linkedIssues as $linkedIssue) {
        if ( false === strpos($linkedIssue->key, $jira_jpd_prefix ) ) {
            continue;
        }

        $response = $client->request('PUT', 'https://' . $jira_subdomain . '.atlassian.net/rest/api/3/issue/' . $linkedIssue->key, [
            'auth' => [$jira_email, $jira_api_token],
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'],
            'json' => ['fields' => [$jira_custom_field_id => $votes]]
        ]);

        $total++;
    }

}

echo 'Synced ' . $total . ' Canny post votes with Jira (' . $total_votes . ' votes)';


