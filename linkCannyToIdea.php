<?php
// Link Canny posts to JPD ideas from a CSV file
// CSV format: canny_url, jpd_url
// e.g. https://acf.canny.io/feature-requests/p/some-feature, https://wpengine.atlassian.net/browse/ACFI-123

if ($argc < 2) {
    echo "Usage: php linkCannyToIdea.php <csv_file>\n";
    exit(1);
}

$csv_file = $argv[1];

if (!file_exists($csv_file)) {
    echo "File not found: {$csv_file}\n";
    exit(1);
}

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$canny_api_key = $_ENV['CANNY_API_KEY'];
$canny_board_id = $_ENV['CANNY_BOARD_ID'];

$client = new GuzzleHttp\Client();

$fp = fopen($csv_file, 'r');
$header = fgetcsv($fp);

$linked = 0;
$errors = 0;
$row = 1;

while (($line = fgetcsv($fp)) !== false) {
    $row++;
    $canny_url = trim($line[0]);
    $jpd_url = trim($line[1]);

    // Extract urlName from Canny URL (last path segment)
    $path = parse_url($canny_url, PHP_URL_PATH);
    $segments = explode('/', rtrim($path, '/'));
    $url_name = end($segments);

    if (empty($url_name)) {
        echo "Row {$row}: Could not extract urlName from {$canny_url}\n";
        $errors++;
        continue;
    }

    // Extract issue key from JPD URL (e.g. ACFI-123 from .../browse/ACFI-123)
    if (preg_match('#/browse/([A-Z]+-\d+)#', $jpd_url, $matches)) {
        $issue_key = $matches[1];
    } elseif (preg_match('#([A-Z]+-\d+)#', $jpd_url, $matches)) {
        $issue_key = $matches[1];
    } else {
        echo "Row {$row}: Could not extract issue key from {$jpd_url}\n";
        $errors++;
        continue;
    }

    // Look up Canny post ID by urlName
    try {
        $response = $client->post('https://canny.io/api/v1/posts/retrieve', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'apiKey' => $canny_api_key,
                'boardID' => $canny_board_id,
                'urlName' => $url_name,
            ]
        ]);

        $post = json_decode($response->getBody()->getContents());

        if (empty($post->id)) {
            echo "Row {$row}: Canny post not found for urlName '{$url_name}'\n";
            $errors++;
            continue;
        }

        $post_id = $post->id;
    } catch (Exception $e) {
        echo "Row {$row}: Failed to retrieve Canny post - {$e->getMessage()}\n";
        $errors++;
        continue;
    }

    // Link Jira issue to Canny post
    try {
        $client->post('https://canny.io/api/v1/posts/link_jira', [
            'headers' => ['Content-Type' => 'application/json'],
            'json' => [
                'apiKey' => $canny_api_key,
                'postID' => $post_id,
                'issueKey' => $issue_key,
            ]
        ]);

        echo "Row {$row}: Linked {$url_name} -> {$issue_key}\n";
        $linked++;
    } catch (Exception $e) {
        echo "Row {$row}: Failed to link {$url_name} -> {$issue_key} - {$e->getMessage()}\n";
        $errors++;
    }
}

fclose($fp);

echo "\nDone. Linked: {$linked}, Errors: {$errors}\n";
