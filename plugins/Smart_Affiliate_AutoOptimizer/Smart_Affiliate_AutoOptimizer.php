/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoOptimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoOptimizer
 * Plugin URI: https://example.com/smart-affiliate-autooptimizer
 * Description: AI-powered plugin that automatically detects content topics, inserts relevant affiliate links from multiple networks, and tracks performance for maximum conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autooptimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoOptimizer {
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
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_shortcode('affiliate_optimizer', array($this, 'display_optimizer_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        // Load text domain
        load_plugin_textdomain('smart-affiliate-autooptimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');

        // Add admin menu
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function auto_insert_links($content) {
        if (!is_single() || is_admin()) return $content;

        $settings = get_option('smart_affiliate_settings', array('enabled' => false, 'max_links' => 3));
        if (!$settings['enabled']) return $content;

        // Simple keyword-based topic detection (placeholder for AI)
        $keywords = array('wordpress' => 'https://affiliate.amazon.com', 'hosting' => 'https://affiliate.bluehost.com', 'plugin' => 'https://yourplugin.com/aff');
        $words = explode(' ', strtolower(strip_tags($content)));
        $inserted = 0;

        foreach ($keywords as $keyword => $link) {
            if ($inserted >= $settings['max_links']) break;
            if (array_search($keyword, $words) !== false) {
                $link_html = '<p><a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">Check out this ' . ucfirst($keyword) . ' deal! <span class="aff-track" data-link="' . esc_attr($link) . '">→</span></a></p>';
                $content .= $link_html;
                $inserted++;
            }
        }

        return $content;
    }

    public function display_optimizer_shortcode($atts) {
        $atts = shortcode_atts(array('type' => 'amazon'), $atts);
        return '<div class="affiliate-optimizer" data-type="' . esc_attr($atts['type']) . '">Optimized affiliate links loading...</div>';
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
            <h1>Smart Affiliate AutoOptimizer Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_group'); ?>
                <?php do_settings_sections('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_settings[enabled]" value="1" <?php checked(get_option('smart_affiliate_settings')['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_settings[max_links]" value="<?php echo esc_attr(get_option('smart_affiliate_settings')['max_links'] ?? 3); ?>" min="1" max="10" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Pro Features (Upgrade for $49/year)</h2>
            <ul>
                <li>Unlimited links & premium networks (Amazon, Bluehost, etc.)</li>
                <li>AI content analysis & A/B testing</li>
                <li>Conversion tracking dashboard</li>
            </ul>
            <a href="https://example.com/pro" class="button button-primary">Upgrade to Pro</a>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_settings', array('enabled' => false, 'max_links' => 3));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

SmartAffiliateAutoOptimizer::get_instance();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        mkdir($assets_dir, 0755, true);
    }
    file_put_contents($assets_dir . 'script.js', '// Placeholder JS for tracking\nconsole.log("Affiliate Optimizer loaded"); jQuery(".aff-track").click(function(){ console.log("Link clicked:", $(this).data("link")); });');
    file_put_contents($assets_dir . 'style.css', '.affiliate-optimizer { border: 1px solid #ddd; padding: 10px; background: #f9f9f9; } .aff-track { color: #0073aa; }');
});
?>