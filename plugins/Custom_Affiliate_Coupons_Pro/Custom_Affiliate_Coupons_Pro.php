/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Affiliate Coupons Pro
 * Plugin URI: https://example.com/affiliate-coupons-pro
 * Description: Generate personalized affiliate coupon codes, track usage, and boost commissions with exclusive discounts.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class CustomAffiliateCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_save_coupon', array($this, 'save_coupon'));
        }
    }

    public function admin_menu() {
        add_menu_page('Affiliate Coupons', 'Coupons', 'manage_options', 'affiliate-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['coupon_code'])) {
            update_option('aff_coupons', $_POST['coupons'] ?? array());
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('aff_coupons', array());
        ?>
        <div class="wrap">
            <h1>Manage Affiliate Coupons</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon Name</th>
                        <th>Code</th>
                        <th>Affiliate Link</th>
                        <th>Discount</th>
                    </tr>
                    <?php foreach ($coupons as $index => $coupon): ?>
                    <tr>
                        <td><input type="text" name="coupons[<?php echo $index; ?>][name]" value="<?php echo esc_attr($coupon['name']); ?>" /></td>
                        <td><input type="text" name="coupons[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" /></td>
                        <td><input type="url" name="coupons[<?php echo $index; ?>][link]" value="<?php echo esc_attr($coupon['link']); ?>" /></td>
                        <td><input type="text" name="coupons[<?php echo $index; ?>][discount]" value="<?php echo esc_attr($coupon['discount']); ?>" /></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="text" name="new_coupon[name]" placeholder="New Name" /></td>
                        <td><input type="text" name="new_coupon[code]" placeholder="CODE123" /></td>
                        <td><input type="url" name="new_coupon[link]" placeholder="https://affiliate-link.com" /></td>
                        <td><input type="text" name="new_coupon[discount]" placeholder="20% OFF" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Coupons'); ?>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon id="0"]</code> (replace id with coupon index).</p>
        </div>
        <?php
    }

    public function save_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        $coupons = $_POST['coupons'] ?? array();
        $new = $_POST['new_coupon'] ?? array();
        if (!empty($new['code'])) {
            $coupons[] = $new;
        }
        update_option('aff_coupons', $coupons);
        wp_redirect(admin_url('admin.php?page=affiliate-coupons'));
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('aff_coupons', array());
        $id = intval($atts['id']);
        if (!isset($coupons[$id])) return '';
        $coupon = $coupons[$id];
        $unique_id = uniqid();
        return '<div class="aff-coupon" data-coupon-id="' . $id . '"><h3>' . esc_html($coupon['name']) . '</h3><p><strong>' . esc_html($coupon['discount']) . '</strong></p><input type="text" readonly value="' . esc_attr($coupon['code']) . '" onclick="this.select()" /><a href="' . esc_url($coupon['link']) . '" target="_blank" class="button">Get Deal</a><p class="uses">Uses: <span id="uses-' . $unique_id . '">0</span></p></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', "jQuery(document).on('click', '.aff-coupon a', function(e) { var uses = jQuery('#uses-' + jQuery(this).closest('.aff-coupon').data('coupon-id')).text(); uses = parseInt(uses) + 1; jQuery('#uses-' + jQuery(this).closest('.aff-coupon').data('coupon-id')).text(uses); jQuery.post(ajaxurl, {action: 'track_coupon_use', id: jQuery(this).closest('.aff-coupon').data('coupon-id')}); });");
        wp_localize_script('jquery', 'ajaxurl', admin_url('admin-ajax.php'));
        wp_enqueue_style('aff-coupons-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function activate() {
        add_option('aff_coupons', array(
            array('name' => 'Sample Deal', 'code' => 'WELCOME20', 'link' => '#', 'discount' => '20% OFF')
        ));
    }
}

new CustomAffiliateCouponsPro();

add_action('wp_ajax_track_coupon_use', function() {
    $id = intval($_POST['id']);
    $stats = get_option('aff_coupon_stats', array());
    $stats[$id] = ($stats[$id] ?? 0) + 1;
    update_option('aff_coupon_stats', $stats);
    wp_die();
});

/* Add basic CSS */
function aff_coupons_css() {
    echo '<style>.aff-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; } .aff-coupon input { font-size: 18px; padding: 10px; width: 200px; } .aff-coupon a.button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; }</style>';
}
add_action('wp_head', 'aff_coupons_css');

/* Premium upsell notice */
function aff_premium_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Custom Affiliate Coupons Pro</strong> for unlimited coupons, analytics dashboard, and email integrations! <a href="https://example.com/premium">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'aff_premium_notice');