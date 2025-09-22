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
    $type = $_POST['h'] ?? 'entry';
    $content = $_POST['content'] ?? '';
    $date = date('Y-m-d-H-i-s');
  $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($_POST['mp-slug'] ?? $date));
  $filename = "$POSTS_DIR/$slug.json";

  $post = [
    'type' => $type,
    'name' => $_POST['name'] ?? null,
    'content' => $_POST['content'] ?? null,
    // 'content.html' => $_POST['content'] ?? null,
    'category' => $_POST['category'] ?? [],
    'slug' => $_POST['mp-slug'] ?? $slug,
    'status' => $_POST['status'] ?? 'published',
    'published' => $_POST['published'] ?? date(DATE_ATOM),
    'bookmark-of' => $_POST['bookmark-of'] ?? null,
    'like-of' => $_POST['like-of'] ?? null,
    // 'post-status' => $_POST['post-status'] ?? null,
    'in-reply-to' => $_POST['in-reply-to'] ?? null,
    'location' => $_POST['location'] ?? null,
    'author' => $user
  ];
    file_put_contents($filename, json_encode($post, JSON_PRETTY_PRINT));
  
    // Optionally save HTML
    // $htmlFile = "$POSTS_DIR/$slug.html";
    // file_put_contents($htmlFile, $_POST['content.html'] ?? '');

    // Respond with location that exists
    http_response_code(201);
    header('Content-Type: application/json'); // JSON content
    header('Location: ' . "$BASE_URL/posts/$slug.json"); // MUST be set
    echo json_encode(['location' => "$BASE_URL/posts/$slug.json"]);
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
