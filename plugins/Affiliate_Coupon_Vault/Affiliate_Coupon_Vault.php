/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create exclusive affiliate coupon sections to boost conversions and monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons updated!</p></div>';
        }
        $coupons = get_option('acv_coupons', "Coupon 1|DISCOUNT10|Brand A|https://affiliate-link1.com|Active\nCoupon 2|SAVE20|Brand B|https://affiliate-link2.com|Active");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <form method="post">
                <p><label>Coupons (format: Name|Code|Brand|Affiliate Link|Status):</label></p>
                <textarea name="coupons" rows="10" cols="80"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <p>Use shortcode: <code>[affiliate_coupons]</code></p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, click tracking, analytics dashboard. <a href="#">Get Pro</a></p>
        </div>
        <?php
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_clicks';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            coupon_name varchar(100) NOT NULL,
            click_time datetime DEFAULT CURRENT_TIMESTAMP,
            ip varchar(45) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function coupons_shortcode($atts) {
        $coupons_text = get_option('acv_coupons', "");
        $coupons = explode("\n", trim($coupons_text));
        $output = '<div class="acv-coupons"><h3>Exclusive Coupons</h3><ul>';
        foreach ($coupons as $coupon_line) {
            $parts = explode('|', trim($coupon_line));
            if (count($parts) == 5 && $parts[4] == 'Active') {
                $name = esc_html($parts);
                $code = esc_html($parts[1]);
                $brand = esc_html($parts[2]);
                $link = esc_url($parts[3]);
                $output .= '<li><strong>' . $name . '</strong> - Code: <code>' . $code . '</code> (' . $brand . ") <a href=\"{$link}\" class=\"acv-track\" data-coupon=\"{$name}\">Grab Deal</a></li>";
            }
        }
        $output .= '</ul><p>Limited to 5 free coupons. <a href="#pro">Upgrade for unlimited</a></p></div>';
        return $output;
    }
}

new AffiliateCouponVault();

// Free CSS
/*
.acv-coupons { background: #f9f9f9; padding: 20px; border-radius: 8px; }
.acv-coupons ul { list-style: none; }
.acv-coupons li { margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #0073aa; }
.acv-track { background: #0073aa; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; }
.acv-track:hover { background: #005a87; }
*/

// Free JS
/*
jQuery(document).ready(function($) {
    $('.acv-track').click(function(e) {
        var coupon = $(this).data('coupon');
        $.post(ajaxurl || '', {action: 'acv_track_click', coupon: coupon});
        // Pro: Advanced tracking
    });
});
*/

add_action('wp_ajax_acv_track_click', function() {
    global $wpdb;
    $coupon = sanitize_text_field($_POST['coupon']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $table = $wpdb->prefix . 'acv_clicks';
    $wpdb->insert($table, array('coupon_name' => $coupon, 'ip' => $ip));
    wp_die();
});

add_action('wp_ajax_nopriv_acv_track_click', function() {
    global $wpdb;
    $coupon = sanitize_text_field($_POST['coupon']);
    $ip = $_SERVER['REMOTE_ADDR'];
    $table = $wpdb->prefix . 'acv_clicks';
    $wpdb->insert($table, array('coupon_name' => $coupon, 'ip' => $ip));
    wp_die();
});