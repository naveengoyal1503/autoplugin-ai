/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your posts and converts them into affiliate links from Amazon, boosting commissions effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoLinker {
    private $keywords = [];
    private $affiliate_id = '';

    public function __construct() {
        add_action('init', [$this, 'init});
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_filter('the_content', [$this, 'auto_link_affiliates']);
        add_filter('widget_text', [$this, 'auto_link_affiliates']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        $this->affiliate_id = get_option('saal_amazon_affiliate_id', 'your-affiliate-id');
        $this->keywords = get_option('saal_keywords', [
            'iphone' => 'https://amazon.com/dp/B0ABC123?tag=your-affiliate-id',
            'laptop' => 'https://amazon.com/dp/B0DEF456?tag=your-affiliate-id',
        ]);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saal-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoLinker', 'Affiliate AutoLinker', 'manage_options', 'saal-settings', [$this, 'settings_page']);
    }

    public function admin_init() {
        register_setting('saal_settings', 'saal_amazon_affiliate_id');
        register_setting('saal_settings', 'saal_keywords');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saal_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate ID</th>
                        <td><input type="text" name="saal_amazon_affiliate_id" value="<?php echo esc_attr(get_option('saal_amazon_affiliate_id')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (JSON format: {"keyword":"url"})</th>
                        <td><textarea name="saal_keywords" rows="10" cols="50"><?php echo esc_textarea(wp_json_encode(get_option('saal_keywords', []))); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited keywords, analytics, and more for $49/year!</p>
        </div>
        <?php
    }

    public function auto_link_affiliates($content) {
        if (is_admin() || !is_singular() || empty($this->keywords)) {
            return $content;
        }
        global $post;
        if (get_post_meta($post->ID, '_saal_disable', true)) {
            return $content;
        }
        foreach ($this->keywords as $keyword => $url) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $link = '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">' . esc_html($keyword) . '</a>';
            $content = preg_replace($pattern, $link, $content, 1);
        }
        return $content;
    }

    public function activate() {
        add_option('saal_amazon_affiliate_id', 'your-affiliate-id');
        add_option('saal_keywords', [
            'iphone' => 'https://amazon.com/dp/B0ABC123?tag=your-affiliate-id',
            'laptop' => 'https://amazon.com/dp/B0DEF456?tag=your-affiliate-id',
        ]);
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

new SmartAffiliateAutoLinker();

// Pro nag
function saal_pro_nag() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoLinker Pro</strong> for advanced features! <a href="https://example.com/pro" target="_blank">Get Pro</a></p></div>';
}
add_action('admin_notices', 'saal_pro_nag');

// Create assets dir placeholder (in real plugin, include JS file)
if (!file_exists(plugin_dir_path(__FILE__) . 'assets')) {
    wp_mkdir_p(plugin_dir_path(__FILE__) . 'assets');
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/script.js', '// Pro analytics script');
}
?>