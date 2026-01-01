/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate and manage exclusive, trackable coupon codes to boost affiliate commissions and reader loyalty.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $this->load_textdomain();
    }

    public function load_textdomain() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ecp_coupons', array());
        add_option('ecp_pro_version', false);
    }

    public function admin_menu() {
        add_options_page(
            'Exclusive Coupons Pro',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['ecp_save'])) {
            update_option('ecp_coupons', sanitize_text_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('ecp_coupons', '{}');
        ?>
        <div class="wrap">
            <h1><?php _e('Exclusive Coupons Pro', 'exclusive-coupons-pro'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Coupons (JSON format: {"code":"discount","afflink":"url","uses":0})', 'exclusive-coupons-pro'); ?></th>
                        <td><textarea name="coupons" rows="10" cols="50"><?php echo esc_textarea($coupons); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(__('Save Coupons', 'exclusive-coupons-pro'), 'primary', 'ecp_save'); ?>
            </form>
            <h2><?php _e('Shortcode', 'exclusive-coupons-pro'); ?></h2>
            <p><?php _e('Use <code>[exclusive_coupon code="YOURCODE"]</code> to display a coupon.', 'exclusive-coupons-pro'); ?></p>
            <?php if (!get_option('ecp_pro_version')) : ?>
            <div class="notice notice-info"><p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics & auto-expiration for $49/year!</p></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        $coupons = json_decode(get_option('ecp_coupons', '{}'), true);
        if (!isset($coupons[$atts['code']])) {
            return '<p>Coupon not found.</p>';
        }
        $coupon = $coupons[$atts['code']];
        $uses = intval($coupon['uses']) + 1;
        $coupons[$atts['code']]['uses'] = $uses;
        update_option('ecp_coupons', json_encode($coupons));
        if (get_option('ecp_pro_version') && isset($coupon['max_uses']) && $uses > $coupon['max_uses']) {
            return '<p>This coupon has expired.</p>';
        }
        $button = '<a href="' . esc_url($coupon['afflink']) . '" target="_blank" class="ecp-coupon-btn" data-code="' . esc_attr($atts['code']) . '">' . __('Get ' . $coupon['discount'] . ' OFF!', 'exclusive-coupons-pro') . '</a>';
        return '<div class="ecp-coupon"><strong>' . esc_html($atts['code']) . '</strong>: ' . esc_html($coupon['discount']) . ' | Uses: ' . $uses . $button . '</div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ecp-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'settings_page_exclusive-coupons-pro') return;
        wp_enqueue_style('ecp-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
    }
}

new ExclusiveCouponsPro();

// Pro nag (demo)
add_action('admin_notices', function() {
    if (!get_option('ecp_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-upgrade"><p>Upgrade to <strong>Exclusive Coupons Pro</strong> for advanced features! <a href="#pro">Get it now</a></p></div>';
    }
});

/*
Example coupons JSON:
{
  "SAVE20": {
    "discount": "20% OFF",
    "afflink": "https://affiliate-link.com",
    "uses": 5
  }
}
*/
?>