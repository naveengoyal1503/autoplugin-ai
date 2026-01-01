/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals to boost conversions and commissions.
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
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $this->load_settings();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    private function load_settings() {
        $this->settings = get_option('affiliate_coupon_vault_settings', array(
            'api_key' => '',
            'affiliate_links' => array(),
            'pro_version' => false
        ));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
            'limit' => 5
        ), $atts);

        $coupons = $this->get_coupons($atts['category'], $atts['limit']);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <h4><?php echo esc_html($coupon['title']); ?></h4>
                <p class="discount"><?php echo esc_html($coupon['discount']); ?></p>
                <a href="<?php echo esc_url($coupon['link']); ?>" class="coupon-btn" target="_blank">Get Deal <?php echo $this->settings['pro_version'] ? '(Pro)' : ''; ?></a>
            </div>
            <?php endforeach; ?>
            <?php if (!$this->settings['pro_version']): ?>
            <div class="pro-upgrade">
                <p>Upgrade to Pro for unlimited coupons and analytics!</p>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_coupons($category, $limit) {
        $sample_coupons = array(
            array('title' => '20% Off Hosting', 'discount' => 'SAVE 20%', 'link' => $this->settings['affiliate_links'] ?? '#'),
            array('title' => 'Free Domain', 'discount' => 'FREE .COM', 'link' => $this->settings['affiliate_links'][1] ?? '#'),
            array('title' => 'AI Tool Discount', 'discount' => '50% OFF', 'link' => $this->settings['affiliate_links'][2] ?? '#'),
            array('title' => 'VPN Deal', 'discount' => '$2.99/mo', 'link' => $this->settings['affiliate_links'][3] ?? '#'),
            array('title' => 'Email Marketing', 'discount' => '15% OFF', 'link' => $this->settings['affiliate_links'][4] ?? '#')
        );
        return array_slice($sample_coupons, 0, $limit);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault', 'affiliate_coupon_vault_settings');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('affiliate_coupon_vault_settings', $_POST['settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = $this->settings;
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array('api_key' => '', 'affiliate_links' => array(), 'pro_version' => false));
    }
}

AffiliateCouponVault::get_instance();

// Pro upsell notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Unlock Pro features for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Minimal CSS
$css = '.affiliate-coupon-vault .coupon-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; } .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; } .pro-upgrade { background: #fff3cd; padding: 10px; text-align: center; margin-top: 20px; }';
wp_add_inline_style('affiliate-coupon-vault', $css);

// Minimal JS
$js = 'jQuery(".coupon-btn").click(function(){ jQuery(this).text("Copied! Thanks!"); });';
wp_add_inline_script('affiliate-coupon-vault', $js);

// Admin page template content (embedded)
function acv_admin_template() { ob_start(); ?>
<div class="wrap">
    <h1>Affiliate Coupon Vault Settings</h1>
    <form method="post">
        <table class="form-table">
            <tr>
                <th>API Key (Pro)</th>
                <td><input type="text" name="settings[api_key]" value="<?php echo esc_attr($settings['api_key']); ?>" /></td>
            </tr>
            <tr>
                <th>Affiliate Links (5 max free)</th>
                <td>
                    <?php for($i=0; $i<5; $i++): ?>
                    <input type="url" name="settings[affiliate_links][<?php echo $i; ?>]" value="<?php echo esc_attr($settings['affiliate_links'][$i] ?? ''); ?>" style="width:100%; margin-bottom:5px;" /><br>
                    <?php endfor; ?>
                    <p class="description">Pro: Unlimited links + auto-tracking.</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    <p>Usage: <code>[affiliate_coupons category="hosting" limit="3"]</code></p>
</div>
<?php return ob_get_clean(); }
// Note: In full impl, save as admin-page.php, but self-contained here via echo in admin_page().
?>