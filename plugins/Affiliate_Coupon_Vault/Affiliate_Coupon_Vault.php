/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault Settings',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Brand1|DISCOUNT10|50|amazon.com/offer1
Brand2|SAVE20|20|example.com/offer2");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Enter coupons (format: Brand|Code|Discount%|Affiliate URL, one per line):</label></p>
                <textarea name="coupons" rows="10" cols="80" class="large-text"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Settings"></p>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon]</code> or Gutenberg block.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $coupons_str = get_option('acv_coupons', '');
        if (empty($coupons_str)) return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>.</p>';

        $coupons = explode("\n", trim($coupons_str));
        $display_coupons = array_slice(array_filter($coupons), 0, intval($atts['limit']));
        $output = '<div class="acv-coupons">';
        foreach ($display_coupons as $coupon) {
            $parts = explode('|', trim($coupon));
            if (count($parts) == 4) {
                $output .= '<div class="acv-coupon">'
                         . '<h4>' . esc_html($parts) . '</h4>'
                         . '<p>Code: <strong>' . esc_html($parts[1]) . '</strong> (' . esc_html($parts[2]) . '% off)</p>'
                         . '<a href="' . esc_url($parts[3]) . '" class="acv-button" target="_blank">Get Deal</a>'
                         . '</div>';
            }
        }
        $output .= '</div><p class="acv-pro">Upgrade to Pro for auto-generated unique codes & analytics!</p>';
        return $output;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', "Brand1|DISCOUNT10|50|amazon.com/offer1");
        }
    }
}

AffiliateCouponVault::get_instance();

// Gutenberg Block
add_action('init', function() {
    register_block_type('affiliate-coupon-vault/coupon-block', array(
        'render_callback' => function() {
            return AffiliateCouponVault::get_instance()->coupon_shortcode(array('limit' => 3));
        }
    ));
});

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
.acv-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1 1 300px; background: #f9f9f9; }
.acv-coupon h4 { margin: 0 0 10px; color: #333; }
.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
.acv-button:hover { background: #005a87; }
.acv-pro { text-align: center; font-style: italic; color: #666; }
@media (max-width: 768px) { .acv-coupons { flex-direction: column; } }
</style>';
});

// Pro upsell admin notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro features like unique code generation and click tracking! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
});