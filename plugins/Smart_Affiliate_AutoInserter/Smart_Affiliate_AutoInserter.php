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
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_affiliate_links', array($this, 'save_affiliate_links'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'api_key' => '',
            'affiliate_tag' => '',
            'keywords' => array(),
            'max_links' => 2,
            'enabled' => true
        ));
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
        wp_enqueue_script('jquery');
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->options['api_key'] = sanitize_text_field($_POST['api_key']);
            $this->options['affiliate_tag'] = sanitize_text_field($_POST['affiliate_tag']);
            $this->options['max_links'] = intval($_POST['max_links']);
            $this->options['enabled'] = isset($_POST['enabled']);
            $keywords = array_map('sanitize_text_field', $_POST['keywords']);
            $this->options['keywords'] = array_filter($keywords);
            update_option('smart_affiliate_options', $this->options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Amazon API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($this->options['api_key']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Tag (e.g., yourtag-20)</th>
                        <td><input type="text" name="affiliate_tag" value="<?php echo esc_attr($this->options['affiliate_tag']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords & Products</th>
                        <td>
                            <p>Add keyword:product ASIN pairs (one per line).</p>
                            <textarea name="keywords" rows="10" cols="50" class="large-text"><?php echo esc_textarea(implode("\n", $this->options['keywords'])); ?></textarea>
                            <p>Example:<br>best laptop: B08N5WRWNW<br>coffee maker: B07H2H8L8Q</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="max_links" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="5" /></td>
                    </tr>
                    <tr>
                        <th>Enable Auto-Insert</th>
                        <td><input type="checkbox" name="enabled" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (!is_single() || !$this->options['enabled'] || empty($this->options['keywords'])) {
            return $content;
        }

        $inserted = 0;
        $max_links = intval($this->options['max_links']);

        foreach ($this->options['keywords'] as $pair) {
            if ($inserted >= $max_links) break;
            list($keyword, $asin) = explode(':', $pair, 2);
            $keyword = trim($keyword);
            $asin = trim($asin);
            if (empty($keyword) || empty($asin)) continue;

            $link = $this->get_amazon_link($asin);
            $regex = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . '</a>';
                $content = preg_replace($regex, $replacement, $content, 1);
                $inserted++;
            }
        }

        return $content;
    }

    private function get_amazon_link($asin) {
        $tag = $this->options['affiliate_tag'];
        return "https://www.amazon.com/dp/" . $asin . "?tag=" . $tag;
    }
}

new SmartAffiliateAutoInserter();

// Premium upsell notice
add_action('admin_notices', function() {
    if (get_option('smart_affiliate_dismissed_premium') !== 'yes') {
        echo '<div class="notice notice-info"><p>Unlock unlimited links and analytics with <strong>Smart Affiliate Pro</strong> for $29/year! <a href="https://example.com/pro" target="_blank">Upgrade now</a> | <a href="?dismiss_premium=1">Dismiss</a></p></div>';
    }
});

add_action('admin_init', function() {
    if (isset($_GET['dismiss_premium'])) {
        update_option('smart_affiliate_dismissed_premium', 'yes');
    }
});