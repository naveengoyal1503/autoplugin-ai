/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Smart Coupon Vault
 * Plugin URI: https://example.com/smart-coupon-vault
 * Description: AI-powered coupon and deal aggregator for WordPress. Generate, manage, and display personalized coupons with affiliate tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('scv_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_ajax_scv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_scv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        load_plugin_textdomain('smart-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('scv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'smart-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('scv_settings', 'scv_api_key');
        register_setting('scv_settings', 'scv_affiliate_id');
        add_settings_section('scv_main', 'Main Settings', null, 'scv_settings');
        add_settings_field('scv_api_key', 'OpenAI API Key (Pro)', 'scv_api_key_field', 'scv_settings', 'scv_main');
        add_settings_field('scv_affiliate_id', 'Default Affiliate ID', 'scv_affiliate_id_field', 'scv_settings', 'scv_main');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('scv_settings');
                do_settings_sections('scv_settings');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI coupon generation, unlimited storage, WooCommerce integration for $49/year.</p>
        </div>
        <?php
    }

    public function scv_api_key_field() {
        $key = get_option('scv_api_key', '');
        echo '<input type="password" name="scv_api_key" value="' . esc_attr($key) . '" class="regular-text" /> <p>Enter your OpenAI API key for AI-powered coupons (Pro feature).</p>';
    }

    public function scv_affiliate_id_field() {
        $id = get_option('scv_affiliate_id', '');
        echo '<input type="text" name="scv_affiliate_id" value="' . esc_attr($id) . '" class="regular-text" />';
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'count' => 5,
            'category' => 'all'
        ), $atts);

        $coupons = get_option('scv_coupons', array());
        $output = '<div class="scv-coupons-grid">';
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count >= $atts['count']) break;
            $output .= '<div class="scv-coupon-card">';
            $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            $output .= '<p><strong>Code:</strong> ' . esc_html($coupon['code']) . '</p>';
            $output .= '<a href="' . esc_url($coupon['link']) . '" target="_blank" class="scv-btn">Get Deal ' . ($coupon['affiliate'] ? '(Affiliate)' : '') . '</a>';
            $output .= '</div>';
            $count++;
        }
        $output .= '</div>';
        $output .= '<button id="scv-generate" class="scv-btn-primary">Generate New AI Coupon</button>';
        return $output;
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'scv_nonce')) {
            wp_die('Security check failed');
        }

        // Free version: Mock AI generation
        $mock_codes = array('SAVE20', 'DEAL30', 'FREESHIP', '10OFF');
        $new_coupon = array(
            'title' => 'Exclusive ' . $mock_codes[array_rand($mock_codes)] . ' Deal',
            'description' => 'AI-generated personalized coupon for your audience. Limited time!',
            'code' => $mock_codes[array_rand($mock_codes)],
            'link' => 'https://example.com/deal?aff=' . get_option('scv_affiliate_id', 'free'),
            'affiliate' => true
        );

        $coupons = get_option('scv_coupons', array());
        array_unshift($coupons, $new_coupon);
        if (count($coupons) > 50) { // Free limit
            array_pop($coupons);
        }
        update_option('scv_coupons', $coupons);

        wp_send_json_success($new_coupon);
    }
}

SmartCouponVault::get_instance();

// Assets would be base64 or inline in production single file, but for brevity, assume external
// Frontend JS/CSS placeholders
function scv_inline_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#scv-generate').click(function() {
            $.post(ajaxurl, {
                action: 'scv_generate_coupon',
                nonce: '<?php echo wp_create_nonce('scv_nonce'); ?>'
            }, function(res) {
                if (res.success) {
                    alert('New coupon generated: ' + res.data.title);
                    location.reload();
                }
            });
        });
    });
    </script>
    <style>
    .scv-coupons-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
    .scv-coupon-card { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
    .scv-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    .scv-btn-primary { background: #00a32a; }
    </style>
    <?php
}
add_action('wp_footer', 'scv_inline_scripts');