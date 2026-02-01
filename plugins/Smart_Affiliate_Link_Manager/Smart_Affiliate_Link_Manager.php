/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automatically cloaks, tracks, and optimizes affiliate links with analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateLinkManager {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sal_update_link', array($this, 'ajax_update_link'));
        add_shortcode('sal_link', array($this, 'shortcode_link'));
        add_filter('widget_text', 'shortcode_unautop');
        add_filter('the_content', 'shortcode_unautop');
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Create table on init if not exists
        global $wpdb;
        $table = $wpdb->prefix . 'sal_links';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            keyword varchar(50) NOT NULL,
            affiliate_url text NOT NULL,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY keyword (keyword)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Auto-cloak links in content
        add_filter('the_content', array($this, 'cloak_links'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-frontend', plugin_dir_url(__FILE__) . 'sal-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-frontend', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Links', 'Affiliate Links', 'manage_options', 'sal-links', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sal_submit'])) {
            $keyword = sanitize_text_field($_POST['keyword']);
            $url = esc_url_raw($_POST['url']);
            global $wpdb;
            $table = $wpdb->prefix . 'sal_links';
            $wpdb->replace($table, array('keyword' => $keyword, 'affiliate_url' => $url));
            echo '<div class="notice notice-success"><p>Link saved!</p></div>';
        }
        $links = $this->get_links();
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Keyword</th>
                        <td><input type="text" name="keyword" value="<?php echo isset($_POST['keyword']) ? $_POST['keyword'] : ''; ?>" placeholder="buy now" required /></td>
                    </tr>
                    <tr>
                        <th>Affiliate URL</th>
                        <td><input type="url" name="url" style="width:100%;" value="<?php echo isset($_POST['url']) ? $_POST['url'] : ''; ?>" required /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Your Links</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Keyword</th><th>URL</th><th>Clicks</th></tr></thead>
                <tbody>
                <?php foreach ($links as $link): ?>
                    <tr><td><?php echo esc_html($link->keyword); ?></td><td><?php echo esc_html($link->affiliate_url); ?></td><td><?php echo $link->clicks; ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p><strong>Upgrade to Pro:</strong> A/B testing, reports, unlimited links. <a href="#">Buy now $49/year</a></p>
        </div>
        <?php
    }

    public function ajax_update_link() {
        if (!wp_verify_nonce($_POST['nonce'], 'sal_nonce')) return;
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE {$wpdb->prefix}sal_links SET clicks = clicks + 1 WHERE keyword = %s", $_POST['keyword']));
        wp_die();
    }

    private function get_links() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sal_links");
    }

    public function cloak_links($content) {
        global $wpdb;
        $links = $this->get_links();
        foreach ($links as $link) {
            $placeholder = '/sal/' . $link->keyword;
            $content = preg_replace('/\b' . preg_quote($link->keyword, '/') . '\b/i', '<a href="' . $placeholder . '" class="sal-link">$0</a>', $content);
        }
        return $content;
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('keyword' => ''), $atts);
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sal_links WHERE keyword = %s", $atts['keyword']));
        if (!$link) return '';
        return '<a href="/sal/' . esc_attr($link->keyword) . '" class="sal-link" data-keyword="' . esc_attr($link->keyword) . '">' . $atts['keyword'] . '</a>';
    }

    public function activate() {
        $this->init();
    }
}

SmartAffiliateLinkManager::get_instance();

add_action('template_redirect', function() {
    if (strpos($_SERVER['REQUEST_URI'], '/sal/') === 0) {
        $keyword = str_replace('/sal/', '', $_SERVER['REQUEST_URI']);
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sal_links WHERE keyword = %s", $keyword));
        if ($link) {
            wp_redirect($link->affiliate_url, 301);
            exit;
        }
    }
});

// Frontend JS (inline for single file)
function sal_frontend_js() {
    ?><script>jQuery(document).ready(function($){$('.sal-link').click(function(e){e.preventDefault();var keyword=$(this).data('keyword');$.post(sal_ajax.ajaxurl,{action:'sal_update_link',keyword:keyword,nonce:'<?php echo wp_create_nonce('sal_nonce'); ?>'},function(){window.location=$(this).attr('href');});});});</script><?php
}
add_action('wp_footer', 'sal_frontend_js');