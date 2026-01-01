/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons and deals to boost your affiliate commissions.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_id' => get_option('acv_affiliate_id', ''),
            'category' => 'general',
            'limit' => 5
        ), $atts);

        $coupons = $this->generate_coupons($atts['category'], $atts['limit']);
        ob_start();
        ?>
        <div class="acv-coupon-vault">
            <h3>Exclusive Deals & Coupons</h3>
            <?php foreach ($coupons as $coupon): ?>
                <div class="acv-coupon-item">
                    <h4><?php echo esc_html($coupon['title']); ?></h4>
                    <p><?php echo esc_html($coupon['description']); ?></p>
                    <div class="acv-coupon-code"><?php echo esc_html($coupon['code']); ?></div>
                    <a href="<?php echo esc_url($coupon['link']); ?}" target="_blank" class="acv-button" rel="nofollow">Get Deal (<?php echo esc_html($coupon['affiliate_label']); ?>)</a>
                </div>
            <?php endforeach; ?>
            <p class="acv-pro-upsell">Upgrade to Pro for unlimited coupons & analytics!</p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($category, $limit) {
        $sample_coupons = array(
            array(
                'title' => '50% Off Hosting',
                'description' => 'Get premium hosting at half price with this exclusive code.',
                'code' => 'AFF50',
                'link' => 'https://example-affiliate.com/hosting?ref=' . get_option('acv_affiliate_id'),
                'affiliate_label' => 'Bluehost Affiliate'
            ),
            array(
                'title' => 'Free Domain + SSL',
                'description' => 'New customers get a free domain and SSL certificate.',
                'code' => 'FREEDOMAIN',
                'link' => 'https://example-affiliate.com/domain?ref=' . get_option('acv_affiliate_id'),
                'affiliate_label' => 'Namecheap Affiliate'
            ),
            array(
                'title' => '20% Off WordPress Themes',
                'description' => 'Premium themes for your site at discounted price.',
                'code' => 'WP20OFF',
                'link' => 'https://example-affiliate.com/themes?ref=' . get_option('acv_affiliate_id'),
                'affiliate_label' => 'ThemeForest Affiliate'
            )
        );

        // Rotate/shuffle for uniqueness
        shuffle($sample_coupons);
        return array_slice($sample_coupons, 0, $limit);
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

    public function admin_init() {
        register_setting('acv_settings', 'acv_affiliate_id');
        register_setting('acv_settings', 'acv_pro_key');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Your Affiliate ID</th>
                        <td><input type="text" name="acv_affiliate_id" value="<?php echo esc_attr(get_option('acv_affiliate_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="acv_pro_key" value="<?php echo esc_attr(get_option('acv_pro_key')); ?>" class="regular-text" />
                        <p class="description">Enter Pro key to unlock unlimited coupons. <a href="https://example.com/pro" target="_blank">Get Pro</a></p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Add <code>[affiliate_coupon_vault]</code> shortcode to any post/page.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('acv_affiliate_id', '');
        flush_rewrite_rules();
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_admin_notice() {
    if (!get_option('acv_pro_key')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Upgrade to Pro for unlimited coupons, custom APIs, and analytics! <a href="options-general.php?page=affiliate-coupon-vault">Settings</a> | <a href="https://example.com/pro" target="_blank">Buy Pro</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');

// Minimal CSS (inline for single file)
function acv_inline_styles() {
    echo '<style>
        .acv-coupon-vault { max-width: 600px; margin: 20px 0; }
        .acv-coupon-item { background: #f9f9f9; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #0073aa; }
        .acv-coupon-code { background: #fff; padding: 10px; font-family: monospace; text-align: center; margin: 10px 0; }
        .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .acv-button:hover { background: #005a87; }
        .acv-pro-upsell { text-align: center; background: #fff3cd; padding: 15px; border-radius: 4px; margin-top: 20px; }
    </style>';
}
add_action('wp_head', 'acv_inline_styles');
add_action('admin_head', 'acv_inline_styles');

// Dummy JS for future interactivity
function acv_inline_scripts() {
    echo '<script>jQuery(document).ready(function($) { $(".acv-coupon-code").on("click", function() { var code = $(this).text(); navigator.clipboard.writeText(code); $(this).text("Copied!"); }); });</script>';
}
add_action('wp_footer', 'acv_inline_scripts');