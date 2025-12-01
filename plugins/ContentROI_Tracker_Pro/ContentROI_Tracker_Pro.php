<?php
/*
Plugin Name: ContentROI Tracker Pro
Plugin URI: https://contentroi.local
Description: Advanced monetization analytics for affiliate links, ads, and sponsored content
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentROI_Tracker_Pro.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

define('CONTENTROI_VERSION', '1.0.0');
define('CONTENTROI_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTROI_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentROITracker {
    private $db_table;
    private $options_table;

    public function __construct() {
        global $wpdb;
        $this->db_table = $wpdb->prefix . 'contentroi_links';
        $this->options_table = $wpdb->prefix . 'options';
        
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_footer', array($this, 'track_clicks'));
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->db_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            link_url longtext NOT NULL,
            link_title varchar(255) NOT NULL,
            link_type varchar(50) NOT NULL,
            tracking_code varchar(100) UNIQUE NOT NULL,
            clicks bigint(20) DEFAULT 0,
            conversions bigint(20) DEFAULT 0,
            revenue decimal(10, 2) DEFAULT 0,
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            updated_date datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY tracking_code (tracking_code)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        update_option('contentroi_version', CONTENTROI_VERSION);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentROI Tracker',
            'ContentROI',
            'manage_options',
            'contentroi-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            25
        );
        
        add_submenu_page(
            'contentroi-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentroi-dashboard',
            array($this, 'render_dashboard')
        );
        
        add_submenu_page(
            'contentroi-dashboard',
            'Links',
            'Manage Links',
            'manage_options',
            'contentroi-links',
            array($this, 'render_links')
        );
        
        add_submenu_page(
            'contentroi-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentroi-settings',
            array($this, 'render_settings')
        );
    }

    public function enqueue_admin_assets() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'contentroi') !== false) {
            wp_enqueue_style('contentroi-admin', CONTENTROI_PLUGIN_URL . 'assets/admin.css');
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
            wp_enqueue_script('contentroi-admin', CONTENTROI_PLUGIN_URL . 'assets/admin.js', array('jquery', 'chart-js'), CONTENTROI_VERSION, true);
            wp_localize_script('contentroi-admin', 'contentoiObj', array(
                'nonce' => wp_create_nonce('contentroi_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php')
            ));
        }
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_script('contentroi-tracking', CONTENTROI_PLUGIN_URL . 'assets/tracking.js', array(), CONTENTROI_VERSION, true);
    }

    public function track_clicks() {
        ?>
        <script>
        document.addEventListener('click', function(e) {
            const link = e.target.closest('a[data-contentroi-code]');
            if (link) {
                const code = link.dataset.contentoroiCode;
                fetch('<?php echo rest_url('contentroi/v1/track'); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({tracking_code: code, action: 'click'})
                });
            }
        });
        </script>
        <?php
    }

    public function register_rest_routes() {
        register_rest_route('contentroi/v1', '/track', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_tracking'),
            'permission_callback' => '__return_true'
        ));
        
        register_rest_route('contentroi/v1', '/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_stats'),
            'permission_callback' => array($this, 'check_auth')
        ));
        
        register_rest_route('contentroi/v1', '/create-link', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_link'),
            'permission_callback' => array($this, 'check_auth')
        ));
    }

    public function handle_tracking($request) {
        global $wpdb;
        $params = $request->get_json_params();
        $tracking_code = sanitize_text_field($params['tracking_code'] ?? '');
        
        if (!$tracking_code) return new WP_REST_Response(['error' => 'Invalid tracking code'], 400);
        
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->db_table} SET clicks = clicks + 1 WHERE tracking_code = %s",
            $tracking_code
        ));
        
        return new WP_REST_Response(['success' => true]);
    }

    public function get_stats($request) {
        global $wpdb;
        $user_id = get_current_user_id();
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->db_table} WHERE user_id = %d",
            $user_id
        ));
        
        return new WP_REST_Response($stats);
    }

    public function create_link($request) {
        global $wpdb;
        $user_id = get_current_user_id();
        $params = $request->get_json_params();
        
        $link_url = esc_url_raw($params['url'] ?? '');
        $link_title = sanitize_text_field($params['title'] ?? '');
        $link_type = sanitize_text_field($params['type'] ?? 'affiliate');
        $tracking_code = 'croi_' . wp_generate_password(12, false);
        
        if (!$link_url) return new WP_REST_Response(['error' => 'Invalid URL'], 400);
        
        $wpdb->insert($this->db_table, array(
            'user_id' => $user_id,
            'link_url' => $link_url,
            'link_title' => $link_title,
            'link_type' => $link_type,
            'tracking_code' => $tracking_code
        ));
        
        return new WP_REST_Response(['tracking_code' => $tracking_code, 'success' => true]);
    }

    public function check_auth() {
        return is_user_logged_in() && current_user_can('manage_options');
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>ContentROI Dashboard</h1>
            <div id="contentroi-charts"></div>
            <div id="contentroi-stats"></div>
        </div>
        <?php
    }

    public function render_links() {
        global $wpdb;
        $user_id = get_current_user_id();
        $links = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->db_table} WHERE user_id = %d ORDER BY created_date DESC",
            $user_id
        ));
        ?>
        <div class="wrap">
            <h1>Manage Links</h1>
            <button id="contentroi-add-link" class="button button-primary">Add New Link</button>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Clicks</th>
                        <th>Revenue</th>
                        <th>Tracking Code</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo esc_html($link->link_title); ?></td>
                        <td><?php echo esc_html($link->link_type); ?></td>
                        <td><?php echo intval($link->clicks); ?></td>
                        <td>$<?php echo number_format($link->revenue, 2); ?></td>
                        <td><code><?php echo esc_html($link->tracking_code); ?></code></td>
                        <td><a href="#" class="button">Edit</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr(get_option('contentroi_api_key', '')); ?>"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new ContentROITracker();
?>