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

    $html = "  <article class='h-entry'>\n";

    // Unwrap helper for single value arrays
    $get = fn($k) => isset($post['properties'][$k]) ? $post['properties'][$k][0] ?? '' : '';

    // Published date
    $published_raw = $get('published');
    if ($published_raw) {
        $dt = new DateTime($published_raw);
        $published = $dt->format("Y-m-d H:i");
    } else {
        $published = '';
    }

    // Post title (p-name)
    $name = $get('name');
    if (!empty($name)) {
        $html .= "    <h2 class='p-name'><a class='u-url' href='?p=$slug'>{$name}</a></h2>\n";
    } else {
        $html .= "    <h2><a class='u-url' href='?p=$slug'>$slug</a></h2>\n";
    }

    if ($published) {
        $html .= "    <time class='dt-published' datetime='{$published_raw}'>$published</time>\n";
    }

    // Favorites (likes) 
    if (!empty($get('like-of'))) {
        $html .= "    <p>⭐ <span class='p-name'>Favorited</span> <a class='u-like-of' href='{$get('like-of')}'>{$get('like-of')}</a></p>\n";
    }
    // Replies
    if (!empty($get('in-reply-to'))) {
        $html .= "    <p>💬 <span class='p-name'>Reply to</span> <a class='u-in-reply-to' href='{$get('in-reply-to')}'>{$get('in-reply-to')}</a></p>\n";
    }
    // Reposts
    if (!empty($get('repost-of'))) {
        $html .= "    <p>🔃 <span class='p-name'>Reposted</span> <a class='u-repost-of' href='{$get('repost-of')}'>{$get('repost-of')}</a></p>\n";
    }
    // Bookmarks
    if (!empty($get('bookmark-of'))) {
        $html .= "    <p>🔖 <span class='p-name'>Bookmarked</span> <a class='u-bookmark-of' href='{$get('bookmark-of')}'>{$get('bookmark-of')}</a></p>\n";
    }

    // Content (always)
    if (!empty($get('content'))) {
        $content = get_content_html($get('content'));
        $html .= "    <div class='e-content'>$content</div>\n";
    }

    // Categories (p-category)
    $categories = $post['properties']['category'] ?? [];
    if (!empty($categories)) {
        $tags = array_map(function($cat) {
            return "<span class='p-category'>$cat</span>";
        }, $categories);
        $html .= "    <div class='tags'>Tags: " . implode(", ", $tags) . "</div>\n";
    }

    // Author (h-card / u-author)
    if (!empty($get('author'))) {
        $html .= "    <div class='p-author h-card'><img class='u-photo' src='$avatar_url&size=60' alt=''/><a class='u-url' href='{$get('author')}'>{$get('author')}</a></div>\n";
    }

    // Syndicated copies (u-syndication)
    $syndicated_urls = $post['properties']['u-syndication'] ?? [];
    if (!empty($syndicated_urls)) {
        $html .= "    <div class='posse-links'>Also on: ";
        $links = array_map(function($url) {
            $host = parse_url($url, PHP_URL_HOST); // get the domain
            return "<a rel='syndication' class='u-syndication' href='$url'>$host</a>";
        }, $syndicated_urls);
        $html .= implode(", ", $links);
        $html .= "</div>\n";
    }

    $html .= "  </article>\n";

    return $html;
}

function get_data($dataFile) {
    // Serve cached data
    if (file_exists($dataFile)) {
        return json_decode(file_get_contents($dataFile), true);
    }
}

function render_count($slug) {
    global $DATA_DIR;
    // Mention counter
    $dataFile = "$DATA_DIR/{$slug}_cnt.json";
    $data = get_data($dataFile);
    
    $html = "  <div class='wm-counter'>";
    $html .= "(<span>" . ($data['type']['like'] ?? 0) . "</span> likes, ";
    $html .= "<span>" . ($data['type']['reply'] ?? 0) . "</span> replies, ";
    $html .= "<span>" . ($data['type']['repost'] ?? 0) . "</span> reposts, ";
    $html .= "<span>" . ($data['type']['mention'] ?? 0) . "</span> mentions)";
    $html .= "</div>\n";

    return $html;
}

function render_webmention($slug) {
    global $site_url, $DATA_DIR;
    // All mentions
    $url = "https://webmention.io/api/mentions.jf2?target=$site_url/?p=$slug";
    $dataFile = "$DATA_DIR/{$slug}_wm.json";
    $data = get_data($dataFile);

    foreach ($data['children'] as $mention) {
        $time = !empty($mention['published']) ? $mention['published'] : $mention['wm-received'];
        // Likes
        if ($mention['wm-property'] === 'like-of') {
            $html .= '  <div class="wm like">'
                . '<a href="'.$mention['author']['url'].'">'
                . '<img src="'.$mention['author']['photo'].'" alt="'.($mention['author']['name'] ?: 'web user').'" width="32">'
                . '</a><div class="wm-body">liked this</div>'
                . '<div class="wm-meta"><a href="'.$mention['url'].'">'
                . '<time class="dt-published" datetime="'.$time.'">'.$time.'</time></a></div></div>'."\n";
        }

        // Replies
        if ($mention['wm-property'] === 'in-reply-to') {
            $html .= '  <div class="wm reply">'
                . '<a href="'.$mention['author']['url'].'">'
                . '<img src="'.$mention['author']['photo'].'" alt="'.($mention['author']['name'] ?: 'web user').'" width="32">'
                . '</a><div class="wm-body">replied: '
                . ($mention['content']['html'] ?? $mention['content']['text']).'</div>'
                . '<div class="wm-meta"><a href="'.$mention['url'].'">'
                . '<time class="dt-published" datetime="'.$time.'">'.$time.'</time></a></div></div>'."\n";
        }

        // Reposts
        if ($mention['wm-property'] === 'repost-of') {
            $html .= '  <div class="wm repost">'
                . '<a href="'.$mention['author']['url'].'">'
                . '<img src="'.$mention['author']['photo'].'" alt="'.($mention['author']['name'] ?: 'web user').'" width="32">'
                . '</a><div class="wm-body">reposted this</div>'
                . '<div class="wm-meta"><a href="'.$mention['url'].'">'
                . '<time class="dt-published" datetime="'.$time.'">'.$time.'</time></a></div></div>'."\n";
        }

        // Mentions
        if ($mention['wm-property'] === 'mention-of') {
            $html .= '  <div class="wm mention">'
                . '<a href="'.$mention['author']['url'].'">'
                . '<img src="'.$mention['author']['photo'].'" alt="'.($mention['author']['name'] ?: 'web user').'" width="32">'
                . '</a><div class="wm-body">mentioned this in: '
                . '<a href="'.$mention['url'].'">'.$mention['name'].'</a></div>'
                . '<div class="wm-meta"><a href="'.$mention['url'].'">'
                . '<time class="dt-published" datetime="'.$time.'">'.$time.'</time></a></div></div>'."\n";
        }
    }

    return $html;
}

$slug = $_GET['p'] ?? null;

if ($slug) {
    $path = __DIR__ . "/posts/$slug.json";
    if (file_exists($path)) {
        $post = json_decode(file_get_contents($path), true);
        // Unwrap helper for single value arrays
        $get = fn($k) => isset($post['properties'][$k]) ? $post['properties'][$k][0] ?? '' : '';
        $name = $get('name');
        $title = !empty($name) ? $name : $slug;
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
          <meta charset="UTF-8">
          <title>$title</title>
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <meta name="description" content="$site_desc">
        $indieweb_html_header
        $shared_html_header
        </head>
        <body>\n
        HTML;
        echo render_post($post, $slug);
        echo render_count($slug);
        echo render_webmention($slug);
        echo "</body>\n<script src='script.js'></script>\n</html>";
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

// Pagination settings
$postsPerPage = 10;

// Total pages
$totalPosts = count($files);
$totalPages = ceil($totalPosts / $postsPerPage);

// Current page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$page = min($page, $totalPages);

// Slice files for this page
$start = ($page - 1) * $postsPerPage;
$filesOnPage = array_slice($files, $start, $postsPerPage);

// Render HTML
echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>$site_name</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="$site_desc">
  <!-- Feed -->
  <link rel="alternate" type="application/rss+xml" title="RSS" href="$site_url/feed.php">
  <link rel="alternate" type="application/json" title="JSON Feed" href="$site_url/feed.php?format=json">
$indieweb_html_header
$shared_html_header
</head>
<body>
  <h1>$site_name</h1>
  <div class="h-card">
    <img class="u-photo" src="$avatar_url&size=80" alt=""/>
    <div class="h-card-text">
      <a class="u-url u-uid" href="$site_url/">$site_url/</a>
      <p class="p-note">$bio</p>
    </div>
  </div>
  <p>Subscribe: 
    <a href="$site_url/feed.php">RSS</a>|
    <a href="$site_url/feed.php?format=json">JSON</a>
  </p>\n
HTML;
foreach ($filesOnPage as $file) {
    $slug = basename($file, ".json");
    $post = json_decode(file_get_contents($file), true);
    echo render_post($post, $slug);
    echo render_count($slug);
}
echo "  <div class='pagination' style='margin: 2em 0;'>";
if ($page > 1) {
    echo "<a href='?page=" . ($page - 1) . "' class='prev' rel='prev'>← Newer Posts</a> ";
}
echo " Page $page of $totalPages ";
if ($page < $totalPages) {
    echo "<a href='?page=" . ($page + 1) . "' class='next' rel='next'>Older Posts →</a>";
}
echo "</div>\n";
echo "</body>\n<script src='script.js'></script>\n</html>";
