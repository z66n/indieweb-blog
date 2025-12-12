<?php
// webhook.php - A simple webhook endpoint to trigger Webmention.io API data fetch

// Load configuration and helpers
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/fetch_webmentions.php'; // for fetching webmentions

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$secret = $webhook_secret; // Define your webhook secret in config.php

// Read POST payload
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

// Verify secret
if (!isset($data['secret']) || $data['secret'] !== $secret) {
    http_response_code(403);
    exit('Invalid secret');
}

// Determine slug from target URL
$target = $data['target'] ?? '';
$parsed = parse_url($target);
parse_str($parsed['query'] ?? '', $queryParams);
$slug = $queryParams['p'] ?? null;

// If slug is found, fetch webmention data
if ($slug) {
    // Wait for Webmention.io to finish processing
    sleep(60);
    fetch_count($slug);
    fetch_mentions($slug);
}

http_response_code(200);
echo 'OK';
