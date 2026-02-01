/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content to boost earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_links = array();

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->affiliate_links = get_option('saa_affiliate_links', array());
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('saa-style', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || !is_single()) return $content;

        global $post;
        $keywords = get_post_meta($post->ID, 'saa_keywords', true);
        if (!$keywords) {
            $keywords = array_keys($this->affiliate_links);
        }

        foreach ($keywords as $keyword) {
            if (isset($this->affiliate_links[$keyword])) {
                $link = $this->affiliate_links[$keyword];
                $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
                $replacement = '<a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow sponsored" class="saa-aff-link">' . $keyword . '</a>';
                $content = preg_replace($pattern, $replacement, $content, 1);
            }
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'Affiliate Inserter', 'manage_options', 'smart-affiliate-autoinserter', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('saa_options', 'saa_affiliate_links');
        add_settings_section('saa_main_section', 'Affiliate Links Setup', null, 'smart-affiliate-autoinserter');
        add_settings_field('saa_links', 'Keyword -> Affiliate Link', array($this, 'links_field'), 'smart-affiliate-autoinserter', 'saa_main_section');
    }

    public function links_field() {
        $links = $this->affiliate_links;
        echo '<div id="saa-links-container">';
        foreach ($links as $keyword => $link) {
            echo '<div class="saa-link-row">';
            echo '<input type="text" name="saa_affiliate_links[' . esc_attr($keyword) . '][keyword]" value="' . esc_attr($keyword) . '" placeholder="Keyword">';
            echo '<input type="url" name="saa_affiliate_links[' . esc_attr($keyword) . '][url]" value="' . esc_url($link['url']) . '" placeholder="Affiliate URL">';
            echo '<button type="button" class="saa-remove">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="saa-add-link">Add New Link</button>';
        echo '<script> jQuery(document).ready(function($){ $("#saa-add-link").click(function(){ $("#saa-links-container").append(`<div class=\"saa-link-row\"><input type=\"text\" name=\"saa_affiliate_links[` + Date.now() + `][keyword]\" placeholder=\"Keyword\"><input type=\"url\" name=\"saa_affiliate_links[` + Date.now() + `][url]\" placeholder=\"Affiliate URL\"><button type=\"button\" class=\"saa-remove\">Remove</button></div>`); }); $(".saa-remove").click(function(){ $(this).parent().remove(); }); }); </script>';
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_options'); do_settings_sections('smart-affiliate-autoinserter'); submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI keyword detection, performance analytics, and A/B testing for $49/year.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('saa_affiliate_links', array('WordPress' => array('url' => 'https://affiliate-link-example.com/wordpress')));
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

// Pro upsell notice
function saa_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoInserter Pro</strong> for AI-powered features and analytics! <a href="https://example.com/pro" target="_blank">Learn More</a></p></div>';
}
add_action('admin_notices', 'saa_pro_notice');
?>