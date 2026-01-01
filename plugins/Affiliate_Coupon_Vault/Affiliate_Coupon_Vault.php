/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized discount codes, boosting conversions and commissions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_options');
        add_settings_section('acv_main', 'Main Settings', null, 'acv');
        add_settings_field('acv_affiliate_links', 'Affiliate Links (JSON)', array($this, 'affiliate_links_field'), 'acv', 'acv_main');
        add_settings_field('acv_pro_key', 'Pro License Key', array($this, 'pro_key_field'), 'acv', 'acv_main');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, analytics & more for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function affiliate_links_field() {
        $options = get_option('acv_options', array('links' => '[]'));
        echo '<textarea name="acv_options[links]" rows="10" cols="50">' . esc_textarea($options['links']) . '</textarea>';
        echo '<p class="description">Enter JSON array of affiliate links: [{"name":"Product","url":"https://aff.link","discount":"10% OFF"}]</p>';
    }

    public function pro_key_field() {
        $options = get_option('acv_options', array());
        echo '<input type="text" name="acv_options[pro_key]" value="' . esc_attr($options['pro_key'] ?? '') . '" />';
        echo '<p class="description">Enter Pro key for premium features. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>';
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $options = get_option('acv_options', array('links' => '[]'));
        $links = json_decode($options['links'], true) ?: array();
        if (empty($links)) return '<p>No coupons configured.</p>';

        $coupon = $links[array_rand($links)];
        $code = $this->generate_unique_code();

        ob_start();
        ?>
        <div id="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; background: #f9f9f9; text-align: center;">
            <h3>Exclusive Deal: <?php echo esc_html($coupon['name']); ?></h3>
            <p><strong><?php echo esc_html($coupon['discount']); ?></strong></p>
            <p>Your Code: <code><?php echo $code; ?></code></p>
            <a href="<?php echo esc_url($coupon['url']) . '?code=' . $code; ?>" class="button button-large" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Redeem Now & Track Commission</a>
            <p style="font-size: 12px; margin-top: 10px;">Limited time offer! Generated for you.</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#acv-coupon .button').click(function() {
                $.post(acv_ajax.ajaxurl, {action: 'acv_generate_coupon', code: '<?php echo $code; ?>'});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'acv_nonce')) wp_die();
        $is_pro = !empty(get_option('acv_options')['pro_key']);
        if (!$is_pro && ($_POST['count'] ?? 0) > 5) {
            wp_send_json_error('Upgrade to Pro for unlimited coupons.');
        }
        wp_send_json_success(array('code' => $this->generate_unique_code()));
    }

    private function generate_unique_code() {
        return substr(md5(uniqid(rand(), true)), 0, 8);
    }
}

new AffiliateCouponVault();

// Pro teaser notice
function acv_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_affiliate-coupon-vault') return;
    echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons & analytics! <a href="' . admin_url('options-general.php?page=affiliate-coupon-vault') . '">Upgrade Now ($49/yr)</a></p></div>';
}
add_action('admin_notices', 'acv_admin_notice');

// Minified JS (self-contained)
function acv_inline_js() {
    ?><script>jQuery(document).ready(function($){console.log('Affiliate Coupon Vault loaded');});</script><?php
}
add_action('wp_footer', 'acv_inline_js');