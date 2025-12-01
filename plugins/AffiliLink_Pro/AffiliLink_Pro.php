/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliLink_Pro.php
*/
<?php
/**
 * Plugin Name: AffiliLink Pro
 * Plugin URI: https://example.com/affililink-pro
 * Description: Manage, cloak and track affiliate links with WooCommerce integration to maximize affiliate earnings.
 * Version: 1.0.0
 * Author: Plugin Developer
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliLinkPro {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'affililink_clicks';
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affililink', array($this, 'affililink_shortcode'));
        add_action('wp', array($this, 'handle_redirect'));
        add_action('admin_post_affililink_add', array($this, 'handle_add_link'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id VARCHAR(191) NOT NULL,
            clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
            last_click DATETIME DEFAULT NULL,
            PRIMARY KEY(id),
            UNIQUE KEY affiliate_id (affiliate_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate() {
        // Optional: clean up tasks
    }

    public function admin_menu() {
        add_menu_page(
            'AffiliLink Pro',
            'AffiliLink Pro',
            'manage_options',
            'affililink_pro',
            array($this, 'admin_page'),
            'dashicons-admin-links',
            80
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        global $wpdb;

        // Handle messages
        $message = '';
        if (isset($_GET['added'])) {
            $message = '<div class="updated"><p>Affiliate Link Added Successfully.</p></div>';
        }

        // Fetch all affiliate links
        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY clicks DESC");

        echo '<div class="wrap">';
        echo '<h1>AffiliLink Pro - Manage Affiliate Links</h1>';
        echo $message;
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="affililink_add">';
        echo '<table class="form-table"><tbody>';
        echo '<tr><th scope="row"><label for="affiliate_id">Affiliate ID (slug)</label></th><td><input name="affiliate_id" type="text" id="affiliate_id" value="" class="regular-text" required></td></tr>';
        echo '<tr><th scope="row"><label for="target_url">Target URL</label></th><td><input name="target_url" type="url" id="target_url" value="" class="regular-text" required></td></tr>';
        echo '</tbody></table>';        
        submit_button('Add Affiliate Link');
        echo '</form>';

        echo '<h2>Existing Affiliate Links</h2>';
        if (empty($links)) {
            echo '<p>No affiliate links added yet.</p>';
        } else {
            echo '<table class="widefat fixed" cellspacing="0">';
            echo '<thead><tr><th>Affiliate ID</th><th>Clicks</th><th>Last Click</th><th>Short Link</th></tr></thead>';
            echo '<tbody>';
            foreach ($links as $link) {
                $short_url = site_url('/affililink/' . rawurlencode($link->affiliate_id));
                echo '<tr>';
                echo '<td>' . esc_html($link->affiliate_id) . '</td>';
                echo '<td>' . intval($link->clicks) . '</td>';
                echo '<td>' . ($link->last_click ? esc_html($link->last_click) : 'Never') . '</td>';
                echo '<td><a href="' . esc_url($short_url) . '" target="_blank">' . esc_html($short_url) . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }

    public function handle_add_link() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer();

        $affiliate_id = isset($_POST['affiliate_id']) ? sanitize_title($_POST['affiliate_id']) : '';
        $target_url = isset($_POST['target_url']) ? esc_url_raw($_POST['target_url']) : '';

        if (empty($affiliate_id) || empty($target_url)) {
            wp_redirect(admin_url('admin.php?page=affililink_pro&error=1'));
            exit;
        }

        // Save link in options with target URL
        update_option('affililink_target_' . $affiliate_id, $target_url);

        global $wpdb;

        // Insert or ignore to clicks table
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO $this->table_name (affiliate_id, clicks) VALUES (%s, 0)",
            $affiliate_id
        ));

        wp_redirect(admin_url('admin.php?page=affililink_pro&added=1&nonce=' . wp_create_nonce('add')));
        exit;
    }

    public function handle_redirect() {
        $request_uri = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        if (strpos($request_uri, 'affililink/') === 0) {
            $parts = explode('/', $request_uri);
            if (count($parts) == 2) {
                $affiliate_id = sanitize_title($parts[1]);
                $target_url = get_option('affililink_target_' . $affiliate_id);
                if ($target_url) {
                    $this->record_click($affiliate_id);
                    wp_redirect($target_url, 302);
                    exit;
                } else {
                    status_header(404);
                    echo 'Affiliate link not found.';
                    exit;
                }
            }
        }
    }

    private function record_click($affiliate_id) {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "INSERT INTO $this->table_name (affiliate_id, clicks, last_click) VALUES (%s, 1, NOW()) ON DUPLICATE KEY UPDATE clicks = clicks + 1, last_click = NOW()",
            $affiliate_id
        ));
    }

    public function affililink_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'text' => 'Affiliate Link'
        ), $atts, 'affililink');

        if (empty($atts['id'])) {
            return '';
        }

        $link_url = site_url('/affililink/' . rawurlencode(sanitize_title($atts['id'])));
        $text = esc_html($atts['text']);

        return '<a href="' . esc_url($link_url) . '" target="_blank" rel="nofollow noopener">' . $text . '</a>';
    }
}

new AffiliLinkPro();
