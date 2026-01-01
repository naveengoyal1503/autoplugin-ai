/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons from top networks, boosting conversions with personalized discount codes and tracking.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'network' => 'amazon',
            'category' => 'electronics',
            'limit' => 5
        ), $atts);

        $coupons = $this->get_sample_coupons($atts['network'], $atts['category'], $atts['limit']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <h3>Exclusive Deals</h3>
            <?php foreach ($coupons as $coupon): ?>
                <div class="coupon-item">
                    <h4><?php echo esc_html($coupon['title']); ?></h4>
                    <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                    <p>Save: <?php echo esc_html($coupon['discount']); ?></p>
                    <a href="<?php echo esc_url($coupon['link']); ?}" target="_blank" class="coupon-btn" rel="nofollow">Shop Now & Save</a>
                    <span class="affiliate-tracking" data-id="<?php echo esc_attr($coupon['id']); ?>"></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_sample_coupons($network, $category, $limit) {
        // Sample data - Pro version would fetch from APIs
        $samples = array(
            array('id' => 1, 'title' => '50% Off Laptops', 'code' => 'SAVE50', 'discount' => '50%', 'link' => 'https://example.com/aff/laptop', 'network' => 'amazon'),
            array('id' => 2, 'title' => '20% Off Headphones', 'code' => 'HEAD20', 'discount' => '20%', 'link' => 'https://example.com/aff/headphones', 'network' => 'amazon'),
            array('id' => 3, 'title' => 'Free Shipping', 'code' => 'FREESHIP', 'discount' => 'Free Shipping', 'link' => 'https://example.com/aff/shipping', 'network' => 'amazon'),
            array('id' => 4, 'title' => '30% Off Software', 'code' => 'SOFT30', 'discount' => '30%', 'link' => 'https://example.com/aff/software', 'network' => 'other'),
            array('id' => 5, 'title' => 'Buy One Get One', 'code' => 'BOGO', 'discount' => 'BOGO', 'link' => 'https://example.com/aff/bogo', 'network' => 'amazon')
        );
        return array_slice($samples, 0, $limit);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'acv_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('affiliate_coupon_vault_options'); ?>
                <?php do_settings_sections('affiliate_coupon_vault_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="text" name="acv_settings[api_key]" value="<?php echo esc_attr(get_option('acv_settings')['api_key'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Network</th>
                        <td>
                            <select name="acv_settings[network]">
                                <option value="amazon">Amazon</option>
                                <option value="other">Other</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for live API integrations, analytics, and unlimited coupons. <a href="https://example.com/pro">Get Pro Now</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('acv_settings', array());
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock API integrations & analytics. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    file_put_contents($assets_dir . '/style.css', '.affiliate-coupon-vault { max-width: 600px; } .coupon-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; } .coupon-btn { background: #ff9900; color: white; padding: 10px 20px; text-decoration: none; }');
    file_put_contents($assets_dir . '/script.js', "jQuery(document).ready(function($) { $('.coupon-btn').click(function() { $(this).siblings('.affiliate-tracking').text('Tracked!'); }); });");
});
