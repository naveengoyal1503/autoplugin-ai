/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Generate exclusive affiliate coupons with tracking and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: exclusive-coupons-pro
 */

if (!defined('ABSPATH')) exit;

class ExclusiveCouponsPro {
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
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_shortcode('ecp_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('exclusive-coupons-pro', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            $this->create_table();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ecp-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ecp-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.css', array(), '1.0.0');
    }

    public function admin_scripts($hook) {
        if (strpos($hook, 'exclusive-coupons') !== false) {
            wp_enqueue_script('ecp-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('ecp-admin', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.0.0');
        }
    }

    public function admin_menu() {
        add_menu_page(
            'Exclusive Coupons Pro',
            'Coupons Pro',
            'manage_options',
            'exclusive-coupons-pro',
            array($this, 'admin_page'),
            'dashicons-tickets-alt',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['ecp_save_coupon'])) {
            $this->save_coupon($_POST);
        }
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    private function save_coupon($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ecp_coupons';
        $wpdb->insert(
            $table,
            array(
                'code' => sanitize_text_field($data['code']),
                'description' => sanitize_textarea_field($data['description']),
                'affiliate_link' => esc_url_raw($data['affiliate_link']),
                'expiry_date' => sanitize_text_field($data['expiry_date']),
                'uses_left' => intval($data['uses_left']),
                'created_at' => current_time('mysql')
            )
        );
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $table = $wpdb->prefix . 'ecp_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $atts['id']));
        if (!$coupon) return '';

        $expired = strtotime($coupon->expiry_date) < time();
        $used_up = $coupon->uses_left <= 0;

        ob_start();
        ?>
        <div class="ecp-coupon <?php echo $expired || $used_up ? 'expired' : ''; ?>" data-id="<?php echo $coupon->id; ?>">
            <?php if ($expired || $used_up): ?>
                <p class="ecp-status">Coupon expired or used up!</p>
            <?php else: ?>
                <h3><?php echo esc_html($coupon->code); ?></h3>
                <p><?php echo esc_html($coupon->description); ?></p>
                <a href="<?php echo esc_url($coupon->affiliate_link . $coupon->code); ?>" class="ecp-button" target="_blank">Redeem Now</a>
                <p>Uses left: <span class="ecp-uses"><?php echo $coupon->uses_left; ?></span></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'ecp_coupons';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description text,
            affiliate_link varchar(500) NOT NULL,
            expiry_date datetime NOT NULL,
            uses_left int DEFAULT 999,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY code (code)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

ExclusiveCouponsPro::get_instance();

// Pro upgrade nag
function ecp_pro_nag() {
    if (!get_option('ecp_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Exclusive Coupons Pro:</strong> Upgrade to Pro for unlimited coupons, analytics & tracking! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'ecp_pro_nag');

// AJAX for use tracking
function ecp_track_use() {
    if (!wp_verify_nonce($_POST['nonce'], 'ecp_nonce')) wp_die();
    global $wpdb;
    $table = $wpdb->prefix . 'ecp_coupons';
    $id = intval($_POST['id']);
    $wpdb->query($wpdb->prepare("UPDATE $table SET uses_left = uses_left - 1 WHERE id = %d AND uses_left > 0", $id));
    wp_die();
}
add_action('wp_ajax_ecp_track_use', 'ecp_track_use');
add_action('wp_ajax_nopriv_ecp_track_use', 'ecp_track_use');

// Frontend JS placeholder
/* assets/frontend.js */
/* jQuery(document).ready(function($) {
    $('.ecp-button').click(function(e) {
        e.preventDefault();
        var $coupon = $(this).closest('.ecp-coupon');
        $.post(ajaxurl, {
            action: 'ecp_track_use',
            id: $coupon.data('id'),
            nonce: '<?php echo wp_create_nonce('ecp_nonce'); ?>'
        }, function() {
            location.href = $(this).attr('href');
        });
    });
}); */

// Minimal CSS placeholders
/* assets/frontend.css */
/* .ecp-coupon { border: 2px solid #007cba; padding: 20px; border-radius: 5px; }
.ecp-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; }
.ecp-coupon.expired { opacity: 0.5; } */

// Admin page template: admin-page.php (inline for single file)
/* <?php
$wpdb->prefix . 'ecp_coupons';
$coupons = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ecp_coupons ORDER BY created_at DESC");
?>
<div class="wrap">
    <h1>Exclusive Coupons Pro</h1>
    <form method="post">
        <table class="form-table">
            <tr><th>Code</th><td><input type="text" name="code" required placeholder="SAVE20"></td></tr>
            <tr><th>Description</th><td><textarea name="description"></textarea></td></tr>
            <tr><th>Affiliate Link</th><td><input type="url" name="affiliate_link" required placeholder="https://affiliate.com/?coupon="></td></tr>
            <tr><th>Expiry Date</th><td><input type="datetime-local" name="expiry_date" required></td></tr>
            <tr><th>Uses Left</th><td><input type="number" name="uses_left" value="999"></td></tr>
        </table>
        <p><input type="submit" name="ecp_save_coupon" class="button-primary" value="Add Coupon"></p>
    </form>
    <h2>Your Coupons</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>ID</th><th>Code</th><th>Link</th><th>Expiry</th><th>Uses</th><th>Shortcode</th></tr></thead>
        <tbody>
        <?php foreach($coupons as $c): ?>
        <tr>
            <td><?php echo $c->id; ?></td>
            <td><?php echo esc_html($c->code); ?></td>
            <td><a href="<?php echo esc_url($c->affiliate_link); ?>" target="_blank">View</a></td>
            <td><?php echo $c->expiry_date; ?></td>
            <td><?php echo $c->uses_left; ?></td>
            <td><code>[ecp_coupon id="<?php echo $c->id; ?>"]</code></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p><em>Upgrade to Pro for analytics dashboard and unlimited features!</em></p>
</div> */