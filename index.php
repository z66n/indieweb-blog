<?php
// index.php - minimal PHP front-end to display posts

// Load configuration
require_once __DIR__ . '/config.php';

$indieweb_html_header = <<<HTML
    <!-- IndieAuth discovery -->
    <link rel="authorization_endpoint" href="$authorization_endpoint">
    <link rel="token_endpoint" href="$token_endpoint">
    <!-- rel=me proofs -->
    <link rel="me" href="mailto:$admin_email">
    <!-- Microsub endpoint -->
    <link rel="microsub" href="$microsub_endpoint">
    <!-- Webmention -->
    <link rel="webmention" href="$webmention_endpoint" />
    <!-- Micropub endpoint -->
    <link rel="micropub" href="$site_url/micropub.php">
HTML;

$shared_html_header = <<<HTML
    <!-- Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Favicons -->
    <link rel="icon" type="image/png" sizes="32x32" href="$avatar_url&size=32">
    <link rel="icon" type="image/png" sizes="16x16" href="$avatar_url&size=16">
    <link rel="apple-touch-icon" sizes="180x180" href="$avatar_url&size=180">
    <link rel="icon" type="image/png" sizes="192x192" href="$avatar_url&size=192">
    <link rel="icon" type="image/png" sizes="512x512" href="$avatar_url&size=512">
    <!-- Open Graph -->
    <meta property="og:title" content="$site_name">
    <meta property="og:description" content="$site_desc">
    <meta property="og:image" content="$avatar_url&size=216">
    <meta property="og:url" content="$site_url">
    <meta property="og:type" content="website">
HTML;

function get_content_html($content) {
    if (is_array($content)) {
        if (!empty($content['html'])) return $content['html'];
        return '';
    }
    return nl2br(htmlspecialchars($content));
}

function render_post($post, $slug) {
    global $avatar_url;

    $html = "<article class='h-entry'>";
    $published = isset($post['published']) ? date("Y-m-d H:i", strtotime($post['published'])) : "";

    // Post title (p-name)
    if (!empty($post['name'])) {
        $html .= "<h2 class='p-name'><a class='u-url' href='?p=$slug'>{$post['name']}</a></h2>";
    } else {
        $html .= "<h2><a class='u-url' href='?p=$slug'>$slug</a></h2>";
    }

    if ($published) {
        $html .= "<time class='dt-published' datetime='{$post['published']}'>$published</time>";
    }

    // Replies
    if (!empty($post['in-reply-to'])) {
        $html .= "<p>💬 <span class='p-name'>Reply to</span> <a class='u-in-reply-to' href='{$post['in-reply-to']}'>{$post['in-reply-to']}</a></p>";
    }
    // Bookmarks
    if (!empty($post['bookmark-of'])) {
        $html .= "<p>🔖 <span class='p-name'>Bookmarked</span> <a class='u-bookmark-of' href='{$post['bookmark-of']}'>{$post['bookmark-of']}</a></p>";
    }
    // Favorites
    if (!empty($post['like-of'])) {
        $html .= "<p>⭐ <span class='p-name'>Favorited</span> <a class='u-like-of' href='{$post['like-of']}'>{$post['like-of']}</a></p>";
    }

    // Content (always)
    if (!empty($post['content'])) {
        $content = get_content_html($post['content']);
        $html .= "<div class='e-content'>$content</div>";
    }

    // Categories (p-category)
    $categories = (array)($post['category'] ?? []);
    if (!empty($categories)) {
        $tags = array_map(function($cat) {
            return "<span class='p-category'>$cat</span>";
        }, $categories);
        $html .= "<div class='tags'>Tags: " . implode(", ", $tags) . "</div>";
    }

    // Author (h-card / u-author)
    if (!empty($post['author'])) {
        $html .= "<div class='p-author h-card'><img class='u-photo' src='$avatar_url&size=60' alt='Avatar'/><br><a class='u-url' href='{$post['author']}'>{$post['author']}</a></div>";
    }

    // Syndicated copies (u-syndication)
    if (!empty($post['syndication'])) {
        $html .= "<div class='syndication'>Syndicated copies: ";
        $links = array_map(function($url) {
            return "<a rel='syndication' class='u-syndication' href='$url'>$url</a>";
        }, (array)$post['syndication']);
        $html .= implode(" ", $links);
        $html .= "</div>";
    }

    $html .= "</article>";
    return $html;
}

$slug = $_GET['p'] ?? null;

if ($slug) {
    $path = __DIR__ . "/posts/$slug.json";
    if (file_exists($path)) {
        $post = json_decode(file_get_contents($path), true);
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>Post</title>
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <meta name="description" content="$site_desc">
          $indieweb_html_header
          $shared_html_header
        </head>
        <body>
        HTML;
        echo render_post($post, $slug);
        echo "</body></html>";
        exit;
    } else {
        http_response_code(404);
        echo "Post not found.";
        exit;
    }
}

// Listing all posts
$files = glob(__DIR__ . "/posts/*.json");
// Sort newest first
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>$site_name</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content=$site_desc>
  <!-- Feed -->
  <link rel="alternate" type="application/rss+xml" title="RSS" href="/feed.php">
  <link rel="alternate" type="application/json" title="JSON Feed" href="/feed.php?format=json">
  $indieweb_html_header
  $shared_html_header
</head>
<body>
  <h1>My Blog</h1>
  <div class='h-card'>
    <a class='u-url u-uid' href='$site_url/'>$site_url/</a><br>
    <img class="u-photo" src="$avatar_url&size=80" alt="Avatar"/>
    <p class="p-note">$bio</p>
  </div>
  <p>Subscribe: 
    <a href="/feed.php">RSS</a>|
    <a href="/feed.php?format=json">JSON</a>
  </p>
HTML;
foreach ($files as $file) {
    $slug = basename($file, ".json");
    $post = json_decode(file_get_contents($file), true);
    echo render_post($post, $slug);
}
echo "</body></html>";
