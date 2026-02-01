/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into WordPress posts and pages using keyword matching to boost monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'load_options'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function load_options() {
        $this->options = get_option('smart_affiliate_options', array(
            'enabled' => '1',
            'affiliates' => array(),
            'max_links' => 3,
            'pro' => false
        ));
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || !$this->options['enabled'] || empty($this->options['affiliates'])) {
            return $content;
        }

        $words = explode(' ', strip_tags($content));
        $inserted = 0;
        $max_links = intval($this->options['max_links']);

        foreach ($this->options['affiliates'] as $aff) {
            if ($inserted >= $max_links) break;

            $keyword = strtolower($aff['keyword']);
            $pos = 0;
            $link_html = '<a href="' . esc_url($aff['url']) . '" target="_blank" rel="nofollow noopener">' . esc_html($aff['text']) . '</a>';

            while (($pos = stripos($content, $keyword, $pos)) !== false && $inserted < $max_links) {
                $content = substr_replace($content, $link_html, $pos, strlen($keyword));
                $pos += strlen($link_html);
                $inserted++;
                if ($inserted >= $max_links) break;
            }
        }

        return $content;
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
            'Affiliate Links',
            null,
            'smart_affiliate'
        );

        add_settings_field(
            'enabled',
            'Enable Auto-Insertion',
            array($this, 'enabled_cb'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'max_links',
            'Max Links per Post',
            array($this, 'max_links_cb'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'affiliates',
            'Affiliate Links',
            array($this, 'affiliates_cb'),
            'smart_affiliate',
            'smart_affiliate_section'
        );
    }

    public function enabled_cb() {
        $val = isset($this->options['enabled']) ? $this->options['enabled'] : '1';
        echo '<input type="checkbox" name="smart_affiliate_options[enabled]" value="1" ' . checked(1, $val, false) . ' />';
    }

    public function max_links_cb() {
        $val = isset($this->options['max_links']) ? $this->options['max_links'] : 3;
        echo '<input type="number" name="smart_affiliate_options[max_links]" value="' . esc_attr($val) . '" min="1" max="10" />';
    }

    public function affiliates_cb() {
        echo '<div id="affiliates-list">';
        $affiliates = isset($this->options['affiliates']) ? $this->options['affiliates'] : array();
        foreach ($affiliates as $i => $aff) {
            echo '<div class="affiliate-row">';
            echo '<input type="text" name="smart_affiliate_options[affiliates][' . $i . '][keyword]" placeholder="Keyword" value="' . esc_attr($aff['keyword']) . '" /> ';
            echo '<input type="text" name="smart_affiliate_options[affiliates][' . $i . '][text]" placeholder="Link Text" value="' . esc_attr($aff['text']) . '" /> ';
            echo '<input type="url" name="smart_affiliate_options[affiliates][' . $i . '][url]" placeholder="Affiliate URL" value="' . esc_url($aff['url']) . '" /> ';
            echo '<button type="button" class="button remove-aff">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<button type="button" id="add-affiliate" class="button">Add Affiliate</button>';
        echo '<script>
        jQuery(document).ready(function($) {
            var i = ' . count($affiliates) . ';
            $("#add-affiliate").click(function() {
                var row = "<div class=\'affiliate-row\'><input type=\'text\' name=\'smart_affiliate_options[affiliates]["+i+"][keyword]\' placeholder=\'Keyword\' /> " +
                          "<input type=\'text\' name=\'smart_affiliate_options[affiliates]["+i+"][text]\' placeholder=\'Link Text\' /> " +
                          "<input type=\'url\' name=\'smart_affiliate_options[affiliates]["+i+"][url]\' placeholder=\'Affiliate URL\' /> " +
                          "<button type=\'button\' class=\'button remove-aff\'>Remove</button></div>";
                $("#affiliates-list").append(row);
                i++;
            });
            $(document).on("click", ".remove-aff", function() {
                $(this).parent().remove();
            });
        });
        </script>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate');
                do_settings_sections('smart_affiliate');
                submit_button();
                ?>
            </form>
            <?php if (!$this->options['pro']): ?>
            <div class="notice notice-info">
                <p>Upgrade to Pro for AI-powered keyword matching, A/B testing, and analytics!</p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_options', array('enabled' => '1', 'max_links' => 3));
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

// Pro check (simplified)
add_action('admin_notices', function() {
    $options = get_option('smart_affiliate_options', array());
    if (!$options['pro']) {
        // Pro upsell logic here
    }
});