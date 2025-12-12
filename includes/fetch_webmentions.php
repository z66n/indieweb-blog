<?php
// includes/fetch_webmentions.php - fetch inbound webmentions via Webmention.io API

// Load configuration
require_once __DIR__ . '/../config.php';

// Fetch and cache webmention count data for a post
function fetch_count($slug) {
    global $site_url, $DATA_DIR;
    // Mention counter
    $url = "https://webmention.io/api/count?target=$site_url/?p=$slug";
    $dataFile = "$DATA_DIR/{$slug}_cnt.json";
    fetch_api_response($url, $dataFile);
}

// Fetch and cache webmention mentions data for a post
function fetch_mentions($slug) {
    global $site_url, $DATA_DIR;
    // All mentions
    $url = "https://webmention.io/api/mentions.jf2?target=$site_url/?p=$slug";
    $dataFile = "$DATA_DIR/{$slug}_wm.json";
    fetch_api_response($url, $dataFile);
}

// Helper function to fetch Webmention.io API response and save to file
function fetch_api_response($url, $dataFile) {
    // Fetch new data
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        file_put_contents($dataFile, $response);
    }
}
