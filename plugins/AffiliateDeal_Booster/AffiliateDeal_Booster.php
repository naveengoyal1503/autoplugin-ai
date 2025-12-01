/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateDeal_Booster.php
*/
<?php
/**
 * Plugin Name: AffiliateDeal Booster
 * Description: Auto-aggregate and display high-converting affiliate coupons with tracking and analytics.
 * Version: 1.0
 * Author: YourName
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    private $option_name = 'adb_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_deals', array($this, 'display_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_adb_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_adb_track_click', array($this, 'track_click'));
    }

    public function add_admin_menu() {
        add_options_page('AffiliateDeal Booster Settings', 'AffiliateDeal Booster', 'manage_options', 'affiliate_deal_booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('adbSettings', $this->option_name);

        add_settings_section(
            'adb_section_main',
            __('General Settings', 'adb'),
            function() { echo '<p>' . __('Configure your affiliate deal sources and display options.', 'adb') . '</p>'; },
            'adbSettings'
        );

        add_settings_field(
            'api_key',
            __('Affiliate Coupon API Key', 'adb'),
            array($this, 'api_key_render'),
            'adbSettings',
            'adb_section_main'
        );

        add_settings_field(
            'num_deals',
            __('Number of Deals to Show', 'adb'),
            array($this, 'num_deals_render'),
            'adbSettings',
            'adb_section_main'
        );
    }

    public function api_key_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='text' name='<?php echo $this->option_name; ?>[api_key]' value='<?php echo isset($options['api_key']) ? esc_attr($options['api_key']) : ''; ?>' size='50'>
        <p class='description'>Enter your API key to fetch affiliate coupons from supported networks.</p>
        <?php
    }

    public function num_deals_render() {
        $options = get_option($this->option_name);
        ?>
        <input type='number' name='<?php echo $this->option_name; ?>[num_deals]' value='<?php echo isset($options['num_deals']) ? intval($options['num_deals']) : 5; ?>' min='1' max='20'>
        <p class='description'>How many deals to display in shortcode output.</p>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>AffiliateDeal Booster Settings</h2>
            <?php
            settings_fields('adbSettings');
            do_settings_sections('adbSettings');
            submit_button();
            ?>
        </form>
        <?php
    }

    // Fetch deals from a mock API or static array for demo purposes
    private function get_deals() {
        $options = get_option($this->option_name);
        $num = isset($options['num_deals']) ? intval($options['num_deals']) : 5;

        // In real use, this would call an external API using the api_key to get current coupons
        // Here we return dummy deals for demonstration
        $deals = array(
            array('title' => '20% off WidgetPro', 'url' => 'https://example.com/widgetpro?aff=123', 'description' => 'Save 20% on WidgetPro with this coupon.'),
            array('title' => 'Buy One Get One Free GadgetX', 'url' => 'https://example.com/gadgetx?aff=123', 'description' => 'BOGO offer on GadgetX, limited time.'),
            array('title' => '15% Savings on All Gizmos', 'url' => 'https://example.com/gizmos?aff=123', 'description' => '15% off sitewide for Gizmos.'),
            array('title' => '$10 off Your First Order at ShopABC', 'url' => 'https://example.com/shopabc?aff=123', 'description' => 'Welcome discount for new customers.'),
            array('title' => 'Free Shipping on Orders Over $50', 'url' => 'https://example.com/freeship?aff=123', 'description' => 'Free delivery when you spend $50 or more.')
        );

        return array_slice($deals, 0, $num);
    }

    public function display_deals_shortcode() {
        $deals = $this->get_deals();
        if (empty($deals)) return '<p>No deals available at the moment.</p>';

        $output = '<ul class="adb-deal-list">';
        foreach ($deals as $index => $deal) {
            $url_escaped = esc_url($deal['url']);
            $title_escaped = esc_html($deal['title']);
            $desc_escaped = esc_html($deal['description']);

            // Track clicks via AJAX
            $track_url = admin_url('admin-ajax.php?action=adb_track_click&deal='.urlencode($url_escaped));

            $output .= "<li><a href='$url_escaped' target='_blank' rel='nofollow noopener noreferrer' class='adb-deal-link' data-track-url='$track_url'>$title_escaped</a><br/><small>$desc_escaped</small></li>";
        }
        $output .= '</ul>';
        return $output;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('adb_script', plugin_dir_url(__FILE__) . 'adb-script.js', array('jquery'), '1.0', true);

        // Inline JS to send click tracking
        $inline_script = "
        jQuery(document).ready(function($) {
            $('body').on('click', '.adb-deal-link', function(e) {
                var trackUrl = $(this).data('track-url');
                $.get(trackUrl);
            });
        });
        ";
        wp_add_inline_script('adb_script', $inline_script);
    }

    public function track_click() {
        $deal = isset($_GET['deal']) ? sanitize_text_field($_GET['deal']) : '';

        if ($deal) {
            $clicks = get_option('adb_clicks', array());
            if (!isset($clicks[$deal])) $clicks[$deal] = 0;
            $clicks[$deal]++;
            update_option('adb_clicks', $clicks);
        }

        wp_send_json_success();
    }
}

new AffiliateDealBooster();