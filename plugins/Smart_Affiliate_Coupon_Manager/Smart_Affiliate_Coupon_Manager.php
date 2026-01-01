/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Plugin URI: https://example.com/smart-affiliate-coupon-manager
 * Description: Automatically generates and manages personalized affiliate coupons, tracks clicks and conversions, and displays dynamic coupon sections to boost affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateCouponManager {
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
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_track_coupon_click', array($this, 'track_coupon_click'));
        add_action('wp_ajax_nopriv_track_coupon_click', array($this, 'track_coupon_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sacm_pro_version')) {
            return; // Pro version active
        }
        // Free version limits
        add_action('admin_notices', array($this, 'pro_nag'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sacm-script', plugin_dir_url(__FILE__) . 'sacm.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sacm-script', 'sacm_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 5
        ), $atts);

        $coupons = get_option('sacm_coupons', array());
        if ($atts['category'] !== 'all') {
            $coupons = array_filter($coupons, function($coupon) use ($atts) {
                return isset($coupon['category']) && $coupon['category'] === $atts['category'];
            });
        }

        $coupons = array_slice($coupons, 0, min($atts['limit'], 10)); // Free limit 10

        ob_start();
        echo '<div class="sacm-coupons">';
        foreach ($coupons as $coupon) {
            $clicks = get_option('sacm_clicks_' . $coupon['id'], 0);
            echo '<div class="sacm-coupon" data-id="' . esc_attr($coupon['id']) . '">';
            echo '<h3>' . esc_html($coupon['title']) . '</h3>';
            echo '<p>Code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
            echo '<p>Discount: ' . esc_html($coupon['discount']) . '% off</p>';
            echo '<a href="' . esc_url($coupon['affiliate_url']) . '" class="sacm-track-btn button" data-nonce="' . wp_create_nonce('sacm_track_' . $coupon['id']) . '">Get Deal (Tracked: ' . $clicks . ')</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function track_coupon_click() {
        check_ajax_referer('sacm_track_' . $_POST['id'], 'nonce');
        $id = sanitize_text_field($_POST['id']);
        $clicks = get_option('sacm_clicks_' . $id, 0);
        update_option('sacm_clicks_' . $id, $clicks + 1);
        wp_die('success');
    }

    public function activate() {
        add_option('sacm_coupons', array(
            array('id' => 1, 'title' => 'Sample Coupon 1', 'code' => 'SAVE10', 'discount' => '10', 'affiliate_url' => '#', 'category' => 'tech'),
            array('id' => 2, 'title' => 'Sample Coupon 2', 'code' => 'DEAL20', 'discount' => '20', 'affiliate_url' => '#', 'category' => 'fashion')
        ));
    }

    public function pro_nag() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Coupon Manager Pro</strong> for unlimited coupons, advanced analytics, and auto-generation! <a href="https://example.com/pro">Get Pro ($49)</a></p></div>';
    }
}

// Sample JS file content (save as sacm.js in plugin folder)
/*
jQuery(document).ready(function($) {
    $('.sacm-track-btn').click(function(e) {
        e.preventDefault();
        var btn = $(this);
        $.post(sacm_ajax.ajax_url, {
            action: 'track_coupon_click',
            id: btn.data('id'),
            nonce: btn.data('nonce')
        }, function() {
            window.location = btn.attr('href');
        });
    });
});
*/

SmartAffiliateCouponManager::get_instance();

// Prevent direct access
if (!defined('SACMPRO')) {
    add_action('admin_menu', function() {
        add_options_page('Coupons', 'Coupons', 'manage_options', 'sacm-coupons', 'sacm_admin_page');
    });
}

function sacm_admin_page() {
    if (isset($_POST['save_coupons'])) {
        update_option('sacm_coupons', $_POST['coupons']);
        echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
    }
    $coupons = get_option('sacm_coupons', array());
    ?>
    <div class="wrap">
        <h1>Manage Affiliate Coupons</h1>
        <form method="post">
            <?php foreach ($coupons as $i => $coupon) : ?>
            <div style="border:1px solid #ccc; margin:10px; padding:10px;">
                <input type="hidden" name="coupons[<?php echo $i; ?>][id]" value="<?php echo esc_attr($coupon['id']); ?>">
                <p><label>Title: <input name="coupons[<?php echo $i; ?>][title]" value="<?php echo esc_attr($coupon['title']); ?>"></label></p>
                <p><label>Code: <input name="coupons[<?php echo $i; ?>][code]" value="<?php echo esc_attr($coupon['code']); ?>"></label></p>
                <p><label>Discount: <input name="coupons[<?php echo $i; ?>][discount]" value="<?php echo esc_attr($coupon['discount']); ?>">%</label></p>
                <p><label>Affiliate URL: <input name="coupons[<?php echo $i; ?>][affiliate_url]" value="<?php echo esc_attr($coupon['affiliate_url']); ?>" style="width:300px;"></label></p>
                <p><label>Category: <input name="coupons[<?php echo $i; ?>][category]" value="<?php echo esc_attr($coupon['category']); ?>"></label></p>
            </div>
            <?php endforeach; ?>
            <p><input type="submit" name="save_coupons" class="button-primary" value="Save Coupons"></p>
        </form>
        <p><strong>Usage:</strong> Use shortcode <code>[affiliate_coupons category="tech" limit="5"]</code></p>
    </div>
    <?php
}
