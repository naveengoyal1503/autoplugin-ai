/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automate affiliate coupon management, personalized promo code generation, and conversion tracking to boost WordPress blog revenue.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('acv_coupon_box', array($this, 'coupon_box_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_options', 'acv_coupons');
        add_settings_section('acv_section', 'Coupons', null, 'acv');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_options');
                do_settings_sections('acv');
                $coupons = get_option('acv_coupons', array());
                ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons (JSON)</th>
                        <td><textarea name="acv_coupons" rows="10" cols="50"><?php echo esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, auto-expiry. <a href="#pro">Get Pro ($49/yr)</a></p>
        </div>
        <?php
    }

    public function coupon_box_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('acv_coupons', array());
        if (empty($atts['id']) || !isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $personalized = $this->generate_personalized_code($coupon['code']);
        ob_start();
        ?>
        <div class="acv-coupon-box" data-coupon-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p>Code: <strong id="acv-code"><?php echo esc_html($personalized); ?></strong></p>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?}" target="_blank" class="acv-button" onclick="acvTrackClick('<?php echo esc_attr($atts['id']); ?>')">Get Deal &copy; <?php echo esc_html($coupon['affiliate']); ?></a>
            <small>Expires: <?php echo esc_html($coupon['expires']); ?></small>
        </div>
        <script>
        function acvTrackClick(id) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=acv_track&coupon=' + id);
            gtag('event', 'coupon_click', {'coupon_id': id});
        }
        </script>
        <?php
        return ob_get_clean();
    }

    public function generate_personalized_code($base_code) {
        $user_id = get_current_user_id();
        if ($user_id) {
            return $base_code . '-' . substr(md5($user_id . time()), 0, 4);
        }
        return $base_code . '-' . substr(md5($_SERVER['REMOTE_ADDR'] . time()), 0, 4);
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', array(
                'demo1' => array(
                    'title' => '50% Off Hosting',
                    'code' => 'SAVE50',
                    'description' => 'Exclusive for readers: Save on premium hosting.',
                    'affiliate_link' => 'https://example.com/affiliate',
                    'affiliate' => 'Bluehost',
                    'expires' => '2026-12-31'
                )
            ));
        }
    }
}

new AffiliateCouponVault();

// AJAX tracking
add_action('wp_ajax_acv_track', 'acv_track_click');
add_action('wp_ajax_nopriv_acv_track', 'acv_track_click');
function acv_track_click() {
    $coupon = sanitize_text_field($_GET['coupon'] ?? '');
    if ($coupon) {
        // Log to DB or analytics (Pro feature)
        error_log('ACV Click: ' . $coupon);
    }
    wp_die();
}

// Free limit
add_action('admin_notices', function() {
    $coupons = get_option('acv_coupons', array());
    if (count($coupons) > 5 && !defined('ACV_PRO')) {
        echo '<div class="notice notice-warning"><p>Affiliate Coupon Vault: Upgrade to Pro for unlimited coupons!</p></div>';
    }
});

// CSS
add_action('wp_head', function() { ?>
<style>
.acv-coupon-box { border: 2px solid #007cba; padding: 20px; border-radius: 10px; background: #f9f9f9; margin: 20px 0; }
.acv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.acv-button:hover { background: #005a87; }
</style>
<?php });

// JS
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) { $('.acv-coupon-box .acv-button').click(function(){ /* Pro analytics */ }); });</script>
<?php });