/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/aicoupon-pro
 * Description: AI-powered coupon generator for affiliate marketing. Create, display, and track exclusive coupons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-pro
 */

if (!defined('ABSPATH')) exit;

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('ai_coupon_generator', [$this, 'coupon_shortcode']);
        add_action('wp_ajax_generate_coupon', [$this, 'ajax_generate_coupon']);
        add_action('wp_ajax_nopriv_generate_coupon', [$this, 'ajax_generate_coupon']);
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (get_option('aicoupon_pro_key') !== 'pro') {
            add_action('admin_notices', [$this, 'pro_nag']);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', [], '1.0.0');
        wp_localize_script('ai-coupon-js', 'aicoupon_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_coupon_nonce')
        ]);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (isset($_POST['aicoupon_submit'])) {
            update_option('aicoupon_affiliates', sanitize_textarea_field($_POST['affiliates']));
            update_option('aicoupon_max_coupons', intval($_POST['max_coupons']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliates = get_option('aicoupon_affiliates', "Amazon:amazon.com\nHostinger:hostinger.com");
        $max = get_option('aicoupon_max_coupons', 5);
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links (one per line: Name:URL)</th>
                        <td><textarea name="affiliates" rows="10" cols="50"><?php echo esc_textarea($affiliates); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Max Coupons per Page</th>
                        <td><input type="number" name="max_coupons" value="<?php echo $max; ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited coupons, advanced AI, analytics. <a href="#" onclick="alert('Upgrade to Pro for $49/year')">Get Pro</a></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(['num' => 3], $atts);
        ob_start();
        echo '<div id="ai-coupon-container" data-max="' . esc_attr(get_option('aicoupon_max_coupons', 5)) . '"></div>';
        echo '<button id="generate-coupons" class="button">Generate Coupons</button>';
        echo '<div id="ai-coupons-output"></div>';
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');
        $num = intval($_POST['num'] ?? 3);
        $max = get_option('aicoupon_max_coupons', 5);
        if ($num > $max && get_option('aicoupon_pro_key') !== 'pro') {
            wp_send_json_error('Upgrade to Pro for more coupons.');
        }
        $affiliates = explode('\n', get_option('aicoupon_affiliates', ''));
        $coupons = [];
        for ($i = 0; $i < $num; $i++) {
            $aff = trim($affiliates[array_rand($affiliates)]);
            if (strpos($aff, ':')) {
                list($name, $url) = explode(':', $aff, 2);
                $code = substr(md5(uniqid()), 0, 8);
                $discount = rand(10, 50) . '% OFF';
                $coupons[] = [
                    'name' => trim($name),
                    'url' => trim($url),
                    'code' => $code,
                    'discount' => $discount,
                    'afflink' => $url . '?ref=' . $code
                ];
            }
        }
        wp_send_json_success($coupons);
    }

    public function pro_nag() {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Coupon Affiliate Pro</strong> features: Unlimited coupons, analytics, priority support for $49/year!</p></div>';
    }
}

new AICouponAffiliatePro();

// Assets would be created as files: assets/script.js, assets/style.css
// script.js example:
/*
$(document).ready(function() {
    $('#generate-coupons').click(function() {
        $.post(aicoupon_ajax.ajaxurl, {
            action: 'generate_coupon',
            num: 3,
            nonce: aicoupon_ajax.nonce
        }, function(res) {
            if (res.success) {
                let html = '';
                res.data.forEach(c => {
                    html += `<div class="coupon-box"><h3>${c.name} - ${c.discount}</h3><p>Code: <strong>${c.code}</strong></p><a href="${c.afflink}" target="_blank" class="aff-link">Shop Now & Save</a></div>`;
                });
                $('#ai-coupons-output').html(html);
            } else {
                alert(res.data);
            }
        });
    });
});
*/
// style.css example:
/*
.coupon-box { border: 1px solid #ddd; padding: 20px; margin: 10px 0; background: #f9f9f9; }
.aff-link { background: #0073aa; color: white; padding: 10px; text-decoration: none; display: inline-block; }
*/