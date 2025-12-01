<?php
/*
Plugin Name: Affiliate Link Optimizer
Description: Detect and optimize affiliate links automatically for better conversions.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Optimizer.php
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class Affiliate_Link_Optimizer {
    private $affiliate_domains = array('amazon.com', 'ebay.com', 'shareasale.com');
    private $option_name = 'alo_settings';

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'process_content_affiliate_links'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        // Optional: enqueue front-end JS if needed
    }

    public function process_content_affiliate_links($content) {
        // Detect all links in content
        if (empty($content)) return $content;
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        $links = $dom->getElementsByTagName('a');

        $settings = get_option($this->option_name, array());
        $cloak_base_url = isset($settings['cloak_base_url']) ? esc_url($settings['cloak_base_url']) : home_url('/go/');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            foreach ($this->affiliate_domains as $domain) {
                if (strpos($href, $domain) !== false) {
                    // Cloak link and add tracking parameters
                    $cloaked_url = $this->cloak_url($href, $cloak_base_url);
                    $link->setAttribute('href', $cloaked_url);
                    $link->setAttribute('rel', 'nofollow sponsored');
                    $link->setAttribute('target', '_blank');
                    break;
                }
            }
        }

        // Save processed content
        $html = $dom->saveHTML();
        // Remove the extra tags added by DOMDocument
        $body_start = strpos($html, '<body>') + 6;
        $body_end = strpos($html, '</body>');
        $processed_content = substr($html, $body_start, $body_end - $body_start);

        return $processed_content;
    }

    private function cloak_url($url, $base) {
        // Cloak by redirecting through a path under plugin, encoding original URL
        $encoded = rawurlencode($url);
        return trailingslashit($base) . '?url=' . $encoded;
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Link Optimizer', 'Affiliate Link Optimizer', 'manage_options', 'affiliate-link-optimizer', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('alo_plugin', $this->option_name);

        add_settings_section('alo_plugin_section', __('Main Settings', 'alo'), null, 'alo_plugin');

        add_settings_field(
            'alo_cloak_base_url',
            __('Link Cloaking Base URL', 'alo'),
            array($this, 'cloak_base_url_render'),
            'alo_plugin',
            'alo_plugin_section'
        );
    }

    public function cloak_base_url_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='alo_settings[cloak_base_url]' value='<?php echo isset($options['cloak_base_url']) ? esc_attr($options['cloak_base_url']) : home_url('/go/'); ?>' size='50'>
        <p class='description'>Base URL for cloaked affiliate links (e.g., yoursite.com/go)</p>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Link Optimizer Settings</h2>
            <?php
            settings_fields('alo_plugin');
            do_settings_sections('alo_plugin');
            submit_button();
            ?>
        </form>
        <?php
    }

}

new Affiliate_Link_Optimizer();

// Handle redirect for cloaked links
add_action('init', function() {
    if (isset($_GET['url']) && strpos($_SERVER['REQUEST_URI'], '/go/') !== false) {
        $url = rawurldecode($_GET['url']);
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            wp_redirect($url, 302);
            exit;
        }
    }
});
