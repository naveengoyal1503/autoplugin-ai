/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content to boost earnings. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (is_admin() || !is_single()) return $content;

        $options = get_option('smart_affiliate_options', array());
        $keywords = !empty($options['keywords']) ? explode(',', $options['keywords']) : array();
        $links = !empty($options['links']) ? explode(',', $options['links']) : array();

        if (empty($keywords) || empty($links)) return $content;

        $words = explode(' ', $content);
        foreach ($keywords as $index => $keyword) {
            $keyword = trim($keyword);
            if (isset($links[$index])) {
                $link = trim($links[$index]);
                foreach ($words as &$word) {
                    if (stripos($word, $keyword) !== false && rand(1, 3) === 1) { // 33% chance
                        $word = str_ireplace($keyword, '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener">' . $keyword . '</a>', $word);
                        break 2;
                    }
                }
            }
        }
        return implode(' ', $words);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_options_group', 'smart_affiliate_options');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_options_group'); ?>
                <?php do_settings_sections('smart_affiliate_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Keywords (comma-separated)</th>
                        <td><input type="text" name="smart_affiliate_options[keywords]" value="<?php echo esc_attr(get_option('smart_affiliate_options')['keywords'] ?? ''); ?>" class="regular-text" placeholder="best laptop, cheap shoes" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Affiliate Links (comma-separated, match keywords order)</th>
                        <td><input type="text" name="smart_affiliate_options[links]" value="<?php echo esc_attr(get_option('smart_affiliate_options')['links'] ?? ''); ?>" class="regular-text" placeholder="https://amazon.com/laptop, https://amazon.com/shoes" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> AI keyword detection, analytics, unlimited links. <a href="#" onclick="alert('Pro features coming soon!')">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_options', array());
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

SmartAffiliateAutoInserter::get_instance();

// Freemium upsell notice
function smart_affiliate_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>AI-powered insertions & analytics</strong> with <a href="https://example.com/pro">Smart Affiliate Pro</a>! Earn 2x more.</p></div>';
}
add_action('admin_notices', 'smart_affiliate_admin_notice');