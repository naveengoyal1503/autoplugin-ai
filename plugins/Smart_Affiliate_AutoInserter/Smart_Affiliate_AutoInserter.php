/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages based on keyword matching. Boost your affiliate earnings effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'settings_link'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array());
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('smart_affiliate_plugin_page', 'smart_affiliate_options');

        add_settings_section(
            'smart_affiliate_section',
            'Affiliate Settings',
            null,
            'smart_affiliate_plugin_page'
        );

        add_settings_field(
            'smart_affiliate_amazon_tag',
            'Amazon Affiliate Tag',
            array($this, 'amazon_tag_render'),
            'smart_affiliate_plugin_page',
            'smart_affiliate_section'
        );

        add_settings_field(
            'smart_affiliate_keywords',
            'Keywords and Products',
            array($this, 'keywords_render'),
            'smart_affiliate_plugin_page',
            'smart_affiliate_section'
        );

        add_settings_field(
            'smart_affiliate_max_links',
            'Max Links per Post',
            array($this, 'max_links_render'),
            'smart_affiliate_plugin_page',
            'smart_affiliate_section'
        );
    }

    public function amazon_tag_render() {
        $options = $this->options;
        ?>
        <input type='text' name='smart_affiliate_options[amazon_tag]' value='<?php echo esc_attr($options['amazon_tag'] ?? ''); ?>' class='regular-text' placeholder='your-affiliate-tag-123' />
        <p class='description'>Enter your Amazon Associates affiliate tag (e.g., yourtag-20).</p>
        <?php
    }

    public function keywords_render() {
        $options = $this->options;
        $keywords = $options['keywords'] ?? "laptop:amazon.com/dp/B08N5WRWNW\nphone:amazon.com/dp/B09G9FPGT6";
        ?>
        <textarea name='smart_affiliate_options[keywords]' rows='5' cols='50' class='large-text'><?php echo esc_textarea($keywords); ?></textarea>
        <p class='description'>Format: keyword:amazon-product-url (one per line). Free version limited to 5 keywords.</p>
        <?php
    }

    public function max_links_render() {
        $options = $this->options;
        $max = $options['max_links'] ?? 3;
        ?>
        <input type='number' name='smart_affiliate_options[max_links]' value='<?php echo esc_attr($max); ?>' min='1' max='10' />
        <p class='description'>Maximum affiliate links to insert per post/page.</p>
        <?php
    }

    public function options_page() { ?>
        <div class='wrap'>
            <h1>Smart Affiliate AutoInserter</h1>
            <form method='post' action='options.php'>
                <?php
                settings_fields('smart_affiliate_plugin_page');
                do_settings_sections('smart_affiliate_plugin_page');
                submit_button();
                ?>
            </form>
            <?php if (empty($this->options['amazon_tag'])) { ?>
                <div class='notice notice-warning'><p><strong>Pro Tip:</strong> Upgrade to Pro for unlimited keywords, analytics, and A/B testing. <a href='https://example.com/pro' target='_blank'>Get Pro Now</a></p></div>
            <?php } ?>
        </div>
    <?php }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->options['amazon_tag'])) {
            return $content;
        }

        global $post;
        if (is_home() || is_archive() || empty($post)) {
            return $content;
        }

        $keywords = explode('\n', $this->options['keywords'] ?? '');
        $keyword_map = array();
        foreach ($keywords as $line) {
            $parts = explode(':', trim($line), 2);
            if (count($parts) === 2) {
                $keyword_map[strtolower(trim($parts))] = trim($parts[1]);
            }
        }

        if (empty($keyword_map)) {
            return $content;
        }

        $max_links = intval($this->options['max_links'] ?? 3);
        $inserted = 0;
        $content_parts = preg_split('/(<p[^>]*>.*<\/p>|<div[^>]*>.*<\/div>)/iU', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        foreach ($content_parts as &$part) {
            if ($inserted >= $max_links || !preg_match('/<p[^>]*>/i', $part)) {
                break;
            }

            $lower_part = strtolower($part);
            foreach ($keyword_map as $keyword => $url) {
                if (strpos($lower_part, $keyword) !== false && $inserted < $max_links) {
                    $link = sprintf(
                        '<a href="%s?tag=%s" target="_blank" rel="nofollow sponsored">%s</a>',
                        esc_url($url),
                        esc_attr($this->options['amazon_tag']),
                        esc_html(ucfirst($keyword))
                    );
                    $part = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', $link . ' \0', $part, 1);
                    $inserted++;
                    break;
                }
            }
        }

        return implode('', $content_parts);
    }

    public function settings_link($links) {
        $settings_link = '<a href="options-general.php?page=smart-affiliate-autoinserter">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

new SmartAffiliateAutoInserter();