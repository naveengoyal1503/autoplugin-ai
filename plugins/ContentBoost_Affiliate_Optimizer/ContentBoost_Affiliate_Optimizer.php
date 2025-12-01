<?php
/*
Plugin Name: ContentBoost Affiliate Optimizer
Plugin URI: https://contentboost.local
Description: Automatically convert product mentions to affiliate links with tracking and analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Affiliate_Optimizer.php
License: GPL v2 or later
Text Domain: contentboost
*/

if (!defined('ABSPATH')) exit;

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTBOOST_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentBoostAffiliateOptimizer {
    private static $instance = null;
    private $db_version = '1.0';
    private $option_name = 'contentboost_options';

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_filter('the_content', array($this, 'process_content_links'), 15);
        add_action('wp_ajax_cb_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_cb_get_analytics', array($this, 'ajax_get_analytics'));
        add_action('template_redirect', array($this, 'handle_affiliate_redirect'));
    }

    public function activate() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentboost_links (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id mediumint(9) NOT NULL,
            product_name varchar(255) NOT NULL,
            affiliate_url text NOT NULL,
            network varchar(100) NOT NULL,
            clicks bigint(20) DEFAULT 0,
            conversions bigint(20) DEFAULT 0,
            commission_earned decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}contentboost_clicks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id mediumint(9) NOT NULL,
            click_time datetime DEFAULT CURRENT_TIMESTAMP,
            user_ip varchar(45),
            user_agent text,
            PRIMARY KEY (id),
            KEY link_id (link_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('contentboost_db_version', $this->db_version);
        add_option($this->option_name, array(
            'networks' => array(),
            'auto_detect' => false,
            'detection_keywords' => array()
        ));
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost Affiliate Optimizer',
            'ContentBoost',
            'manage_options',
            'contentboost',
            array($this, 'render_admin_page'),
            'dashicons-link',
            25
        );
        
        add_submenu_page(
            'contentboost',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentboost',
            array($this, 'render_admin_page')
        );
        
        add_submenu_page(
            'contentboost',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-settings',
            array($this, 'render_settings_page')
        );
        
        add_submenu_page(
            'contentboost',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentboost-analytics',
            array($this, 'render_analytics_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentboost') === false) return;
        
        wp_enqueue_script('jquery');
        wp_enqueue_style('wp-admin');
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js@3/dist/chart.min.js', array(), '3.0', true);
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script('contentboost-tracker', CONTENTBOOST_PLUGIN_URL . 'js/tracker.js', array(), CONTENTBOOST_VERSION, true);
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>ContentBoost Affiliate Optimizer</h1>
            <div style="background:#fff;padding:20px;border-radius:5px;margin-top:20px;">
                <h2>Dashboard</h2>
                <p>Welcome to ContentBoost! Manage your affiliate links and track conversions.</p>
                <div id="cb-dashboard" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;">
                    <div style="background:#f0f0f0;padding:15px;border-radius:5px;">
                        <h3>Total Links</h3>
                        <p style="font-size:24px;font-weight:bold;">0</p>
                    </div>
                    <div style="background:#f0f0f0;padding:15px;border-radius:5px;">
                        <h3>Total Clicks</h3>
                        <p style="font-size:24px;font-weight:bold;">0</p>
                    </div>
                    <div style="background:#f0f0f0;padding:15px;border-radius:5px;">
                        <h3>Total Conversions</h3>
                        <p style="font-size:24px;font-weight:bold;">0</p>
                    </div>
                    <div style="background:#f0f0f0;padding:15px;border-radius:5px;">
                        <h3>Commission Earned</h3>
                        <p style="font-size:24px;font-weight:bold;">$0.00</p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>ContentBoost Settings</h1>
            <form method="post" action="admin-ajax.php">
                <?php wp_nonce_field('contentboost_settings'); ?>
                <input type="hidden" name="action" value="cb_save_settings">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="auto_detect">Enable Auto-Detection</label></th>
                        <td>
                            <input type="checkbox" id="auto_detect" name="auto_detect" value="1">
                            <p class="description">Automatically detect product mentions in your content</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_analytics_page() {
        ?>
        <div class="wrap">
            <h1>Analytics</h1>
            <div style="background:#fff;padding:20px;border-radius:5px;">
                <canvas id="analyticsChart"></canvas>
            </div>
        </div>
        <?php
    }

    public function process_content_links($content) {
        if (is_admin() || !is_singular('post')) return $content;
        
        $options = get_option($this->option_name);
        if (empty($options['networks'])) return $content;
        
        global $post;
        
        foreach ($options['networks'] as $network) {
            if (isset($network['keywords']) && is_array($network['keywords'])) {
                foreach ($network['keywords'] as $keyword) {
                    $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
                    $replacement = '<a href="' . esc_url($this->get_affiliate_redirect_url($post->ID, $keyword, $network['name'])) . '" class="contentboost-link" data-link-id="' . esc_attr($keyword) . '">$0</a>';
                    $content = preg_replace($pattern, $replacement, $content, 1);
                }
            }
        }
        
        return $content;
    }

    private function get_affiliate_redirect_url($post_id, $keyword, $network) {
        $redirect_url = add_query_arg(array(
            'cb_redirect' => base64_encode($network . '|' . $keyword . '|' . $post_id),
            'cb_ref' => get_home_url()
        ), home_url('/index.php'));
        
        return $redirect_url;
    }

    public function handle_affiliate_redirect() {
        if (!isset($_GET['cb_redirect'])) return;
        
        $data = explode('|', base64_decode($_GET['cb_redirect']));
        if (count($data) !== 3) return;
        
        list($network, $keyword, $post_id) = $data;
        
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}contentboost_links WHERE post_id = %d AND product_name = %s AND network = %s",
            $post_id, $keyword, $network
        ));
        
        if ($link) {
            $wpdb->insert($wpdb->prefix . 'contentboost_clicks', array(
                'link_id' => $link->id,
                'click_time' => current_time('mysql'),
                'user_ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT']
            ));
            
            $wpdb->update(
                $wpdb->prefix . 'contentboost_links',
                array('clicks' => $link->clicks + 1),
                array('id' => $link->id)
            );
        }
        
        wp_redirect('https://affiliate.example.com/' . urlencode($keyword));
        exit;
    }

    public function ajax_save_settings() {
        check_ajax_referer('contentboost_settings');
        
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        
        $settings = array(
            'auto_detect' => isset($_POST['auto_detect']) ? 1 : 0,
            'networks' => isset($_POST['networks']) ? array_map('sanitize_text_field', (array)$_POST['networks']) : array()
        );
        
        update_option($this->option_name, $settings);
        wp_send_json_success('Settings saved');
    }

    public function ajax_get_analytics() {
        check_ajax_referer('contentboost_analytics');
        
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        
        global $wpdb;
        $data = $wpdb->get_results("SELECT DATE(click_time) as date, COUNT(*) as clicks FROM {$wpdb->prefix}contentboost_clicks GROUP BY DATE(click_time) ORDER BY date DESC LIMIT 30");
        
        wp_send_json_success($data);
    }
}

ContentBoostAffiliateOptimizer::getInstance();
