/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Affiliate_Deals_Manager.php
*/
<?php
/**
 * Plugin Name: Exclusive Affiliate Deals Manager
 * Plugin URI: https://example.com/deals-manager
 * Description: Generate and manage exclusive affiliate coupon codes and deals to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class ExclusiveDealsManager {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('exclusive_deal', array($this, 'deal_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_table();
    }

    public function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_deals';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) NOT NULL,
            affiliate_url varchar(500) DEFAULT '',
            discount text DEFAULT '',
            expiry date DEFAULT '0000-00-00',
            active tinyint(1) DEFAULT 1,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->create_table();
    }

    public function admin_menu() {
        add_menu_page('Exclusive Deals', 'Deals Manager', 'manage_options', 'exclusive-deals', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['add_deal'])) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'exclusive_deals';
            $code = sanitize_text_field($_POST['code']);
            $wpdb->insert($table_name, array(
                'title' => sanitize_text_field($_POST['title']),
                'code' => $code,
                'affiliate_url' => esc_url_raw($_POST['url']),
                'discount' => sanitize_textarea_field($_POST['discount']),
                'expiry' => sanitize_text_field($_POST['expiry'])
            ));
        }
        $this->admin_html();
    }

    private function admin_html() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_deals';
        $deals = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created DESC");
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Deals</h1>
            <form method="post">
                <table class="form-table">
                    <tr><th>Title</th><td><input type="text" name="title" required /></td></tr>
                    <tr><th>Coupon Code</th><td><input type="text" name="code" required /></td></tr>
                    <tr><th>Affiliate URL</th><td><input type="url" name="url" style="width: 400px;" /></td></tr>
                    <tr><th>Discount</th><td><textarea name="discount"></textarea></td></tr>
                    <tr><th>Expiry</th><td><input type="date" name="expiry" /></td></tr>
                </table>
                <p><input type="submit" name="add_deal" class="button-primary" value="Add Deal" /></p>
            </form>
            <h2>Active Deals</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Title</th><th>Code</th><th>URL</th><th>Discount</th><th>Clicks</th><th>Actions</th></tr></thead>
                <tbody>
        <?php foreach ($deals as $deal): ?>
                    <tr>
                        <td><?php echo $deal->id; ?></td>
                        <td><?php echo esc_html($deal->title); ?></td>
                        <td><strong><?php echo esc_html($deal->code); ?></strong></td>
                        <td><?php echo esc_html($deal->affiliate_url); ?></td>
                        <td><?php echo esc_html($deal->discount); ?></td>
                        <td><?php echo $deal->clicks; ?></td>
                        <td><a href="<?php echo admin_url('admin.php?page=exclusive-deals&delete=' . $deal->id); ?>" onclick="return confirm('Delete?')">Delete</a></td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function deal_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'exclusive_deals';
        $deal = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND active = 1", $atts['id']));
        if (!$deal) return '';

        $click_url = add_query_arg('edm_track', $deal->id, $deal->affiliate_url);
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $deal->id));

        ob_start();
        ?>
        <div style="border: 2px solid #007cba; padding: 20px; background: #f9f9f9; border-radius: 8px;">
            <h3><?php echo esc_html($deal->title); ?></h3>
            <p><strong>Exclusive Code:</strong> <code><?php echo esc_html($deal->code); ?></code></p>
            <?php if ($deal->discount): ?>
            <p><strong>Discount:</strong> <?php echo esc_html($deal->discount); ?></p>
            <?php endif; ?>
            <?php if ($deal->expiry && $deal->expiry !== '0000-00-00'): ?>
            <p><strong>Expires:</strong> <?php echo date('M j, Y', strtotime($deal->expiry)); ?></p>
            <?php endif; ?>
            <a href="<?php echo esc_url($click_url); ?>" class="button" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Get Deal Now (<?php echo $deal->clicks; ?> used)</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('edm-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }
}

new ExclusiveDealsManager();

// Track clicks
add_action('init', function() {
    if (isset($_GET['edm_track'])) {
        // Already tracked in shortcode
    }
});

// Pro upgrade notice
add_action('admin_notices', function() {
    if (current_user_can('manage_options') && !get_option('edm_pro')) {
        echo '<div class="notice notice-info"><p><strong>Exclusive Deals Manager:</strong> Unlock unlimited deals, analytics & integrations with <a href="https://example.com/pro" target="_blank">Pro version</a> for $49/year!</p></div>';
    }
});