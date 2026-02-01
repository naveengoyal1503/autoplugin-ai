/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak and track affiliate links with analytics. Free version with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent direct access
class SmartAffiliateCloaker {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('sac_link', array($this, 'shortcode_link'));
        add_filter('the_content', array($this, 'cloak_links_in_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            $this->handle_ajax();
        }
        $this->create_table();
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Cloaker', 'SAC Pro', 'manage_options', 'smart-affiliate-cloaker', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sac_save'])) {
            update_option('sac_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('sac_api_key', '');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="Enter for premium features" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'sac_save'); ?>
            </form>
            <h2>Analytics</h2>
            <div id="sac-analytics"></div>
        </div>
        <?php
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('url' => '', 'text' => 'Click Here'), $atts);
        $id = uniqid('sac_');
        $cloaked = $this->cloak_url($atts['url']);
        return '<a href="' . esc_url($cloaked) . '" id="' . $id . '" class="sac-link">' . esc_html($atts['text']) . '</a>';
    }

    public function cloak_links_in_content($content) {
        if (!is_single()) return $content;
        preg_match_all('/href=["\']([^\"\']*aff=)["\']/i', $content, $matches);
        foreach ($matches[1] as $aff) {
            // Simplified cloaking for demo
            $content = str_replace($aff, site_url('/go/?u=' . base64_encode($aff)), $content);
        }
        return $content;
    }

    private function cloak_url($url) {
        $premium = get_option('sac_api_key');
        if ($premium && strlen($premium) > 10) {
            // Premium: Simulate short link
            return site_url('/go/premium/' . md5($url));
        }
        return site_url('/go/?u=' . base64_encode($url));
    }

    private function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'sac_clicks';
        $charset = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link text NOT NULL,
            ip varchar(100) NOT NULL,
            user_agent text NOT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function handle_ajax() {
        add_action('wp_ajax_sac_track', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_sac_track', array($this, 'track_click'));
    }

    public function track_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        global $wpdb;
        $link = sanitize_url($_POST['link']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = sanitize_text_field($_SERVER['HTTP_USER_AGENT']);
        $wpdb->insert($wpdb->prefix . 'sac_clicks', array('link' => $link, 'ip' => $ip, 'user_agent' => $ua));
        wp_send_json_success('Tracked');
    }

    public function activate() {
        $this->create_table();
    }

    public function deactivate() {
        // Cleanup optional
    }
}

SmartAffiliateCloaker::get_instance();

// AJAX handler for frontend tracking
add_action('wp_ajax_nopriv_sac_track', array(SmartAffiliateCloaker::get_instance(), 'track_click'));
add_action('wp_ajax_sac_track', array(SmartAffiliateCloaker::get_instance(), 'track_click'));

// Redirect handler
add_action('init', function() {
    if (isset($_GET['u']) || strpos($_SERVER['REQUEST_URI'], '/go/') !== false) {
        $url = base64_decode($_GET['u'] ?? '');
        if ($url && strpos($url, 'aff=') !== false) {
            // Track via JS or server-side
            wp_redirect($url, 301);
            exit;
        }
    }
});

// Free JS file content (embedded for single file)
/*
function sacTrack(link) {
    jQuery.post(sac_ajax.ajax_url, {
        action: 'sac_track',
        link: link,
        nonce: sac_ajax.nonce
    });
}
jQuery('.sac-link').click(function(e) {
    sacTrack(jQuery(this).attr('href'));
});
*/
?>