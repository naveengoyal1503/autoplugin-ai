/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with custom promo codes to boost your affiliate commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
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
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
        wp_localize_script('affiliate-coupon-vault', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_enqueue_scripts($hook) {
        if ('toplevel_page_affiliate-coupon-vault' !== $hook) {
            return;
        }
        wp_enqueue_script('jquery');
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
        $coupons = get_option('affiliate_coupons', array());
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        $coupons = get_option('affiliate_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '<p>Coupon not found.</p>';
        }

        $coupon = $coupons[$atts['id']];
        $code = $this->generate_promo_code($coupon['name']);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-coupon-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <div class="coupon-code"><strong>Promo Code: </strong><span id="promo-code"><?php echo esc_html($code); ?></span></div>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?}" target="_blank" class="coupon-button">Get Deal (<?php echo $coupon['discount']; ?> Off)</a>
            <p class="expires">Expires: <?php echo esc_html($coupon['expires']); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_promo_code($name) {
        return strtoupper(substr(md5($name . time()), 0, 8));
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die();
        }

        $coupons = get_option('affiliate_coupons', array());
        $id = isset($_POST['id']) ? intval($_POST['id']) : count($coupons);
        $coupons[$id] = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
            'discount' => sanitize_text_field($_POST['discount']),
            'expires' => sanitize_text_field($_POST['expires']),
            'name' => sanitize_text_field($_POST['name']),
        );

        update_option('affiliate_coupons', $coupons);
        wp_send_json_success('Coupon saved!');
    }
}

// Create assets directories if they don't exist
$upload_dir = plugin_dir_path(__FILE__) . 'assets/';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

// Simple JS file content
$js_content = "jQuery(document).ready(function($) {
    $('.generate-code').click(function() {
        var couponId = $(this).data('id');
        var name = $('#coupon-name-' + couponId).val();
        $.post(ajax_object.ajax_url, {action: 'generate_code', name: name}, function(code) {
            $('#promo-code-' + couponId).text(code);
        });
    });
});";
file_put_contents($upload_dir . 'script.js', $js_content);

// Simple CSS
$css_content = ".affiliate-coupon-vault { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; }
.coupon-code { font-size: 24px; color: #e74c3c; margin: 10px 0; }
.coupon-button { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.coupon-button:hover { background: #229954; }";
file_put_contents($upload_dir . 'style.css', $css_content);

// Admin page template
$admin_template = '<div class="wrap">
<h1>Affiliate Coupon Vault</h1>
<p><strong>Pro Version:</strong> Unlock unlimited coupons, analytics, auto-expiry, and more! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p>
<div id="coupons-list">
<?php foreach($coupons as $id => $coupon): ?>
<div class="coupon-item">
    <h3><?php echo esc_html($coupon['title']); ?> <small>[affiliate_coupon id="<?php echo $id; ?>"]</small></h3>
</div>
<?php endforeach; ?>
</div>
<h2>Add New Coupon</h2>
<form id="new-coupon-form">
    <p><label>Title: <input type="text" name="title" required></label></p>
    <p><label>Description: <textarea name="description" required></textarea></label></p>
    <p><label>Affiliate Link: <input type="url" name="affiliate_link" required></label></p>
    <p><label>Discount: <input type="text" name="discount" placeholder="50% Off" required></label></p>
    <p><label>Expires: <input type="date" name="expires" required></label></p>
    <p><label>Internal Name: <input type="text" name="name" required></label></p>
    <p><input type="hidden" name="id" value="<?php echo count($coupons); ?>"><button type="submit" class="button-primary">Save Coupon</button></p>
</form>
<script>
jQuery(document).ready(function($) {
    $('#new-coupon-form').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.post(ajax_object.ajax_url, formData + "&action=save_coupon", function() {
            location.reload();
        });
    });
});
</script>
</div>';
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', $admin_template);

AffiliateCouponVault::get_instance();

// Freemium notice
function affiliate_coupon_vault_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Generate unlimited coupons & track clicks! <a href="https://example.com/pro">Get Pro</a></p></div>';
}
add_action('admin_notices', 'affiliate_coupon_vault_pro_notice');