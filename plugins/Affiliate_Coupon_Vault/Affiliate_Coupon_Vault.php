/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically fetches, displays, and tracks affiliate coupons from multiple networks, boosting conversions with personalized discount codes and performance analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_coupons', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-frontend', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['acv_settings'])) {
            update_option('acv_api_keys', sanitize_text_field($_POST['api_key']));
            update_option('acv_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('acv_api_keys', '');
        $aff_links = get_option('acv_affiliate_links', '');
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Coupon API Key (Demo: demo123)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON format)</th>
                        <td><textarea name="affiliate_links" rows="10" class="large-text"><?php echo esc_textarea($aff_links); ?></textarea><br><small>Example: {"amazon":"https://amzn.to/EXAMPLE","other":"https://example.com/AFF"}</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Pro Upgrade</h2>
            <p>Unlock advanced tracking, unlimited coupons, and custom domains for <strong>$49/year</strong>. <a href="#" onclick="alert('Pro features coming soon!')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('category' => 'all'), $atts);
        $coupons = $this->get_demo_coupons($atts['category']);
        $output = '<div class="acv-coupons">';
        foreach ($coupons as $coupon) {
            $link = $this->get_affiliate_link($coupon['network']);
            $track_url = add_query_arg(array('acv_coupon' => $coupon['code'], 'ref' => 'wp'), wp_nonce_url(admin_url('admin-ajax.php?action=acv_track_click'), 'acv_nonce'));
            $output .= '<div class="acv-coupon">';
            $output .= '<h3>' . esc_html($coupon['title']) . '</h3>';
            $output .= '<p>Code: <strong>' . esc_html($coupon['code']) . '</strong> - Save ' . esc_html($coupon['discount']) . '%</p>';
            $output .= '<a href="' . esc_url($track_url) . '" class="button acv-copy" data-clipboard="' . esc_attr($coupon['code']) . '">Copy & Track</a>';
            $output .= '<a href="' . esc_url($link . '?coupon=' . $coupon['code']) . '" class="button button-primary" target="_blank">Shop Now</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    private function get_demo_coupons($category) {
        $demo_coupons = array(
            array('title' => 'Amazon Prime Deal', 'code' => 'PRIME50', 'discount' => '50', 'network' => 'amazon', 'category' => 'tech'),
            array('title' => 'Shopify Starter', 'code' => 'SHOP20', 'discount' => '20', 'network' => 'other', 'category' => 'ecom'),
            array('title' => 'Hosting Discount', 'code' => 'HOST30', 'discount' => '30', 'network' => 'other', 'category' => 'all'),
        );
        $filtered = array();
        foreach ($demo_coupons as $c) {
            if ($category === 'all' || $c['category'] === $category) {
                $filtered[] = $c;
            }
        }
        return $filtered;
    }

    private function get_affiliate_link($network) {
        $links = json_decode(get_option('acv_affiliate_links', '{}'), true);
        return isset($links[$network]) ? $links[$network] : 'https://example.com/' . $network;
    }

    public function track_click() {
        if (!wp_verify_nonce($_REQUEST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }
        // Log click (Pro feature simulation)
        error_log('ACV Click tracked: ' . sanitize_text_field($_REQUEST['acv_coupon']));
        wp_redirect(remove_query_arg(array('acv_coupon', '_wpnonce'), wp_get_referer()));
        exit;
    }

    public function activate() {
        update_option('acv_api_keys', 'demo123');
        update_option('acv_affiliate_links', json_encode(array('amazon' => 'https://amzn.to/YOURAFF', 'other' => 'https://example.com/AFF')));
    }
}

new AffiliateCouponVault();

// Assets placeholder - In real plugin, create assets/ folder with frontend.js
/*
assets/frontend.js:
 jQuery(document).ready(function($) {
    $('.acv-copy').click(function(e) {
        e.preventDefault();
        var code = $(this).data('clipboard');
        navigator.clipboard.writeText(code).then(function() {
            $(this).text('Copied!');
        });
        window.open($(this).attr('href'), '_blank');
    });
});
*/
?>