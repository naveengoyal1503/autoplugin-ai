/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_ajax_nopriv_optimize_content', array($this, 'ajax_optimize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        $this->check_premium();
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    private function check_premium() {
        // Simulate premium check - in real, use license key or Freemius
        $this->is_premium = false; // get_option('ai_content_optimizer_premium') === 'activated';
    }

    public function activate() {
        add_option('ai_content_optimizer_dismissed_nag', false);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'assets/optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium
        ));
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'post.php') !== false || strpos($hook, 'post-new.php') !== false) {
            wp_enqueue_script('ai-optimizer-admin-js', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-optimizer-admin-js', 'ai_optimizer_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_nonce'),
                'is_premium' => $this->is_premium
            ));
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_license']) && check_admin_referer('ai_optimizer_license_nonce')) {
            update_option('ai_content_optimizer_license', sanitize_text_field($_POST['ai_optimizer_license']));
            $this->check_premium();
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <?php if (!$this->is_premium) : ?>
            <div class="notice notice-info">
                <p>Upgrade to <strong>Pro</strong> for AI rewrites, bulk optimization, and more! <a href="https://example.com/premium" target="_blank">Get Premium</a></p>
            </div>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field('ai_optimizer_license_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th>License Key</th>
                        <td><input type="text" name="ai_optimizer_license" value="<?php echo esc_attr(get_option('ai_content_optimizer_license', '')); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function ajax_optimize_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        $content = sanitize_textarea_field($_POST['content']);
        $post_id = intval($_POST['post_id']);

        if (empty($content)) {
            wp_die(json_encode(array('error' => 'No content provided')));
        }

        // Basic free analysis
        $readability = $this->calculate_readability($content);
        $seo_score = $this->calculate_seo_score($content);
        $suggestions = $this->get_basic_suggestions($content);

        $response = array(
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => $suggestions,
            'is_premium' => $this->is_premium
        );

        if ($this->is_premium) {
            // Premium AI rewrite simulation (in real: API call to OpenAI or similar)
            $response['optimized_content'] = $this->simulate_ai_rewrite($content);
        } else {
            $response['upgrade_message'] = 'Upgrade to Pro for AI-optimized content!';
        }

        wp_die(json_encode($response));
    }

    private function calculate_readability($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentence_count = preg_match_all('/[.!?]+/', $content);
        $avg_sentence_length = $sentence_count > 0 ? $word_count / $sentence_count : 0;
        // Flesch Reading Ease simplified
        return round(206.835 - 1.015 * ($word_count / $sentence_count) - 84.6 * (strlen($content) / $word_count), 2);
    }

    private function calculate_seo_score($content) {
        $score = 0;
        $title_like = preg_match('/<h1[^>]*>/i', $content) || preg_match('/<title[^>]*>/i', $content);
        if ($title_like) $score += 25;
        $headings = preg_match_all('/<h[2-6][^>]*>/i', $content);
        if ($headings > 2) $score += 25;
        $images = preg_match_all('/<img[^>]+alt=["\'][^\']+["\']/i', $content);
        if ($images > 0) $score += 25;
        $keywords = substr_count(strtolower($content), 'keyword density simulation');
        if ($keywords > 0) $score += 25;
        return min($score, 100);
    }

    private function get_basic_suggestions($content) {
        $suggestions = array();
        if (str_word_count(strip_tags($content)) < 300) {
            $suggestions[] = 'Add more content for better SEO (aim for 300+ words).';
        }
        if (preg_match_all('/<h1/i', $content) < 1) {
            $suggestions[] = 'Include an H1 heading.';
        }
        return $suggestions;
    }

    private function simulate_ai_rewrite($content) {
        // Simulated AI rewrite - replace with real API
        return '<p>AI-Optimized version: Improved ' . strip_tags($content) . ' with better SEO, readability score boosted!</p>';
    }
}

AIContentOptimizer::get_instance();

// Add meta box to posts
add_action('add_meta_boxes', function() {
    add_meta_box('ai-content-optimizer', 'AI Content Optimizer', 'ai_optimizer_meta_box_callback', 'post', 'side');
});

function ai_optimizer_meta_box_callback($post) {
    wp_nonce_field('ai_optimizer_meta_box', 'ai_optimizer_meta_box_nonce');
    echo '<div id="ai-optimizer-box">
        <button id="optimize-now" class="button button-primary">Analyze & Optimize</button>
        <div id="optimizer-results"></div>
    </div>';
}

// Create assets directory placeholder (in real plugin, include files)
if (!file_exists(plugin_dir_path(__FILE__) . 'assets')) {
    mkdir(plugin_dir_path(__FILE__) . 'assets', 0755, true);
}
// Note: JS files would be placed in assets/optimizer.js and admin.js with AJAX calls to optimize button.