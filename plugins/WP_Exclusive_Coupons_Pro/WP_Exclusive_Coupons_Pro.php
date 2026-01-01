/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Exclusive_Coupons_Pro.php
*/
<?php
/**
 * Plugin Name: WP Exclusive Coupons Pro
 * Plugin URI: https://example.com/wp-exclusive-coupons-pro
 * Description: Automatically generates, manages, and displays exclusive affiliate coupons with personalized promo codes to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-exclusive-coupons-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Exclusive_Coupons_Pro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('wpec_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpec-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('wpec-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('WP Exclusive Coupons Pro', 'Coupons Pro', 'manage_options', 'wpec-pro', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('wpec_options', 'wpec_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Exclusive Coupons Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wpec_options'); ?>
                <?php do_settings_sections('wpec_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="wpec_settings[affiliates]" rows="5" cols="50" placeholder="https://affiliate.link1?coupon=CODE1&#10;https://affiliate.link2?coupon=CODE2"><?php echo esc_textarea(get_option('wpec_settings')['affiliates'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Default Discount %</th>
                        <td><input type="number" name="wpec_settings[discount]" value="<?php echo esc_attr(get_option('wpec_settings')['discount'] ?? 20); ?>" /> %</td>
                    </tr>
                    <tr>
                        <th>Pro Upgrade</th>
                        <td><a href="https://example.com/upgrade" target="_blank" class="button button-primary">Upgrade to Pro ($49/year)</a> for unlimited coupons & analytics.</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $settings = get_option('wpec_settings', array());
        $affiliates = explode("\n", $settings['affiliates'] ?? '');
        $affiliate = $affiliates[array_rand($affiliates)] ?? '';
        $code = 'SAVE' . wp_generate_uuid4() . substr(md5(time()), 0, 4);
        $discount = $settings['discount'] ?? 20;
        $link = str_replace('CODE', $code, $affiliate);

        ob_start();
        ?>
        <div class="wpec-coupon" data-link="<?php echo esc_url($link); ?>">
            <h3>Exclusive Deal: <strong><?php echo $discount; ?>% OFF</strong></h3>
            <p>Use code: <span class="wpec-code"><?php echo $code; ?></span></p>
            <a href="#" class="wpec-btn button">Copy Code & Shop Now</a>
            <p class="wpec-cta">Limited time offer! Generated just for you.</p>
        </div>
        <script>
        jQuery('.wpec-coupon .wpec-btn').click(function(e) {
            e.preventDefault();
            navigator.clipboard.writeText(jQuery('.wpec-code', this.parentNode).text());
            window.open(jQuery(this.parentNode).data('link'), '_blank');
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        add_option('wpec_settings', array('discount' => 20));
    }
}

new WP_Exclusive_Coupons_Pro();

// Pro teaser notice
add_action('admin_notices', function() {
    if (!get_option('wpec_pro_activated')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>WP Exclusive Coupons Pro</strong> features: Unlimited coupons, analytics & auto-expiration. <a href="https://example.com/upgrade">Upgrade now ($49/year)</a></p></div>';
    }
});

// Minimal CSS
add_action('wp_head', function() {
    echo '<style>.wpec-coupon {border: 2px dashed #0073aa; padding: 20px; text-align: center; background: #f9f9f9; margin: 20px 0;}.wpec-code {font-size: 1.5em; background: #fff; padding: 5px 10px; border: 1px solid #ddd; font-family: monospace;}.wpec-btn {background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;}</style>';
});