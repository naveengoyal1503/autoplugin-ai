<?php
/*
Plugin Name: WP Affiliate Link Manager
Description: Manage, track, and optimize affiliate links with analytics and cloaking.
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
            'Affiliate Link Manager',
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
                'slug' => sanitize_title($_POST['slug']),
                'clicks' => 0
            );
            update_option('wp_affiliate_links', $links);
        }
        if (isset($_POST['delete_link'])) {
            unset($links[$_POST['index']]);
            update_option('wp_affiliate_links', array_values($links));
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Link Manager</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label>Name</label></th>
                        <td><input type="text" name="name" required /></td>
                    </tr>
                    <tr>
                        <th><label>URL</label></th>
                        <td><input type="url" name="url" required /></td>
                    </tr>
                    <tr>
                        <th><label>Slug</label></th>
                        <td><input type="text" name="slug" required /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="add_link" class="button-primary" value="Add Link" /></p>
            </form>
            <h2>Existing Links</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Slug</th>
                        <th>Clicks</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $i => $link): ?>
                    <tr>
                        <td><?php echo esc_html($link['name']); ?></td>
                        <td><?php echo esc_url($link['url']); ?></td>
                        <td><?php echo esc_html($link['slug']); ?></td>
                        <td><?php echo intval($link['clicks']); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="index" value="<?php echo $i; ?>" />
                                <input type="submit" name="delete_link" class="button" value="Delete" />
                            </form>
                        </td>
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
            foreach ($links as $i => $link) {
                if ($link['slug'] === $slug) {
                    $links[$i]['clicks']++;
                    update_option('wp_affiliate_links', $links);
                    wp_redirect($link['url']);
                    exit;
                }
            }
        }
    }
}

new WPAffiliateLinkManager();
