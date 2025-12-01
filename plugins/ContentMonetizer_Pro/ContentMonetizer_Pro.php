<?php
/*
Plugin Name: ContentMonetizer Pro
Plugin URI: https://contentmonetizer.local
Description: Monetize WordPress content through paywalls, affiliate links, sponsored content, and donations
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentMonetizer_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: contentmonetizer
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTMONETIZER_VERSION', '1.0.0');
define('CONTENTMONETIZER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTMONETIZER_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentMonetizer {
    private static $instance = null;
    private $db_version = '1.0.0';

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_filter('the_content', array($this, 'add_paywall_content'));
        add_shortcode('contentmonetizer_donation', array($this, 'render_donation_shortcode'));
        add_shortcode('contentmonetizer_paywall', array($this, 'render_paywall_shortcode'));
        add_action('wp_ajax_cm_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_cm_process_donation', array($this, 'process_donation'));
    }

    public function init() {
        load_plugin_textdomain('contentmonetizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cm_monetization (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            monetization_type varchar(50) NOT NULL,
            settings longtext NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_type (post_id, monetization_type)
        ) $charset_collate;
        
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cm_donations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            donor_email varchar(100),
            amount decimal(10, 2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            status varchar(20) DEFAULT 'pending',
            transaction_id varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('contentmonetizer_db_version', $this->db_version);
        update_option('contentmonetizer_settings', array(
            'currency' => 'USD',
            'enable_donations' => true,
            'enable_paywalls' => true,
            'donation_button_text' => 'Support This Content',
            'premium_enabled' => false
        ));
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentMonetizer',
            'ContentMonetizer',
            'manage_options',
            'contentmonetizer',
            array($this, 'render_dashboard'),
            'dashicons-dollar',
            6
        );
        
        add_submenu_page(
            'contentmonetizer',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentmonetizer',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'contentmonetizer',
            'Settings',
            'Settings',
            'manage_options',
            'contentmonetizer_settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentmonetizer') === false) {
            return;
        }
        
        wp_enqueue_style('contentmonetizer-admin', CONTENTMONETIZER_PLUGIN_URL . 'assets/admin.css', array(), CONTENTMONETIZER_VERSION);
        wp_enqueue_script('contentmonetizer-admin', CONTENTMONETIZER_PLUGIN_URL . 'assets/admin.js', array('jquery'), CONTENTMONETIZER_VERSION, true);
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('contentmonetizer-frontend', CONTENTMONETIZER_PLUGIN_URL . 'assets/frontend.css', array(), CONTENTMONETIZER_VERSION);
        wp_enqueue_script('contentmonetizer-frontend', CONTENTMONETIZER_PLUGIN_URL . 'assets/frontend.js', array('jquery'), CONTENTMONETIZER_VERSION, true);
        
        wp_localize_script('contentmonetizer-frontend', 'contentMonetizer', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentmonetizer_nonce')
        ));
    }

    public function render_dashboard() {
        global $wpdb;
        
        $total_donations = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}cm_donations WHERE status = 'completed'");
        $donation_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cm_donations WHERE status = 'completed'");
        $paywall_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cm_monetization WHERE monetization_type = 'paywall'");
        
        echo '<div class="wrap">';
        echo '<h1>ContentMonetizer Pro Dashboard</h1>';
        echo '<div class="cm-dashboard-grid">';
        echo '<div class="cm-stat-box"><h3>Total Donations</h3><p class="cm-stat-value'>\$' . number_format($total_donations || 0, 2) . '</p></div>';
        echo '<div class="cm-stat-box"><h3>Donations Received</h3><p class="cm-stat-value">' . ($donation_count || 0) . '</p></div>';
        echo '<div class="cm-stat-box"><h3>Paywalled Posts</h3><p class="cm-stat-value">' . ($paywall_count || 0) . '</p></div>';
        echo '</div>';
        echo '</div>';
    }

    public function render_settings() {
        if ($_POST && isset($_POST['contentmonetizer_nonce']) && wp_verify_nonce($_POST['contentmonetizer_nonce'], 'contentmonetizer_settings')) {
            $settings = array(
                'currency' => sanitize_text_field($_POST['cm_currency']),
                'enable_donations' => isset($_POST['cm_enable_donations']),
                'enable_paywalls' => isset($_POST['cm_enable_paywalls']),
                'donation_button_text' => sanitize_text_field($_POST['cm_donation_button_text']),
                'paywall_message' => wp_kses_post($_POST['cm_paywall_message'])
            );
            update_option('contentmonetizer_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved successfully.</p></div>';
        }
        
        $settings = get_option('contentmonetizer_settings', array());
        
        echo '<div class="wrap">';
        echo '<h1>ContentMonetizer Settings</h1>';
        echo '<form method="post" action="">';
        wp_nonce_field('contentmonetizer_settings', 'contentmonetizer_nonce');
        echo '<table class="form-table">';
        echo '<tr><th><label for="cm_currency">Currency</label></th>';
        echo '<td><input type="text" name="cm_currency" id="cm_currency" value="' . esc_attr($settings['currency'] ?? 'USD') . '" class="regular-text"></td></tr>';
        echo '<tr><th><label for="cm_donation_button_text">Donation Button Text</label></th>';
        echo '<td><input type="text" name="cm_donation_button_text" id="cm_donation_button_text" value="' . esc_attr($settings['donation_button_text'] ?? 'Support This Content') . '" class="regular-text"></td></tr>';
        echo '<tr><th><label for="cm_paywall_message">Paywall Message</label></th>';
        echo '<td><textarea name="cm_paywall_message" id="cm_paywall_message" class="large-text" rows="5">' . wp_kses_post($settings['paywall_message'] ?? 'This content is exclusively available to members.') . '</textarea></td></tr>';
        echo '<tr><th></th><td><label><input type="checkbox" name="cm_enable_donations" value="1" ' . checked($settings['enable_donations'] ?? false, true, false) . '> Enable Donations</label></td></tr>';
        echo '<tr><th></th><td><label><input type="checkbox" name="cm_enable_paywalls" value="1" ' . checked($settings['enable_paywalls'] ?? false, true, false) . '> Enable Paywalls</label></td></tr>';
        echo '</table>';
        echo '<p class="submit"><input type="submit" class="button button-primary" value="Save Settings"></p>';
        echo '</form>';
        echo '</div>';
    }

    public function add_paywall_content($content) {
        if (!is_single() || is_user_logged_in()) {
            return $content;
        }
        
        global $wpdb, $post;
        
        $paywall = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cm_monetization WHERE post_id = %d AND monetization_type = 'paywall'",
            $post->ID
        ));
        
        if ($paywall) {
            $settings = get_option('contentmonetizer_settings', array());
            $excerpt_length = 200;
            $excerpt = wp_trim_words($content, $excerpt_length);
            
            $content = $excerpt . ' ... <div class="cm-paywall-notice">';
            $content .= '<p>' . esc_html($settings['paywall_message'] ?? 'This content requires membership.') . '</p>';
            $content .= '<p><a href="' . esc_url(wp_registration_url()) . '" class="button">Register to Read More</a></p>';
            $content .= '</div>';
        }
        
        return $content;
    }

    public function render_donation_shortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => 5,
            'text' => 'Donate Now'
        ), $atts);
        
        $settings = get_option('contentmonetizer_settings', array());
        $currency = $settings['currency'] ?? 'USD';
        
        $html = '<div class="cm-donation-widget">';
        $html .= '<button class="cm-donate-btn" data-amount="' . esc_attr($atts['amount']) . '" data-currency="' . esc_attr($currency) . '">';
        $html .= esc_html($atts['text']);
        $html .= '</button>';
        $html .= '</div>';
        
        return $html;
    }

    public function render_paywall_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="cm-paywall-restricted"><p>This content is for members only. <a href="' . esc_url(wp_registration_url()) . '">Sign up here</a></p></div>';
        }
        
        return '<div class="cm-paywall-content">' . do_shortcode($atts['content']) . '</div>';
    }

    public function process_donation() {
        check_ajax_referer('contentmonetizer_nonce', 'nonce');
        
        global $wpdb;
        
        $amount = floatval($_POST['amount']);
        $email = sanitize_email($_POST['email'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if ($amount <= 0) {
            wp_send_json_error('Invalid amount');
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'cm_donations',
            array(
                'post_id' => $post_id,
                'donor_email' => $email,
                'amount' => $amount,
                'status' => 'pending'
            ),
            array('%d', '%s', '%f', '%s')
        );
        
        wp_send_json_success(array('donation_id' => $wpdb->insert_id));
    }
}

ContentMonetizer::getInstance();
?>