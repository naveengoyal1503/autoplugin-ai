<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.com
Description: Lock premium content behind email capture, payments, or social shares
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL v2 or later
Text Domain: smart-content-locker
*/

if (!defined('ABSPATH')) exit;

define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCL_VERSION', '1.0.0');

class SmartContentLocker {
    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_shortcode('locked_content', [$this, 'render_locked_content']);
        add_action('wp_ajax_unlock_content', [$this, 'ajax_unlock_content']);
        add_action('wp_ajax_nopriv_unlock_content', [$this, 'ajax_unlock_content']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function enqueue_assets() {
        wp_enqueue_style('scl-style', SCL_PLUGIN_URL . 'assets/style.css', [], SCL_VERSION);
        wp_enqueue_script('scl-script', SCL_PLUGIN_URL . 'assets/script.js', ['jquery'], SCL_VERSION, true);
        wp_localize_script('scl-script', 'sclAjax', ['ajaxurl' => admin_url('admin-ajax.php')]);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Smart Content Locker',
            'Content Locker',
            'manage_options',
            'smart-content-locker',
            [$this, 'admin_page'],
            'dashicons-lock',
            80
        );
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Content Locker Pro</h1>
            <form method="post" action="options.php">
                <?php settings_fields('scl_settings'); ?>
                <?php do_settings_sections('scl_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="scl_lock_type">Default Lock Type</label></th>
                        <td>
                            <select name="scl_lock_type" id="scl_lock_type">
                                <option value="email" <?php selected(get_option('scl_lock_type'), 'email'); ?>>Email Capture</option>
                                <option value="social" <?php selected(get_option('scl_lock_type'), 'social'); ?>>Social Share</option>
                                <option value="payment" <?php selected(get_option('scl_lock_type'), 'payment'); ?>>Payment</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="scl_email_list">Email List Provider</label></th>
                        <td>
                            <input type="text" name="scl_email_list" id="scl_email_list" value="<?php echo esc_attr(get_option('scl_email_list')); ?>" class="regular-text" placeholder="Mailchimp, ConvertKit, etc." />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function register_settings() {
        register_setting('scl_settings', 'scl_lock_type');
        register_setting('scl_settings', 'scl_email_list');
    }

    public function render_locked_content($atts) {
        $atts = shortcode_atts([
            'id' => uniqid('scl_'),
            'type' => get_option('scl_lock_type', 'email'),
            'preview' => '',
            'message' => 'Unlock premium content by entering your email'
        ], $atts);

        $content = $atts['preview'] ? '<div class="scl-preview">' . wp_kses_post($atts['preview']) . '</div>' : '';
        $content .= '<div class="scl-locker" data-id="' . esc_attr($atts['id']) . '" data-type="' . esc_attr($atts['type']) . '">';
        $content .= '<div class="scl-unlock-message">' . esc_html($atts['message']) . '</div>';
        $content .= $this->get_unlock_form($atts['type'], $atts['id']);
        $content .= '</div>';
        $content .= '<div class="scl-locked-content" id="' . esc_attr($atts['id']) . '" style="display:none;">' . do_shortcode(wp_kses_post($this->get_saved_content($atts['id']))) . '</div>';

        return $content;
    }

    public function get_unlock_form($type, $id) {
        if ($type === 'email') {
            return '<form class="scl-unlock-form" data-id="' . esc_attr($id) . '">
                <input type="email" name="email" placeholder="Enter your email" required />
                <button type="submit" class="scl-submit">Unlock Content</button>
            </form>';
        } elseif ($type === 'social') {
            return '<div class="scl-social-buttons">
                <button class="scl-social-btn facebook" data-id="' . esc_attr($id) . '">Share on Facebook</button>
                <button class="scl-social-btn twitter" data-id="' . esc_attr($id) . '">Share on Twitter</button>
            </div>';
        } else {
            return '<button class="scl-payment-btn" data-id="' . esc_attr($id) . '">Unlock for $2.99</button>';
        }
    }

    public function get_saved_content($id) {
        return get_post_meta(get_the_ID(), 'scl_content_' . $id, true);
    }

    public function ajax_unlock_content() {
        check_ajax_referer('scl_nonce', 'nonce');

        $type = sanitize_text_field($_POST['type'] ?? '');
        $id = sanitize_text_field($_POST['id'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        if ($type === 'email' && !empty($email)) {
            setcookie('scl_unlocked_' . $id, '1', time() + 86400 * 30, '/');
            wp_send_json_success(['message' => 'Content unlocked!']);
        } else {
            wp_send_json_error(['message' => 'Invalid unlock attempt']);
        }
    }
}

new SmartContentLocker();
?>