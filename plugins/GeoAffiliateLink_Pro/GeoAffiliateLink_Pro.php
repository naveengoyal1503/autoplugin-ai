<?php
/*
Plugin Name: GeoAffiliateLink Pro
Plugin URI: https://example.com/geoaffiliatelink-pro
Description: Cloak, categorize, and geotarget affiliate links with auto insertion.
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliateLink_Pro.php
License: GPLv2 or later
Text Domain: geoaffiliatelink-pro
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class GeoAffiliateLinkPro {
    private static $instance = null;

    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('geo_affiliate_link', array($this, 'geo_affiliate_link_shortcode'));
        add_filter('the_content', array($this, 'auto_insert_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'handle_redirect'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'GeoAffiliateLink Pro',
            'GeoAffiliateLink',
            'manage_options',
            'geoaffiliatelink-pro',
            array($this, 'settings_page'),
            'dashicons-admin-links'
        );
    }

    public function register_settings() {
        register_setting('geoaffiliatelink_pro_options', 'geo_affiliate_links');
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        // Handle new link submission
        if (isset($_POST['new_link_nonce']) && wp_verify_nonce($_POST['new_link_nonce'], 'add_new_link')) {
            $links = get_option('geo_affiliate_links', array());
            $new_link = array(
                'id' => uniqid('gal_'),
                'name' => sanitize_text_field($_POST['link_name']),
                'url' => esc_url_raw($_POST['link_url']),
                'category' => sanitize_text_field($_POST['link_category']),
                'geo' => sanitize_text_field($_POST['link_geo']), // country code
                'cloaked_slug' => sanitize_title($_POST['link_name'])
            );
            $links[] = $new_link;
            update_option('geo_affiliate_links', $links);
            echo '<div class="updated"><p>Link added successfully.</p></div>';
        }

        // Delete link
        if (isset($_GET['delete_link'])) {
            $delete_id = sanitize_text_field($_GET['delete_link']);
            $links = get_option('geo_affiliate_links', array());
            $links = array_filter($links, function($link) use ($delete_id) {
                return $link['id'] !== $delete_id;
            });
            update_option('geo_affiliate_links', $links);
            echo '<div class="updated"><p>Link deleted successfully.</p></div>';
        }

        $links = get_option('geo_affiliate_links', array());
        ?>
        <div class="wrap">
            <h1>GeoAffiliateLink Pro Settings</h1>
            <form method="post">
                <?php wp_nonce_field('add_new_link', 'new_link_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="link_name">Link Name</label></th>
                            <td><input name="link_name" type="text" id="link_name" required class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="link_url">Affiliate URL</label></th>
                            <td><input name="link_url" type="url" id="link_url" required class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="link_category">Category</label></th>
                            <td><input name="link_category" type="text" id="link_category" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="link_geo">Geolocation Country Code (e.g. US, GB)</label></th>
                            <td><input name="link_geo" type="text" id="link_geo" class="regular-text" maxlength="2"></td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit"><input type="submit" class="button button-primary" value="Add New Link"></p>
            </form>
            <h2>Existing Links</h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Category</th>
                        <th>Geo</th>
                        <th>Cloaked Slug</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(empty($links)) : ?>
                    <tr><td colspan="6">No links added yet.</td></tr>
                <?php else : ?>
                    <?php foreach($links as $link) : ?>
                        <tr>
                            <td><?php echo esc_html($link['name']); ?></td>
                            <td><a href="<?php echo esc_url($link['url']); ?>" target="_blank" rel="nofollow noopener noreferrer"><?php echo esc_html($link['url']); ?></a></td>
                            <td><?php echo esc_html($link['category']); ?></td>
                            <td><?php echo esc_html(strtoupper($link['geo'])); ?></td>
                            <td><?php echo esc_html($link['cloaked_slug']); ?></td>
                            <td><a href="?page=geoaffiliatelink-pro&delete_link=<?php echo esc_attr($link['id']); ?>" onclick="return confirm('Are you sure you want to delete this link?');">Delete</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // Detect visitor country code using IP (simple and free method, can be replaced by paid geoIP)
    private function get_visitor_country() {
        if ( ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
            return sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY']); // Cloudflare header
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return '';
        }

        $response = wp_remote_get("https://ipapi.co/{$ip}/country/");
        if (is_wp_error($response)) {
            return '';
        }
        $country = trim(wp_remote_retrieve_body($response));
        if (strlen($country) === 2) {
            return strtoupper($country);
        }
        return '';
    }

    // Shortcode to create cloaked affiliate links: [geo_affiliate_link slug="example-slug" default_url="https://defaultaffiliate.com"]
    public function geo_affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
            'default_url' => ''
        ), $atts, 'geo_affiliate_link');

        if (empty($atts['slug'])) {
            return '';
        }

        $links = get_option('geo_affiliate_links', array());
        $visitor_country = $this->get_visitor_country();

        // Find link that matches visitor country and slug
        foreach ($links as $link) {
            if ($link['cloaked_slug'] === sanitize_title($atts['slug']) && strtoupper($link['geo']) === $visitor_country) {
                return $this->get_cloak_url($link['cloaked_slug']);
            }
        }

        // Fallback: if slug matches but no geo, return default or best available
        foreach ($links as $link) {
            if ($link['cloaked_slug'] === sanitize_title($atts['slug'])) {
                return $this->get_cloak_url($link['cloaked_slug']);
            }
        }

        // Return default URL if provided
        if (!empty($atts['default_url'])) {
            return esc_url($atts['default_url']);
        }

        return '';
    }

    // Cloaked redirect handler
    public function handle_redirect() {
        $request_uri = trim($_SERVER['REQUEST_URI'], '/');
        // Match cloaked slugs
        $links = get_option('geo_affiliate_links', array());
        foreach ($links as $link) {
            if ($link['cloaked_slug'] === $request_uri) {
                wp_redirect($link['url'], 301);
                exit;
            }
        }
    }

    private function get_cloak_url($slug) {
        return home_url('/' . $slug);
    }

    // Automatically insert cloaked affiliate links by category keywords in content
    public function auto_insert_links($content) {
        $links = get_option('geo_affiliate_links', array());
        if (empty($links)) return $content;

        $visitor_country = $this->get_visitor_country();

        foreach ($links as $link) {
            // Only replace content for links that match visitor country or global (empty geo)
            if ($link['geo'] === '' || strtoupper($link['geo']) === $visitor_country) {
                // Simple keyword replacement by category (exact match)
                if (!empty($link['category'])) {
                    $pattern = '/\b' . preg_quote($link['category'], '/') . '\b/i';
                    $replacement = '<a href="' . esc_url($this->get_cloak_url($link['cloaked_slug'])) . '" target="_blank" rel="nofollow noopener noreferrer">' . esc_html($link['category']) . '</a>';
                    $content = preg_replace($pattern, $replacement, $content, 1);
                }
            }
        }

        return $content;
    }

    public function enqueue_scripts() {
        // Currently no front-end scripts needed
    }
}

GeoAffiliateLinkPro::instance();
