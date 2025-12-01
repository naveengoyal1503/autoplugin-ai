/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Auto_Affiliate_Deals.php
*/
<?php
/**
 * Plugin Name: Auto Affiliate Deals
 * Description: Automatically fetch and display affiliate coupons and deals for your niche with shortcode.
 * Version: 1.0
 * Author: Plugin Dev
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AutoAffiliateDeals {
    private $option_name = 'aad_options';
    private $transient_key = 'aad_deals_cache';
    private $api_endpoint = 'https://api.example-affiliate.com/v1/deals'; // Placeholder API

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('auto_affiliate_deals', array($this, 'shortcode_render'));
    }

    // Admin menu
    public function add_admin_menu() {
        add_options_page('Auto Affiliate Deals', 'Auto Affiliate Deals', 'manage_options', 'auto_affiliate_deals', array($this, 'options_page'));
    }

    // Register settings
    public function settings_init() {
        register_setting('aad_group', $this->option_name);

        add_settings_section(
            'aad_section',
            __('Affiliate API Settings', 'aad'),
            function() { echo __('Configure API credentials and parameters.', 'aad'); },
            'auto_affiliate_deals'
        );

        add_settings_field(
            'aad_api_key',
            __('API Key', 'aad'),
            array($this, 'render_api_key'),
            'auto_affiliate_deals',
            'aad_section'
        );

        add_settings_field(
            'aad_keyword',
            __('Search Keyword', 'aad'),
            array($this, 'render_keyword'),
            'auto_affiliate_deals',
            'aad_section'
        );
    }

    // Fields rendering
    public function render_api_key() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo esc_attr($this->option_name); ?>[api_key]' value='<?php echo isset($options['api_key']) ? esc_attr($options['api_key']) : ''; ?>' size='40'>
        <?php
    }

    public function render_keyword() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo esc_attr($this->option_name); ?>[keyword]' value='<?php echo isset($options['keyword']) ? esc_attr($options['keyword']) : ''; ?>' size='40'>
        <p class='description'>Enter the keyword to fetch relevant deals (e.g., 'laptop', 'fitness').</p>
        <?php
    }

    // Options page
    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Auto Affiliate Deals Settings</h2>
            <?php
            settings_fields('aad_group');
            do_settings_sections('auto_affiliate_deals');
            submit_button();
            ?>
        </form>
        <?php
    }

    // Fetch deals from remote API or cache
    private function get_deals() {
        $cached = get_transient($this->transient_key);
        if ($cached !== false) {
            return $cached;
        }

        $options = get_option($this->option_name);
        if (empty($options['api_key']) || empty($options['keyword'])) {
            return [];
        }

        // Build API request
        $url = add_query_arg(array(
            'api_key' => $options['api_key'],
            'q' => sanitize_text_field($options['keyword']),
            'limit' => 5
        ), $this->api_endpoint);

        $response = wp_remote_get($url, ['timeout' => 10]);
        if (is_wp_error($response)) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        if (empty($data) || empty($data['deals'])) {
            return [];
        }

        set_transient($this->transient_key, $data['deals'], 3600); // Cache 1 hour
        return $data['deals'];
    }

    // Shortcode render
    public function shortcode_render($atts) {
        $deals = $this->get_deals();
        if (empty($deals)) {
            return '<p>No deals found. Please configure your API settings.</p>';
        }

        $output = '<ul class="aad-deals-list" style="list-style:none;padding:0;">';
        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $url = esc_url($deal['url']);
            $desc = esc_html($deal['description'] ?? '');
            $code = esc_html($deal['coupon_code'] ?? '');

            $output .= '<li style="margin-bottom:15px;border-bottom:1px solid #ccc;padding-bottom:10px;">';
            $output .= '<a href="' . $url . '" target="_blank" rel="nofollow noopener">' . $title . '</a>';
            if ($code) {
                $output .= ' <strong>Coupon: </strong><code>' . $code . '</code>';
            }
            if ($desc) {
                $output .= '<p style="margin:5px 0 0 0;font-size:0.9em;color:#555;">' . $desc . '</p>';
            }
            $output .= '</li>';
        }
        $output .= '</ul>';
        return $output;
    }
}

new AutoAffiliateDeals();
