/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost your affiliate commissions.
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
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $this->load_textdomain();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'product' => 'Sample Product',
            'discount' => '20%',
            'link' => 'https://example.com/affiliate-link',
            'code' => ''
        ), $atts);

        $unique_code = $atts['code'] ?: 'ACV' . wp_generate_uuid4() . substr(md5(auth()->user()->get('email') ?? 'visitor'), 0, 4);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <div class="coupon-header">
                <h3><?php echo esc_html($atts['product']); ?> - Exclusive Deal!</h3>
            </div>
            <div class="coupon-body">
                <span class="discount"><?php echo esc_html($atts['discount']); ?> OFF</span>
                <div class="promo-code"><strong><?php echo esc_html($unique_code); ?></strong></div>
                <a href="<?php echo esc_url($atts['link'] . '?coupon=' . urlencode($unique_code)); ?>" class="claim-button" target="_blank">Claim Coupon</a>
                <p>Limited time offer! Generated just for you.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault_options');
                do_settings_sections('affiliate_coupon_vault_options');
                ?>
                <table class="form-table">
                    <tr>
                        <th>Default Affiliate Link</th>
                        <td><input type="url" name="affiliate_coupon_settings[default_link]" value="" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Enable Pro Features</th>
                        <td>
                            <label>
                                <input type="checkbox" name="affiliate_coupon_settings[pro]" value="1" />
                                Upgrade to Pro for unlimited coupons & analytics
                            </label>
                            <p class="description">Pro version available at <a href="https://example.com/pro">example.com/pro</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon affiliate="amazon" product="Product Name" discount="50%" link="your-link"]</code></p>
        </div>
        <?php
    }

    public function activate() {
        // Create default options
        add_option('affiliate_coupon_settings', array('pro' => 0));
    }

    private function load_textdomain() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

// Global instance
AffiliateCouponVault::get_instance();

// Inline CSS and JS for single file

function acv_inline_assets() {
    ?>
    <style>
    .affiliate-coupon-vault {
        border: 2px dashed #007cba;
        border-radius: 10px;
        padding: 20px;
        max-width: 400px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        text-align: center;
        font-family: Arial, sans-serif;
        box-shadow: 0 4px 15px rgba(0,124,186,0.2);
        margin: 20px auto;
    }
    .coupon-header h3 {
        color: #007cba;
        margin: 0 0 15px;
        font-size: 1.4em;
    }
    .discount {
        display: inline-block;
        background: #ff6b6b;
        color: white;
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: bold;
        font-size: 1.2em;
        margin-bottom: 15px;
    }
    .promo-code {
        background: white;
        padding: 15px;
        border-radius: 8px;
        margin: 15px 0;
        font-size: 1.5em;
        font-family: monospace;
        border: 2px solid #ddd;
    }
    .claim-button {
        display: inline-block;
        background: #007cba;
        color: white;
        padding: 12px 30px;
        text-decoration: none;
        border-radius: 25px;
        font-weight: bold;
        transition: all 0.3s;
        margin: 10px 0;
    }
    .claim-button:hover {
        background: #005a87;
        transform: translateY(-2px);
    }
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('.affiliate-coupon-vault .claim-button').on('click', function(e) {
            $(this).text('Copied! Check your email for code.');
            // Simulate copy to clipboard
            const code = $(this).closest('.affiliate-coupon-vault').find('.promo-code strong').text();
            navigator.clipboard.writeText(code).then(function() {
                console.log('Coupon code copied: ' + code);
            });
        });
    });
    </script>
    <?php
}
add_action('wp_head', 'acv_inline_assets');

// Pro upsell notice
function acv_pro_notice() {
    if (!is_admin() && current_user_can('manage_options')) {
        echo '<div style="position:fixed;bottom:20px;right:20px;background:#007cba;color:white;padding:15px;border-radius:5px;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.15);">';
        echo '<strong>Affiliate Coupon Vault Pro</strong><br>Unlock unlimited coupons & analytics! <a href="https://example.com/pro" style="color:#fff;font-weight:bold;" target="_blank">Upgrade Now ($49/yr)</a>';
        echo '</div>';
    }
}
add_action('wp_footer', 'acv_pro_notice');