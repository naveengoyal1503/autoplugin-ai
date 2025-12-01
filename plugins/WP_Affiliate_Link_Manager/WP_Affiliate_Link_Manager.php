/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: WP Affiliate Link Manager
 * Plugin URI: https://example.com/wp-affiliate-link-manager
 * Description: Manage, track, and optimize your affiliate links for higher revenue.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WPAffiliateLinkManager {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_action('wp_footer', array($this, 'track_click'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Affiliate Link Manager',
            'Affiliate Links',
            'manage_options',
            'wp_affiliate_link_manager',
            array($this, 'plugin_settings_page')
        );
    }

    public function settings_init() {
        register_setting('wp_affiliate_link_manager', 'wp_affiliate_link_manager_options');

        add_settings_section(
            'wp_affiliate_link_manager_section',
            'Affiliate Links',
            null,
            'wp_affiliate_link_manager'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links',
            array($this, 'affiliate_links_render'),
            'wp_affiliate_link_manager',
            'wp_affiliate_link_manager_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_affiliate_link_manager_options');
        $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();
        echo '<table class="form-table">
            <tr>
                <th>Link Name</th>
                <th>Affiliate URL</th>
                <th>Shortcode</th>
            </tr>';
        foreach ($links as $key => $link) {
            echo '<tr>
                <td><input type="text" name="wp_affiliate_link_manager_options[affiliate_links][' . $key . '][name]" value="' . esc_attr($link['name']) . '" /></td>
                <td><input type="text" name="wp_affiliate_link_manager_options[affiliate_links][' . $key . '][url]" value="' . esc_attr($link['url']) . '" /></td>
                <td>[affiliate_link id="' . $key . '"]</td>
            </tr>';
        }
        echo '<tr>
                <td><input type="text" name="wp_affiliate_link_manager_options[affiliate_links][][name]" placeholder="Link Name" /></td>
                <td><input type="text" name="wp_affiliate_link_manager_options[affiliate_links][][url]" placeholder="Affiliate URL" /></td>
                <td>New Link</td>
            </tr>';
        echo '</table>';
    }

    public function plugin_settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Link Manager</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('wp_affiliate_link_manager');
                do_settings_sections('wp_affiliate_link_manager');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts, 'affiliate_link');

        $options = get_option('wp_affiliate_link_manager_options');
        $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();

        if (isset($links[$atts['id']])) {
            $link = $links[$atts['id']];
            return '<a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow">' . esc_html($link['name']) . '</a>';
        }
        return '';
    }

    public function track_click() {
        if (isset($_GET['affiliate_id'])) {
            $options = get_option('wp_affiliate_link_manager_options');
            $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();
            $id = sanitize_text_field($_GET['affiliate_id']);
            if (isset($links[$id])) {
                $links[$id]['clicks'] = isset($links[$id]['clicks']) ? $links[$id]['clicks'] + 1 : 1;
                $options['affiliate_links'] = $links;
                update_option('wp_affiliate_link_manager_options', $options);
            }
        }
    }
}

new WPAffiliateLinkManager();
?>