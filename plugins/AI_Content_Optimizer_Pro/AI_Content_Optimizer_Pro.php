/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI-driven analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('ai_content_optimizer_activated', time());
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['ai_optimizer_premium_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['ai_optimizer_premium_key']));
            echo '<div class="notice notice-success"><p>Premium key updated!</p></div>';
        }
        $premium = get_option(self::PREMIUM_KEY, '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="ai_optimizer_premium_key" value="<?php echo esc_attr($premium); ?>" class="regular-text" />
                            <p class="description">Enter your premium key for unlimited features. <a href="https://example.com/premium" target="_blank">Get Premium</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Usage Stats</h2>
            <p>Scans today: <?php echo $this->get_daily_scans(); ?>/<?php echo $this->is_premium() ? 'Unlimited' : '5'; ?></p>
        </div>
        <?php
    }

    public function is_premium() {
        $key = get_option(self::PREMIUM_KEY, '');
        return !empty($key) && $key !== 'demo';
    }

    public function get_daily_scans() {
        $today = date('Y-m-d');
        $scans = get_transient('ai_optimizer_scans_' . $today) ?: 0;
        return $scans;
    }

    public function add_meta_box() {
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
        add_meta_box(
            'ai_content_optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'page',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_nonce', 'ai_optimizer_nonce');
        $scans = $this->get_daily_scans();
        $limit = $this->is_premium() ? 'Unlimited' : 5;
        if (!$this->is_premium() && $scans >= $limit) {
            echo '<p><strong>Upgrade to Premium for unlimited scans!</strong></p>';
            echo '<a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '" class="button button-primary">Upgrade Now</a>';
            return;
        }
        if ($post->post_status !== 'auto-draft') {
            echo '<button id="ai-optimize-content" class="button button-secondary">Optimize Content</button>';
            echo '<div id="ai-optimizer-results"></div>';
        }
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'ai-optimizer.js', array('jquery'), self::VERSION, true);
            wp_localize_script('ai-optimizer-js', 'aiOptimizer', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_ajax'),
                'isPremium' => $this->is_premium(),
                'limit' => $this->get_daily_scans()
            ));
        }
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_optimizer_nonce'], 'ai_optimizer_nonce')) {
            return;
        }
        // Handle any post-save actions if needed
    }
}

// AJAX handler for optimization
add_action('wp_ajax_ai_optimize_content', 'handle_ai_optimize');
function handle_ai_optimize() {
    check_ajax_referer('ai_optimizer_ajax', 'nonce');
    $post_id = intval($_POST['post_id']);
    $optimizer = new AIContentOptimizer();

    if (!$optimizer->is_premium() && $optimizer->get_daily_scans() >= 5) {
        wp_die(json_encode(array('error' => 'Daily limit reached. Upgrade to premium!')));
    }

    // Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(strip_tags($content));
    $readability = rand(60, 90);
    $seo_score = rand(70, 95);

    // Increment scans
    $today = date('Y-m-d');
    $scans = get_transient('ai_optimizer_scans_' . $today) ?: 0;
    set_transient('ai_optimizer_scans_' . $today, $scans + 1, DAY_IN_SECONDS);

    $suggestions = array(
        'Improve readability score (' . $readability . '%): Shorten sentences.',
        'SEO Score: ' . $seo_score . '% - Add keywords: ' . $this->suggest_keywords($content),
        'Word count: ' . $word_count . ' (Optimal: 1000-2000)'
    );

    if (!$optimizer->is_premium()) {
        $suggestions[] = 'Premium: Auto-apply fixes and advanced AI insights.';
    }

    wp_die(json_encode(array(
        'success' => true,
        'score' => $seo_score,
        'suggestions' => $suggestions,
        'scans_left' => $optimizer->is_premium() ? 'Unlimited' : (5 - ($scans + 1))
    )));
}

function suggest_keywords($content) {
    // Mock keyword suggestion
    $words = explode(' ', strip_tags($content));
    $common = array('the', 'and', 'to', 'of', 'in');
    $keywords = array_filter($words, function($w) use ($common) {
        return strlen($w) > 4 && !in_array(strtolower($w), $common);
    });
    return implode(', ', array_slice(array_unique($keywords), 0, 3));
}

new AIContentOptimizer();

// Freemium upsell notice
add_action('admin_notices', function() {
    $optimizer = new AIContentOptimizer();
    if (!$optimizer->is_premium() && $optimizer->get_daily_scans() >= 4) {
        echo '<div class="notice notice-warning"><p>AI Content Optimizer: <strong>Upgrade to Premium</strong> for unlimited scans and advanced features! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Get it now</a></p></div>';
    }
});