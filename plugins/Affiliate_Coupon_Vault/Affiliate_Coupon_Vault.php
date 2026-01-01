/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost conversions.
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
        add_action('wp_ajax_save_coupon', array($this, 'ajax_save_coupon'));
        add_action('wp_ajax_nopriv_save_coupon', array($this, 'ajax_save_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-js', plugin_dir_url(__FILE__) . 'coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('affiliate-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('affiliate-coupon-css', plugin_dir_url(__FILE__) . 'coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['affiliate_coupons'])) {
            update_option('affiliate_coupons', sanitize_textarea_field($_POST['coupons']));
            echo '<div class="notice notice-success"><p>Coupons saved!</p></div>';
        }
        $coupons = get_option('affiliate_coupons', "Coupon Code: SAVE10\nAffiliate Link: https://example.com/aff\nDescription: 10% off first purchase");
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <textarea name="coupons" rows="10" cols="50" class="large-text"><?php echo esc_textarea($coupons); ?></textarea>
                <p class="description">Enter coupons one per line: Coupon Code | Affiliate Link | Description</p>
                <p><?php submit_button(); ?></p>
            </form>
            <p>Use shortcode: <code>[affiliate_coupon id="1"]</code> or <code>[affiliate_coupon]</code> for random.</p>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, click tracking, analytics dashboard ($49/year).</p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons_text = get_option('affiliate_coupons', '');
        if (empty($coupons_text)) return '<p>No coupons configured. <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Set up now</a>.</p>';

        $coupons = explode('\n', $coupons_text);
        if ($atts['id']) {
            $coupon = explode('|', trim($coupons[(int)$atts['id'] - 1]));
        } else {
            $coupon = explode('|', trim($coupons[array_rand($coupons)]));
        }

        if (count($coupon) < 3) return '<p>Invalid coupon format.</p>';

        $code = trim($coupon);
        $link = trim($coupon[1]);
        $desc = trim($coupon[2]);

        $tracking_id = uniqid('acv_');
        $tracked_link = add_query_arg('ref', $tracking_id, $link);

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-tracking="<?php echo esc_attr($tracking_id); ?>">
            <div class="coupon-code"><?php echo esc_html($code); ?></div>
            <p class="coupon-desc"><?php echo esc_html($desc); ?></p>
            <a href="<?php echo esc_url($tracked_link); ?>" class="coupon-button" target="_blank">Get Deal Now</a>
            <button class="copy-code" onclick="copyToClipboard(this)">Copy Code</button>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_save_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'coupon_nonce')) wp_die();
        // Pro feature simulation
        wp_send_json_success('Coupon saved! Upgrade to Pro for full tracking.');
    }

    public function activate() {
        if (!get_option('affiliate_coupons')) {
            update_option('affiliate_coupons', "WELCOME20|https://example.com/aff1|20% off welcome deal\nSAVE15|https://example.com/aff2|15% sitewide discount");
        }
    }
}

new AffiliateCouponVault();

// Inline CSS
add_action('wp_head', function() { ?>
<style>
.affiliate-coupon-vault { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; text-align: center; max-width: 400px; }
.coupon-code { font-size: 2em; font-weight: bold; color: #0073aa; background: #fff; padding: 10px; border-radius: 5px; display: inline-block; margin-bottom: 10px; }
.coupon-desc { margin: 10px 0; }
.coupon-button { background: #0073aa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px; }
.coupon-button:hover { background: #005a87; }
.copy-code { background: #46b450; color: white; border: none; padding: 8px 16px; border-radius: 5px; cursor: pointer; }
</style>
<script>
function copyToClipboard(btn) { var code = btn.previousElementSibling.previousElementSibling.textContent; navigator.clipboard.writeText(code).then(() => { btn.textContent = 'Copied!'; setTimeout(() => btn.textContent = 'Copy Code', 2000); }); }
</script>
<?php });

// JS file content would be enqueued, but for single file, inline basic JS
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) { $('.copy-code').on('click', function() { var code = $(this).prevAll('.coupon-code').first().text(); navigator.clipboard.writeText(code).then(() => $(this).text('Copied!').delay(2000).queue(() => $(this).text('Copy Code').dequeue())); }); });</script>
<?php });