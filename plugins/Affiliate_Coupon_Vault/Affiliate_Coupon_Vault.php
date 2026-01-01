/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_promo_code', array($this, 'generate_promo_code'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'acv-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_enqueue_scripts($hook) {
        if ('settings_page_affiliate-coupon-vault' !== $hook) {
            return;
        }
        wp_enqueue_script('acv-admin', plugin_dir_url(__FILE__) . 'acv-admin.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('acv_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            update_option('acv_coupon_text', sanitize_text_field($_POST['coupon_text']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('acv_affiliate_links', "Amazon: https://amazon.com/link\nBrandX: https://brandx.com/offer");
        $text = get_option('acv_coupon_text', 'Exclusive 20% OFF!');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="affiliate_links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea><br><small>One per line: Name: URL</small></td>
                    </tr>
                    <tr>
                        <th>Coupon Text</th>
                        <td><input type="text" name="coupon_text" value="<?php echo esc_attr($text); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Shortcode:</strong> <code>[affiliate_coupon]</code></p>
            <p><em>Pro Version: Unlimited coupons, analytics, auto-expiration.</em></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $links = explode("\n", get_option('acv_affiliate_links', ''));
        $coupons = array();
        foreach ($links as $link) {
            if (strpos($link, ':') !== false) {
                list($name, $url) = explode(':', $link, 2);
                $name = trim($name);
                $url = trim($url);
                $code = $this->generate_unique_code($name);
                $coupons[] = array('name' => $name, 'url' => $url, 'code' => $code);
            }
        }
        ob_start();
        ?>
        <div id="acv-coupons" style="background: #f9f9f9; padding: 20px; border-radius: 8px;">
            <h3><?php echo esc_html(get_option('acv_coupon_text', 'Exclusive Coupons!')); ?></h3>
            <?php foreach ($coupons as $coupon): ?>
            <div class="acv-coupon" style="margin: 10px 0; padding: 15px; background: white; border-left: 4px solid #0073aa;">
                <strong><?php echo esc_html($coupon['name']); ?></strong><br>
                <span style="font-size: 24px; color: #e74c3c;"><?php echo esc_html($coupon['code']); ?></span><br>
                <a href="<?php echo esc_url($coupon['url']); ?><?php echo strpos($coupon['url'], '?') === false ? '?' : '&'; ?>ref=<?php echo esc_attr(get_bloginfo('url')); ?>" class="button" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Get Deal</a>
                <button class="acv-copy" data-code="<?php echo esc_attr($coupon['code']); ?>">Copy Code</button>
            </div>
            <?php endforeach; ?>
        </div>
        <script>
        jQuery('.acv-copy').click(function() {
            navigator.clipboard.writeText(jQuery(this).data('code'));
            alert('Copied!');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function generate_unique_code($name) {
        return strtoupper(substr(md5($name . get_bloginfo('url') . time()), 0, 8));
    }

    public function generate_promo_code() {
        check_ajax_referer('acv_nonce', 'nonce');
        $name = sanitize_text_field($_POST['name']);
        echo $this->generate_unique_code($name);
        wp_die();
    }

    public function activate() {
        add_option('acv_affiliate_links', "Amazon: https://amazon.com/link\nBrandX: https://brandx.com/offer");
    }

    public function deactivate() {}
}

AffiliateCouponVault::get_instance();

// Pro Upsell Notice
function acv_admin_notice() {
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, analytics & integrations! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/yr)</a></p></div>';
}
add_action('admin_notices', 'acv_admin_notice');

// Frontend JS (inline for single file)
function acv_inline_scripts() {
    if (!is_admin()) {
        ?>
        <script>jQuery(document).ready(function($) {
            $('.acv-copy').on('click', function() {
                var code = $(this).data('code');
                navigator.clipboard.writeText(code).then(function() {
                    alert('Code copied to clipboard!');
                });
            });
        });</script>
        <?php
    }
}
add_action('wp_footer', 'acv_inline_scripts');