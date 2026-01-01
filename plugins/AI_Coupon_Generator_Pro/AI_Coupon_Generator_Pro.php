/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/aicouponpro
 * Description: Automatically generates unique, personalized coupon codes for your WordPress site, tracks usage, and enables affiliate commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGeneratorPro {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'affiliate' => '',
            'discount' => '10',
            'expiry' => '+7 days'
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-container" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>" data-discount="<?php echo esc_attr($atts['discount']); ?>" data-expiry="<?php echo esc_attr($atts['expiry']); ?>">
            <button id="generate-coupon-btn">Get Your Exclusive Coupon!</button>
            <div id="coupon-result" style="display:none;">
                <p>Your code: <strong id="coupon-code"></strong></p>
                <p>Discount: <span id="coupon-discount"></span>% off</p>
                <p>Expires: <span id="coupon-expiry"></span></p>
                <a id="affiliate-link" href="#" target="_blank">Shop Now & Save</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');

        $affiliate = sanitize_text_field($_POST['affiliate'] ?? '');
        $discount = intval($_POST['discount'] ?? 10);
        $expiry = sanitize_text_field($_POST['expiry'] ?? '+7 days');

        // Generate unique coupon code
        $coupon_code = 'AIC-' . wp_generate_uuid4() . substr(md5(uniqid()), 0, 8);
        $expires = strtotime($expiry);

        // Store in options (simple DB for demo)
        $coupons = get_option('ai_coupons', array());
        $coupons[$coupon_code] = array(
            'affiliate' => $affiliate,
            'discount' => $discount,
            'expires' => $expires,
            'used' => false,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'date' => current_time('mysql')
        );
        update_option('ai_coupons', $coupons);

        wp_send_json_success(array(
            'code' => $coupon_code,
            'discount' => $discount,
            'expiry' => date('Y-m-d', $expires),
            'affiliate_link' => $affiliate ?: '#'
        ));
    }

    public function admin_menu() {
        add_options_page(
            'AI Coupon Pro',
            'AI Coupon Pro',
            'manage_options',
            'ai-coupon-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['pro_upgrade'])) {
            // Simulate pro upgrade check
            update_option('ai_coupon_pro', true);
            echo '<div class="notice notice-success"><p>Pro activated! (Demo)</p></div>';
        }
        $is_pro = get_option('ai_coupon_pro', false);
        $coupons = get_option('ai_coupons', array());
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Pro</h1>
            <?php if (!$is_pro): ?>
            <form method="post">
                <p>Upgrade to Pro for unlimited coupons & analytics ($49/year).</p>
                <input type="submit" name="pro_upgrade" value="Activate Pro (Demo)" class="button-primary">
            </form>
            <?php endif; ?>
            <h2>Generated Coupons (<?php echo count($coupons); ?>)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Code</th><th>Discount</th><th>Expires</th><th>Used</th></tr></thead>
                <tbody>
                <?php foreach ($coupons as $code => $data): ?>
                <tr>
                    <td><?php echo esc_html($code); ?></td>
                    <td><?php echo esc_html($data['discount']); ?>%</td>
                    <td><?php echo esc_html(date('Y-m-d', $data['expires'])); ?></td>
                    <td><?php echo $data['used'] ? 'Yes' : 'No'; ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function activate() {
        // Create empty coupons array
        add_option('ai_coupons', array());
    }
}

// Include JS file content inline for single-file
$js_content = "jQuery(document).ready(function($) {
    $('#generate-coupon-btn').click(function() {
        var container = $('#ai-coupon-container');
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'generate_coupon',
                affiliate: container.data('affiliate'),
                discount: container.data('discount'),
                expiry: container.data('expiry'),
                nonce: ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#coupon-code').text(response.data.code);
                    $('#coupon-discount').text(response.data.discount);
                    $('#coupon-expiry').text(response.data.expiry);
                    $('#affiliate-link').attr('href', response.data.affiliate_link);
                    $('#coupon-result').show();
                    $('#generate-coupon-btn').hide();
                }
            }
        });
    });
});";

/* Inline JS enqueue */
function ai_coupon_inline_js() {
    if (has_shortcode(get_post()->post_content, 'ai_coupon_generator')) {
        wp_add_inline_script('jquery', $js_content);
    }
}
add_action('wp_enqueue_scripts', 'ai_coupon_inline_js');

AICouponGeneratorPro::get_instance();

// Track usage (simple hook)
add_action('wp', function() {
    if (isset($_GET['coupon_used'])) {
        $code = sanitize_text_field($_GET['coupon_used']);
        $coupons = get_option('ai_coupons', array());
        if (isset($coupons[$code])) {
            $coupons[$code]['used'] = true;
            update_option('ai_coupons', $coupons);
        }
    }
});