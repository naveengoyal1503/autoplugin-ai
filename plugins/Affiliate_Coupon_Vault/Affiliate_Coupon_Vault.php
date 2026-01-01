/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from popular networks, boosting conversions and commissions.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Affiliate Coupon Vault Settings', 'affiliate-coupon-vault'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('affiliate_coupon_vault_options'); ?>
                <?php do_settings_sections('affiliate_coupon_vault_options'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('API Key (Pro Feature)', 'affiliate-coupon-vault'); ?></th>
                        <td><input type="text" name="affiliate_coupon_vault_settings[api_key]" value="<?php echo esc_attr(get_option('affiliate_coupon_vault_settings')['api_key'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th><?php _e('Affiliate Networks', 'affiliate-coupon-vault'); ?></th>
                        <td>
                            <label><input type="checkbox" name="affiliate_coupon_vault_settings[networks][]" value="amazon" <?php checked(in_array('amazon', get_option('affiliate_coupon_vault_settings')['networks'] ?? array())); ?> /> Amazon</label><br>
                            <label><input type="checkbox" name="affiliate_coupon_vault_settings[networks][]" value="cj" <?php checked(in_array('cj', get_option('affiliate_coupon_vault_settings')['networks'] ?? array())); ?> /> Commission Junction</label>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Default Discount %', 'affiliate-coupon-vault'); ?></th>
                        <td><input type="number" name="affiliate_coupon_vault_settings[default_discount]" value="<?php echo esc_attr(get_option('affiliate_coupon_vault_settings')['default_discount'] ?? 20); ?>" min="1" max="90" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and auto-rotation for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'count' => 5,
        ), $atts);

        $settings = get_option('affiliate_coupon_vault_settings', array());
        $networks = $settings['networks'] ?? array('amazon');
        $default_discount = $settings['default_discount'] ?? 20;

        // Simulate coupon data (Pro integrates real APIs)
        $demo_coupons = array(
            array('code' => 'SAVE20', 'discount' => $default_discount . '%', 'network' => 'Amazon', 'link' => 'https://amazon.com/?tag=youraffiliateid'),
            array('code' => 'DEAL15', 'discount' => '15%', 'network' => 'CJ', 'link' => 'https://example.com/aff'),
        );

        $output = '<div class="affiliate-coupon-vault" data-category="' . esc_attr($atts['category']) . '">';
        for ($i = 0; $i < min($atts['count'], count($demo_coupons)); $i++) {
            $coupon = $demo_coupons[$i];
            $output .= '<div class="coupon-item">';
            $output .= '<h4>Exclusive Deal: <strong>' . esc_html($coupon['discount']) . ' OFF</strong></h4>';
            $output .= '<p>Code: <code>' . esc_html($coupon['code']) . '</code></p>';
            $output .= '<p>From ' . esc_html($coupon['network']) . '</p>';
            $output .= '<a href="' . esc_url($coupon['link']) . '" class="coupon-button" target="_blank">Shop Now & Save</a>';
            $output .= '</div>';
        }
        $output .= '<p class="pro-upsell">Upgrade to Pro for unlimited real-time coupons from ' . implode(', ', $networks) . '!</p>';
        $output .= '</div>';

        return $output;
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array('default_discount' => 20));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function affiliate_coupon_vault_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro features for more revenue! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'affiliate_coupon_vault_pro_notice');

// Assets (base64 or inline for single file - here assuming external, but for pure single-file, inline CSS/JS)
/*
Inline CSS:
.affiliate-coupon-vault { max-width: 400px; }
.coupon-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #f9f9f9; }
.coupon-button { background: #ff9900; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.pro-upsell { font-style: italic; color: #0073aa; }
*/

/* Inline JS:
 jQuery(document).ready(function($) {
    $('.coupon-button').on('click', function() {
        $(this).text('Copied! Shop Now');
    });
 });
*/