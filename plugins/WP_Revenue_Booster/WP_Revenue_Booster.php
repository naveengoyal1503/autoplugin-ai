/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Maximize your WordPress site's revenue by rotating and optimizing affiliate links, ads, and sponsored content.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'output_revenue_booster')); // Output logic in footer
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'revenue-booster.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_revenue_booster_nonce')
        ));
    }

    public function output_revenue_booster() {
        // Example: Rotate affiliate links based on simple logic
        $links = get_option('wp_revenue_booster_links', array());
        if (!empty($links)) {
            $random_link = $links[array_rand($links)];
            echo '<div class="wp-revenue-booster" style="display:none;">
                    <a href="' . esc_url($random_link['url']) . '" target="_blank" rel="nofollow">' . esc_html($random_link['label']) . '</a>
                  </div>';
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('wpRevenueBooster', 'wp_revenue_booster_links');
        add_settings_section(
            'wpRevenueBooster_section',
            'Affiliate Links & Ads',
            null,
            'wpRevenueBooster'
        );
        add_settings_field(
            'wp_revenue_booster_links',
            'Links',
            array($this, 'links_render'),
            'wpRevenueBooster',
            'wpRevenueBooster_section'
        );
    }

    public function links_render() {
        $links = get_option('wp_revenue_booster_links', array());
        echo '<div id="wp-revenue-booster-links">';
        foreach ($links as $link) {
            echo '<p><input type="text" name="wp_revenue_booster_links[url][]" value="' . esc_attr($link['url']) . '" placeholder="URL" />
                    <input type="text" name="wp_revenue_booster_links[label][]" value="' . esc_attr($link['label']) . '" placeholder="Label" /></p>';
        }
        echo '</div>';
        echo '<button type="button" onclick="addLinkField()">Add Link</button>';
        echo '<script>
            function addLinkField() {
                var container = document.getElementById("wp-revenue-booster-links");
                var p = document.createElement("p");
                p.innerHTML = "<input type=\"text\" name=\"wp_revenue_booster_links[url][]\" placeholder=\"URL\" /> <input type=\"text\" name=\"wp_revenue_booster_links[label][]\" placeholder=\"Label\" />";
                container.appendChild(p);
            }
        </script>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wpRevenueBooster');
                do_settings_sections('wpRevenueBooster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
?>