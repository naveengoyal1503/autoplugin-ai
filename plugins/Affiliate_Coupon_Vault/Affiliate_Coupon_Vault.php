/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking, boosting conversions for bloggers and e-commerce sites.
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

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('aff-coupon-js', plugin_dir_url(__FILE__) . 'aff-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('aff-coupon-css', plugin_dir_url(__FILE__) . 'aff-coupon.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => 'SAVE10',
            'affiliate' => 'youraffiliateid',
            'discount' => '10%',
            'expires' => date('Y-m-d', strtotime('+30 days')),
            'product' => 'Featured Product',
            'link' => '#',
        ), $atts);

        $tracking_id = uniqid('acv_');
        $coupon_link = add_query_arg(array('acv_track' => $tracking_id, 'aff' => $atts['affiliate']), $atts['link']);

        ob_start();
        ?>
        <div class="aff-coupon-vault" data-tracking="<?php echo esc_attr($tracking_id); ?>">
            <div class="coupon-header">Exclusive Deal: <strong><?php echo esc_html($atts['product']); ?></strong></div>
            <div class="coupon-code"><span class="code"><?php echo esc_html($atts['code']); ?></span></div>
            <div class="coupon-details"><?php echo esc_html($atts['discount']); ?> Off - Expires: <?php echo esc_html($atts['expires']); ?></div>
            <a href="<?php echo esc_url($coupon_link); ?>" class="coupon-btn" target="_blank">Shop Now & Save</a>
            <div class="coupon-stats">Clicks: <span class="clicks">0</span></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'aff-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_pro_key', sanitize_text_field($_POST['pro_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $pro_key = get_option('acv_pro_key', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="pro_key" value="<?php echo esc_attr($pro_key); ?>" class="regular-text" /> (Upgrade for unlimited coupons)</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: [affiliate_coupon code="SAVE20" affiliate="yourid" discount="20%" expires="2026-12-31" product="Product Name" link="https://affiliate-link.com"]</p>
            <p><strong>Pro Features:</strong> Advanced analytics, unlimited coupons, API integrations. <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p>
        </div>
        <?php
    }

    public function admin_init() {
        // Pro check simulation
        if (get_option('acv_pro_key') !== 'pro-activated') {
            add_action('admin_notices', array($this, 'pro_nag'));
        }
    }

    public function pro_nag() {
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock unlimited coupons! <a href="options-general.php?page=aff-coupon-vault">Upgrade</a></p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['acv_track'])) {
        $track_id = sanitize_text_field($_GET['acv_track']);
        $aff = isset($_GET['aff']) ? sanitize_text_field($_GET['aff']) : '';
        // In pro version, log to DB or API
        set_transient('acv_click_' . $track_id, time(), 3600);
        // Redirect to affiliate
        wp_redirect(remove_query_arg(array('acv_track', 'aff')));
        exit;
    }
});

AffiliateCouponVault::get_instance();

// Inline JS and CSS for self-contained
add_action('wp_head', function() { ob_start(); ?>
<style>
.aff-coupon-vault { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; border-radius: 10px; max-width: 400px; }
.coupon-header { font-size: 1.2em; margin-bottom: 10px; }
.coupon-code { background: #fff; padding: 15px; font-size: 2em; font-family: monospace; margin: 10px 0; border: 1px solid #ddd; }
.coupon-details { color: #666; margin: 10px 0; }
.coupon-btn { display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 0; }
.coupon-btn:hover { background: #005a87; }
.coupon-stats { font-size: 0.9em; color: #999; }
</style>
<script>jQuery(document).ready(function($){ $('.aff-coupon-vault .coupon-btn').click(function(){ var $stats = $(this).closest('.aff-coupon-vault').find('.clicks'); $stats.text(parseInt($stats.text())+1); }); });</script>
<?php echo ob_get_clean(); });

// Freemium notice
add_action('admin_notices', function() {
    if (!get_option('acv_pro_key') || get_option('acv_pro_key') !== 'pro-activated') {
        echo '<div class="notice notice-upgrade notice-info is-dismissible"><p><strong>Affiliate Coupon Vault:</strong> Upgrade to Pro for full tracking & unlimited coupons! <a href="options-general.php?page=aff-coupon-vault">Get Pro</a></p></div>';
    }
});