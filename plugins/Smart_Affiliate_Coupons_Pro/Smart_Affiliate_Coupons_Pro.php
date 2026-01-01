/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: AI-powered coupon and affiliate link manager to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sac_pro_version') !== 'activated') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function activate() {
        add_option('sac_coupons', array());
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'SAC Pro', 'manage_options', 'sac-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sac_submit'])) {
            update_option('sac_coupons', sanitize_textarea_field($_POST['sac_coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('sac_coupons', '');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons Pro</h1>
            <form method="post">
                <p><label>Coupons (JSON format: {"code":"AFF10","afflink":"https://example.com","desc":"10% off"}):</label></p>
                <textarea name="sac_coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea>
                <p><input type="submit" name="sac_submit" class="button-primary" value="Save"></p>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI generation for $49/year. <a href="#">Buy Now</a></p>
            <p>Use shortcode: <code>[sac_coupon id="code"]</code></p>
        </div>
        <?php
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Coupons Pro:</strong> Upgrade to Pro for AI-powered coupons and tracking!</p></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sac-style', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = json_decode(get_option('sac_coupons', '[]'), true);
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $track_id = uniqid('sac_');
        $tracked_link = add_query_arg('sac', $track_id, $coupon['afflink']);

        // Track click
        if (isset($_GET['sac'])) {
            $this->track_click($_GET['sac']);
        }

        ob_start();
        ?>
        <div class="sac-coupon">
            <h3><?php echo esc_html($coupon['desc']); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($coupon['code']); ?></code></p>
            <a href="<?php echo esc_url($tracked_link); ?>" class="button sac-btn" target="_blank">Get Deal & Track</a>
            <small>Tracked for commissions | Pro: AI-Generated</small>
        </div>
        <style>
        .sac-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
        .sac-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .sac-btn:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    private function track_click($track_id) {
        $clicks = get_option('sac_clicks', array());
        $clicks[$track_id] = current_time('mysql');
        update_option('sac_clicks', $clicks);
    }
}

new SmartAffiliateCouponsPro();

// Pro stub - replace with real AI integration
if (!function_exists('sac_ai_generate')) {
    function sac_ai_generate($niche) {
        return json_encode(array('AI10' => array('code' => 'AI' . rand(10,50), 'afflink' => 'https://example.com', 'desc' => 'AI Exclusive Deal')));
    }
}
?>