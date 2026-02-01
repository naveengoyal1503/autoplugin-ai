/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching to boost affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_id = '';
    private $keywords = [];
    private $products = [];

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_filter('the_content', [$this, 'insert_affiliate_links'], 99);
        add_filter('pro_version_notice', [$this, 'pro_notice']);
    }

    public function init() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->keywords = get_option('saa_keywords', ['laptop', 'phone', 'book', 'headphones']);
        $this->products = get_option('saa_products', [
            'laptop' => 'https://amazon.com/dp/B08N5WRWNW?tag=YOURAFFID',
            'phone' => 'https://amazon.com/dp/B0B3GNYN7M?tag=YOURAFFID',
            'book' => 'https://amazon.com/dp/0596008275?tag=YOURAFFID',
            'headphones' => 'https://amazon.com/dp/B08PZD4T2Q?tag=YOURAFFID'
        ]);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('saa-style', plugin_dir_url(__FILE__) . 'style.css', [], '1.0.0');
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'smart-affiliate', [$this, 'settings_page']);
    }

    public function admin_init() {
        register_setting('saa_settings', 'saa_affiliate_id');
        register_setting('saa_settings', 'saa_keywords');
        register_setting('saa_settings', 'saa_products');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate ID</th>
                        <td><input type="text" name="saa_affiliate_id" value="<?php echo esc_attr($this->affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (comma-separated)</th>
                        <td><input type="text" name="saa_keywords" value="<?php echo esc_attr(implode(',', $this->keywords)); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Products (JSON: {"keyword":"link"})</th>
                        <td><textarea name="saa_products" rows="10" cols="50"><?php echo esc_textarea(json_encode($this->products)); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Version:</strong> Unlimited links, A/B testing, analytics. <a href="#" onclick="alert('Upgrade to Pro for advanced features!')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->affiliate_id)) return $content;

        global $post;
        if (!$post || in_array($post->post_status, ['auto-draft', 'draft'])) return $content;

        $word_count = str_word_count(strip_tags($content));
        if ($word_count < 100) return $content; // Only insert in longer content

        $inserted = 0;
        $max_free = 2; // Free limit

        foreach ($this->keywords as $keyword) {
            if ($inserted >= $max_free) break;

            if (stripos($content, $keyword) !== false) {
                if (isset($this->products[$keyword])) {
                    $link = $this->products[$keyword];
                    $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored" class="saa-aff-link">' . esc_html(ucfirst($keyword)) . '</a>';
                    $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $replacement, $content, 1);
                    $inserted++;
                }
            }
        }

        return $content;
    }

    public function pro_notice() {
        if (!current_user_can('manage_options')) return;
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoInserter Pro</strong> for unlimited links and more features!</p></div>';
    }
}

new SmartAffiliateAutoInserter();

// Add CSS
add_action('wp_head', function() {
    echo '<style>.saa-aff-link { color: #ff9900; font-weight: bold; text-decoration: none; } .saa-aff-link:hover { text-decoration: underline; }</style>';
});

// Pro upsell notice
add_action('admin_notices', function() {
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_smart-affiliate') return;
    global $SmartAffiliateAutoInserter;
    $SmartAffiliateAutoInserter->pro_notice();
});