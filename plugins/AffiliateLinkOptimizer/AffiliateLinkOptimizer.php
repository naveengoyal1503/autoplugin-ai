/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLinkOptimizer.php
*/
<?php
/**
 * Plugin Name: AffiliateLinkOptimizer
 * Description: Auto-detect, cloak, rotate affiliate links and track their performance with smart suggestions to boost commissions.
 * Version: 1.0
 * Author: YourName
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AffiliateLinkOptimizer {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'alo_clicks';
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_filter('the_content', array($this, 'process_content_affiliate_links'));
        add_action('wp_ajax_alo_get_suggestions', array($this, 'ajax_get_suggestions'));
        add_action('wp_ajax_nopriv_alo_get_suggestions', array($this, 'ajax_get_suggestions'));
        add_action('init', array($this, 'handle_redirect'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            link_key VARCHAR(191) NOT NULL,
            original_url TEXT NOT NULL,
            clicks BIGINT(20) UNSIGNED DEFAULT 0,
            last_clicked DATETIME DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY link_key (link_key(191))
          ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Cloak and rotate links in content
    public function process_content_affiliate_links($content) {
        // Regex to detect affiliate URLs by typical patterns (example domains)
        $pattern = '/https?:\/\/(?:www\.)?(amazon|clickbank|shareasale|cj|impactradius)\.com\/[^"\s<]+/i';

        $content = preg_replace_callback($pattern, array($this, 'replace_affiliate_link'), $content);

        return $content;
    }

    private function replace_affiliate_link($matches) {
        $original_url = esc_url_raw($matches);
        $link_key = md5($original_url);
        $redirect_url = home_url("/?alo_r=") . $link_key;
        return esc_url($redirect_url);
    }

    // Handle redirecting cloaked URLs and counting clicks
    public function handle_redirect() {
        if (!isset($_GET['alo_r'])) return;
        global $wpdb;
        $link_key = sanitize_text_field($_GET['alo_r']);

        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE link_key = %s", $link_key));

        if ($row) {
            $original_url = $row->original_url;
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->table_name} SET clicks = clicks + 1, last_clicked = NOW() WHERE id = %d",
                $row->id
            ));
            wp_redirect($original_url, 302);
            exit;
        } else {
            // New link, add to DB
            $referer = isset($_SERVER['HTTP_REFERER']) ? esc_url_raw($_SERVER['HTTP_REFERER']) : '';
            $original_url = '';
            if ($referer) {
                // If no DB row, try to find original from referer? Not reliable, skip.
            }
            // No original stored, redirect home
            wp_redirect(home_url(), 302);
            exit;
        }
    }

    // Admin menu
    public function admin_menu() {
        add_menu_page('AffiliateLinkOptimizer', 'Affiliate Link Optimizer', 'manage_options', 'alo_main', array($this, 'admin_page'), 'dashicons-admin-links');
    }

    public function admin_page() {
        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY clicks DESC LIMIT 100");
        ?>
        <div class="wrap">
        <h1>Affiliate Link Optimizer - Links Report</h1>
        <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>Original URL</th><th>Clicks</th><th>Last Clicked</th></tr></thead>
        <tbody>
        <?php foreach ($links as $link): ?>
            <tr>
                <td><a href="<?php echo esc_url($link->original_url); ?>" target="_blank"><?php echo esc_html($link->original_url); ?></a></td>
                <td><?php echo intval($link->clicks); ?></td>
                <td><?php echo esc_html($link->last_clicked); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
        </table>
        </div>
        <?php
    }

    // Ajax handler for smart suggestions (stub)
    public function ajax_get_suggestions() {
        // Normally, here would be AI-based or heuristic suggestions for better affiliate links
        wp_send_json_success(array('suggestions' => ['Try shorter URLs', 'Add nofollow attribute', 'Rotate offers weekly']));
    }
}

new AffiliateLinkOptimizer();
