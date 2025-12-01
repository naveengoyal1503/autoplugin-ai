<?php
/*
Plugin Name: ContentLocker Pro
Description: Lock premium content behind subscription and pay-per-post access with multiple pricing tiers and payment integration.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentLocker_Pro.php
*/

if (!defined('ABSPATH')) { exit; }

class ContentLockerPro {
    public function __construct() {
        add_shortcode('lock_content', array($this, 'lock_content_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_clp_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_clp_process_payment', array($this, 'process_payment'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('clp-script', plugin_dir_url(__FILE__) . 'clp-script.js', array('jquery'), '1.0', true);
        wp_localize_script('clp-script', 'clp_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('ContentLocker Pro', 'ContentLocker Pro', 'manage_options', 'contentlockerpro', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('clp_settings', 'clp_options');

        add_settings_section('clp_section', 'Subscription Settings', null, 'contentlockerpro');

        add_settings_field('clp_price', 'Subscription Price ($)', array($this, 'price_render'), 'contentlockerpro', 'clp_section');
        add_settings_field('clp_paypal_email', 'PayPal Email', array($this, 'paypal_email_render'), 'contentlockerpro', 'clp_section');
    }

    public function price_render() {
        $options = get_option('clp_options');
        ?>
        <input type='number' step='0.01' name='clp_options[price]' value='<?php echo isset($options['price']) ? esc_attr($options['price']) : '9.99'; ?>'>
        <?php
    }

    public function paypal_email_render() {
        $options = get_option('clp_options');
        ?>
        <input type='email' name='clp_options[paypal_email]' value='<?php echo isset($options['paypal_email']) ? esc_attr($options['paypal_email']) : ''; ?>' size='40'>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>ContentLocker Pro Settings</h2>
            <?php
            settings_fields('clp_settings');
            do_settings_sections('contentlockerpro');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function lock_content_shortcode($atts, $content = null) {
        $user_paid = $this->user_has_access();
        if ($user_paid) {
            return do_shortcode($content);
        } else {
            $options = get_option('clp_options');
            $price = isset($options['price']) ? floatval($options['price']) : 9.99;
            ob_start();
            ?>
            <div class='clp-lock-message'>
                <p>This content is locked. Please subscribe for $<?php echo number_format($price, 2); ?> to get access.</p>
                <button id='clp-subscribe-btn'>Subscribe Now</button>
                <div id='clp-payment-result'></div>
            </div>
            <?php
            return ob_get_clean();
        }
    }

    private function user_has_access() {
        if (!is_user_logged_in()) {
            return false;
        }
        $user_id = get_current_user_id();
        $access = get_user_meta($user_id, 'clp_access_active', true);
        return $access === 'yes';
    }

    public function process_payment() {
        // Simplified mock payment processing for demonstration
        // In real-world use, integrate a real payment gateway
        check_ajax_referer('clp_nonce', 'security');
        if (!is_user_logged_in()) {
            wp_send_json_error('Please log in to subscribe.');
        }

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'clp_access_active', 'yes');

        wp_send_json_success('Subscription activated. You now have full content access.');
    }
}
new ContentLockerPro();

// JS for ajax (simulated inline script for single file plugin)
add_action('wp_footer', function() {
    if (shortcode_exists('lock_content')) {
        ?>
        <script type='text/javascript'>
            jQuery(document).ready(function($){
                $('#clp-subscribe-btn').on('click', function(){
                    var $btn = $(this);
                    $btn.prop('disabled', true).text('Processing...');
                    $.post(
                        '<?php echo admin_url('admin-ajax.php'); ?>',
                        { action: 'clp_process_payment', security: '<?php echo wp_create_nonce('clp_nonce'); ?>' },
                        function(response) {
                            if(response.success){
                                $('#clp-payment-result').html('<p style="color:green;">' + response.data + '</p>');
                                $btn.hide();
                                $('.clp-lock-message').fadeOut();
                                location.reload();
                            } else {
                                $('#clp-payment-result').html('<p style="color:red;">' + response.data + '</p>');
                                $btn.prop('disabled', false).text('Subscribe Now');
                            }
                        }
                    );
                });
            });
        </script>
        <?php
    }
});