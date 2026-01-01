/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons to boost conversions and earnings.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $this->load_textdomain();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 5,
        ), $atts);

        $coupons = get_option('acv_coupons', array());
        $output = '<div class="acv-vault">';
        $count = 0;
        foreach ($coupons as $coupon) {
            if ($count >= $atts['limit']) break;
            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
            $output .= '<p>' . esc_html($coupon['description']) . '</p>';
            $output .= '<div class="acv-code">Code: <strong>' . esc_html($coupon['code']) . '</strong></div>';
            $output .= '<a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" class="acv-button" rel="nofollow">Shop Now & Save</a>';
            $output .= '</div>';
            $count++;
        }
        $output .= '</div>';
        return $output;
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_text_field_deep($_POST['acv_coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('acv_coupons', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="">
                <h2>Add/Edit Coupons</h2>
                <table class="form-table">
                    <tr>
                        <th>Title</th>
                        <td><input type="text" name="acv_coupons[title]" value="<?php echo isset($coupons['title']) ? esc_attr($coupons['title']) : ''; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="acv_coupons[description]"><?php echo isset($coupons['description']) ? esc_textarea($coupons['description']) : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="acv_coupons[code]" value="<?php echo isset($coupons['code']) ? esc_attr($coupons['code']) : ''; ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="acv_coupons[affiliate_link]" value="<?php echo isset($coupons['affiliate_link']) ? esc_attr($coupons['affiliate_link']) : ''; ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Unlimited coupons, analytics, auto-generation, custom designs. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', array());
        }
    }

    private function load_textdomain() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

AffiliateCouponVault::get_instance();

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.acv-vault { display: grid; gap: 20px; max-width: 600px; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: #f9f9f9; }
.acv-code { font-size: 1.2em; color: #e74c3c; margin: 10px 0; }
.acv-button { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
.acv-button:hover { background: #219a52; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.acv-button').on('click', function() {
        gtag('event', 'coupon_click', {'coupon': $(this).data('coupon')});
    });
});
</script>
<?php });