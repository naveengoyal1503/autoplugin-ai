/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generate, manage, and display exclusive affiliate coupons to boost your commissions.
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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
    }

    public function init() {
        if (is_admin()) {
            wp_register_style('acv-admin', plugin_dir_url(__FILE__) . 'admin.css');
            wp_enqueue_style('acv-admin');
        }
    }

    public function enqueue_scripts() {
        wp_register_style('acv-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_style('acv-style');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('acv_coupons', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <textarea name="coupons" rows="20" cols="80" placeholder="Code: DISCOUNT10&#10;Affiliate Link: https://affiliate-link.com/?ref=you&#10;Description: 10% off first purchase&#10;---&#10;"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Format: Code: CODE<br>Affiliate Link: URL<br>Description: Text<br>--- (separate coupons)</p>
                <p><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Upgrade to Pro for unlimited coupons, analytics, and auto-expiration! <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = explode('---', get_option('acv_coupons', ''));
        $html = '';
        foreach ($coupons as $coupon) {
            $lines = preg_split('/\r\n|\r|\n/', trim($coupon));
            $code = $link = $desc = '';
            foreach ($lines as $line) {
                if (strpos($line, 'Code:') === 0) $code = substr($line, 5);
                elseif (strpos($line, 'Affiliate Link:') === 0) $link = substr($line, 13);
                elseif (strpos($line, 'Description:') === 0) $desc = substr($line, 12);
            }
            if ($code && $link) {
                $html .= '<div class="acv-coupon"><strong>' . esc_html($code) . '</strong><br>' . esc_html($desc) . '<br><a href="' . esc_url($link) . '" class="button" target="_blank">Get Deal</a></div>';
            }
        }
        return $html ?: '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Configure now</a>.</p>';
    }

    public function ajax_save_coupon() {
        // Pro feature placeholder
        wp_die();
    }
}

AffiliateCouponVault::get_instance();

/* Pro Upsell Notice */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
});

/* CSS */
function acv_add_styles() {
    echo '<style>
.acv-coupon { background: #f9f9f9; padding: 20px; margin: 10px 0; border-left: 4px solid #0073aa; border-radius: 4px; }
.acv-coupon strong { color: #0073aa; font-size: 24px; }
.acv-coupon .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
.acv-coupon .button:hover { background: #005a87; }
</style>';
}
add_action('wp_head', 'acv_add_styles');
add_action('admin_head', 'acv_add_styles');