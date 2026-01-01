/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Monetizer.php
*/
<?php
/**
 * Plugin Name: AI Content Monetizer
 * Plugin URI: https://example.com/ai-content-monetizer
 * Description: Automatically locks premium AI-generated content behind paywalls, allowing users to unlock with one-time micropayments.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentMonetizer {
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
        add_action('wp_ajax_acm_unlock_content', array($this, 'handle_unlock'));
        add_action('wp_ajax_nopriv_acm_unlock_content', array($this, 'handle_unlock'));
        add_shortcode('acm_lock', array($this, 'lock_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('acm_settings')) {
            add_filter('the_content', array($this, 'lock_content'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('acm-script', plugin_dir_url(__FILE__) . 'acm.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acm-script', 'acm_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('acm_nonce')));
        wp_enqueue_style('acm-style', plugin_dir_url(__FILE__) . 'acm.css', array(), '1.0.0');
    }

    public function lock_content($content) {
        if (!is_single() || !in_the_loop()) {
            return $content;
        }
        $settings = get_option('acm_settings', array('price' => 0.99));
        $unlock_price = $settings['price'];
        $locked_content = '<div id="acm-lock" class="acm-locked" data-price="' . $unlock_price . '"><p>Your content is locked! Unlock for <strong>$' . $unlock_price . '</strong></p><button id="acm-unlock-btn" class="button">Unlock Now</button><div id="acm-message"></div></div>';
        return substr($content, 0, 200) . '...' . $locked_content . '<div id="acm-content" style="display:none;">' . $content . '</div>';
    }

    public function lock_shortcode($atts) {
        $atts = shortcode_atts(array('price' => '0.99'), $atts);
        return '<div class="acm-shortcode-lock" data-price="' . $atts['price'] . '"><button class="acm-unlock-shortcode">Unlock ($' . $atts['price'] . ')</button><div class="acm-locked-content">[Content here]</div></div>';
    }

    public function handle_unlock() {
        check_ajax_referer('acm_nonce', 'nonce');
        if (!wp_verify_nonce($_POST['nonce'], 'acm_nonce')) {
            wp_die('Security check failed');
        }
        $user_ip = $_SERVER['REMOTE_ADDR'];
        $today = date('Y-m-d');
        $unlocks = get_option('acm_unlocks', array());
        $key = md5($user_ip . $today);
        if (!isset($unlocks[$key])) {
            $unlocks[$key] = true;
            update_option('acm_unlocks', $unlocks);
            wp_send_json_success('Unlocked for today!');
        } else {
            wp_send_json_success('Already unlocked today!');
        }
    }

    public function activate() {
        add_option('acm_settings', array('price' => 0.99));
    }
}

// Dummy JS and CSS content (in real plugin, separate files)
function acm_add_inline_scripts() {
    $script = "jQuery(document).ready(function($) {
        $('#acm-unlock-btn').click(function() {
            var price = $(this).closest('#acm-lock').data('price');
            $('#acm-message').html('<p>Processing payment of $' + price + '... (Demo: Unlocked!)</p>');
            $.post(acm_ajax.ajaxurl, {
                action: 'acm_unlock_content',
                nonce: acm_ajax.nonce
            }, function(response) {
                if (response.success) {
                    $('#acm-lock').hide();
                    $('#acm-content').show();
                }
                $('#acm-message').html(response.data);
            });
        });
    });";
    wp_add_inline_script('jquery', $script);

    $style = ".acm-locked { border: 2px solid #0073aa; padding: 20px; margin: 20px 0; background: #f9f9f9; text-align: center; } .acm-unlock-btn { background: #0073aa; color: white; padding: 10px 20px; border: none; cursor: pointer; }";
    wp_add_inline_style('wp-block-library', $style);
}
add_action('wp_enqueue_scripts', 'acm_add_inline_scripts');

AIContentMonetizer::get_instance();

// Admin settings page
function acm_admin_menu() {
    add_options_page('AI Content Monetizer Settings', 'AI Content Monetizer', 'manage_options', 'acm-settings', 'acm_settings_page');
}
add_action('admin_menu', 'acm_admin_menu');

function acm_settings_page() {
    if (isset($_POST['submit'])) {
        update_option('acm_settings', array('price' => floatval($_POST['price'])));
        echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
    }
    $settings = get_option('acm_settings', array('price' => 0.99));
    ?>
    <div class="wrap">
        <h1>AI Content Monetizer Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Unlock Price ($)</th>
                    <td><input type="number" step="0.01" name="price" value="<?php echo esc_attr($settings['price']); ?>" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p>Usage: Content is auto-locked on single posts. Use [acm_lock price="1.99"] for shortcodes. Premium unlocks via IP daily demo.</p>
    </div>
    <?php
}
?>