<?php
/*
Plugin Name: Affiliate Booster Pro
Description: Smart affiliate link management with auto-insertion, cloaking, and analytics.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateBoosterPro {
    private $option_name = 'abp_links';

    function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'auto_insert_links'));
        add_action('init', array($this, 'handle_redirect'));
        add_action('wp_ajax_abp_mark_click', array($this, 'mark_click')); // For tracking clicks
        add_action('wp_footer', array($this, 'insert_tracking_js'));
    }

    // Admin menu
    function admin_menu() {
        add_menu_page('Affiliate Booster', 'Affiliate Booster', 'manage_options', 'affiliate-booster', array($this, 'admin_page'), 'dashicons-networking', 80);
    }

    // Settings page
    function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Booster Pro</h1>
            <form action="options.php" method="POST">
                <?php
                settings_fields('abp_settings_group');
                do_settings_sections('affiliate-booster');
                submit_button('Save Links');
                ?>
            </form>
            <h2>Existing Affiliate Links</h2>
            <table class="widefat fixed">
                <thead><tr><th>ID</th><th>Slug</th><th>URL</th><th>Category</th></tr></thead>
                <tbody>
                <?php
                $links = get_option($this->option_name, array());
                if (!empty($links)) {
                    foreach ($links as $id => $link) {
                        echo '<tr><td>' . esc_html($id) . '</td><td><code>' . esc_html($link['slug']) . '</code></td><td>' . esc_url($link['url']) . '</td><td>' . esc_html($link['category']) . '</td></tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No affiliate links added yet.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    function settings_init() {
        register_setting('abp_settings_group', $this->option_name, array($this, 'validate_links'));

        add_settings_section('abp_section_main', 'Manage Your Affiliate Links', null, 'affiliate-booster');

        add_settings_field('abp_field_links', 'Affiliate Links', array($this, 'links_field_cb'), 'affiliate-booster', 'abp_section_main');
    }

    // Textarea for inputting links as JSON
    function links_field_cb() {
        $links = get_option($this->option_name, array());
        echo '<textarea name="' . esc_attr($this->option_name) . '" rows="10" cols="70" style="font-family: monospace;">' . esc_textarea(json_encode($links, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">Enter your affiliate links as JSON, e.g.: [{"slug":"amazon","url":"https://amazon.com/product","category":"shopping"}]</p>';
    }

    // Validate and sanitize JSON input
    function validate_links($input) {
        if (is_string($input)) {
            $decoded = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // sanitize escaping
                $clean = array();
                foreach ($decoded as $index => $link) {
                    if (isset($link['slug'], $link['url'])) {
                        $clean[$index] = array(
                            'slug' => sanitize_title($link['slug']),
                            'url' => esc_url_raw($link['url']),
                            'category' => isset($link['category']) ? sanitize_text_field($link['category']) : ''
                        );
                    }
                }
                return $clean;
            }
        } elseif (is_array($input)) {
            return $input;
        }
        add_settings_error($this->option_name, 'invalid_json', 'Invalid JSON format.');
        return get_option($this->option_name, array());
    }

    // Automatically insert affiliate links in content
    function auto_insert_links($content) {
        $links = get_option($this->option_name, array());
        if (empty($links)) return $content;

        // Simplified: For each affiliate slug, insert cloaked link after first matching keyword in the content
        foreach ($links as $link) {
            $slug = $link['slug'];
            $url = admin_url('admin-ajax.php?action=abp_redirect&slug=' . urlencode($slug));

            // Find a keyword from slug to replace - here we use slug as keyword for simplicity
            $keyword = $slug;
            if (stripos($content, $keyword) !== false) {
                // Cloaked link: link to plugin redirect URL
                $anchor = ucfirst($keyword);
                $replacement = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noreferrer noopener">' . esc_html($anchor) . '</a>';

                // Replace only once
                $content = preg_replace('/\b(' . preg_quote($keyword, '/') . ')\b/i', $replacement, $content, 1);
            }
        }

        return $content;
    }

    // Redirect handler to actual affiliate URL + tracking click
    function handle_redirect() {
        if (isset($_GET['action']) && $_GET['action'] === 'abp_redirect' && isset($_GET['slug'])) {
            $slug = sanitize_title($_GET['slug']);
            $links = get_option($this->option_name, array());
            foreach ($links as $link) {
                if ($link['slug'] === $slug) {
                    // Increment click count (transient or option) - simplified here
                    $clicks = get_option('abp_clicks_' . $slug, 0);
                    update_option('abp_clicks_' . $slug, $clicks + 1);

                    // Redirect to actual URL
                    wp_redirect($link['url']);
                    exit;
                }
            }
            wp_die('Affiliate link not found.');
        }
    }

    // AJAX endpoint for marking clicks (optional, for advanced tracking)
    function mark_click() {
        check_ajax_referer('abp_nonce', 'nonce');
        $slug = isset($_POST['slug']) ? sanitize_title($_POST['slug']) : '';
        if ($slug) {
            $clicks = get_option('abp_clicks_' . $slug, 0);
            update_option('abp_clicks_' . $slug, $clicks + 1);
            wp_send_json_success(array('clicks' => $clicks + 1));
        }
        wp_send_json_error('Invalid slug');
    }

    // Insert JavaScript for tracking clicks if needed
    function insert_tracking_js() {
        ?>
        <script>
        // Placeholder for advanced click tracking if needed
        </script>
        <?php
    }
}

new AffiliateBoosterPro();
