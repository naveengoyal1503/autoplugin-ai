<?php
/*
Plugin Name: ContentBoost Affiliate Manager
Plugin URI: https://contentboost.local
Description: Monetize your WordPress site with advanced affiliate link management and performance tracking
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Affiliate_Manager.php
License: GPL v2 or later
Text Domain: contentboost-affiliate
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PATH', plugin_dir_path(__FILE__));
define('CONTENTBOOST_URL', plugin_dir_url(__FILE__));

class ContentBoostAffiliateManager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
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
        add_action('init', array($this, 'register_shortcodes'));
        add_filter('the_content', array($this, 'auto_link_keywords'));
        add_action('wp_ajax_cb_save_affiliate_link', array($this, 'save_affiliate_link'));
        add_action('wp_ajax_nopriv_cb_track_click', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_affiliate_links';
        $stats_table = $wpdb->prefix . 'contentboost_link_stats';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(100) NOT NULL UNIQUE,
            affiliate_url text NOT NULL,
            display_text varchar(255),
            network varchar(100),
            keywords text,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $stats_sql = "CREATE TABLE IF NOT EXISTS $stats_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(100) NOT NULL,
            clicks mediumint(9) DEFAULT 0,
            ctr decimal(5,2) DEFAULT 0,
            last_clicked datetime,
            PRIMARY KEY (id),
            FOREIGN KEY (link_id) REFERENCES $table_name(link_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($stats_sql);

        add_option('contentboost_version', CONTENTBOOST_VERSION);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost Affiliate Manager',
            'ContentBoost',
            'manage_options',
            'contentboost-dashboard',
            array($this, 'dashboard_page'),
            'dashicons-chart-line',
            25
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'contentboost-links',
            array($this, 'links_page')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentboost-analytics',
            array($this, 'analytics_page')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentboost-settings',
            array($this, 'settings_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentboost') === false) return;

        wp_enqueue_style(
            'contentboost-admin-style',
            CONTENTBOOST_URL . 'admin/css/admin.css',
            array(),
            CONTENTBOOST_VERSION
        );

        wp_enqueue_script(
            'contentboost-admin-script',
            CONTENTBOOST_URL . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-tabs'),
            CONTENTBOOST_VERSION,
            true
        );

        wp_localize_script('contentboost-admin-script', 'ContentBoost', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentboost_nonce')
        ));
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script(
            'contentboost-tracker',
            CONTENTBOOST_URL . 'frontend/js/tracker.js',
            array(),
            CONTENTBOOST_VERSION,
            true
        );

        wp_localize_script('contentboost-tracker', 'ContentBoostTracker', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentboost_tracking')
        ));
    }

    public function dashboard_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_affiliate_links';
        $stats_table = $wpdb->prefix . 'contentboost_link_stats';

        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $stats_table");
        ?>
        <div class="wrap">
            <h1>ContentBoost Affiliate Manager Dashboard</h1>
            <div class="contentboost-dashboard">
                <div class="dashboard-card">
                    <h2><?php echo esc_html($total_links); ?></h2>
                    <p>Total Affiliate Links</p>
                </div>
                <div class="dashboard-card">
                    <h2><?php echo esc_html($total_clicks ?: 0); ?></h2>
                    <p>Total Clicks</p>
                </div>
                <div class="dashboard-card">
                    <h2><?php echo get_option('contentboost_version'); ?></h2>
                    <p>Plugin Version</p>
                </div>
            </div>
            <div class="contentboost-info">
                <h3>Getting Started</h3>
                <p>Welcome to ContentBoost! Here's how to get started:</p>
                <ol>
                    <li>Navigate to <strong>Affiliate Links</strong> to add your affiliate URLs</li>
                    <li>Use the <strong>Analytics</strong> page to track performance</li>
                    <li>Insert affiliate links using <strong>[contentboost_link]</strong> shortcode or auto-link keywords</li>
                </ol>
            </div>
        </div>
        <?php
    }

    public function links_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_affiliate_links';
        ?>
        <div class="wrap">
            <h1>Manage Affiliate Links</h1>
            <form method="post" action="" class="contentboost-form">
                <?php wp_nonce_field('contentboost_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="link_id">Link ID</label></th>
                        <td><input type="text" name="link_id" id="link_id" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_url">Affiliate URL</label></th>
                        <td><input type="url" name="affiliate_url" id="affiliate_url" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="display_text">Display Text</label></th>
                        <td><input type="text" name="display_text" id="display_text" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="network">Affiliate Network</label></th>
                        <td>
                            <select name="network" id="network" class="regular-text">
                                <option value="amazon">Amazon Associates</option>
                                <option value="custom">Custom Network</option>
                                <option value="cj">Commission Junction</option>
                                <option value="other">Other</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="keywords">Auto-Link Keywords (comma-separated)</label></th>
                        <td><textarea name="keywords" id="keywords" class="large-text" rows="3"></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Add Affiliate Link'); ?>
            </form>

            <h2>Your Affiliate Links</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Link ID</th>
                        <th>Affiliate URL</th>
                        <th>Network</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $links = $wpdb->get_results("SELECT * FROM $table_name");
                    foreach ($links as $link) {
                        echo '<tr>';
                        echo '<td>' . esc_html($link->link_id) . '</td>';
                        echo '<td><a href="' . esc_url($link->affiliate_url) . '" target="_blank">' . esc_html(substr($link->affiliate_url, 0, 50)) . '...</a></td>';
                        echo '<td>' . esc_html($link->network) . '</td>';
                        echo '<td>' . esc_html(date('M d, Y', strtotime($link->created_date))) . '</td>';
                        echo '<td><a href="#" class="button button-small">Delete</a></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function analytics_page() {
        global $wpdb;
        $stats_table = $wpdb->prefix . 'contentboost_link_stats';
        ?>
        <div class="wrap">
            <h1>Analytics</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Link ID</th>
                        <th>Clicks</th>
                        <th>CTR (%)</th>
                        <th>Last Clicked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stats = $wpdb->get_results("SELECT * FROM $stats_table ORDER BY clicks DESC");
                    foreach ($stats as $stat) {
                        echo '<tr>';
                        echo '<td>' . esc_html($stat->link_id) . '</td>';
                        echo '<td>' . esc_html($stat->clicks) . '</td>';
                        echo '<td>' . esc_html(number_format($stat->ctr, 2)) . '%</td>';
                        echo '<td>' . esc_html($stat->last_clicked ?: 'Never') . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>ContentBoost Settings</h1>
            <form method="post" action="options.php" class="contentboost-settings-form">
                <?php settings_fields('contentboost_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="auto_link_enabled">Enable Auto-Linking</label></th>
                        <td>
                            <input type="checkbox" name="auto_link_enabled" id="auto_link_enabled" value="1" <?php checked(get_option('auto_link_enabled'), 1); ?>> 
                            <p class="description">Automatically link keywords to affiliate URLs in your content</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="link_limit_per_post">Links per Post</label></th>
                        <td>
                            <input type="number" name="link_limit_per_post" id="link_limit_per_post" value="<?php echo esc_attr(get_option('link_limit_per_post', 3)); ?>" min="1" max="10">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function register_shortcodes() {
        add_shortcode('contentboost_link', array($this, 'shortcode_affiliate_link'));
    }

    public function shortcode_affiliate_link($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'text' => 'Click Here'
        ), $atts);

        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_affiliate_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE link_id = %s", $atts['id']));

        if ($link) {
            $display_text = !empty($link->display_text) ? $link->display_text : $atts['text'];
            return '<a href="' . esc_url($link->affiliate_url) . '" class="contentboost-affiliate-link" data-link-id="' . esc_attr($link->link_id) . '" target="_blank" rel="noopener noreferrer">' . esc_html($display_text) . '</a>';
        }

        return '';
    }

    public function auto_link_keywords($content) {
        if (!get_option('auto_link_enabled')) {
            return $content;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_affiliate_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name WHERE keywords IS NOT NULL AND keywords != ''");

        $link_count = 0;
        $limit = (int)get_option('link_limit_per_post', 3);

        foreach ($links as $link) {
            if ($link_count >= $limit) break;

            $keywords = array_map('trim', explode(',', $link->keywords));

            foreach ($keywords as $keyword) {
                if (!empty($keyword) && $link_count < $limit && strpos($content, $keyword) !== false) {
                    $replacement = '<a href="' . esc_url($link->affiliate_url) . '" class="contentboost-auto-link" data-link-id="' . esc_attr($link->link_id) . '" target="_blank" rel="noopener noreferrer">' . esc_html($keyword) . '</a>';
                    $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b(?!<)/i', $replacement, $content, 1);
                    $link_count++;
                }
            }
        }

        return $content;
    }

    public function save_affiliate_link() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contentboost_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'contentboost_affiliate_links';

        $link_id = sanitize_text_field($_POST['link_id']);
        $affiliate_url = esc_url_raw($_POST['affiliate_url']);
        $display_text = sanitize_text_field($_POST['display_text']);
        $network = sanitize_text_field($_POST['network']);
        $keywords = sanitize_text_field($_POST['keywords']);

        $result = $wpdb->insert(
            $table_name,
            array(
                'link_id' => $link_id,
                'affiliate_url' => $affiliate_url,
                'display_text' => $display_text,
                'network' => $network,
                'keywords' => $keywords
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            wp_send_json_success('Link added successfully');
        } else {
            wp_send_json_error('Failed to add link');
        }
    }

    public function track_click() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contentboost_tracking')) {
            wp_send_json_error('Invalid nonce');
        }

        $link_id = sanitize_text_field($_POST['link_id']);

        global $wpdb;
        $stats_table = $wpdb->prefix . 'contentboost_link_stats';
        $table_name = $wpdb->prefix . 'contentboost_affiliate_links';

        $exists = $wpdb->get_row($wpdb->prepare("SELECT * FROM $stats_table WHERE link_id = %s", $link_id));

        if ($exists) {
            $wpdb->update(
                $stats_table,
                array(
                    'clicks' => $exists->clicks + 1,
                    'last_clicked' => current_time('mysql')
                ),
                array('link_id' => $link_id),
                array('%d', '%s'),
                array('%s')
            );
        } else {
            $wpdb->insert(
                $stats_table,
                array(
                    'link_id' => $link_id,
                    'clicks' => 1,
                    'last_clicked' => current_time('mysql')
                ),
                array('%s', '%d', '%s')
            );
        }

        wp_send_json_success('Click tracked');
    }
}

ContentBoostAffiliateManager::get_instance();
?>