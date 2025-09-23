<?php
// micropub.php - minimal PHP Micropub endpoint

// Load configuration
require_once __DIR__ . '/config.php';

// --- CONFIG ---
$BASE_URL = $site_url;
$INDIEAUTH_TOKEN_URL = $token_endpoint; // IndieAuth token verification
$POSTS_DIR = __DIR__ . '/posts'; // folder to store posts
if (!is_dir($POSTS_DIR)) mkdir($POSTS_DIR, 0755, true);

// --- FUNCTIONS ---
function verify_token($access_token) {
    global $INDIEAUTH_TOKEN_URL;
    $headers = ["Authorization: Bearer $access_token"];
    $ch = curl_init($INDIEAUTH_TOKEN_URL);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    if (!$res) return false;
    parse_str($res, $data);
    return isset($data['me']) ? $data['me'] : false;
}

// --- MAIN ---
$method = $_SERVER['REQUEST_METHOD'];
$access_token = '';
$auth_header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) $access_token = $matches[1];

$user = verify_token($access_token);
if (!$user) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'invalid_token']);
    exit;
}

if ($method === 'POST') {
    $is_json = (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);

    if ($is_json) {
        // JSON body
        $input = file_get_contents('php://input');
        $json  = json_decode($input, true);

        $type  = $json['type'][0] ?? 'h-entry';
        $props = $json['properties'] ?? [];

    } else {
        // Form-encoded body
        $h = $_POST['h'] ?? 'entry';
        $type = ($h === 'entry') ? 'h-entry' : $h;
        $props = [
            'name'        => $_POST['name'] ?? null,
            'content'     => $_POST['content'] ?? null,
            'category'    => (array)($_POST['category'] ?? []),
            'mp-slug'     => $_POST['mp-slug'] ?? null,
            'status'      => $_POST['status'] ?? 'published',
            'published'   => $_POST['published'] ?? date(DATE_ATOM),
            'bookmark-of' => $_POST['bookmark-of'] ?? null,
            'like-of'     => $_POST['like-of'] ?? null,
            'in-reply-to' => $_POST['in-reply-to'] ?? null,
            'location'    => $_POST['location'] ?? null,
        ];
    }

    // Common helper
    $get = fn($k) => isset($props[$k]) ? (is_array($props[$k]) ? $props[$k][0] : $props[$k]) : null;

    // Slug/filename
    $date         = date('Y-m-d-H-i-s');
    $slug         = preg_replace('/[^a-z0-9_-]/', '', strtolower($get('mp-slug') ?? $date));
    $filename     = "$POSTS_DIR/$slug.json";
    $location_url = "$BASE_URL/?p=$slug";

    // Build final post object (shared)
    $post = [
        'type' => [$type],
        'properties' => [
            'name'        => (array)($props['name'] ?? []),
            'content'     => (array)($props['content'] ?? []),
            'category'    => (array)($props['category'] ?? []),
            'mp-slug'     => (array)($props['mp-slug'] ?? [$slug]),
            'status'      => (array)($props['status'] ?? ['published']),
            'published'   => (array)($props['published'] ?? [date(DATE_ATOM)]),
            'bookmark-of' => (array)($props['bookmark-of'] ?? []),
            'like-of'     => (array)($props['like-of'] ?? []),
            'in-reply-to' => (array)($props['in-reply-to'] ?? []),
            'location'    => (array)($props['location'] ?? []),
            'author'      => [$user],
        ]
    ];

    // Save post
    file_put_contents($filename, json_encode($post, JSON_PRETTY_PRINT));

    http_response_code(201);
    header('Content-Type: application/json');
    header('Location: ' . $location_url);
    echo json_encode(['location' => $location_url]);
    exit;
}

// Optional: GET returns list of posts
if ($method === 'GET') {
    $posts = [];
    foreach (glob("$POSTS_DIR/*.json") as $file) {
        $data = json_decode(file_get_contents($file), true);
        $posts[] = $data;
    }
    header('Content-Type: application/json');
    echo json_encode($posts);
    exit;
}

// fallback
header('HTTP/1.1 400 Bad Request');
echo json_encode(['error' => 'invalid_request']);
