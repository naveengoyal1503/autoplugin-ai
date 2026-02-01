/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Paywall_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Paywall Pro
 * Plugin URI: https://example.com/smart-paywall-pro
 * Description: Automatically add paywalls to high-traffic posts for easy monetization. Freemium model with pro upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-paywall-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartPaywallPro {
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
        add_filter('the_content', array($this, 'add_paywall'));
        add_shortcode('smart_paywall', array($this, 'paywall_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-paywall-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        add_settings_field('spp_api_key', 'Stripe API Key (Pro)', array($this, 'api_key_field'), 'reading', 'default');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smart-paywall-pro', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('smart-paywall-pro', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('smart-paywall-pro', 'spp_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('spp_nonce')));
    }

    public function add_paywall($content) {
        if (!is_single() || is_admin()) return $content;

        $post_id = get_the_ID();
        $views = get_post_meta($post_id, 'spp_views', true) ?: 0;
        $paywall_enabled = get_post_meta($post_id, 'spp_paywall', true);

        // Auto-enable for high-traffic posts (free feature)
        if ($views > 1000 && empty($paywall_enabled)) {
            update_post_meta($post_id, 'spp_paywall', '1');
            $paywall_enabled = '1';
        }

        if ($paywall_enabled && !is_user_logged_in()) {
            $preview_length = 500; // Free preview words
            $words = explode(' ', $content);
            if (count($words) > $preview_length) {
                $preview = implode(' ', array_slice($words, 0, $preview_length));
                $content = $preview . '...<div id="spp-paywall" class="spp-overlay">'
                    . '<h3>Unlock Full Article</h3>'
                    . '<p>Subscribe for $4.99/month or pay $9.99 once.</p>'
                    . $this->paywall_shortcode(array('post_id' => $post_id)) . '</div>';
            }
        }

        // Track views
        if (!wp_doing_ajax()) {
            $views++;
            update_post_meta($post_id, 'spp_views', $views);
        }

        return $content;
    }

    public function paywall_shortcode($atts) {
        $atts = shortcode_atts(array('post_id' => 0), $atts);
        $post_id = $atts['post_id'] ?: get_the_ID();
        ob_start();
        ?>
        <div class="spp-buttons">
            <button id="spp-subscribe" data-post="<?php echo $post_id; ?>" data-type="monthly">Subscribe $4.99/mo</button>
            <button id="spp-onetime" data-post="<?php echo $post_id; ?>" data-type="onetime">One-time $9.99</button>
            <p id="spp-message"></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function api_key_field() {
        $key = get_option('spp_api_key');
        echo '<input type="text" name="spp_api_key" value="' . esc_attr($key) . '" placeholder="Pro feature: Enter Stripe key" />';
        echo '<p class="description">Upgrade to Pro for real payments.</p>';
    }

    public function activate() {
        if (!get_option('spp_installed')) {
            add_option('spp_installed', time());
        }
    }
}

SmartPaywallPro::get_instance();

// AJAX handlers for payments (Pro demo)
add_action('wp_ajax_spp_payment', 'spp_handle_payment');
add_action('wp_ajax_nopriv_spp_payment', 'spp_handle_payment');

function spp_handle_payment() {
    check_ajax_referer('spp_nonce', 'nonce');
    $type = sanitize_text_field($_POST['type']);
    // Pro: Integrate Stripe/PayPal
    wp_send_json_success('Thanks for subscribing to ' . $type . '! (Pro feature)');
}

// CSS
/*
.spp-overlay { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 2rem; border: 1px solid #ccc; box-shadow: 0 0 10px rgba(0,0,0,0.5); z-index: 9999; }
.spp-buttons button { margin: 0 0.5rem; padding: 1rem; background: #0073aa; color: white; border: none; cursor: pointer; }
.spp-buttons button:hover { background: #005a87; }
*/
// Save as style.css in plugin folder

// JS
/*
$(document).ready(function() {
    $('#spp-subscribe, #spp-onetime').click(function() {
        var btn = $(this);
        $.post(spp_ajax.ajax_url, {
            action: 'spp_payment',
            type: btn.data('type'),
            post: btn.data('post'),
            nonce: spp_ajax.nonce
        }, function(res) {
            $('#spp-message').text(res.data);
            $('.spp-overlay').fadeOut();
        });
    });
});
*/
// Save as script.js in plugin folder
?>