/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Generate, manage, and track exclusive affiliate coupons to boost your WordPress site monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        // Load frontend assets
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Coupons',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON format)</th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Usage:</strong> Use shortcode <code>[affiliate_coupon id="1"]</code> or <code>[affiliate_coupon]</code> for random.</p>
            <p><strong>Pro Upgrade:</strong> Unlock analytics, unlimited coupons, and click tracking for $49/year.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons_json = get_option('acv_coupons', '[]');
        $coupons = json_decode($coupons_json, true);
        if (empty($coupons)) {
            return '<p>No coupons configured. Go to <a href="' . admin_url('admin.php?page=affiliate-coupon-vault') . '">settings</a>.</p>';
        }
        if ($atts['id']) {
            $coupon = isset($coupons[$atts['id'] - 1]) ? $coupons[$atts['id'] - 1] : $coupons;
        } else {
            $coupon = $coupons[array_rand($coupons)];
        }
        $aff_link = esc_url($coupon['link']);
        $code = esc_html($coupon['code']);
        $desc = esc_html($coupon['desc']);
        $click_id = uniqid();
        return "<div class=\"acv-coupon\"><h3>Exclusive Deal: $desc</h3><p><strong>Code:</strong> $code</p><a href=\"$aff_link\" class=\"acv-button\" data-click=\"$click_id\" target=\"_blank\">Get Deal Now (Affiliate Link)</a></div>";
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', json_encode(array(
                array('code' => 'SAVE20', 'desc' => '20% off on hosting', 'link' => 'https://example.com/aff'),
                array('code' => 'WP10', 'desc' => '10% off WordPress tools', 'link' => 'https://example.com/aff2')
            )));
        }
    }
}

AffiliateCouponVault::get_instance();

// Create assets directories if needed
add_action('init', function() {
    $assets = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets)) {
        mkdir($assets, 0755, true);
    }
    if (!file_exists($assets . 'style.css')) {
        file_put_contents($assets . 'style.css', '.acv-coupon { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; border-radius: 5px; background: #f9f9f9; } .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; } .acv-button:hover { background: #005a87; }');
    }
    if (!file_exists($assets . 'script.js')) {
        file_put_contents($assets . 'script.js', 'jQuery(document).ready(function($) { $(".acv-button").on("click", function() { console.log("Coupon clicked: " + $(this).data("click")); /* Pro: Send to analytics */ }); });');
    }
});