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
 * Text Domain: affiliate-coupon-vault
 */

if (!defined('ABSPATH')) {
    exit;
}

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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
        load_plugin_textdomain('affiliate-coupon-vault', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
        wp_enqueue_style('acv-style', plugin_dir_url(__FILE__) . 'acv-style.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate_id' => '',
            'offer' => '20% Off',
            'code' => 'SAVE20',
            'link' => '',
            'expires' => '',
        ), $atts);

        $tracking_id = uniqid('acv_');
        $coupon_code = $atts['code'] ?: 'ACV' . substr(md5($tracking_id), 0, 8);

        ob_start();
        ?>
        <div class="acv-coupon-vault" data-tracking="<?php echo esc_attr($tracking_id); ?>">
            <div class="acv-coupon-code"><?php echo esc_html($coupon_code); ?></div>
            <div class="acv-offer"><?php echo esc_html($atts['offer']); ?></div>
            <a href="<?php echo esc_url($atts['link']); ?><?php echo strpos($atts['link'], '?') === false ? '?' : '&'; ?>ref=<?php echo $tracking_id; ?>" class="acv-button" target="_blank">Get Deal</a>
            <?php if ($atts['expires']) : ?>
            <div class="acv-expires">Expires: <?php echo esc_html($atts['expires']); ?></div>
            <?php endif; ?>
            <div class="acv-stats" style="display:none;">
                <span class="acv-clicks">Clicks: 0</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $offer = sanitize_text_field($_POST['offer']);
        $link = esc_url_raw($_POST['link']);
        $code = sanitize_text_field($_POST['code']);

        $coupon_data = array(
            'offer' => $offer,
            'code' => $code ?: 'ACV' . wp_generate_uuid4(),
            'link' => $link,
            'tracking' => uniqid('acv_'),
            'timestamp' => current_time('mysql'),
        );

        $coupons = get_option('acv_coupons', array());
        $coupons[] = $coupon_data;
        update_option('acv_coupons', $coupons);

        wp_send_json_success($coupon_data);
    }

    public function activate() {
        add_option('acv_version', '1.0.0');
        flush_rewrite_rules();
    }
}

// Custom JS (embedded for single file)
function acv_inline_js() {
    if (!is_admin()) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.acv-coupon-vault .acv-button').on('click', function() {
                var $container = $(this).closest('.acv-coupon-vault');
                var tracking = $container.data('tracking');
                var clicks = parseInt($container.find('.acv-clicks').text().match(/\d+/) || 0) + 1;
                $container.find('.acv-clicks').text('Clicks: ' + clicks).show();
                // Track in free version (Pro: server-side)
                console.log('Coupon clicked: ' + tracking);
            });

            // Generate coupon button if exists
            $('#acv-generate').on('click', function() {
                $.post(acv_ajax.ajax_url, {
                    action: 'acv_generate_coupon',
                    nonce: acv_ajax.nonce,
                    offer: $('#acv-offer').val(),
                    link: $('#acv-link').val(),
                    code: $('#acv-code').val()
                }, function(response) {
                    if (response.success) {
                        alert('Coupon generated: ' + response.data.code);
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'acv_inline_js');

// Custom CSS (embedded)
function acv_inline_css() {
    echo '<style>
    .acv-coupon-vault { border: 2px dashed #007cba; padding: 20px; margin: 20px 0; text-align: center; background: #f9f9f9; border-radius: 10px; }
    .acv-coupon-code { font-size: 2em; font-weight: bold; color: #007cba; background: white; padding: 10px; display: inline-block; margin-bottom: 10px; }
    .acv-offer { font-size: 1.2em; margin-bottom: 10px; }
    .acv-button { background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
    .acv-button:hover { background: #005a87; }
    .acv-expires { font-size: 0.9em; color: #666; margin-top: 10px; }
    .acv-stats { font-size: 0.8em; color: #007cba; margin-top: 10px; }
    #acv-admin { margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ddd; }
    </style>';
}
add_action('wp_head', 'acv_inline_css');

// Admin page
add_action('admin_menu', function() {
    add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv', 'acv_admin_page');
});

function acv_admin_page() {
    if (isset($_POST['submit'])) {
        update_option('acv_pro_version', sanitize_text_field($_POST['pro_version']));
        echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
    }
    $pro = get_option('acv_pro_version');
    echo '<div class="wrap"><h1>Affiliate Coupon Vault</h1>
    <form method="post"><table class="form-table">
    <tr><th>Pro License Key</th><td><input type="text" name="pro_version" value="' . esc_attr($pro) . '" /></td></tr>
    </table><p><input type="submit" name="submit" class="button-primary" value="Save" /></p></form>
    <h2>Usage</h2><p>Use shortcode: [affiliate_coupon affiliate_id="" offer="20% Off" code="SAVE20" link="https://example.com" expires="2026-12-31"]</p>
    <h2>Generate New</h2>
    <div id="acv-admin">
    <input id="acv-offer" placeholder="Offer (e.g. 20% Off)" /><br>
    <input id="acv-link" placeholder="Affiliate Link" /><br>
    <input id="acv-code" placeholder="Coupon Code" /><br>
    <button id="acv-generate">Generate Coupon</button>
    </div></div>';
}

AffiliateCouponVault::get_instance();

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('acv_pro_version') && current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Affiliate Coupon Vault Pro:</strong> Unlock unlimited coupons, analytics, and automation for $49/year. <a href="options-general.php?page=acv">Upgrade now</a></p></div>';
    }
});