/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Custom_Affiliate_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: Custom Affiliate Coupons Pro
 * Plugin URI: https://example.com/custom-affiliate-coupons
 * Description: Generate and manage exclusive custom coupons for affiliate products, boosting conversions with personalized promo codes and tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: custom-affiliate-coupons
 */

if (!defined('ABSPATH')) {
    exit;
}

class CustomAffiliateCouponsPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('cac_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function init() {
        $this->create_table();
        register_post_type('cac_coupon', array(
            'labels' => array('name' => 'Coupons', 'singular_name' => 'Coupon'),
            'public' => true,
            'show_ui' => true,
            'supports' => array('title', 'editor'),
        ));
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cac_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            expires datetime DEFAULT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_menu_page('Coupons', 'Coupons', 'manage_options', 'cac-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['add_coupon'])) {
            $this->add_coupon($_POST);
        }
        echo '<div class="wrap"><h1>Manage Coupons</h1><form method="post">';
        echo '<table class="form-table"><tr><th>Code</th><td><input type="text" name="code" required /></td></tr>';
        echo '<tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width:400px;" required /></td></tr>';
        echo '<tr><th>Max Uses</th><td><input type="number" name="max_uses" /></td></tr>';
        echo '<tr><th>Expires</th><td><input type="datetime-local" name="expires" /></td></tr>';
        echo '<tr><td colspan="2"><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon" /></td></tr></table></form>';
        $this->list_coupons();
        echo '</div>';
    }

    private function add_coupon($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cac_coupons';
        $wpdb->insert($table_name, array(
            'code' => sanitize_text_field($data['code']),
            'affiliate_url' => esc_url_raw($data['affiliate_url']),
            'max_uses' => intval($data['max_uses']),
            'expires' => !empty($data['expires']) ? $data['expires'] : null,
        ));
    }

    private function list_coupons() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cac_coupons';
        $coupons = $wpdb->get_results("SELECT * FROM $table_name");
        echo '<table class="wp-list-table widefat fixed striped"><thead><tr><th>Code</th><th>URL</th><th>Uses</th><th>Max Uses</th><th>Expires</th></tr></thead><tbody>';
        foreach ($coupons as $coupon) {
            echo '<tr><td>' . esc_html($coupon->code) . '</td><td>' . esc_url($coupon->affiliate_url) . '</td><td>' . $coupon->uses . '</td><td>' . $coupon->max_uses . '</td><td>' . ($coupon->expires ? $coupon->expires : 'Never') . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        if (empty($atts['code'])) return '';
        return $this->render_coupon($atts['code']);
    }

    private function render_coupon($code) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cac_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE code = %s", $code));
        if (!$coupon) return '<p>Coupon not found.</p>';

        $expired = $coupon->expires && strtotime($coupon->expires) < current_time('timestamp');
        $maxed = $coupon->max_uses > 0 && $coupon->uses >= $coupon->max_uses;
        if ($expired || $maxed) {
            return '<p style="background:#fee;color:#c00;padding:10px;border:1px solid #fcc;">Coupon expired or max uses reached.</p>';
        }

        $nonce = wp_create_nonce('cac_use_' . $coupon->id);
        ob_start();
        ?>
        <div id="cac-coupon-<?php echo $coupon->id; ?>" style="background:#e7f3ff;padding:20px;border:2px dashed #007cba;text-align:center;">
            <h3>Exclusive Coupon: <strong><?php echo esc_html($coupon->code); ?></strong></h3>
            <p>Click to redeem and track your exclusive deal!</p>
            <a href="<?php echo esc_url($coupon->affiliate_url); ?>" class="button button-large" onclick="cacTrack(<?php echo $coupon->id; ?>, '<?php echo $nonce; ?>');" style="background:#007cba;color:#fff;padding:12px 24px;text-decoration:none;font-size:16px;">Redeem Now</a>
            <p style="font-size:12px;margin-top:10px;">Uses left: <?php echo $coupon->max_uses > 0 ? $coupon->max_uses - $coupon->uses : 'Unlimited'; ?></p>
        </div>
        <script>
        function cacTrack(id, nonce) {
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=cac_track_use&id=' + id + '&nonce=' + nonce
            });
        }
        </script>
        <?php
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET uses = uses + 1 WHERE id = %d", $coupon->id));
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('cac-script', plugin_dir_url(__FILE__) . 'cac-script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook != 'toplevel_page_cac-coupons') return;
        wp_enqueue_script('cac-admin', plugin_dir_url(__FILE__) . 'cac-admin.js', array('jquery'), '1.0.0', true);
    }
}

new CustomAffiliateCouponsPro();

add_action('wp_ajax_cac_track_use', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'cac_use_' . intval($_POST['id']))) {
        wp_die('Unauthorized');
    }
    // Tracking logic here (free version logs, pro adds analytics)
    wp_die();
});

// Pro upsell notice
function cac_pro_notice() {
    if (!get_option('cac_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Custom Affiliate Coupons Pro</strong> for unlimited coupons, analytics, and custom branding! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a> | <a href="?cac_dismiss=1">Dismiss</a></p></div>';
    }
}
add_action('admin_notices', 'cac_pro_notice');

if (isset($_GET['cac_dismiss'])) {
    update_option('cac_pro_dismissed', 1);
}