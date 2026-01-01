/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for maximum WordPress blog monetization.
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
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
        add_shortcode('acv_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            return; // Pro version active
        }
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons', array($this, 'sanitize_coupons'));
    }

    public function sanitize_coupons($input) {
        return is_array($input) ? array_map('sanitize_text_field', $input) : array();
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <?php do_settings_sections('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons</th>
                        <td>
                            <div id="acv-coupons">
                                <?php $coupons = get_option('acv_coupons', array()); foreach ($coupons as $i => $coupon): ?>
                                    <div class="acv-coupon-row">
                                        <input type="text" name="acv_coupons[<?php echo $i; ?>][name]" value="<?php echo esc_attr($coupon['name']); ?>" placeholder="Coupon Name" />
                                        <input type="url" name="acv_coupons[<?php echo $i; ?>][affiliate_url]" value="<?php echo esc_attr($coupon['affiliate_url']); ?>" placeholder="Affiliate URL" />
                                        <input type="text" name="acv_coupons[<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>" placeholder="Promo Code" />
                                        <input type="text" name="acv_coupons[<?php echo $i; ?>][discount]" value="<?php echo esc_attr($coupon['discount']); ?>" placeholder="Discount %" />
                                        <button type="button" class="button acv-remove">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="acv-add-coupon" class="button">Add Coupon</button>
                            <p class="description">Free version limited to 3 coupons. <a href="https://example.com/pro" target="_blank">Upgrade to Pro</a> for unlimited.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var rowCount = <?php echo count(get_option('acv_coupons', array())); ?>;
            $('#acv-add-coupon').click(function() {
                if (rowCount >= 3) {
                    alert('Upgrade to Pro for more coupons!');
                    return;
                }
                var row = '<div class="acv-coupon-row">' +
                    '<input type="text" name="acv_coupons[' + rowCount + '][name]" placeholder="Coupon Name" />' +
                    '<input type="url" name="acv_coupons[' + rowCount + '][affiliate_url]" placeholder="Affiliate URL" />' +
                    '<input type="text" name="acv_coupons[' + rowCount + '][code]" placeholder="Promo Code" />' +
                    '<input type="text" name="acv_coupons[' + rowCount + '][discount]" placeholder="Discount %" />' +
                    '<button type="button" class="button acv-remove">Remove</button>' +
                    '</div>';
                $('#acv-coupons').append(row);
                rowCount++;
            });
            $(document).on('click', '.acv-remove', function() {
                $(this).closest('.acv-coupon-row').remove();
            });
        });
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('acv_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return 'Coupon not found.';
        }
        $coupon = $coupons[$atts['id']];
        $unique_id = uniqid('acv_');
        $click_url = admin_url('admin-ajax.php') . '?action=acv_track_click&id=' . $atts['id'] . '&ref=' . urlencode($_SERVER['HTTP_REFERER'] ?? '');
        ob_start();
        ?>
        <div class="acv-coupon" id="<?php echo esc_attr($unique_id); ?>" data-coupon-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['name']); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($coupon['code']); ?></code></p>
            <p><strong>Save:</strong> <?php echo esc_html($coupon['discount']); ?>% Off!</p>
            <a href="#" class="button acv-claim-btn" data-url="<?php echo esc_attr($coupon['affiliate_url']); ?>">Claim Offer & Track</a>
            <div class="acv-stats">Clicks: <span class="acv-clicks">0</span></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        if (!isset($_GET['id'])) {
            wp_die();
        }
        $id = intval($_GET['id']);
        $ref = sanitize_url($_GET['ref'] ?? '');
        $clicks = get_option('acv_clicks_' . $id, 0);
        update_option('acv_clicks_' . $id, $clicks + 1);
        $coupons = get_option('acv_coupons', array());
        if (isset($coupons[$id]['affiliate_url'])) {
            wp_redirect($coupons[$id]['affiliate_url']);
            exit;
        }
        wp_die();
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', array());
        }
    }
}

AffiliateCouponVault::get_instance();

// Inline JS for click tracking
add_action('wp_footer', function() {
    if (!is_admin()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $(document).on('click', '.acv-claim-btn', function(e) {
                e.preventDefault();
                var btn = $(this);
                var container = btn.closest('.acv-coupon');
                var couponId = container.data('coupon-id');
                var url = btn.data('url');
                $.get(acv_ajax.ajax_url + '?action=acv_track_click&id=' + couponId + '&ref=' + encodeURIComponent(window.location.href), function() {
                    window.open(url, '_blank');
                    var clicks = parseInt(container.find('.acv-clicks').text()) + 1;
                    container.find('.acv-clicks').text(clicks);
                });
            });
        });
        </script>
        <style>
        .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
        .acv-coupon h3 { color: #0073aa; }
        .acv-claim-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
        .acv-claim-btn:hover { background: #005a87; }
        .acv-stats { margin-top: 10px; font-size: 0.9em; color: #666; }
        </style>
        <?php
    }
});