/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
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
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_id' => '',
            'coupon_code' => 'SAVE20',
            'discount' => '20%',
            'link' => '#',
            'expires' => date('Y-m-d', strtotime('+30 days')),
        ), $atts);

        $output = '<div class="affiliate-coupon-vault" data-affiliate="' . esc_attr($atts['affiliate_id']) . '">';
        $output .= '<div class="coupon-header">Exclusive Deal: <strong>' . esc_html($atts['discount']) . ' OFF</strong></div>';
        $output .= '<div class="coupon-code">' . esc_html($atts['coupon_code']) . '</div>';
        $output .= '<a href="' . esc_url($atts['link']) . '" class="coupon-button" target="_blank">Shop Now & Save</a>';
        $output .= '<div class="coupon-expires">Expires: ' . esc_html($atts['expires']) . '</div>';
        $output .= '</div>';

        return $output;
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'acv_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault_options');
                do_settings_sections('affiliate_coupon_vault_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th>Default Affiliate Link</th>
                        <td><input type="url" name="acv_settings[default_link]" value="<?php echo esc_attr(get_option('acv_settings')['default_link'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Enable Analytics</th>
                        <td><input type="checkbox" name="acv_settings[analytics]" <?php checked(get_option('acv_settings')['analytics'] ?? 0); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon_vault affiliate_id="your-id" coupon_code="SAVE20" discount="20%" link="#" expires="2026-01-31"]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, click tracking, A/B testing - <a href="#pro">Get Pro for $49/year</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('acv_settings', array('default_link' => '', 'analytics' => 0));
    }

    private function load_textdomain() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

// Initialize
AffiliateCouponVault::get_instance();

// Pro Upsell Notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons & analytics for $49/year! <a href="#pro">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Assets (base64 or inline for single file)
/*
Create folders: assets/script.js and assets/style.css
script.js:
$(document).ready(function() {
  $('.coupon-button').click(function() {
    $(this).text('Copied! Shop Now');
    navigator.clipboard.writeText($(this).prev().text());
  });
});

style.css:
.affiliate-coupon-vault {
  border: 2px dashed #0073aa;
  padding: 20px;
  margin: 20px 0;
  text-align: center;
  background: #f9f9f9;
  border-radius: 10px;
}
.coupon-button {
  background: #0073aa;
  color: white;
  padding: 10px 20px;
  text-decoration: none;
  border-radius: 5px;
  display: inline-block;
}
*/