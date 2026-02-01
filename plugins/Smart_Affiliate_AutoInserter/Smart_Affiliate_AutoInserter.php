/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress posts and pages to boost revenue with AI-powered context matching.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_tag;
    private $api_key;
    private $enabled_posts;
    private $enabled_pages;
    private $max_links;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
        $this->api_key = get_option('saa_api_key', '');
        $this->enabled_posts = get_option('saa_enabled_posts', 1);
        $this->enabled_pages = get_option('saa_enabled_pages', 0);
        $this->max_links = get_option('saa_max_links', 3);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('saa_settings', 'saa_affiliate_tag');
        register_setting('saa_settings', 'saa_api_key');
        register_setting('saa_settings', 'saa_enabled_posts');
        register_setting('saa_settings', 'saa_enabled_pages');
        register_setting('saa_settings', 'saa_max_links');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <?php do_settings_sections('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="saa_affiliate_tag" value="<?php echo esc_attr($this->affiliate_tag); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Amazon API Key (optional for pro)</th>
                        <td><input type="text" name="saa_api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Enable on Posts</th>
                        <td><input type="checkbox" name="saa_enabled_posts" value="1" <?php checked(1, $this->enabled_posts); ?> /></td>
                    </tr>
                    <tr>
                        <th>Enable on Pages</th>
                        <td><input type="checkbox" name="saa_enabled_pages" value="1" <?php checked(1, $this->enabled_pages); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="saa_max_links" value="<?php echo esc_attr($this->max_links); ?>" min="1" max="10" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI-powered product matching, analytics, and unlimited sites for $49/year. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (!is_single() && !is_page()) return $content;
        global $post;
        if (is_page() && !$this->enabled_pages) return $content;
        if (is_single() && !$this->enabled_posts) return $content;
        if (empty($this->affiliate_tag)) return $content;

        // Simple keyword-based matching (Pro: AI enhanced)
        $keywords = $this->extract_keywords($post->post_content);
        $links = array();
        foreach ($keywords as $keyword) {
            if (count($links) >= $this->max_links) break;
            $product_link = $this->get_amazon_link($keyword);
            if ($product_link) {
                $links[] = $product_link;
            }
        }

        if (!empty($links)) {
            $insert_html = '<p>' . implode(' | ', $links) . '</p>';
            $content .= $insert_html;
        }

        return $content;
    }

    private function extract_keywords($content) {
        $words = explode(' ', strip_tags($content));
        $keywords = array();
        foreach ($words as $word) {
            $word = trim(strtolower($word), ".,!?;:'\"()[]");
            if (strlen($word) > 4 && substr_count($content, $word) > 1) {
                $keywords[] = $word;
            }
        }
        return array_slice(array_unique($keywords), 0, 5);
    }

    private function get_amazon_link($keyword) {
        // Mock Amazon search link (Pro: Real API integration)
        $search_term = urlencode($keyword);
        $link = "https://www.amazon.com/s?k={$search_term}&tag={$this->affiliate_tag}";
        return "<a href=\"{$link}\" target=\"_blank\" rel=\"nofollow noopener\">Check out {$keyword} on Amazon &#x1F6D2;</a>";
    }

    public function enqueue_scripts() {
        wp_enqueue_style('saa-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function activate() {
        add_option('saa_enabled_posts', 1);
        add_option('saa_max_links', 3);
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new SmartAffiliateAutoInserter();

// Pro upsell notice
function saa_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter:</strong> Upgrade to Pro for AI product matching and analytics! <a href="' . admin_url('options-general.php?page=smart-affiliate-autoinserter') . '">Settings</a> | <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
}
add_action('admin_notices', 'saa_pro_notice');

?>