<?php 
// List all Canny posts without a linked Jira issues, in order of popularity

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

$posts = [];

foreach ($data->posts as $post) {
    if (! empty($post->jira->linkedIssues)) {
        continue;
    }

	if ( $post->status === 'complete' ) {
		continue;
	}

    $votes = $post->score;

	$posts[]= array( $votes, $post->url );

	$total++;
}

function mySort( $l, $r ) {
	return (( $l[0] == $r[0] ) ? 0 : ($l[0] < $r[0] ? 1 : -1) );
}

usort( $posts, 'mySort' );

foreach( $posts as $post ) {
	echo $post[0] . " - " . $post[1] . "\n";
}

echo  "\n" . $total . ' Canny posts without ideas';


