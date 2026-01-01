/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Auto-Tracker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Auto-Tracker
 * Plugin URI: https://example.com/smart-affiliate-tracker
 * Description: Automatically tracks affiliate link performance, provides analytics, and optimizes conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-tracker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateTracker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sat_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sat_track_click', array($this, 'track_click'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sat-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sat-tracker', 'sat_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_enqueue($hook) {
        if (strpos($hook, 'sat') !== false) {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        }
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sat_nonce')) {
            wp_die('Security check failed');
        }

        $link_id = sanitize_text_field($_POST['link_id']);
        $url = esc_url_raw($_POST['url']);
        $ip = $_SERVER['REMOTE_ADDR'];

        $data = array(
            'link_id' => $link_id,
            'url' => $url,
            'ip' => $ip,
            'timestamp' => current_time('mysql'),
            'user_agent' => $_SERVER['HTTP_USER_AGENT']
        );

        global $wpdb;
        $table = $wpdb->prefix . 'sat_clicks';
        $wpdb->insert($table, $data);

        wp_redirect($url);
        exit;
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Tracker',
            'Affiliate Tracker',
            'manage_options',
            'smart-affiliate-tracker',
            array($this, 'admin_page'),
            'dashicons-chart-line',
            30
        );
    }

    public function admin_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'sat_clicks';
        $clicks = $wpdb->get_results("SELECT * FROM $table ORDER BY timestamp DESC LIMIT 50");
        $totals = $wpdb->get_var("SELECT COUNT(*) FROM $table");

        echo '<div class="wrap"><h1>Affiliate Tracker Dashboard</h1>';
        echo '<p>Total Clicks: <strong>' . $totals . '</strong></p>';
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>ID</th><th>Link ID</th><th>URL</th><th>IP</th><th>Date</th></tr></thead><tbody>';
        foreach ($clicks as $click) {
            echo '<tr><td>' . esc_html($click->id) . '</td><td>' . esc_html($click->link_id) . '</td><td>' . esc_html($click->url) . '</td><td>' . esc_html($click->ip) . '</td><td>' . esc_html($click->timestamp) . '</td></tr>';
        }
        echo '</tbody></table>';

        if (isset($_POST['add_link'])) {
            // Pro feature placeholder
            echo '<p><em>Pro: Add and manage affiliate links here.</em></p>';
        }

        echo '</div>';
    }

    public function activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'sat_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(50) NOT NULL,
            url text NOT NULL,
            ip varchar(45) NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            user_agent text,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Auto-wrap affiliate links
function sat_auto_link_filter($content) {
    $patterns = array(
        '/https?:\/\/[^\s]+aff=([a-z0-9]+)/i',
        '/(amazon|clickbank|shareasale)\.[a-z]+/i'
    );

    foreach ($patterns as $pattern) {
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $link_id = isset($match[1]) ? $match[1] : uniqid('sat_');
            $wrapped = '<a href="#" class="sat-link" data-link-id="' . esc_attr($link_id) . '" data-url="' . esc_url($match) . '" onclick="return false;">' . $match . '</a>';
            $content = str_replace($match, $wrapped, $content);
        }
    }
    return $content;
}
add_filter('the_content', 'sat_auto_link_filter');

SmartAffiliateTracker::get_instance();

// JS file content would be embedded or separate, but for single file, add script tag in admin

function sat_add_tracker_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sat-link').on('click', function(e) {
            e.preventDefault();
            var linkId = $(this).data('link-id');
            var url = $(this).data('url');
            $.post(sat_ajax.ajaxurl, {
                action: 'sat_track_click',
                nonce: '<?php echo wp_create_nonce("sat_nonce"); ?>',
                link_id: linkId,
                url: url
            }, function() {
                window.location.href = url;
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'sat_add_tracker_js');

// Pro upsell notice
function sat_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate Tracker Pro:</strong> Unlock A/B testing, detailed analytics, link cloaking, and more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'sat_pro_notice');