/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays personalized affiliate coupons with tracking to boost conversions.
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
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $this->load_textdomain();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('acv_settings');
                do_settings_sections('acv_settings');
                submit_button();
                ?>
            </form>
            <h2>Generate Coupon</h2>
            <input type="text" id="coupon_code" placeholder="Enter base coupon code">
            <button id="generate_coupon">Generate Unique Coupon</button>
            <div id="coupon_result"></div>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'code' => '',
            'affiliate_id' => '',
            'discount' => '20%',
            'expires' => '+30 days'
        ), $atts);

        $unique_code = $atts['code'] ? $this->generate_unique_code($atts['code']) : '';
        $expires = strtotime($atts['expires']);
        $tracking_url = add_query_arg(array('ref' => $atts['affiliate_id'], 'coupon' => $unique_code), $this->get_option('default_affiliate_url', ''));

        ob_start();
        ?>
        <div class="acv-coupon" style="border: 2px dashed #007cba; padding: 20px; margin: 20px 0; background: #f9f9f9;">
            <h3>Exclusive Coupon: <strong><?php echo esc_html($unique_code); ?></strong></h3>
            <p>Save <strong><?php echo esc_html($atts['discount']); ?> OFF</strong>! Expires: <?php echo date('Y-m-d', $expires); ?></p>
            <a href="<?php echo esc_url($tracking_url); ?>" target="_blank" class="button button-large" style="background: #007cba; color: white; padding: 10px 20px; text-decoration: none;">Redeem Now & Track</a>
            <p style="font-size: 12px; margin-top: 10px;">Tracked via Affiliate Coupon Vault</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.acv-coupon a').click(function() {
                gtag('event', 'coupon_click', {'coupon_code': '<?php echo esc_js($unique_code); ?>'});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function generate_unique_code($base) {
        return $base . '-' . wp_generate_uuid4() . substr(md5(auth()->user_id ?? ''), 0, 4);
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $base = sanitize_text_field($_POST['base_code']);
        $unique = $this->generate_unique_code($base);
        wp_send_json_success(array('code' => $unique));
    }

    public function get_option($key, $default = '') {
        return get_option('acv_' . $key, $default);
    }

    private function load_textdomain() {
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

// Initialize
AffiliateCouponVault::get_instance();

// Register settings
add_action('admin_init', function() {
    register_setting('acv_settings', 'acv_default_affiliate_url');
    add_settings_section('acv_main', 'Main Settings', null, 'acv_settings');
    add_settings_field('default_affiliate_url', 'Default Affiliate URL', function() {
        $val = get_option('acv_default_affiliate_url', '');
        echo '<input type="url" name="acv_default_affiliate_url" value="' . esc_attr($val) . '" style="width: 100%;">';
    }, 'acv_settings', 'acv_main');
});

// Pro upgrade notice
function acv_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Affiliate Coupon Vault Pro</strong> for unlimited coupons, analytics & more! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
}
add_action('admin_notices', 'acv_pro_notice');

// Embed JS inline for single file
add_action('wp_footer', function() {
    if (!is_admin()) {
        ?>
        <script>jQuery(document).ready(function($){
            $('#generate_coupon').click(function(){
                $.post(acv_ajax.ajax_url, {
                    action: 'acv_generate_coupon',
                    nonce: acv_ajax.nonce,
                    base_code: $('#coupon_code').val()
                }, function(res){
                    if(res.success) $('#coupon_result').html('<p>Generated: <strong>' + res.data.code + '</strong> <small>Copy to shortcode: [affiliate_coupon code="' + res.data.code + '"]</small></p>');
                });
            });
        });</script>
        <?php
    }
});