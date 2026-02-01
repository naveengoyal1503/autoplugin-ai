/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Paywall_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Paywall Pro
 * Plugin URI: https://example.com/smart-paywall-pro
 * Description: Automatically places intelligent paywalls behind premium content, offering freemium teasers and one-click subscriptions to boost conversions and recurring revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-paywall-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartPaywallPro {
    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'apply_paywall'));
        add_shortcode('paywall_teaser', array($this, 'paywall_teaser_shortcode'));
        add_action('wp_ajax_spp_subscribe', array($this, 'handle_subscription'));
        add_action('wp_ajax_nopriv_spp_subscribe', array($this, 'handle_subscription'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('spp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('spp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('spp-script', 'spp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('spp_nonce')));
    }

    public function apply_paywall($content) {
        if (!is_single() || get_post_meta(get_the_ID(), '_spp_paywall', true) !== 'yes') {
            return $content;
        }

        $word_limit = 100;
        $words = explode(' ', $content);
        if (count($words) > $word_limit) {
            $teaser = implode(' ', array_slice($words, 0, $word_limit));
            $hidden = implode(' ', array_slice($words, $word_limit));
            $content = '<div class="spp-teaser">' . $teaser . '...</div>';
            $content .= '<div class="spp-paywall" style="display:none;">' . $hidden . '</div>';
            $content .= $this->paywall_teaser_shortcode(array('price' => '9.99', 'period' => 'month'));
        }
        return $content;
    }

    public function paywall_teaser_shortcode($atts) {
        $atts = shortcode_atts(array('price' => '9.99', 'period' => 'month'), $atts);
        ob_start();
        ?>
        <div id="spp-paywall-box" class="spp-modal">
            <div class="spp-overlay"></div>
            <div class="spp-content">
                <h3>Unlock Full Access</h3>
                <p>Subscribe for just $<span class="spp-price"><?php echo $atts['price']; ?></span>/<?php echo $atts['period']; ?> and read the full article.</p>
                <form id="spp-subscribe-form">
                    <input type="email" id="spp-email" placeholder="Enter your email" required>
                    <button type="submit">Subscribe Now</button>
                </form>
                <p><small>Powered by Stripe. Cancel anytime.</small></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_subscription() {
        check_ajax_referer('spp_nonce', 'nonce');
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) {
            wp_die('Invalid email');
        }

        // In pro version, integrate with Stripe/WooCommerce
        // For demo: Log subscription
        update_option('spp_subscribers', get_option('spp_subscribers', array()) + array($email));

        wp_send_json_success('Subscription successful!');
    }
}

new SmartPaywallPro();

// Pro upgrade notice
function spp_pro_notice() {
    if (!get_option('spp_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Smart Paywall Pro:</strong> Upgrade to Pro for unlimited paywalls, A/B testing & analytics! <a href="https://example.com/upgrade">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'spp_pro_notice');

// Prevent direct access
if (!defined('SPP_VERSION')) {
    define('SPP_VERSION', '1.0.0');
}
?>