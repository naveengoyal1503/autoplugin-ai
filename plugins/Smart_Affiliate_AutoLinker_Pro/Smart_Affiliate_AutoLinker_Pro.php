/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects content keywords and inserts relevant affiliate links from Amazon, boosting commissions without manual work.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoLinker {
    private $keywords = [];
    private $affiliate_id = '';

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_filter('the_content', [$this, 'auto_link_affiliates']);
        add_filter('wp_insert_post_data', [$this, 'save_post_data'], 10, 2);
    }

    public function init() {
        $this->affiliate_id = get_option('saal_amazon_affiliate_id', '');
        $this->keywords = get_option('saal_keywords', [
            'laptop' => 'https://amazon.com/dp/B08N5WRWNW?tag=YOURAFFID',
            'phone' => 'https://amazon.com/dp/B0C7C4L2S6?tag=YOURAFFID',
            'book' => 'https://amazon.com/dp/1234567890?tag=YOURAFFID'
        ]);
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker Settings',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            [$this, 'options_page']
        );
    }

    public function settings_init() {
        register_setting('saal_plugin_page', 'saal_amazon_affiliate_id');
        register_setting('saal_plugin_page', 'saal_keywords');

        add_settings_section(
            'saal_plugin_page_section',
            'Affiliate Settings',
            [$this, 'settings_section_callback'],
            'saal_plugin_page'
        );

        add_settings_field(
            'saal_affiliate_id',
            'Amazon Affiliate ID',
            [$this, 'affiliate_id_render'],
            'saal_plugin_page',
            'saal_plugin_page_section'
        );

        add_settings_field(
            'saal_keywords',
            'Keywords and Links',
            [$this, 'keywords_render'],
            'saal_plugin_page',
            'saal_plugin_page_section'
        );
    }

    public function settings_section_callback() {
        echo '<p>Configure your affiliate links here. Free version supports 3 keywords; upgrade to Pro for unlimited.</p>';
    }

    public function affiliate_id_render() {
        $affiliate_id = get_option('saal_amazon_affiliate_id');
        echo '<input type="text" name="saal_amazon_affiliate_id" value="' . esc_attr($affiliate_id) . '" size="50" />';
    }

    public function keywords_render() {
        $keywords = get_option('saal_keywords', []);
        echo '<textarea name="saal_keywords" rows="10" cols="100">' . esc_textarea(json_encode($keywords, JSON_PRETTY_PRINT)) . '</textarea>';
        echo '<p class="description">JSON format: {"keyword":"amazon-link-url"}</p>';
        echo '<p><strong>Pro Feature:</strong> Unlimited keywords, A/B testing, analytics. <a href="#" onclick="alert(\'Upgrade to Pro for $49/year!\')">Upgrade Now</a></p>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Pro</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('saal_plugin_page');
                do_settings_sections('saal_plugin_page');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function auto_link_affiliates($content) {
        if (is_admin() || !is_single()) return $content;

        global $post;
        if (get_post_meta($post->ID, '_saal_disable', true)) return $content;

        foreach ($this->keywords as $keyword => $link) {
            if (stripos($content, $keyword) !== false && stripos($content, 'saal-linked') === false) {
                $link_html = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored" class="saal-link">' . esc_html($keyword) . '</a>';
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link_html . '<span class="saal-linked"></span>', $content, 1);
            }
        }
        return $content;
    }

    public function save_post_data($data, $postarr) {
        if (isset($_POST['saal_disable'])) {
            update_post_meta($postarr['ID'], '_saal_disable', '1');
        }
        return $data;
    }
}

new SmartAffiliateAutoLinker();

// Pro teaser notice
function saal_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoLinker Pro:</strong> Unlock unlimited keywords, analytics & more for $49/year! <a href="https://example.com/pro">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'saal_pro_notice');

// Add post meta box to disable per post
function saal_add_meta_box() {
    add_meta_box('saal-disable', 'Affiliate AutoLinker', 'saal_meta_box_callback', 'post', 'side');
}
add_action('add_meta_boxes', 'saal_add_meta_box');

function saal_meta_box_callback($post) {
    $disable = get_post_meta($post->ID, '_saal_disable', true);
    echo '<label><input type="checkbox" name="saal_disable" ' . checked($disable, '1', false) . '> Disable auto-linking on this post</label>';
}
