/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupon codes and deals to boost conversions.
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
        add_shortcode('affiliate_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-coupon-vault-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('affiliate-coupon-vault-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'discount' => '10%',
            'expires' => date('Y-m-d', strtotime('+30 days')),
            'product' => 'Featured Product',
        ), $atts);

        $options = get_option('affiliate_coupon_vault_options', array());
        $base_url = !empty($options['affiliate_url']) ? $options['affiliate_url'] : '#';
        $tracking_id = !empty($options['tracking_id']) ? $options['tracking_id'] : '';

        $coupon_code = $this->generate_coupon_code($atts['affiliate']);
        $link = $base_url . '?coupon=' . $coupon_code . ($tracking_id ? '&ref=' . $tracking_id : '');

        ob_start();
        ?>
        <div class="affiliate-coupon-vault" data-product="<?php echo esc_attr($atts['product']); ?>">
            <h3>Exclusive Deal: <?php echo esc_html($atts['discount']); ?> OFF <?php echo esc_html($atts['product']); ?>!</h3>
            <p>Use code: <strong><?php echo esc_html($coupon_code); ?></strong></p>
            <p>Expires: <?php echo esc_html($atts['expires']); ?></p>
            <a href="<?php echo esc_url($link); ?>" class="coupon-button" target="_blank">Get Deal Now</a>
            <div class="coupon-copy">Copied!</div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($affiliate) {
        $prefix = strtoupper(substr($affiliate, 0, 3));
        return $prefix . '-' . wp_generate_uuid4() . substr(md5(current_time('timestamp')), 0, 4);
    }

    public function admin_menu() {
        add_options_page(
            'Affiliate Coupon Vault Settings',
            'Coupon Vault',
            'manage_options',
            'affiliate-coupon-vault',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('affiliate_coupon_vault_options', 'affiliate_coupon_vault_options');
        add_settings_section('main_section', 'Main Settings', null, 'affiliate-coupon-vault');
        add_settings_field('affiliate_url', 'Affiliate Base URL', array($this, 'affiliate_url_field'), 'affiliate-coupon-vault', 'main_section');
        add_settings_field('tracking_id', 'Tracking ID', array($this, 'tracking_id_field'), 'affiliate-coupon-vault', 'main_section');
    }

    public function affiliate_url_field() {
        $options = get_option('affiliate_coupon_vault_options', array());
        echo '<input type="url" name="affiliate_coupon_vault_options[affiliate_url]" value="' . esc_attr($options['affiliate_url']) . '" class="regular-text" />';
        echo '<p class="description">Base URL for affiliate links (e.g., https://affiliate.com/shop).</p>';
    }

    public function tracking_id_field() {
        $options = get_option('affiliate_coupon_vault_options', array());
        echo '<input type="text" name="affiliate_coupon_vault_options[tracking_id]" value="' . esc_attr($options['tracking_id']) . '" class="regular-text" />';
        echo '<p class="description">Your affiliate tracking ID or ref code.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('affiliate_coupon_vault_options');
                do_settings_sections('affiliate-coupon-vault');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: <code>[affiliate_coupon_vault affiliate="amazon" discount="20%" expires="2026-02-01" product="Laptop"]</code></p>
            <p><strong>Pro Version:</strong> Unlimited coupons, analytics, API integrations. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('affiliate_coupon_vault_options', array());
    }
}

// Create assets directories and files on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
    $css_content = '.affiliate-coupon-vault { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; }.coupon-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }.coupon-copy { display: none; color: green; margin-top: 10px; }';
    file_put_contents($upload_dir . '/style.css', $css_content);
    $js_content = 'jQuery(document).ready(function($) { $(".coupon-button").on("click", function() { var code = $(this).closest(".affiliate-coupon-vault").find("strong").text(); navigator.clipboard.writeText(code).then(function() { $(".coupon-copy").fadeIn().delay(2000).fadeOut(); }); }); });';
    file_put_contents($upload_dir . '/script.js', $js_content);
});

AffiliateCouponVault::get_instance();
