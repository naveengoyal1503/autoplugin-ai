/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak and track affiliate links with analytics. Free core, pro add-ons available.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloaker {
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
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('wp_loaded', array($this, 'handle_redirects'));
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sal_save_link', array($this, 'ajax_save_link'));
        add_action('wp_ajax_nopriv_sal_save_link', array($this, 'ajax_save_link'));
    }

    public function activate() {
        add_option('sal_links', array());
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        add_rewrite_rule('^go/([^/]+)/?', 'index.php?sal_redirect=$matches[1]', 'top');
        add_rewrite_tag('%sal_redirect%', '([^&]+)');
        add_filter('query_vars', function($vars) {
            $vars[] = 'sal_redirect';
            return $vars;
        });
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-admin-js', plugin_dir_url(__FILE__) . 'sal-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sal-admin-js', 'sal_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sal_nonce')));
    }

    public function handle_redirects() {
        if (get_query_var('sal_redirect')) {
            $key = sanitize_text_field(get_query_var('sal_redirect'));
            $links = get_option('sal_links', array());
            if (isset($links[$key])) {
                $link = esc_url_raw($links[$key]['url']);
                $this->log_click($key);
                wp_redirect($link, 301);
                exit;
            }
        }
    }

    private function log_click($key) {
        $logs = get_option('sal_click_logs', array());
        $logs[] = array(
            'key' => $key,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'ua' => $_SERVER['HTTP_USER_AGENT'],
            'time' => current_time('mysql')
        );
        if (count($logs) > 1000) $logs = array_slice($logs, -500);
        update_option('sal_click_logs', $logs);
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $links = get_option('sal_links', array());
        if (isset($links[$atts['id']])) {
            return '<a href="' . home_url('/go/' . $atts['id'] . '/') . '" target="_blank" rel="nofollow">' . esc_html($links[$atts['id']]['name']) . '</a>';
        }
        return '';
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'sal-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sal_submit'])) {
            check_admin_referer('sal_settings');
            $links = get_option('sal_links', array());
            $id = sanitize_text_field($_POST['sal_id']);
            $name = sanitize_text_field($_POST['sal_name']);
            $url = esc_url_raw($_POST['sal_url']);
            $links[$id] = array('name' => $name, 'url' => $url);
            update_option('sal_links', $links);
            echo '<div class="notice notice-success"><p>Link saved!</p></div>';
        }
        $links = get_option('sal_links', array());
        $logs = get_option('sal_click_logs', array());
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Cloaker</h1>
            <form method="post">
                <?php wp_nonce_field('sal_settings'); ?>
                <table class="form-table">
                    <tr><th>ID (slug)</th><td><input type="text" name="sal_id" required /></td></tr>
                    <tr><th>Link Name</th><td><input type="text" name="sal_name" required /></td></tr>
                    <tr><th>Affiliate URL</th><td><input type="url" name="sal_url" style="width:50%;" required /></td></tr>
                </table>
                <p>Use shortcode: <code>[afflink id="your-id"]</code> or link: <code><?php echo home_url('/go/your-id/'); ?></code></p>
                <?php submit_button(); ?>
            </form>
            <h2>Links</h2>
            <ul><?php foreach($links as $id => $link): ?><li><?php echo esc_html($link['name']); ?> - <a href="<?php echo home_url('/go/'.$id.'/'); ?>" target="_blank"><?php echo home_url('/go/'.$id.'/'); ?></a> (<?php echo $this->get_click_count($id); ?> clicks)</li><?php endforeach; ?></ul>
            <h2>Recent Clicks (Free: Last 10)</h2>
            <ul><?php foreach(array_slice(array_reverse($logs), 0, 10) as $log): ?><li><?php echo esc_html($log['key']); ?> from <?php echo esc_html($log['ip']); ?> at <?php echo $log['time']; ?></li><?php endforeach; ?></ul>
            <p><strong>Pro Upgrade:</strong> Full analytics, A/B testing, geo-targeting. <a href="#pro">Buy now $49/year</a></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#submit').click(function() {
                $.post(ajaxurl, {
                    action: 'sal_save_link',
                    id: $('[name="sal_id"]').val(),
                    name: $('[name="sal_name"]').val(),
                    url: $('[name="sal_url"]').val(),
                    nonce: sal_ajax.nonce
                });
            });
        });
        </script>
        <?php
    }

    private function get_click_count($key) {
        $logs = get_option('sal_click_logs', array());
        return count(array_filter($logs, function($log) use ($key) { return $log['key'] === $key; }));
    }

    public function ajax_save_link() {
        check_ajax_referer('sal_nonce', 'nonce');
        // AJAX handler placeholder
        wp_die();
    }
}

SmartAffiliateCloaker::get_instance();

// Pro teaser - in real pro version, load separate file
if (false && file_exists(plugin_dir_path(__FILE__) . 'pro/sal-pro.php')) {
    include plugin_dir_path(__FILE__) . 'pro/sal-pro.php';
}
?>