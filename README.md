# WordPress RSS Feed Importer with Image Filter and Auto-Thumbnail

This script automatically imports posts from multiple RSS feed sources into your WordPress site. It assigns categories, filters entries without images, and sets featured images. It also includes a utility to clean up posts without thumbnails.

## Features

- ✅ Imports latest post from multiple RSS sources
- ✅ Automatically creates categories if they don't exist
- ✅ Only imports posts that include an image
- ✅ Sets the image as the featured thumbnail
- ✅ Schedules imported posts
- ✅ Optional cleanup: deletes or updates posts missing a featured image

---

## Installation

1. Add the provided code to your theme's `functions.php` file or a custom plugin.
2. Make sure the following WordPress core files are included for media handling:
   - `wp-admin/includes/media.php`
   - `wp-admin/includes/file.php`
   - `wp-admin/includes/image.php`
   - `wp-admin/includes/post.php`

---

## Usage

### 🔄 Trigger Feed Import

Visit any page on your site with the following query string:

