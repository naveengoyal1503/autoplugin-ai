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
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $affiliate_id;
    private $keywords;
    private $products;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_filter('widget_text', array($this, 'insert_affiliate_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->keywords = get_option('saa_keywords', array());
        $this->products = get_option('saa_products', array());
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'saa-script.js', array('jquery'), '1.0.0', true);
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('saa_plugin_page', 'saa_affiliate_id');
        register_setting('saa_plugin_page', 'saa_keywords');
        register_setting('saa_plugin_page', 'saa_products');

        add_settings_section(
            'saa_plugin_page_section',
            __('Main Settings', 'smart-affiliate-autoinserter'),
            array($this, 'settings_section_callback'),
            'saa_plugin_page'
        );

        add_settings_field(
            'saa_affiliate_id',
            __('Amazon Affiliate ID (tag)', 'smart-affiliate-autoinserter'),
            array($this, 'affiliate_id_render'),
            'saa_plugin_page',
            'saa_plugin_page_section'
        );

        add_settings_field(
            'saa_keywords',
            __('Keywords and Products (JSON format: {"keyword":"product_url"})', 'smart-affiliate-autoinserter'),
            array($this, 'keywords_render'),
            'saa_plugin_page',
            'saa_plugin_page_section'
        );
    }

    public function settings_section_callback() {
        echo __('Enter your settings below to start auto-inserting affiliate links.', 'smart-affiliate-autoinserter');
    }

    public function affiliate_id_render() {
        $options = get_option('saa_affiliate_id');
        ?><input type='text' name='saa_affiliate_id' value='<?php echo esc_attr($options); ?>' size='50' /><p class="description"><?php _e('Your Amazon Associates tag, e.g., yourid-20', 'smart-affiliate-autoinserter'); ?></p><?php
    }

    public function keywords_render() {
        $options = get_option('saa_keywords', '{}');
        ?><textarea name='saa_keywords' rows='10' cols='50'><?php echo esc_textarea($options); ?></textarea><p class="description"><?php _e('JSON object like: {"laptop":"https://amazon.com/dp/B08N5WRWNW","phone":"https://amazon.com/dp/B0BTM1NWFY"}', 'smart-affiliate-autoinserter'); ?></p><?php
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('saa_plugin_page');
                do_settings_sections('saa_plugin_page');
                submit_button();
                ?>
            </form>
            <?php $this->stats_display(); ?>
        </div><?php
    }

    public function stats_display() {
        $clicks = get_option('saa_clicks', 0);
        echo "<h2>Stats (Free Version)</h2><p>Total Clicks: " . intval($clicks) . "</p>";
        echo "<p><strong>Upgrade to Pro for detailed analytics, A/B testing, and unlimited links!</strong></p>";
    }

    public function insert_affiliate_links($content) {
        if (empty($this->affiliate_id) || empty($this->keywords) || is_admin() || is_feed()) {
            return $content;
        }

        $keywords = json_decode($this->keywords, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($keywords)) {
            return $content;
        }

        $free_limit = 3; // Free version limit
        $inserted = 0;

        foreach ($keywords as $keyword => $product_url) {
            if ($inserted >= $free_limit) break;

            $link_html = $this->create_affiliate_link($keyword, $product_url);
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link_html, $content, 1, $count);
            if ($count > 0) $inserted++;
        }

        return $content;
    }

    private function create_affiliate_link($keyword, $product_url) {
        $link = "<a href=\"{$product_url}?tag={$this->affiliate_id}\" target=\"_blank\" rel=\"nofollow sponsored\" class=\"saa-aff-link\" onclick=\"saaTrackClick();\">{$keyword}</a>";
        return $link;
    }

    public function activate() {
        add_option('saa_clicks', 0);
    }

    public function deactivate() {
        // No-op
    }
}

new SmartAffiliateAutoInserter();

// Track clicks
add_action('wp_ajax_saa_track_click', 'saa_track_click');
add_action('wp_ajax_nopriv_saa_track_click', 'saa_track_click');
function saa_track_click() {
    $clicks = get_option('saa_clicks', 0) + 1;
    update_option('saa_clicks', $clicks);
    wp_die();
}

// Inline JS for tracking
function saa_inline_js() {
    ?>
    <script>
    function saaTrackClick() {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=saa_track_click'
        });
    }
    </script><?php
}
add_action('wp_footer', 'saa_inline_js');