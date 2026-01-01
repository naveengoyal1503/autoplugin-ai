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
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features
        }
        add_menu_page('Coupon Vault', 'Coupon Vault', 'manage_options', 'affiliate-coupon-vault', array($this, 'admin_page'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acv_nonce')));
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>Affiliate Coupon Vault</h1><form method="post" action="options.php">';
        settings_fields('acv_settings');
        do_settings_sections('acv_settings');
        submit_button();
        echo '</form></div>';
    }

    public function activate() {
        add_option('acv_api_key', '');
        add_option('acv_affiliate_links', array());
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 'default',
            'affiliate' => ''
        ), $atts);

        ob_start();
        ?>
        <div id="acv-coupon-<?php echo esc_attr($atts['id']); ?>" class="acv-coupon-container">
            <div class="acv-coupon-code">Loading coupon...</div>
            <button class="acv-generate-btn" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">Generate Coupon</button>
            <a class="acv-affiliate-link" href="#" target="_blank" style="display:none;">Shop Now</a>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('acv_nonce', 'nonce');
        $affiliate = sanitize_text_field($_POST['affiliate']);
        $code = 'SAVE' . wp_rand(10, 99) . substr(md5(time()), 0, 4);
        $link = get_option('acv_affiliate_links')[$affiliate] ?? '#';
        $tracked_link = add_query_arg(array('ref' => get_option('acv_api_key'), 'coupon' => $code), $link);

        wp_send_json_success(array(
            'code' => $code,
            'link' => $tracked_link,
            'discount' => '20% OFF'
        ));
    }
}

new AffiliateCouponVault();

// Settings
add_action('admin_init', 'acv_admin_init');
function acv_admin_init() {
    register_setting('acv_settings', 'acv_api_key');
    register_setting('acv_settings', 'acv_affiliate_links');

    add_settings_section('acv_main', 'Main Settings', null, 'acv_settings');
    add_settings_field('acv_api_key', 'API Key', 'acv_api_key_cb', 'acv_settings', 'acv_main');
    add_settings_field('acv_links', 'Affiliate Links', 'acv_links_cb', 'acv_settings', 'acv_main');
}

function acv_api_key_cb() {
    $value = get_option('acv_api_key');
    echo '<input type="text" name="acv_api_key" value="' . esc_attr($value) . '" />';
}

function acv_links_cb() {
    $links = get_option('acv_affiliate_links', array());
    echo '<textarea name="acv_affiliate_links" rows="10" cols="50">' . esc_textarea(json_encode($links)) . '</textarea><p>JSON format: {"brand":"https://affiliate.link"}</p>';
}

// Sample JS file content (save as acv.js in plugin folder)
/*
jQuery(document).ready(function($) {
    $('.acv-generate-btn').click(function() {
        var $container = $(this).closest('.acv-coupon-container');
        $.post(acv_ajax.ajax_url, {
            action: 'acv_generate_coupon',
            nonce: acv_ajax.nonce,
            affiliate: $(this).data('affiliate')
        }, function(response) {
            if (response.success) {
                $container.find('.acv-coupon-code').text(response.data.code + ' (' + response.data.discount + ')');
                $container.find('.acv-affiliate-link').attr('href', response.data.link).show();
                $(this).hide();
            }
        });
    });
});
*/
?>