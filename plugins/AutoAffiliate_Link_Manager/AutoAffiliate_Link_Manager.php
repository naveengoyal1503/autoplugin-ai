<?php
/*
Plugin Name: AutoAffiliate Link Manager
Description: Automatically converts specified keywords into affiliate links throughout your posts and pages with link tracking.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AutoAffiliate_Link_Manager.php
*/

if (!defined('ABSPATH')) exit;

class AutoAffiliateLinkManager {
    private $keywords = array();

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'replace_keywords_with_links'));
    }

    public function add_admin_menu() {
        add_options_page('AutoAffiliate Links', 'AutoAffiliate Links', 'manage_options', 'auto_affiliate_links', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('auto_affiliate_links', 'aal_keywords');

        add_settings_section(
            'aal_section',
            __('Configure Keywords and Affiliate URLs', 'autoaffiliate'),
            null,
            'auto_affiliate_links'
        );

        add_settings_field(
            'aal_keywords_textarea',
            __('Keywords and URLs (one per line, format: keyword|https://affiliate-link.com)', 'autoaffiliate'),
            array($this, 'keywords_textarea_render'),
            'auto_affiliate_links',
            'aal_section'
        );
    }

    public function keywords_textarea_render() {
        $options = get_option('aal_keywords');
        echo '<textarea cols="60" rows="10" name="aal_keywords">'.esc_textarea($options).'</textarea>';
        echo '<p class="description">Example:<br />product|https://example.com/affiliate?ref=123</p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>AutoAffiliate Link Manager</h2>
            <?php
            settings_fields('auto_affiliate_links');
            do_settings_sections('auto_affiliate_links');
            submit_button();
            ?>
        </form>
        <?php
    }

    // Parse the user input into keyword=>url pairs
    private function get_keywords() {
        if (!empty($this->keywords)) {
            return $this->keywords;
        }

        $raw = get_option('aal_keywords');
        $pairs = array();
        if (!empty($raw)) {
            $lines = explode("\n", $raw);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '|') === false) continue;
                list($keyword, $url) = explode('|', $line, 2);
                $keyword = trim($keyword);
                $url = trim($url);
                if ($keyword && $url) {
                    $pairs[$keyword] = esc_url_raw($url);
                }
            }
        }
        $this->keywords = $pairs;
        return $pairs;
    }

    public function replace_keywords_with_links($content) {
        $keywords = $this->get_keywords();
        if (empty($keywords)) return $content;

        // Avoid replacing in existing links
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        // Load content as HTML fragment
        $wrappedContent = '<div>' . mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8') . '</div>';
        $dom->loadHTML($wrappedContent, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);

        // Find all text nodes not inside anchor tags
        $textNodes = $xpath->query('//text()[not(ancestor::a)]');

        foreach ($textNodes as $textNode) {
            $originalText = $textNode->nodeValue;
            $replacedText = $originalText;

            foreach ($keywords as $keyword => $url) {
                // Regex word boundary to match whole words
                $regex = '/\b' . preg_quote($keyword, '/') . '\b/i';
                if (preg_match($regex, $replacedText)) {
                    // Replace only the first occurrence
                    $replacement = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">' . $keyword . '</a>';
                    $replacedText = preg_replace($regex, $replacement, $replacedText, 1);
                }
            }

            if ($replacedText !== $originalText) {
                // Replace text node with new HTML
                $fragment = $dom->createDocumentFragment();
                $fragment->appendXML($replacedText);
                $textNode->parentNode->replaceChild($fragment, $textNode);
            }
        }

        $newContent = '';
        foreach ($dom->documentElement->childNodes as $child) {
            $newContent .= $dom->saveHTML($child);
        }
        return $newContent;
    }
}

new AutoAffiliateLinkManager();
