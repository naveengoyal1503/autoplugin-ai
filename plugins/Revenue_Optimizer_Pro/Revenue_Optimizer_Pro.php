/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Revenue_Optimizer_Pro.php
*/
<?php

/**
 * Plugin Name: Revenue Optimizer Pro
 * Plugin URI: https://revenueoptimizerpro.com
 * Description: Intelligent monetization strategy analyzer and implementation tool for WordPress sites
 * Version: 1.0.0
 * Author: Revenue Optimizer Team
 * Author URI: https://revenueoptimizerpro.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit;
}

define('REVOPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REVOPT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('REVOPT_VERSION', '1.0.0');

class RevenueOptimizerPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_shortcode('revopt_signup', array($this, 'render_signup_form'));
        add_action('wp_ajax_revopt_analyze_site', array($this, 'analyze_site'));
        add_action('wp_ajax_nopriv_revopt_analyze_site', array($this, 'analyze_site'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $table_name = $wpdb->prefix . 'revopt_analytics';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            metric_type varchar(100) NOT NULL,
            metric_value mediumint(9) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('revopt_settings', array(
            'enabled' => true,
            'selected_strategies' => array('display_ads', 'affiliate_marketing'),
            'premium_active' => false
        ));
    }

    public function deactivate_plugin() {
        delete_option('revopt_settings');
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Optimizer Pro',
            'Revenue Optimizer',
            'manage_options',
            'revopt-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            90
        );

        add_submenu_page(
            'revopt-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'revopt-dashboard',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'revopt-dashboard',
            'Strategies',
            'Strategies',
            'manage_options',
            'revopt-strategies',
            array($this, 'render_strategies')
        );

        add_submenu_page(
            'revopt-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'revopt-settings',
            array($this, 'render_settings')
        );
    }

    public function register_settings() {
        register_setting('revopt_settings_group', 'revopt_settings');
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'revopt-') === false) {
            return;
        }

        wp_enqueue_style('revopt-admin', REVOPT_PLUGIN_URL . 'css/admin-style.css', array(), REVOPT_VERSION);
        wp_enqueue_script('revopt-chart', 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js', array(), '3.9.1', true);
        wp_enqueue_script('revopt-admin', REVOPT_PLUGIN_URL . 'js/admin-script.js', array('jquery', 'revopt-chart'), REVOPT_VERSION, true);

        wp_localize_script('revopt-admin', 'revoptAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('revopt_nonce')
        ));
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('revopt-frontend', REVOPT_PLUGIN_URL . 'css/frontend-style.css', array(), REVOPT_VERSION);
        wp_enqueue_script('revopt-frontend', REVOPT_PLUGIN_URL . 'js/frontend-script.js', array('jquery'), REVOPT_VERSION, true);
    }

    public function analyze_site() {
        check_ajax_referer('revopt_nonce');

        $analysis = array(
            'total_posts' => wp_count_posts()->publish ?? 0,
            'total_pages' => wp_count_posts('page')->publish ?? 0,
            'total_comments' => wp_count_comments()->approved ?? 0,
            'avg_post_length' => $this->get_avg_post_length(),
            'recommended_strategies' => $this->get_recommended_strategies(),
            'revenue_potential' => $this->calculate_revenue_potential()
        );

        $this->log_analytics('site_analysis', 1);

        wp_send_json_success($analysis);
    }

    private function get_avg_post_length() {
        global $wpdb;
        $result = $wpdb->get_var("SELECT AVG(CHAR_LENGTH(post_content)) FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish'");
        return intval($result) ?? 0;
    }

    private function get_recommended_strategies() {
        $post_count = wp_count_posts()->publish ?? 0;
        $strategies = array();

        if ($post_count > 50) {
            $strategies[] = array(
                'name' => 'Display Advertising',
                'description' => 'Google AdSense or Mediavine for traffic-heavy blogs',
                'revenue_potential' => 'High',
                'difficulty' => 'Easy'
            );
        }

        if ($post_count > 20) {
            $strategies[] = array(
                'name' => 'Affiliate Marketing',
                'description' => 'Promote relevant products and earn commissions',
                'revenue_potential' => 'Medium-High',
                'difficulty' => 'Medium'
            );
        }

        $strategies[] = array(
            'name' => 'Membership/Subscriptions',
            'description' => 'Offer premium content to subscribers',
            'revenue_potential' => 'High',
            'difficulty' => 'Medium'
        );

        $strategies[] = array(
            'name' => 'Sponsored Content',
            'description' => 'Brands pay for promotional posts',
            'revenue_potential' => 'High',
            'difficulty' => 'Hard'
        );

        return $strategies;
    }

    private function calculate_revenue_potential() {
        $post_count = intval(wp_count_posts()->publish ?? 0);
        $base_revenue = $post_count * 5;

        return array(
            'conservative' => '$' . number_format($base_revenue, 2),
            'moderate' => '$' . number_format($base_revenue * 2.5, 2),
            'optimistic' => '$' . number_format($base_revenue * 5, 2)
        );
    }

    private function log_analytics($metric_type, $value) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'revopt_analytics';
        $wpdb->insert($table_name, array(
            'metric_type' => sanitize_text_field($metric_type),
            'metric_value' => intval($value)
        ));
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        echo '<div class="wrap revopt-dashboard">';
        echo '<h1>Revenue Optimizer Pro Dashboard</h1>';
        echo '<div class="revopt-welcome">Welcome to Revenue Optimizer Pro! Analyze your site and discover optimal monetization strategies.</div>';
        echo '<button class="button button-primary" id="revopt-analyze-btn">Analyze My Site Now</button>';
        echo '<div id="revopt-analysis-results"></div>';
        echo '</div>';
    }

    public function render_strategies() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        echo '<div class="wrap revopt-strategies">';
        echo '<h1>Monetization Strategies</h1>';
        echo '<p>Choose the strategies that best fit your site and audience.</p>';
        echo '<div class="revopt-strategy-list">';
        echo '<h2>Available Strategies:</h2>';
        echo '<ul>';
        echo '<li><strong>Display Ads</strong> - Simple setup, great for traffic-heavy blogs</li>';
        echo '<li><strong>Affiliate Marketing</strong> - Ideal for niche blogs with product recommendations</li>';
        echo '<li><strong>Digital Products</strong> - Scalable income from original content or tools</li>';
        echo '<li><strong>Memberships</strong> - Offer gated premium content or community access</li>';
        echo '<li><strong>Sponsored Content</strong> - Brands pay for promotional posts</li>';
        echo '<li><strong>Services/Consulting</strong> - Leverage your expertise for client work</li>';
        echo '</ul>';
        echo '</div>';
        echo '</div>';
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        echo '<div class="wrap revopt-settings">';
        echo '<h1>Revenue Optimizer Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('revopt_settings_group');
        echo '<table class="form-table">';
        echo '<tr><th>Plugin Status</th><td><input type="checkbox" name="revopt_enabled" value="1" checked /> Enabled</td></tr>';
        echo '<tr><th>Premium Features</th><td><p>Upgrade to Premium for advanced analytics and A/B testing!</p><button class="button button-success">Upgrade to Premium</button></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function render_signup_form() {
        ob_start();
        echo '<div class="revopt-signup-form">';
        echo '<h3>Join Revenue Optimizer Pro</h3>';
        echo '<p>Get personalized monetization recommendations for your WordPress site.</p>';
        echo '<form id="revopt-signup" method="post">';
        echo '<input type="email" name="email" placeholder="Your email" required />';
        echo '<input type="text" name="website" placeholder="Your website URL" required />';
        echo '<button type="submit" class="button button-primary">Get Started Free</button>';
        echo '</form>';
        echo '</div>';
        return ob_get_clean();
    }
}

function revopt_init() {
    RevenueOptimizerPro::get_instance();
}

add_action('plugins_loaded', 'revopt_init');
