<?php
// send-webmentions.php - send webmentions for a post

// Source URL
$source  = $location_url;

// Collect target URLs
$targets = [];
// Unwrap helper for single value arrays
$get = fn($k) => isset($post['properties'][$k]) ? $post['properties'][$k][0] ?? '' : '';
// Explicit link properties
foreach (['like-of', 'in-reply-to', 'repost-of'] as $prop) {
    if (!empty($get($prop))) {
        $targets[] = $get($prop);
        break; // stop after first match
    }
}
// Links inside e-content (both HTML and plain text)
function get_content_html_links($content) {
    if (is_array($content) && !empty($content['html'])) {
        $html = $content['html'];
        if (preg_match_all('/https?:\/\/[^\s"\']+/i', $html, $matches)) {
            return $matches[0]; // array of links
        }
    }
    return []; // no links
}
$targets = array_merge($targets, get_content_html_links($get('content')));
$targets = array_unique($targets); // Deduplicate

// No targets, nothing to do
if (empty($targets)) {
    echo "No links found in $filename, skipping.\n";
    exit(0);
}

// Function to send one webmention via Telegraph
function sendWebmention($token, $source, $target) {
    $url = "https://telegraph.p3k.io/webmention";
    $data = http_build_query([
        'token'  => $token,
        'source' => $source,
        'target' => $target,
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $result = curl_exec($ch);
    $error  = curl_error($ch);
    curl_close($ch);

    if ($result === false) {
        // Optionally log $error somewhere
        echo "cURL error: $error\n";
        return null;
    }
    return $result;
}

// Send mentions to all targets
foreach ($targets as $target) {
    echo "Sending webmention: $source → $target\n";
    $response = sendWebmention($telegraph_token, $source, $target);
    if ($response) {
        echo "Success: $response\n";
    } else {
        error_log("Failed to send webmention: $source → $target");
    }
}
