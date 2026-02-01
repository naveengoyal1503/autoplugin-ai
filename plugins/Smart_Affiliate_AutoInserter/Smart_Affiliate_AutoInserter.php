/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching for passive income.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $api_key;
    private $affiliate_tag;
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'load_settings'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function load_settings() {
        $this->options = get_option('smart_affiliate_settings', array(
            'api_key' => '',
            'affiliate_tag' => '',
            'keywords' => array('laptop', 'phone', 'book'),
            'max_links' => 2,
            'enabled' => true
        ));
    }

    public function auto_insert_links($content) {
        if (!is_single() || !$this->options['enabled']) {
            return $content;
        }

        if (empty($this->options['api_key']) || empty($this->options['affiliate_tag'])) {
            return $content;
        }

        $paragraphs = explode('</p>', $content);
        $inserted = 0;
        $max_links = intval($this->options['max_links']);

        foreach ($paragraphs as &$paragraph) {
            if ($inserted >= $max_links) {
                break;
            }

            foreach ($this->options['keywords'] as $keyword) {
                if (stripos($paragraph, $keyword) !== false && $inserted < $max_links) {
                    $product = $this->get_amazon_product($keyword);
                    if ($product) {
                        $link = $this->create_affiliate_link($product);
                        $paragraph = str_replace($keyword, $link, $paragraph, $count);
                        if ($count > 0) {
                            $inserted++;
                        }
                    }
                }
            }
            $paragraph .= '</p>';
        }

        return implode('', $paragraphs);
    }

    private function get_amazon_product($keyword) {
        // Mock Amazon API call (replace with real Amazon Product Advertising API)
        // In pro version, integrate real API
        $mock_products = array(
            'laptop' => array('title' => 'Dell XPS 13 Laptop', 'asin' => 'B08N5LN5QJ', 'image' => 'https://images-na.ssl-images-amazon.com/images/I/81hj2aKQkQL.jpg'),
            'phone' => array('title' => 'iPhone 15', 'asin' => 'B0CHX1W1XT', 'image' => 'https://images-na.ssl-images-amazon.com/images/I/71BC5vBGTtL.jpg'),
            'book' => array('title' => 'Atomic Habits', 'asin' => 'B07RFSSYBH', 'image' => 'https://images-na.ssl-images-amazon.com/images/I/81Y4gC5bZAL.jpg')
        );

        return isset($mock_products[$keyword]) ? $mock_products[$keyword] : false;
    }

    private function create_affiliate_link($product) {
        $link = '<a href="https://www.amazon.com/dp/' . $product['asin'] . '?tag=' . $this->options['affiliate_tag'] . '" target="_blank" rel="nofollow sponsored">' .
                '<img src="' . $product['image'] . '" alt="' . esc_attr($product['title']) . '" style="max-width:200px;height:auto;"> ' .
                $product['title'] . '</a>';
        return $link;
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('smart_affiliate', 'smart_affiliate_settings');

        add_settings_section(
            'smart_affiliate_section',
            'API & Affiliate Settings',
            null,
            'smart_affiliate'
        );

        add_settings_field(
            'api_key',
            'Amazon API Key',
            array($this, 'api_key_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'affiliate_tag',
            'Your Affiliate Tag',
            array($this, 'affiliate_tag_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );

        add_settings_field(
            'keywords',
            'Keywords (comma separated)',
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

        add_settings_field(
            'enabled',
            'Enable Auto-Insert',
            array($this, 'enabled_render'),
            'smart_affiliate',
            'smart_affiliate_section'
        );
    }

    public function api_key_render() {
        $options = $this->options;
        ?><input type='text' name='smart_affiliate_settings[api_key]' value='<?php echo esc_attr($options['api_key']); ?>' class='regular-text' placeholder='Your Amazon Product API Key' /><?php
    }

    public function affiliate_tag_render() {
        $options = $this->options;
        ?><input type='text' name='smart_affiliate_settings[affiliate_tag]' value='<?php echo esc_attr($options['affiliate_tag']); ?>' class='regular-text' placeholder='yourtag-20' /><?php
    }

    public function keywords_render() {
        $options = $this->options;
        $keywords = isset($options['keywords']) ? implode(', ', (array)$options['keywords']) : '';
        ?><input type='text' name='smart_affiliate_settings[keywords]' value='<?php echo esc_attr($keywords); ?>' class='regular-text' placeholder='laptop, phone, book' /><?php
        echo '<p class="description">Comma-separated keywords to match and insert affiliate links.</p>';
    }

    public function max_links_render() {
        $options = $this->options;
        ?><input type='number' name='smart_affiliate_settings[max_links]' value='<?php echo esc_attr($options['max_links']); ?>' min='1' max='5' /><?php
    }

    public function enabled_render() {
        $options = $this->options;
        ?><input type='checkbox' name='smart_affiliate_settings[enabled]' value='1' <?php checked(1, $options['enabled']); ?> /><?php
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
            <p><strong>Pro Features (Upgrade for $49/year):</strong> Real Amazon API integration, unlimited keywords, A/B testing, analytics dashboard, custom niches, link cloaking.</p>
        </div><?php
    }

    public function activate() {
        add_option('smart_affiliate_settings');
    }
}

new SmartAffiliateAutoInserter();

// Pro upgrade notice
function smart_affiliate_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter Pro:</strong> Unlock unlimited links, real Amazon API, and analytics for $49/year! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'smart_affiliate_pro_notice');