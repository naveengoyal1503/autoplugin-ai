<?php
/*
Plugin Name: ContentLocker Pro
Description: Monetize your premium content by locking it behind a customizable paywall or subscription.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentLocker_Pro.php
License: GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ContentLockerPro {
    const OPTION_NAME = 'clp_paywall_settings';

    public function __construct() {
        add_shortcode('contentlocker', array($this, 'render_locker'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_clp_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_clp_process_payment', array($this, 'process_payment'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('clp-script', plugin_dir_url(__FILE__) . 'clp-script.js', array('jquery'), '1.0', true);
        wp_localize_script('clp-script', 'clp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
        wp_enqueue_style('clp-style', plugin_dir_url(__FILE__) . 'clp-style.css');
    }

    public function admin_menu() {
        add_menu_page('ContentLocker Pro Settings', 'ContentLocker Pro', 'manage_options', 'clp-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting(self::OPTION_NAME, self::OPTION_NAME);
        add_settings_section('clp_main_section', 'General Settings', null, 'clp-settings');
        add_settings_field('clp_payment_email', 'Payment Receiver Email', array($this, 'field_payment_email'), 'clp-settings', 'clp_main_section');
        add_settings_field('clp_locker_button_text', 'Locker Button Text', array($this, 'field_button_text'), 'clp-settings', 'clp_main_section');
    }

    public function field_payment_email() {
        $options = get_option(self::OPTION_NAME);
        echo '<input type="email" name="'. esc_attr(self::OPTION_NAME) .'[payment_email]" value="'. esc_attr($options['payment_email'] ?? '') .'" class="regular-text" />';
    }

    public function field_button_text() {
        $options = get_option(self::OPTION_NAME);
        $value = $options['locker_button_text'] ?? 'Unlock Content';
        echo '<input type="text" name="'. esc_attr(self::OPTION_NAME) .'[locker_button_text]" value="'. esc_attr($value) .'" class="regular-text" />';
        echo '<p class="description">Text displayed on the content unlock button.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>ContentLocker Pro Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_NAME);
                do_settings_sections('clp-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_locker($atts, $content = null) {
        if (!$content) return '';

        // Check if user has access cookie
        if (isset($_COOKIE['content_unlocked']) && $_COOKIE['content_unlocked'] === 'yes') {
            return do_shortcode($content);
        }

        $options = get_option(self::OPTION_NAME);
        $button_text = $options['locker_button_text'] ?? 'Unlock Content';

        // Output locker markup
        ob_start();
        ?>
        <div class="clp-locker">
            <div class="clp-locked-message">
                <p>This content is locked. Please pay to unlock.</p>
                <button id="clp-pay-btn"><?php echo esc_html($button_text); ?></button>
                <div id="clp-status" style="margin-top:10px;color:green;"></div>
            </div>
        </div>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#clp-pay-btn').on('click', function(){
                var btn = $(this);
                btn.prop('disabled', true).text('Processing...');
                $.post(clp_ajax.ajaxurl, {action: 'clp_process_payment'}, function(response){
                    if(response.success) {
                        $('#clp-status').text('Payment successful! Content unlocked.');
                        btn.hide();
                        location.reload();
                    } else {
                        $('#clp-status').css('color', 'red').text('Payment failed: ' + response.data);
                        btn.prop('disabled', false).text('<?php echo esc_js($button_text); ?>');
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function process_payment() {
        // This is a mock payment processor for demo purposes
        // In real plugin, integrate with a payment gateway SDK/API

        // For demonstration, simulate success randomly
        if (rand(0, 1) === 1) {
            // Set a cookie for 1 day to mark content unlocked
            setcookie('content_unlocked', 'yes', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl());
            wp_send_json_success();
        } else {
            wp_send_json_error('Payment processing failed. Please try again later.');
        }
        wp_die();
    }
}
new ContentLockerPro();

// Minimal CSS for locker styling
add_action('wp_head', function(){
    echo '<style>.clp-locker {border: 1px solid #ccc; padding: 15px; background: #fafafa; max-width: 400px; margin: 1em auto; text-align: center;} .clp-locker button {background: #0073aa; color: white; border: none; padding: 10px 20px; font-size:16px; cursor:pointer;} .clp-locker button:disabled {background: #555; cursor: not-allowed;}</style>';
});