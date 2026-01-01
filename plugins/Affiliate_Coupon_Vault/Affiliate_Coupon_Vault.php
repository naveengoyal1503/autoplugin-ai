/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Generate and manage exclusive affiliate coupons to boost your commissions. Shortcode [affiliate_coupon_vault] displays a customizable coupon section.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('affiliate_coupon_vault', array($this, 'shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function activate() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url text NOT NULL,
            discount varchar(50) DEFAULT '',
            expiry date DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 5,
            'category' => ''
        ), $atts);

        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';
        $where = 'WHERE active = 1';
        if (!empty($atts['category'])) {
            $where .= $wpdb->prepare(' AND category = %s', $atts['category']);
        }
        $coupons = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $where ORDER BY created_at DESC LIMIT %d", $atts['limit']));

        if (empty($coupons)) {
            return '<p>No active coupons available.</p>';
        }

        ob_start();
        echo '<div class="affiliate-coupon-vault">';
        foreach ($coupons as $coupon) {
            $expiry = $coupon->expiry ? 'Expires: ' . date('M j, Y', strtotime($coupon->expiry)) : 'No expiry';
            echo '<div class="coupon-item">';
            echo '<h3>' . esc_html($coupon->title) . '</h3>';
            echo '<div class="coupon-code">' . esc_html($coupon->code) . '</div>';
            echo '<p>Discount: ' . esc_html($coupon->discount) . '</p>';
            echo '<p>' . esc_html($expiry) . '</p>';
            echo '<a href="' . esc_url($coupon->affiliate_url) . '" class="coupon-btn" target="_blank">Shop Now & Save</a>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_coupons';

        if (isset($_POST['add_coupon']) && check_admin_referer('add_coupon_nonce')) {
            $wpdb->insert($table_name, array(
                'title' => sanitize_text_field($_POST['title']),
                'code' => sanitize_text_field($_POST['code']),
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'discount' => sanitize_text_field($_POST['discount']),
                'expiry' => sanitize_text_field($_POST['expiry']),
                'active' => isset($_POST['active']) ? 1 : 0
            ));
        }

        $coupons = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }
}

new AffiliateCouponVault();

// Inline CSS for simplicity
add_action('wp_head', 'affiliate_coupon_vault_styles');
function affiliate_coupon_vault_styles() {
    echo '<style>
    .affiliate-coupon-vault { max-width: 600px; margin: 20px 0; }
    .coupon-item { background: #f9f9f9; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    .coupon-code { font-size: 24px; font-weight: bold; color: #e74c3c; background: white; padding: 10px; margin: 10px 0; text-align: center; letter-spacing: 2px; }
    .coupon-btn { display: inline-block; background: #27ae60; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
    .coupon-btn:hover { background: #219a52; }
    </style>';
}

// Premium upsell notice
add_action('admin_notices', 'acv_premium_notice');
function acv_premium_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock premium features like auto-coupon generation, analytics, and unlimited coupons. <a href="https://example.com/premium" target="_blank">Upgrade now</a> for $49/year!</p></div>';
    }
}

// Admin page template (embedded for single file)
function acv_admin_template() { ob_start(); ?>
<div class="wrap">
    <h1>Affiliate Coupon Vault</h1>
    <form method="post">
        <?php wp_nonce_field('add_coupon_nonce'); ?>
        <table class="form-table">
            <tr><th>Title</th><td><input type="text" name="title" required /></td></tr>
            <tr><th>Coupon Code</th><td><input type="text" name="code" required /></td></tr>
            <tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width: 300px;" required /></td></tr>
            <tr><th>Discount</th><td><input type="text" name="discount" placeholder="e.g., 20% OFF" /></td></tr>
            <tr><th>Expiry Date</th><td><input type="date" name="expiry" /></td></tr>
            <tr><th>Active</th><td><input type="checkbox" name="active" value="1" checked /></td></tr>
        </table>
        <p><input type="submit" name="add_coupon" class="button-primary" value="Add Coupon" /></p>
    </form>
    <h2>Active Coupons</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>Discount</th><th>URL</th><th>Active</th></tr></thead>
        <tbody><?php foreach ($GLOBALS['coupons'] ?? [] as $c): ?><tr>
            <td><?php echo $c->id; ?></td>
            <td><?php echo esc_html($c->title); ?></td>
            <td><?php echo esc_html($c->code); ?></td>
            <td><?php echo esc_html($c->discount); ?></td>
            <td><a href="<?php echo esc_url($c->affiliate_url); ?>">View</a></td>
            <td><?php echo $c->active ? 'Yes' : 'No'; ?></td>
        </tr><?php endforeach; ?></tbody>
    </table>
    <p>Use shortcode <code>[affiliate_coupon_vault]</code> to display coupons on any page/post.</p>
</div>
<?php return ob_get_clean(); }

// Fix for admin page include
add_action('admin_init', function() { if (isset($_GET['page']) && $_GET['page'] === 'affiliate-coupon-vault') { add_action('admin_footer', 'acv_admin_template'); } }); ?>