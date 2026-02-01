/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages based on keyword matching. Boost your passive income effortlessly.
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
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_filter('widget_text', array($this, 'auto_insert_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array());
    }

    public function activate() {
        add_option('smart_affiliate_settings');
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function settings_init() {
        register_setting('smart_affiliate_plugin', 'smart_affiliate_settings');

        add_settings_section(
            'smart_affiliate_section',
            'Affiliate Link Rules',
            null,
            'smart_affiliate'
        );

        add_settings_field(
            'affiliate_links',
            'Keyword -> Product Links',
            array($this, 'affiliate_links_callback'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'amazon_id',
            'Your Amazon Associate ID',
            array($this, 'amazon_id_callback'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'max_links',
            'Max Links Per Post (Free: 3)',
            array($this, 'max_links_callback'),
            'smart_affiliate',
            'smart_affiliate_section'
        );
    }

    public function affiliate_links_callback() {
        $options = $this->options;
        $value = isset($options['affiliate_links']) ? $options['affiliate_links'] : "laptop: B08N5WRWNW\nphone: B07RF1XD5K\nshoes: B01M0L9V2Q";
        echo '<textarea name="smart_affiliate_settings[affiliate_links]" rows="10" cols="50">' . esc_textarea($value) . '</textarea>';
        echo '<p class="description">Format: keyword:amazon_asin (one per line)</p>';
    }

    public function amazon_id_callback() {
        $options = $this->options;
        $value = isset($options['amazon_id']) ? $options['amazon_id'] : '';
        echo '<input type="text" name="smart_affiliate_settings[amazon_id]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">Your Amazon Associates tag, e.g., yourid-20</p>';
    }

    public function max_links_callback() {
        $options = $this->options;
        $value = isset($options['max_links']) ? $options['max_links'] : 3;
        echo '<input type="number" name="smart_affiliate_settings[max_links]" value="' . esc_attr($value) . '" min="1" max="10" />';
        echo '<p class="description">Pro version allows unlimited.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_plugin');
                do_settings_sections('smart_affiliate');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited links, analytics, and more for $49/year. <a href="#" onclick="alert('Visit pro version at example.com/pro')">Learn More</a></p>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (is_admin() || empty($this->options['amazon_id'])) {
            return $content;
        }

        $links_str = isset($this->options['affiliate_links']) ? $this->options['affiliate_links'] : '';
        $max_links = isset($this->options['max_links']) ? (int)$this->options['max_links'] : 3;
        $inserted = 0;

        $link_rules = array();
        $lines = explode("\n", trim($links_str));
        foreach ($lines as $line) {
            $parts = explode(':', trim($line), 2);
            if (count($parts) === 2) {
                $keyword = trim($parts);
                $asin = trim($parts[1]);
                $link_rules[$keyword] = $asin;
            }
        }

        $content_lower = strtolower($content);
        foreach ($link_rules as $keyword => $asin) {
            if ($inserted >= $max_links) break;
            $pos = strpos($content_lower, strtolower($keyword));
            if ($pos !== false) {
                $link_html = '<a href="https://amazon.com/dp/' . esc_attr($asin) . '?tag=' . esc_attr($this->options['amazon_id']) . '" target="_blank" rel="nofollow sponsored">' . esc_html(ucwords($keyword)) . '</a>';
                $content = substr_replace($content, $link_html, $pos, strlen($keyword));
                $content_lower = strtolower($content); // Update for next search
                $inserted++;
            }
        }

        return $content;
    }
}

new SmartAffiliateAutoInserter();