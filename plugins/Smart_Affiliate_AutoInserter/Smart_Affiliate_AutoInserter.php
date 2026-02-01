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
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $api_key;
    private $affiliate_tag;
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'api_key' => '',
            'affiliate_tag' => '',
            'keywords' => array(),
            'pro' => false
        ));
        $this->api_key = $this->options['api_key'];
        $this->affiliate_tag = $this->options['affiliate_tag'];
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (is_admin() || !$this->affiliate_tag) return $content;

        if (!$this->options['pro'] && substr_count($content, 'data-aff-link') >= 3) {
            return $content; // Free limit: 3 links per post
        }

        global $post;
        $keywords = $this->options['keywords'];
        if (empty($keywords)) return $content;

        foreach ($keywords as $keyword => $asin) {
            if (stripos($content, $keyword) !== false && stripos($content, 'data-aff-link') === false) {
                $link = $this->get_amazon_link($asin);
                $content = preg_replace('/(' . preg_quote($keyword, '/') . ')(?!\s*(?:<\/?\w+[^>]*>))/i', '<span class="aff-link" data-aff-link="true">$1</span><a href="$link" target="_blank" rel="nofollow sponsored"> (Amazon)</a>', $content, 1);
                if (!$this->options['pro'] && substr_count($content, 'data-aff-link') >= 3) break;
            }
        }
        return $content;
    }

    private function get_amazon_link($asin) {
        return "https://www.amazon.com/dp/" . esc_attr($asin) . "?tag=" . esc_attr($this->affiliate_tag);
    }

    public function add_admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'smart-affiliate', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('smart_affiliate', 'smart_affiliate_options');

        add_settings_section('smart_section', 'API & Affiliate Settings', null, 'smart_affiliate');

        add_settings_field('api_key', 'Amazon API Key (Pro)', array($this, 'api_key_render'), 'smart_affiliate', 'smart_section');
        add_settings_field('affiliate_tag', 'Amazon Affiliate Tag', array($this, 'affiliate_tag_render'), 'smart_affiliate', 'smart_section');
        add_settings_field('keywords', 'Keywords & ASINs (keyword:ASIN)', array($this, 'keywords_render'), 'smart_affiliate', 'smart_section');
        add_settings_field('pro', 'Go Pro', array($this, 'pro_render'), 'smart_affiliate', 'smart_section');
    }

    public function api_key_render() {
        $options = $this->options;
        echo '<input type="text" name="smart_affiliate_options[api_key]" value="' . esc_attr($options['api_key']) . '" class="regular-text" />';
    }

    public function affiliate_tag_render() {
        $options = $this->options;
        echo '<input type="text" name="smart_affiliate_options[affiliate_tag]" value="' . esc_attr($options['affiliate_tag']) . '" class="regular-text" placeholder="yourtag-20" /> <p class="description">Your Amazon Associates tag (e.g., yourtag-20)</p>';
    }

    public function keywords_render() {
        $options = $this->options;
        $keywords_str = implode('\n', array_map(function($k, $v) { return $k . ':' . $v; }, array_keys($options['keywords']), $options['keywords']));
        echo '<textarea name="smart_affiliate_options[keywords_str]" rows="5" cols="50">' . esc_textarea($keywords_str) . '</textarea>';
        echo '<p class="description">Enter one per line: keyword:ASIN (e.g., laptop:B07H8Q1T4V)</p>';
    }

    public function pro_render() {
        echo '<p>Unlock unlimited links, analytics & A/B testing with Pro! <a href="https://example.com/pro" target="_blank">Upgrade Now ($29/year)</a></p>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('smart_affiliate');
                do_settings_sections('smart_affiliate');
                submit_button();
                ?>
            </form>
            <?php $this->handle_keywords_update(); ?>
        </div>
        <?php
    }

    private function handle_keywords_update() {
        if (isset($_POST['smart_affiliate_options']['keywords_str'])) {
            $str = sanitize_textarea_field($_POST['smart_affiliate_options']['keywords_str']);
            $lines = explode('\n', $str);
            $keywords = array();
            foreach ($lines as $line) {
                $parts = explode(':', trim($line), 2);
                if (count($parts) === 2) {
                    $keywords[sanitize_text_field($parts)] = sanitize_text_field($parts[1]);
                }
            }
            $options = $this->options;
            $options['keywords'] = $keywords;
            update_option('smart_affiliate_options', $options);
        }
    }

    public function activate() {
        add_option('smart_affiliate_options', array());
    }
}

new SmartAffiliateAutoInserter();

// Pro check (demo - in real pro, verify license)
function is_pro() {
    $options = get_option('smart_affiliate_options', array());
    return !empty($options['pro']);
}

// Add assets folder note: Create /assets/script.js with empty or basic JS for stats
?>