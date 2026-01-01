/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/ai-coupon-affiliate-pro
 * Description: AI-powered coupon management for affiliate marketing. Generate, track, and monetize exclusive deals.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponAffiliatePro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            return;
        }
        $this->load_textdomain();
    }

    public function activate() {
        add_option('ai_coupon_api_key', '');
        add_option('ai_coupon_affiliates', json_encode(array(
            array('name' => 'Amazon', 'link' => 'https://amazon.com/?tag=YOURTAG', 'discount' => '10%'),
        )));
    }

    public function admin_menu() {
        add_options_page(
            'AI Coupon Affiliate Pro',
            'AI Coupons',
            'manage_options',
            'ai-coupon-pro',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_coupon_affiliates', wp_json_encode($_POST['affiliates']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_coupon_api_key');
        $affiliates = json_decode(get_option('ai_coupon_affiliates'), true);
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>AI API Key (Pro Feature)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Programs</th>
                        <td>
                            <div id="affiliates-list">
                                <?php foreach ($affiliates as $aff) : ?>
                                    <div class="affiliate-row">
                                        <input type="text" name="affiliates[<?php echo esc_attr($aff['name']); ?>][name]" value="<?php echo esc_attr($aff['name']); ?>" placeholder="Program Name" />
                                        <input type="url" name="affiliates[<?php echo esc_attr($aff['name']); ?>][link]" value="<?php echo esc_attr($aff['link']); ?>" placeholder="Affiliate Link" />
                                        <input type="text" name="affiliates[<?php echo esc_attr($aff['name']); ?>][discount]" value="<?php echo esc_attr($aff['discount']); ?>" placeholder="Discount %" />
                                        <button type="button" class="button remove-aff">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-affiliate" class="button">Add Affiliate</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-affiliate').click(function() {
                var row = '<div class="affiliate-row">\n' +
                    '<input type="text" name="affiliates[][name]" placeholder="Program Name" />\n' +
                    '<input type="url" name="affiliates[][link]" placeholder="Affiliate Link" />\n' +
                    '<input type="text" name="affiliates[][discount]" placeholder="Discount %" />\n' +
                    '<button type="button" class="button remove-aff">Remove</button>\n' +
                    '</div>';
                $('#affiliates-list').append(row);
            });
            $(document).on('click', '.remove-aff', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook !== 'settings_page_ai-coupon-pro') return;
        wp_enqueue_script('jquery');
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'program' => 'all'
        ), $atts);

        $affiliates = json_decode(get_option('ai_coupon_affiliates'), true) ?: array();
        $output = '<div class="ai-coupon-container">';

        foreach ($affiliates as $aff) {
            if ($atts['program'] !== 'all' && $aff['name'] !== $atts['program']) continue;
            $code = substr(md5($aff['name'] . time()), 0, 8);
            $output .= '<div class="ai-coupon-card">';
            $output .= '<h3>' . esc_html($aff['name']) . '</h3>';
            $output .= '<p>Exclusive Discount: <strong>' . esc_html($aff['discount']) . '</strong></p>';
            $output .= '<p>Your Code: <strong>' . $code . '</strong></p>';
            $output .= '<a href="' . esc_url($aff['link']) . '" class="ai-coupon-btn" target="_blank">Shop Now & Save</a>';
            $output .= '</div>';
        }

        $output .= '</div>';
        return $output;
    }

    private function load_textdomain() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

new AICouponAffiliatePro();

// Pro Teaser
add_action('admin_notices', function() {
    if (!get_option('ai_coupon_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>AI Coupon Affiliate Pro:</strong> Unlock AI-generated coupons and premium features! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
    }
});

// Minimal CSS (inline for single file)
add_action('wp_head', function() {
    echo '<style>
.ai-coupon-container { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; }
.ai-coupon-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2); max-width: 300px; }
.ai-coupon-card h3 { margin: 0 0 10px; }
.ai-coupon-btn { display: inline-block; background: #ff6b6b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; transition: background 0.3s; }
.ai-coupon-btn:hover { background: #ff5252; }
@media (max-width: 768px) { .ai-coupon-container { flex-direction: column; align-items: center; } }
</style>';
});