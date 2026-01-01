/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Affiliate Coupons Pro
 * Plugin URI: https://example.com/affiliate-coupons-pro
 * Description: Generate personalized affiliate coupons with tracking and analytics to maximize commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class CustomAffiliateCouponsPro {
    private static $instance = null;
    public $coupons = [];

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('cac_coupon', [$this, 'coupon_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        $this->coupons = get_option('cac_coupons', []);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('cac-script', plugin_dir_url(__FILE__) . 'cac.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('cac-style', plugin_dir_url(__FILE__) . 'cac.css', [], '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'cac-pro', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (isset($_POST['cac_save'])) {
            update_option('cac_coupons', $_POST['coupons']);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Custom Affiliate Coupons Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Name</th>
                        <td><input type="text" name="coupons[name]" value="<?php echo esc_attr($this->coupons['name'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="coupons[link]" value="<?php echo esc_attr($this->coupons['link'] ?? ''); ?>" size="50" /></td>
                    </tr>
                    <tr>
                        <th>Code</th>
                        <td><input type="text" name="coupons[code]" value="<?php echo esc_attr($this->coupons['code'] ?? 'SAVE10'); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Expiry (days)</th>
                        <td><input type="number" name="coupons[expiry]" value="<?php echo esc_attr($this->coupons['expiry'] ?? 30); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="cac_save" class="button-primary" value="Save Coupon" /></p>
            </form>
            <h2>Shortcode</h2>
            <p>Use <code>[cac_coupon id="0"]</code> to display.</p>
            <?php if (!function_exists('cac_pro_features')) { ?>
            <div class="notice notice-info"><p>Upgrade to Pro for unlimited coupons and analytics!</p></div>
            <?php } ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['id' => 0], $atts);
        $coupon = $this->coupons[$atts['id']] ?? null;
        if (!$coupon) return 'Coupon not found.';

        $expired = isset($coupon['created']) && (time() - $coupon['created'] > $coupon['expiry'] * 86400);
        if ($expired) return '<div class="cac-expired">Coupon expired!</div>';

        $code = $coupon['code'];
        $link = $coupon['link'] . (strpos($coupon['link'], '?') ? '&' : '?') . 'ref=' . uniqid();

        ob_start();
        ?>
        <div class="cac-coupon">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p>Use code: <strong><?php echo esc_html($code); ?></strong></p>
            <a href="<?php echo esc_url($link); ?>" class="cac-button" target="_blank">Get Deal</a>
            <small>Expires in <?php echo $coupon['expiry']; ?> days</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        $default = ['0' => ['name' => 'Sample Deal', 'link' => '#', 'code' => 'WELCOME20', 'expiry' => 30, 'created' => time()]];
        update_option('cac_coupons', $default);
    }
}

CustomAffiliateCouponsPro::get_instance();

// Pro check stub
function cac_pro_features() { return false; } // Replace with license check in pro

// Inline styles and scripts for single file
add_action('wp_head', function() {
    echo '<style>.cac-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }.cac-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }.cac-expired { color: red; }</style>';
});

add_action('wp_footer', function() {
    echo '<script>jQuery(".cac-button").click(function(){ console.log("Coupon clicked!"); });</script>';
});