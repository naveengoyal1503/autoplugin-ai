/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered coupon generator for personalized deals and affiliate marketing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_generator', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_post_save_coupon_settings', array($this, 'save_settings'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'ai-coupon.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupon', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        $settings = get_option('ai_coupon_settings', array('api_key' => '', 'affiliate_id' => ''));
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator Settings</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_coupon_settings">
                <?php wp_nonce_field('ai_coupon_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>AI API Key (OpenAI or similar)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($settings['api_key']); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th>Affiliate ID</th>
                        <td><input type="text" name="affiliate_id" value="<?php echo esc_attr($settings['affiliate_id']); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function save_settings() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'ai_coupon_nonce')) {
            wp_die('Security check failed');
        }
        update_option('ai_coupon_settings', array(
            'api_key' => sanitize_text_field($_POST['api_key']),
            'affiliate_id' => sanitize_text_field($_POST['affiliate_id'])
        ));
        wp_redirect(admin_url('options-general.php?page=ai-coupon&updated=1'));
        exit;
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('niche' => 'general'), $atts);
        $settings = get_option('ai_coupon_settings');
        $coupon = $this->generate_coupon($atts['niche'], $settings);
        ob_start();
        ?>
        <div id="ai-coupon-container" data-niche="<?php echo esc_attr($atts['niche']); ?>">
            <div class="coupon-card">
                <h3>Exclusive Deal for You!</h3>
                <div id="coupon-code"></div>
                <p id="coupon-desc"></p>
                <a id="coupon-link" href="#" target="_blank" class="coupon-btn">Get Deal Now</a>
                <button id="generate-new">New Coupon</button>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            generateCoupon();
            $('#generate-new').click(function() {
                generateCoupon();
            });
            function generateCoupon() {
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'generate_coupon',
                    niche: $('#ai-coupon-container').data('niche'),
                    _wpnonce: '<?php echo wp_create_nonce('ai_coupon_ajax'); ?>'
                }, function(data) {
                    if (data.success) {
                        $('#coupon-code').text(data.data.code);
                        $('#coupon-desc').text(data.data.desc);
                        $('#coupon-link').attr('href', data.data.link);
                    }
                });
            }
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function generate_coupon($niche, $settings) {
        // Simulate AI generation (replace with real OpenAI API call)
        $coupons = array(
            'general' => array(
                'code' => 'SAVE20-' . wp_generate_password(4, false),
                'desc' => '20% off on your next purchase!',
                'link' => 'https://affiliate.com/deal?ref=' . $settings['affiliate_id']
            ),
            'tech' => array(
                'code' => 'TECH15-' . wp_generate_password(4, false),
                'desc' => '15% off gadgets and software.',
                'link' => 'https://techaffiliate.com?ref=' . $settings['affiliate_id']
            )
        );
        return isset($coupons[$niche]) ? $coupons[$niche] : $coupons['general'];
    }

    public function activate() {
        add_option('ai_coupon_settings', array());
    }
}

new AICouponGenerator();

add_action('wp_ajax_generate_coupon', function() {
    check_ajax_referer('ai_coupon_ajax', '_wpnonce');
    $plugin = new AICouponGenerator();
    $coupon = $plugin->generate_coupon(sanitize_text_field($_POST['niche']), get_option('ai_coupon_settings'));
    wp_send_json_success($coupon);
});

/* Pro Upgrade Notice */
function ai_coupon_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>AI Coupon Generator Pro:</strong> Unlock unlimited coupons, custom AI prompts, analytics & more for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'ai_coupon_pro_notice');

/* CSS */
function ai_coupon_styles() {
    echo '<style>
    .coupon-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; max-width: 400px; margin: 20px auto; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
    .coupon-card h3 { margin: 0 0 10px; }
    #coupon-code { font-size: 2em; font-weight: bold; background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; margin: 10px 0; }
    .coupon-btn { display: inline-block; background: #ff6b6b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 25px; margin: 10px; font-weight: bold; }
    .coupon-btn:hover { background: #ff5252; }
    #generate-new { background: rgba(255,255,255,0.2); border: none; color: white; padding: 10px 20px; border-radius: 20px; cursor: pointer; }
    </style>';
}
add_action('wp_head', 'ai_coupon_styles');