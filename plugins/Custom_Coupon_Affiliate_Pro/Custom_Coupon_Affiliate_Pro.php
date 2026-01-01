/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Coupon Affiliate Pro
 * Plugin URI: https://example.com/custom-coupon-pro
 * Description: Generate exclusive custom coupons for affiliate marketing, track clicks, and boost site revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class CustomCouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
        add_shortcode('coupon_display', array($this, 'coupon_shortcode'));
        add_rewrite_rule('coupon/([a-z0-9-]+)/track/?$', 'index.php?coupon_track=$matches[1]', 'top');
        add_rewrite_tag('%coupon_track%', '([^&]+)');
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('coupon-pro-js', plugin_dir_url(__FILE__) . 'coupon-pro.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('coupon-pro-css', plugin_dir_url(__FILE__) . 'coupon-pro.css', array(), '1.0.0');
    }

    public function query_vars($vars) {
        $vars[] = 'coupon_track';
        return $vars;
    }

    public function template_redirect() {
        if (get_query_var('coupon_track')) {
            $code = get_query_var('coupon_track');
            $coupon = $this->get_coupon_by_code($code);
            if ($coupon) {
                $this->log_click($code);
                wp_redirect($coupon['affiliate_url'], 302);
                exit;
            }
        }
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_pro';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(50) NOT NULL UNIQUE,
            description text,
            affiliate_url varchar(500) NOT NULL,
            discount varchar(50),
            expires datetime,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function get_coupons() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_pro';
        return $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    }

    public function get_coupon_by_code($code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_pro';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE code = %s", $code), ARRAY_A);
    }

    public function log_click($code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'coupon_pro';
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE code = %s", $code));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupon = $this->get_coupon_by_code($atts['id']);
        if (!$coupon) return '';
        $expires = $coupon['expires'] ? 'Expires: ' . date('M j, Y', strtotime($coupon['expires'])) : 'No expiration';
        $url = home_url('/coupon/' . $coupon['code'] . '/track/');
        ob_start();
        ?>
        <div class="coupon-card">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <?php if ($coupon['discount']) : ?>
            <div class="discount-badge"><?php echo esc_html($coupon['discount']); ?> OFF</div>
            <?php endif; ?>
            <a href="<?php echo esc_url($url); ?>" class="coupon-btn" target="_blank">Get Coupon (<?php echo $coupon['clicks']; ?> used)</a>
            <small><?php echo esc_html($expires); ?></small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Coupon Pro Settings', 'Coupon Pro', 'manage_options', 'coupon-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('coupon_pro_options', 'coupon_pro_coupons', array($this, 'sanitize_coupons'));
        add_settings_section('coupon_section', 'Add New Coupon', null, 'coupon-pro');
        add_settings_field('coupons', 'Coupons', array($this, 'coupons_field'), 'coupon-pro', 'coupon_section');
    }

    public function sanitize_coupons($input) {
        return $input;
    }

    public function coupons_field() {
        $coupons = get_option('coupon_pro_coupons', array());
        echo '<textarea name="coupon_pro_coupons" rows="10" cols="50" style="width:100%;">' . esc_textarea(json_encode($coupons, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON array of coupons: {"title":"Test","code":"TEST123","description":"Test desc","affiliate_url":"https://example.com","discount":"20%","expires":"2026-12-31 23:59:59"}</p>';
        echo '<p><strong>Upgrade to Pro for unlimited coupons, analytics dashboard, and auto-expiration!</strong> <a href="#" onclick="alert(\'Pro version: $49/year - Contact support@example.com\')">Get Pro</a></p>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Coupon Affiliate Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('coupon_pro_options');
                do_settings_sections('coupon-pro');
                submit_button();
                ?>
            </form>
            <h2>Shortcode Usage</h2>
            <p>Use <code>[coupon_display id="YOURCODE"]</code> to display coupons.</p>
            <h2>Analytics</h2>
            <?php
            $coupons = $this->get_coupons();
            echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Title</th><th>Code</th><th>Clicks</th></tr></thead><tbody>';
            foreach ($coupons as $c) {
                echo '<tr><td>' . esc_html($c['title']) . '</td><td>' . esc_html($c['code']) . '</td><td>' . esc_html($c['clicks']) . '</td></tr>';
            }
            echo '</tbody></table>';
            ?>
        </div>
        <?php
    }

    public function activate() {
        $this->create_table();
        update_option('coupon_pro_coupons', array());
    }
}

new CustomCouponAffiliatePro();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.coupon-card { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; border-radius: 8px; background: #f9f9f9; }
.coupon-btn { display: inline-block; background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
.coupon-btn:hover { background: #005a87; }
.discount-badge { display: inline-block; background: #ff6b35; color: white; padding: 5px 10px; border-radius: 20px; margin: 10px 0; font-weight: bold; }
</style>
<?php });

// Inline JS
add_action('wp_footer', function() { ?>
<script>
jQuery(document).ready(function($) {
    $('.coupon-btn').on('click', function() {
        $(this).text('Redirecting...');
    });
});
</script>
<?php });