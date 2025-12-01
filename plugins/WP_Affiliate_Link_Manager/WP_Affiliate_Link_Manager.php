/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: WP Affiliate Link Manager
 * Plugin URI: https://example.com/wp-affiliate-link-manager
 * Description: Manage, track, and optimize affiliate links on your WordPress site.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WPAffiliateLinkManager {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('aff_link', array($this, 'aff_link_shortcode'));
        add_action('wp_head', array($this, 'track_click'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'wp_affiliate_link_manager',
            array($this, 'plugin_settings_page'),
            'dashicons-admin-links'
        );
    }

    public function settings_init() {
        register_setting('wp_affiliate_link_manager', 'wp_affiliate_link_manager_options');

        add_settings_section(
            'wp_affiliate_link_manager_section',
            'Manage Your Affiliate Links',
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
        echo '<div id="affiliate-links-container">';
        foreach ($links as $index => $link) {
            echo '<div class="affiliate-link-item">
                    <input type="text" name="wp_affiliate_link_manager_options[affiliate_links][' . $index . '][name]" placeholder="Link Name" value="' . esc_attr($link['name']) . '" />
                    <input type="url" name="wp_affiliate_link_manager_options[affiliate_links][' . $index . '][url]" placeholder="Affiliate URL" value="' . esc_attr($link['url']) . '" />
                    <input type="text" name="wp_affiliate_link_manager_options[affiliate_links][' . $index . '][slug]" placeholder="Slug" value="' . esc_attr($link['slug']) . '" />
                    <button type="button" class="remove-link">Remove</button>
                  </div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-link">Add Link</button>';
        echo '<script>
            document.getElementById("add-link").addEventListener("click", function() {
                var container = document.getElementById("affiliate-links-container");
                var index = container.children.length;
                var div = document.createElement("div");
                div.className = "affiliate-link-item";
                div.innerHTML = `<input type="text" name="wp_affiliate_link_manager_options[affiliate_links][${index}][name]" placeholder="Link Name" />
                                <input type="url" name="wp_affiliate_link_manager_options[affiliate_links][${index}][url]" placeholder="Affiliate URL" />
                                <input type="text" name="wp_affiliate_link_manager_options[affiliate_links][${index}][slug]" placeholder="Slug" />
                                <button type="button" class="remove-link">Remove</button>`;
                container.appendChild(div);
            });
            document.addEventListener("click", function(e) {
                if (e.target && e.target.classList.contains("remove-link")) {
                    e.target.parentElement.remove();
                }
            });
        </script>';
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

    public function aff_link_shortcode($atts) {
        $atts = shortcode_atts(array(
            'slug' => '',
        ), $atts, 'aff_link');

        $options = get_option('wp_affiliate_link_manager_options');
        $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();

        foreach ($links as $link) {
            if ($link['slug'] === $atts['slug']) {
                return '<a href="' . esc_url(home_url('/go/' . $link['slug'])) . '" target="_blank">' . esc_html($link['name']) . '</a>';
            }
        }
        return '';
    }

    public function track_click() {
        if (isset($_GET['go'])) {
            $slug = sanitize_text_field($_GET['go']);
            $options = get_option('wp_affiliate_link_manager_options');
            $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();
            foreach ($links as $link) {
                if ($link['slug'] === $slug) {
                    // Log click (could be extended to store in database)
                    wp_redirect($link['url']);
                    exit;
                }
            }
        }
    }
}

new WPAffiliateLinkManager();
