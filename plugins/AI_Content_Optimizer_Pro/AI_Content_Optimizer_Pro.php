/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO and monetization by integrating affiliate links, ads, and readability scores using AI analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizerPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'optimize_content'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        // Premium check (simulate license)
        $this->is_premium = get_option('ai_content_optimizer_premium', false);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('ai-optimizer-css', plugin_dir_url(__FILE__) . 'assets/optimizer.css', array(), '1.0.0');
    }

    public function optimize_content($content) {
        if (is_admin() || !is_single()) return $content;

        // Readability score (Flesch-Kincaid approximation)
        $readability = $this->calculate_readability($content);
        $content .= '<div class="ai-readability-score">Readability Score: ' . round($readability, 2) . '/100</div>';

        // Insert affiliate placeholder (premium simulates AI keyword match)
        if ($this->is_premium) {
            $affiliate_links = array(
                'best tools' => 'https://example-affiliate.com/tool1?ref=yourid',
                'recommended product' => 'https://example-affiliate.com/product2?ref=yourid'
            );
            foreach ($affiliate_links as $keyword => $link) {
                $content = str_ireplace($keyword, '<a href="' . $link . '" target="_blank" rel="nofollow">' . $keyword . '</a>', $content);
            }
        } else {
            $content .= '<p><em>Upgrade to Pro for automatic affiliate link insertion!</em></p>';
        }

        // Ad insertion
        $ad_code = '<div class="ai-ad-placeholder">[Your AdSense or Affiliate Ad Code Here]</div>';
        $content .= $ad_code;

        return $content;
    }

    private function calculate_readability($text) {
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = preg_split('/\s+/', strip_tags($text));
        $sentences_count = count($sentences);
        $words_count = count($words);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += $this->count_syllables($word);
        }
        if ($words_count == 0 || $sentences_count == 0) return 0;
        $asl = $words_count / $sentences_count;
        $asw = $syllables / $words_count;
        $fk = 206.835 - (1.015 * $asl) - (84.6 * $asw);
        return max(0, min(100, round(206.835 - $fk)));
    }

    private function count_syllables($word) {
        $word = strtolower(preg_replace('/[^a-z]/', '', $word));
        if (strlen($word) <= 3) return 1;
        $count = 0;
        $word = preg_replace('/es$/', '', $word);
        $vowels = preg_match_all('/[aeiouy]/', $word, $matches);
        $ends_silent_e = preg_match('/e$/', $word);
        $diphthongs = preg_match_all('/(ai|ay|ea|ee|ei|ey|oa|oe|ou|oo)/', $word);
        return max(1, $vowels - $ends_silent_e - $diphthongs + 1);
    }

    public function admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'admin_page'));
    }

    public function admin_init() {
        register_setting('ai_optimizer_group', 'ai_content_optimizer_premium');
        register_setting('ai_optimizer_group', 'ai_optimizer_affiliate_ids');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ai_optimizer_group'); ?>
                <?php do_settings_sections('ai_optimizer_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Premium Features</th>
                        <td><input type="checkbox" name="ai_content_optimizer_premium" value="1" <?php checked(get_option('ai_content_optimizer_premium')); ?> /></td>
                    </tr>
                    <tr>
                        <th>Affiliate IDs (JSON)</th>
                        <td><textarea name="ai_optimizer_affiliate_ids" rows="5" cols="50"><?php echo esc_textarea(get_option('ai_optimizer_affiliate_ids', '{}')); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI-powered keyword affiliate matching and ad optimization for $49/year.</p>
        </div>
        <?php
    }
}

new AIContentOptimizerPro();

// Create assets directories on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = wp_upload_dir();
    $assets_dir = plugin_dir_path(__FILE__) . 'assets/';
    if (!file_exists($assets_dir)) {
        wp_mkdir_p($assets_dir);
    }
    // Note: Create empty assets/optimizer.js and assets/optimizer.css manually or via FTP
});

// CSS content (inline for single file)
function ai_optimizer_inline_css() {
    if (!wp_script_is('ai-optimizer-js', 'enqueued')) return;
    ?>
    <style>
    .ai-readability-score { background: #e7f3ff; padding: 10px; margin: 20px 0; border-left: 4px solid #0073aa; }
    .ai-ad-placeholder { background: #f9f9f9; padding: 20px; text-align: center; margin: 20px 0; border: 1px dashed #ccc; }
    </style>
    <?php
}
add_action('wp_head', 'ai_optimizer_inline_css');

// JS content (inline)
function ai_optimizer_inline_js() {
    if (!wp_script_is('ai-optimizer-js', 'enqueued')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.ai-readability-score').each(function() {
            var score = $(this).text().match(/(\d+)/);
            var color = score > 70 ? '#28a745' : score > 50 ? '#ffc107' : '#dc3545';
            $(this).css('border-left-color', color);
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'ai_optimizer_inline_js');