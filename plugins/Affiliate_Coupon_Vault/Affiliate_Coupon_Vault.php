/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Generate and manage exclusive affiliate coupons with auto-expiring links, personalized promo codes, and conversion tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-js', plugin_dir_url(__FILE__) . 'coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-css', plugin_dir_url(__FILE__) . 'coupon.css', array(), '1.0.0');
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            affiliate_url text NOT NULL,
            code varchar(50) NOT NULL,
            expiry datetime DEFAULT NULL,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function admin_menu() {
        add_menu_page('Coupons', 'Coupons', 'manage_options', 'affiliate-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['add_coupon'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'affiliate_coupons';
            $wpdb->insert($table_name, array(
                'title' => sanitize_text_field($_POST['title']),
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'code' => sanitize_text_field($_POST['code']),
                'expiry' => !empty($_POST['expiry']) ? $_POST['expiry'] : null,
                'max_uses' => intval($_POST['max_uses'])
            ));
        }
        $coupons = $this->get_coupons();
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function get_coupons() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 'affiliate_coupons');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . 'affiliate_coupons' . " WHERE id = %d AND active = 1", $atts['id']));
        if (!$coupon) return '';

        $now = current_time('mysql');
        if ($coupon->expiry && $now > $coupon->expiry) return '<p>Coupon expired.</p>';
        if ($coupon->max_uses && $coupon->uses >= $coupon->max_uses) return '<p>Coupon uses exhausted.</p>';

        $personalized_url = add_query_arg('ref', uniqid(), $coupon->affiliate_url);
        ob_start();
        ?>
        <div class="affiliate-coupon" data-id="<?php echo $coupon->id; ?>">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <p><strong>Code:</strong> <span class="coupon-code"><?php echo esc_html($coupon->code); ?></span></p>
            <a href="<?php echo esc_url($personalized_url); ?>" class="coupon-btn" target="_blank">Get Deal</a>
            <p class="coupon-uses">Uses: <?php echo $coupon->uses; ?>/<?php echo $coupon->max_uses ?: 'Unlimited'; ?></p>
        </div>
        <?php
        return ob_get_clean();
    }
}

new AffiliateCouponVault();

// Premium notice
function acv_premium_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, analytics, and API integrations!</p></div>';
}
add_action('admin_notices', 'acv_premium_notice');

// JS for tracking clicks
function acv_track_click($coupon_id) {
    global $wpdb;
    $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . 'affiliate_coupons' . " SET uses = uses + 1 WHERE id = %d", $coupon_id));
}

// Embed admin page template
$admin_template = '<div class="wrap"><h1>Affiliate Coupons</h1><form method="post"><table class="form-table"><tr><th>Title</th><td><input type="text" name="title" required /></td></tr><tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:100%;" required /></td></tr><tr><th>Code</th><td><input type="text" name="code" required /></td></tr><tr><th>Expiry</th><td><input type="datetime-local" name="expiry" /></td></tr><tr><th>Max Uses</th><td><input type="number" name="max_uses" /></td></tr></table><p><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon" /></p></form><h2>Active Coupons</h2><table class="wp-list-table widefat"><thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Uses</th><th>Shortcode</th></tr></thead><tbody>'; foreach($coupons as $c) { $admin_template .= '<tr><td>'.$c->id.'</td><td>'.$c->title.'</td><td>'.$c->code.'</td><td>'.$c->uses.'/'.$c->max_uses.'</td><td>[affiliate_coupon id="'.$c->id.'"]</td></tr>'; } $admin_template .= '</tbody></table></div>'; file_put_contents(plugin_dir_path(__FILE__).'admin-page.php', $admin_template); // CSS file_put_contents(plugin_dir_path(__FILE__).'coupon.css', '.affiliate-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; } .coupon-code { font-size: 24px; color: #0073aa; font-weight: bold; } .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; } .coupon-btn:hover { background: #005a87; }'); // JS file_put_contents(plugin_dir_path(__FILE__).'coupon.js', 'jQuery(document).ready(function($) { $(".coupon-btn").click(function(e) { var id = $(this).closest(".affiliate-coupon").data("id"); $.post(ajaxurl, { action: "acv_track_click", coupon_id: id }); }); });');