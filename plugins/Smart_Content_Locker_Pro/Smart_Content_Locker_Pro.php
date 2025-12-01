<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.com
Description: Gate premium content behind user actions to build email lists and increase engagement
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: smart-content-locker
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SCL_VERSION', '1.0.0');
define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartContentLocker {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('content_locker', array($this, 'render_locker'));
        add_action('wp_ajax_scl_unlock_content', array($this, 'unlock_content'));
        add_action('wp_ajax_nopriv_scl_unlock_content', array($this, 'unlock_content'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.js', array('jquery'), SCL_VERSION, true);
        wp_enqueue_style('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.css', array(), SCL_VERSION);
        wp_localize_script('scl-frontend', 'sclData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scl_nonce')
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Smart Content Locker',
            'Content Locker',
            'manage_options',
            'smart-content-locker',
            array($this, 'render_admin_page'),
            'dashicons-lock',
            90
        );
    }

    public function register_settings() {
        register_setting('scl_settings', 'scl_email_provider');
        register_setting('scl_settings', 'scl_mailchimp_key');
        register_setting('scl_settings', 'scl_conversion_tracking');
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Content Locker Pro</h1>
            <form method="post" action="options.php">
                <?php settings_fields('scl_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="scl_email_provider">Email Provider</label></th>
                        <td>
                            <select name="scl_email_provider" id="scl_email_provider">
                                <option value="mailchimp" <?php selected(get_option('scl_email_provider'), 'mailchimp'); ?>>Mailchimp</option>
                                <option value="convertkit" <?php selected(get_option('scl_email_provider'), 'convertkit'); ?>>ConvertKit</option>
                                <option value="custom" <?php selected(get_option('scl_email_provider'), 'custom'); ?>>Custom Webhook</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="scl_mailchimp_key">API Key</label></th>
                        <td>
                            <input type="password" name="scl_mailchimp_key" id="scl_mailchimp_key" value="<?php echo esc_attr(get_option('scl_mailchimp_key')); ?>" class="regular-text">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Shortcode Usage</h2>
            <p><code>[content_locker action="email" title="Unlock Premium Content"]Your locked content here[/content_locker]</code></p>
            <h2>Documentation</h2>
            <p>Actions: <strong>email</strong>, <strong>social_share</strong>, <strong>referral</strong></p>
        </div>
        <?php
    }

    public function render_locker($atts, $content = '') {
        $atts = shortcode_atts(array(
            'action' => 'email',
            'title' => 'Unlock Premium Content',
            'id' => uniqid('scl_')
        ), $atts, 'content_locker');

        $locker_id = $atts['id'];
        $user_has_unlocked = isset($_COOKIE['scl_unlocked_' . md5($locker_id)]);

        if ($user_has_unlocked) {
            return '<div class="scl-unlocked-content">' . do_shortcode($content) . '</div>';
        }

        $html = '<div class="scl-locker" data-locker-id="' . esc_attr($locker_id) . '" data-action="' . esc_attr($atts['action']) . '">';
        $html .= '<div class="scl-locked-overlay">';
        $html .= '<div class="scl-lock-message">';
        $html .= '<h3>' . esc_html($atts['title']) . '</h3>';

        switch ($atts['action']) {
            case 'email':
                $html .= $this->render_email_form($locker_id);
                break;
            case 'social_share':
                $html .= $this->render_social_share($locker_id);
                break;
            case 'referral':
                $html .= $this->render_referral_form($locker_id);
                break;
        }

        $html .= '</div></div>';
        $html .= '<div class="scl-locked-content" style="filter: blur(5px); pointer-events: none;">' . do_shortcode($content) . '</div>';
        $html .= '</div>';

        return $html;
    }

    private function render_email_form($locker_id) {
        return '<form class="scl-email-form" data-locker-id="' . esc_attr($locker_id) . '">
            <input type="email" placeholder="Enter your email" required>
            <button type="submit" class="scl-unlock-btn">Unlock Content</button>
        </form>';
    }

    private function render_social_share($locker_id) {
        return '<div class="scl-social-share">
            <p>Share this post to unlock</p>
            <button class="scl-share-btn" data-network="twitter">Share on Twitter</button>
            <button class="scl-share-btn" data-network="facebook">Share on Facebook</button>
        </div>';
    }

    private function render_referral_form($locker_id) {
        return '<div class="scl-referral">
            <p>Refer 3 friends to unlock this content</p>
            <input type="text" readonly value="' . esc_url(add_query_arg('ref', md5(get_current_user_id()), home_url())) . '" class="scl-ref-link">
            <button type="button" class="scl-copy-btn">Copy Link</button>
        </div>';
    }

    public function unlock_content() {
        check_ajax_referer('scl_nonce', 'nonce');

        $locker_id = sanitize_text_field($_POST['locker_id'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $action = sanitize_text_field($_POST['action'] ?? 'email');

        if (!$locker_id) {
            wp_send_json_error('Invalid locker ID');
        }

        if ($action === 'email' && is_email($email)) {
            $this->subscribe_email($email);
        }

        setcookie('scl_unlocked_' . md5($locker_id), '1', time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);

        wp_send_json_success('Content unlocked');
    }

    private function subscribe_email($email) {
        $provider = get_option('scl_email_provider', 'mailchimp');
        $api_key = get_option('scl_mailchimp_key');

        if ($provider === 'mailchimp' && $api_key) {
            $this->add_to_mailchimp($email, $api_key);
        }
    }

    private function add_to_mailchimp($email, $api_key) {
        $list_id = apply_filters('scl_mailchimp_list_id', 'default_list');
        $server = substr($api_key, strpos($api_key, '-') + 1);
        $url = 'https://' . $server . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members';

        wp_remote_post($url, array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'email_address' => $email,
                'status' => 'subscribed'
            ))
        ));
    }

    public function activate() {
        if (!current_user_can('activate_plugins')) {
            return;
        }
        set_transient('scl_admin_notice', true, 10);
    }

    public function deactivate() {
        if (!current_user_can('activate_plugins') || !is_plugin_active(plugin_basename(__FILE__))) {
            return;
        }
    }
}

SmartContentLocker::get_instance();
?>