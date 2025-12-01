<?php
/*
Plugin Name: AffiliateLinkOptimizer
Plugin URI: https://affiliatelinkoptimizer.com
Description: Track, manage, and optimize affiliate links with analytics and A/B testing
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLinkOptimizer.php
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit;
}

define('ALO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ALO_VERSION', '1.0.0');

class AffiliateLinkOptimizer {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'));
        add_shortcode('alo_link', array($this, 'renderAffiliateLink'));
        add_action('wp_ajax_alo_track_click', array($this, 'trackClick'));
        add_action('wp_ajax_nopriv_alo_track_click', array($this, 'trackClick'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}alo_links (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) NOT NULL,
            link_name VARCHAR(255) NOT NULL,
            original_url LONGTEXT NOT NULL,
            short_code VARCHAR(50) UNIQUE NOT NULL,
            clicks INT(11) DEFAULT 0,
            conversions INT(11) DEFAULT 0,
            revenue DECIMAL(10, 2) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function addAdminMenu() {
        add_menu_page(
            'Affiliate Link Optimizer',
            'Affiliate Links',
            'manage_options',
            'alo-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-link',
            25
        );
        add_submenu_page(
            'alo-dashboard',
            'All Links',
            'All Links',
            'manage_options',
            'alo-links',
            array($this, 'renderLinksPage')
        );
        add_submenu_page(
            'alo-dashboard',
            'Create Link',
            'Create Link',
            'manage_options',
            'alo-create',
            array($this, 'renderCreatePage')
        );
    }

    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'alo-') === false) return;
        wp_enqueue_style('alo-admin', ALO_PLUGIN_URL . 'assets/admin.css', array(), ALO_VERSION);
        wp_enqueue_script('alo-admin', ALO_PLUGIN_URL . 'assets/admin.js', array('jquery'), ALO_VERSION, true);
        wp_localize_script('alo-admin', 'amoAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function enqueueFrontendScripts() {
        wp_enqueue_script('alo-frontend', ALO_PLUGIN_URL . 'assets/frontend.js', array('jquery'), ALO_VERSION, true);
        wp_localize_script('alo-frontend', 'amoFrontend', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function renderDashboard() {
        global $wpdb;
        $table = $wpdb->prefix . 'alo_links';
        $stats = $wpdb->get_row("SELECT COUNT(*) as total_links, SUM(clicks) as total_clicks, SUM(conversions) as total_conversions, SUM(revenue) as total_revenue FROM $table WHERE user_id = " . get_current_user_id());
        ?>
        <div class="wrap">
            <h1>Affiliate Link Optimizer Dashboard</h1>
            <div class="alo-stats">
                <div class="stat-box"><h3><?php echo $stats->total_links ?: 0; ?></h3><p>Total Links</p></div>
                <div class="stat-box"><h3><?php echo $stats->total_clicks ?: 0; ?></h3><p>Total Clicks</p></div>
                <div class="stat-box"><h3><?php echo $stats->total_conversions ?: 0; ?></h3><p>Conversions</p></div>
                <div class="stat-box"><h3>$<?php echo number_format($stats->total_revenue ?: 0, 2); ?></h3><p>Revenue</p></div>
            </div>
        </div>
        <?php
    }

    public function renderLinksPage() {
        global $wpdb;
        $table = $wpdb->prefix . 'alo_links';
        $links = $wpdb->get_results("SELECT * FROM $table WHERE user_id = " . get_current_user_id() . " ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Your Affiliate Links</h1>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Link Name</th>
                        <th>Short Code</th>
                        <th>Clicks</th>
                        <th>Conversions</th>
                        <th>Revenue</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link) : ?>
                    <tr>
                        <td><?php echo esc_html($link->link_name); ?></td>
                        <td><code><?php echo esc_html($link->short_code); ?></code></td>
                        <td><?php echo $link->clicks; ?></td>
                        <td><?php echo $link->conversions; ?></td>
                        <td>$<?php echo number_format($link->revenue, 2); ?></td>
                        <td><?php echo date('M d, Y', strtotime($link->created_at)); ?></td>
                        <td><button class="button alo-delete" data-id="<?php echo $link->id; ?>">Delete</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderCreatePage() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('alo_create_nonce')) {
            $this->saveLink();
        }
        ?>
        <div class="wrap">
            <h1>Create New Affiliate Link</h1>
            <form method="post" class="alo-form">
                <?php wp_nonce_field('alo_create_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="link_name">Link Name</label></th>
                        <td><input type="text" id="link_name" name="link_name" required class="regular-text"/></td>
                    </tr>
                    <tr>
                        <th><label for="original_url">Original URL</label></th>
                        <td><input type="url" id="original_url" name="original_url" required class="regular-text"/></td>
                    </tr>
                </table>
                <?php submit_button('Create Link'); ?>
            </form>
        </div>
        <?php
    }

    public function saveLink() {
        global $wpdb;
        $link_name = sanitize_text_field($_POST['link_name']);
        $original_url = esc_url($_POST['original_url']);
        $short_code = 'alo_' . substr(md5(time()), 0, 8);
        $wpdb->insert(
            $wpdb->prefix . 'alo_links',
            array(
                'user_id' => get_current_user_id(),
                'link_name' => $link_name,
                'original_url' => $original_url,
                'short_code' => $short_code
            )
        );
    }

    public function renderAffiliateLink($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}alo_links WHERE short_code = %s", $atts['id']));
        if (!$link) return '';
        return '<a href="#" class="alo-track-link" data-link-id="' . $link->id . '" data-url="' . esc_url($link->original_url) . '" target="_blank">' . esc_html($link->link_name) . '</a>';
    }

    public function trackClick() {
        global $wpdb;
        $link_id = intval($_POST['link_id']);
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}alo_links SET clicks = clicks + 1 WHERE id = %d", $link_id));
        wp_send_json_success();
    }
}

AffiliateLinkOptimizer::getInstance();
?>