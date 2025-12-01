/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_SmartPaywall.php
*/
<?php
/**
 * Plugin Name: WP SmartPaywall
 * Plugin URI: https://example.com/wp-smartpaywall
 * Description: A dynamic paywall plugin that intelligently unlocks premium content based on user engagement, subscription status, or micro-payments.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSmartPaywall {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_unlock_content', array($this, 'ajax_unlock_content'));
        add_action('wp_ajax_nopriv_unlock_content', array($this, 'ajax_unlock_content'));
    }

    public function init() {
        add_shortcode('smartpaywall', array($this, 'shortcode'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-smartpaywall', plugin_dir_url(__FILE__) . 'js/smartpaywall.js', array('jquery'), '1.0.0', true);
        wp_localize_script('wp-smartpaywall', 'smartpaywall_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('WP SmartPaywall', 'SmartPaywall', 'manage_options', 'wp-smartpaywall', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_POST['save_settings'])) {
            update_option('smartpaywall_unlock_method', sanitize_text_field($_POST['unlock_method']));
            update_option('smartpaywall_price', floatval($_POST['price']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $unlock_method = get_option('smartpaywall_unlock_method', 'subscription');
        $price = get_option('smartpaywall_price', 2.99);
        ?>
        <div class="wrap">
            <h1>WP SmartPaywall Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="unlock_method">Unlock Method</label></th>
                        <td>
                            <select name="unlock_method" id="unlock_method">
                                <option value="subscription" <?php selected($unlock_method, 'subscription'); ?>>Subscription</option>
                                <option value="micro" <?php selected($unlock_method, 'micro'); ?>>Micro-payment</option>
                                <option value="engagement" <?php selected($unlock_method, 'engagement'); ?>>Engagement (e.g., social share)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="price">Price (for micro-payments)</label></th>
                        <td><input type="number" step="0.01" name="price" id="price" value="<?php echo esc_attr($price); ?>" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="save_settings" class="button-primary" value="Save Settings" />
                </p>
            </form>
        </div>
        <?php
    }

    public function shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'method' => get_option('smartpaywall_unlock_method', 'subscription'),
            'price' => get_option('smartpaywall_price', 2.99)
        ), $atts);

        if (is_user_logged_in() && user_can(get_current_user_id(), 'read')) {
            return $content;
        }

        if ($atts['method'] === 'engagement') {
            return '<div class="smartpaywall-engagement">
                        <p>This content is locked. Share this post on social media to unlock.</p>
                        <button onclick="smartpaywall_share()">Share to Unlock</button>
                        <div id="smartpaywall-message"></div>
                    </div>';
        } elseif ($atts['method'] === 'micro') {
            return '<div class="smartpaywall-micro">
                        <p>This content costs $' . esc_html($atts['price']) . ' to unlock.</p>
                        <button onclick="smartpaywall_unlock(' . esc_js($atts['price']) . ')">Pay to Unlock</button>
                        <div id="smartpaywall-message"></div>
                    </div>';
        } else {
            return '<div class="smartpaywall-subscription">
                        <p>Subscribe to unlock this content.</p>
                        <a href="/subscribe">Subscribe Now</a>
                    </div>';
        }
    }

    public function ajax_unlock_content() {
        if (!wp_verify_nonce($_POST['nonce'], 'unlock_content')) {
            wp_die('Security check failed');
        }

        $price = floatval($_POST['price']);
        // Simulate payment processing
        if ($price > 0) {
            // In a real plugin, integrate with a payment gateway
            update_user_meta(get_current_user_id(), 'smartpaywall_paid', true);
            wp_send_json_success(array('message' => 'Content unlocked!'));
        } else {
            wp_send_json_error(array('message' => 'Payment failed'));
        }
    }
}

new WPSmartPaywall;

// JavaScript file: js/smartpaywall.js
// (This would be a separate file, but for this example, it's included as a comment)
/*
function smartpaywall_unlock(price) {
    jQuery.post(smartpaywall_ajax.ajax_url, {
        action: 'unlock_content',
        price: price,
        nonce: '<?php echo wp_create_nonce('unlock_content'); ?>'
    }, function(response) {
        if (response.success) {
            jQuery('#smartpaywall-message').html('<p>' + response.data.message + '</p>');
            location.reload();
        } else {
            jQuery('#smartpaywall-message').html('<p>' + response.data.message + '</p>');
        }
    });
}

function smartpaywall_share() {
    // Simulate sharing and unlocking
    jQuery('#smartpaywall-message').html('<p>Content unlocked via engagement!</p>');
    location.reload();
}
*/
?>