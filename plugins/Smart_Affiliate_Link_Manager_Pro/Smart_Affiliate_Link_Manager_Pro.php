/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Plugin URI: https://example.com/smart-affiliate-link-manager
 * Description: Automatically cloak, track, and optimize affiliate links to maximize commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-link-manager
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateLinkManager {
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
        add_action('wp_ajax_salmp_track_click', array($this, 'track_click'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('salmp_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
        load_plugin_textdomain('smart-affiliate-link-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salmp-tracker', plugin_dir_url(__FILE__) . 'salmp-tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('salmp-tracker', 'salmp_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Link Manager', 'Affiliate Links', 'manage_options', 'salmp', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['salmp_save'])) {
            update_option('salmp_enabled', sanitize_text_field($_POST['salmp_enabled']));
            update_option('salmp_cloak_base', esc_url_raw($_POST['salmp_cloak_base']));
            if (current_user_can('manage_options')) {
                update_option('salmp_pro', 'yes');
            }
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $enabled = get_option('salmp_enabled', '1');
        $cloak_base = get_option('salmp_cloak_base', 'go');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Enable Link Cloaking</th>
                        <td><input type="checkbox" name="salmp_enabled" value="1" <?php checked($enabled, '1'); ?> /></td>
                    </tr>
                    <tr>
                        <th>Cloak Base (e.g., go.yoursite.com)</th>
                        <td><input type="text" name="salmp_cloak_base" value="<?php echo esc_attr($cloak_base); ?>" /></td>
                    </tr>
                </table>
                <?php if (!get_option('salmp_pro')): ?>
                <p><strong>Upgrade to Pro for A/B testing and analytics!</strong></p>
                <?php endif; ?>
                <p><input type="submit" name="salmp_save" class="button-primary" value="Save Settings" /></p>
            </form>
        </div>
        <?php
    }

    public function cloak_links($content) {
        if (!get_option('salmp_enabled')) return $content;
        $cloak_base = get_option('salmp_cloak_base', 'go');
        $pattern = '/https?:\/\/(amzn|aff|affiliate|ref|track|go)\.[^\s"\']+/i';
        $content = preg_replace_callback($pattern, array($this, 'replace_link'), $content);
        return $content;
    }

    private function replace_link($matches) {
        $id = uniqid('salmp_');
        $original = $matches;
        update_option('salmp_links_' . $id, $original, false);
        $cloak_base = get_option('salmp_cloak_base', 'go');
        $cloak_url = home_url('/' . $cloak_base . '/' . $id . '/');
        return '<a href="' . esc_url($cloak_url) . '" class="salmp-link" data-id="' . esc_attr($id) . '" data-original="' . esc_attr($original) . '">' . $original . '</a>';
    }

    public function track_click() {
        $id = sanitize_text_field($_POST['id']);
        $original = get_option('salmp_links_' . $id);
        if ($original) {
            // Log click (Pro feature stub)
            if (get_option('salmp_pro')) {
                error_log('SALMP Pro Click: ' . $original);
            }
            wp_redirect($original);
            exit;
        }
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Unlock Pro features: A/B Testing & Analytics. <a href="options-general.php?page=salmp">Upgrade now!</a></p></div>';
    }

    public function activate() {
        update_option('salmp_enabled', '1');
    }

    public function deactivate() {}
}

// Add rewrite rules
add_action('init', function() {
    add_rewrite_rule('^' . get_option('salmp_cloak_base', 'go') . '/([^/]+)/?', 'index.php?salmp_id=$matches[1]', 'top');
});

add_filter('query_vars', function($vars) {
    $vars[] = 'salmp_id';
    return $vars;
});

add_action('template_redirect', function() {
    $id = get_query_var('salmp_id');
    if ($id) {
        $original = get_option('salmp_links_' . $id);
        if ($original) {
            wp_redirect($original, 301);
            exit;
        }
    }
});

SmartAffiliateLinkManager::get_instance();

// Pro stub
function salmp_pro_features() {
    if (get_option('salmp_pro')) {
        // A/B testing, analytics code here
    }
}

?>
<script>
// salmp-tracker.js content embedded
jQuery(document).ready(function($) {
    $('.salmp-link').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post(salmp_ajax.ajaxurl, {action: 'salmp_track_click', id: id}, function() {
            window.location = $(this).attr('href');
        }.bind(this));
    });
});
</script>