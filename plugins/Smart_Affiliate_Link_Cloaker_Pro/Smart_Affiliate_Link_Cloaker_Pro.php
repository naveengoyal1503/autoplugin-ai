/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak affiliate links, track clicks, and optimize conversions. Free version with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCloakerPro {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->is_premium = get_option('sacp_license_key') !== false;
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sacp_track_click', array($this, 'track_click'));
        add_shortcode('sacp_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-cloaker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sacp-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sacp-tracker', 'sacp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sacp_nonce')));
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Click Here',
            'id' => 'default'
        ), $atts);

        if (empty($atts['url'])) return '';

        $id = sanitize_text_field($atts['id']);
        $slug = sanitize_title($id);

        // Store link mapping
        $links = get_option('sacp_links', array());
        $links[$slug] = esc_url_raw($atts['url']);
        update_option('sacp_links', $links);

        $href = $this->is_premium ? home_url("/sacp/{$slug}/") : $atts['url'];

        return '<a href="' . $href . '" class="sacp-link" data-id="' . $slug . '" data-nonce="' . wp_create_nonce('sacp_nonce') . '">' . esc_html($atts['text']) . '</a>';
    }

    public function track_click() {
        check_ajax_referer('sacp_nonce', 'nonce');
        $id = sanitize_text_field($_POST['id']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $stats = get_option('sacp_stats', array());
        $stats[$id]['clicks'][] = array(
            'ip' => $ip,
            'ua' => substr($user_agent, 0, 100),
            'time' => current_time('mysql')
        );
        update_option('sacp_stats', $stats);

        $links = get_option('sacp_links', array());
        if (isset($links[$id])) {
            if ($this->is_premium) {
                wp_send_json_success(array('redirect' => $links[$id]));
            } else {
                wp_redirect($links[$id]);
                exit;
            }
        }
        wp_die();
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Cloaker Pro',
            'Affiliate Cloaker',
            'manage_options',
            'sacp-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['sacp_license'])) {
            update_option('sacp_license_key', sanitize_text_field($_POST['sacp_license']));
            $this->is_premium = true;
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        if (isset($_POST['sacp_upgrade'])) {
            echo '<div class="notice notice-info"><p>Upgrade to premium for A/B testing, geo-targeting & more: <strong>$49/year</strong></p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Pro</h1>
            <?php if (!$this->is_premium): ?>
            <form method="post">
                <p><label>Enter Premium License Key:</label> <input type="text" name="sacp_license" placeholder="Enter key to unlock premium"></p>
                <p><input type="submit" class="button-primary" value="Activate Premium"></p>
            </form>
            <form method="post"><input type="submit" name="sacp_upgrade" class="button" value="Learn More About Premium"></form>
            <?php else: ?>
            <p><strong>Premium Active!</strong></p>
            <?php endif; ?>
            <h2>Links</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>URL</th><th>Clicks</th></tr></thead>
                <tbody>
                <?php
                $links = get_option('sacp_links', array());
                $stats = get_option('sacp_stats', array());
                foreach ($links as $id => $url) {
                    $clicks = isset($stats[$id]) ? count($stats[$id]['clicks']) : 0;
                    echo "<tr><td>{$id}</td><td>{$url}</td><td>{$clicks}</td></tr>
                    ";
                }
                ?>
                </tbody>
            </table>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[sacp_link url="https://affiliate.com" text="Buy Now" id="unique1"]</code></p>
            <?php if ($this->is_premium): ?>
            <p>Premium features: A/B testing, geo-targeting unlocked.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function activate() {
        add_rewrite_rule('^sacp/([^/]+)/?', 'index.php?sacp=$matches[1]', 'top');
        flush_rewrite_rules();
    }
}

// Tracker JS inline
add_action('wp_head', function() {
    echo '<script>jQuery(document).ready(function($) { $(".sacp-link").click(function(e) { e.preventDefault(); var id = $(this).data("id"); var nonce = $(this).data("nonce"); $.post(sacp_ajax.ajax_url, {action: "sacp_track_click", id: id, nonce: nonce}, function(res) { if (res.success) { window.location = res.data.redirect; } }); }); });</script>';
});

SmartAffiliateCloakerPro::get_instance();