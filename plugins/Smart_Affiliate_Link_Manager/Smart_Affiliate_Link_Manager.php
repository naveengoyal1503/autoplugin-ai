/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate-manager
 * Description: Automatically cloaks, tracks clicks, and displays affiliate links with performance analytics to boost conversions and earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateManager {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
        add_action('wp_ajax_sam_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sam_track_click', array($this, 'track_click'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function activate() {
        add_option('sam_links', array());
        add_option('sam_stats', array());
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function cloak_links($content) {
        $links = get_option('sam_links', array());
        foreach ($links as $short => $data) {
            $pattern = '/\b' . preg_quote($short, '/') . '\b/i';
            $content = preg_replace($pattern, $this->generate_cloaked_link($short), $content);
        }
        return $content;
    }

    private function generate_cloaked_link($shortcode) {
        $nonce = wp_create_nonce('sam_click');
        return '<a href="' . admin_url('admin-ajax.php?action=sam_track_click&link=' . urlencode($shortcode) . '&nonce=' . $nonce) . '" target="_blank" rel="nofollow noopener">' . $shortcode . '</a>';
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts);
        if (empty($atts['id'])) return '';
        $links = get_option('sam_links', array());
        if (!isset($links[$atts['id']])) return '';
        $link = $links[$atts['id']]['url'];
        $name = $links[$atts['id']]['name'];
        $nonce = wp_create_nonce('sam_click');
        return '<a href="' . admin_url('admin-ajax.php?action=sam_track_click&link=' . urlencode($atts['id']) . '&nonce=' . $nonce) . '" target="_blank" rel="nofollow noopener">' . esc_html($name) . '</a>';
    }

    public function track_click() {
        if (!wp_verify_nonce($_GET['nonce'], 'sam_click')) {
            wp_die('Unauthorized');
        }
        $link_id = sanitize_text_field($_GET['link']);
        $stats = get_option('sam_stats', array());
        if (!isset($stats[$link_id])) {
            $stats[$link_id] = array('clicks' => 0, 'date' => current_time('mysql'));
        }
        $stats[$link_id]['clicks']++;
        update_option('sam_stats', $stats);
        $links = get_option('sam_links', array());
        if (isset($links[$link_id]['url'])) {
            wp_redirect($links[$link_id]['url'], 302);
            exit;
        }
        wp_die('Link not found');
    }

    public function add_dashboard_widget() {
        wp_add_dashboard_widget('sam_stats_widget', 'Affiliate Link Stats', array($this, 'dashboard_widget'));
    }

    public function dashboard_widget() {
        $stats = get_option('sam_stats', array());
        if (empty($stats)) {
            echo '<p>No stats yet. Add some links!</p>';
            return;
        }
        echo '<ul>';
        foreach ($stats as $id => $data) {
            echo '<li>' . esc_html($id) . ': ' . intval($data['clicks']) . ' clicks</li>';
        }
        echo '</ul>';
        echo '<p><a href="' . admin_url('options-general.php?page=sam-settings') . '">Manage Links</a></p>';
    }

    public function add_admin_menu() {
        add_options_page('Smart Affiliate Manager', 'Affiliate Links', 'manage_options', 'sam-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $links = array();
            if (isset($_POST['links']) && is_array($_POST['links'])) {
                foreach ($_POST['links'] as $id => $data) {
                    if (!empty($data['url']) && !empty($data['name'])) {
                        $links[$id] = array(
                            'url' => esc_url_raw($data['url']),
                            'name' => sanitize_text_field($data['name'])
                        );
                    }
                }
            }
            update_option('sam_links', $links);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('sam_links', array());
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Link ID</th>
                        <th>Affiliate URL</th>
                        <th>Display Name</th>
                    </tr>
                    <?php foreach ($links as $id => $data): ?>
                    <tr>
                        <td><input type="text" name="links[<?php echo esc_attr($id); ?>][id]" value="<?php echo esc_attr($id); ?>" /></td>
                        <td><input type="url" name="links[<?php echo esc_attr($id); ?>][url]" value="<?php echo esc_attr($data['url']); ?>" style="width: 300px;" /></td>
                        <td><input type="text" name="links[<?php echo esc_attr($id); ?>][name]" value="<?php echo esc_attr($data['name']); ?>" /></td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td><input type="text" name="new_id" placeholder="new-link-id" /></td>
                        <td><input type="url" name="new_url" placeholder="https://affiliate.com/product" style="width: 300px;" /></td>
                        <td><input type="text" name="new_name" placeholder="Product Name" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage</h2>
            <p>Use <code>[afflink id="your-link-id"]</code> shortcode or just type <code>your-link-id</code> in posts.</p>
        </div>
        <?php
    }
}

SmartAffiliateManager::get_instance();