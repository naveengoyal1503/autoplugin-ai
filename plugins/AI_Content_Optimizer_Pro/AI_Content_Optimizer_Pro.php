/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO and engagement using AI. Freemium with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerPro {
    private static $instance = null;
    private $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->is_premium = get_option('ai_cop_pro_license_key') !== false;
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_notices', array($this, 'premium_nag'));
        add_filter('the_content', array($this, 'optimize_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function activate() {
        update_option('ai_cop_activated', true);
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer Pro',
            'AI Optimizer',
            'manage_options',
            'ai-cop-pro',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_cop_license']) && check_admin_referer('ai_cop_license')) {
            update_option('ai_cop_pro_license_key', sanitize_text_field($_POST['ai_cop_license']));
            $this->is_premium = true;
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <?php if (!$this->is_premium): ?>
            <form method="post">
                <?php wp_nonce_field('ai_cop_license'); ?>
                <p>Enter license key for premium features ($9.99/month): <input type="text" name="ai_cop_license" placeholder="Premium License Key" /></p>
                <p><input type="submit" class="button-primary" value="Activate Premium" /></p>
            </form>
            <p><strong>Premium Features:</strong> Advanced AI rewrites, keyword suggestions, analytics dashboard.</p>
            <?php else: ?>
            <p><strong>Premium Active!</strong> Enjoy unlimited optimizations.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function premium_nag() {
        if (!$this->is_premium && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>AI Content Optimizer Pro</strong> premium for $9.99/month: Advanced AI, analytics & more! <a href="' . admin_url('options-general.php?page=ai-cop-pro') . '">Upgrade Now</a></p></div>';
        }
    }

    public function optimize_content($content) {
        if (is_admin() || !is_single()) return $content;

        // Simulate AI optimization (basic free, advanced premium)
        $keywords = $this->extract_keywords($content);
        $optimized = $content;

        // Free: Add meta title suggestion
        $optimized .= '<p><em>SEO Tip (Free): Optimize for keywords: ' . implode(', ', array_slice($keywords, 0, 3)) . '</em></p>';

        if ($this->is_premium) {
            // Premium: Rewrite first paragraph with keywords
            $first_para = wp_trim_words($content, 20, '');
            $premium_rewrite = $this->premium_rewrite($first_para, $keywords);
            $optimized = str_replace($first_para, $premium_rewrite, $content);
            $optimized .= '<div class="ai-optimized-badge">AI Optimized (Premium)</div>';
        }

        return $optimized;
    }

    private function extract_keywords($content) {
        // Simple keyword extraction simulation
        $words = explode(' ', strip_tags($content));
        $common = array('the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had');
        $counts = array_count_values($words);
        arsort($counts);
        $keywords = array();
        foreach ($counts as $word => $count) {
            if (!in_array(strtolower($word), $common) && strlen($word) > 4 && count($keywords) < 5) {
                $keywords[] = $word;
            }
        }
        return $keywords;
    }

    private function premium_rewrite($text, $keywords) {
        // Premium simulation: Bold keywords in text
        foreach ($keywords as $kw) {
            $text = preg_replace('/\b' . preg_quote($kw, '/') . '\b/i', '<strong>$0</strong>', $text, 2);
        }
        return $text;
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-cop-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
    }

    public function admin_enqueue_scripts($hook) {
        if ('settings_page_ai-cop-pro' !== $hook) return;
        wp_enqueue_style('ai-cop-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
    }
}

AIContentOptimizerPro::get_instance();

// Create CSS files on activation (self-contained)
function ai_cop_create_assets() {
    $css = ".ai-optimized-badge { background: #0073aa; color: white; padding: 5px 10px; display: inline-block; margin-top: 10px; border-radius: 3px; font-size: 12px; }";
    file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $css);
    $admin_css = ".wrap h1 { color: #0073aa; } .notice-info { border-left-color: #0073aa; }";
    file_put_contents(plugin_dir_path(__FILE__) . 'admin-style.css', $admin_css);
}
register_activation_hook(__FILE__, 'ai_cop_create_assets');

?>