/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Automatically generates and displays exclusive, trackable coupon codes for affiliate partners, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-script', plugin_dir_url(__FILE__) . 'ecp-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'ecp-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'exclusive-coupons-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('ecp_options', 'ecp_coupons');
        add_settings_section('ecp_main', 'Coupon Settings', null, 'exclusive-coupons-pro');
        add_settings_field('ecp_coupons', 'Coupons', array($this, 'coupons_field'), 'exclusive-coupons-pro', 'ecp_main');
    }

    public function coupons_field() {
        $coupons = get_option('ecp_coupons', array());
        echo '<textarea name="ecp_coupons" rows="10" cols="50">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"name":"Coupon Name","code":"UNIQUECODE","affiliate_url":"https://example.com","discount":"20%","expires":"2026-12-31"}</p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Exclusive Coupons Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('ecp_options');
                do_settings_sections('exclusive-coupons-pro');
                submit_button();
                ?>
            </form>
            <p>Upgrade to Pro for unlimited coupons, analytics, and auto-generation!</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('ecp_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $unique_code = $coupon['code'] . '_' . uniqid();
        $expires = isset($coupon['expires']) ? strtotime($coupon['expires']) : 0;
        if ($expires && $expires < time()) {
            return '<p>Coupon expired.</p>';
        }
        ob_start();
        ?>
        <div class="ecp-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><strong>Exclusive Code:</strong> <span class="ecp-code"><?php echo esc_html($unique_code); ?></span> (<?php echo esc_html($coupon['discount']); ?>)</p>
            <a href="<?php echo esc_url($coupon['affiliate_url'] . '?coupon=' . $unique_code); ?>" class="ecp-button" target="_blank">Get Deal Now</a>
            <?php if ($expires) : ?>
                <p>Expires: <?php echo date('Y-m-d', $expires); ?></p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.ecp-coupon .ecp-code').click(function() {
                navigator.clipboard.writeText($(this).text());
                $(this).text('Copied!');
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('ecp_coupons')) {
            update_option('ecp_coupons', array(
                0 => array(
                    'name' => 'Sample 20% Off',
                    'code' => 'WELCOME20',
                    'affiliate_url' => 'https://example.com',
                    'discount' => '20%',
                    'expires' => '2026-12-31'
                )
            ));
        }
    }
}

new ExclusiveCouponsPro();

// Inline styles and scripts for self-contained

function ecp_add_inline_styles() {
    ?>
    <style>
    .ecp-coupon { border: 2px solid #0073aa; padding: 20px; border-radius: 10px; background: #f9f9f9; text-align: center; max-width: 400px; margin: 20px auto; }
    .ecp-code { background: #0073aa; color: white; padding: 10px; cursor: pointer; border-radius: 5px; font-weight: bold; }
    .ecp-button { display: inline-block; background: #ff6b35; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
    .ecp-button:hover { background: #e55a2b; }
    </style>
    <?php
}
add_action('wp_head', 'ecp_add_inline_styles');

// Minimal JS
function ecp_add_inline_script() {
    ?>
    <script>jQuery(document).ready(function($){$('.ecp-code').click(function(){var t=$(this).text();navigator.clipboard.writeText(t).then(function(){$(this).text('Copied!').delay(2000).queue(function(){$(this).text(t).dequeue();});});});});</script>
    <?php
}
add_action('wp_footer', 'ecp_add_inline_script');

// Pro upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Upgrade for unlimited features! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
});