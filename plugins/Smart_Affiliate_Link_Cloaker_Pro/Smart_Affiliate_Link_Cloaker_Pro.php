/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak and track affiliate links with analytics. Premium features available.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloaker {
    private static $instance = null;
    public $is_pro = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->is_pro = get_option('smart_affiliate_pro_active', false);
        add_action('init', array($this, 'init'));
        add_shortcode('cloaklink', array($this, 'cloaklink_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_sal_track_click', array($this, 'track_click'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_notices', array($this, 'pro_nag'));
    }

    public function enqueue_assets() {
        wp_enqueue_script('sal-tracker', plugin_dir_url(__FILE__) . 'sal-tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-tracker', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function cloaklink_shortcode($atts) {
        $atts = shortcode_atts(array(
            'url' => '',
            'text' => 'Click Here',
            'id' => uniqid('sal_')
        ), $atts);

        $nonce = wp_create_nonce('sal_click_' . $atts['id']);
        return '<a href="#" class="sal-link" data-url="' . esc_url($atts['url']) . '" data-nonce="' . $nonce . '" data-id="' . esc_attr($atts['id']) . '">' . esc_html($atts['text']) . '</a>';
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sal_click_' . $_POST['id'])) {
            wp_die('Security check failed');
        }
        $url = esc_url_raw($_POST['url']);
        $id = sanitize_key($_POST['id']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $stats = get_option('sal_stats', array());
        $stats[$id][] = array(
            'time' => current_time('mysql'),
            'ip' => $ip,
            'ua' => substr($user_agent, 0, 100)
        );
        update_option('sal_stats', $stats);

        if ($this->is_pro) {
            // Premium: Advanced tracking
            error_log('Pro click tracked: ' . $url);
        }

        wp_redirect($url);
        exit;
    }

    public function admin_menu() {
        add_options_page('Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'sal-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sal_pro_key']) && $this->activate_pro($_POST['sal_pro_key'])) {
            echo '<div class="notice notice-success"><p>Pro activated!</p></div>';
        }
        include plugin_dir_path(__FILE__) . 'settings.php';
    }

    private function activate_pro($key) {
        // Simulate pro activation (in real: validate with API)
        if ($key === 'prokey123') {
            update_option('smart_affiliate_pro_active', true);
            $this->is_pro = true;
            return true;
        }
        return false;
    }

    public function pro_nag() {
        if (!current_user_can('manage_options') || $this->is_pro) return;
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Cloaker Pro:</strong> Unlock A/B testing, detailed analytics & more! <a href="' . admin_url('options-general.php?page=sal-settings') . '">Upgrade now</a> for $9/mo.</p></div>';
    }

    public function activate() {
        add_option('sal_stats', array());
    }

    public function deactivate() {
        // Keep data
    }
}

// Tracker JS (embedded)
function sal_tracker_js() {
    if (!wp_script_is('sal-tracker', 'enqueued')) return;
?>
<script>
jQuery(document).ready(function($) {
    $('.sal-link').on('click', function(e) {
        e.preventDefault();
        var $this = $(this);
        $.post(sal_ajax.ajaxurl, {
            action: 'sal_track_click',
            url: $this.data('url'),
            nonce: $this.data('nonce'),
            id: $this.data('id')
        }, function() {
            window.location = $this.data('url');
        });
    });
});
</script>
<?php
}
add_action('wp_footer', 'sal_tracker_js');

SmartAffiliateCloaker::get_instance();

// Simple settings.php content (embedded as string for single file)
function sal_embed_settings() {
    echo '<div class="wrap"><h1>Affiliate Cloaker Settings</h1><form method="post"><table class="form-table">';
    echo '<tr><th>Stats</th><td><pre>' . esc_html(print_r(get_option('sal_stats', array()), true)) . '</pre></td></tr>';
    echo '<tr><th>Pro Key</th><td><input type="text" name="sal_pro_key" placeholder="Enter Pro Key"><input type="submit" value="Activate Pro" class="button-primary"></td></tr>';
    echo '</table></form><p><a href="https://example.com/pro-upgrade" target="_blank">Buy Pro ($9/mo)</a></p></div>';
}
// Call in settings_page via include simulation
echo 'Settings page uses embedded form above.';
?>