// Run RSS import from multiple sources with category, only if image exists
function import_rss_feed_to_posts_on_homepage() {
    if (!function_exists('post_exists')) {
        require_once ABSPATH . 'wp-admin/includes/post.php';
    }
    if (!function_exists('media_sideload_image')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    include_once ABSPATH . WPINC . '/feed.php';

    $feed_sources = [
        // News
        ['rssurl' => 'https://www.vice.com/en/category/news/feed/', 'category' => 'News'],
        ['rssurl' => 'https://metro.co.uk/news/feed/', 'category' => 'News'],
        // Investigations
        ['rssurl' => 'https://www.vice.com/en/tag/Investigations/feed/', 'category' => 'Investigations'],
        // Health
        ['rssurl' => 'https://www.vice.com/en/category/health/feed/', 'category' => 'Health'],
        // Tech
        ['rssurl' => 'https://www.vice.com/en/category/tech/feed/', 'category' => 'Tech'],
        ['rssurl' => 'https://devphics.com/blog/feed/', 'category' => 'Tech'],
        // Entertainment
        ['rssurl' => 'https://metro.co.uk/entertainment/feed/', 'category' => 'Entertainment'],
        ['rssurl' => 'https://www.vice.com/en/category/entertainment/feed/', 'category' => 'Entertainment'],
        // Sports
        ['rssurl' => 'https://www.vice.com/en/category/sports/feed/', 'category' => 'Sports'],
        ['rssurl' => 'https://metro.co.uk/sport/feed/', 'category' => 'Sports']
    ];

    echo "<pre>";

    foreach ($feed_sources as $source) {
        $feed_url = $source['rssurl'];
        $category_name = $source['category'];

        echo "🔄 Checking feed: $feed_url\n";

        $term = term_exists($category_name, 'category');
        if (!$term) {
            $term = wp_insert_term($category_name, 'category');
            echo "✅ Created category: $category_name\n";
        }
        $category_id = is_array($term) ? $term['term_id'] : $term;

        $rss = fetch_feed($feed_url);
        if (is_wp_error($rss)) {
            echo "❌ Error fetching: $feed_url - " . $rss->get_error_message() . "\n";
            continue;
        }

        $rss_items = $rss->get_items(0, 1);
        if (empty($rss_items)) {
            echo "⚠️ No items in: $feed_url\n";
            continue;
        }

        foreach ($rss_items as $item) {
            $title = $item->get_title();
            $link = $item->get_link();
            $date = $item->get_date('Y-m-d H:i:s');

            $full_content = $item->get_item_tags('http://purl.org/rss/1.0/modules/content/', 'encoded');
            $description = !empty($full_content) ? $full_content[0]['data'] : $item->get_description();

            $itunes_image_tag = $item->get_item_tags('http://www.itunes.com/dtds/podcast-1.0.dtd', 'image');
            $image_url = '';
            if (!empty($itunes_image_tag[0]['attribs']['']['href'])) {
                $image_url = $itunes_image_tag[0]['attribs']['']['href'];
            } else {
                preg_match('/<img[^>]+src="([^">]+)"/', $description, $matches);
                if (isset($matches[1])) {
                    $image_url = $matches[1];
                }
            }

            if (post_exists($title)) {
                echo "⏭️ Skipped (already exists): $title\n";
                continue;
            }

            if (empty($image_url)) {
                echo "⛔ Skipped (no image): $title\n";
                continue;
            }

            $post_id = wp_insert_post([
                'post_title'    => wp_strip_all_tags($title),
                'post_content'  => $description . "<br><br><a href='$link' target='_blank'>Source</a>",
                'post_status' => 'future',
                'post_date' => date('Y-m-d H:i:s', strtotime('+2 minutes')),
                'post_date'     => $date,
                'post_type'     => 'post',
                'post_category' => [$category_id],
            ]);

            if ($post_id) {
                $attach_id = media_sideload_image($image_url, $post_id, null, 'id');
                if (!is_wp_error($attach_id)) {
                    set_post_thumbnail($post_id, $attach_id);
                    echo "✅ Imported: $title\n";
                } else {
                    echo "⚠️ Image issue for: $title\n";
                }
            } else {
                echo "❌ Failed to insert: $title\n";
            }
        }
    }

    echo "\n🚀 Feed import completed.\n</pre>";
}

// Trigger with ?update=true on any page
add_action('template_redirect', 'trigger_feed_import_on_update_param');
function trigger_feed_import_on_update_param() {
    if (isset($_GET['update']) && $_GET['update'] === 'true' ) {
        import_rss_feed_to_posts_on_homepage();
        exit;
    }
}

function auto_set_or_delete_posts_without_images() {
    if (!function_exists('media_sideload_image')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    $args = [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    ];

    $posts = get_posts($args);

    echo "<pre>";
    foreach ($posts as $post) {
        $has_thumbnail = has_post_thumbnail($post->ID);
        $content = $post->post_content;

        preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $content, $matches);
        $first_image_url = $matches[1] ?? '';

        if ($has_thumbnail) {
            echo "✅ Post already has featured image: {$post->post_title}\n";
        } elseif ($first_image_url) {
            echo "🖼️ Setting featured image for post: {$post->post_title} - $first_image_url\n";
            $attach_id = media_sideload_image($first_image_url, $post->ID, null, 'id');

            if (!is_wp_error($attach_id)) {
                set_post_thumbnail($post->ID, $attach_id);
                echo "✅ Featured image set for: {$post->post_title}\n";
            } else {
                echo "⚠️ Failed to set image for: {$post->post_title} - " . $attach_id->get_error_message() . "\n";
            }
        } else {
            // No featured image and no image in content → Delete
            wp_delete_post($post->ID, true);
            echo "🗑️ Deleted post (no images): {$post->post_title}\n";
        }
    }
    echo "🚀 Operation complete.</pre>";
    exit;
}

// Trigger with ?thumb=true
add_action('template_redirect', function () {
    if (isset($_GET['thumb']) && $_GET['thumb'] === 'true') {
        auto_set_or_delete_posts_without_images();
    }
});
