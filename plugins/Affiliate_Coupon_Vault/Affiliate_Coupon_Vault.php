/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Create and display exclusive affiliate coupons to boost your commissions. Track clicks and manage expirations effortlessly.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_acv_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_acv_delete_coupon', array($this, 'ajax_delete_coupon'));
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
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
            expiry_date datetime DEFAULT NULL,
            clicks int DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($){ $(".acv-copy").click(function(){ var code = $(this).data("code"); navigator.clipboard.writeText(code).then(function(){ $(this).text("Copied!"); setTimeout(function(){ $(this).text("Copy"); }, 2000); }.bind(this)); }); });');
        wp_enqueue_style('acv-style', plugins_url('style.css', __FILE__), array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_coupon($_POST);
        }
        $coupons = $this->get_coupons();
        include 'admin-page.php';
    }

    private function get_coupons() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    }

    private function save_coupon($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $wpdb->insert(
            $table_name,
            array(
                'title' => sanitize_text_field($data['title']),
                'code' => sanitize_text_field($data['code']),
                'affiliate_url' => esc_url_raw($data['affiliate_url']),
                'description' => sanitize_textarea_field($data['description']),
                'expiry_date' => !empty($data['expiry_date']) ? $data['expiry_date'] : null,
                'is_active' => isset($data['is_active']) ? 1 : 0
            )
        );
    }

    public function ajax_save_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        $this->save_coupon($_POST);
        wp_send_json_success();
    }

    public function ajax_delete_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $wpdb->delete($table_name, array('id' => intval($_POST['id'])));
        wp_send_json_success();
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 10), $atts);
        $coupons = $this->get_active_coupons($atts['limit']);
        ob_start();
        include 'frontend-display.php';
        return ob_get_clean();
    }

    private function get_active_coupons($limit = 10) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE is_active = 1 AND (expiry_date IS NULL OR expiry_date > %s) ORDER BY created_at DESC LIMIT %d", current_time('mysql'), $limit));
    }

    public function track_click($coupon_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $coupon_id));
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['acv_click'])) {
        $coupon_id = intval($_GET['acv_click']);
        AffiliateCouponVault::get_instance()->track_click($coupon_id);
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM " . $wpdb->prefix . "acv_coupons WHERE id = %d", $coupon_id));
        if ($coupon) {
            wp_redirect($coupon->affiliate_url);
            exit;
        }
    }
});

AffiliateCouponVault::get_instance();

// Inline styles
function acv_inline_styles() {
    echo '<style>
    .acv-coupon { border: 1px solid #ddd; padding: 20px; margin: 10px 0; border-radius: 8px; background: #f9f9f9; }
    .acv-code { font-size: 24px; font-weight: bold; color: #e74c3c; cursor: pointer; }
    .acv-btn { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    .expired { opacity: 0.5; }
    </style>';
}
add_action('wp_head', 'acv_inline_styles');

// Admin page template
file_put_contents(plugin_dir_path(__FILE__) . 'admin-page.php', '<?php
if (!defined("ABSPATH")) exit;
?>
<div class="wrap">
    <h1>Affiliate Coupon Vault</h1>
    <form method="post">
        <table class="form-table">
            <tr><th>Title</th><td><input type="text" name="title" required style="width:300px;"></td></tr>
            <tr><th>Coupon Code</th><td><input type="text" name="code" required style="width:300px;"></td></tr>
            <tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" required style="width:500px;"></td></tr>
            <tr><th>Description</th><td><textarea name="description" rows="3" style="width:500px;"></textarea></td></tr>
            <tr><th>Expiry Date</th><td><input type="datetime-local" name="expiry_date"></td></tr>
            <tr><th>Active</th><td><input type="checkbox" name="is_active" checked></td></tr>
        </table>
        <p><input type="submit" name="submit" class="button-primary" value="Add Coupon"></p>
    </form>
    <h2>Your Coupons</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Clicks</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach($coupons as $c): ?>
        <tr>
            <td><?php echo $c->id; ?></td>
            <td><?php echo esc_html($c->title); ?></td>
            <td><?php echo esc_html($c->code); ?></td>
            <td><?php echo $c->clicks; ?></td>
            <td><button class="button" onclick="deleteCoupon(<?php echo $c->id; ?>)" style="background:#e74c3c;color:white;">Delete</button></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <p>Use shortcode: <code>[acv_coupons]</code> or <code>[acv_coupons limit="5"]</code></p>
</div>
<script>
function deleteCoupon(id) {
    if(confirm("Delete?")) {
        jQuery.post(ajaxurl, {action:"acv_delete_coupon", id:id}, function(){ location.reload(); });
    }
}
</script>');

// Frontend display template
file_put_contents(plugin_dir_path(__FILE__) . 'frontend-display.php', '<?php if (!defined("ABSPATH")) exit; ?>
<div class="acv-vault">
    <?php foreach($coupons as $coupon): 
        $expired = $coupon->expiry_date && $coupon->expiry_date < current_time("mysql");
        if($expired) continue;
    ?>
    <div class="acv-coupon <?php echo $expired ? "expired" : ""; ?>">
        <h3><?php echo esc_html($coupon->title); ?></h3>
        <div class="acv-code" data-code="<?php echo esc_attr($coupon->code); ?>">Copy Code: <?php echo esc_html($coupon->code); ?></div>
        <?php if($coupon->description): ?>
        <p><?php echo esc_html($coupon->description); ?></p>
        <?php endif; ?>
        <a href="?acv_click=<?php echo $coupon->id; ?>" class="acv-btn" onclick="trackClick(<?php echo $coupon->id; ?>)" target="_blank">Redeem Deal & Track</a>
        <small>Clicks: <?php echo $coupon->clicks; ?></small>
    </div>
    <?php endforeach; ?>
</div>');