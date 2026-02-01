/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress content to maximize affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_tag;
    private $keywords;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_settings', array($this, 'save_settings'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }

    public function init() {
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
        $this->keywords = get_option('saa_keywords', array());
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

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page') return;
        wp_enqueue_script('saa-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('saa_affiliate_tag', sanitize_text_field($_POST['affiliate_tag']));
            update_option('saa_keywords', array_map('sanitize_text_field', $_POST['keywords']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $tag = get_option('saa_affiliate_tag', '');
        $keywords = get_option('saa_keywords', array());
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="affiliate_tag" value="<?php echo esc_attr($tag); ?>" class="regular-text" placeholder="yourtag-20"></td>
                    </tr>
                    <tr>
                        <th>Keywords & Products (JSON format)</th>
                        <td>
                            <textarea name="keywords" rows="10" cols="50" class="large-text" placeholder='[{"keyword":"laptop","url":"https://amazon.com/dp/B08N5WRWNW"}]'><?php echo esc_textarea(json_encode($keywords)); ?></textarea>
                            <p class="description">Enter JSON array: [{"keyword":"laptop","url":"amazon-product-url","text":"Buy on Amazon"}]</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock A/B testing, click tracking, and more for $49/year!</p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || empty($this->affiliate_tag) || empty($this->keywords)) {
            return $content;
        }

        $words = explode(' ', $content);
        $inserted = 0;
        $max_inserts = 3;

        foreach ($words as $index => &$word) {
            if ($inserted >= $max_inserts) break;

            foreach ($this->keywords as $item) {
                if (stripos($word, $item['keyword']) !== false) {
                    $link = sprintf(
                        '<a href="%s?tag=%s" target="_blank" rel="nofollow sponsored">%s</a>',
                        esc_url($item['url']),
                        esc_attr($this->affiliate_tag),
                        esc_html($item['text'] ?? 'Buy on Amazon')
                    );
                    $word = $link . ' ' . $word;
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $words);
    }
}

new SmartAffiliateAutoInserter();

// Pro teaser
add_action('admin_notices', function() {
    if (!get_option('saa_pro_activated')) {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter Pro:</strong> Upgrade for analytics & more! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
});