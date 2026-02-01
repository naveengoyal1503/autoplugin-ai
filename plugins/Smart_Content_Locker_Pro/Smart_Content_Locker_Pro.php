/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Content Locker Pro
 * Plugin URI: https://example.com/smart-content-locker
 * Description: Lock content behind email signup, shares, or payments. Freemium: Upgrade for advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-content-locker
 */

if (!defined('ABSPATH')) exit;

class SmartContentLocker {
    const VERSION = '1.0.0';
    const PREMIUM_URL = 'https://example.com/premium-upgrade';

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_shortcode('content_locker', [$this, 'content_locker_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_scl_unlock', [$this, 'ajax_unlock']);
        add_action('wp_ajax_nopriv_scl_unlock', [$this, 'ajax_unlock']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', [$this, 'admin_menu']);
            add_action('admin_notices', [$this, 'premium_notice']);
        }
        load_plugin_textdomain('smart-content-locker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        if (has_shortcode(get_post()->post_content, 'content_locker')) {
            wp_enqueue_script('jquery');
            wp_add_inline_script('jquery', "
                jQuery(document).ready(function($) {
                    $('.scl-lock').on('click', '.scl-unlock-btn', function(e) {
                        e.preventDefault();
                        var type = $(this).data('type');
                        var locker = $(this).closest('.scl-locker');
                        if (type === 'email') {
                            var email = locker.find('.scl-email').val();
                            if (!email) return alert('Please enter email');
                            $.post('" . admin_url('admin-ajax.php') . "', {action: 'scl_unlock', type: 'email', email: email, nonce: '" . wp_create_nonce('scl_nonce') . "'}, function(res) {
                                if (res.success) locker.addClass('scl-unlocked');
                            });
                        }
                    });
                });
            ");
        }
    }

    public function content_locker_shortcode($atts, $content = null) {
        $atts = shortcode_atts([
            'type' => 'email',
            'title' => 'Unlock Premium Content',
            'message' => 'Enter your email to unlock!',
        ], $atts);

        if (!is_user_logged_in() && $atts['type'] === 'email') {
            ob_start();
            ?>
            <div class="scl-locker" style="border: 2px solid #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9;">
                <h3><?php echo esc_html($atts['title']); ?></h3>
                <p><?php echo esc_html($atts['message']); ?></p>
                <input type="email" class="scl-email" placeholder="your@email.com" style="width: 100%; padding: 10px; margin: 10px 0;">
                <button class="scl-unlock-btn button" data-type="email" style="background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer;">Unlock Now</button>
                <div class="scl-content" style="display: none;"><?php echo do_shortcode($content); ?></div>
                <?php if ($atts['type'] !== 'premium') : ?>
                <p style="font-size: 12px; margin-top: 10px;"><em>Premium users get social share & payment unlocks. <a href="<?php echo esc_url(self::PREMIUM_URL); ?>" target="_blank">Upgrade Now</a></em></p>
                <?php endif; ?>
            </div>
            <script>jQuery('.scl-locker .scl-unlock-btn').trigger('click'); /* Demo unlock on click */</script>
            <?php
            return ob_get_clean();
        }
        return do_shortcode($content);
    }

    public function ajax_unlock() {
        check_ajax_referer('scl_nonce', 'nonce');
        // Simulate unlock (store in transient for demo)
        set_transense('scl_unlock_' . sanitize_email($_POST['email']), true, 3600);
        wp_send_json_success();
    }

    public function admin_menu() {
        add_options_page('Smart Content Locker', 'Content Locker', 'manage_options', 'scl-settings', [$this, 'settings_page']);
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Content Locker Settings</h1>
            <p>Free version active. <a href="<?php echo esc_url(self::PREMIUM_URL); ?>" target="_blank"><strong>Upgrade to Pro</strong></a> for:</p>
            <ul>
                <li>Social share unlocks</li>
                <li>Payment integration (Stripe/PayPal)</li>
                <li>Analytics dashboard</li>
                <li>Unlimited lockers & A/B testing</li>
            </ul>
        </div>
        <?php
    }

    public function premium_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Unlock advanced features with <a href="' . esc_url(self::PREMIUM_URL) . '" target="_blank">Smart Content Locker Pro</a>! Social shares, payments, analytics & more.</p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new SmartContentLocker();

// Premium teaser function (hooks into free version)
function scl_premium_teaser() {
    if (!is_super_admin()) return;
    echo '<div style="position: fixed; bottom: 20px; right: 20px; background: #0073aa; color: white; padding: 10px; border-radius: 5px; z-index: 9999;"><strong>Go Pro!</strong> | <a href="https://example.com/premium-upgrade" style="color: #fff;">Upgrade</a></div>';
}
add_action('wp_footer', 'scl_premium_teaser');