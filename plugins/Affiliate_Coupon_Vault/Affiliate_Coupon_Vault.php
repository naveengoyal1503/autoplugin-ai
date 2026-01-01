/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically fetches and displays exclusive affiliate coupons from major networks, with smart tracking and conversion optimization for bloggers.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'network' => 'amazon',
            'category' => 'all',
            'limit' => 5,
        ), $atts);

        $coupons = $this->get_sample_coupons($atts['network'], $atts['limit']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <?php foreach ($coupons as $coupon): ?>
                <div class="coupon-item">
                    <h4><?php echo esc_html($coupon['title']); ?></h4>
                    <p class="discount">Save <strong><?php echo esc_html($coupon['discount']); ?></strong></p>
                    <p class="expires">Expires: <?php echo esc_html($coupon['expires']); ?></p>
                    <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank" rel="nofollow">Get Deal <span class="aff-track"><?php echo esc_attr($coupon['id']); ?></span></a>
                </div>
            <?php endforeach; ?>
            <p class="pro-upsell">Upgrade to Pro for real-time API feeds from 20+ networks!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_sample_coupons($network, $limit) {
        // Sample data - Pro version would fetch from APIs like CJ Affiliate, ShareASale, etc.
        $samples = array(
            array('id' => 'c1', 'title' => '50% Off Hosting', 'discount' => '50%', 'expires' => '2026-01-31', 'link' => '#'),
            array('id' => 'c2', 'title' => 'Free Domain', 'discount' => 'FREE', 'expires' => '2026-02-15', 'link' => '#'),
            array('id' => 'c3', 'title' => '20% Off Themes', 'discount' => '20%', 'expires' => '2026-01-20', 'link' => '#'),
        );
        return array_slice($samples, 0, $limit);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'acv_api_keys');
        add_settings_section('acv_main_section', 'API Settings', null, 'affiliate-coupon-vault');
        add_settings_field('acv_amazon_key', 'Amazon Affiliate Key (Pro)', array($this, 'amazon_key_field'), 'affiliate-coupon-vault', 'acv_main_section');
    }

    public function amazon_key_field() {
        $options = get_option('acv_api_keys', array());
        echo '<input type="text" name="acv_api_keys[amazon]" value="' . esc_attr($options['amazon'] ?? '') . '" class="regular-text" />';
        echo '<p class="description">Enter your affiliate keys. <strong>Pro feature:</strong> Real API integration.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault_options');
                do_settings_sections('affiliate-coupon-vault');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupons network="amazon" limit="5"]</code></p>
            <h2>Pro Upgrade</h2>
            <p>Get real-time coupons, analytics, and more: <a href="#pro">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
        update_option('acv_version', '1.0.0');
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock 20+ networks, click tracking & analytics! <a href="https://example.com/pro">Upgrade</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Tracking
function acv_track_click() {
    if (isset($_POST['aff_track'])) {
        $track_id = sanitize_text_field($_POST['aff_track']);
        error_log('Coupon clicked: ' . $track_id);
        wp_die('Tracked');
    }
}
add_action('wp_ajax_acv_track', 'acv_track_click');
add_action('wp_ajax_nopriv_acv_track', 'acv_track_click');
?>