/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking for WordPress blogs.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_links'));
    }

    public function activate() {
        add_option('acv_coupons', array());
        add_option('acv_pro', false);
    }

    public function deactivate() {}

    public function enqueue_scripts() {
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'afflink' => '',
            'code' => 'SAVE10',
            'desc' => 'Exclusive 10% off!',
            'expiry' => date('Y-m-d', strtotime('+30 days'))
        ), $atts);

        $coupons = get_option('acv_coupons', array());
        if (isset($coupons[$atts['id']])) {
            $coupon = $coupons[$atts['id']];
        } else {
            $coupon = $atts;
        }

        $tracking_id = uniqid('acv_');
        $click_url = add_query_arg(array('acv_track' => $tracking_id, 'ref' => 'wp'), $coupon['afflink']);

        ob_start();
        ?>
        <div class="acv-coupon-widget" data-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="acv-coupon-code"><?php echo esc_html($coupon['code']); ?></div>
            <p class="acv-description"><?php echo esc_html($coupon['desc']); ?></p>
            <p class="acv-expiry">Expires: <?php echo esc_html($coupon['expiry']); ?></p>
            <a href="<?php echo esc_url($click_url); ?>" class="acv-button" target="_blank" rel="nofollow">Get Deal Now (<?php echo wp_create_nonce('acv_click'); ?>)</a>
            <div class="acv-stats">Clicks: <span class="acv-clicks">0</span></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('acv_settings', 'acv_coupons');
        register_setting('acv_settings', 'acv_pro');
    }

    public function settings_page() {
        if (isset($_GET['acv_track'])) {
            update_option('acv_total_clicks', (get_option('acv_total_clicks', 0) + 1));
            wp_redirect(remove_query_arg('acv_track'));
            exit;
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('acv_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Coupons</th>
                        <td><textarea name="acv_coupons" rows="10" cols="50"><?php echo esc_textarea(json_encode(get_option('acv_coupons', array()), JSON_PRETTY_PRINT)); ?></textarea><br>
                        Format: {"1":{"afflink":"https://example.com","code":"SAVE20","desc":"20% Off","expiry":"2026-12-31"}}</td>
                    </tr>
                    <tr>
                        <th>Pro Version</th>
                        <td><label><input type="checkbox" name="acv_pro" value="1" <?php checked(get_option('acv_pro')); ?>> Unlock Pro Features</label><br>
                        <a href="https://example.com/pro" target="_blank">Upgrade to Pro ($49/year)</a></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode: [affiliate_coupon id="1"]</p>
            <p>Total Clicks: <strong><?php echo get_option('acv_total_clicks', 0); ?></strong></p>
        </div>
        <?php
    }

    public function plugin_links($links) {
        $links[] = '<a href="options-general.php?page=acv-settings">Settings</a>';
        $links[] = '<a href="https://example.com/pro" target="_blank">Pro</a>';
        return $links;
    }
}

AffiliateCouponVault::get_instance();

// Pro Upsell Notice
function acv_pro_notice() {
    if (!get_option('acv_pro') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>Affiliate Coupon Vault Pro</strong>: Unlimited coupons, analytics & more! <a href="options-general.php?page=acv-settings">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'acv_pro_notice');

/* CSS */
function acv_add_inline_css() {
    echo '<style>
        .acv-coupon-widget { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; border-radius: 10px; }
        .acv-coupon-code { font-size: 2em; font-weight: bold; color: #007cba; background: white; padding: 10px; display: inline-block; margin-bottom: 10px; }
        .acv-button { background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .acv-button:hover { background: #005a87; }
        .acv-stats { margin-top: 10px; font-size: 0.9em; }
    </style>';
}
add_action('wp_head', 'acv_add_inline_css');
add_action('admin_head', 'acv_add_inline_css');

/* JS for click tracking */
function acv_add_inline_js() {
    echo '<script>jQuery(document).ready(function($) { $(".acv-button").click(function(e) { var widget = $(this).closest(".acv-coupon-widget"); var clicks = parseInt(widget.find(".acv-clicks").text()); widget.find(".acv-clicks").text(clicks + 1); }); });</script>';
}
add_action('wp_footer', 'acv_add_inline_js');