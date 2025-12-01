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
        add_action('wp_footer', array($this, 'inject_revenue_elements'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'revenue-booster.js', array('jquery'), '1.0', true);
        wp_localize_script('wp-revenue-booster', 'wp_revenue_booster', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('revenue_booster_nonce')
        ));
    }

    public function inject_revenue_elements() {
        $options = get_option('wp_revenue_booster_options');
        if (!$options || empty($options['affiliate_links']) || empty($options['ads']) || empty($options['sponsored_content'])) return;

        $affiliate_links = $options['affiliate_links'];
        $ads = $options['ads'];
        $sponsored_content = $options['sponsored_content'];

        // Rotate affiliate links
        $rotated_affiliate = $affiliate_links[array_rand($affiliate_links)];
        echo '<div class="wp-revenue-affiliate" data-id="' . esc_attr($rotated_affiliate['id']) . '">
            <a href="' . esc_url($rotated_affiliate['url']) . '" target="_blank">' . esc_html($rotated_affiliate['text']) . '</a>
        </div>';

        // Rotate ads
        $rotated_ad = $ads[array_rand($ads)];
        echo '<div class="wp-revenue-ad" data-id="' . esc_attr($rotated_ad['id']) . '">
            ' . wp_kses_post($rotated_ad['code']) . '
        </div>';

        // Rotate sponsored content
        $rotated_sponsored = $sponsored_content[array_rand($sponsored_content)];
        echo '<div class="wp-revenue-sponsored" data-id="' . esc_attr($rotated_sponsored['id']) . '">
            ' . wp_kses_post($rotated_sponsored['content']) . '
        </div>';

        // Track impressions
        echo '<script>jQuery(document).ready(function(){jQuery.post(wp_revenue_booster.ajax_url, {action: "wp_revenue_booster_track_impression", id: "' . esc_attr($rotated_affiliate['id']) . '", type: "affiliate", nonce: wp_revenue_booster.nonce});});</script>';
    }

    public function add_admin_menu() {
        add_options_page('WP Revenue Booster', 'Revenue Booster', 'manage_options', 'wp-revenue-booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('wp_revenue_booster', 'wp_revenue_booster_options');

        add_settings_section(
            'wp_revenue_booster_section',
            'Revenue Booster Settings',
            null,
            'wp-revenue-booster'
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
            'wp_revenue_booster_section'
        );

        add_settings_field(
            'sponsored_content',
            'Sponsored Content',
            array($this, 'sponsored_content_render'),
            'wp-revenue-booster',
            'wp_revenue_booster_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('wp_revenue_booster_options');
        $links = isset($options['affiliate_links']) ? $options['affiliate_links'] : array();
        echo '<div id="affiliate-links-container">';
        foreach ($links as $link) {
            echo '<div><input type="text" name="wp_revenue_booster_options[affiliate_links][][text]" value="' . esc_attr($link['text']) . '" placeholder="Link Text" />
                  <input type="url" name="wp_revenue_booster_options[affiliate_links][][url]" value="' . esc_url($link['url']) . '" placeholder="Affiliate URL" />
                  <input type="hidden" name="wp_revenue_booster_options[affiliate_links][][id]" value="' . esc_attr($link['id']) . '" /></div>';
        }
        echo '</div>';
        echo '<button type="button" onclick="addAffiliateLink()">Add Link</button>';
        echo '<script>function addAffiliateLink(){var container=document.getElementById("affiliate-links-container");var div=document.createElement("div");div.innerHTML="<input type=\"text\" name=\"wp_revenue_booster_options[affiliate_links][][text]\" placeholder=\"Link Text\" /> <input type=\"url\" name=\"wp_revenue_booster_options[affiliate_links][][url]\" placeholder=\"Affiliate URL\" /> <input type=\"hidden\" name=\"wp_revenue_booster_options[affiliate_links][][id]\" value=\""+Date.now()+"\" />";container.appendChild(div);}</script>';
    }

    public function ads_render() {
        $options = get_option('wp_revenue_booster_options');
        $ads = isset($options['ads']) ? $options['ads'] : array();
        echo '<div id="ads-container">';
        foreach ($ads as $ad) {
            echo '<div><textarea name="wp_revenue_booster_options[ads][][code]" placeholder="Ad Code">' . esc_textarea($ad['code']) . '</textarea>
                  <input type="hidden" name="wp_revenue_booster_options[ads][][id]" value="' . esc_attr($ad['id']) . '" /></div>';
        }
        echo '</div>';
        echo '<button type="button" onclick="addAd()">Add Ad</button>';
        echo '<script>function addAd(){var container=document.getElementById("ads-container");var div=document.createElement("div");div.innerHTML="<textarea name=\"wp_revenue_booster_options[ads][][code]\" placeholder=\"Ad Code\"></textarea> <input type=\"hidden\" name=\"wp_revenue_booster_options[ads][][id]\" value=\""+Date.now()+"\" />";container.appendChild(div);}</script>';
    }

    public function sponsored_content_render() {
        $options = get_option('wp_revenue_booster_options');
        $content = isset($options['sponsored_content']) ? $options['sponsored_content'] : array();
        echo '<div id="sponsored-content-container">';
        foreach ($content as $item) {
            echo '<div><textarea name="wp_revenue_booster_options[sponsored_content][][content]" placeholder="Sponsored Content">' . esc_textarea($item['content']) . '</textarea>
                  <input type="hidden" name="wp_revenue_booster_options[sponsored_content][][id]" value="' . esc_attr($item['id']) . '" /></div>';
        }
        echo '</div>';
        echo '<button type="button" onclick="addSponsoredContent()">Add Content</button>';
        echo '<script>function addSponsoredContent(){var container=document.getElementById("sponsored-content-container");var div=document.createElement("div");div.innerHTML="<textarea name=\"wp_revenue_booster_options[sponsored_content][][content]\" placeholder=\"Sponsored Content\"></textarea> <input type=\"hidden\" name=\"wp_revenue_booster_options[sponsored_content][][id]\" value=\""+Date.now()+"\" />";container.appendChild(div);}</script>';
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

// AJAX handler for tracking impressions
add_action('wp_ajax_wp_revenue_booster_track_impression', 'wp_revenue_booster_track_impression');
add_action('wp_ajax_nopriv_wp_revenue_booster_track_impression', 'wp_revenue_booster_track_impression');
function wp_revenue_booster_track_impression() {
    if (!wp_verify_nonce($_POST['nonce'], 'revenue_booster_nonce')) {
        wp_die('Security check failed');
    }
    $id = intval($_POST['id']);
    $type = sanitize_text_field($_POST['type']);
    // Here you can log impressions or update stats in the database
    // For simplicity, just send a success response
    wp_die('1');
}

// JavaScript for the plugin
file_put_contents(plugin_dir_path(__FILE__) . 'revenue-booster.js', "
jQuery(document).ready(function(){
    // Optional: Add more client-side logic here
});
");
?>