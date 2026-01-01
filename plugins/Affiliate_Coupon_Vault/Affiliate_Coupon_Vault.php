/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes with tracking to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['acv_save'])) {
            update_option('acv_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('acv_affiliate_links', "Amazon: https://amazon.com/affiliate-link?coupon=%CODE%\nBrandX: https://brandx.com/aff?code=%CODE%");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <p><label>Affiliate Links (one per line: Name: URL with %CODE% placeholder):</label></p>
                <textarea name="affiliate_links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea>
                <p class="submit"><input type="submit" name="acv_save" class="button-primary" value="Save Settings"></p>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon]</code></p>
            <p>Pro upgrade for analytics and unlimited coupons: <a href="#pro">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 3), $atts);
        ob_start();
        echo '<div id="acv-coupons" data-count="' . esc_attr($atts['count']) . '"><p>Loading exclusive coupons...</p></div>';
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $links = explode("\n", get_option('acv_affiliate_links', ''));
        $coupons = array();
        $count = intval($_POST['count'] ?? 3);
        for ($i = 0; $i < min($count, count($links)); $i++) {
            $line = trim($links[array_rand($links)]);
            if (strpos($line, ':')) {
                list($name, $url) = explode(':', $line, 2);
                $code = substr(md5(uniqid()), 0, 8);
                $link = str_replace('%CODE%', $code, trim($url));
                $coupons[] = array('name' => trim($name), 'code' => $code, 'link' => $link);
            }
        }
        wp_send_json_success($coupons);
    }

    public function activate() {
        update_option('acv_affiliate_links', "Amazon: https://amazon.com/affiliate-link?coupon=%CODE%\nBrandX: https://brandx.com/aff?code=%CODE%");
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, click tracking, and analytics for $49/year. <a href="#pro">Learn More</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// JS file content would be embedded or separate, but for single file, inline it
?><script type="text/javascript">
jQuery(document).ready(function($) {
    $('#acv-coupons').on('click', '.acv-load', function() {
        $.post(acv_ajax.ajax_url, {
            action: 'acv_generate_coupon',
            nonce: acv_ajax.nonce,
            count: $('#acv-coupons').data('count')
        }, function(response) {
            if (response.success) {
                var html = '';
                $.each(response.data, function(i, coupon) {
                    html += '<div class="acv-coupon"><h4>' + coupon.name + '</h4><p>Code: <strong>' + coupon.code + '</strong></p><a href="' + coupon.link + '" target="_blank" class="button">Shop Now & Save</a></div>';
                });
                $('#acv-coupons').html(html);
            }
        });
    }).find('.acv-load').click();
});
</script>
<style>
#acv-coupons { max-width: 600px; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 5px; }
.acv-coupon h4 { margin: 0 0 10px; color: #333; }
.acv-coupon .button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
</style>