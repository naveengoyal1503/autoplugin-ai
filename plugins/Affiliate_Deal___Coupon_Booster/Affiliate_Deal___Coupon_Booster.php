<?php
/*
Plugin Name: Affiliate Deal & Coupon Booster
Plugin URI: https://example.com/plugin-affiliate-deal-coupon-booster
Description: Automatically aggregates and displays affiliate deals and coupons by niche.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal___Coupon_Booster.php
License: GPL2
Text Domain: affiliate-deal-coupon-booster
*/

if (!defined('ABSPATH')) { exit; }

class ADACB_Plugin {
    private $option_name = 'adacb_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_shortcode('adacb_deals', [$this, 'render_deals_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'adacb_plugin', [$this, 'options_page']);
    }

    public function settings_init() {
        register_setting('adacb_group', $this->option_name);

        add_settings_section(
            'adacb_section',
            __('API Settings & Configuration', 'affiliate-deal-coupon-booster'),
            null,
            'adacb_group'
        );

        add_settings_field(
            'adacb_api_key',
            __('Affiliate API Key', 'affiliate-deal-coupon-booster'),
            [$this, 'render_text_input'],
            'adacb_group',
            'adacb_section',
            ['label_for' => 'adacb_api_key', 'name' => 'api_key']
        );

        add_settings_field(
            'adacb_default_niche',
            __('Default Niche Keyword', 'affiliate-deal-coupon-booster'),
            [$this, 'render_text_input'],
            'adacb_group',
            'adacb_section',
            ['label_for' => 'adacb_default_niche', 'name' => 'default_niche']
        );
    }

    public function render_text_input($args) {
        $options = get_option($this->option_name);
        $name = $args['name'];
        ?>
        <input type="text" id="adacb_<?= esc_attr($name) ?>" name="<?= esc_attr($this->option_name) ?>[<?= esc_attr($name) ?>]" value="<?= isset($options[$name]) ? esc_attr($options[$name]) : '' ?>" class="regular-text">
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Deal & Coupon Booster Settings</h2>
            <?php
            settings_fields('adacb_group');
            do_settings_sections('adacb_group');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adacb_styles', plugin_dir_url(__FILE__) . 'style.css');
    }

    private function fetch_deals($niche) {
        $options = get_option($this->option_name);
        $api_key = isset($options['api_key']) ? trim($options['api_key']) : '';

        if (empty($api_key)) {
            return 'Affiliate API key not set in plugin settings.';
        }

        // Simulated API endpoint (replace with real affiliate deals API)
        $endpoint = 'https://api.example.com/affiliate-deals';
        $args = [
            'timeout' => 10,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key
            ],
            'body' => [
                'niche' => sanitize_text_field($niche),
                'limit' => 5
            ],
            'method' => 'GET'
        ];

        // For demonstration, since no real API exists, we simulate data
        $deals = [
            [
                'title' => 'Super Saver 20% Off Electronics',
                'url' => 'https://affiliate.example.com/deal1',
                'description' => 'Save 20% on all electronics using this exclusive coupon.',
                'expiry' => '2026-01-31'
            ],
            [
                'title' => 'Buy 1 Get 1 Free on Select Apparel',
                'url' => 'https://affiliate.example.com/deal2',
                'description' => 'BOGO offer on popular apparel brands.',
                'expiry' => '2025-12-15'
            ]
        ];

        // Normally you would do below WP HTTP request (commented)
        /*
        $response = wp_remote_get(add_query_arg(['niche' => $niche, 'limit' => 5], $endpoint), [
            'headers' => ['Authorization' => 'Bearer ' . $api_key],
            'timeout' => 10
        ]);
        if (is_wp_error($response)) {
            return 'Error fetching deals: ' . $response->get_error_message();
        }
        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        if ($code != 200) {
            return 'Failed to fetch deals, HTTP status ' . $code;
        }
        $deals = json_decode($body, true);
        */

        return $deals;
    }

    public function render_deals_shortcode($atts) {
        $atts = shortcode_atts(['niche' => ''], $atts, 'adacb_deals');
        $niche = sanitize_text_field($atts['niche']);
        if (empty($niche)) {
            $options = get_option($this->option_name);
            $niche = isset($options['default_niche']) ? sanitize_text_field($options['default_niche']) : 'general';
        }

        $deals = $this->fetch_deals($niche);

        if (is_string($deals)) { // error message
            return '<p><em>' . esc_html($deals) . '</em></p>';
        }

        ob_start();
        ?>
        <div class="adacb-container">
            <h3>Top Deals & Coupons for "<?= esc_html($niche) ?>"</h3>
            <ul class="adacb-list">
                <?php foreach ($deals as $deal): ?>
                    <li class="adacb-item">
                        <a href="<?= esc_url($deal['url']) ?>" target="_blank" rel="nofollow noopener noreferrer" class="adacb-title"><?= esc_html($deal['title']) ?></a>
                        <p class="adacb-desc"><?= esc_html($deal['description']) ?></p>
                        <p class="adacb-expiry">Expires: <?= esc_html($deal['expiry']) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
}

new ADACB_Plugin();