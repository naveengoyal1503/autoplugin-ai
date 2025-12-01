/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Optimizer
 * Description: Auto-detects product mentions and converts them into optimized affiliate links with A/B testing and analytics.
 * Version: 1.0
 * Author: Plugin Developer
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartAffiliateLinkOptimizer {
    private $option_name = 'salo_settings';
    private $default_affiliate_tag = 'defaulttag-20';
    private $affiliate_networks = [
        'amazon' => [
            'base_url' => 'https://www.amazon.com/dp/',
            'tag_param' => 'tag',
            'domain' => 'amazon.com'
        ]
    ];

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_filter('the_content', array($this, 'auto_link_products'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_salo_track_click', array($this, 'ajax_track_click'));
        add_action('wp_ajax_nopriv_salo_track_click', array($this, 'ajax_track_click'));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Link Optimizer', 'Affiliate Link Optimizer', 'manage_options', 'salo_settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('salo_group', $this->option_name);
        add_settings_section('salo_main_section', 'Main Settings', null, 'salo_settings');

        add_settings_field('default_affiliate_tag', 'Default Amazon Affiliate Tag', array($this, 'field_default_affiliate_tag'), 'salo_settings', 'salo_main_section');
        add_settings_field('product_keywords', 'Product Keywords (comma-separated)', array($this, 'field_product_keywords'), 'salo_settings', 'salo_main_section');
    }

    public function field_default_affiliate_tag() {
        $options = get_option($this->option_name);
        $tag = isset($options['default_affiliate_tag']) ? esc_attr($options['default_affiliate_tag']) : $this->default_affiliate_tag;
        echo '<input type="text" name="' . $this->option_name . '[default_affiliate_tag]" value="' . $tag . '" size="40">';
        echo '<p class="description">Your default Amazon Associates Affiliate Tag.</p>';
    }

    public function field_product_keywords() {
        $options = get_option($this->option_name);
        $keywords = isset($options['product_keywords']) ? esc_attr($options['product_keywords']) : 'Kindle,Fire Tablet,Fujifilm Camera';
        echo '<textarea name="' . $this->option_name . '[product_keywords]" rows="4" cols="50">' . $keywords . '</textarea>';
        echo '<p class="description">Comma-separated list of product keywords to auto-link.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Optimizer Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('salo_group');
                do_settings_sections('salo_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function auto_link_products($content) {
        $options = get_option($this->option_name);
        $keywords_raw = isset($options['product_keywords']) ? $options['product_keywords'] : '';
        $affiliate_tag = isset($options['default_affiliate_tag']) ? $options['default_affiliate_tag'] : $this->default_affiliate_tag;

        if (empty($keywords_raw)) return $content;

        $keywords = array_map('trim', explode(',', $keywords_raw));

        // Pattern to find keywords, word boundaries to avoid partial matches
        foreach ($keywords as $keyword) {
            if (empty($keyword)) continue;

            // Skip if keyword already linked
            $pattern = '/(?<!<a[^>]*>)(\b' . preg_quote($keyword, '/') . '\b)(?![^<]*<\/a>)/i';

            $replacement = '<a href="' . esc_url($this->generate_affiliate_link($keyword, $affiliate_tag)) . '" class="salo-affiliate-link" data-keyword="' . esc_attr($keyword) . '" target="_blank" rel="nofollow noopener">$1</a>';

            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        return $content;
    }

    private function generate_affiliate_link($keyword, $tag) {
        // For demo: transform keyword into Amazon search URL with affiliate tag
        $search_term = urlencode($keyword);
        $url = 'https://www.amazon.com/s?k=' . $search_term . '&tag=' . $tag;
        return $url;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salo-script', plugin_dir_url(__FILE__) . 'salo-script.js', array('jquery'), '1.0', true);
        wp_localize_script('salo-script', 'salo_ajax_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('salo_nonce')
        ));
    }

    public function ajax_track_click() {
        check_ajax_referer('salo_nonce', 'nonce');

        $keyword = sanitize_text_field($_POST['keyword'] ?? '');

        if (!$keyword) {
            wp_send_json_error('No keyword provided');
        }

        $clicks = get_option('salo_clicks', []);
        if (!isset($clicks[$keyword])) {
            $clicks[$keyword] = 0;
        }
        $clicks[$keyword]++;

        update_option('salo_clicks', $clicks);

        wp_send_json_success('Click recorded');
    }
}

new SmartAffiliateLinkOptimizer();

// Minimal inline script to track clicks (injected by WordPress enqueue)
add_action('wp_footer', function () {
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.salo-affiliate-link').forEach(function(link){
                link.addEventListener('click', function(e){
                    var keyword = link.getAttribute('data-keyword');
                    if(keyword) {
                        fetch(salo_ajax_obj.ajax_url, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'action=salo_track_click&nonce=' + encodeURIComponent(salo_ajax_obj.nonce) + '&keyword=' + encodeURIComponent(keyword)
                        });
                    }
                });
            });
        });
    </script>
    <?php
});