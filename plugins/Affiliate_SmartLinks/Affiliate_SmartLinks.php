<?php
/*
Plugin Name: Affiliate SmartLinks
Plugin URI: https://example.com/affiliate-smartlinks
Description: Auto-replaces product mentions with your affiliate links to increase affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_SmartLinks.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateSmartLinks {
    private $affiliate_keywords = array(
        'laptop' => 'https://affiliate.example.com/product/laptop',
        'smartphone' => 'https://affiliate.example.com/product/smartphone',
        'headphones' => 'https://affiliate.example.com/product/headphones'
    );

    public function __construct() {
        add_filter('the_content', array($this, 'replace_keywords_with_links'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function replace_keywords_with_links($content) {
        foreach ($this->affiliate_keywords as $keyword => $url) {
            $pattern = '/\b(' . preg_quote($keyword, '/') . ')\b/i';
            $replacement = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">$1</a>';
            $content = preg_replace($pattern, $replacement, $content, 1); // replace first occurrence per keyword
        }
        return $content;
    }

    public function add_admin_menu() {
        add_options_page('Affiliate SmartLinks', 'Affiliate SmartLinks', 'manage_options', 'affiliate_smartlinks', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('affiliateSmartLinks', 'affiliateSmartLinks_options');

        add_settings_section(
            'affiliateSmartLinks_section',
            __('Configure your affiliate keywords and URLs', 'affiliateSmartLinks'),
            null,
            'affiliateSmartLinks'
        );

        add_settings_field(
            'affiliateSmartLinks_keywords',
            __('Affiliate Keywords and URLs', 'affiliateSmartLinks'),
            array($this, 'affiliateSmartLinks_keywords_render'),
            'affiliateSmartLinks',
            'affiliateSmartLinks_section'
        );
        
        $options = get_option('affiliateSmartLinks_options');
        if (isset($options['keywords'])) {
            $this->affiliate_keywords = json_decode(stripslashes($options['keywords']), true) ?: $this->affiliate_keywords;
        }
    }

    public function affiliateSmartLinks_keywords_render() {
        $options = get_option('affiliateSmartLinks_options');
        $text = isset($options['keywords']) ? stripslashes($options['keywords']) : json_encode($this->affiliate_keywords, JSON_PRETTY_PRINT);
        echo '<textarea cols="60" rows="10" name="affiliateSmartLinks_options[keywords]" aria-describedby="affiliateSmartLinks_keywords_help">' . esc_textarea($text) . '</textarea>';
        echo '<p id="affiliateSmartLinks_keywords_help">Enter JSON object of keyword to affiliate URL mappings, e.g. {"laptop":"https://example.com/laptop-affiliate"}</p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate SmartLinks</h2>
            <?php
            settings_fields('affiliateSmartLinks');
            do_settings_sections('affiliateSmartLinks');
            submit_button();
            ?>
        </form>
        <?php
    }
}

new AffiliateSmartLinks();
