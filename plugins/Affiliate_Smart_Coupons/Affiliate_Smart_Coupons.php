/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Smart_Coupons.php
*/
<?php
/**
 * Plugin Name: Affiliate Smart Coupons
 * Description: Automatically aggregates and displays affiliate coupons and deals on your WordPress site to boost affiliate revenue.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateSmartCoupons {

    private $table_name;
    private $version = '1.0';

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'affiliate_coupons';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_add_coupon', array($this, 'handle_add_coupon'));
        add_shortcode('affiliate_coupons', array($this, 'coupons_shortcode'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(250) NOT NULL,
            description text NOT NULL,
            affiliate_link varchar(255) NOT NULL,
            expiry_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Schedule coupon auto-import every 12 hours if not scheduled
        if (!wp_next_scheduled('asc_cron_fetch_coupons')) {
            wp_schedule_event(time(), 'twicedaily', 'asc_cron_fetch_coupons');
        }
    }

    public function deactivate() {
        // Clear scheduled event
        $timestamp = wp_next_scheduled('asc_cron_fetch_coupons');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'asc_cron_fetch_coupons');
        }
    }

    // Admin menu setup
    public function admin_menu() {
        add_menu_page('Affiliate Smart Coupons', 'Affiliate Coupons', 'manage_options', 'affiliate-smart-coupons', array($this, 'admin_page'), 'dashicons-tickets', 65);
    }

    // Admin page HTML
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized user')); 
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Smart Coupons</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="add_coupon">
                <?php wp_nonce_field('asc_add_coupon_check'); ?>
                <table class="form-table">
                    <tr><th><label for="title">Coupon Title</label></th><td><input type="text" name="title" id="title" required class="regular-text"></td></tr>
                    <tr><th><label for="description">Description</label></th><td><textarea name="description" id="description" rows="4" required class="large-text"></textarea></td></tr>
                    <tr><th><label for="affiliate_link">Affiliate Link</label></th><td><input type="url" name="affiliate_link" id="affiliate_link" required class="regular-text"></td></tr>
                    <tr><th><label for="expiry_date">Expiry Date (optional)</label></th><td><input type="date" name="expiry_date" id="expiry_date" class="regular-text"></td></tr>
                </table>
                <?php submit_button('Add Coupon'); ?>
            </form>
            <h2>Existing Coupons</h2>
            <?php $this->list_coupons(); ?>
        </div>
        <?php
    }

    // Handle new coupon submission
    public function handle_add_coupon() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized user');
        check_admin_referer('asc_add_coupon_check');

        if (empty($_POST['title']) || empty($_POST['description']) || empty($_POST['affiliate_link'])) {
            wp_redirect(admin_url('admin.php?page=affiliate-smart-coupons&message=missing_fields'));
            exit;
        }

        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $affiliate_link = esc_url_raw($_POST['affiliate_link']);
        $expiry_date = !empty($_POST['expiry_date']) ? sanitize_text_field($_POST['expiry_date']) . ' 23:59:59' : null;

        global $wpdb;
        $wpdb->insert($this->table_name, array(
            'title' => $title,
            'description' => $description,
            'affiliate_link' => $affiliate_link,
            'expiry_date' => $expiry_date
        ));

        wp_redirect(admin_url('admin.php?page=affiliate-smart-coupons&message=added'));
        exit;
    }

    // List coupons in admin
    private function list_coupons() {
        global $wpdb;
        $coupons = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created_at DESC");

        if (!$coupons) {
            echo '<p>No coupons added yet.</p>';
            return;
        }

        echo '<table class="widefat fixed" cellspacing="0">';
        echo '<thead><tr><th>Title</th><th>Description</th><th>Affiliate Link</th><th>Expiry Date</th></tr></thead><tbody>';

        foreach ($coupons as $coupon) {
            $expiry = $coupon->expiry_date ? esc_html(date('Y-m-d', strtotime($coupon->expiry_date))) : 'No expiry';
            echo '<tr>';
            echo '<td>' . esc_html($coupon->title) . '</td>';
            echo '<td>' . esc_html($coupon->description) . '</td>';
            echo '<td><a href="' . esc_url($coupon->affiliate_link) . '" target="_blank" rel="nofollow">Link</a></td>';
            echo '<td>' . $expiry . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    // Shortcode to display coupons on site
    public function coupons_shortcode($atts) {
        global $wpdb;
        $today = current_time('mysql');

        // Get valid coupons with no expiry or future expiry
        $coupons = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE expiry_date IS NULL OR expiry_date >= %s ORDER BY expiry_date ASC, created_at DESC",
            $today
        ));

        if (!$coupons) return '<p>No coupons available at the moment.</p>';

        ob_start();
        echo '<div class="affiliate-smart-coupons">';
        foreach ($coupons as $coupon) {
            echo '<div class="asc-coupon" style="border:1px solid #ddd;padding:10px;margin:10px 0;">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            echo '<p>' . esc_html($coupon->description) . '</p>';
            echo '<a href="' . esc_url($coupon->affiliate_link) . '" class="asc-btn" target="_blank" rel="nofollow noopener" style="display:inline-block;padding:8px 15px;color:#fff;background:#0073aa;border-radius:3px;text-decoration:none;">Get Deal</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

}
new AffiliateSmartCoupons();
