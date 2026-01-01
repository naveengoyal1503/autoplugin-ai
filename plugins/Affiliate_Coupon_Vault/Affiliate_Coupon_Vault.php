/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Generate exclusive affiliate coupons with tracking and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    private static $instance = null;
    public $db_version = '1.0';
    public $table_name;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'aff_coupons';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('aff_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(50) NOT NULL,
            affiliate_url text NOT NULL,
            expiry_date datetime DEFAULT NULL,
            clicks int DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0,
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function init() {
        if (is_admin()) return;
        $this->check_expired_coupons();
    }

    public function admin_menu() {
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'aff-coupon-vault', array($this, 'admin_page'), 'dashicons-tickets-alt');
        add_submenu_page('aff-coupon-vault', 'Dashboard', 'Dashboard', 'manage_options', 'aff-coupon-vault', array($this, 'admin_page'));
        add_submenu_page('aff-coupon-vault', 'Add New', 'Add New', 'manage_options', 'aff-coupon-new', array($this, 'add_new_page'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'aff-coupon') !== false) {
            wp_enqueue_script('jquery');
            wp_add_inline_script('jquery', 'jQuery(document).ready(function($){ $(".delete-coupon").click(function(){ if(confirm("Delete?")) return true; return false; }); });');
        }
    }

    public function admin_page() {
        global $wpdb;
        $coupons = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        include plugin_dir_path(__FILE__) . 'admin-dashboard.php';
    }

    public function add_new_page() {
        if (isset($_POST['submit'])) {
            global $wpdb;
            $code = sanitize_text_field($_POST['code']);
            $title = sanitize_text_field($_POST['title']);
            $url = esc_url_raw($_POST['url']);
            $expiry = !empty($_POST['expiry']) ? sanitize_text_field($_POST['expiry']) : null;
            $wpdb->insert($this->table_name, array(
                'title' => $title,
                'code' => $code,
                'affiliate_url' => $url,
                'expiry_date' => $expiry
            ));
            echo '<div class="notice notice-success"><p>Coupon created!</p></div>';
        }
        include plugin_dir_path(__FILE__) . 'admin-add-new.php';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d AND is_active = 1", $atts['id']));
        if (!$coupon || ( $coupon->expiry_date && $coupon->expiry_date < current_time('mysql') )) return '';

        ob_start();
        ?>
        <div class="aff-coupon-vault" style="border:2px solid #0073aa; padding:20px; background:#f9f9f9; text-align:center; max-width:400px;">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <div style="font-size:24px; color:#0073aa; margin:10px 0;"><?php echo esc_html($coupon->code); ?></div>
            <a href="#" class="acv-use-coupon" data-id="<?php echo $coupon->id; ?>" style="background:#0073aa; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">Use Coupon</a>
        </div>
        <script>
        jQuery('.acv-use-coupon[data-id="<?php echo $coupon->id; ?>"]').click(function(e){
            e.preventDefault();
            jQuery.post(ajaxurl, {action:'acv_track_click', id:'<?php echo $coupon->id; ?>'}, function(){ window.location='<?php echo esc_js($coupon->affiliate_url); ?>'; });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        global $wpdb;
        $id = intval($_POST['id']);
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE id = %d", $id));
        wp_die();
    }

    private function check_expired_coupons() {
        global $wpdb;
        $wpdb->query("UPDATE $this->table_name SET is_active = 0 WHERE expiry_date < NOW() AND is_active = 1");
    }
}

// Pro nag
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id == 'toplevel_page_aff-coupon-vault') {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, revenue tracking, and API! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
});

AffiliateCouponVault::get_instance();

// Admin templates
$admin_dashboard = '<?php foreach($coupons as $c): ?>
<tr>
    <td><?php echo esc_html($c->title); ?></td>
    <td><?php echo esc_html($c->code); ?></td>
    <td><?php echo esc_html($c->clicks); ?></td>
    <td><?php echo $c->revenue; ?></td>
    <td><?php echo $c->is_active ? "Yes" : "No"; ?></td>
    <td><a href="?page=aff-coupon-new&edit=<?php echo $c->id; ?>">Edit</a> | <a href="#" class="delete-coupon" onclick="return deleteCoupon(<?php echo $c->id; ?>);">Delete</a></td>
</tr>
<?php endforeach; ?>';

file_put_contents(plugin_dir_path(__FILE__) . 'admin-dashboard.php', '<div class="wrap"><h1>Coupon Dashboard</h1><table class="wp-list-table widefat fixed striped"><thead><tr><th>Title</th><th>Code</th><th>Clicks</th><th>Revenue</th><th>Active</th><th>Actions</th></tr></thead><tbody>' . $admin_dashboard . '</tbody></table><p><a href="?page=aff-coupon-new" class="button button-primary">Add New Coupon</a></p></div><script>function deleteCoupon(id){jQuery.post(ajaxurl,{action:"delete_coupon",id:id});}</script>');

$admin_add_new = '<div class="wrap"><h1>Add New Coupon</h1><form method="post"><table class="form-table"><tr><th>Title</th><td><input type="text" name="title" required class="regular-text"></td></tr><tr><th>Code</th><td><input type="text" name="code" required placeholder="SAVE20"></td></tr><tr><th>Affiliate URL</th><td><input type="url" name="url" required class="regular-text"></td></tr><tr><th>Expiry (optional)</th><td><input type="datetime-local" name="expiry"></td></tr></table><p><input type="submit" name="submit" class="button-primary" value="Create Coupon"></p></form></div>';

file_put_contents(plugin_dir_path(__FILE__) . 'admin-add-new.php', $admin_add_new);

add_action('wp_ajax_delete_coupon', function() {
    global $wpdb;
    $wpdb->delete($this->table_name, array('id' => intval($_POST['id'])));
    wp_die();
});