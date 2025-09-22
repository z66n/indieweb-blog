<?php
// feed.php - RSS/JSON feed for posts

// Load configuration
require_once __DIR__ . '/config.php';

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
        $url = "$site_url/index.php?p=$slug";
        $content = is_array($post['content'] ?? null) ? $post['content']['text'] : ($post['content'] ?? "");
        $items[] = [
            "id" => $url,
            "url" => $url,
            "title" => $post['name'] ?? $slug,
            "content_text" => $content,
            "date_published" => $post['published'] ?? "",
        ];
    }
    echo json_encode([
        "version" => "https://jsonfeed.org/version/1",
        "title" => "My Blog",
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
    <title>My Blog</title>
    <link><?= htmlspecialchars($site_url) ?></link>
    <description>Micropub-powered blog feed</description>
    <language>en</language>
    <?php foreach ($posts as [$slug, $post]): 
        $url = "$site_url/index.php?p=$slug";
        $content = is_array($post['content'] ?? null) ? $post['content']['text'] : ($post['content'] ?? "");
    ?>
    <item>
      <title><?= htmlspecialchars($post['name'] ?? $slug) ?></title>
      <link><?= htmlspecialchars($url) ?></link>
      <guid><?= htmlspecialchars($url) ?></guid>
      <?php if (!empty($post['published'])): ?>
      <pubDate><?= date(DATE_RSS, strtotime($post['published'])) ?></pubDate>
      <?php endif; ?>
      <description><![CDATA[<?= $content ?>]]></description>
    </item>
    <?php endforeach; ?>
  </channel>
</rss>
