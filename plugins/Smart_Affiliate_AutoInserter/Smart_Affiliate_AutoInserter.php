/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using keyword matching to boost monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
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

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('saa_keywords', sanitize_textarea_field($_POST['keywords']));
            update_option('saa_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $keywords = get_option('saa_keywords', "");
        $links = get_option('saa_affiliate_links', "");
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Keywords (one per line)</th>
                        <td><textarea name="keywords" rows="10" cols="50"><?php echo esc_textarea($keywords); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (format: keyword|url|description, one per line)</th>
                        <td><textarea name="affiliate_links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea><br>
                        <small>Example: amazon|https://amazon.com/link|Buy on Amazon</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Premium Upgrade</h2>
            <p>Unlock unlimited links, AI matching, analytics & more for $49/year. <a href="https://example.com/premium" target="_blank">Get Premium</a></p>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (is_admin() || !is_single()) return $content;

        $keywords = get_option('saa_keywords', "");
        $aff_links = get_option('saa_affiliate_links', "");
        if (empty($keywords) || empty($aff_links)) return $content;

        $keyword_array = explode("\n", trim($keywords));
        $link_array = explode("\n", trim($aff_links));
        $replacements = array();

        foreach ($link_array as $line) {
            $parts = explode('|', trim($line), 3);
            if (count($parts) >= 2) {
                $replacements[$parts] = '<a href="' . esc_url($parts[1]) . '" target="_blank" rel="nofollow noopener">' . (isset($parts[2]) ? esc_html($parts[2]) : 'Link') . '</a>';
            }
        }

        $limit = 3; // Free version limit
        $inserted = 0;

        foreach ($keyword_array as $keyword) {
            $keyword = trim(strtolower($keyword));
            if (isset($replacements[$keyword]) && $inserted < $limit) {
                $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
                if (preg_match($pattern, $content) && rand(1, 3) == 1) { // 33% chance per match
                    $content = preg_replace($pattern, $replacements[$keyword], $content, 1);
                    $inserted++;
                }
            }
        }

        return $content;
    }

    public function activate() {
        add_option('saa_keywords', "wordpress\nplugin\ntheme");
        add_option('saa_affiliate_links', "wordpress|https://affiliate-link.com/wordpress|Get WordPress\nplugin|https://affiliate-link.com/plugin|Buy Plugin");
    }
}

SmartAffiliateAutoInserter::get_instance();

// Premium nag
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoInserter Premium</strong> for unlimited links & analytics! <a href="https://example.com/premium" target="_blank">Learn More</a></p></div>';
});