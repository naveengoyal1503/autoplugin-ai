/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress posts and pages to boost commissions effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_tag;
    private $keywords;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
        $this->keywords = get_option('saa_keywords', array('laptop', 'phone', 'book'));
    }

    public function enqueue_assets() {
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (empty($this->affiliate_tag) || !is_single() || is_admin()) {
            return $content;
        }

        foreach ($this->keywords as $keyword) {
            $keyword = esc_attr($keyword);
            $search = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($search, $content)) {
                $product_url = 'https://www.amazon.com/s?k=' . urlencode($keyword) . '&tag=' . $this->affiliate_tag;
                $link = '<a href="' . esc_url($product_url) . '" target="_blank" rel="nofollow sponsored">Check ' . ucfirst($keyword) . ' on Amazon</a> ';
                $content = preg_replace($search, $keyword . ' ' . $link, $content, 1);
            }
        }
        return $content;
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('saa_plugin', 'saa_affiliate_tag');
        register_setting('saa_plugin', 'saa_keywords');

        add_settings_section(
            'saa_section',
            'Affiliate Settings',
            null,
            'smart-affiliate-autoinserter'
        );

        add_settings_field(
            'saa_affiliate_tag',
            'Amazon Affiliate Tag',
            array($this, 'affiliate_tag_render'),
            'smart-affiliate-autoinserter',
            'saa_section'
        );

        add_settings_field(
            'saa_keywords',
            'Keywords (comma-separated)',
            array($this, 'keywords_render'),
            'smart-affiliate-autoinserter',
            'saa_section'
        );
    }

    public function affiliate_tag_render() {
        $options = get_option('saa_affiliate_tag');
        ?>
        <input type="text" name="saa_affiliate_tag" value="<?php echo esc_attr($options); ?>" />
        <?php
    }

    public function keywords_render() {
        $options = get_option('saa_keywords', 'laptop,phone,book');
        ?>
        <textarea name="saa_keywords" rows="4" cols="50"><?php echo esc_textarea($options); ?></textarea>
        <p class="description">Enter keywords separated by commas. Links will be inserted near matching words.</p>
        <?php
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('saa_plugin');
                do_settings_sections('smart-affiliate-autoinserter');
                submit_button();
                ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock advanced targeting, analytics, custom link positions, and more! <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('saa_keywords', 'laptop,phone,book');
    }

    public function deactivate() {
        // Cleanup optional
    }
}

new SmartAffiliateAutoInserter();

// Pro teaser notice
function saa_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoInserter Pro</strong> for analytics and more features!</p></div>';
}
add_action('admin_notices', 'saa_pro_notice');