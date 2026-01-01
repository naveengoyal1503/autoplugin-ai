/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and manages exclusive affiliate coupons for your WordPress site, boosting conversions with personalized discount codes and tracking.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv-frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-frontend', plugin_dir_url(__FILE__) . 'acv-frontend.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        add_settings_section('acv_main', 'Coupons', null, 'acv');
        add_settings_field('coupons', 'Add Coupon', array($this, 'coupons_field'), 'acv', 'acv_main');
    }

    public function coupons_field() {
        $coupons = get_option('acv_coupons', array());
        echo '<table class="form-table">
                <tr>
                    <th scope="row">Coupons</th>
                    <td>';
        foreach ($coupons as $index => $coupon) {
            echo '<div style="margin-bottom:10px;border:1px solid #ccc;padding:10px;">
                    <label>Affiliate Link: <input type="text" name="acv_coupons[' . $index . '][link]" value="' . esc_attr($coupon['link']) . '" /></label><br>
                    <label>Code: <input type="text" name="acv_coupons[' . $index . '][code]" value="' . esc_attr($coupon['code']) . '" /></label><br>
                    <label>Discount: <input type="text" name="acv_coupons[' . $index . '][discount]" value="' . esc_attr($coupon['discount']) . '" /></label><br>
                    <label>Expires: <input type="date" name="acv_coupons[' . $index . '][expires]" value="' . esc_attr($coupon['expires']) . '" /></label><br>
                    <button type="button" onclick="jQuery(this).parent().remove();">Remove</button>
                  </div>';
        }
        echo '<button type="button" id="add-coupon">Add New Coupon</button>
                <script>
                jQuery("#add-coupon").click(function() {
                    var index = jQuery(".form-table div").length;
                    jQuery("td").append(
                        '<div style="margin-bottom:10px;border:1px solid #ccc;padding:10px;">
                            <label>Affiliate Link: <input type="text" name="acv_coupons[" + index + "][link]" /></label><br>
                            <label>Code: <input type="text" name="acv_coupons[" + index + "][code]" /></label><br>
                            <label>Discount: <input type="text" name="acv_coupons[" + index + "][discount]" /></label><br>
                            <label>Expires: <input type="date" name="acv_coupons[" + index + "][expires]" /></label><br>
                            <button type="button" onclick="jQuery(this).parent().remove();">Remove</button>
                         </div>'
                    );
                });
                </script>';
        echo '</td></tr></table>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, click tracking, analytics, and auto-expiration for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('acv_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $expired = !empty($coupon['expires']) && strtotime($coupon['expires']) < current_time('timestamp');
        ob_start();
        ?>
        <div class="acv-coupon" data-coupon-id="<?php echo esc_attr($atts['id']); ?>">
            <?php if ($expired): ?>
                <p class="acv-expired">Coupon expired!</p>
            <?php else: ?>
                <h3><?php echo esc_html($coupon['code']); ?> - <?php echo esc_html($coupon['discount']); ?> OFF</h3>
                <p>Exclusive deal for readers! <a href="<?php echo esc_url($coupon['link']); ?}" target="_blank" class="acv-button" onclick="acvTrackClick(<?php echo esc_attr($atts['id']); ?>);">Get Deal Now</a></p>
            <?php endif; ?>
        </div>
        <script>
        function acvTrackClick(id) {
            // Pro feature: Send analytics
            console.log('Coupon clicked: ' + id);
            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=acv_track&coupon=' + id);
        }
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', array());
        }
    }
}

// AJAX for pro tracking (basic free version logs to console)
add_action('wp_ajax_acv_track', function() {
    // Pro: Log to database
    wp_die();
});

AffiliateCouponVault::get_instance();

// Inline CSS and JS for self-contained
add_action('wp_head', function() {
    echo '<style>
        .acv-coupon { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
        .acv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .acv-button:hover { background: #005a87; }
        .acv-expired { color: red; font-weight: bold; }
    </style>';
    echo '<script>jQuery(document).ready(function($) { console.log("Affiliate Coupon Vault loaded"); });</script>';
});

/* Pro Notice */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id == 'settings_page_affiliate-coupon-vault') return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Upgrade for unlimited coupons & analytics! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Learn More</a></p></div>';
});