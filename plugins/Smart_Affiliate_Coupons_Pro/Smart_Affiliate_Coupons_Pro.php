/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupons Pro
 * Plugin URI: https://example.com/smart-affiliate-coupons
 * Description: Automatically generates and displays personalized affiliate coupons with custom promo codes, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateCoupons {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sac_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sac-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Coupons', 'Affiliate Coupons', 'manage_options', 'sac-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('sac_options', 'sac_settings');
        add_settings_section('sac_main', 'Coupon Settings', null, 'sac-settings');
        add_settings_field('sac_coupons', 'Coupons', array($this, 'coupons_field'), 'sac-settings', 'sac_main');
    }

    public function coupons_field() {
        $settings = get_option('sac_settings', array('coupons' => array()));
        $coupons = $settings['coupons'];
        echo '<textarea name="sac_settings[coupons]" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p>Enter JSON array of coupons: {"name":"Discount","code":"SAVE20","afflink":"https://aff.link","desc":"20% off"}</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupons Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sac_options');
                do_settings_sections('sac-settings');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and auto-expiry for $49/year!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $settings = get_option('sac_settings', array('coupons' => array()));
        $coupons = $settings['coupons'];
        $id = intval($atts['id']);
        if (!isset($coupons[$id])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$id];
        $random_code = substr(md5(uniqid()), 0, 8);
        ob_start();
        ?>
        <div class="sac-coupon" data-id="<?php echo $id; ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <div class="sac-code">Promo Code: <strong><?php echo $random_code; ?></strong></div>
            <a href="<?php echo esc_url($coupon['afflink']); ?}" class="sac-button" target="_blank">Get Deal (Affiliate)</a>
            <p class="sac-copy">Click to copy code: <button onclick="copyCode('<?php echo $random_code; ?>')">Copy</button></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('sac_settings', array('coupons' => array(
            array('name' => 'Sample 20% Off', 'code' => 'SAVE20', 'afflink' => '#', 'desc' => 'Great deal on tools!')
        )));
    }
}

new SmartAffiliateCoupons();

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
.sac-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 10px; text-align: center; }
.sac-code { font-size: 24px; margin: 15px 0; }
.sac-button { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
.sac-button:hover { background: #005a87; }
    </style>';
});

// Inline JS
add_action('wp_footer', function() {
    echo '<script>
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => alert("Copied: " + code));
}
jQuery(document).ready(function($) {
    $(".sac-coupon").on("click", ".sac-button", function() {
        gtag("event", "coupon_click", {"coupon_id": $(this).closest(".sac-coupon").data("id")});
    });
});
    </script>';
});