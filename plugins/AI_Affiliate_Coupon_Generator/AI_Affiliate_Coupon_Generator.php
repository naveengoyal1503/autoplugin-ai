/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Coupon_Generator.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Coupon Generator
 * Plugin URI: https://example.com/ai-affiliate-coupon
 * Description: Automatically generates personalized affiliate coupons and discount codes for blog posts using AI, boosting conversions and commissions.
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
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->api_key = get_option('aiacg_api_key', '');
        $this->is_pro = get_option('aiacg_pro', false);

        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }

        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_filter('the_content', array($this, 'auto_insert_coupon'));
    }

    public function activate() {
        add_option('aiacg_limit', 5);
    }

    public function deactivate() {}

    public function admin_menu() {
        add_options_page('AI Coupon Settings', 'AI Coupons', 'manage_options', 'aiacg', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('aiacg_options', 'aiacg_api_key');
        register_setting('aiacg_options', 'aiacg_pro');
        register_setting('aiacg_options', 'aiacg_affiliate_ids');
        register_setting('aiacg_options', 'aiacg_auto_insert');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Affiliate Coupon Generator Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('aiacg_options'); ?>
                <?php do_settings_sections('aiacg_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>AI API Key (OpenAI)</th>
                        <td><input type="password" name="aiacg_api_key" value="<?php echo esc_attr(get_option('aiacg_api_key')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Networks</th>
                        <td><textarea name="aiacg_affiliate_ids" rows="5" cols="50"><?php echo esc_textarea(get_option('aiacg_affiliate_ids', 'Amazon: your-id\nClickBank: your-id')); ?></textarea><br><small>Format: Network: ID per line</small></td>
                    </tr>
                    <tr>
                        <th>Auto-insert in posts</th>
                        <td><input type="checkbox" name="aiacg_auto_insert" value="1" <?php checked(get_option('aiacg_auto_insert')); ?> /></td>
                    </tr>
                    <tr>
                        <th>Pro Version</th>
                        <td><label><input type="checkbox" name="aiacg_pro" value="1" <?php checked(get_option('aiacg_pro')); ?> /> Enable Pro (Enter license key)</label></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlimited coupons, analytics. <a href="https://example.com/pro">Buy Now $49/year</a></p>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box('aiacg_coupon', 'AI Coupon', array($this, 'meta_box_callback'), 'post', 'normal');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aiacg_meta_nonce', 'aiacg_nonce');
        $keywords = get_post_meta($post->ID, '_aiacg_keywords', true);
        $coupon = get_post_meta($post->ID, '_aiacg_coupon', true);
        echo '<label>Keywords for coupon:</label><br><input type="text" name="aiacg_keywords" value="' . esc_attr($keywords) . '" style="width:100%" />';
        echo '<p><button type="button" id="aiacg_generate" class="button">Generate Coupon</button></p>';
        echo '<textarea name="aiacg_coupon" style="width:100%;height:100px;">' . esc_textarea($coupon) . '</textarea>';
        echo '<script>jQuery(document).ready(function($){ $("#aiacg_generate").click(function(){ /* AJAX call to generate */ alert("Pro feature: Generate AI coupon"); }); });</script>';
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['aiacg_nonce']) || !wp_verify_nonce($_POST['aiacg_nonce'], 'aiacg_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        update_post_meta($post_id, '_aiacg_keywords', sanitize_text_field($_POST['aiacg_keywords']));
        update_post_meta($post_id, '_aiacg_coupon', wp_kses_post($_POST['aiacg_coupon']));
    }

    public function generate_coupon($keywords) {
        if (!$this->api_key || !$this->check_limit()) {
            return 'Upgrade to Pro for unlimited AI coupons!';
        }

        $prompt = "Generate a personalized discount coupon for: $keywords. Include affiliate link placeholder [AFFILIATE_LINK], code, expiry. Make it compelling.";
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 150,
            )),
        ));

        if (is_wp_error($response)) {
            return 'Error generating coupon. Check API key.';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $coupon_text = $body['choices']['message']['content'] ?? 'Failed to generate.';
        $this->update_limit();
        return $coupon_text;
    }

    private function check_limit() {
        if ($this->is_pro) return true;
        $used = get_option('aiacg_used', 0);
        return $used < get_option('aiacg_limit', 5);
    }

    private function update_limit() {
        if (!$this->is_pro) {
            $used = get_option('aiacg_used', 0) + 1;
            update_option('aiacg_used', $used);
        }
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('keywords' => ''), $atts);
        return $this->generate_coupon($atts['keywords']);
    }

    public function auto_insert_coupon($content) {
        if (!is_single() || !get_option('aiacg_auto_insert')) return $content;
        $keywords = get_post_meta(get_the_ID(), '_aiacg_keywords', true);
        if ($keywords) {
            $coupon = $this->generate_coupon($keywords);
            $content .= '<div class="ai-coupon-box" style="background:#f9f9f9;padding:20px;margin:20px 0;border-left:5px solid #0073aa;">' . $coupon . '</div>';
        }
        return $content;
    }
}

new AIAffiliateCouponGenerator();

// Pro upsell notice
function aiacg_admin_notice() {
    if (!get_option('aiacg_pro')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Affiliate Coupon Generator Pro</strong> for unlimited generations and more! <a href="options-general.php?page=aiacg">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'aiacg_admin_notice');