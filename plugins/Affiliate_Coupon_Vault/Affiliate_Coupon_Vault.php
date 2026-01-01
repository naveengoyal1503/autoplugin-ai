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

if (!defined('ABSPATH')) exit;

class AffiliateCouponVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_acv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features would go here
        }
        register_post_type('acv_coupon', array(
            'labels' => array('name' => 'Coupons', 'singular_name' => 'Coupon'),
            'public' => false,
            'show_ui' => true,
            'capability_type' => 'post',
            'supports' => array('title')
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
            'affiliate_link' => '',
            'discount' => '10%',
            'expires' => '+30 days'
        ), $atts);

        $coupon_code = 'ACV' . wp_generate_uuid4() . substr(md5(time()), 0, 4);
        $expires = strtotime($atts['expires']);
        $tracking_id = uniqid('acv_');

        ob_start();
        ?>
        <div class="acv-coupon" data-tracking="<?php echo esc_attr($tracking_id); ?>">
            <h3>Exclusive Deal: <strong><?php echo esc_html($atts['discount']); ?> OFF</strong></h3>
            <p>Use code: <span class="coupon-code"><?php echo esc_html($coupon_code); ?></span></p>
            <p>Expires: <?php echo date('M j, Y', $expires); ?></p>
            <a href="<?php echo esc_url($atts['affiliate_link'] . (strpos($atts['affiliate_link'], '?') ? '&' : '?') . 'coupon=' . $coupon_code . '&tid=' . $tracking_id); ?>" class="acv-button" target="_blank">Shop Now & Save</a>
            <button class="acv-copy" data-code="<?php echo esc_attr($coupon_code); ?>">Copy Code</button>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.acv-copy').click(function() {
                navigator.clipboard.writeText($(this).data('code'));
                $(this).text('Copied!');
            });
            $('.acv-coupon').click(function() {
                gtag('event', 'coupon_click', {'coupon_id': '<?php echo esc_js($tracking_id); ?>'});
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) wp_die();
        $coupons = get_posts(array('post_type' => 'acv_coupon', 'numberposts' => -1));
        wp_send_json_success(array('count' => count($coupons)));
    }
}

new AffiliateCouponVault();

// Admin menu
add_action('admin_menu', function() {
    add_menu_page('Coupons', 'Coupons', 'manage_options', 'acv-coupons', function() {
        echo '<h1>Affiliate Coupon Vault</h1><p>Create new coupons via shortcode [affiliate_coupon].</p>';
    });
});

// Sample JS - save as acv.js in plugin folder
/*
(function($) {
    $(document).on('click', '.acv-generate', function() {
        $.post(acv_ajax.ajax_url, {action: 'acv_generate_coupon', nonce: 'dummy'}, function(res) {
            alert('Generated: ' + res.data.count + ' coupons');
        });
    });
})(jQuery);
*/