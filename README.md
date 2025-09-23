# IndieWeb Minimal Micropub Blog

A minimal PHP-based IndieWeb blog supporting [Micropub](https://indieweb.org/Micropub), [IndieAuth](https://indieweb.org/IndieAuth), and JSON/RSS feeds. Posts are stored as JSON files.

## Features

- IndieAuth authentication
- Micropub endpoint for publishing posts
- Syndication to other Micropub endpoints
- JSON Feed and RSS Feed
- Webmention, Microsub, and IndieAuth endpoint discovery
- Minimal, file-based storage (no database required)

## Getting Started

1. **Clone this repository**

2. **Copy and configure `config.php`:**
   ```sh
   cp example.config.php config.php
   ```
   Edit `config.php` and update your site details.

3. **Create the `posts/` directory if it doesn't exist:**
   ```sh
   mkdir posts
   ```

4. **Deploy to your PHP web server.**

## Endpoints

- **Home:** `/index.php`
- **Micropub:** `/micropub.php`
- **RSS Feed:** `/feed.php`
- **JSON Feed:** `/feed.php?format=json`

## IndieWeb Integration

- IndieAuth: [https://indieauth.com/](https://indieauth.com/)
- Microsub: [https://aperture.p3k.io/](https://aperture.p3k.io/)
- Webmention: [https://webmention.io/](https://webmention.io/)

## License

MIT

---

Inspired by the IndieWeb community.