/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes to boost affiliate commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_save_coupons', array($this, 'save_coupons'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            wp_enqueue_script('jquery');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('affiliate-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('affiliate-coupon-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['coupons'])) {
            update_option('affiliate_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('affiliate_coupons', '{
  "amazon": {
    "code": "SAVE20",
    "link": "https://amazon.com/deal",
    "desc": "20% off electronics"
  },
  "shopify": {
    "code": "BLOG10",
    "link": "https://shopify.com/deal",
    "desc": "10% off first purchase"
  }
}');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_coupons">
                <textarea name="coupons" rows="20" cols="80" style="width:100%;"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Enter JSON format: {"brand":{"code":"CODE","link":"URL","desc":"Description"}}</p>
                <p><input type="submit" class="button-primary" value="Save Coupons"></p>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon id="amazon"]</code> or <code>[affiliate_coupon]</code> for random.</p>
            <p>Premium: Unlock analytics, unlimited coupons, auto-generation (Upgrade at example.com).</p>
        </div>
        <?php
    }

    public function save_coupons() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        wp_redirect(admin_url('options-general.php?page=affiliate-coupon-vault'));
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = json_decode(get_option('affiliate_coupons', '{}'), true);
        if (empty($coupons)) {
            return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>.</p>';
        }
        if ($atts['id'] && isset($coupons[$atts['id']])) {
            $coupon = $coupons[$atts['id']];
        } else {
            $keys = array_keys($coupons);
            $coupon = $coupons[$keys[array_rand($keys)]];
        }
        $personalized_code = $coupon['code'] . '-' . substr(md5(uniqid()), 0, 4);
        ob_start();
        ?>
        <div class="affiliate-coupon-vault">
            <div class="coupon-header">🛍️ <strong>Exclusive Deal!</strong></div>
            <div class="coupon-code"><strong><?php echo esc_html($personalized_code); ?></strong></div>
            <p><?php echo esc_html($coupon['desc']); ?></p>
            <a href="<?php echo esc_url($coupon['link'] . '?ref=' . get_bloginfo('url')); ?>" class="coupon-button" target="_blank">Grab Deal Now</a>
            <small>Generated for you exclusively</small>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        if (!get_option('affiliate_coupons')) {
            update_option('affiliate_coupons', '{"amazon":{"code":"SAVE20","link":"https://amazon.com","desc":"20% off"},"shopify":{"code":"BLOG10","link":"https://shopify.com","desc":"10% off"}}');
        }
    }
}

new AffiliateCouponVault();

/* Premium Upsell Notice */
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_affiliate-coupon-vault') return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault:</strong> Upgrade to Pro for unlimited coupons, analytics & auto-generation! <a href="https://example.com/upgrade" target="_blank">Get Pro ($49/yr)</a></p></div>';
});

// Inline CSS
add_action('wp_head', function() {
    echo '<style>
.affiliate-coupon-vault { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; max-width: 300px; margin: 20px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
.coupon-header { font-size: 1.2em; margin-bottom: 10px; }
.coupon-code { font-size: 2em; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; margin: 10px 0; }
.coupon-button { display: inline-block; background: #ff6b6b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 25px; margin-top: 15px; font-weight: bold; transition: all 0.3s; }
.coupon-button:hover { background: #ff5252; transform: translateY(-2px); }
@media (max-width: 768px) { .affiliate-coupon-vault { margin: 10px; } }
</style>';
});

// Inline JS
add_action('wp_footer', function() {
    echo '<script>
jQuery(document).ready(function($) {
    $(".coupon-button").on("click", function() {
        $(this).text("Copied! Go grab it!");
        gtag("event", "coupon_click", {"event_category": "affiliate", "event_label": $(this).closest(".affiliate-coupon-vault").find(".coupon-code").text() });
    });
});
</script>';
});