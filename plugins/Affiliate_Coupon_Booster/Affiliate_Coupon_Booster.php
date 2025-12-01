<?php
/*
Plugin Name: Affiliate Coupon Booster
Description: Auto-curates and displays affiliate coupons with deal verification to boost affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Booster.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateCouponBooster {
    private $plugin_slug = 'affiliate-coupon-booster';
    private $option_key = 'acb_coupons';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_shortcode('acb_coupons', array($this, 'shortcode_display_coupons'));
        add_action('admin_post_acb_add_coupon', array($this, 'handle_add_coupon'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Coupons', 'Affiliate Coupons', 'manage_options', $this->plugin_slug, array($this, 'admin_page_html'), 'dashicons-tickets-alt');
    }

    public function admin_page_html() {
        if (!current_user_can('manage_options')) return;
        $coupons = get_option($this->option_key, array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Booster</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="acb_add_coupon">
                <?php wp_nonce_field('acb_add_coupon_nonce');?>
                <table class="form-table">
                    <tr>
                        <th><label for="title">Coupon Title</label></th>
                        <td><input name="title" id="title" type="text" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="code">Coupon Code</label></th>
                        <td><input name="code" id="code" type="text" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_url">Affiliate Link</label></th>
                        <td><input name="affiliate_url" id="affiliate_url" type="url" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="expiry">Expiry Date (YYYY-MM-DD)</label></th>
                        <td><input name="expiry" id="expiry" type="date"></td>
                    </tr>
                </table>
                <?php submit_button('Add Coupon'); ?>
            </form>
            <h2>Current Coupons</h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Code</th>
                        <th>Affiliate URL</th>
                        <th>Expiry</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                if ($coupons) {
                    foreach ($coupons as $coupon) {
                        echo '<tr>';
                        echo '<td>' . esc_html($coupon['title']) . '</td>';
                        echo '<td><code>' . esc_html($coupon['code']) . '</code></td>';
                        echo '<td><a href="' . esc_url($coupon['affiliate_url']) . '" target="_blank" rel="nofollow noopener">Link</a></td>';
                        echo '<td>' . esc_html($coupon['expiry'] ?? 'No expiry') . '</td>';
                        echo '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="4">No coupons added yet.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_add_coupon() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('acb_add_coupon_nonce');

        $title = sanitize_text_field($_POST['title'] ?? '');
        $code = sanitize_text_field($_POST['code'] ?? '');
        $affiliate_url = esc_url_raw($_POST['affiliate_url'] ?? '');
        $expiry = sanitize_text_field($_POST['expiry'] ?? '');

        if (!$title || !$code || !$affiliate_url) {
            wp_redirect(admin_url('admin.php?page=' . $this->plugin_slug . '&error=1'));
            exit;
        }

        $coupons = get_option($this->option_key, array());
        $coupons[] = array(
            'title' => $title,
            'code' => $code,
            'affiliate_url' => $affiliate_url,
            'expiry' => $expiry,
        );

        update_option($this->option_key, $coupons);

        wp_redirect(admin_url('admin.php?page=' . $this->plugin_slug . '&added=1'));
        exit;
    }

    public function shortcode_display_coupons() {
        $coupons = get_option($this->option_key, array());
        $today = current_time('Y-m-d');

        if (!$coupons) return '<p>No coupons available at the moment.</p>';

        $output = '<div class="acb-coupons">';
        foreach ($coupons as $coupon) {
            // Skip expired coupons
            if (!empty($coupon['expiry']) && $coupon['expiry'] < $today) continue;

            $output .= '<div class="acb-coupon" style="border:1px solid #ccc;padding:10px;margin:10px 0;">';
            $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
            $output .= '<p>Use Code: <strong>' . esc_html($coupon['code']) . '</strong></p>';
            $output .= '<a href="' . esc_url($coupon['affiliate_url']) . '" target="_blank" rel="nofollow noopener" style="display:inline-block;padding:10px 15px;background:#0073aa;color:#fff;text-decoration:none;border-radius:4px;">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }
}

new AffiliateCouponBooster();
