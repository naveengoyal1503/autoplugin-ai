/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate-manager
 * Description: Automatically converts keywords in your posts to cloaked affiliate links, tracks clicks, and displays performance stats to boost earnings effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-manager
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateManager {
    private static $instance = null;
    private $db_version = '1.0';
    private $table_name;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sam_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'replace_keywords'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_sam_track_click', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            keyword varchar(255) NOT NULL,
            affiliate_url text NOT NULL,
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY keyword (keyword)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('sam_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-manager');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Manager',
            'Affiliate Links',
            'manage_options',
            'smart-affiliate-manager',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        global $wpdb;

        if (isset($_POST['submit'])) {
            $keyword = sanitize_text_field($_POST['keyword']);
            $url = esc_url_raw($_POST['affiliate_url']);

            if (!empty($keyword) && !empty($url)) {
                $wpdb->replace(
                    $this->table_name,
                    array(
                        'keyword' => $keyword,
                        'affiliate_url' => $url,
                    ),
                    array('%s', '%s')
                );
                echo '<div class="notice notice-success"><p>Link saved!</p></div>';
            }
        }

        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY clicks DESC");
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Keyword</th>
                        <td><input type="text" name="keyword" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th>Affiliate URL</th>
                        <td><input type="url" name="affiliate_url" class="regular-text" required /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Links & Stats (Free: <?php echo count($links); ?>/5 max)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Keyword</th><th>URL</th><th>Clicks</th></tr></thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo esc_html($link->keyword); ?></td>
                        <td><?php echo esc_html($link->affiliate_url); ?></td>
                        <td><?php echo $link->clicks; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><strong>Pro Upgrade:</strong> Unlimited links, A/B testing, detailed analytics. <a href="#" onclick="alert('Pro features coming soon!')">Learn More</a></p>
        </div>
        <?php
    }

    public function replace_keywords($content) {
        if (is_feed() || is_admin()) return $content;

        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM $this->table_name");

        foreach ($links as $link) {
            $pattern = '/\b' . preg_quote($link->keyword, '/') . '\b/i';
            $replacement = '<a href="' . admin_url('admin-ajax.php?action=sam_track_click&id=' . $link->id) . '" class="sam-link" target="_blank" rel="nofollow">' . $link->keyword . '</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        return $content;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sam-script', plugin_dir_url(__FILE__) . 'sam.js', array('jquery'), '1.0', true);
        wp_localize_script('sam-script', 'sam_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_enqueue_scripts($hook) {
        if ('settings_page_smart-affiliate-manager' != get_current_screen()->id) return;
        wp_enqueue_script('sam-admin', plugin_dir_url(__FILE__) . 'sam-admin.js', array('jquery'), '1.0', true);
    }

    public function track_click() {
        global $wpdb;
        $id = intval($_GET['id']);

        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE id = %d", $id));

        $link = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM $this->table_name WHERE id = %d", $id));
        if ($link) {
            wp_redirect($link->affiliate_url);
            exit;
        }
    }
}

SmartAffiliateManager::get_instance();

// Note: Create empty sam.js and sam-admin.js files in plugin dir for JS (optional enhancements)
?>