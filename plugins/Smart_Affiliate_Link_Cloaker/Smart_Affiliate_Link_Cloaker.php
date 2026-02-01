/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Automatically cloaks, tracks, and monetizes affiliate links with click stats and dashboards.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloaker {
    private static $instance = null;
    public $db_version = '1.0';
    public $table_name;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'salc_clicks';
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_filter('the_content', array($this, 'cloak_links'));
        add_shortcode('salc_dashboard', array($this, 'dashboard_shortcode'));
        add_rewrite_rule('^salc/([a-zA-Z0-9_-]+)/?$', 'index.php?salc_id=$matches[1]', 'top');
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('template_redirect', array($this, 'handle_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(255) NOT NULL,
            original_url text NOT NULL,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY link_id (link_id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        if (get_option('salc_db_version') != $this->db_version) {
            $this->activate();
            update_option('salc_db_version', $this->db_version);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salc-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('salc-tracker', 'salc_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_menu_page('SALC Dashboard', 'Affiliate Cloaker', 'manage_options', 'salc-dashboard', array($this, 'admin_page'));
    }

    public function admin_scripts($hook) {
        if ('toplevel_page_salc-dashboard' != $hook) return;
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
    }

    public function cloak_links($content) {
        if (is_admin() || !is_singular()) return $content;
        preg_match_all('/href=["\'](https?:\/\/[^\s"\']+)["\']/i', $content, $matches);
        $links = $matches[1];
        foreach ($links as $url) {
            if (strpos($url, 'affiliate') !== false || strpos($url, '?ref=') !== false || strpos($url, 'amazon.com/') !== false) {
                $shortcode = $this->create_cloaked_link($url);
                $content = str_replace('href="' . $url . '"', 'href="' . $shortcode . '"', $content);
                $content = str_replace("href='" . $url . "'", "href='" . $shortcode . "'", $content);
            }
        }
        return $content;
    }

    private function create_cloaked_link($url) {
        global $wpdb;
        $link_id = md5($url);
        $wpdb->insert($this->table_name, array('link_id' => $link_id, 'original_url' => $url), array('%s', '%s'));
        return home_url('/salc/' . $link_id . '/');
    }

    public function query_vars($vars) {
        $vars[] = 'salc_id';
        return $vars;
    }

    public function handle_click() {
        if (get_query_var('salc_id')) {
            $link_id = sanitize_text_field(get_query_var('salc_id'));
            global $wpdb;
            $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE link_id = %s", $link_id));
            $link = $wpdb->get_row($wpdb->prepare("SELECT original_url FROM $this->table_name WHERE link_id = %s", $link_id));
            if ($link) {
                wp_redirect($link->original_url, 301);
                exit;
            }
        }
    }

    public function dashboard_shortcode($atts) {
        if (!current_user_can('manage_options')) return '';
        global $wpdb;
        $stats = $wpdb->get_results("SELECT link_id, original_url, clicks, created FROM $this->table_name ORDER BY created DESC LIMIT 10");
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $this->table_name");
        ob_start();
        ?>
        <div id="salc-dashboard">
            <h3>Affiliate Link Stats</h3>
            <p>Total Clicks: <strong><?php echo $total_clicks; ?></strong></p>
            <table>
                <tr><th>Link</th><th>Clicks</th><th>Date</th></tr>
                <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><a href="<?php echo esc_url($stat->original_url); ?>" target="_blank"><?php echo esc_html(substr($stat->original_url, 0, 50)); ?>...</a></td>
                    <td><?php echo $stat->clicks; ?></td>
                    <td><?php echo $stat->created; ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <p><em>Upgrade to Pro for charts, A/B testing & more!</em></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>Smart Affiliate Cloaker Pro</h1><p>Use [salc_dashboard] shortcode on any page for stats.</p><p><a href="https://example.com/pro" class="button button-primary">Upgrade to Pro ($49/yr)</a></p></div>';
    }
}

SmartAffiliateCloaker::get_instance();

// Dummy tracker.js content (inline for single file)
function salc_inline_tracker() {
    if (!wp_script_is('salc-tracker', 'enqueued')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('a[href^="/salc/"]').on('click', function(e) {
            var href = $(this).attr('href');
            $.post(salc_ajax.ajaxurl, {action: 'salc_track', url: href});
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'salc_inline_tracker');

add_action('wp_ajax_salc_track', function() {
    error_log('SALC Track: ' . $_POST['url']);
    wp_die();
});