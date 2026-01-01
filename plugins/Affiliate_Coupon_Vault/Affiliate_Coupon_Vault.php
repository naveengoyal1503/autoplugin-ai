/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons to boost commissions.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-vault', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_settings');
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
                        <th>Affiliate Links</th>
                        <td><textarea name="affiliate_coupon_vault_settings[links]" rows="10" cols="50"><?php echo esc_textarea(get_option('affiliate_coupon_vault_settings')['links'] ?? ''); ?></textarea><br>
                        Format: Product Name|Affiliate Link|Discount Code|Expiry Date (YYYY-MM-DD)</td>
                    </tr>
                    <tr>
                        <th>Enable Pro Features</th>
                        <td><input type="checkbox" name="affiliate_coupon_vault_settings[pro]" value="1" <?php checked((get_option('affiliate_coupon_vault_settings')['pro'] ?? 0)); ?> disabled> Upgrade to Pro for unlimited coupons</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics, and custom designs for $49/year. <a href="https://example.com/pro" target="_blank">Buy Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $settings = get_option('affiliate_coupon_vault_settings', array('links' => ''));
        $links = explode('\n', trim($settings['links']));
        $coupons = array();
        foreach ($links as $line) {
            if (empty(trim($line))) continue;
            $parts = explode('|', trim($line), 4);
            if (count($parts) >= 3) {
                $coupons[] = array(
                    'name' => sanitize_text_field($parts),
                    'link' => esc_url($parts[1]),
                    'code' => sanitize_text_field($parts[2]),
                    'expiry' => !empty($parts[3]) ? date('Y-m-d', strtotime($parts[3])) : ''
                );
            }
        }
        if (current_time('timestamp') > time() && count($coupons) > $atts['limit']) {
            $coupons = array_slice($coupons, 0, $atts['limit']);
        }
        ob_start();
        ?>
        <div id="coupon-vault" class="coupon-vault-container">
            <?php foreach ($coupons as $coupon): 
                $expired = !empty($coupon['expiry']) && current_time('timestamp') > strtotime($coupon['expiry'] . ' 23:59:59');
            ?>
            <div class="coupon-item <?php echo $expired ? 'expired' : ''; ?>">
                <h4><?php echo esc_html($coupon['name']); ?></h4>
                <p><strong>Code:</strong> <span class="coupon-code"><?php echo esc_html($coupon['code']); ?></span></p>
                <?php if ($expired): ?>
                    <p class="expired">Expired</p>
                <?php else: ?>
                    <a href="<?php echo $coupon['link']; ?}" target="_blank" class="coupon-btn" rel="nofollow">Get Deal (<?php echo ($settings['pro'] ? 'Tracked' : 'Basic'); ?>)</a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (!$settings['pro'] && count($coupons) >= 3): ?>
            <div class="pro-upgrade-banner">
                <p>Upgrade to Pro for unlimited coupons & analytics! <a href="https://example.com/pro" target="_blank">Get Pro ($49/yr)</a></p>
            </div>
            <?php endif; ?>
        </div>
        <style>
        .coupon-vault-container { max-width: 600px; margin: 20px 0; }
        .coupon-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; background: #f9f9f9; }
        .coupon-item.expired { opacity: 0.6; background: #ffebee; }
        .coupon-code { font-family: monospace; background: #fff; padding: 5px 10px; border-radius: 3px; }
        .coupon-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; }
        .coupon-btn:hover { background: #005a87; }
        .pro-upgrade-banner { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; text-align: center; margin-top: 20px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('.coupon-code').click(function() {
                var code = $(this).text();
                navigator.clipboard.writeText(code).then(function() {
                    $(this).after('<span style="color:green;"> Copied!</span>');
                }.bind(this));
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('affiliate_coupon_vault_settings', array('links' => "Sample Product|https://affiliate-link.com/product?ref=yourid|SAVE10|2026-12-31\nAnother Deal|https://affiliate-link.com/deal?ref=yourid|DEAL20|2026-06-30"));
    }
}

AffiliateCouponVault::get_instance();

// Pro teaser notice
function acv_admin_notice() {
    if (!get_option('affiliate_coupon_vault_settings')['pro']) {
        echo '<div class="notice notice-info"><p>Affiliate Coupon Vault Pro: Unlimited coupons & more for $49/yr! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_admin_notice');