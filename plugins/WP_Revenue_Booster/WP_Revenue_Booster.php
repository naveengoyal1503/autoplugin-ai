<?php
/*
Plugin Name: WP Revenue Booster
Description: Maximize your WordPress site's revenue by rotating and optimizing affiliate links, ads, and sponsored content.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'output_revenue_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'js/revenue-booster.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wp_revenue_booster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('revenue_booster_nonce')
        ));
    }

    public function output_revenue_content() {
        $options = get_option('wp_revenue_booster_settings');
        if (!$options || empty($options['enabled'])) return;

        $content = $this->get_rotated_content();
        if ($content) {
            echo '<div class="wp-revenue-booster-content">' . $content . '</div>';
        }
    }

    private function get_rotated_content() {
        $options = get_option('wp_revenue_booster_settings');
        $items = array();

        if (!empty($options['affiliate_links'])) {
            foreach ($options['affiliate_links'] as $link) {
                if (!empty($link['url']) && !empty($link['text'])) {
                    $items[] = '<a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow">' . esc_html($link['text']) . '</a>';
                }
            }
        }

        if (!empty($options['ads'])) {
            foreach ($options['ads'] as $ad) {
                if (!empty($ad['code'])) {
                    $items[] = $ad['code'];
                }
            }
        }

        if (!empty($options['sponsored'])) {
            foreach ($options['sponsored'] as $sponsored) {
                if (!empty($sponsored['content'])) {
                    $items[] = $sponsored['content'];
                }
            }
        }

        if (empty($items)) return false;

        $index = array_rand($items);
        return $items[$index];
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
        register_setting('wp_revenue_booster', 'wp_revenue_booster_settings');

        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Booster Settings',
            null,
            'wp-revenue-booster'
        );

        add_settings_field(
            'enabled',
            'Enable Revenue Booster',
            array($this, 'enabled_render'),
            'wp-revenue-booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'affiliate_links',
            'Affiliate Links',
            array($this, 'affiliate_links_render'),
            'wp-revenue-booster',
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'ads',
            'Ad Codes',
            array($this, 'ads_render'),
            'wp-revenue-booster',
            'wp-revenue_booster_section'
        );

        add_settings_field(
            'sponsored',
            'Sponsored Content',
            array($this, 'sponsored_render'),
            'wp-revenue-booster',
            'wp-revenue_booster_section'
        );
    }

    public function enabled_render() {
        $options = get_option('wp_revenue_booster_settings');
        $enabled = isset($options['enabled']) ? $options['enabled'] : 0;
        echo '<input type="checkbox" name="wp_revenue_booster_settings[enabled]" value="1" ' . checked(1, $enabled, false) . ' />';
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_settings');
        $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();
        echo '<div id="affiliate-links-container">';
        foreach ($links as $link) {
            echo '<div><input type="text" name="wp_revenue_booster_settings[affiliate_links][][url]" value="' . esc_attr($link['url']) . '" placeholder="Affiliate URL" style="width: 40%" />
            <input type="text" name="wp_revenue_booster_settings[affiliate_links][][text]" value="' . esc_attr($link['text']) . '" placeholder="Link Text" style="width: 40%" />
            <button type="button" class="remove-link">Remove</button></div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-affiliate-link">Add Link</button>';
        echo '<script>
            jQuery(document).ready(function($) {
                $("#add-affiliate-link").click(function() {
                    $("#affiliate-links-container").append("<div><input type=\"text\" name=\"wp_revenue_booster_settings[affiliate_links][][url]\" placeholder=\"Affiliate URL\" style=\"width: 40%\" /><input type=\"text\" name=\"wp_revenue_booster_settings[affiliate_links][][text]\" placeholder=\"Link Text\" style=\"width: 40%\" /><button type=\"button\" class=\\\"remove-link\\\">Remove</button></div>");
                });
                $(document).on("click", ".remove-link", function() {
                    $(this).parent().remove();
                });
            });
        </script>';
    }

    public function ads_render() {
        $options = get_option('wp_revenue_booster_settings');
        $ads = isset($options['ads']) ? $options['ads'] : array();
        echo '<div id="ads-container">';
        foreach ($ads as $ad) {
            echo '<div><textarea name="wp_revenue_booster_settings[ads][][code]" placeholder="Ad Code" style="width: 90%; height: 100px;">' . esc_textarea($ad['code']) . '</textarea><button type="button" class="remove-ad">Remove</button></div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-ad">Add Ad</button>';
        echo '<script>
            jQuery(document).ready(function($) {
                $("#add-ad").click(function() {
                    $("#ads-container").append("<div><textarea name=\"wp_revenue_booster_settings[ads][][code]\" placeholder=\"Ad Code\" style=\"width: 90%; height: 100px;\"></textarea><button type=\"button\" class=\\\"remove-ad\\\">Remove</button></div>");
                });
                $(document).on("click", ".remove-ad", function() {
                    $(this).parent().remove();
                });
            });
        </script>';
    }

    public function sponsored_render() {
        $options = get_option('wp_revenue_booster_settings');
        $sponsored = isset($options['sponsored']) ? $options['sponsored'] : array();
        echo '<div id="sponsored-container">';
        foreach ($sponsored as $item) {
            echo '<div><textarea name="wp_revenue_booster_settings[sponsored][][content]" placeholder="Sponsored Content" style="width: 90%; height: 100px;">' . esc_textarea($item['content']) . '</textarea><button type="button" class="remove-sponsored">Remove</button></div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-sponsored">Add Sponsored Content</button>';
        echo '<script>
            jQuery(document).ready(function($) {
                $("#add-sponsored").click(function() {
                    $("#sponsored-container").append("<div><textarea name=\"wp_revenue_booster_settings[sponsored][][content]\" placeholder=\"Sponsored Content\" style=\"width: 90%; height: 100px;\"></textarea><button type=\"button\" class=\\\"remove-sponsored\\\">Remove</button></div>");
                });
                $(document).on("click", ".remove-sponsored", function() {
                    $(this).parent().remove();
                });
            });
        </script>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('wp_revenue_booster');
                do_settings_sections('wp-revenue-booster');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

new WP_Revenue_Booster();
?>