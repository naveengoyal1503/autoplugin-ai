/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Display exclusive affiliate coupon deals with automatic fetching, smart targeting, and user engagement to increase clicks and conversions.
 * Version: 1.0
 * Author: Generated Plugin
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    private $option_name = 'adb_settings';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('adb_deals', [$this, 'display_deals_shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('init', [$this, 'handle_ajax_click']);
    }

    public function admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'affiliate-deal-booster', [$this, 'admin_page'], 'dashicons-megaphone');
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name, [$this, 'validate_settings']);

        add_settings_section('adb_main_section', 'Main Settings', null, $this->option_name);

        add_settings_field('affiliate_id', 'Affiliate ID or Key', [$this, 'affiliate_id_field'], $this->option_name, 'adb_main_section');
        add_settings_field('coupon_feed_url', 'Coupon Feed URL', [$this, 'coupon_feed_url_field'], $this->option_name, 'adb_main_section');
        add_settings_field('deal_display_limit', 'Max Deals to Display', [$this, 'deal_display_limit_field'], $this->option_name, 'adb_main_section');
    }

    public function affiliate_id_field() {
        $options = get_option($this->option_name);
        $val = isset($options['affiliate_id']) ? esc_attr($options['affiliate_id']) : '';
        echo "<input type='text' name='{$this->option_name}[affiliate_id]' value='$val' class='regular-text' />";
    }

    public function coupon_feed_url_field() {
        $options = get_option($this->option_name);
        $val = isset($options['coupon_feed_url']) ? esc_url($options['coupon_feed_url']) : '';
        echo "<input type='url' name='{$this->option_name}[coupon_feed_url]' value='$val' class='regular-text' />";
        echo "<p class='description'>Enter the URL of your affiliate coupon XML or JSON feed.</p>";
    }

    public function deal_display_limit_field() {
        $options = get_option($this->option_name);
        $val = isset($options['deal_display_limit']) ? intval($options['deal_display_limit']) : 5;
        echo "<input type='number' min='1' max='20' name='{$this->option_name}[deal_display_limit]' value='$val' />";
    }

    public function validate_settings($input) {
        $output = [];
        $output['affiliate_id'] = sanitize_text_field($input['affiliate_id']);
        $output['coupon_feed_url'] = esc_url_raw($input['coupon_feed_url']);
        $output['deal_display_limit'] = max(1, intval($input['deal_display_limit']));
        return $output;
    }

    public function admin_page() {
        ?>
        <div class='wrap'>
            <h1>Affiliate Deal Booster Settings</h1>
            <form method='post' action='options.php'>
                <?php
                settings_fields($this->option_name);
                do_settings_sections($this->option_name);
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use shortcode <code>[adb_deals]</code> in any post or page to display affiliate deals.</p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adb-style', plugins_url('/style.css', __FILE__));
        wp_enqueue_script('adb-script', plugins_url('/script.js', __FILE__), ['jquery'], false, true);

        wp_localize_script('adb-script', 'adb_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('adb_click_nonce')
        ]);
    }

    // Fetch deals from feed cached in transient
    private function get_deals() {
        $options = get_option($this->option_name);
        $feed_url = isset($options['coupon_feed_url']) ? $options['coupon_feed_url'] : '';
        $limit = isset($options['deal_display_limit']) ? intval($options['deal_display_limit']) : 5;

        if (!$feed_url) return [];

        $cache_key = 'adb_deals_cache';
        $cached = get_transient($cache_key);
        if ($cached !== false) return $cached;

        // For demo: simple JSON feed fetch and parse
        $response = wp_remote_get($feed_url, ['timeout' => 5]);
        if (is_wp_error($response)) return [];
        $body = wp_remote_retrieve_body($response);
        $deals = json_decode($body, true);
        if (!is_array($deals)) return [];

        $deals = array_slice($deals, 0, $limit);
        set_transient($cache_key, $deals, 3600); // cache 1 hour

        return $deals;
    }

    public function display_deals_shortcode() {
        $deals = $this->get_deals();
        if (empty($deals)) return '<p>No deals available currently.</p>';

        ob_start();
        echo '<div class="adb-deals">';
        foreach ($deals as $deal) {
            $title = esc_html($deal['title'] ?? 'Deal');
            $link = esc_url($deal['url'] ?? '#');
            $coupon = esc_html($deal['coupon'] ?? '');
            $desc = esc_html($deal['description'] ?? '');
            echo "<div class='adb-deal-item'>";
            echo "<h3>$title</h3>";
            if($coupon) echo "<p><strong>Coupon: $coupon</strong></p>";
            if($desc) echo "<p>$desc</p>";
            echo "<a href='$link' class='adb-deal-link' data-url='$link' target='_blank' rel='nofollow noopener'>Grab Deal</a>";
            echo "</div>";
        }
        echo '</div>';
        return ob_get_clean();
    }

    // Handle AJAX click tracking (basic example)
    public function handle_ajax_click() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'adb_track_click') return;

        check_ajax_referer('adb_click_nonce', 'nonce');

        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';

        if ($url) {
            // Record click logic here (e.g., increment meta or write to log)
            // For demo, just respond success
            wp_send_json_success(['message' => 'Click recorded']);
        } else {
            wp_send_json_error(['message' => 'Invalid URL']);
        }
        wp_die();
    }
}

new AffiliateDealBooster();

// Minimal CSS and JS can be inline or added as separate files in real usage
