/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupon codes with tracking, boosting conversions and commissions.
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
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_settings');
        add_settings_section('main_section', 'Coupon Settings', null, 'affiliate-coupon-vault');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'affiliate-coupon-vault', 'main_section');
    }

    public function coupons_field() {
        $settings = get_option('affiliate_coupon_settings', array());
        $coupons = isset($settings['coupons']) ? $settings['coupons'] : array();
        echo '<textarea name="affiliate_coupon_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>Enter JSON array of coupons: {"name":"Coupon Name","code":"SAVE10","afflink":"https://affiliate.link","description":"10% off"}</p>';
        echo '<p><strong>Pro:</strong> Unlimited coupons, analytics. <a href="https://example.com/pro">Upgrade</a></p>';
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
        $settings = get_option('affiliate_coupon_settings', array());
        $coupons = isset($settings['coupons']) ? json_decode($settings['coupons'], true) : array();
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $track_id = uniqid('acv_');
        $click_url = add_query_arg('acv_track', $track_id, $coupon['afflink']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault" id="<?php echo esc_attr($track_id); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($click_url); ?>" class="coupon-button" target="_blank">Shop Now & Save</a>
            <p class="copy-notice">Click to copy code</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo esc_js($track_id); ?> .coupon-code').click(function() {
                navigator.clipboard.writeText('<?php echo esc_js($coupon['code']); ?>');
                $(this).addClass('copied');
                setTimeout(() => $(this).removeClass('copied'), 2000);
            });
            $('#<?php echo esc_js($track_id); ?> .coupon-button').click(function(e) {
                gtag('event', 'coupon_click', {'coupon_id': '<?php echo esc_attr($atts['id']); ?>'});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('affiliate_coupon_settings')) {
            update_option('affiliate_coupon_settings', array('coupons' => array(
                array('name' => 'Sample Coupon', 'code' => 'WELCOME10', 'afflink' => '#', 'description' => '10% off first purchase')
            )));
        }
    }
}

AffiliateCouponVault::get_instance();

/* Inline CSS for single file */
function acv_inline_styles() {
    echo '<style>
    .affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; border-radius: 10px; background: #f9f9f9; text-align: center; }
    .affiliate-coupon-vault h3 { color: #007cba; margin: 0 0 10px; }
    .coupon-code { background: #fff; display: inline-block; padding: 10px 20px; font-size: 24px; font-weight: bold; cursor: pointer; border: 2px solid #007cba; border-radius: 5px; margin: 10px 0; transition: all 0.3s; }
    .coupon-code.copied { background: #4CAF50; color: white; border-color: #4CAF50; }
    .coupon-button { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; transition: background 0.3s; }
    .coupon-button:hover { background: #005a87; }
    .copy-notice { font-size: 12px; color: #666; margin: 5px 0 0; }
    @media (max-width: 768px) { .coupon-code { font-size: 20px; } }
    </style>';
}
add_action('wp_head', 'acv_inline_styles');

/* Inline JS for single file */
function acv_inline_scripts() {
    echo '<script>jQuery(document).ready(function($){ /* Enhanced tracking */ });</script>';
}
add_action('wp_footer', 'acv_inline_scripts');