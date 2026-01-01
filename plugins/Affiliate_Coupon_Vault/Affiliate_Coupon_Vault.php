/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Manage and display affiliate coupons with tracking to monetize your WordPress site.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_shortcode('acv_coupons', array($this, 'coupons_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_acv_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_acv_delete_coupon', array($this, 'ajax_delete_coupon'));
    }

    public function activate() {
        $this->create_table();
    }

    public function deactivate() {
        // Cleanup optional
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
            active tinyint(1) DEFAULT 1,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'acv-coupons',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        wp_register_style('acv-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0');
        wp_enqueue_style('acv-admin');
    }

    public function admin_page() {
        if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
            $this->edit_coupon_page();
        } else {
            $this->list_coupons_page();
        }
    }

    private function list_coupons_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault</h1>
            <p><a href="<?php echo admin_url('options-general.php?page=acv-coupons&action=add'); ?>" class="button button-primary">Add New Coupon</a></p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Code</th>
                        <th>Affiliate URL</th>
                        <th>Clicks</th>
                        <th>Expiry</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><?php echo esc_html($coupon->title); ?></td>
                        <td><?php echo esc_html($coupon->code); ?></td>
                        <td><?php echo esc_html(substr($coupon->affiliate_url, 0, 50)) . '...'; ?></td>
                        <td><?php echo $coupon->clicks; ?></td>
                        <td><?php echo $coupon->expiry_date ? date('Y-m-d', strtotime($coupon->expiry_date)) : 'No expiry'; ?></td>
                        <td>
                            <a href="<?php echo admin_url('options-general.php?page=acv-coupons&action=edit&id=' . $coupon->id); ?>">Edit</a> |
                            <a href="#" class="acv-delete" data-id="<?php echo $coupon->id; ?>">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.acv-delete').click(function(e) {
                e.preventDefault();
                if (confirm('Delete this coupon?')) {
                    $.post(ajaxurl, {action: 'acv_delete_coupon', id: $(this).data('id')}, function() {
                        location.reload();
                    });
                }
            });
        });
        </script>
        <?php
    }

    private function edit_coupon_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $coupon = null;
        $action = 'add';
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($id > 0) {
            $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
            $action = 'edit';
        }

        if (isset($_POST['submit'])) {
            $title = sanitize_text_field($_POST['title']);
            $code = sanitize_text_field($_POST['code']);
            $affiliate_url = esc_url_raw($_POST['affiliate_url']);
            $description = sanitize_textarea_field($_POST['description']);
            $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null;

            $data = array(
                'title' => $title,
                'code' => $code,
                'affiliate_url' => $affiliate_url,
                'description' => $description,
                'expiry_date' => $expiry_date,
                'active' => isset($_POST['active']) ? 1 : 0
            );

            if ($action == 'edit') {
                $wpdb->update($table_name, $data, array('id' => $id));
            } else {
                $wpdb->insert($table_name, $data);
            }
            echo '<div class="notice notice-success"><p>Coupon saved!</p></div>';
            wp_redirect(admin_url('options-general.php?page=acv-coupons'));
            exit;
        }

        ?>
        <div class="wrap">
            <h1><?php echo $action == 'edit' ? 'Edit' : 'Add New'; ?> Coupon</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Title</th>
                        <td><input type="text" name="title" value="<?php echo $coupon ? esc_attr($coupon->title) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="code" value="<?php echo $coupon ? esc_attr($coupon->code) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th>Affiliate URL</th>
                        <td><input type="url" name="affiliate_url" value="<?php echo $coupon ? esc_attr($coupon->affiliate_url) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="description" rows="4" class="large-text"><?php echo $coupon ? esc_textarea($coupon->description) : ''; ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Expiry Date</th>
                        <td><input type="datetime-local" name="expiry_date" value="<?php echo $coupon && $coupon->expiry_date ? date('Y-m-d\TH:i', strtotime($coupon->expiry_date)) : ''; ?>"></td>
                    </tr>
                    <tr>
                        <th>Active</th>
                        <td><input type="checkbox" name="active" <?php checked($coupon ? $coupon->active : 1); ?>></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function ajax_save_coupon() {
        // Simplified, use admin for full CRUD
        wp_die();
    }

    public function ajax_delete_coupon() {
        if (!current_user_can('manage_options')) wp_die();
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $id = intval($_POST['id']);
        $wpdb->delete($table_name, array('id' => $id));
        wp_send_json_success();
    }

    public function coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
        ), $atts);

        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $coupons = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE active = 1 AND (expiry_date > NOW() OR expiry_date IS NULL) ORDER BY created_at DESC LIMIT %d",
            $atts['limit']
        ));

        if (empty($coupons)) {
            return '<p>No active coupons available.</p>';
        }

        ob_start();
        echo '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $click_url = add_query_arg('acv_click', $coupon->id, home_url('/'));
            echo '<div class="acv-coupon">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            echo '<p><strong>Code:</strong> <code>' . esc_html($coupon->code) . '</code></p>';
            if ($coupon->description) {
                echo '<p>' . esc_html($coupon->description) . '</p>';
            }
            echo '<a href="' . esc_url($click_url) . '" class="button acv-button" data-affurl="' . esc_attr($coupon->affiliate_url) . '">Get Deal (Tracked)</a>';
            if ($coupon->expiry_date) {
                echo '<p><small>Expires: ' . date('M j, Y', strtotime($coupon->expiry_date)) . '</small></p>';
            }
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', 'jQuery(document).ready(function($) {
            $(".acv-button").click(function(e) {
                e.preventDefault();
                var affUrl = $(this).data("affurl");
                window.open(affUrl, "_blank");
                // Optional: Track click via AJAX
                $.post("' . admin_url('admin-ajax.php') . '", {action: "acv_track_click", id: $(this).closest(".acv-coupon").find("a").data("id")});
            });
        });');
        wp_register_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
        wp_enqueue_style('acv-style');
    }
}

// Track clicks
add_action('init', function() {
    if (isset($_GET['acv_click'])) {
        $id = intval($_GET['acv_click']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'acv_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM $table_name WHERE id = %d AND active = 1", $id));
        if ($coupon) {
            $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $id));
            wp_redirect($coupon->affiliate_url);
            exit;
        }
    }
});

AffiliateCouponVault::get_instance();

// Premium upsell notice
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !defined('ACV_PREMIUM')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock unlimited coupons, analytics dashboard, and custom branding with <a href="https://example.com/premium" target="_blank">Premium version</a> for $49/year!</p></div>';
    }
});