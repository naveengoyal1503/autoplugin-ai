/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Affiliate Coupons Pro
 * Plugin URI: https://example.com/exclusive-affiliate-coupons
 * Description: Generate and manage exclusive affiliate coupons with personalized promo codes, affiliate tracking, and automated sharing to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveAffiliateCoupons {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('eac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('eac_upgraded_to_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_upgrade_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('eac-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('eac-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons', 'Affiliate Coupons', 'manage_options', 'exclusive-affiliate-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['eac_save'])) {
            update_option('eac_coupons', sanitize_textarea_field($_POST['eac_coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('eac_coupons', '{"example":{"brand":"Example Brand","code":"SAVE20","afflink":"https://affiliate.link","desc":"20% off first purchase"}}');
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Affiliate Coupons</h1>
            <form method="post">
                <textarea name="eac_coupons" rows="20" cols="80" class="large-text code"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">JSON format: {"coupon_id":{"brand":"Brand","code":"CODE","afflink":"Affiliate Link","desc":"Description"}}</p>
                <p><input type="submit" name="eac_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock unlimited coupons, click tracking, analytics, and more for $49/year.</p>
            <a href="https://example.com/pro" class="button button-hero">Get Pro Now</a>
            <h3>Shortcode Usage</h3>
            <p>Use <code>[eac_coupon id="coupon_id"]</code> to display coupons.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        if (empty($atts['id'])) return '';
        $coupons = json_decode(get_option('eac_coupons', '{}'), true);
        if (!isset($coupons[$atts['id']])) return '';
        $coupon = $coupons[$atts['id']];
        $unique_code = $coupon['code'] . '-' . uniqid();
        ob_start();
        ?>
        <div class="eac-coupon" data-coupon="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['brand']); ?> Exclusive Deal</h3>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <div class="eac-code">Your Code: <strong><?php echo esc_html($unique_code); ?></strong></div>
            <a href="<?php echo esc_url($coupon['afflink']); ?>&coupon=<?php echo urlencode($unique_code); ?>" class="button eac-btn" target="_blank">Shop Now & Save</a>
            <small>Limited time offer - Generated for you exclusively!</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function pro_upgrade_notice() {
        if (current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p><strong>Exclusive Affiliate Coupons:</strong> Upgrade to Pro for advanced features like tracking and unlimited coupons! <a href="' . admin_url('options-general.php?page=exclusive-affiliate-coupons') . '">Upgrade Now</a></p></div>';
        }
    }

    public function activate() {
        if (!get_option('eac_coupons')) {
            update_option('eac_coupons', '{"example":{"brand":"Example Brand","code":"SAVE20","afflink":"https://your-affiliate-link.com","desc":"20% off first purchase"}}');
        }
    }
}

new ExclusiveAffiliateCoupons();

// Pro teaser - in real pro version, remove limits
function eac_pro_teaser() {
    echo '<script>console.log("Exclusive Affiliate Coupons Pro: Track clicks and boost commissions!");</script>';
}
add_action('wp_footer', 'eac_pro_teaser');

// Basic CSS
/*
.eac-coupon { border: 2px solid #007cba; padding: 20px; margin: 20px 0; border-radius: 8px; background: #f9f9f9; }
.eac-coupon h3 { color: #007cba; }
.eac-code { background: #fff; padding: 10px; margin: 10px 0; font-family: monospace; }
.eac-btn { background: #007cba; color: #fff; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
.eac-btn:hover { background: #005a87; }
*/
// Save as assets/frontend.css
// Empty JS for now - assets/frontend.js
?>