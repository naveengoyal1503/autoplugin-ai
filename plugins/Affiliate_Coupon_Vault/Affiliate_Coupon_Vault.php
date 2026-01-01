/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost conversions.
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
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
        add_settings_section('main_section', 'Coupon Settings', null, 'affiliate-coupon-vault');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'main_section');
    }

    public function coupons_field() {
        $settings = get_option('affiliate_coupon_vault_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array();
        echo '<textarea name="affiliate_coupon_vault_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"name":"Coupon Name","code":"SAVE10","affiliate_link":"https://aff.link","description":"10% off"}</p>';
        echo '<p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-expiration. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>';
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
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('affiliate_coupon_vault_settings', array());
        $coupons = isset($settings['coupons']) ? json_decode($settings['coupons'], true) : array();
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $track_id = uniqid('acv_');
        $link = add_query_arg('acv_track', $track_id, $coupon['affiliate_link']);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-track="<?php echo esc_attr($track_id); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($link); ?>" class="coupon-button" target="_blank">Shop Now & Save</a>
            <small>Tracked via Affiliate Coupon Vault</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array('coupons' => json_encode(array(
            array('name' => 'Sample 10% Off', 'code' => 'SAVE10', 'affiliate_link' => 'https://example.com/aff', 'description' => 'Get 10% off your first purchase')
        ))));
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, detailed analytics, and more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Inline styles
add_action('wp_head', function() {
    echo '<style>
        .affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; }
        .coupon-code { background: #fff; font-size: 24px; font-weight: bold; padding: 10px; margin: 10px 0; border: 1px solid #ddd; display: inline-block; }
        .coupon-button { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; }
        .coupon-button:hover { background: #005a87; }
    </style>';
});

// Basic tracking
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.affiliate-coupon-vault').on('click', '.coupon-button', function() {
            var track = $(this).closest('.affiliate-coupon-vault').data('track');
            // Pro: Send to analytics endpoint
            console.log('Coupon clicked: ' + track);
        });
    });
    </script>
    <?php
});