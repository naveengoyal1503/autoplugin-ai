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
    private $api_key;
    private $is_pro = false;

    public function __construct() {
        $this->api_key = get_option('ai_coupon_api_key', '');
        $this->is_pro = get_option('ai_coupon_pro', false);
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (isset($_GET['activate_pro'])) {
            update_option('ai_coupon_pro', true);
            wp_redirect(admin_url('admin.php?page=ai-coupon-settings'));
            exit;
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'ai-coupon.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-js', 'ai_coupon_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_coupon_nonce')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'ai-coupon-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_coupon_api_key'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['ai_coupon_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        if (!$this->is_pro) {
            echo '<div class="notice notice-info"><p><a href="' . admin_url('admin.php?page=ai-coupon-settings&activate_pro=1') . '">Click to activate Pro (demo)</a> | Real Pro: $49/year</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Affiliate Coupon Generator</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="text" name="ai_coupon_api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Use shortcode: <code>[ai_coupon product="shoes" affiliate_url="https://affiliate.link"]</code></p>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'product' => 'product',
            'affiliate_url' => '',
        ), $atts);

        ob_start();
        ?>
        <div id="ai-coupon-<?php echo uniqid(); ?>" class="ai-coupon-container" data-product="<?php echo esc_attr($atts['product']); ?>" data-affiliate="<?php echo esc_attr($atts['affiliate_url']); ?>">
            <button class="generate-coupon-btn">Generate Coupon</button>
            <div class="coupon-result"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_generate_coupon() {
        check_ajax_referer('ai_coupon_nonce', 'nonce');

        if (!$this->api_key) {
            wp_die('API Key required');
        }

        if (!$this->is_pro && get_transient('ai_coupon_limit_' . get_current_user_id()) > 5) {
            wp_send_json_error('Upgrade to Pro for unlimited coupons');
        }

        $product = sanitize_text_field($_POST['product']);
        $affiliate = esc_url_raw($_POST['affiliate']);

        $prompt = "Generate a unique 20% off coupon code for {$product}. Make it catchy and urgent. Format: CODE: code | Description: desc";

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 100,
            )),
        ));

        if (is_wp_error($response)) {
            wp_send_json_error('AI API error');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $content = $body['choices']['message']['content'] ?? 'SAVE20';

        preg_match('/CODE: (\w+)/', $content, $matches);
        $code = $matches[1] ?? 'SAVE20';
        preg_match('/Description: (.*)/', $content, $matches);
        $desc = $matches[1] ?? "{$code} - 20% Off!";

        $coupon_html = "<div class=\"coupon-box\"><span class=\"code\">{$code}</span><p>{$desc}</p><a href=\"{$affiliate}?coupon={$code}\" class=\"affiliate-btn\" target=\"_blank\">Shop Now & Save</a></div>";

        set_transient('ai_coupon_limit_' . get_current_user_id(), 1 + (get_transient('ai_coupon_limit_' . get_current_user_id()) ?: 0), DAY_IN_SECONDS);

        wp_send_json_success($coupon_html);
    }
}

new AIAffiliateCouponGenerator();

/* Pro Upsell Widget */
function ai_coupon_pro_widget() {
    if (!is_active_widget(false, false, 'ai_coupon_pro', true) && !get_option('ai_coupon_pro')) {
        register_sidebar(array(
            'name' => 'AI Coupon Pro Upsell',
            'id' => 'ai_coupon_pro',
            'before_widget' => '<div class="ai-pro-upsell">',
            'after_widget' => '</div>',
        ));
    }
}
add_action('widgets_init', 'ai_coupon_pro_widget');

/* Basic CSS */
function ai_coupon_styles() {
    echo '<style>
    .ai-coupon-container { max-width: 300px; }
    .generate-coupon-btn { background: #0073aa; color: white; padding: 10px; border: none; cursor: pointer; }
    .coupon-box { background: #f0f0f0; padding: 20px; text-align: center; margin-top: 10px; }
    .code { font-size: 2em; font-weight: bold; color: #d63638; }
    .affiliate-btn { display: inline-block; background: #d63638; color: white; padding: 10px 20px; text-decoration: none; margin-top: 10px; }
    .ai-pro-upsell { background: #fff3cd; padding: 20px; text-align: center; border: 1px solid #ffeaa7; }
    </style>';
}
add_action('wp_head', 'ai_coupon_styles');

/* JS File content would be enqueued, but for single file, inline it */
function ai_coupon_inline_js() {
    if (!wp_script_is('ai-coupon-js', 'enqueued')) return;
    ?>
    <script>jQuery(document).ready(function($) {
        $(document).on('click', '.generate-coupon-btn', function() {
            var $container = $(this).closest('.ai-coupon-container');
            $.post(ai_coupon_ajax.ajax_url, {
                action: 'generate_coupon',
                nonce: ai_coupon_ajax.nonce,
                product: $container.data('product'),
                affiliate: $container.data('affiliate')
            }, function(res) {
                if (res.success) {
                    $container.find('.coupon-result').html(res.data);
                } else {
                    alert(res.data);
                }
            });
        });
    });</script>
    <?php
}
add_action('wp_footer', 'ai_coupon_inline_js');