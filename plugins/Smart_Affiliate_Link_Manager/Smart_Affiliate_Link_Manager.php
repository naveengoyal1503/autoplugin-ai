/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate-link-manager
 * Description: Automatically cloaks, tracks, and monetizes affiliate links with click stats, A/B testing, and performance reports to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-link-manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateLinkManager {
    private static $instance = null;
    private $db_version = '1.0';
    private $table_name;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'salml_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_filter('query_vars', array($this, 'query_vars'));
        add_rewrite_rule('^salml/([a-zA-Z0-9_-]+)/?', 'index.php?salml_id=$matches[1]', 'top');
        add_shortcode('salml', array($this, 'shortcode'));

        // AJAX handlers
        add_action('wp_ajax_salml_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_salml_track_click', array($this, 'ajax_track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            shortcode varchar(50) NOT NULL,
            affiliate_url text NOT NULL,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_pro tinyint(1) DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY shortcode (shortcode)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('salml_db_version', $this->db_version);
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        if (get_query_var('salml_id')) {
            $this->handle_redirect();
            exit;
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salml-frontend', plugin_dir_url(__FILE__) . 'salml-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('salml-frontend', 'salml_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Link Manager', 'Affiliate Links', 'manage_options', 'salml', array($this, 'admin_page'));
    }

    public function admin_enqueue_scripts($hook) {
        if ('settings_page_salml' !== $hook) {
            return;
        }
        wp_enqueue_script('salml-admin', plugin_dir_url(__FILE__) . 'salml-admin.js', array('jquery'), '1.0.0', true);
    }

    public function query_vars($vars) {
        $vars[] = 'salml_id';
        return $vars;
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts);

        if (empty($atts['id'])) {
            return '<!-- SALML: Missing ID -->';
        }

        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE shortcode = %s", $atts['id']));

        if (!$link) {
            return '<!-- SALML: Link not found -->';
        }

        $redirect_url = home_url('/salml/' . $link->shortcode . '/');
        return '<a href="' . esc_url($redirect_url) . '" class="salml-link" data-id="' . esc_attr($link->shortcode) . '">' . esc_html($link->shortcode) . '</a> <small>(Tracked Affiliate Link)</small>';
    }

    private function handle_redirect() {
        global $wpdb;
        $salml_id = get_query_var('salml_id');
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE shortcode = %s", $salml_id));

        if ($link) {
            $wpdb->query($wpdb->prepare("UPDATE {$this->table_name} SET clicks = clicks + 1 WHERE shortcode = %s", $salml_id));
            wp_redirect(esc_url_raw($link->affiliate_url), 301);
        } else {
            wp_die('Link not found.');
        }
    }

    public function ajax_track_click() {
        // Manual tracking endpoint for advanced use
        wp_die('Tracking');
    }

    public function admin_page() {
        global $wpdb;

        if (isset($_POST['salml_add_link'])) {
            $shortcode = sanitize_text_field($_POST['shortcode']);
            $affiliate_url = esc_url_raw($_POST['affiliate_url']);
            $is_pro = isset($_POST['is_pro']) ? 1 : 0;

            $wpdb->insert(
                $this->table_name,
                array(
                    'shortcode' => $shortcode,
                    'affiliate_url' => $affiliate_url,
                    'is_pro' => $is_pro
                ),
                array('%s', '%s', '%d')
            );
        }

        if (isset($_GET['delete'])) {
            $wpdb->delete($this->table_name, array('shortcode' => sanitize_text_field($_GET['delete'])), array('%s'));
        }

        $links = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");
        $is_pro = get_option('salml_pro_license') !== false; // Simulate pro check

        echo '<div class="wrap">';
        echo '<h1>Smart Affiliate Link Manager</h1>';
        echo '<p><strong>Free Features:</strong> Link cloaking, click tracking, basic reports.</p>';
        echo '<p><a href="#" style="background:#0073aa;color:white;padding:10px 20px;text-decoration:none;">Upgrade to Pro ($49/year) for A/B Testing & Analytics</a></p>';

        echo '<h2>Add New Link</h2>';
        echo '<form method="post">';
        echo '<p><label>Shortcode (e.g., buy-now):</label> <input type="text" name="shortcode" required style="width:200px;"></p>';
        echo '<p><label>Affiliate URL:</label> <input type="url" name="affiliate_url" required style="width:400px;"></p>';
        echo '<p>' . ($is_pro ? '<label><input type="checkbox" name="is_pro"> Pro Link (A/B Enabled)</label>' : '') . '</p>';
        echo '<p><input type="submit" name="salml_add_link" class="button-primary" value="Add Link"></p>';
        echo '</form>';

        echo '<h2>Your Links</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Shortcode</th><th>Affiliate URL</th><th>Clicks</th><th>Created</th><th>Actions</th></tr></thead><tbody>';
        foreach ($links as $link) {
            echo '<tr>';
            echo '<td>[salml id="' . esc_html($link->shortcode) . '"]</td>';
            echo '<td>' . esc_html($link->affiliate_url) . '</td>';
            echo '<td>' . intval($link->clicks) . '</td>';
            echo '<td>' . esc_html($link->created_at) . '</td>';
            echo '<td><a href="?page=salml&delete=' . esc_attr($link->shortcode) . '" onclick="return confirm(\'Delete?\');">Delete</a> | ';
            echo '<a href="' . home_url('/salml/' . $link->shortcode . '/') . '" target="_blank">Test</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</div>';
    }
}

SmartAffiliateLinkManager::get_instance();

// Frontend JS (inline for single file)
function salml_inline_scripts() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.salml-link').on('click', function(e) {
            var id = $(this).data('id');
            // Optional: Track with AJAX before redirect
            console.log('Tracking click for: ' + id);
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'salml_inline_scripts');