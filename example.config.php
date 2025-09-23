<?php
// example.config.php - rename to config.php and update values

// Site info
$site_name = "My Blog";
$site_desc = "A simple Micropub-powered blog";
$site_domain = "example.com";
$site_url = "https://$site_domain";
$admin_email = "admin@$site_domain";
$bio = "Welcome, traveller";
$avatar_url = "https://api.dicebear.com/9.x/identicon/png?seed=Leo&scale=80";

// IndieWeb endpoints
$authorization_endpoint = "https://indieauth.com/auth";
$token_endpoint = "https://tokens.indieauth.com/token";
$microsub_endpoint = "https://aperture.p3k.io/microsub/9999"; // sign up at https://aperture.p3k.io
$webmention_endpoint = "https://webmention.io/$site_domain/webmention"; // sign up at https://webmention.io

// Syndication
$syndication_targets = [
    [
        'uid' => 'https://your-syndication-site.com/micropub', // micropub endpoint URL
        'name' => 'Your Syndication Site' // human-readable name
    ]
];
$syndication_tokens = [
    'https://your-syndication-site.com/micropub' => '123456789' // Bearer token for syndication endpoint
];
