/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with tracking to boost your commissions.
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
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
        register_setting('acv_settings', 'acv_api_key');
        register_setting('acv_settings', 'acv_affiliate_id');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product' => 'default',
            'affiliate' => get_option('acv_affiliate_id', ''),
        ), $atts);

        ob_start();
        ?>
        <div id="acv-coupon-<?php echo esc_attr($atts['product']); ?>" class="acv-coupon-vault">
            <div class="acv-loading">Loading exclusive coupon...</div>
            <div class="acv-coupon-content" style="display:none;">
                <h3>Exclusive Deal: <span class="acv-discount"></span> OFF</h3>
                <div class="acv-code"></div>
                <a href="#" class="acv-copy-btn">Copy Code</a>
                <a href="#" class="acv-track-link" target="_blank">Shop Now & Save</a>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#acv-coupon-<?php echo esc_js($atts['product']); ?>').on('click', '.acv-copy-btn', function(e) {
                e.preventDefault();
                var code = $(this).siblings('.acv-code').text();
                navigator.clipboard.writeText(code).then(function() {
                    $(this).text('Copied!');
                }.bind(this));
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        $product = sanitize_text_field($_POST['product']);
        $affiliate = get_option('acv_affiliate_id');

        // Simulate coupon generation (integrate with real APIs in pro)
        $coupons = array(
            'default' => array('code' => 'SAVE20', 'discount' => '20%', 'url' => 'https://example.com/deal?aff=' . $affiliate),
            'software' => array('code' => 'WP50', 'discount' => '50%', 'url' => 'https://example.com/software?aff=' . $affiliate),
        );

        $coupon = isset($coupons[$product]) ? $coupons[$product] : $coupons['default'];

        wp_send_json_success($coupon);
    }
}

// Admin settings
if (is_admin()) {
    add_action('admin_menu', function() {
        add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', 'acv_settings_page');
    });
}

function acv_settings_page() {
    ?>
    <div class="wrap">
        <h1>Affiliate Coupon Vault Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('acv_settings'); ?>
            <table class="form-table">
                <tr>
                    <th>Affiliate ID</th>
                    <td><input type="text" name="acv_affiliate_id" value="<?php echo esc_attr(get_option('acv_affiliate_id')); ?>" /></td>
                </tr>
                <tr>
                    <th>API Key (Pro)</th>
                    <td><input type="text" name="acv_api_key" value="<?php echo esc_attr(get_option('acv_api_key')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, real-time API integrations, and analytics for $49/year.</p>
    </div>
    <?php
}

AffiliateCouponVault::get_instance();

// Add CSS
add_action('wp_head', function() {
    echo '<style>
    .acv-coupon-vault { border: 2px solid #0073aa; padding: 20px; border-radius: 10px; text-align: center; background: #f9f9f9; }
    .acv-loading { font-style: italic; color: #666; }
    .acv-discount { color: #e74c3c; font-size: 1.5em; font-weight: bold; }
    .acv-code { background: #fff; padding: 10px; font-family: monospace; font-size: 1.2em; margin: 10px 0; }
    .acv-copy-btn, .acv-track-link { display: inline-block; padding: 10px 20px; margin: 5px; background: #0073aa; color: white; text-decoration: none; border-radius: 5px; }
    .acv-track-link:hover { background: #005a87; }
    </style>';
});

// JS file content (base64 encoded or inline, but for single file, add here)
add_action('wp_footer', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-coupon-vault').each(function() {
            var $container = $(this);
            var product = $container.attr('id').replace('acv-coupon-', '');
            $.post(acv_ajax.ajax_url, {
                action: 'acv_generate_coupon',
                product: product
            }, function(response) {
                if (response.success) {
                    $container.find('.acv-loading').hide();
                    $container.find('.acv-discount').text(response.data.discount);
                    $container.find('.acv-code').text(response.data.code);
                    $container.find('.acv-track-link').attr('href', response.data.url);
                    $container.find('.acv-coupon-content').show();
                }
            });
        });
    });
    </script>
    <?php
});