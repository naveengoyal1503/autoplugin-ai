/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon Vault
 * Plugin URI: https://example.com/affiliate-coupon-vault
 * Description: Automatically generates and displays exclusive affiliate coupons with personalized promo codes, tracking clicks and conversions for bloggers.
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
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('affiliate_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_acv_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_acv_track_click', array($this, 'track_click'));
    }

    public function init() {
        if (get_option('acv_pro_version')) {
            // Pro features would go here
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acv-script', plugin_dir_url(__FILE__) . 'acv-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acv-script', 'acv_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => 'default',
            'code' => 'SAVE10',
            'link' => 'https://example.com',
            'description' => 'Get 10% off with this exclusive coupon!',
            'expiry' => date('Y-m-d', strtotime('+30 days'))
        ), $atts);

        ob_start();
        ?>
        <div class="acv-coupon" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>" data-code="<?php echo esc_attr($atts['code']); ?>">
            <h3><?php echo esc_html($atts['description']); ?></h3>
            <p><strong>Code:</strong> <code><?php echo esc_html($atts['code']); ?></code></p>
            <p><strong>Expires:</strong> <?php echo esc_html($atts['expiry']); ?></p>
            <a href="#" class="acv-copy-code button">Copy Code</a>
            <a href="<?php echo esc_url($atts['link']); ?>" target="_blank" class="acv-track-link button-primary">Redeem Now (Track Affiliate)</a>
            <div class="acv-stats">Clicks: <span class="acv-clicks">0</span></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'acv_nonce')) {
            wp_die('Security check failed');
        }

        $affiliate = sanitize_text_field($_POST['affiliate']);
        $code = sanitize_text_field($_POST['code']);
        $ip = $_SERVER['REMOTE_ADDR'];

        $clicks = get_option('acv_clicks_' . $affiliate, 0);
        update_option('acv_clicks_' . $affiliate, $clicks + 1);

        // In pro version, integrate with affiliate networks
        wp_send_json_success(array('clicks' => $clicks + 1));
    }
}

// Enqueue JS
add_action('wp_footer', function() {
    if (!wp_script_is('acv-script', 'enqueued')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.acv-copy-code').click(function(e) {
            e.preventDefault();
            var code = $(this).closest('.acv-coupon').find('code').text();
            navigator.clipboard.writeText(code).then(function() {
                $(this).text('Copied!');
            }.bind(this));
        });

        $('.acv-track-link').click(function(e) {
            e.preventDefault();
            var $coupon = $(this).closest('.acv-coupon');
            var affiliate = $coupon.data('affiliate');
            var code = $coupon.data('code');

            $.post(acv_ajax.ajax_url, {
                action: 'acv_track_click',
                nonce: '<?php echo wp_create_nonce('acv_nonce'); ?>',
                affiliate: affiliate,
                code: code
            }, function(response) {
                if (response.success) {
                    $coupon.find('.acv-clicks').text(response.data.clicks);
                    window.open($(e.target).attr('href'), '_blank');
                }
            });
        });
    });
    </script>
    <style>
    .acv-coupon { border: 2px dashed #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; border-radius: 8px; }
    .acv-coupon h3 { color: #0073aa; }
    .acv-coupon code { background: #fff; padding: 2px 6px; border-radius: 3px; }
    .acv-coupon .button { display: inline-block; margin: 5px; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
    .acv-coupon .button-primary { background: #0073aa; color: #fff; }
    .acv-stats { margin-top: 10px; font-weight: bold; }
    </style>
    <?php
});

AffiliateCouponVault::get_instance();

// Admin page
add_action('admin_menu', function() {
    add_options_page('Affiliate Coupon Vault', 'Coupon Vault', 'manage_options', 'acv-settings', function() {
        echo '<div class="wrap"><h1>Affiliate Coupon Vault Settings</h1>';
        echo '<p>Upgrade to Pro for advanced tracking and unlimited coupons!</p>';
        settings_fields('acv_options');
        echo '</div>';
    });
});