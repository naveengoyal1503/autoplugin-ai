/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Coupon_Aggregator.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Coupon Aggregator
 * Description: Automatically aggregate affiliate coupons from multiple sources, customize display, and increase affiliate conversions.
 * Version: 1.0
 * Author: Plugin Generator
 */

// Prevent direct access
if (!defined('ABSPATH')) { exit; }

class SmartAffiliateCouponAggregator {

    private $coupon_data_option = 'saca_coupon_data';

    public function __construct() {
        add_shortcode('saca_coupons', array($this, 'render_coupons_shortcode'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_saca_refresh_coupons', array($this, 'ajax_refresh_coupons'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('saca_style', plugin_dir_url(__FILE__) . 'style.css');
        // Simple JS could be added if needed
    }

    // Admin menu for plugin settings
    public function add_admin_menu() {
        add_options_page('SACA Settings', 'SACA Coupons', 'manage_options', 'saca-settings', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('saca_settings_group', 'saca_coupon_sources');
        register_setting('saca_settings_group', $this->coupon_data_option);
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Coupon Aggregator Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saca_settings_group'); ?>
                <?php do_settings_sections('saca_settings_group'); ?>
                <h2>Coupon Sources (RSS feed URLs, one per line)</h2>
                <textarea name="saca_coupon_sources" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('saca_coupon_sources')); ?></textarea>
                <?php submit_button(); ?>
            </form>
            <form method="post" id="refresh_coupons_form">
                <input type="hidden" name="action" value="saca_refresh_coupons" />
                <?php wp_nonce_field('saca_refresh_coupons_nonce', '_saca_nonce'); ?>
                <p><button type="submit" class="button button-primary">Refresh Coupons Now</button></p>
            </form>
            <p><em>Refresh coupons fetches and updates all coupons from your configured sources.</em></p>
        </div>
        <?php
    }

    public function ajax_refresh_coupons() {
        if (!current_user_can('manage_options') || !check_admin_referer('saca_refresh_coupons_nonce', '_saca_nonce')) {
            wp_send_json_error('Unauthorized');
        }

        $result = $this->fetch_and_store_coupons();

        if ($result === false) {
            wp_send_json_error('Failed to fetch coupons');
        } else {
            wp_send_json_success('Coupons updated: ' . count($result));
        }
    }

    private function fetch_and_store_coupons() {
        $sources = get_option('saca_coupon_sources', '');
        $urls = array_filter(array_map('trim', explode("\n", $sources)));

        if (empty($urls)) {
            return false;
        }

        $coupons = array();

        foreach ($urls as $url) {
            $feed = fetch_feed($url);
            if (is_wp_error($feed)) {
                continue;
            }

            $max_items = $feed->get_item_quantity(10);
            $feed_items = $feed->get_items(0, $max_items);

            foreach ($feed_items as $item) {
                $title = $item->get_title();
                $link = $item->get_permalink();
                $desc = strip_tags($item->get_description());

                // Basic coupon data extraction
                $coupon_code = $this->extract_coupon_code($title . ' ' . $desc);

                $coupons[] = array(
                    'title' => $title,
                    'link' => esc_url($link),
                    'code' => $coupon_code,
                    'description' => $desc,
                );
            }
        }

        // Store coupons transient for 12 hours for free/basic usage
        update_option($this->coupon_data_option, $coupons);

        return $coupons;
    }

    private function extract_coupon_code($text) {
        // Naive coupon code extraction: looks for uppercase alphanumeric strings 5-15 chars
        preg_match('/([A-Z0-9]{5,15})/', $text, $matches);
        return isset($matches[1]) ? $matches[1] : '';
    }

    public function render_coupons_shortcode($atts) {
        $atts = shortcode_atts(array(
            'max' => 5,
        ), $atts, 'saca_coupons');

        $coupons = get_option($this->coupon_data_option, array());

        if (empty($coupons)) {
            // Try to fetch fresh coupons if none
            $coupons = $this->fetch_and_store_coupons();
            if (empty($coupons)) {
                return '<p>No coupons available right now. Please check back later.</p>';
            }
        }

        $coupons = array_slice($coupons, 0, intval($atts['max']));

        ob_start();
        echo '<div class="saca-coupon-list">';
        foreach ($coupons as $coupon) {
            $esc_title = esc_html($coupon['title']);
            $esc_code = esc_html($coupon['code']);
            $esc_desc = esc_html($coupon['description']);
            $esc_link = esc_url($coupon['link']);

            echo '<div class="saca-coupon-item" style="border:1px solid #ccc;margin:8px;padding:8px;border-radius:4px;">';
            echo '<h4><a href="' . $esc_link . '" target="_blank" rel="nofollow noopener">' . $esc_title . '</a></h4>';

            if ($esc_code) {
                echo '<p><strong>Use Code:</strong> <code>' . $esc_code . '</code></p>';
            }

            echo '<p>' . $esc_desc . '</p>';

            echo '<p><a href="' . $esc_link . '" target="_blank" rel="nofollow noopener" class="button">Get Deal</a></p>';
            echo '</div>';
        }
        echo '</div>';
        return ob_get_clean();
    }

}

// Initialize plugin
new SmartAffiliateCouponAggregator();