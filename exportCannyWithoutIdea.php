<?php
// Export all Canny posts without a linked Jira idea to CSV for AI analysis

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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

$posts = [];

foreach ($data->posts as $post) {
    if (! empty($post->jira->linkedIssues)) {
        continue;
    }

    if ( $post->status === 'complete' ) {
        continue;
    }

    $posts[] = [
        'votes'     => $post->score,
        'title'     => $post->title,
        'details'   => $post->details ?? '',
        'status'    => $post->status,
        'url'       => $post->url,
    ];
}

usort($posts, function ($l, $r) {
    return $r['votes'] <=> $l['votes'];
});

$filename = 'canny_without_idea_' . date('Y-m-d') . '.csv';
$fp = fopen($filename, 'w');

fputcsv($fp, ['Votes', 'Title', 'Details', 'Status', 'URL']);

foreach ($posts as $post) {
    fputcsv($fp, [
        $post['votes'],
        $post['title'],
        $post['details'],
        $post['status'],
        $post['url'],
    ]);
}

fclose($fp);

echo count($posts) . " Canny posts exported to {$filename}\n";
