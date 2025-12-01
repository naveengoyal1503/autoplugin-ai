/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_SmartPaywall.php
*/
<?php
/**
 * Plugin Name: WP SmartPaywall
 * Description: Intelligent paywall for WordPress content monetization.
 * Version: 1.0
 * Author: WP Dev Team
 */

define('WP_SMARTPAYWALL_VERSION', '1.0');

class WPSmartPaywall {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'insert_paywall_modal'));
        add_shortcode('smartpaywall', array($this, 'paywall_shortcode'));
    }

    public function init() {
        // Register settings
        register_setting('wp_smartpaywall_settings', 'wp_smartpaywall_enabled');
        register_setting('wp_smartpaywall_settings', 'wp_smartpaywall_threshold');
        register_setting('wp_smartpaywall_settings', 'wp_smartpaywall_message');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-smartpaywall-style', plugin_dir_url(__FILE__) . 'css/style.css');
        wp_enqueue_script('wp-smartpaywall-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), WP_SMARTPAYWALL_VERSION, true);
    }

    public function insert_paywall_modal() {
        if (get_option('wp_smartpaywall_enabled') !== '1') return;
        $threshold = get_option('wp_smartpaywall_threshold', 50);
        $message = get_option('wp_smartpaywall_message', 'Subscribe to unlock more content!');
        echo '<div id="wp-smartpaywall-modal" style="display:none;">
                <div class="wp-smartpaywall-content">
                    <p>' . esc_html($message) . '</p>
                    <button onclick="document.getElementById(\'wp-smartpaywall-modal\').style.display=\'none\';">Close</button>
                </div>
              </div>';
    }

    public function paywall_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'threshold' => 50,
            'message' => 'Subscribe to unlock more content!'
        ), $atts, 'smartpaywall');

        $threshold = intval($atts['threshold']);
        $message = esc_html($atts['message']);

        if (is_user_logged_in()) {
            return $content;
        }

        $scroll_percent = '<script>document.addEventListener("scroll", function(){
            var scrollPercent = (window.scrollY / (document.body.scrollHeight - window.innerHeight)) * 100;
            if (scrollPercent > ' . $threshold . ') {
                document.getElementById("wp-smartpaywall-modal").style.display = "block";
            }
        });</script>';

        return $scroll_percent . '<div class="wp-smartpaywall-content">' . $content . '</div>';
    }
}

new WPSmartPaywall();

// Activation hook
register_activation_hook(__FILE__, 'wp_smartpaywall_activate');
function wp_smartpaywall_activate() {
    add_option('wp_smartpaywall_enabled', '1');
    add_option('wp_smartpaywall_threshold', '50');
    add_option('wp_smartpaywall_message', 'Subscribe to unlock more content!');
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'wp_smartpaywall_deactivate');
function wp_smartpaywall_deactivate() {
    delete_option('wp_smartpaywall_enabled');
    delete_option('wp_smartpaywall_threshold');
    delete_option('wp_smartpaywall_message');
}
?>