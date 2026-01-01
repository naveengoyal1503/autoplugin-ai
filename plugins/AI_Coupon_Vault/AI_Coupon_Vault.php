/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Vault.php
*/
<?php
/**
 * Plugin Name: AI Coupon Vault
 * Plugin URI: https://example.com/ai-coupon-vault
 * Description: AI-powered coupon management for affiliate revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponVault {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_vault', array($this, 'coupon_shortcode'));
        add_action('wp_ajax_generate_coupon', array($this, 'ajax_generate_coupon'));
        add_action('wp_ajax_nopriv_generate_coupon', array($this, 'ajax_generate_coupon'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-vault', plugin_dir_url(__FILE__) . 'ai-coupon-vault.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-coupon-vault', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('AI Coupon Vault', 'Coupon Vault', 'manage_options', 'ai-coupon-vault', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('ai_coupon_vault_options', 'ai_coupon_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>AI Coupon Vault Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_coupon_vault_options'); ?>
                <?php do_settings_sections('ai_coupon_vault_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Affiliate Programs</th>
                        <td><textarea name="ai_coupon_settings[affiliates]" rows="5" cols="50" placeholder='{"amazon": "Your Amazon Affiliate ID", "other": "ID"}'><?php echo esc_textarea(get_option('ai_coupon_settings')['affiliates'] ?? ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="ai_coupon_settings[pro_key]" value="<?php echo esc_attr(get_option('ai_coupon_settings')['pro_key'] ?? ''); ?>" /> <a href="#" class="upgrade-pro">Upgrade to Pro</a></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Generate Coupons</h2>
            <input type="text" id="coupon-keyword" placeholder="e.g., laptop" />
            <button id="generate-coupon">Generate AI Coupon</button>
            <div id="coupon-output"></div>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $coupons = get_option('ai_coupon_vault_coupons', array());
        $coupon = isset($coupons[$atts['id']]) ? $coupons[$atts['id']] : $this->generate_sample_coupon();
        $is_pro = $this->is_pro();
        $pro_notice = !$is_pro ? '<p><strong>Pro Feature:</strong> Unlimited coupons & analytics. <a href="https://example.com/pro">Get Pro</a></p>' : '';

        return '<div class="ai-coupon-vault">'
               . '<h3>' . esc_html($coupon['title']) . '</h3>'
               . '<p><strong>Code:</strong> ' . esc_html($coupon['code']) . '</p>'
               . '<p><strong>Discount:</strong> ' . esc_html($coupon['discount']) . '% off</p>'
               . '<a href="' . esc_url($coupon['link']) . '" target="_blank" class="coupon-btn">Shop Now & Save</a>'
               . $pro_notice
               . '</div>';
    }

    private function generate_sample_coupon() {
        $samples = array(
            array('title' => 'Amazon Deal', 'code' => 'SAVE20', 'discount' => '20', 'link' => 'https://amazon.com'),
            array('title' => 'Generic Discount', 'code' => 'WPDEAL', 'discount' => '15', 'link' => 'https://example.com/deal')
        );
        return $samples[array_rand($samples)];
    }

    public function ajax_generate_coupon() {
        if (!wp_verify_nonce($_POST['nonce'], 'ai_coupon_nonce')) {
            wp_die('Security check failed');
        }

        $keyword = sanitize_text_field($_POST['keyword']);
        $is_pro = $this->is_pro();

        if (!$is_pro && count(get_option('ai_coupon_vault_coupons', array())) >= 5) {
            wp_send_json_error('Upgrade to Pro for unlimited coupons.');
        }

        $coupon = array(
            'id' => uniqid(),
            'title' => 'AI Generated: ' . ucwords($keyword) . ' Deal',
            'code' => strtoupper(substr(md5($keyword . time()), 0, 8)),
            'discount' => rand(10, 50),
            'link' => 'https://affiliate.com/' . sanitize_title($keyword),
            'generated' => current_time('mysql')
        );

        $coupons = get_option('ai_coupon_vault_coupons', array());
        $coupons[$coupon['id']] = $coupon;
        update_option('ai_coupon_vault_coupons', $coupons);

        wp_send_json_success($coupon);
    }

    private function is_pro() {
        $settings = get_option('ai_coupon_settings', array());
        return !empty($settings['pro_key']) && strlen($settings['pro_key']) > 10;
    }
}

AICouponVault::get_instance();

// Embed JS
add_action('wp_footer', function() {
    if (!is_admin()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#generate-coupon').click(function() {
                var keyword = $('#coupon-keyword').val();
                if (!keyword) return;
                $.post(ajax_object.ajax_url, {
                    action: 'generate_coupon',
                    keyword: keyword,
                    nonce: '<?php echo wp_create_nonce('ai_coupon_nonce'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#coupon-output').html('<div class="coupon-preview">[ai_coupon_vault id="' + response.data.id + '"] <p>Copy this shortcode to your posts!</p></div>');
                    } else {
                        $('#coupon-output').html('<p>Error: ' + response.data + '</p>');
                    }
                });
            });
        });
        </script>
        <style>
        .ai-coupon-vault { border: 1px solid #ddd; padding: 20px; margin: 10px 0; background: #f9f9f9; }
        .coupon-btn { background: #ff6600; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .coupon-btn:hover { background: #e55a00; }
        #coupon-output { margin-top: 20px; padding: 10px; background: #e7f3ff; }
        </style>
        <?php
    }
});