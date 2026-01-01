/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon
 * Description: Automatically generates and displays personalized affiliate coupons using AI to boost conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIAffiliateCouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ai_coupon_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'affiliate' => 'amazon'
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-container" data-niche="<?php echo esc_attr($atts['niche']); ?>" data-affiliate="<?php echo esc_attr($atts['affiliate']); ?>">
            <button id="generate-coupon" class="button">Generate Coupon</button>
            <div id="coupon-output"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_coupon_nonce')) {
            wp_die('Security check failed');
        }

        $niche = sanitize_text_field($_POST['niche']);
        $affiliate = sanitize_text_field($_POST['affiliate']);

        // Simulate AI generation (Pro version would integrate real AI API)
        $coupons = array(
            'amazon' => array(
                'general' => 'SAVE20% on Electronics - Affiliate Link: https://amazon.com/deal',
                'fashion' => '30% OFF Fashion - Code: FASHION30',
                'tech' => '15% Tech Discount - Link: https://amazon.com/techdeal'
            ),
            'other' => '10% OFF Any Purchase - Custom Affiliate Link'
        );

        $coupon = isset($coupons[$affiliate][$niche]) ? $coupons[$affiliate][$niche] : 'Default 20% OFF - Trackable Affiliate Link';

        // Free limit check
        $usage = get_option('ai_coupon_usage', 0);
        if ($usage >= 5 && get_option('ai_coupon_pro') !== 'yes') {
            echo json_encode(array('error' => 'Upgrade to Pro for unlimited coupons.'));
            wp_die();
        }
        update_option('ai_coupon_usage', $usage + 1);

        echo json_encode(array('coupon' => $coupon));
        wp_die();
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_pro', sanitize_text_field($_POST['pro_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Affiliate Coupon Settings</h1>
            <form method="post">
                <p>Pro License Key: <input type="text" name="pro_key" value="<?php echo get_option('ai_coupon_pro'); ?>" /></p>
                <?php wp_nonce_field('ai_coupon_settings'); ?>
                <p><input type="submit" name="submit" class="button-primary" value="Save" /></p>
            </form>
            <p><strong>Pro Features:</strong> Unlimited generations, analytics, custom affiliates. <a href="https://example.com/pro">Upgrade Now ($49/year)</a></p>
        </div>
        <?php
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Unlock unlimited AI coupons with <a href="' . admin_url('options-general.php?page=ai-coupon') . '">Pro version</a>!</p></div>';
    }

    public function activate() {
        update_option('ai_coupon_usage', 0);
    }
}

new AIAffiliateCouponGenerator();

// Frontend JS (embedded for single file)
function ai_coupon_add_inline_script() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#generate-coupon').click(function() {
            var container = $('#ai-coupon-container');
            var niche = container.data('niche');
            var affiliate = container.data('affiliate');

            $.post(ajax_object.ajax_url, {
                action: 'generate_coupon',
                nonce: '<?php echo wp_create_nonce('ai_coupon_nonce'); ?>',
                niche: niche,
                affiliate: affiliate
            }, function(response) {
                if (response.error) {
                    $('#coupon-output').html('<p style="color:red;">' + response.error + '</p>');
                } else {
                    $('#coupon-output').html('<div class="coupon-box" style="border:1px solid #ccc; padding:20px; margin:10px 0;"><strong>' + response.coupon + '</strong><br><small>Track conversions automatically!</small></div>');
                }
            });
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'ai_coupon_add_inline_script');

// CSS
function ai_coupon_styles() {
    echo '<style>
    .coupon-box { background: #f9f9f9; border-radius: 5px; }
    #generate-coupon { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }
    </style>';
}
add_action('wp_head', 'ai_coupon_styles');