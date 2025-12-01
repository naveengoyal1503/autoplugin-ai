/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Manager
 * Description: Create and display affiliate coupons with tracking and optimization.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCouponManager {
    private static $instance = null;
    private $db_version = '1.0';

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->setup_hooks();
        }
        return self::$instance;
    }

    private function setup_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_save_coupon', array($this, 'handle_save_coupon'));

        add_shortcode('affiliate_coupons', array($this, 'render_coupons_shortcode'));
        add_action('init', array($this, 'track_coupon_click'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sacm_coupons';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            code varchar(50) NOT NULL,
            description text NOT NULL,
            affiliate_url text NOT NULL,
            clicks bigint(20) NOT NULL DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('sacm_db_version', $this->db_version);
    }

    public function deactivate() {
        // Optional: keep data on deactivate
    }

    public function add_admin_menu() {
        add_menu_page(
            'Affiliate Coupon Manager',
            'Coupon Manager',
            'manage_options',
            'sacm-coupon-manager',
            array($this, 'admin_page'),
            'dashicons-tickets',
            60
        );
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'sacm_coupons';

        if (isset($_GET['edit'])) {
            $coupon_id = intval($_GET['edit']);
            $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $coupon_id));
        } else {
            $coupon = null;
        }

        $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created DESC");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Manager</h1>
            <h2><?php echo $coupon ? 'Edit Coupon' : 'Add New Coupon'; ?></h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_coupon" />
                <input type="hidden" name="id" value="<?php echo esc_attr($coupon ? $coupon->id : ''); ?>" />
                <?php wp_nonce_field('sacm_save_coupon'); ?>
                <table class="form-table">
                    <tr><th><label for="code">Coupon Code</label></th>
                        <td><input type="text" name="code" id="code" required value="<?php echo esc_attr($coupon ? $coupon->code : ''); ?>" maxlength="50" class="regular-text" /></td></tr>
                    <tr><th><label for="description">Description</label></th>
                        <td><textarea name="description" id="description" rows="3" class="large-text"><?php echo esc_textarea($coupon ? $coupon->description : ''); ?></textarea></td></tr>
                    <tr><th><label for="affiliate_url">Affiliate URL</label></th>
                        <td><input type="url" name="affiliate_url" id="affiliate_url" required value="<?php echo esc_url($coupon ? $coupon->affiliate_url : ''); ?>" class="regular-text" /></td></tr>
                </table>
                <?php submit_button($coupon ? 'Update Coupon' : 'Add Coupon'); ?>
            </form>
            <h2>Existing Coupons</h2>
            <table class="widefat fixed striped">
                <thead><tr><th>Code</th><th>Description</th><th>Clicks</th><th>Created</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($coupons): foreach ($coupons as $c): ?>
                    <tr>
                        <td><?php echo esc_html($c->code); ?></td>
                        <td><?php echo esc_html(wp_trim_words($c->description, 10)); ?></td>
                        <td><?php echo intval($c->clicks); ?></td>
                        <td><?php echo esc_html($c->created); ?></td>
                        <td><a href="<?php echo admin_url('admin.php?page=sacm-coupon-manager&edit=' . $c->id); ?>">Edit</a></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5">No coupons added yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_save_coupon() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('sacm_save_coupon');

        global $wpdb;
        $table_name = $wpdb->prefix . 'sacm_coupons';

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $code = sanitize_text_field($_POST['code']);
        $description = sanitize_textarea_field($_POST['description']);
        $affiliate_url = esc_url_raw($_POST['affiliate_url']);

        if ($id > 0) {
            $wpdb->update(
                $table_name,
                array('code' => $code, 'description' => $description, 'affiliate_url' => $affiliate_url),
                array('id' => $id),
                array('%s', '%s', '%s'),
                array('%d')
            );
        } else {
            $wpdb->insert(
                $table_name,
                array('code' => $code, 'description' => $description, 'affiliate_url' => $affiliate_url),
                array('%s', '%s', '%s')
            );
        }

        wp_redirect(admin_url('admin.php?page=sacm-coupon-manager'));
        exit;
    }

    public function render_coupons_shortcode($atts) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sacm_coupons';
        $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created DESC");
        if (!$coupons) return '<p>No coupons available.</p>';

        $html = '<div class="sacm-coupon-list" style="display:flex;flex-wrap:wrap;gap:15px;">';
        foreach ($coupons as $coupon) {
            $link = add_query_arg(array('sacm_coupon' => $coupon->id), site_url());
            $html .= '<div class="sacm-coupon" style="border:1px solid #ccc;padding:15px;width:200px;border-radius:5px;">
                <strong style="font-size:1.2em;">' . esc_html($coupon->code) . '</strong><br />
                <p>' . esc_html($coupon->description) . '</p>
                <a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener">Use Coupon</a>
            </div>';
        }
        $html .= '</div>';
        return $html;
    }

    public function track_coupon_click() {
        if (!isset($_GET['sacm_coupon'])) return;
        global $wpdb;
        $id = intval($_GET['sacm_coupon']);
        $table_name = $wpdb->prefix . 'sacm_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));

        if (!$coupon) return;

        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $id));

        wp_redirect($coupon->affiliate_url);
        exit;
    }
}

SmartAffiliateCouponManager::instance();