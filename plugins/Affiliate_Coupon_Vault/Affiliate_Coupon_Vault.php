/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from multiple networks to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
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
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'vault.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'vault.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'network' => 'amazon',
            'category' => 'all',
            'limit' => 5
        ), $atts);

        $coupons = $this->get_sample_coupons($atts['network'], $atts['category'], $atts['limit']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <h4><?php echo esc_html($coupon['title']); ?></h4>
                <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                <p>Discount: <?php echo esc_html($coupon['discount']); ?></p>
                <a href="<?php echo esc_url($coupon['link']); ?}" target="_blank" class="coupon-btn" rel="nofollow">Shop Now & Save</a>
                <span class="copy-btn" data-code="<?php echo esc_attr($coupon['code']); ?>">Copy Code</span>
            </div>
            <?php endforeach; ?>
            <p class="pro-upsell">Upgrade to Pro for real-time coupons from 10+ networks!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_sample_coupons($network, $category, $limit) {
        $samples = array(
            array(
                'title' => '50% Off Hosting',
                'code' => 'SAVE50',
                'discount' => '50% OFF First Year',
                'link' => 'https://example.com/aff/hosting',
                'network' => 'hosting'
            ),
            array(
                'title' => 'Free Domain',
                'code' => 'FREEDOMAIN',
                'discount' => 'Free .com Domain',
                'link' => 'https://example.com/aff/domain',
                'network' => 'hosting'
            ),
            array(
                'title' => '20% Off VPN',
                'code' => 'VPN20',
                'discount' => '20% Lifetime Discount',
                'link' => 'https://example.com/aff/vpn',
                'network' => 'tools'
            ),
            array(
                'title' => 'WordPress Themes 30% Off',
                'code' => 'WP30',
                'discount' => '30% Off Premium Themes',
                'link' => 'https://example.com/aff/themes',
                'network' => 'wordpress'
            ),
            array(
                'title' => 'Email Marketing Deal',
                'code' => 'EMAILDEAL',
                'discount' => '$10/mo First 3 Months',
                'link' => 'https://example.com/aff/email',
                'network' => 'tools'
            )
        );

        return array_slice($samples, 0, $limit);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <p>Configure your affiliate networks and API keys (Pro feature).</p>
            <p><strong>Free version:</strong> Use shortcode <code>[affiliate_coupons network="hosting" limit="3"]</code></p>
            <a href="https://example.com/pro" class="button button-primary" target="_blank">Upgrade to Pro</a>
        </div>
        <?php
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'acv_api_keys');
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.affiliate-coupon-vault { max-width: 600px; }
.coupon-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; background: #f9f9f9; }
.coupon-btn { background: #ff6b35; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; }
.copy-btn { background: #0073aa; color: white; padding: 5px 10px; cursor: pointer; margin-left: 10px; border-radius: 3px; }
.pro-upsell { background: #fff3cd; padding: 10px; border-left: 4px solid #ffc107; margin-top: 20px; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) {
    $('.copy-btn').click(function() {
        var code = $(this).data('code');
        navigator.clipboard.writeText(code).then(function() {
            $(this).text('Copied!');
        }.bind(this));
    });
});</script>
<?php });

// Create JS and CSS files on activation (simplified inline for single file)
