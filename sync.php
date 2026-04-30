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

// Validate Jira credentials before proceeding
try {
    $client->request('GET', 'https://' . $jira_subdomain . '.atlassian.net/rest/api/3/myself', [
        'auth' => [$jira_email, $jira_api_token],
    ]);
} catch (GuzzleHttp\Exception\ClientException $e) {
    echo 'Jira authentication failed. Regenerate your API token at https://id.atlassian.com/manage-profile/security/api-tokens' . PHP_EOL;
    exit(1);
}

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

        try {
            $response = $client->request('PUT', 'https://' . $jira_subdomain . '.atlassian.net/rest/api/3/issue/' . $linkedIssue->key, [
                'auth' => [$jira_email, $jira_api_token],
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'],
                'json' => ['fields' => [$jira_custom_field_id => $votes]]
            ]);

            $total++;
        } catch (GuzzleHttp\Exception\ClientException $e) {
            echo 'Skipping ' . $linkedIssue->key . ': ' . $e->getResponse()->getStatusCode() . ' - issue may not exist or no permission' . PHP_EOL;
        }
    }

}

echo 'Synced ' . $total . ' Canny post votes with Jira (' . $total_votes . ' votes)';


