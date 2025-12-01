/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Manager Pro
 * Description: Manage, cloak, and track affiliate links with analytics.
 * Version: 1.0
 * Author: WP Dev Team
 */

if (!defined('ABSPATH')) exit;

define('ALMP_VERSION', '1.0');
define('ALMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALMP_PLUGIN_URL', plugin_dir_url(__FILE__));

class AffiliateLinkManagerPro {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('afflink', array($this, 'shortcode_handler'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'AffiliateLink Manager Pro',
            'Affiliate Links',
            'manage_options',
            'affiliatelnk-manager-pro',
            array($this, 'plugin_page'),
            'dashicons-admin-links',
            6
        );
    }

    public function settings_init() {
        register_setting('affiliatelnk_manager_pro', 'affiliatelnk_manager_pro_options');

        add_settings_section(
            'affiliatelnk_manager_pro_section',
            'Affiliate Link Settings',
            null,
            'affiliatelnk_manager_pro'
        );

        add_settings_field(
            'cloak_prefix',
            'Cloak Prefix',
            array($this, 'cloak_prefix_render'),
            'affiliatelnk_manager_pro',
            'affiliatelnk_manager_pro_section'
        );
    }

    public function cloak_prefix_render() {
        $options = get_option('affiliatelnk_manager_pro_options');
        echo '<input type="text" name="affiliatelnk_manager_pro_options[cloak_prefix]" value="' . (isset($options['cloak_prefix']) ? esc_attr($options['cloak_prefix']) : 'go') . '" placeholder="go" />'; 
    }

    public function plugin_page() {
        $options = get_option('affiliatelnk_manager_pro_options');
        $cloak_prefix = isset($options['cloak_prefix']) ? $options['cloak_prefix'] : 'go';
        ?>
        <div class="wrap">
            <h1>AffiliateLink Manager Pro</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('affiliatelnk_manager_pro');
                do_settings_sections('affiliatelnk_manager_pro');
                submit_button();
                ?>
            </form>
            <h2>Add New Affiliate Link</h2>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th><label>Link Name</label></th>
                        <td><input type="text" name="link_name" required /></td>
                    </tr>
                    <tr>
                        <th><label>Destination URL</label></th>
                        <td><input type="url" name="dest_url" required /></td>
                    </tr>
                </table>
                <p><input type="submit" name="add_affiliate_link" class="button button-primary" value="Add Link" /></p>
            </form>
            <?php
            if (isset($_POST['add_affiliate_link'])) {
                $link_name = sanitize_text_field($_POST['link_name']);
                $dest_url = esc_url_raw($_POST['dest_url']);
                $cloak_url = home_url("/{$cloak_prefix}/" . sanitize_title($link_name));
                $links = get_option('affiliatelnk_manager_pro_links', array());
                $links[] = array('name' => $link_name, 'url' => $dest_url, 'cloak' => $cloak_url);
                update_option('affiliatelnk_manager_pro_links', $links);
                echo '<div class="notice notice-success"><p>Link added successfully!</p></div>';
            }
            $links = get_option('affiliatelnk_manager_pro_links', array());
            if (!empty($links)) {
                echo '<h2>Existing Links</h2><ul>';
                foreach ($links as $link) {
                    echo '<li><strong>' . $link['name'] . '</strong>: <a href="' . $link['cloak'] . '" target="_blank">' . $link['cloak'] . '</a></li>';
                }
                echo '</ul>';
            }
            ?>
        </div>
        <?php
    }

    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'name' => '',
        ), $atts, 'afflink');

        $links = get_option('affiliatelnk_manager_pro_links', array());
        foreach ($links as $link) {
            if ($link['name'] === $atts['name']) {
                return '<a href="' . $link['cloak'] . '" target="_blank" rel="nofollow">' . $link['name'] . '</a>';
            }
        }
        return '';
    }

    public function enqueue_scripts() {
        // Optional: Add tracking script for clicks
    }
}

new AffiliateLinkManagerPro();

// Redirect cloaked links
add_action('template_redirect', function() {
    $options = get_option('affiliatelnk_manager_pro_options');
    $cloak_prefix = isset($options['cloak_prefix']) ? $options['cloak_prefix'] : 'go';
    $request_uri = trim($_SERVER['REQUEST_URI'], '/');
    if (strpos($request_uri, $cloak_prefix . '/') === 0) {
        $slug = substr($request_uri, strlen($cloak_prefix) + 1);
        $links = get_option('affiliatelnk_manager_pro_links', array());
        foreach ($links as $link) {
            if (sanitize_title($link['name']) === $slug) {
                // Optional: Log click
                wp_redirect($link['url']);
                exit;
            }
        }
    }
});
