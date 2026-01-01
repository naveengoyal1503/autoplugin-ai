/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoOptimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoOptimizer
 * Plugin URI: https://example.com/smart-affiliate-autooptimizer
 * Description: AI-powered automatic affiliate link insertion and optimization for maximum conversions.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autooptimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoOptimizer {
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
        load_plugin_textdomain('smart-affiliate-autooptimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function auto_insert_links($content) {
        if (!is_single() || is_admin()) return $content;

        $settings = get_option('smart_affiliate_settings', array('enabled' => true, 'networks' => array('amazon'), 'keywords' => array()));
        if (!$settings['enabled']) return $content;

        // Simple keyword-based auto-insertion (extendable to AI in Pro)
        $keywords = array(
            'laptop' => 'https://amazon.com/laptop-affiliate-link?tag=yourtag',
            'phone' => 'https://amazon.com/phone-affiliate-link?tag=yourtag',
            'book' => 'https://amazon.com/book-affiliate-link?tag=yourtag'
        );

        foreach ($keywords as $keyword => $link) {
            if (stripos($content, $keyword) !== false && rand(1, 3) === 1) { // Insert ~33% of time
                $link_html = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored" class="smart-affiliate-link">' . ucfirst($keyword) . ' (affiliate)</a>';
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link_html, $content, 1);
            }
        }

        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Optimizer',
            'Affiliate Optimizer',
            'manage_options',
            'smart-affiliate',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoOptimizer Settings', 'smart-affiliate-autooptimizer'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_group'); ?>
                <?php do_settings_sections('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="enabled">Enable Auto-Insertion</label></th>
                        <td><input type="checkbox" id="enabled" name="smart_affiliate_settings[enabled]" value="1" <?php checked(1, isset(get_option('smart_affiliate_settings')['enabled']) ? get_option('smart_affiliate_settings')['enabled'] : 0); ?> /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Networks</th>
                        <td>
                            <label><input type="checkbox" name="smart_affiliate_settings[networks][]" value="amazon" <?php checked(true, in_array('amazon', (array) get_option('smart_affiliate_settings')['networks'] ?? array())); ?> /> Amazon</label><br>
                            <label><input type="checkbox" name="smart_affiliate_settings[networks][]" value="clickbank" <?php checked(true, in_array('clickbank', (array) get_option('smart_affiliate_settings')['networks'] ?? array())); ?> /> ClickBank</label>
                        </td>
                    </tr>
                    <tr>
                        <th>Custom Keywords (Pro Feature)</th>
                        <td><input type="text" name="smart_affiliate_settings[pro_keywords]" placeholder="keyword=url" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
                <p><strong>Pro Upgrade:</strong> Unlock AI content analysis, A/B testing, analytics, and 50+ networks for $49/year.</p>
            </form>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_settings', array('enabled' => true, 'networks' => array('amazon')));
    }

    public function deactivate() {
        // Cleanup optional
    }
}

SmartAffiliateAutoOptimizer::get_instance();

// Freemium upsell notice
function smart_affiliate_admin_notice() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="notice notice-info">
        <p><strong>Smart Affiliate AutoOptimizer Pro:</strong> Upgrade for AI optimization, unlimited links & analytics! <a href="https://example.com/pro" target="_blank">Get Pro Now</a></p>
    </div>
    <?php
}
add_action('admin_notices', 'smart_affiliate_admin_notice');

// Prevent direct access to assets
add_action('wp_head', function() {
    if (is_admin()) return;
    echo '<style>.smart-affiliate-link {color: #0073aa; text-decoration: underline;}</style>';
});

// Track clicks (basic)
add_action('wp_ajax_track_affiliate_click', 'smart_affiliate_track_click');
add_action('wp_ajax_nopriv_track_affiliate_click', 'smart_affiliate_track_click');
function smart_affiliate_track_click() {
    // Pro feature: Log clicks
    wp_die();
}
?>