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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
    }

    public function activate() {
        add_option('acv_coupons', array(
            array('code' => 'SAVE10', 'affiliate_link' => '', 'discount' => '10%', 'uses' => 0, 'max_uses' => 100)
        ));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupons = get_option('acv_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }
        $coupon = $coupons[$atts['id']];
        $clicks = get_option('acv_clicks_' . $atts['id'], 0);
        ob_start();
        ?>
        <div class="acv-coupon" data-id="<?php echo esc_attr($atts['id']); ?>">
            <h3><?php echo esc_html($coupon['discount']); ?> Off!</h3>
            <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
            <p>Clicks: <?php echo esc_html($clicks); ?> | Uses: <?php echo esc_html($coupon['uses']); ?>/<?php echo esc_html($coupon['max_uses']); ?></p>
            <a href="#" class="acv-copy-btn">Copy Code</a>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?>" target="_blank" class="button acv-use-coupon" data-coupon="<?php echo esc_attr($coupon['code']); ?>">Use Coupon</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        $coupons = get_option('acv_coupons', array());
        $new_coupon = array(
            'code' => wp_generate_password(8, false),
            'affiliate_link' => sanitize_url($_POST['link']),
            'discount' => sanitize_text_field($_POST['discount']),
            'uses' => 0,
            'max_uses' => intval($_POST['max_uses'])
        );
        $coupons[] = $new_coupon;
        update_option('acv_coupons', $coupons);
        wp_send_json_success(array('id' => count($coupons) - 1, 'code' => $new_coupon['code']));
    }
}

AffiliateCouponVault::get_instance();

// Admin menu
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_admin_page');
    });
}

function acv_admin_page() {
    if (isset($_POST['submit'])) {
        $coupons = get_option('acv_coupons', array());
        $coupons[] = array(
            'code' => sanitize_text_field($_POST['code']),
            'affiliate_link' => esc_url_raw($_POST['link']),
            'discount' => sanitize_text_field($_POST['discount']),
            'uses' => 0,
            'max_uses' => intval($_POST['max_uses'])
        );
        update_option('acv_coupons', $coupons);
    }
    $coupons = get_option('acv_coupons', array());
    echo '<div class="wrap"><h1>Affiliate Coupon Vault</h1><form method="post">';
    echo '<table class="form-table"><tr><th>Code</th><td><input name="code" value="" /></td></tr>';
    echo '<tr><th>Affiliate Link</th><td><input name="link" size="50" value="" /></td></tr>';
    echo '<tr><th>Discount</th><td><input name="discount" value="10%" /></td></tr>';
    echo '<tr><th>Max Uses</th><td><input name="max_uses" type="number" value="100" /></td></tr></table>';
    wp_nonce_field('acv_admin');
    echo '<p><input type="submit" name="submit" class="button-primary" value="Add Coupon" /></p></form>';
    echo '<h2>Existing Coupons</h2><ul>';
    foreach ($coupons as $i => $c) {
        $clicks = get_option('acv_clicks_' . $i, 0);
        echo '<li>ID: ' . $i . ' - ' . esc_html($c['code']) . ' (' . esc_html($c['discount']) . ') Clicks: ' . $clicks . ' <small>[<a href="[affiliate_coupon id=' . $i . ']" target="_blank">Shortcode</a>]</small></li>';
    }
    echo '</ul></div>';
}

// Track clicks
add_action('wp_ajax_acv_track_click', 'acv_track_click');
add_action('wp_ajax_nopriv_acv_track_click', 'acv_track_click');
function acv_track_click() {
    $id = intval($_POST['id']);
    $clicks = get_option('acv_clicks_' . $id, 0) + 1;
    update_option('acv_clicks_' . $id, $clicks);
    wp_die();
}

// JS and CSS placeholders (in real plugin, include files)
function acv_script() { ?>
<script>
jQuery(document).ready(function($) {
    $('.acv-use-coupon').click(function(e) {
        e.preventDefault();
        var id = $(this).closest('.acv-coupon').data('id');
        $.post(acv_ajax.ajax_url, {action: 'acv_track_click', id: id});
        window.open($(this).attr('href'), '_blank');
    });
    $('.acv-copy-btn').click(function() {
        var code = $(this).siblings('p strong').text();
        navigator.clipboard.writeText(code);
        $(this).text('Copied!');
    });
});
</script>
<style>
.acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; }
.acv-coupon h3 { color: #0073aa; }
.acv-use-coupon { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }
.acv-copy-btn { margin-right: 10px; padding: 5px 10px; }
</style>
<?php }
add_action('wp_footer', 'acv_script');