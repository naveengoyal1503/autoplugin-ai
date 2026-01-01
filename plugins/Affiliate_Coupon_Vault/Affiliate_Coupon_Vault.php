/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with custom promo codes, boosting conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_save_coupon', array($this, 'save_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_register_style('acv-admin', plugin_dir_url(__FILE__) . 'admin.css');
            wp_enqueue_style('acv-admin');
        }
    }

    public function enqueue_scripts() {
        wp_register_style('acv-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_style('acv-style');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts);

        $coupons = get_option('acv_coupons', array());
        if (!isset($coupons[$atts['id']])) {
            return '<p>No coupon found.</p>';
        }

        $coupon = $coupons[$atts['id']];
        $unique_code = $this->generate_unique_code($coupon['base_code']);

        ob_start();
        ?>
        <div class="acv-coupon-vault">
            <h3><?php echo esc_html($coupon['title']); ?></h3>
            <p>Exclusive Code: <strong><?php echo esc_html($unique_code); ?></strong></p>
            <p><?php echo esc_html($coupon['description']); ?></p>
            <a href="<?php echo esc_url($coupon['affiliate_link']); ?>&coupon=<?php echo urlencode($unique_code); ?>" class="acv-button" target="_blank">Get Deal Now (Affiliate Link)</a>
            <small>Save <?php echo esc_html($coupon['discount']); ?> - Limited time!</small>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_unique_code($base) {
        return $base . '-' . wp_generate_uuid4() . substr(md5(uniqid()), 0, 4);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('acv_save');
            $this->save_coupon();
        }

        $coupons = get_option('acv_coupons', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <?php wp_nonce_field('acv_save'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupon ID</th>
                        <td><input type="number" name="coupon_id" value="<?php echo count($coupons); ?>" readonly></td>
                    </tr>
                    <tr>
                        <th>Title</th>
                        <td><input type="text" name="title" required style="width:100%"></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><textarea name="description" rows="3" style="width:100%"></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Link</th>
                        <td><input type="url" name="affiliate_link" required style="width:100%"></td>
                    </tr>
                    <tr>
                        <th>Base Code</th>
                        <td><input type="text" name="base_code" required placeholder="e.g., SAVE20"></td>
                    </tr>
                    <tr>
                        <th>Discount</th>
                        <td><input type="text" name="discount" required placeholder="20% off"></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Add Coupon"></p>
            </form>
            <h2>Existing Coupons</h2>
            <ul>
                <?php foreach ($coupons as $id => $c): ?>
                    <li>ID: <?php echo $id; ?> - <?php echo esc_html($c['title']); ?> <a href="<?php echo admin_url('options-general.php?page=acv-settings&delete=' . $id); ?>">Delete</a></li>
                <?php endforeach; ?>
            </ul>
            <p><strong>Usage:</strong> Use shortcode [affiliate_coupon id="X"] where X is the coupon ID.</p>
            <p><em>Pro Upgrade: Unlimited coupons, analytics, auto-expiry ($49/year)</em></p>
        </div>
        <style>
        .acv-admin { /* Inline styles for admin */ }
        </style>
        <?php
    }

    public function save_coupon() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $coupons = get_option('acv_coupons', array());
        $id = intval($_POST['coupon_id']);
        $coupons[$id] = array(
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'affiliate_link' => esc_url_raw($_POST['affiliate_link']),
            'base_code' => sanitize_text_field($_POST['base_code']),
            'discount' => sanitize_text_field($_POST['discount']),
        );

        update_option('acv_coupons', $coupons);
        wp_redirect(admin_url('options-general.php?page=acv-settings'));
        exit;
    }

    public function activate() {
        if (!get_option('acv_coupons')) {
            add_option('acv_coupons', array());
        }
    }
}

new AffiliateCouponVault();

/* CSS for frontend */
function acv_add_styles() {
    echo '<style>
    .acv-coupon-vault { background: #f9f9f9; padding: 20px; border: 2px dashed #0073aa; border-radius: 10px; text-align: center; max-width: 400px; margin: 20px auto; }
    .acv-coupon-vault h3 { color: #0073aa; margin: 0 0 10px; }
    .acv-button { display: inline-block; background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; }
    .acv-button:hover { background: #005a87; }
    </style>';
}
add_action('wp_head', 'acv_add_styles');

?>