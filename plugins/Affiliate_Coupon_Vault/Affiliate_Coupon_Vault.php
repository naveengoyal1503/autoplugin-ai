/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
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
            'niche' => 'general',
            'count' => 3
        ), $atts);

        $coupons = $this->generate_coupons($atts['niche'], $atts['count']);
        ob_start();
        ?>
        <div class="aff-coupon-vault">
            <h3>Exclusive Deals for You</h3>
            <?php foreach ($coupons as $coupon): ?>
                <div class="coupon-item">
                    <h4><?php echo esc_html($coupon['title']); ?></h4>
                    <p><?php echo esc_html($coupon['description']); ?></p>
                    <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                    <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Shop Now & Save</a>
                    <small>Affiliate link - We earn a commission at no extra cost to you.</small>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($niche, $count) {
        $samples = array(
            array(
                'title' => '20% Off Hosting',
                'description' => 'Get started with premium hosting.',
                'code' => 'AFF20',
                'link' => 'https://example-affiliate.com/hosting?ref=yourid'
            ),
            array(
                'title' => '50% Off VPN',
                'description' => 'Secure your online privacy.',
                'code' => 'VPN50',
                'link' => 'https://example-affiliate.com/vpn?ref=yourid'
            ),
            array(
                'title' => '$10 Off Tools',
                'description' => 'Essential productivity suite.',
                'code' => 'TOOLS10',
                'link' => 'https://example-affiliate.com/tools?ref=yourid'
            )
        );

        // Simulate niche-specific coupons
        if ($niche === 'tech') {
            $samples['title'] = '30% Off Gadgets';
        }

        return array_slice($samples, 0, $count);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'aff-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <p>Upgrade to Pro for custom affiliate links, analytics, and unlimited coupons!</p>
            <a href="https://example.com/pro" class="button button-primary">Get Pro Version</a>
        </div>
        <?php
    }

    public function admin_init() {
        // Pro upsell notice
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Inline CSS and JS for self-contained plugin

function aff_coupon_vault_styles() {
    ?>
    <style>
    .aff-coupon-vault { max-width: 600px; margin: 20px 0; }
    .coupon-item { background: #f9f9f9; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #0073aa; }
    .coupon-code { background: #fff; padding: 10px; font-family: monospace; font-size: 18px; text-align: center; margin: 10px 0; border: 1px dashed #ccc; }
    .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
    .coupon-btn:hover { background: #005a87; }
    </style>
    <?php
}
add_action('wp_head', 'aff_coupon_vault_styles');

/*
 * Pro Features Teaser JS
 */
function aff_coupon_vault_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.coupon-btn').on('click', function() {
            // Track clicks (Pro feature)
            console.log('Coupon clicked!');
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'aff_coupon_vault_scripts');

?>