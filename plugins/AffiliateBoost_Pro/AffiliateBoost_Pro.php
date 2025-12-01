<?php
/*
Plugin Name: AffiliateBoost Pro
Description: Comprehensive affiliate marketing management for WordPress with tracking, commissions, and payouts.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoost_Pro.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateBoostPro {
    private $version = '1.0';
    private $plugin_slug = 'affiliateboost-pro';

    public function __construct() {
        add_action('init', array($this, 'init')); 
        add_action('wp_ajax_abp_register_affiliate', array($this, 'register_affiliate'));
        add_action('wp_ajax_nopriv_abp_register_affiliate', array($this, 'register_affiliate'));
        add_shortcode('abp_affiliate_link', array($this, 'shortcode_affiliate_link'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('template_redirect', array($this, 'track_referral'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function init() {
        // Create DB tables on activation
        global $wpdb;
        $table_affiliates = $wpdb->prefix . 'abp_affiliates';
        $table_referrals = $wpdb->prefix . 'abp_referrals';
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql_affiliates = "CREATE TABLE $table_affiliates (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED DEFAULT NULL,
            affiliate_code VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending','active','inactive') DEFAULT 'pending',
            PRIMARY KEY(id),
            UNIQUE KEY affiliate_code (affiliate_code),
            UNIQUE KEY email (email)
        ) $charset_collate;";

        $sql_referrals = "CREATE TABLE $table_referrals (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            referred_url TEXT NOT NULL,
            commission DECIMAL(10,2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id),
            KEY affiliate_id (affiliate_id)
        ) $charset_collate;";

        dbDelta($sql_affiliates);
        dbDelta($sql_referrals);
    }

    public function enqueue_scripts() {
        if (is_page()) {
            wp_enqueue_style('abp-style', plugin_dir_url(__FILE__) . 'style.css');
            wp_enqueue_script('abp-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), $this->version, true);
            wp_localize_script('abp-script', 'abp_ajax_obj', array('ajaxurl' => admin_url('admin-ajax.php')));
        }
    }

    public function generate_affiliate_code($email) {
        return 'ABP' . strtoupper(substr(md5($email . time()), 0, 8));
    }

    public function register_affiliate() {
        // Basic AJAX registration
        if (!isset($_POST['email']) || !is_email($_POST['email'])) {
            wp_send_json_error('Invalid email');
        }
        $email = sanitize_email($_POST['email']);
        global $wpdb;
        $table = $wpdb->prefix . 'abp_affiliates';

        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE email=%s", $email));
        if ($exists > 0) {
            wp_send_json_error('Email is already registered');
        }

        $affiliate_code = $this->generate_affiliate_code($email);

        $result = $wpdb->insert($table, array(
            'email' => $email,
            'affiliate_code' => $affiliate_code,
            'status' => 'active',
            'created_at' => current_time('mysql')
        ));

        if ($result) {
            wp_send_json_success(array('affiliate_code' => $affiliate_code));
        } else {
            wp_send_json_error('Registration failed');
        }
    }

    public function shortcode_affiliate_link($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        if (empty($atts['code'])) return '';
        $url = site_url('/') . '?ref=' . urlencode($atts['code']);
        return esc_url($url);
    }

    public function track_referral() {
        if (isset($_GET['ref']) && !empty($_GET['ref'])) {
            $ref_code = sanitize_text_field($_GET['ref']);
            setcookie('abp_ref', $ref_code, time() + 30 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
        }

        if (is_user_logged_in() || isset($_COOKIE['abp_ref'])) {
            // For demo, we track visits only once per user visit session
            if (!isset($_COOKIE['abp_tracked'])) {
                global $wpdb;
                $table_aff = $wpdb->prefix . 'abp_affiliates';
                $table_ref = $wpdb->prefix . 'abp_referrals';

                $ref_code = isset($_COOKIE['abp_ref']) ? sanitize_text_field($_COOKIE['abp_ref']) : '';
                if (!$ref_code) return;

                $affiliate_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_aff WHERE affiliate_code=%s AND status='active'", $ref_code));
                if ($affiliate_id) {
                    // Example flat commission
                    $commission = 1.00;
                    $wpdb->insert($table_ref, array(
                        'affiliate_id' => $affiliate_id,
                        'referred_url' => esc_url_raw($_SERVER['REQUEST_URI']),
                        'commission' => $commission,
                        'created_at' => current_time('mysql')
                    ));
                    setcookie('abp_tracked', '1', time() + 12 * HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);
                }
            }
        }
    }

    public function admin_menu() {
        add_menu_page('AffiliateBoost Pro', 'AffiliateBoost Pro', 'manage_options', 'affiliateboost-pro', array($this, 'admin_page'), 'dashicons-networking');
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) return;

        global $wpdb;
        $table_aff = $wpdb->prefix . 'abp_affiliates';
        $table_ref = $wpdb->prefix . 'abp_referrals';

        $affiliates = $wpdb->get_results("SELECT * FROM $table_aff ORDER BY created_at DESC");

        echo '<div class="wrap"><h1>AffiliateBoost Pro - Affiliates</h1>';
        echo '<table class="widefat fixed striped"><thead><tr><th>ID</th><th>Email</th><th>Code</th><th>Status</th><th>Joined</th><th>Commissions</th></tr></thead><tbody>';

        foreach ($affiliates as $aff) {
            $commissions = $wpdb->get_var($wpdb->prepare("SELECT SUM(commission) FROM $table_ref WHERE affiliate_id=%d", $aff->id));
            $commissions = $commissions ? number_format($commissions,2) : '0.00';
            echo '<tr>' .
                 '<td>' . intval($aff->id) . '</td>' .
                 '<td>' . esc_html($aff->email) . '</td>' .
                 '<td><code>' . esc_html($aff->affiliate_code) . '</code></td>' .
                 '<td>' . esc_html($aff->status) . '</td>' .
                 '<td>' . esc_html($aff->created_at) . '</td>' .
                 '<td>$' . $commissions . '</td>' .
                 '</tr>';
        }

        echo '</tbody></table></div>';
    }

}

// Activation hook
register_activation_hook(__FILE__, function() {
    $affiliateboost = new AffiliateBoostPro();
    $affiliateboost->init();
});

// Initialize plugin
new AffiliateBoostPro();
