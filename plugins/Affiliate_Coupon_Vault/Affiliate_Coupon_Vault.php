/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from top networks to boost your commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_settings'])) {
            update_option('acv_api_key', sanitize_text_field($_POST['api_key']));
            update_option('acv_coupon_networks', sanitize_text_field($_POST['networks']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('acv_api_key', '');
        $networks = get_option('acv_coupon_networks', 'amazon,clickbank') ?>
        ?>
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1><form method="post"><table class="form-table">
        <tr><th>API Key</th><td><input type="text" name="api_key" value="' . esc_attr($api_key) . '" class="regular-text"></td></tr>
        <tr><th>Coupon Networks</th><td><input type="text" name="networks" value="' . esc_attr($networks) . '" class="regular-text" placeholder="amazon,clickbank,walmart"></td></tr>
        </table><p><input type="submit" name="acv_settings" class="button-primary" value="Save Settings"></p></form></div>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 5
        ), $atts);

        $coupons = $this->fetch_coupons($atts['category'], $atts['limit']);
        ob_start();
        ?>
        <div class="acv-coupon-vault" data-category="<?php echo esc_attr($atts['category']); ?>">
            <?php if (empty($coupons)): ?>
                <p>No coupons available. <a href="<?php echo admin_url('options-general.php?page=affiliate-coupon-vault'); ?>">Configure settings</a>.</p>
            <?php else: foreach ($coupons as $coupon): ?>
                <div class="acv-coupon-item">
                    <h4><?php echo esc_html($coupon['title']); ?></h4>
                    <p class="acv-discount">Save <strong><?php echo esc_html($coupon['discount']); ?></strong></p>
                    <p>Code: <code><?php echo esc_html($coupon['code']); ?></code></p>
                    <a href="<?php echo esc_url($coupon['affiliate_link']); ?}" class="acv-button" target="_blank" rel="nofollow">Get Deal <?php echo $this->get_pro_badge(); ?></a>
                </div>
            <?php endforeach; endif; ?>
            <?php if (!$this->is_pro()): ?>
                <div class="acv-upgrade-notice">Upgrade to Pro for unlimited coupons and analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function fetch_coupons($category, $limit) {
        // Simulated API fetch - replace with real affiliate API like CJ, ShareASale, or coupon APIs
        $api_key = get_option('acv_api_key');
        if (empty($api_key)) return array();

        $sample_coupons = array(
            array(
                'title' => '50% Off Hosting Plans',
                'discount' => '50%',
                'code' => 'AFF50',
                'affiliate_link' => 'https://example-affiliate.com/hosting?ref=yourid',
                'expires' => '2026-12-31'
            ),
            array(
                'title' => 'Free Domain with Purchase',
                'discount' => 'Free Domain',
                'code' => 'DOMAINFREE',
                'affiliate_link' => 'https://example-affiliate.com/domain?ref=yourid',
                'expires' => '2026-06-30'
            )
        );

        return array_slice($sample_coupons, 0, $limit);
    }

    private function get_pro_badge() {
        return $this->is_pro() ? '<span class="pro-badge">PRO</span>' : '';
    }

    private function is_pro() {
        // Check for pro license - extend in pro version
        return false;
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.acv-coupon-vault { max-width: 600px; margin: 20px 0; }
.acv-coupon-item { background: #f9f9f9; padding: 20px; margin-bottom: 15px; border-radius: 8px; border-left: 4px solid #0073aa; }
.acv-discount { color: #e74c3c; font-size: 1.2em; }
.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.acv-button:hover { background: #005a87; }
.acv-upgrade-notice { background: #fff3cd; padding: 15px; border-radius: 5px; text-align: center; margin-top: 20px; }
.pro-badge { background: #28a745; color: white; font-size: 0.8em; padding: 2px 6px; border-radius: 3px; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.acv-coupon-vault .acv-button').on('click', function() {
        // Track clicks for pro analytics
        if (typeof gtag !== 'undefined') gtag('event', 'coupon_click', {'event_category': 'affiliate'});
    });
});
</script>
<?php });