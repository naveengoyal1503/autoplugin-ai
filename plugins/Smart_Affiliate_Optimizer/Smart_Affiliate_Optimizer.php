/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Optimizer
 * Description: Automatically manages and optimizes affiliate links with dynamic offer suggestions and conversion tracking.
 * Version: 1.0
 * Author: Plugin Author
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartAffiliateOptimizer {
    private $option_name = 'sao_options';
    private $options;

    public function __construct() {
        $this->options = get_option($this->option_name, array('affiliate_links' => array()));

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));

        add_filter('the_content', array($this, 'auto_insert_affiliates'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sao_track_click', array($this, 'track_click')); // for logged-in users
        add_action('wp_ajax_nopriv_sao_track_click', array($this, 'track_click'));

        add_action('wp_footer', array($this, 'inject_tracking_script'));
    }

    public function add_admin_menu() {
        add_options_page('Smart Affiliate Optimizer', 'Smart Affiliate Optimizer', 'manage_options', 'smart_affiliate_optimizer', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('sao_settings', $this->option_name);
        add_settings_section('sao_section', __('Affiliate Links Settings', 'sao'), null, 'sao_settings');
        add_settings_field('affiliate_links', __('Affiliate Links', 'sao'), array($this, 'affiliate_links_html'), 'sao_settings', 'sao_section');
    }

    public function affiliate_links_html() {
        $value = isset($this->options['affiliate_links']) ? $this->options['affiliate_links'] : array();
        echo '<p>Define keywords and their affiliate URLs (one per line, format: keyword|affiliateURL):</p>';
        echo '<textarea name="' . esc_attr($this->option_name) . '[affiliate_links]" rows="8" cols="50" class="large-text code">';
        if (!empty($value) && is_array($value)) {
            foreach ($value as $line) {
                echo esc_html($line) . "\n";
            }
        }
        echo '</textarea>';
        echo '<p><em>Example:<br>product|https://affiliate.example.com/track?product=123</em></p>';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Smart Affiliate Optimizer</h2>
            <?php
            settings_fields('sao_settings');
            do_settings_sections('sao_settings');
            submit_button();
            ?>
        </form>
        <?php
    }

    // Hook into content and replace keywords with affiliate links
    public function auto_insert_affiliates($content) {
        if (is_singular() && in_the_loop() && is_main_query()) {
            $links = $this->options['affiliate_links'] ?? array();
            $map = array();
            foreach ($links as $line) {
                if (strpos($line, '|') !== false) {
                    list($keyword, $url) = explode('|', trim($line), 2);
                    $keyword = trim($keyword);
                    $url = trim($url);
                    if ($keyword && $url) {
                        $map[$keyword] = esc_url($url);
                    }
                }
            }
            if (empty($map)) {
                return $content;
            }

            // Avoid replacing inside existing links or tags
            $content = preg_replace_callback('/(<a .*?>.*?</a>)|(<.*?>)|([^<]+)/i', function($matches) use ($map) {
                // If this segment contains anchor tag or other tags, return as is
                if (!empty($matches[1]) || !empty($matches[2])) {
                    return $matches;
                }

                $text = $matches;

                foreach ($map as $keyword => $url) {
                    // Escape keyword for regex
                    $regex = '/\b(' . preg_quote($keyword, '/') . ')\b/i';
                    if (preg_match($regex, $text)) {
                        // Wrap keyword in affiliate link with onclick tracking
                        $replacement = '<a href="' . $url . '" class="sao-affiliate-link" target="_blank" rel="nofollow noopener" data-keyword="' . esc_attr($keyword) . '">$1</a>';
                        $text = preg_replace($regex, $replacement, $text, 1); // Replace first occurrence only
                    }
                }

                return $text;
            }, $content);

            return $content;
        }
        return $content;
    }

    // Enqueue JS for click tracking
    public function enqueue_scripts() {
        wp_enqueue_script('sao-script', plugin_dir_url(__FILE__) . 'sao-script.js', array('jquery'), '1.0', true);
        wp_localize_script('sao-script', 'sao_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sao_nonce')
        ));

        // Inline JS in footer via this method instead for self-contained single PHP
        $inline_js = "jQuery(document).ready(function($){
            $('body').on('click', '.sao-affiliate-link', function(e){
                var keyword = $(this).data('keyword');
                $.ajax({
                    type: 'POST',
                    url: sao_ajax.ajax_url,
                    data: {
                        action: 'sao_track_click',
                        keyword: keyword,
                        _ajax_nonce: sao_ajax.nonce
                    }
                });
            });
        });";
        wp_add_inline_script('jquery', $inline_js);
    }

    public function inject_tracking_script() {
        // No additional footer needed since injected inline script above
    }

    // AJAX handler to track clicks (very simple example: increments count stored in options)
    public function track_click() {
        check_ajax_referer('sao_nonce', '_ajax_nonce');
        $keyword = sanitize_text_field($_POST['keyword'] ?? '');
        if (!$keyword) {
            wp_send_json_error('No keyword');
        }
        $stats = get_option('sao_click_stats', array());
        if (!isset($stats[$keyword])) {
            $stats[$keyword] = 0;
        }
        $stats[$keyword]++;
        update_option('sao_click_stats', $stats);
        wp_send_json_success('Click tracked');
    }
}

new SmartAffiliateOptimizer();
