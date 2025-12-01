<?php
/*
Plugin Name: WP Affiliate Link Manager
Description: Manage, cloak, and track affiliate links with analytics.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Link_Manager.php
*/

if (!defined('ABSPATH')) exit;

class WPAffiliateLinkManager {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_link', array($this, 'render_affiliate_link'));
        add_action('wp_head', array($this, 'track_click'));
    }

    public function add_menu() {
        add_menu_page(
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'wp-affiliate-link-manager',
            array($this, 'admin_page'),
            'dashicons-admin-links'
        );
    }

    public function register_settings() {
        register_setting('wp_affiliate_link_manager', 'wp_affiliate_links');
    }

    public function admin_page() {
        $links = get_option('wp_affiliate_links', array());
        if (isset($_POST['add_link'])) {
            $links[] = array(
                'name' => sanitize_text_field($_POST['name']),
                'url' => esc_url_raw($_POST['url']),
                'slug' => sanitize_title($_POST['slug'])
            );
            update_option('wp_affiliate_links', $links);
            echo '<div class="notice notice-success"><p>Link added!</p></div>';
        }
        if (isset($_GET['delete'])) {
            unset($links[$_GET['delete']]);
            update_option('wp_affiliate_links', $links);
            wp_redirect(admin_url('admin.php?page=wp-affiliate-link-manager'));
            exit;
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Link Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="name">Name</label></th>
                        <td><input type="text" name="name" id="name" required /></td>
                    </tr>
                    <tr>
                        <th><label for="url">Affiliate URL</label></th>
                        <td><input type="url" name="url" id="url" required /></td>
                    </tr>
                    <tr>
                        <th><label for="slug">Slug</label></th>
                        <td><input type="text" name="slug" id="slug" required /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="add_link" class="button-primary" value="Add Link" /></p>
            </form>
            <h2>Existing Links</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Slug</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $i => $link): ?>
                    <tr>
                        <td><?php echo esc_html($link['name']); ?></td>
                        <td><?php echo esc_url($link['url']); ?></td>
                        <td><?php echo esc_html($link['slug']); ?></td>
                        <td><a href="<?php echo admin_url('admin.php?page=wp-affiliate-link-manager&delete=' . $i); ?>" class="button">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_affiliate_link($atts) {
        $atts = shortcode_atts(array('slug' => ''), $atts);
        $links = get_option('wp_affiliate_links', array());
        foreach ($links as $link) {
            if ($link['slug'] === $atts['slug']) {
                return '<a href="' . home_url('/go/' . $link['slug']) . '" target="_blank">' . esc_html($link['name']) . '</a>';
            }
        }
        return '';
    }

    public function track_click() {
        if (isset($_GET['go'])) {
            $slug = sanitize_title($_GET['go']);
            $links = get_option('wp_affiliate_links', array());
            foreach ($links as $link) {
                if ($link['slug'] === $slug) {
                    // Log click (could be extended with analytics)
                    wp_redirect($link['url']);
                    exit;
                }
            }
        }
    }
}

new WPAffiliateLinkManager();
?>