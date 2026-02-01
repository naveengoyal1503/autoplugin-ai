/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: AI-powered plugin that automatically inserts relevant Amazon affiliate links into WordPress posts and pages to boost revenue.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 * Domain Path: /languages
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
        add_filter('the_content', array($this, 'auto_insert_affiliate_links'), 99);
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

    public function auto_insert_affiliate_links($content) {
        if (!is_single() || is_admin()) return $content;

        $settings = get_option('smart_affiliate_settings', array('amazon_tag' => '', 'enabled' => true));
        if (!$settings['enabled']) return $content;

        $amazon_tag = $settings['amazon_tag'];

        // Simple keyword-based matching (premium: AI-powered)
        $keywords = array(
            'phone' => 'https://www.amazon.com/dp/B0ABC123XYZ?tag=' . $amazon_tag,
            'laptop' => 'https://www.amazon.com/dp/B0DEF456UVW?tag=' . $amazon_tag,
            'book' => 'https://www.amazon.com/dp/1234567890?tag=' . $amazon_tag,
            'headphones' => 'https://www.amazon.com/dp/B0GHI789JKL?tag=' . $amazon_tag
        );

        foreach ($keywords as $keyword => $link) {
            if (stripos($content, $keyword) !== false) {
                $aff_link = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">Check on Amazon</a> ';
                $content = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '$1 ' . $aff_link, $content, 1);
            }
        }

        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_group'); ?>
                <?php do_settings_sections('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Amazon Affiliate Tag</th>
                        <td><input type="text" name="smart_affiliate_settings[amazon_tag]" value="<?php echo esc_attr(get_option('smart_affiliate_settings')['amazon_tag'] ?? ''); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_settings[enabled]" value="1" <?php checked(get_option('smart_affiliate_settings')['enabled'] ?? true); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Premium:</strong> AI keyword matching, analytics, multiple networks. <a href="https://example.com/premium">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_settings', array('amazon_tag' => '', 'enabled' => true));
    }

    public function deactivate() {
        // Cleanup optional
    }
}

SmartAffiliateAutoInserter::get_instance();

// Freemium upsell notice
function smart_affiliate_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock AI-powered features with <a href="https://example.com/premium">Smart Affiliate Pro</a>! Earn more with advanced analytics and integrations.</p></div>';
}
add_action('admin_notices', 'smart_affiliate_admin_notice');

// Prevent direct access to assets folder if needed
if (!defined('ABSPATH')) die();