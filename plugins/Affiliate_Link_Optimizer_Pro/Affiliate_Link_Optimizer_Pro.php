/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Link Optimizer Pro
 * Plugin URI: https://example.com/affiliate-optimizer
 * Description: Automatically tracks, optimizes, and cloaks affiliate links with performance analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AffiliateLinkOptimizer {
    private static $instance = null;
    public $settings;

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
        add_action('wp_ajax_alo_track_click', array($this, 'track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->settings = get_option('alo_settings', array('cloak' => 'yes', 'tracking' => 'yes'));
    }

    public function activate() {
        add_option('alo_settings', array('cloak' => 'yes', 'tracking' => 'yes'));
    }

    public function admin_menu() {
        add_options_page('Affiliate Link Optimizer', 'Affiliate Optimizer', 'manage_options', 'alo-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('alo_settings', $_POST['alo_settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = $this->settings;
        ?>
        <div class="wrap">
            <h1>Affiliate Link Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Cloak Links</th>
                        <td><input type="checkbox" name="alo_settings[cloak]" <?php checked($settings['cloak'], 'yes'); ?> value="yes" /></td>
                    </tr>
                    <tr>
                        <th>Enable Tracking</th>
                        <td><input type="checkbox" name="alo_settings[tracking]" <?php checked($settings['tracking'], 'yes'); ?> value="yes" /></td>
                    </tr>
                    <tr>
                        <th>Track Keywords</th>
                        <td><input type="text" name="alo_settings[keywords]" value="<?php echo esc_attr($settings['keywords'] ?? ''); ?>" placeholder="amazon,clickbank" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Analytics</h2>
            <div id="alo-analytics">Loading...</div>
        </div>
        <script>
        jQuery.post(ajaxurl, {action: 'alo_get_analytics'}, function(data) {
            jQuery('#alo-analytics').html(data);
        });
        </script>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function cloak_links($content) {
        if ($this->settings['cloak'] !== 'yes') return $content;
        $keywords = !empty($this->settings['keywords']) ? explode(',', $this->settings['keywords']) : array('amazon', 'affiliate');
        foreach ($keywords as $keyword) {
            $keyword = trim($keyword);
            $content = preg_replace_callback(
                '|https?://[^\s<>"]*(?:' . preg_quote($keyword, '/') . ')[^\s<>"]*|i',
                function($match) {
                    $url = $match;
                    $id = uniqid('alo_');
                    return '<a href="#" class="alo-link" data-url="' . esc_url($url) . '" data-id="' . $id . '">' . $url . '</a>';
                },
                $content
            );
        }
        return $content . '<script>jQuery(".alo-link").click(function(e){e.preventDefault();var url=jQuery(this).data("url");jQuery.post("' . admin_url('admin-ajax.php') . '",{action:"alo_track_click",url:url},function(){window.location=url;});});</script>';
    }

    public function track_click() {
        if ($this->settings['tracking'] !== 'yes') wp_die();
        $url = sanitize_url($_POST['url']);
        $clicks = get_option('alo_clicks', array()) + array($url => (get_option('alo_clicks'][$url] ?? 0) + 1);
        update_option('alo_clicks', $clicks);
        wp_die('OK');
    }
}

add_action('wp_ajax_alo_get_analytics', function() {
    $clicks = get_option('alo_clicks', array());
    arsort($clicks);
    echo '<ul>';
    foreach (array_slice($clicks, 0, 10, true) as $url => $count) {
        echo '<li>' . esc_url($url) . ': ' . $count . ' clicks</li>';
    }
    echo '</ul>';
    wp_die();
});

AffiliateLinkOptimizer::get_instance();