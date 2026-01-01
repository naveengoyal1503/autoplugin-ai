/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons for your site's niche, boosting conversions and commissions.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon_vault', array($this, 'shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_api_key', sanitize_text_field($_POST['api_key']));
            update_option('acv_niche', sanitize_text_field($_POST['niche']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('acv_api_key', '');
        $niche = get_option('acv_niche', 'general');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Site Niche</th>
                        <td>
                            <select name="niche">
                                <option value="general" <?php selected($niche, 'general'); ?>>General</option>
                                <option value="tech" <?php selected($niche, 'tech'); ?>>Tech</option>
                                <option value="fashion" <?php selected($niche, 'fashion'); ?>>Fashion</option>
                                <option value="travel" <?php selected($niche, 'travel'); ?>>Travel</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode <code>[affiliate_coupon_vault]</code> to display coupons.</p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics, auto-rotation. <a href="https://example.com/pro">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function shortcode($atts) {
        $atts = shortcode_atts(array('count' => 3), $atts);
        $coupons = $this->generate_coupons($atts['count']);
        ob_start();
        ?>
        <div id="acv-vault" class="acv-coupons" data-niche="<?php echo esc_attr(get_option('acv_niche')); ?>">
            <?php foreach ($coupons as $coupon): ?>
            <div class="acv-coupon">
                <h3><?php echo esc_html($coupon['title']); ?></h3>
                <p>Code: <strong><?php echo esc_html($coupon['code']); ?></strong></p>
                <p>Save: <strong><?php echo esc_html($coupon['discount']); ?></strong></p>
                <a href="<?php echo esc_url($coupon['link']); ?>" class="acv-button" target="_blank" rel="nofollow">Get Deal <?php echo $this->track_click($coupon['id']); ?></a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupons($count = 3) {
        // Demo coupons - Pro version integrates real APIs
        $demo_coupons = array(
            array('id' => 1, 'title' => '20% Off Hosting', 'code' => 'WP2026', 'discount' => '20%', 'link' => 'https://example.com/hosting?aff=123'),
            array('id' => 2, 'title' => 'Buy 1 Get 1 Theme', 'code' => 'BOGO26', 'discount' => '50%', 'link' => 'https://example.com/themes?aff=123'),
            array('id' => 3, 'title' => 'Plugin Bundle Deal', 'code' => 'PLUGINSAVE', 'discount' => '30%', 'link' => 'https://example.com/plugins?aff=123'),
        );
        return array_slice($demo_coupons, 0, $count);
    }

    private function track_click($coupon_id) {
        return "&ref=" . $coupon_id;
    }

    public function activate() {
        update_option('acv_version', '1.0.0');
    }
}

new AffiliateCouponVault();

// Pro upsell notice
function acv_pro_notice() {
    if (!get_option('acv_pro_dismissed')) {
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlock unlimited coupons & analytics for $49/year. <a href="https://example.com/pro">Upgrade Now</a> | <a href="' . wp_nonce_url(admin_url('admin-post.php?action=acv_dismiss_pro'), 'acv_dismiss') . '">Dismiss</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_notice');

// CSS and JS placeholders (add as separate files in production)
/* style.css content:
.acv-coupons { display: flex; flex-wrap: wrap; gap: 20px; }
.acv-coupon { border: 1px solid #ddd; padding: 20px; border-radius: 8px; flex: 1 1 300px; }
.acv-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; }
.acv-button:hover { background: #005a87; }
*/
/* script.js content:
jQuery(document).ready(function($) {
    $('#acv-vault .acv-button').on('click', function() {
        // Track click in Pro
        console.log('Coupon clicked!');
    });
});
*/