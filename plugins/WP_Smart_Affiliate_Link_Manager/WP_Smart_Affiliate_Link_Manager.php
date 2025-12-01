/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: WP Smart Affiliate Link Manager
 * Plugin URI: https://example.com/wp-smart-affiliate-link-manager
 * Description: Automatically convert keywords into affiliate links, track clicks, and optimize link placement for higher conversions.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPSmartAffiliateLinkManager {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'convert_keywords_to_links'));
        add_action('wp_ajax_track_affiliate_click', array($this, 'track_affiliate_click'));
        add_action('wp_ajax_nopriv_track_affiliate_click', array($this, 'track_affiliate_click'));
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate Link Manager',
            'Affiliate Links',
            'manage_options',
            'wp_smart_affiliate_link_manager',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('wp_smart_affiliate_link_manager', 'wp_smart_affiliate_link_manager_options');

        add_settings_section(
            'wp_smart_affiliate_link_manager_section',
            'Affiliate Link Settings',
            null,
            'wp_smart_affiliate_link_manager'
        );

        add_settings_field(
            'keywords',
            'Keywords & Links',
            array($this, 'keywords_render'),
            'wp_smart_affiliate_link_manager',
            'wp_smart_affiliate_link_manager_section'
        );
    }

    public function keywords_render() {
        $options = get_option('wp_smart_affiliate_link_manager_options');
        $keywords = isset($options['keywords']) ? $options['keywords'] : '';
        echo '<textarea name="wp_smart_affiliate_link_manager_options[keywords]" rows="10" cols="50">' . esc_textarea($keywords) . '</textarea><br>';
        echo '<small>Format: keyword|affiliate_url (one per line)</small>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wp_smart_affiliate_link_manager');
                do_settings_sections('wp_smart_affiliate_link_manager');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function convert_keywords_to_links($content) {
        $options = get_option('wp_smart_affiliate_link_manager_options');
        $keywords = isset($options['keywords']) ? $options['keywords'] : '';
        $lines = explode('\n', $keywords);
        $replacements = array();

        foreach ($lines as $line) {
            $parts = explode('|', trim($line));
            if (count($parts) === 2) {
                $keyword = trim($parts);
                $url = esc_url(trim($parts[1]));
                $replacements[$keyword] = '<a href="' . $url . '" class="wp-smart-affiliate-link" data-keyword="' . $keyword . '" onclick="trackAffiliateClick(this); return false;">' . $keyword . '</a>';
            }
        }

        foreach ($replacements as $keyword => $link) {
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link, $content);
        }

        $content .= '<script>
            function trackAffiliateClick(element) {
                var keyword = element.getAttribute("data-keyword");
                jQuery.post(ajaxurl, {
                    action: "track_affiliate_click",
                    keyword: keyword
                });
                window.open(element.href, "_blank");
            }
        </script>';

        return $content;
    }

    public function track_affiliate_click() {
        if (isset($_POST['keyword'])) {
            $keyword = sanitize_text_field($_POST['keyword']);
            $transient_key = 'affiliate_clicks_' . $keyword;
            $clicks = get_transient($transient_key);
            if ($clicks === false) {
                $clicks = 0;
            }
            $clicks++;
            set_transient($transient_key, $clicks, YEAR_IN_SECONDS);
        }
        wp_die();
    }
}

new WPSmartAffiliateLinkManager();
?>