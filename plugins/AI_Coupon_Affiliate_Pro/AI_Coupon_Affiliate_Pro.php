/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Affiliate_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Affiliate Pro
 * Plugin URI: https://example.com/ai-coupon-affiliate-pro
 * Description: AI-powered coupon generator for affiliate marketing. Create, track, and display personalized coupons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-coupon-affiliate-pro
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICouponAffiliatePro {
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
        add_shortcode('ai_coupon', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-coupon-affiliate-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-coupon-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-coupon-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page('AI Coupon Pro Settings', 'AI Coupon Pro', 'manage_options', 'ai-coupon-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_coupon_affiliates', sanitize_textarea_field($_POST['affiliates']));
            update_option('ai_coupon_theme', sanitize_text_field($_POST['theme']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $affiliates = get_option('ai_coupon_affiliates', "Amazon|https://amazon.com/affiliate-link?tag=yourtag\nShopify|https://shopify.com/aff?ref=yourref");
        $theme = get_option('ai_coupon_theme', 'default');
        ?>
        <div class="wrap">
            <h1>AI Coupon Affiliate Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links (Format: Name|URL)</th>
                        <td><textarea name="affiliates" rows="10" cols="50"><?php echo esc_textarea($affiliates); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Coupon Theme</th>
                        <td>
                            <select name="theme">
                                <option value="default" <?php selected($theme, 'default'); ?>>Default</option>
                                <option value="modern" <?php selected($theme, 'modern'); ?>>Modern</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $affiliates = explode('\n', get_option('ai_coupon_affiliates', ''));
        $coupons = array();
        foreach ($affiliates as $aff) {
            list($name, $url) = explode('|', $aff, 2);
            $code = $this->generate_coupon_code($name);
            $coupons[] = array('name' => trim($name), 'url' => trim($url), 'code' => $code);
        }
        ob_start();
        $theme = get_option('ai_coupon_theme', 'default');
        ?>
        <div class="ai-coupon-container theme-<?php echo esc_attr($theme); ?>">
            <h3>Exclusive Deals & Coupons</h3>
            <?php foreach ($coupons as $coupon): ?>
            <div class="coupon-item">
                <strong><?php echo esc_html($coupon['name']); ?>:</strong>
                Use code <code><?php echo esc_html($coupon['code']); ?></code>
                <a href="<?php echo esc_url($coupon['url']); ?>&coupon=<?php echo esc_attr($coupon['code']); ?>" target="_blank" class="coupon-btn">Grab Deal</a>
                <?php $this->track_click($coupon['url']); ?>
            </div>
            <?php endforeach; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('.coupon-btn').on('click', function() {
                // Track click
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=ai_coupon_track&url=' + encodeURIComponent($(this).attr('href'))
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    private function generate_coupon_code($name) {
        return strtoupper(substr(md5($name . time() . rand(1000,9999)), 0, 8));
    }

    public function track_click($url) {
        // Simple tracking: log to options or DB in pro version
        $tracks = get_option('ai_coupon_tracks', array());
        $tracks[$url] = isset($tracks[$url]) ? $tracks[$url] + 1 : 1;
        update_option('ai_coupon_tracks', $tracks);
    }

    public function activate() {
        if (!get_option('ai_coupon_affiliates')) {
            update_option('ai_coupon_affiliates', "Amazon|https://amazon.com/affiliate-link?tag=yourtag\nShopify|https://shopify.com/aff?ref=yourref");
        }
    }
}

AICouponAffiliatePro::get_instance();

// AJAX for tracking
add_action('wp_ajax_ai_coupon_track', 'ai_coupon_ajax_track');
add_action('wp_ajax_nopriv_ai_coupon_track', 'ai_coupon_ajax_track');
function ai_coupon_ajax_track() {
    $url = sanitize_url($_POST['url']);
    $tracks = get_option('ai_coupon_tracks', array());
    $tracks[$url] = isset($tracks[$url]) ? $tracks[$url] + 1 : 1;
    update_option('ai_coupon_tracks', $tracks);
    wp_die();
}

// Assets placeholder - create folders assets/ with style.css and script.js
// style.css: .ai-coupon-container { border: 1px solid #ddd; padding: 20px; } .coupon-item { margin: 10px 0; } .coupon-btn { background: #0073aa; color: white; padding: 10px; }
// script.js: empty for now
?>