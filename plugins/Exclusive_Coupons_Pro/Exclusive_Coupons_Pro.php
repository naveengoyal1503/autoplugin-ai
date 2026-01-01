/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Exclusive Coupons Pro
 * Plugin URI: https://example.com/exclusive-coupons-pro
 * Description: Create, manage, and display exclusive coupons to boost affiliate sales and site revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ecp_db_version') != '1.0') {
            $this->create_table();
        }
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'exclusive_coupons';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            discount varchar(50) NOT NULL,
            affiliate_url text,
            brand varchar(255),
            expiry date,
            uses_limit int DEFAULT 0,
            uses_count int DEFAULT 0,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option('ecp_db_version', '1.0');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ecp-styles', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function admin_menu() {
        add_options_page('Exclusive Coupons', 'Coupons', 'manage_options', 'exclusive-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_coupon($_POST);
        }
        $coupons = $this->get_coupons();
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    private function save_coupon($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'exclusive_coupons';
        $wpdb->insert($table, array(
            'title' => sanitize_text_field($data['title']),
            'code' => strtoupper(sanitize_text_field($data['code'])),
            'discount' => sanitize_text_field($data['discount']),
            'affiliate_url' => esc_url_raw($data['affiliate_url']),
            'brand' => sanitize_text_field($data['brand']),
            'expiry' => sanitize_text_field($data['expiry']),
            'uses_limit' => intval($data['uses_limit'])
        ));
    }

    private function get_coupons($id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'exclusive_coupons';
        if ($id) {
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
        }
        return $wpdb->get_results("SELECT * FROM $table WHERE active = 1 AND (expiry IS NULL OR expiry >= CURDATE()) AND (uses_limit = 0 OR uses_count < uses_limit)");
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        $coupon = $this->get_coupons($atts['id']);
        if (!$coupon) return '';

        // Track use
        global $wpdb;
        $table = $wpdb->prefix . 'exclusive_coupons';
        $wpdb->query($wpdb->prepare("UPDATE $table SET uses_count = uses_count + 1 WHERE id = %d", $coupon->id));

        ob_start();
        ?>
        <div class="ecp-coupon" style="border: 2px dashed #007cba; padding: 20px; text-align: center; background: #f9f9f9;">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <div style="font-size: 2em; color: #007cba; margin: 10px 0;"><?php echo esc_html($coupon->code); ?></div>
            <p><strong><?php echo esc_html($coupon->discount); ?> OFF</strong> from <em><?php echo esc_html($coupon->brand); ?></em></p>
            <?php if ($coupon->affiliate_url): ?>
            <a href="<?php echo esc_url($coupon->affiliate_url); ?>" target="_blank" class="button" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Get Deal Now</a>
            <?php endif; ?>
            <?php if ($coupon->uses_limit): ?>
            <small>(<?php echo $coupon->uses_count; ?>/<?php echo $coupon->uses_limit; ?> uses left)</small>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        $this->create_table();
    }
}

new ExclusiveCouponsPro();

// Premium teaser
function ecp_premium_teaser() {
    if (!get_option('ecp_pro_activated')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Exclusive Coupons Pro Premium</strong> for unlimited coupons, analytics, auto-expiry, and affiliate tracking!</p></div>';
    }
}
add_action('admin_notices', 'ecp_premium_teaser');

// Minimal style.css content
$style_content = ".ecp-coupon { max-width: 400px; margin: 20px auto; border-radius: 10px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }";
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $style_content);

// Placeholder admin-page.php
echo '<div class="wrap"><h1>Manage Exclusive Coupons</h1><form method="post"><table class="form-table">';
echo '<tr><th>Title</th><td><input type="text" name="title" required></td></tr>'; 
echo '<tr><th>Code</th><td><input type="text" name="code" required></td></tr>';
echo '<tr><th>Discount</th><td><input type="text" name="discount" placeholder="50% OFF" required></td></tr>';
echo '<tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:100%"></td></tr>';
echo '<tr><th>Brand</th><td><input type="text" name="brand"></td></tr>';
echo '<tr><th>Expiry</th><td><input type="date" name="expiry"></td></tr>';
echo '<tr><th>Uses Limit</th><td><input type="number" name="uses_limit" min="0"></td></tr>';
echo '</table><p><input type="submit" name="submit" class="button-primary" value="Add Coupon"></p></form>';

$coupons = $GLOBALS['ExclusiveCouponsPro']->get_coupons(); // Simplified
echo '<h2>Active Coupons</h2><ul>';
foreach ($coupons as $c) {
    echo '<li>' . esc_html($c->title) . ' - ' . esc_html($c->code) . '</li>';
}
echo '</ul></div>';