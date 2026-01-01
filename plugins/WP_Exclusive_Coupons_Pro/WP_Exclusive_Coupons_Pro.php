/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons
 * Description: Generate exclusive affiliate coupons with tracking and auto-expiration to maximize commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Exclusive_Coupons {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('wpec_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('wp-exclusive-coupons', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('wpec_api_key', '');
        add_option('wpec_coupons', array());
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons',
            'Coupons',
            'manage_options',
            'wp-exclusive-coupons',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['wpec_save'])) {
            update_option('wpec_api_key', sanitize_text_field($_POST['api_key']));
            $coupons = array();
            if (isset($_POST['coupons']) && is_array($_POST['coupons'])) {
                foreach ($_POST['coupons'] as $index => $coupon) {
                    $coupons[] = array(
                        'code' => sanitize_text_field($coupon['code']),
                        'affiliate_link' => esc_url_raw($coupon['link']),
                        'description' => sanitize_textarea_field($coupon['desc']),
                        'expiry' => sanitize_text_field($coupon['expiry']),
                        'uses' => intval($coupon['uses']),
                        'max_uses' => intval($coupon['max_uses'])
                    );
                }
            }
            update_option('wpec_coupons', $coupons);
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }

        $api_key = get_option('wpec_api_key', '');
        $coupons = get_option('wpec_coupons', array());
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro</h1>
            <form method="post">
                <?php wp_nonce_field('wpec_save'); ?>
                <table class="form-table">
                    <tr>
                        <th>Pro API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="Enter Pro key for unlimited features"></td>
                    </tr>
                </table>
                <h2>Add/Edit Coupons</h2>
                <div id="coupons-list">
                    <?php foreach ($coupons as $index => $coupon): ?>
                    <div class="coupon-row">
                        <input type="hidden" name="coupons[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>">
                        <label>Coupon Code: <input type="text" name="coupons[<?php echo $index; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>"></label>
                        <label>Affiliate Link: <input type="url" name="coupons[<?php echo $index; ?>][link]" value="<?php echo esc_attr($coupon['affiliate_link']); ?>"></label>
                        <label>Description: <textarea name="coupons[<?php echo $index; ?>][desc]"><?php echo esc_textarea($coupon['description']); ?></textarea></label>
                        <label>Expiry (YYYY-MM-DD): <input type="date" name="coupons[<?php echo $index; ?>][expiry]" value="<?php echo esc_attr($coupon['expiry']); ?>"></label>
                        <label>Current Uses: <input type="number" name="coupons[<?php echo $index; ?>][uses]" value="<?php echo intval($coupon['uses']); ?>"></label>
                        <label>Max Uses: <input type="number" name="coupons[<?php echo $index; ?>][max_uses]" value="<?php echo intval($coupon['max_uses']); ?>"></label>
                        <button type="button" onclick="removeRow(this)">Remove</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="addCouponRow()">Add Coupon</button>
                <p><em>Upgrade to Pro for unlimited coupons and analytics.</em></p>
                <?php submit_button('Save Coupons'); ?>
            </form>
        </div>
        <script>
        let couponIndex = <?php echo count($coupons); ?>;
        function addCouponRow() {
            const div = document.createElement('div');
            div.className = 'coupon-row';
            div.innerHTML = `
                <label>Coupon Code: <input type="text" name="coupons[${couponIndex}][code]"></label>
                <label>Affiliate Link: <input type="url" name="coupons[${couponIndex}][link]"></label>
                <label>Description: <textarea name="coupons[${couponIndex}][desc]"></textarea></label>
                <label>Expiry (YYYY-MM-DD): <input type="date" name="coupons[${couponIndex}][expiry]"></label>
                <label>Current Uses: <input type="number" name="coupons[${couponIndex}][uses]" value="0"></label>
                <label>Max Uses: <input type="number" name="coupons[${couponIndex}][max_uses]"></label>
                <button type="button" onclick="removeRow(this)">Remove</button>
            `;
            document.getElementById('coupons-list').appendChild(div);
            couponIndex++;
        }
        function removeRow(btn) {
            btn.parentElement.remove();
        }
        </script>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('wpec_coupons', array());
        $index = intval($atts['id']);
        if (!isset($coupons[$index])) {
            return '<p>Coupon not found.</p>';
        }
        $coupon = $coupons[$index];
        $today = current_time('Y-m-d');
        if ($today > $coupon['expiry'] || $coupon['uses'] >= $coupon['max_uses']) {
            return '<p class="wpec-expired">Coupon expired or max uses reached.</p>';
        }
        $api_key = get_option('wpec_api_key', '');
        $is_pro = !empty($api_key) && strlen($api_key) > 10;
        $track_id = $is_pro ? uniqid('wpec_') : '';
        return '<div class="wpec-coupon" data-id="' . $index . '" data-track="' . esc_attr($track_id) . '"><h3>' . esc_html($coupon['code']) . '</h3><p>' . esc_html($coupon['description']) . '</p><a href="' . esc_url($coupon['affiliate_link']) . '" class="button wpec-btn" target="_blank">Get Deal</a></div>';
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpec-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('wpec-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('wpec-script', 'wpec_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('wpec_nonce')));
    }
}

new WP_Exclusive_Coupons();

add_action('wp_ajax_wpec_track_use', 'wpec_track_use');
function wpec_track_use() {
    check_ajax_referer('wpec_nonce', 'nonce');
    $id = intval($_POST['id']);
    $coupons = get_option('wpec_coupons', array());
    if (isset($coupons[$id])) {
        $coupons[$id]['uses']++;
        update_option('wpec_coupons', $coupons);
        wp_send_json_success();
    }
    wp_send_json_error();
}

/* Pro Teaser */
function wpec_pro_notice() {
    if (empty(get_option('wpec_api_key'))) {
        echo '<div class="notice notice-info"><p>Unlock unlimited coupons and analytics with <strong>WP Exclusive Coupons Pro</strong> - <a href="https://example.com/pro" target="_blank">Upgrade now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'wpec_pro_notice');

/* Minimal CSS - inline for single file */
function wpec_inline_styles() {
    echo '<style>
    .wpec-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; }
    .wpec-coupon h3 { color: #0073aa; margin: 0 0 10px; }
    .wpec-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; }
    .wpec-btn:hover { background: #005a87; }
    .wpec-expired { color: #d63638; text-align: center; padding: 20px; background: #fce8e6; }
    .coupon-row { border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #fff; }
    .coupon-row label { display: block; margin: 5px 0; }
    </style>';
}
add_action('wp_head', 'wpec_inline_styles');
add_action('admin_head', 'wpec_inline_styles');

/* Minimal JS - inline */
function wpec_inline_scripts() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.wpec-btn').on('click', function() {
            var $this = $(this).closest('.wpec-coupon');
            $.post(wpec_ajax.ajax_url, {
                action: 'wpec_track_use',
                nonce: wpec_ajax.nonce,
                id: $this.data('id')
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'wpec_inline_scripts');
add_action('admin_footer', 'wpec_inline_scripts');