/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Manage affiliate coupons, custom promo codes, and track earnings with shortcodes and dashboards.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

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
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        add_shortcode('acv_dashboard', array($this, 'dashboard_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-admin', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['add_coupon'])) {
            $this->add_coupon($_POST);
        }
        $coupons = $this->get_coupons();
        include plugin_dir_path(__FILE__) . 'admin-view.php';
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text NOT NULL,
            description text,
            usage_count int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function add_coupon($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $wpdb->insert($table_name, array(
            'title' => sanitize_text_field($data['title']),
            'code' => sanitize_text_field($data['code']),
            'affiliate_url' => esc_url_raw($data['affiliate_url']),
            'description' => sanitize_textarea_field($data['description'])
        ));
    }

    private function get_coupons() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . 'acv_coupons');
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts);
        $coupons = $this->get_coupons();
        if (empty($coupons)) return '<p>No coupons available yet.</p>';

        $output = '<div class="acv-coupons">';
        foreach (array_slice($coupons, 0, $atts['limit']) as $coupon) {
            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . esc_html($coupon->title) . '</h3>';
            $output .= '<p><strong>Code:</strong> ' . esc_html($coupon->code) . '</p>';
            $output .= '<p>' . esc_html($coupon->description) . '</p>';
            $output .= '<a href="' . esc_url($coupon->affiliate_url) . '" target="_blank" class="acv-button">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    public function dashboard_shortcode($atts) {
        $coupons = $this->get_coupons();
        $total_usage = array_sum(array_column($coupons, 'usage_count'));
        return '<div class="acv-dashboard"><h3>Total Coupon Uses: ' . $total_usage . '</h3></div>';
    }

    public function activate() {
        $this->create_table();
    }
}

AffiliateCouponVault::get_instance();

// Track clicks
function acv_track_click() {
    if (isset($_GET['acv_click'])) {
        global $wpdb;
        $id = intval($_GET['acv_click']);
        $wpdb->query($wpdb->prepare("UPDATE " . $wpdb->prefix . "acv_coupons SET usage_count = usage_count + 1 WHERE id = %d", $id));
    }
}
add_action('init', 'acv_track_click');

// Sample CSS and JS would be in separate files, but for single-file, inline them

function acv_inline_assets() {
    ?>
    <style>
    .acv-coupons { display: grid; gap: 20px; }
    .acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; }
    .acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
    .acv-dashboard { background: #f9f9f9; padding: 20px; text-align: center; }
    </style>
    <script>jQuery(document).ready(function($) { $('.acv-button').click(function() { $(this).text('Redeemed!'); }); });</script>
    <?php
}
add_action('wp_head', 'acv_inline_assets');

// Admin view template
function acv_admin_template() { ?>
<div class="wrap">
    <h1>Affiliate Coupon Vault</h1>
    <form method="post">
        <table class="form-table">
            <tr><th>Title</th><td><input type="text" name="title" required /></td></tr>
            <tr><th>Code</th><td><input type="text" name="code" required /></td></tr>
            <tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:100%;" required /></td></tr>
            <tr><th>Description</th><td><textarea name="description"></textarea></td></tr>
        </table>
        <p><input type="submit" name="add_coupon" value="Add Coupon" class="button-primary" /></p>
    </form>
    <h2>Existing Coupons</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>URL</th><th>Uses</th></tr></thead>
        <tbody>
        <?php foreach ($GLOBALS['acv_coupons'] as $c): ?>
        <tr><td><?php echo $c->id; ?></td><td><?php echo esc_html($c->title); ?></td><td><?php echo esc_html($c->code); ?></td><td><a href="<?php echo esc_url($c->affiliate_url); ?>">View</a></td><td><?php echo $c->usage_count; ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php }
// Note: In full admin_page, set $GLOBALS['acv_coupons'] = $this->get_coupons(); and echo admin view
?>