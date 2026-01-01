/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Vault_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Vault Pro
 * Plugin URI: https://example.com/aicouponvault
 * Description: AI-powered coupon management with affiliate tracking for WordPress monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-vault-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-vault-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Vault', 'Coupon Vault', 'manage_options', 'ai-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('aicv_affiliates', sanitize_textarea_field($_POST['affiliates']));
            update_option('aicv_pro', isset($_POST['pro']) ? '1' : '0');
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliates = get_option('aicv_affiliates', "Amazon:10% off\nShopify:Free trial");
        $pro = get_option('aicv_pro', '0');
        ?>
        <div class="wrap">
            <h1>AI Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Deals (one per line: Name:Code:Link:Discount)</th>
                        <td><textarea name="affiliates" rows="10" cols="50"><?php echo esc_textarea($affiliates); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Pro Version</th>
                        <td><input type="checkbox" name="pro" <?php checked($pro, '1'); ?> /> Enable Pro Features (Unlimited Coupons)</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock analytics, custom designs, and more for $49/year. <a href="#pro">Buy Now</a></p>
        </div>
        <?php
    }

    public function generate_coupon($name = 'Generic') {
        $deals = explode("\n", get_option('aicv_affiliates', ''));
        $coupon = array();
        foreach ($deals as $deal) {
            if (trim($deal)) {
                list($n, $code, $link, $disc) = explode(':', trim($deal) . '::', 4);
                $coupon = array('name' => trim($n), 'code' => trim($code), 'link' => trim($link), 'discount' => trim($disc));
                break;
            }
        }
        // Simple AI-like randomization for uniqueness
        $random_code = substr(md5($name . time()), 0, 8);
        return array('name' => $coupon['name'] ?? $name, 'code' => $random_code, 'link' => $coupon['link'] ?? '#', 'discount' => $coupon['discount'] ?? '10% OFF');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('product' => 'default'), $atts);
        $coupon = $this->generate_coupon($atts['product']);
        $pro = get_option('aicv_pro', '0');
        ob_start();
        ?>
        <div id="ai-coupon-vault" class="coupon-vault <?php echo $pro === '1' ? 'pro' : 'free'; ?>">
            <h3><?php echo esc_html($coupon['name']); ?> Deal</h3>
            <p><strong><?php echo esc_html($coupon['discount']); ?></strong></p>
            <div class="coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <a href="<?php echo esc_url($coupon['link']); ?}" class="coupon-btn" target="_blank">Get Deal (Affiliate Link)</a>
            <?php if ($pro !== '1') { ?>
                <p class="upgrade"><a href="<?php echo admin_url('options-general.php?page=ai-coupon-vault'); ?>">Upgrade to Pro for more!</a></p>
            <?php } ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        update_option('aicv_affiliates', "Amazon:AMAZON10:https://amazon.com:10% off\nShopify:SHOPIFYFREE:https://shopify.com:Free Trial");
    }
}

new AICouponVault();

// Create assets directories if needed
$upload_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
}

// Sample assets content (base64 or inline would be better, but for single file)
$css = ".coupon-vault { border: 2px dashed #0073aa; padding: 20px; text-align: center; background: #f9f9f9; }.coupon-code { font-size: 24px; background: #fff; padding: 10px; border: 1px solid #ddd; margin: 10px 0; }.coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }.pro { border-color: #00aa00; }.upgrade { color: #0073aa; font-weight: bold; }";
file_put_contents($upload_dir . '/style.css', $css);

$js = "jQuery(document).ready(function($) { $('.coupon-code').click(function() { var code = $(this).text(); navigator.clipboard.writeText(code).then(function() { alert('Copied: ' + code); }); }); });";
file_put_contents($upload_dir . '/script.js', $js);
?>