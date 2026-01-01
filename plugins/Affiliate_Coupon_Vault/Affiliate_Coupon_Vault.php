/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons, tracks clicks, and boosts conversions for WordPress bloggers.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_api_key') === false) {
            add_option('acv_api_key', wp_generate_uuid4());
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon Code: SAVE20\nAffiliate Link: https://example.com/aff\nDescription: 20% off first purchase");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="80" placeholder="Coupon Code: CODE\nAffiliate Link: https://aff.link\nDescription: Description"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">One coupon per line: Coupon Code:CODE&#10;Affiliate Link:URL&#10;Description:TEXT</p>
                <p><input type="submit" name="acv_save" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Pro Tip: Use shortcode <code>[affiliate_coupon id="1"]</code> to display coupons. Upgrade to Pro for auto-generation and analytics.</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => '1'), $atts);
        $coupons = explode("\n\n", get_option('acv_coupons', ''));
        $coupon_data = explode("\n", $coupons ?? '');
        $code = '';
        $link = '';
        $desc = '';
        foreach ($coupon_data as $line) {
            if (strpos($line, 'Code:') === 0) $code = substr($line, 5);
            if (strpos($line, 'Link:') === 0) $link = substr($line, 5);
            if (strpos($line, 'Description:') === 0) $desc = substr($line, 11);
        }
        $track_url = add_query_arg('acv_track', '1', $link);
        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3>🎉 Exclusive Deal: <strong><?php echo esc_html($code); ?></strong></h3>
            <p><?php echo esc_html($desc); ?></p>
            <a href="#" class="acv-button button" data-url="<?php echo esc_url($track_url); ?>">Get Deal Now (Tracked)</a>
        </div>
        <script>jQuery('.acv-button').click(function(e){e.preventDefault();window.location=$(this).data('url');});</script>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) wp_die('Security check failed');
        $url = sanitize_url($_POST['url']);
        error_log('ACV Click tracked: ' . $url);
        wp_redirect($url);
        exit;
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

AffiliateCouponVault::get_instance();

// Inline JS
add_action('wp_footer', function() {
    if (is_admin()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-button').on('click', function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            $.post(acv_ajax.ajaxurl, {
                action: 'acv_track_click',
                nonce: acv_ajax.nonce,
                url: url
            }, function() {
                window.location = url;
            });
        });
    });
    </script>
    <?php
});