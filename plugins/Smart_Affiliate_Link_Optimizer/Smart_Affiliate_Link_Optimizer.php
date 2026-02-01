/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Optimizer
 * Plugin URI: https://example.com/smart-affiliate-optimizer
 * Description: Automatically optimizes and cloaks affiliate links, tracks clicks, and boosts conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateOptimizer {
    private static $instance = null;
    public $options;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('sao_options', array('api_key' => '', 'is_premium' => false));
        load_plugin_textdomain('smart-affiliate-optimizer');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sao-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sao-tracker', 'sao_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sao_nonce')));
    }

    public function cloak_links($content) {
        if (is_feed() || is_admin()) return $content;
        preg_match_all('/href=["\'](https?:\/\/[^\?]+\?[^\"\']*?(?:aff|ref|tag)=[^\"\']*?)[\"\']/i', $content, $matches);
        foreach ($matches[1] as $url) {
            $cloaked = $this->cloak_url($url);
            $content = str_replace($url, $cloaked, $content);
        }
        return $content;
    }

    private function cloak_url($url) {
        $base = home_url('/go/');
        $id = md5($url);
        update_option('sao_redirect_' . $id, $url);
        return $base . $id;
    }

    public function handle_redirect() {
        if (strpos($_SERVER['REQUEST_URI'], '/go/') === 0) {
            $id = basename($_SERVER['REQUEST_URI']);
            $url = get_option('sao_redirect_' . $id);
            if ($url) {
                $this->track_click($id);
                wp_redirect($url, 301);
                exit;
            }
        }
    }

    private function track_click($id) {
        $clicks = get_option('sao_clicks', array());
        $clicks[$id] = isset($clicks[$id]) ? $clicks[$id] + 1 : 1;
        update_option('sao_clicks', $clicks);
        // Premium: Send to analytics API
        if ($this->options['is_premium']) {
            wp_remote_post('https://api.example.com/track', array(
                'body' => array('id' => $id, 'clicks' => $clicks[$id], 'key' => $this->options['api_key'])
            ));
        }
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Optimizer', 'Affiliate Optimizer', 'manage_options', 'sao-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->options['api_key'] = sanitize_text_field($_POST['api_key']);
            $this->options['is_premium'] = isset($_POST['is_premium']);
            update_option('sao_options', $this->options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($this->options['api_key']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Enable Premium Features</th>
                        <td><input type="checkbox" name="is_premium" <?php checked($this->options['is_premium']); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Click Stats</h2>
            <pre><?php print_r(get_option('sao_clicks', array())); ?></pre>
            <?php if (!$this->options['is_premium']): ?>
            <p><strong>Upgrade to Premium for advanced analytics and suggestions!</strong></p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function activate() {
        add_rewrite_rule('^go/([^/]+)/?', 'index.php?sao_redirect=$matches[1]', 'top');
        flush_rewrite_rules();
        add_action('template_redirect', array($this, 'handle_redirect'));
    }
}

SmartAffiliateOptimizer::get_instance();

add_action('wp_ajax_sao_track', 'sao_ajax_track');
function sao_ajax_track() {
    check_ajax_referer('sao_nonce', 'nonce');
    // Track AJAX clicks for premium
    wp_die();
}

// Inline tracker.js equivalent
add_action('wp_head', function() {
    if (!is_admin()) {
        echo '<script>jQuery(document).ready(function($){ $("a[href*=\u0027/go/\u0027]").click(function(){ var id=$(this).attr("href").split("/").pop(); $.post(sao_ajax.ajax_url, {action:"sao_track", nonce:sao_ajax.nonce, id:id}); }); });</script>';
    }
});