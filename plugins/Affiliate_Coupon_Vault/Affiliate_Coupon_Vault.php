/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('affiliate-coupon-vault', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_affiliate-coupon-vault') return;
        wp_enqueue_style('affiliate-coupon-vault-admin', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page'),
            'dashicons-cart',
            30
        );
    }

    public function admin_page() {
        $coupons = get_option('acv_coupons', array());
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function ajax_save_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $coupons = get_option('acv_coupons', array());
        $id = sanitize_text_field($_POST['id']);
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
            'code' => sanitize_text_field($_POST['code']),
            'discount' => sanitize_text_field($_POST['discount']),
            'expires' => sanitize_text_field($_POST['expires']),
            'image' => esc_url_raw($_POST['image'])
        );
        if ($id === 'new') {
            $data['id'] = uniqid();
            $coupons[] = $data;
        } else {
            foreach ($coupons as &$coupon) {
                if ($coupon['id'] === $id) {
                    $coupon = $data;
                    break;
                }
            }
        }
        update_option('acv_coupons', $coupons);
        wp_send_json_success('Coupon saved!');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => ''
        ), $atts);

        $coupons = get_option('acv_coupons', array());
        $coupon = null;
        foreach ($coupons as $c) {
            if ($c['id'] === $atts['id']) {
                $coupon = $c;
                break;
            }
        }
        if (!$coupon) return '';

        $personalized_code = $coupon['code'] . '-' . substr(md5(auth()->user_id ?? 'guest'), 0, 4);

        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center;">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <?php if ($coupon['image']) : ?>
                <img src="<?php echo esc_url($coupon['image']); ?>" alt="<?php echo esc_attr($coupon['title']); ?>" style="max-width: 200px;">
            <?php endif; ?>
            <p><strong>Discount:</strong> <?php echo esc_html($coupon['discount']); ?></p>
            <p><strong>Your Code:</strong> <code><?php echo esc_html($personalized_code); ?></code></p>
            <?php if ($coupon['expires']) : ?>
                <p><strong>Expires:</strong> <?php echo esc_html($coupon['expires']); ?></p>
            <?php endif; ?>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?>" target="_blank" class="button button-primary" style="display: inline-block; margin-top: 10px; padding: 10px 20px;">Get Deal Now!</a>
        </div>
        <?php
        return ob_get_clean();
    }
}

new AffiliateCouponVault();

// Pro upgrade notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Create style.css file content
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', '.acv-coupon { font-family: Arial, sans-serif; } .acv-coupon code { background: #fff; padding: 5px; border-radius: 3px; }');

// Create script.js
file_put_contents(plugin_dir_path(__FILE__) . 'script.js', 'jQuery(document).ready(function($) { console.log("Affiliate Coupon Vault loaded"); });');

// Create admin-style.css
file_put_contents(plugin_dir_path(__FILE__) . 'admin-style.css', 'body { font-family: Arial; }');

// Placeholder for admin-page.php
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', '<div class="wrap"><h1>Affiliate Coupon Vault</h1><div id="acv-coupons-list">Loading...</div><button id="add-coupon">Add New Coupon</button><script>/* Admin JS for CRUD */</script></div>');