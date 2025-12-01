/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Create and manage affiliate deal sections with dynamic coupons and trackable affiliate links.
 * Version: 1.0
 * Author: Generated
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    private $deals_option_name = 'adb_deals_list';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_adb_save_deal', array($this, 'handle_save_deal'));
        add_shortcode('affiliate_deals', array($this, 'render_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    public function enqueue_styles() {
        wp_enqueue_style('adb_styles', plugin_dir_url(__FILE__) . 'adb-style.css');
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deals', 'manage_options', 'affiliate-deals', array($this, 'admin_page'), 'dashicons-cart', 26);
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;

        $deals = get_option($this->deals_option_name, array());
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster - Manage Deals</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="adb_save_deal" />
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><label for="deal_title">Deal Title</label></th>
                            <td><input name="deal_title" type="text" id="deal_title" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="deal_url">Affiliate URL</label></th>
                            <td><input name="deal_url" type="url" id="deal_url" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="coupon_code">Coupon Code (Optional)</label></th>
                            <td><input name="coupon_code" type="text" id="coupon_code" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="description">Description</label></th>
                            <td><textarea name="description" id="description" rows="4" cols="50"></textarea></td>
                        </tr>
                    </tbody>
                </table>
                <?php wp_nonce_field('adb_deal_nonce_action', 'adb_deal_nonce_field'); ?>
                <?php submit_button('Add Deal'); ?>
            </form>
            <h2>Current Deals</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Affiliate URL</th>
                        <th>Coupon</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deals as $key => $deal): ?>
                    <tr>
                        <td><?php echo esc_html($deal['deal_title']); ?></td>
                        <td><a href="<?php echo esc_url($deal['deal_url']); ?>" target="_blank" rel="nofollow noopener noreferrer">Link</a></td>
                        <td><?php echo isset($deal['coupon_code']) ? esc_html($deal['coupon_code']) : '-'; ?></td>
                        <td><?php echo esc_html($deal['description']); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="adb_delete_deal">
                                <input type="hidden" name="deal_key" value="<?php echo esc_attr($key); ?>">
                                <?php wp_nonce_field('adb_delete_nonce_action', 'adb_delete_nonce_field'); ?>
                                <input type="submit" class="button button-link-delete" value="Delete">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($deals)): ?>
                    <tr><td colspan="5">No deals added yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p>Use shortcode <code>[affiliate_deals]</code> on any page/post to display the deals.</p>
        </div>
        <?php
    }

    public function handle_save_deal() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized user');

        if (!isset($_POST['adb_deal_nonce_field']) || !wp_verify_nonce($_POST['adb_deal_nonce_field'], 'adb_deal_nonce_action')) {
            wp_die('Security check failed');
        }

        $deals = get_option($this->deals_option_name, array());
        $new_deal = array(
            'deal_title' => sanitize_text_field($_POST['deal_title']),
            'deal_url' => esc_url_raw($_POST['deal_url']),
            'coupon_code' => sanitize_text_field($_POST['coupon_code']),
            'description' => sanitize_textarea_field($_POST['description'])
        );

        $deals[] = $new_deal;

        update_option($this->deals_option_name, $deals);

        wp_redirect(admin_url('admin.php?page=affiliate-deals&added=1'));
        exit;
    }

    public function render_deals_shortcode() {
        $deals = get_option($this->deals_option_name, array());
        if (empty($deals)) return '<p>No affiliate deals available at the moment.</p>';

        $output = '<div class="adb-deals-container">';

        foreach ($deals as $deal) {
            $title = esc_html($deal['deal_title']);
            $url = esc_url($deal['deal_url']);
            $coupon = isset($deal['coupon_code']) && $deal['coupon_code'] !== '' ? esc_html($deal['coupon_code']) : '';
            $desc = esc_html($deal['description']);

            $output .= '<div class="adb-deal-item">';
            $output .= "<h3>{$title}</h3>";
            $output .= "<p>{$desc}</p>";
            if ($coupon) {
                $output .= "<p><strong>Coupon Code: </strong><code>{$coupon}</code></p>";
            }
            $output .= "<a href='{$url}' class='adb-deal-button' target='_blank' rel='nofollow noopener noreferrer'>Grab the Deal</a>";
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }
}

new AffiliateDealBooster();
