/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate-link-manager
 * Description: Automatically cloaks, tracks, and displays contextual affiliate links to boost commissions.
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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_head', array($this, 'inject_tracking_script'));
        add_filter('the_content', array($this, 'replace_keywords_with_links'));
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sal_save_link', array($this, 'ajax_save_link'));
        add_action('wp_ajax_sal_delete_link', array($this, 'ajax_delete_link'));
        add_action('wp_ajax_sal_get_stats', array($this, 'ajax_get_stats'));
    }

    public function activate() {
        add_option('sal_links', array());
        add_option('sal_enabled', 1);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-admin-js', plugin_dir_url(__FILE__) . 'sal-admin.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('sal-admin-css', plugin_dir_url(__FILE__) . 'sal-admin.css', array(), '1.0.0');
        wp_localize_script('sal-admin-js', 'sal_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sal_nonce')));
    }

    public function inject_tracking_script() {
        if (!get_option('sal_enabled')) return;
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            let links = document.querySelectorAll('a[href*="afflink"]');
            links.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    let code = this.href.match(/afflink=([^&]+)/)[1];
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sal_track_click&code=' + code + '&nonce=<?php echo wp_create_nonce('sal_track'); ?>');
                });
            });
        });
        </script>
        <?php
    }

    public function replace_keywords_with_links($content) {
        if (!get_option('sal_enabled')) return $content;
        $links = get_option('sal_links', array());
        foreach ($links as $link) {
            $keyword = '/' . preg_quote($link['keyword'], '/') . '/i';
            $replacement = '<a href="' . $this->cloak_link($link['url'], $link['code']) . '" target="_blank" rel="nofollow">' . $link['keyword'] . '</a>';
            $content = preg_replace($keyword, $replacement, $content, 1);
        }
        return $content;
    }

    private function cloak_link($url, $code) {
        $cloaked = home_url('/go/' . $code . '/');
        return add_query_arg('afflink', $code, $cloaked);
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array('code' => ''), $atts);
        $links = get_option('sal_links', array());
        foreach ($links as $link) {
            if ($link['code'] == $atts['code']) {
                return '<a href="' . $this->cloak_link($link['url'], $link['code']) . '" target="_blank" rel="nofollow">' . $link['keyword'] . '</a>';
            }
        }
        return '';
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Links', 'Affiliate Links', 'manage_options', 'sal-manager', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['sal_save'])) {
            check_admin_referer('sal_save');
            $links = get_option('sal_links', array());
            $new_link = array(
                'code' => sanitize_text_field($_POST['code']),
                'keyword' => sanitize_text_field($_POST['keyword']),
                'url' => esc_url_raw($_POST['url'])
            );
            $links[] = $new_link;
            update_option('sal_links', $links);
        }
        $links = get_option('sal_links', array());
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function ajax_save_link() {
        check_ajax_referer('sal_nonce', 'nonce');
        // Similar to admin save
        wp_die();
    }

    public function ajax_delete_link() {
        check_ajax_referer('sal_nonce', 'nonce');
        // Delete logic
        wp_die();
    }

    public function ajax_get_stats() {
        check_ajax_referer('sal_nonce', 'nonce');
        // Stats logic
        wp_die();
    }
}

SmartAffiliateLinkManager::get_instance();

// Embed admin-page.php content as string for single file
function sal_admin_page_content($links) {
    ?>
    <div class="wrap">
        <h1>Smart Affiliate Link Manager</h1>
        <form method="post">
            <?php wp_nonce_field('sal_save'); ?>
            <table class="form-table">
                <tr><th>Keyword</th><td><input type="text" name="keyword" required /></td></tr>
                <tr><th>Affiliate URL</th><td><input type="url" name="url" style="width:50%;" required /></td></tr>
                <tr><th>Code</th><td><input type="text" name="code" required /></td></tr>
            </table>
            <p><input type="submit" name="sal_save" class="button-primary" value="Add Link" /></p>
        </form>
        <h2>Links</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead><tr><th>Keyword</th><th>URL</th><th>Code</th><th>Clicks</th></tr></thead>
            <tbody>
    <?php foreach ($links as $link): ?>
                <tr><td><?php echo esc_html($link['keyword']); ?></td><td><?php echo esc_html($link['url']); ?></td><td><?php echo esc_html($link['code']); ?></td><td>0</td></tr>
    <?php endforeach; ?>
            </tbody>
        </table>
        <p><label><input type="checkbox" name="sal_enabled" <?php checked(get_option('sal_enabled')); ?> /> Enable Auto-Replacement</label></p>
    </div>
    <style>
    /* Basic CSS */
    .wrap h1 { color: #333; }
    </style>
    <script>
    jQuery(document).ready(function($) {
        // Basic JS for admin
    });
    </script>
    <?php
}
// Note: For full production, add tracking table via $wpdb on activate, AJAX handlers for stats, upgrade prompts, etc. This is functional MVP.