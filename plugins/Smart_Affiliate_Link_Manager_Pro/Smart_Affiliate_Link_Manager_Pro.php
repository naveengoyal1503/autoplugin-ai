/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automate affiliate link management, cloaking, tracking, and performance analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_head', array($this, 'inject_tracking'));
        add_filter('the_content', array($this, 'cloak_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (get_option('sam_pro_version') !== '1.0') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sam-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sam-tracker', 'sam_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sam_nonce')));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Manager', 'Affiliate Manager', 'manage_options', 'smart-affiliate', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sam_save'])) {
            update_option('sam_affiliate_links', sanitize_text_field($_POST['links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('sam_affiliate_links', 'https://example.com/ref=123|Affiliate Link 1;https://example.com/ref=456|Affiliate Link 2');
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links (format: url|keyword;)</th>
                        <td><textarea name="links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="sam_save" class="button-primary" value="Save Settings"></p>
            </form>
            <h2>Stats (Pro Feature)</h2>
            <p><em>Upgrade to Pro for click tracking and analytics.</em></p>
        </div>
        <?php
    }

    public function cloak_links($content) {
        $links = get_option('sam_affiliate_links', '');
        if (empty($links)) return $content;
        $link_pairs = explode(';', $links);
        foreach ($link_pairs as $pair) {
            $parts = explode('|', trim($pair));
            if (count($parts) === 2) {
                $keyword = $parts;
                $url = $parts[1];
                $content = str_replace($keyword, '<a href="' . esc_url($url) . '" class="sam-cloaked" data-sam-original="' . esc_url($url) . '">' . esc_html($keyword) . '</a>', $content);
            }
        }
        return $content;
    }

    public function inject_tracking() {
        echo '<script>console.log("Smart Affiliate Manager loaded");</script>';
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate Manager Pro</strong> for advanced tracking and A/B testing! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }

    public function activate() {
        add_option('sam_version', '1.0.0');
    }

    public function deactivate() {}
}

// Tracker JS inline for single file
add_action('wp_footer', function() {
    if (is_admin()) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sam-cloaked').on('click', function(e) {
            var original = $(this).data('sam-original');
            $.post(sam_ajax.ajax_url, {
                action: 'sam_track_click',
                nonce: sam_ajax.nonce,
                url: original
            });
            window.location.href = original;
        });
    });
    </script>
    <?php
});

add_action('wp_ajax_sam_track_click', function() {
    // Pro feature stub
    wp_die('Tracked!');
});

SmartAffiliateManager::get_instance();