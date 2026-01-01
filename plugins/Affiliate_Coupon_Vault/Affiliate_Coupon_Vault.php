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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 1,
        ), $atts);

        $coupons = get_option('acv_coupons', array());
        $coupon = isset($coupons[$atts['id'] - 1]) ? $coupons[$atts['id'] - 1] : null;

        if (!$coupon) {
            return '<p>No coupon found.</p>';
        }

        $output = '<div class="acv-coupon-vault">';
        $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
        $output .= '<p class="acv-discount">' . esc_html($coupon['discount']) . '</p>';
        $output .= '<p class="acv-description">' . esc_html($coupon['description']) . '</p>';
        $output .= '<a href="' . esc_url($coupon['affiliate_link']) . '" target="_blank" class="acv-button">' . esc_html($coupon['cta']) . ' <span class="acv-code">' . esc_html($coupon['code']) . '</span></a>';
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
        register_setting('acv_settings', 'acv_coupons');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_text_field_deep($_POST['acv_coupons']));
        }
        $coupons = get_option('acv_coupons', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons</th>
                        <td>
                            <div id="coupons-list">
                                <?php $this->render_coupon_fields($coupons); ?>
                            </div>
                            <button type="button" id="add-coupon">Add Coupon</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon_vault id="1"]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-expiry. <a href="#pro">Get Pro</a></p>
        </div>
        <style>
        .acv-coupon-vault { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; }
        .acv-discount { font-size: 2em; color: #0073aa; font-weight: bold; }
        .acv-button { display: inline-block; background: #0073aa; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; }
        .acv-code { background: #fff; color: #0073aa; padding: 5px 10px; margin-left: 10px; font-family: monospace; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            let couponCount = <?php echo count($coupons); ?>;
            $('#add-coupon').click(function() {
                let html = '<div class="coupon-field">' +
                    '<h4>Coupon ' + (++couponCount) + '</h4>' +
                    '<input type="text" name="acv_coupons[' + (couponCount-1) + '][title]" placeholder="Title" /> ' +
                    '<input type="text" name="acv_coupons[' + (couponCount-1) + '][discount]" placeholder="Discount (e.g. 50% OFF)" /> ' +
                    '<textarea name="acv_coupons[' + (couponCount-1) + '][description]" placeholder="Description"></textarea> ' +
                    '<input type="url" name="acv_coupons[' + (couponCount-1) + '][affiliate_link]" placeholder="Affiliate Link" /> ' +
                    '<input type="text" name="acv_coupons[' + (couponCount-1) + '][cta]" placeholder="Button Text" value="Get Deal" /> ' +
                    '<input type="text" name="acv_coupons[' + (couponCount-1) + '][code]" placeholder="Coupon Code" /> ' +
                    '<button type="button" class="remove-coupon">Remove</button>' +
                    '</div>';
                $('#coupons-list').append(html);
            });
            $(document).on('click', '.remove-coupon', function() {
                $(this).closest('.coupon-field').remove();
            });
        });
        </script>
        <?php
    }

    private function render_coupon_fields($coupons) {
        foreach ($coupons as $index => $coupon) {
            echo '<div class="coupon-field">';
            echo '<h4>Coupon ' . ($index + 1) . '</h4>';
            echo '<input type="text" name="acv_coupons[' . $index . '][title]" value="' . esc_attr($coupon['title'] ?? '') . '" placeholder="Title" /> ';
            echo '<input type="text" name="acv_coupons[' . $index . '][discount]" value="' . esc_attr($coupon['discount'] ?? '') . '" placeholder="Discount (e.g. 50% OFF)" /> ';
            echo '<textarea name="acv_coupons[' . $index . '][description]" placeholder="Description">' . esc_textarea($coupon['description'] ?? '') . '</textarea> ';
            echo '<input type="url" name="acv_coupons[' . $index . '][affiliate_link]" value="' . esc_url($coupon['affiliate_link'] ?? '') . '" placeholder="Affiliate Link" /> ';
            echo '<input type="text" name="acv_coupons[' . $index . '][cta]" value="' . esc_attr($coupon['cta'] ?? 'Get Deal') . '" placeholder="Button Text" /> ';
            echo '<input type="text" name="acv_coupons[' . $index . '][code]" value="' . esc_attr($coupon['code'] ?? '') . '" placeholder="Coupon Code" /> ';
            echo '<button type="button" class="remove-coupon">Remove</button>';
            echo '</div>';
        }
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            update_option('acv_coupons', array(array(
                'title' => 'Sample Deal',
                'discount' => '50% OFF',
                'description' => 'Exclusive discount for our readers!',
                'affiliate_link' => 'https://example.com/affiliate',
                'cta' => 'Grab Deal',
                'code' => 'SAVE50'
            )));
        }
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, analytics & more! <a href="https://example.com/pro">Upgrade Now ($49/yr)</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Prevent direct access
if (!defined('ABSPATH')) exit;