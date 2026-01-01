/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Affiliate_Deals_Manager.php
*/
<?php
/**
 * Plugin Name: Exclusive Affiliate Deals Manager
 * Plugin URI: https://example.com/deals-manager
 * Description: Automatically generates and manages exclusive affiliate coupon deals for your WordPress site.
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
        if (is_admin()) {
            add_action('admin_post_save_deal', array($this, 'save_deal'));
        }
    }

    public function admin_menu() {
        add_menu_page('Exclusive Deals', 'Deals Manager', 'manage_options', 'exclusive-deals', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['save_deal'])) {
            $this->save_deal();
        }
        $deals = get_option('exclusive_deals', array());
        ?>
        <div class="wrap">
            <h1>Manage Exclusive Deals</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_deal">
                <?php wp_nonce_field('save_deal_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>Title</th>
                        <td><input type="text" name="deal_title" required></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="affiliate_link" required style="width: 400px;"></td>
                    </tr>
                    <tr>
                        <th>Coupon Code</th>
                        <td><input type="text" name="coupon_code" required></td>
                    </tr>
                    <tr>
                        <th>Discount %</th>
                        <td><input type="number" name="discount" min="1" max="100" required></td>
                    </tr>
                    <tr>
                        <th>Expiration</th>
                        <td><input type="date" name="expiration" required></td>
                    </tr>
                </table>
                <p><input type="submit" class="button-primary" value="Add Deal"></p>
            </form>
            <h2>Active Deals</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Code</th><th>Discount</th><th>Shortcode</th><th>Actions</th></tr></thead>
                <tbody>
        <?php foreach ($deals as $id => $deal): if (strtotime($deal['expiration']) > time()): ?>
                    <tr>
                        <td><?php echo esc_html($deal['title']); ?></td>
                        <td><?php echo esc_html($deal['coupon_code']); ?></td>
                        <td><?php echo esc_html($deal['discount']); ?>%</td>
                        <td><code>[exclusive_deal id="<?php echo $id; ?>"]</code></td>
                        <td><a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=delete_deal&id=' . $id), 'delete_deal'); ?>" onclick="return confirm('Delete?');">Delete</a></td>
                    </tr>
        <?php endif; endforeach; ?>
                </tbody>
            </table>
            <p><strong>Pro Features:</strong> Unlimited deals, click tracking, auto-expiration emails. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function save_deal() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'save_deal_nonce') || !current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        $deals = get_option('exclusive_deals', array());
        $id = uniqid();
        $deals[$id] = array(
            'title' => sanitize_text_field($_POST['deal_title']),
            'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
            'coupon_code' => sanitize_text_field($_POST['coupon_code']),
            'discount' => intval($_POST['discount']),
            'expiration' => sanitize_text_field($_POST['expiration'])
        );
        update_option('exclusive_deals', $deals);
        wp_redirect(admin_url('admin.php?page=exclusive-deals'));
        exit;
    }

    public function deal_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $deals = get_option('exclusive_deals', array());
        if (!isset($deals[$atts['id']])) {
            return '';
        }
        $deal = $deals[$atts['id']];
        if (strtotime($deal['expiration']) < time()) {
            return '<p style="color: red;">Deal expired!</p>';
        }
        ob_start();
        ?>
        <div style="border: 2px solid #28a745; padding: 20px; border-radius: 10px; background: #f8fff9; text-align: center;">
            <h3 style="color: #28a745;"><?php echo esc_html($deal['title']); ?></h3>
            <p><strong>Exclusive Coupon:</strong> <code style="background: #fff; padding: 5px 10px; border-radius: 5px; font-size: 1.2em;"><?php echo esc_html($deal['coupon_code']); ?></code></p>
            <p><strong><?php echo esc_html($deal['discount']); ?>% OFF</strong> - Limited Time!</p>
            <a href="<?php echo esc_url(add_query_arg('ref', 'exclusive', $deal['affiliate_link'])); ?>" class="button" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">Grab Deal Now</a>
            <p style="font-size: 0.9em; margin-top: 10px;">Expires: <?php echo date('M d, Y', strtotime($deal['expiration'])); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('exclusive-deals', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function activate() {
        if (!get_option('exclusive_deals')) {
            update_option('exclusive_deals', array());
        }
    }
}

new ExclusiveDealsManager();

// Pro upsell notice
function exclusive_deals_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'toplevel_page_exclusive-deals') {
        echo '<div class="notice notice-info"><p><strong>Go Pro:</strong> Unlock unlimited deals, analytics & more for $49/year! <a href="https://example.com/pro" target="_blank">Learn More</a></p></div>';
    }
}
add_action('admin_notices', 'exclusive_deals_notice');

// Track clicks (basic)
add_action('wp', function() {
    if (isset($_GET['ref']) && $_GET['ref'] === 'exclusive') {
        // Pro feature: set_transient('deal_click_' . $_GET['deal_id'], 1, 3600);
    }
});

?>