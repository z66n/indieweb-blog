<?php
// feed.php - RSS/JSON feed for posts

// Load configuration
require_once __DIR__ . '/config.php';

function get_content_html($content) {
    if (is_array($content)) {
        if (!empty($content['html'])) return $content['html'];
        return '';
    }
    return nl2br(htmlspecialchars($content));
}

header('Content-Type: application/xml; charset=utf-8');

$files = glob(__DIR__ . "/posts/*.json");
usort($files, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

// Load posts
$posts = [];
foreach ($files as $file) {
    $slug = basename($file, ".json");
    $post = json_decode(file_get_contents($file), true);
    if (!$post) continue;
    $posts[] = [$slug, $post];
}

// Detect ?format=json
if (isset($_GET['format']) && $_GET['format'] === "json") {
    header('Content-Type: application/json; charset=utf-8');
    $items = [];
    foreach ($posts as [$slug, $post]) {
        $url = "$site_url/?p=$slug";
        // unwrap helper
        $get = fn($k) => isset($post['properties'][$k]) ? $post['properties'][$k][0] ?? '' : '';
        $content = get_content_html($get('content'));
        $title = !empty($get('name')) ? $get('name') : $slug;
        $items[] = [
            "id" => $url,
            "url" => $url,
            "title" => $title,
            "content_text" => $content,
            "date_published" => $get('published'),
        ];
    }
    echo json_encode([
        "version" => "https://jsonfeed.org/version/1",
        "title" => $site_name,
        "home_page_url" => $site_url,
        "feed_url" => "$site_url/feed.php?format=json",
        "items" => $items
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    exit;
}

// Otherwise output RSS
echo "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";
?>
<rss version="2.0">
  <channel>
    <title><?= htmlspecialchars($site_name) ?></title>
    <link><?= htmlspecialchars($site_url) ?></link>
    <description><?= htmlspecialchars($site_desc) ?></description>
    <language>en</language>
    <?php foreach ($posts as [$slug, $post]):
        $url = "$site_url/?p=$slug";
        // unwrap helper
        $get = fn($k) => isset($post['properties'][$k]) ? $post['properties'][$k][0] ?? '' : '';
        $title = !empty($get('name')) ? $get('name') : $slug;
        $content = get_content_html($get('content'));
    ?>
    <item>
      <title><?= htmlspecialchars($title) ?></title>
      <link><?= htmlspecialchars($url) ?></link>
      <guid><?= htmlspecialchars($url) ?></guid>
      <?php if (!empty($get('published'))): ?>
      <pubDate><?= date(DATE_RSS, strtotime($get('published'))) ?></pubDate>
      <?php endif; ?>
      <description><![CDATA[<?= $content ?>]]></description>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
