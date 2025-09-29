# IndieWeb Blog

A minimal PHP-based IndieWeb blog supporting [IndieAuth](https://indieweb.org/IndieAuth), [Micropub](https://indieweb.org/Micropub), and [Webmention](https://indieweb.org/Webmention).

## Features

- IndieAuth authentication via [IndieAuth](https://indieauth.com/)
- Microformats2 support
- Micropub endpoint (`/micropub.php`) compatible with [Quill](https://quill.p3k.io/)
- Syndication to other Micropub endpoints
- Send and receive Webmentions via [Telegraph](https://telegraph.p3k.io/) and [Webmention.io](https://webmention.io/)
- Microsub endpoint via [Aperture](https://aperture.p3k.io/)
- Minimal, file-based storage
- RSS Feed (`/feed.php`) and JSON Feed (`/feed.php?format=json`)

## Getting Started

1. **Clone this repository**

2. **Copy and configure `config.php`:**
   ```sh
   cp example.config.php config.php
   ```
   Edit `config.php` to configure your settings.

3. **Deploy to your PHP web server.**

## License

MIT

---

Inspired by the IndieWeb community.