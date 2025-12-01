<?php
/*
Plugin Name: GeoAffiliate Smart Links
Description: Automatically inserts and cloaks affiliate links based on visitor geolocation with scheduling features.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=GeoAffiliate_Smart_Links.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class GeoAffiliateSmartLinks {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'start_session'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('geoaffiliate_link', array($this, 'shortcode_geoaffiliate_link'));
        add_filter('the_content', array($this, 'auto_insert_links'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function start_session() {
        if (!session_id()) {
            session_start();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('geoaffiliate-js', plugin_dir_url(__FILE__) . 'geoaffiliate.js', array('jquery'), '1.0', true);
    }

    public function add_admin_menu() {
        add_options_page('GeoAffiliate Links', 'GeoAffiliate Links', 'manage_options', 'geoaffiliate_links', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('geoaffiliate_group', 'geoaffiliate_options');

        add_settings_section(
            'geoaffiliate_section',
            __('Affiliate Links Settings', 'geoaffiliate'),
            array($this, 'settings_section_callback'),
            'geoaffiliate_group'
        );

        add_settings_field(
            'geoaffiliate_links',
            __('Affiliate Links JSON', 'geoaffiliate'),
            array($this, 'affiliate_links_render'),
            'geoaffiliate_group',
            'geoaffiliate_section'
        );
    }

    public function affiliate_links_render() {
        $options = get_option('geoaffiliate_options');
        ?>
        <textarea cols='60' rows='10' name='geoaffiliate_options[affiliate_links]'><?php echo isset($options['affiliate_links']) ? esc_textarea($options['affiliate_links']) : '[{"country":"US","url":"https://affiliatesite.com/us-product","text":"Buy US version"},{"country":"CA","url":"https://affiliatesite.com/ca-product","text":"Buy Canada version"}]'; ?></textarea>
        <p><?php _e('Enter affiliate links as JSON array with country codes, URLs, and display texts. Example: [{"country":"US","url":"https://example.com/us","text":"Buy US Version"}]', 'geoaffiliate'); ?></p>
        <?php
    }

    public function settings_section_callback() {
        echo __('Configure your geo-targeted affiliate links as a JSON array.', 'geoaffiliate');
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>GeoAffiliate Smart Links</h2>
            <?php
            settings_fields('geoaffiliate_group');
            do_settings_sections('geoaffiliate_group');
            submit_button();
            ?>
        </form>
        <?php
    }

    private function get_user_country() {
        if (isset($_SESSION['geoaffiliate_country'])) {
            return $_SESSION['geoaffiliate_country'];
        }
        // Use a free IP geolocation API
        $ip = $_SERVER['REMOTE_ADDR'];
        $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
        if (is_wp_error($response)) {
            return 'US'; // default fallback
        }
        $country = trim(wp_remote_retrieve_body($response));
        $_SESSION['geoaffiliate_country'] = $country;
        return $country;
    }

    public function get_affiliate_link_by_country($country) {
        $options = get_option('geoaffiliate_options');
        $links_json = isset($options['affiliate_links']) ? $options['affiliate_links'] : '[]';
        $links = json_decode($links_json, true);
        if (!$links) return false;

        foreach ($links as $link) {
            if (strtoupper($link['country']) === strtoupper($country)) {
                return $link;
            }
        }
        return false;
    }

    public function shortcode_geoaffiliate_link($atts) {
        $atts = shortcode_atts(array('text' => 'Buy Now', 'default_url' => '#'), $atts, 'geoaffiliate_link');
        $country = $this->get_user_country();
        $link = $this->get_affiliate_link_by_country($country);
        if ($link) {
            $url = esc_url($link['url']);
            $text = esc_html($link['text']);
        } else {
            $url = esc_url($atts['default_url']);
            $text = esc_html($atts['text']);
        }

        // Cloak link with redirect for tracking
        $redirect_url = add_query_arg(array('geoaffiliate_redirect' => base64_encode($url)), home_url('/'));

        return '<a href="' . esc_url($redirect_url) . '" target="_blank" rel="nofollow noopener">' . $text . '</a>';
    }

    public function auto_insert_links($content) {
        if (is_singular() && in_the_loop() && is_main_query()) {
            // Inject first affiliate link after first paragraph
            $country = $this->get_user_country();
            $link = $this->get_affiliate_link_by_country($country);
            if (!$link) return $content;

            $url = esc_url($link['url']);
            $text = esc_html($link['text']);
            $redirect_url = add_query_arg(array('geoaffiliate_redirect' => base64_encode($url)), home_url('/'));
            $affiliate_link = '<p><a href="' . esc_url($redirect_url) . '" target="_blank" rel="nofollow noopener">' . $text . '</a></p>';

            // Insert after first paragraph
            $closing_p = strpos($content, '</p>');
            if ($closing_p !== false) {
                $first_para = substr($content, 0, $closing_p + 4);
                $rest = substr($content, $closing_p + 4);
                return $first_para . $affiliate_link . $rest;
            }
        }

        return $content;
    }
}

$geoAffiliateInstance = new GeoAffiliateSmartLinks();

// Redirect handler for cloaked links
add_action('template_redirect', function() {
    if (isset($_GET['geoaffiliate_redirect'])) {
        $url = base64_decode($_GET['geoaffiliate_redirect']);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            wp_redirect($url, 301);
            exit();
        }
    }
});
