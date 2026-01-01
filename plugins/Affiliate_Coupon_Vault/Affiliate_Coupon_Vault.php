/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_settings', 'affiliate_coupons');
        add_settings_section('coupons_section', 'Manage Coupons', null, 'affiliate-coupon-vault');
        add_settings_field('coupons_list', 'Coupons', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'coupons_section');
    }

    public function coupons_field() {
        $coupons = get_option('affiliate_coupons', array());
        echo '<textarea name="affiliate_coupons" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>JSON format: [{"name":"Coupon Name","code":"SAVE20","affiliate_link":"https://aff.link","description":"20% off"}]</p>';
        echo '<p><strong>Pro Upgrade:</strong> Unlimited coupons, auto-generation, analytics ($49/yr)</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_settings');
                do_settings_sections('affiliate-coupon-vault');
                submit_button();
                ?>
            </form>
            <p>Insert <code>[affiliate_coupon]</code> shortcode anywhere. <a href="#" onclick="alert('Pro features: Unlimited, tracking, APIs. Visit example.com/pro')">Upgrade to Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('affiliate_coupons', array());
        if (empty($coupons)) {
            return '<p><em>Add coupons in settings for Affiliate Coupon Vault.</em></p>';
        }
        $coupon = $coupons[array_rand($coupons)];
        ob_start();
        ?>
        <div id="affiliate-coupon-vault" class="coupon-vault-box">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?>" class="coupon-btn" target="_blank">Get Deal (Affiliate Link)</a>
            <small>Your unique coupon! <span class="pro-tease">Pro: Track clicks</span></small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('affiliate_coupons')) {
            update_option('affiliate_coupons', array(
                array('name' => 'Sample Coupon', 'code' => 'WELCOME10', 'affiliate_link' => '#', 'description' => '10% off first purchase')
            ));
        }
    }
}

// Inline CSS/JS for single file
add_action('wp_head', function() {
    echo '<style>
    .coupon-vault-box { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
    .coupon-code { background: #fff; font-size: 24px; font-weight: bold; padding: 10px; margin: 10px 0; display: inline-block; border: 1px solid #ddd; }
    .coupon-btn { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 10px; }
    .coupon-btn:hover { background: #005a87; }
    .pro-tease { color: #ff6600; }
    </style>';
});

add_action('wp_footer', function() {
    echo '<script>jQuery(document).ready(function($) { $(".coupon-btn").click(function(){ console.log("Coupon clicked - Pro tracks conversions"); }); });</script>';
});

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock unlimited coupons & analytics with <a href="https://example.com/pro">Pro ($49/yr)</a>! Earn more commissions.</p></div>';
}
add_action('admin_notices', 'acv_admin_notice');