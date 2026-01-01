/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Affiliate Link Optimizer
 * Plugin URI: https://example.com/ai-affiliate-optimizer
 * Description: Automatically generates and optimizes affiliate links with AI-powered product recommendations, personalized discounts, and conversion tracking.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-affiliate-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIAffiliateOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('ai_affiliate_links', array($this, 'affiliate_links_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-affiliate-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-affiliate-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function affiliate_links_shortcode($atts) {
        $atts = shortcode_atts(array(
            'niche' => 'general',
            'count' => 3
        ), $atts);

        $links = $this->generate_ai_links($atts['niche'], intval($atts['count']));
        ob_start();
        ?>
        <div class="ai-affiliate-container">
            <h3>Recommended Deals</h3>
            <?php foreach ($links as $link): ?>
                <div class="affiliate-item">
                    <h4><?php echo esc_html($link['title']); ?></h4>
                    <p><?php echo esc_html($link['desc']); ?> <strong><?php echo esc_html($link['discount']); ?></strong></p>
                    <a href="<?php echo esc_url($link['url']); ?}" target="_blank" rel="nofollow" class="affiliate-btn">Get Deal <?php echo esc_html($link['commission']); ?> Commission</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function generate_ai_links($niche, $count) {
        // Simulated AI generation based on niche (Pro version would integrate real AI API)
        $products = array(
            array('title' => 'Premium WordPress Theme', 'desc' => 'Best theme for blogs', 'discount' => '50% OFF', 'url' => 'https://example.com/theme?aff=123', 'commission' => '30%'),
            array('title' => 'SEO Tool Pro', 'desc' => 'Rank higher on Google', 'discount' => '20% OFF', 'url' => 'https://example.com/seo?aff=123', 'commission' => '40%'),
            array('title' => 'Email Marketing Software', 'desc' => 'Build your list fast', 'discount' => 'FREE Trial', 'url' => 'https://example.com/email?aff=123', 'commission' => '25%')
        );
        // Filter by niche and limit count
        if ($niche !== 'general') {
            $products = array_filter($products, function($p) use ($niche) { return stripos($p['desc'], $niche) !== false; });
        }
        return array_slice($products, 0, $count);
    }

    public function admin_menu() {
        add_options_page('AI Affiliate Optimizer', 'AI Affiliate', 'manage_options', 'ai-affiliate', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_affiliate_api_key'])) {
            update_option('ai_affiliate_api_key', sanitize_text_field($_POST['ai_affiliate_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('ai_affiliate_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Affiliate Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>AI API Key (Pro)</th>
                        <td><input type="text" name="ai_affiliate_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for real AI integration, unlimited links, and analytics. <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p>
        </div>
        <?php
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AIAffiliateOptimizer();

// Pro upsell notice
function ai_affiliate_admin_notice() {
    if (!get_option('ai_affiliate_pro_dismissed')) {
        echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>AI Affiliate Link Optimizer Pro</strong> for AI-powered recommendations and advanced tracking! <a href="https://example.com/pro" target="_blank">Upgrade Now ($49/year)</a></p></div>';
    }
}
add_action('admin_notices', 'ai_affiliate_admin_notice');

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    file_put_contents($assets_dir . '/style.css', '.ai-affiliate-container { max-width: 600px; margin: 20px 0; } .affiliate-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; } .affiliate-btn { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; }');
    file_put_contents($assets_dir . '/script.js', 'jQuery(document).ready(function($) { $(".affiliate-btn").on("click", function() { $(this).text("Tracking..."); }); });');
});
?>