/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/ai-coupon-affiliate-pro
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost your commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ai_coupon_api_key') === false) {
            add_option('ai_coupon_api_key', '');
        }
        if (get_option('ai_coupon_affiliate_ids') === false) {
            add_option('ai_coupon_affiliate_ids', json_encode(array(
                'amazon' => 'your-amazon-id',
                'other' => 'your-other-id'
            )));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'affiliate' => 'amazon'
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-container" data-niche="<?php echo esc_attr($atts['niche']); ?>" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <button id="generate-coupon" class="button-coupon">Generate AI Coupon</button>
            <div id="coupon-output"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        $niche = sanitize_text_field($_POST['niche'] ?? 'general');
        $affiliate = sanitize_text_field($_POST['affiliate'] ?? 'amazon');

        // Mock AI generation (replace with real OpenAI API in pro version)
        $products = array(
            'general' => array('50% off Electronics', '20% off Fashion'),
            'tech' => array('Buy 1 Get 1 Laptop', 'Free Shipping on Gadgets'),
            'fashion' => array('30% off Dresses', 'BOGO Shoes')
        );

        $coupons = $products[$niche] ?? $products['general'];
        $coupon = $coupons[array_rand($coupons)];
        $affiliate_ids = json_decode(get_option('ai_coupon_affiliate_ids'), true);
        $link = 'https://affiliate-link.example.com/' . $affiliate . '?coupon=' . urlencode($coupon) . '&ref=' . ($affiliate_ids[$affiliate] ?? '');

        wp_send_json_success(array(
            'coupon' => $coupon,
            'link' => $link,
            'expiry' => date('Y-m-d', strtotime('+7 days')),
            'pro' => empty(get_option('ai_coupon_pro')) ? 'Upgrade for real AI & tracking' : 'Pro active'
        ));
    }

    public function activate() {
        add_option('ai_coupon_pro', false);
        flush_rewrite_rules();
    }
}

new AICouponAffiliatePro();

// Admin settings page
add_action('admin_menu', function() {
    add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon', 'ai_coupon_settings_page');
});

function ai_coupon_settings_page() {
    if (isset($_POST['ai_coupon_api_key'])) {
        update_option('ai_coupon_api_key', sanitize_text_field($_POST['ai_coupon_api_key']));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    if (isset($_POST['go_pro'])) {
        update_option('ai_coupon_pro', true);
        echo '<div class="notice notice-success"><p>Pro activated! (Demo)</p></div>';
    }
    $api_key = get_option('ai_coupon_api_key');
    $aff_ids = json_decode(get_option('ai_coupon_affiliate_ids'), true);
    ?>
    <div class="wrap">
        <h1>AI Coupon Affiliate Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>OpenAI API Key (Pro)</th>
                    <td><input type="text" name="ai_coupon_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th>Affiliate IDs (JSON)</th>
                    <td><textarea name="ai_coupon_affiliate_ids" class="large-text"><?php echo esc_textarea(json_encode($aff_ids)); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <hr>
        <form method="post">
            <p><input type="submit" name="go_pro" class="button button-primary" value="Activate Pro Version (Demo)" /></p>
        </form>
        <p><strong>Pro Features:</strong> Real AI coupon generation, click tracking, analytics dashboard. <a href="https://example.com/pricing">Upgrade Now</a></p>
    </div>
    <?php
}

// Inline JS and CSS for single file
add_action('wp_head', function() {
    echo '<style>
    #ai-coupon-container { max-width: 400px; margin: 20px 0; }
    .button-coupon { background: #0073aa; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; }
    .button-coupon:hover { background: #005a87; }
    #coupon-output { margin-top: 15px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
    .coupon-code { font-size: 18px; font-weight: bold; color: #0073aa; }
    .coupon-link { display: inline-block; margin-top: 10px; padding: 10px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; }
    </style>';

    echo '<script>jQuery(document).ready(function($) {
        $("#generate-coupon").click(function() {
            var container = $("#ai-coupon-container");
            var data = { action: "generate_coupon", niche: container.data("niche"), affiliate: container.data("affiliate") };
            $("#coupon-output").html("<p>Generating...</p>");
            $.post(ajax_object.ajax_url, data, function(response) {
                if (response.success) {
                    var r = response.data;
                    $("#coupon-output").html(
                        "<div class=\"coupon-code\">" + r.coupon + "</div>" +
                        "<p><strong>Expires:</strong> " + r.expiry + "</p>" +
                        "<a href=\"" + r.link + "\" class=\"coupon-link\" target=\"_blank\">Grab Deal (Affiliate)</a>" +
                        "<p style=\"font-size:12px;color:#666;\">" + r.pro + "</p>"
                    );
                }
            });
        });
    });</script>';
});