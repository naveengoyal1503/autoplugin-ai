/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages for passive income.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'affiliate_tag' => '',
            'keywords' => array(),
            'max_links' => 3,
            'pro_version' => false
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || !is_single()) return $content;

        $keywords = $this->options['keywords'];
        $max_links = intval($this->options['max_links']);
        $aff_tag = $this->options['affiliate_tag'];
        $inserted = 0;

        foreach ($keywords as $keyword => $asin) {
            if ($inserted >= $max_links) break;

            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($pattern, $content)) {
                $link = $this->generate_amazon_link($asin, $aff_tag, $keyword);
                $content = preg_replace($pattern, $link, $content, 1);
                $inserted++;
            }
        }

        return $content;
    }

    private function generate_amazon_link($asin, $aff_tag, $text) {
        $url = 'https://www.amazon.com/dp/' . $asin . '?tag=' . $aff_tag;
        return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow sponsored">' . esc_html($text) . '</a> ';
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('smart_affiliate', 'smart_affiliate_options');

        add_settings_section(
            'smart_affiliate_section',
            'Affiliate Settings',
            null,
            'smart_affiliate'
        );

        add_settings_field(
            'affiliate_tag',
            'Amazon Affiliate Tag',
            array($this, 'affiliate_tag_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'keywords',
            'Keywords & ASINs (keyword:ASIN per line)',
            array($this, 'keywords_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'max_links',
            'Max Links per Post',
            array($this, 'max_links_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );
    }

    public function affiliate_tag_render() {
        $options = $this->options;
        echo '<input type="text" name="smart_affiliate_options[affiliate_tag]" value="' . esc_attr($options['affiliate_tag']) . '" />';
        echo '<p class="description">Your Amazon Associates tag (e.g., yourtag-20)</p>';
    }

    public function keywords_render() {
        $options = $this->options;
        $keywords_str = implode("\n", array_map(function($k, $v) { return $k . ':' . $v; }, array_keys($options['keywords']), $options['keywords']));
        echo '<textarea name="smart_affiliate_options[keywords_str]" rows="10" cols="50">' . esc_textarea($keywords_str) . '</textarea>';
        echo '<p class="description">One per line: keyword:ASIN (e.g., laptop:B08N5WRWNW)</p>';
    }

    public function max_links_render() {
        $options = $this->options;
        echo '<input type="number" name="smart_affiliate_options[max_links]" value="' . esc_attr($options['max_links']) . '" min="1" max="10" />';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate');
                do_settings_sections('smart_affiliate');
                submit_button();
                ?>
            </form>
            <?php $this->pro_notice(); ?>
        </div>
        <?php
    }

    private function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Go Pro!</strong> Unlock AI context matching, analytics, and unlimited links for $49/year. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }

    public function activate() {
        add_option('smart_affiliate_options', array(
            'affiliate_tag' => '',
            'keywords' => array(),
            'max_links' => 3,
            'pro_version' => false
        ));
    }

    public function deactivate() {
        // Cleanup optional
    }
}

new SmartAffiliateAutoInserter();

// Handle keywords save
function smart_affiliate_save_keywords($input) {
    if (!empty($input['keywords_str'])) {
        $lines = explode("\n", trim($input['keywords_str']));
        $keywords = array();
        foreach ($lines as $line) {
            $parts = explode(':', trim($line), 2);
            if (count($parts) === 2) {
                $keywords[trim($parts)] = trim($parts[1]);
            }
        }
        $input['keywords'] = $keywords;
    } else {
        $input['keywords'] = array();
    }
    unset($input['keywords_str']);
    return $input;
}
add_filter('sanitize_option_smart_affiliate_options', 'smart_affiliate_save_keywords', 10, 2);

// Pro check stub
if (file_exists(plugin_dir_path(__FILE__) . 'pro-version.php')) {
    require_once plugin_dir_path(__FILE__) . 'pro-version.php';
}