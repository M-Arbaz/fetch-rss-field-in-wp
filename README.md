# Fetch RSS Field in WordPress

This WordPress snippet/plugin allows you to fetch and display data from an RSS feed directly within your WordPress theme or plugin.

## Features

- Fetch RSS feed from any public URL
- Parse specific fields (title, link, description, date, etc.)
- Easy integration into themes or custom plugins
- Lightweight and fast

## Installation

1. Add the function to your `functions.php` file **or** create a custom plugin.
2. Call the function where you want to display RSS data (e.g., in a template file).

## Usage

### Basic Example

```php
<?php
$rss = fetch_rss_fields('https://example.com/feed');

if (!empty($rss)) {
    foreach ($rss as $item) {
        echo '<h3><a href="' . esc_url($item['link']) . '">' . esc_html($item['title']) . '</a></h3>';
        echo '<p>' . esc_html($item['description']) . '</p>';
    }
}
?>
