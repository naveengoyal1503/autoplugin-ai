/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Automatically discovers and shows affiliate coupons and deals related to your posts to boost affiliate conversions.
 * Version: 1.0
 * Author: Perplexity AI
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'append_affiliate_deals'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'css/adb-style.css');
    }

    // Append deals below post content
    public function append_affiliate_deals($content) {
        if (!is_single() || is_admin()) return $content;

        $post_keywords = $this->get_keywords(get_the_title());
        $deals_html = $this->get_deals_html($post_keywords);

        return $content . $deals_html;
    }

    // Simple keyword extractor from title
    private function get_keywords($text) {
        $words = preg_split('/\W+/', strtolower($text));
        $common_words = array('the', 'and', 'with', 'for', 'from', 'that', 'this', 'your', 'you');
        $keywords = array_diff($words, $common_words);
        return array_slice($keywords, 0, 5);
    }

    // Retrieve dummy deals relevant to keywords
    private function get_deals_html($keywords) {
        // In real plugin, integrate with affiliate APIs or coupon APIs here
        $dummy_deals = array(
            array('title' => 'Save 20% on Product A', 'url' => 'https://affiliate.example.com/product-a?ref=123'),
            array('title' => 'Exclusive 15% Off on Product B', 'url' => 'https://affiliate.example.com/product-b?ref=123'),
            array('title' => 'Buy One Get One Free on Product C', 'url' => 'https://affiliate.example.com/product-c?ref=123'),
        );

        // Filter deals whose title matches any keyword
        $matched_deals = array();
        foreach ($dummy_deals as $deal) {
            foreach ($keywords as $kw) {
                if (stripos($deal['title'], $kw) !== false) {
                    $matched_deals[] = $deal;
                    break;
                }
            }
        }

        if (empty($matched_deals)) {
            $matched_deals = array_slice($dummy_deals, 0, 2); // fallback deals
        }

        $html = '<div class="adb-deals-container"><h3>Exclusive Deals & Coupons</h3><ul>';
        foreach ($matched_deals as $deal) {
            $html .= '<li><a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener">' . esc_html($deal['title']) . '</a></li>';
        }
        $html .= '</ul></div>';
        return $html;
    }

    // Admin menu
    public function admin_menu() {
        add_options_page('Affiliate Deal Booster Settings', 'Affiliate Deal Booster', 'manage_options', 'affiliate-deal-booster', array($this, 'settings_page'));
    }

    public function settings_init() {
        register_setting('adb_settings', 'adb_settings_options');

        add_settings_section('adb_settings_section', 'General Settings', null, 'affiliate-deal-booster');

        add_settings_field('adb_affiliate_id', 'Affiliate ID', array($this, 'affiliate_id_render'), 'affiliate-deal-booster', 'adb_settings_section');
    }

    public function affiliate_id_render() {
        $options = get_option('adb_settings_options');
        ?>
        <input type='text' name='adb_settings_options[affiliate_id]' value='<?php echo isset($options['affiliate_id']) ? esc_attr($options['affiliate_id']) : ''; ?>' placeholder='Your affiliate ID'/>
        <p class='description'>Insert your affiliate ID to track referrals</p>
        <?php
    }

    public function settings_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Deal Booster Settings</h2>
            <?php
            settings_fields('adb_settings');
            do_settings_sections('affiliate-deal-booster');
            submit_button();
            ?>
        </form>
        <?php
    }

}

new AffiliateDealBooster();

// Minimal inline CSS for deal box
add_action('wp_head', function() {
    echo '<style>.adb-deals-container{background:#f9f9f9;border:1px solid #ddd;padding:15px;margin-top:25px;border-radius:5px;}.adb-deals-container h3{margin-top:0;color:#333;}.adb-deals-container ul{list-style-type:square;margin-left:20px;}.adb-deals-container ul li{margin-bottom:8px;}</style>';
});
