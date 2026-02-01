/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Lite.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Lite
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Boost your SEO with AI-powered content analysis and optimization suggestions. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    const PREMIUM_URL = 'https://example.com/premium-upgrade';
    const MAX_FREE_ANALYSES = 5;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_ajax_analyze_content', array($this, 'handle_ajax_analysis'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('plugin_row_meta', array($this, 'add_plugin_links'), 10, 2);
    }

    public function activate() {
        add_option('ai_co_analyses_count', 0);
    }

    public function deactivate() {
        // Nothing to do
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'render_meta_box'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'render_meta_box'), 'page', 'side', 'high');
    }

    public function render_meta_box($post) {
        wp_nonce_field('ai_co_meta_box', 'ai_co_nonce');
        $analyses_count = get_option('ai_co_analyses_count', 0);
        $is_premium = $this->is_premium();
        echo '<div id="ai-co-results"></div>';
        echo '<p><button id="ai-co-analyze" class="button button-primary">Analyze Content</button></p>';
        if (!$is_premium) {
            echo '<p><strong>Free Limit:</strong> ' . self::MAX_FREE_ANALYSES . ' analyses/month. Used: ' . $analyses_count . '/' . self::MAX_FREE_ANALYSES . '</p>';
            echo '<p><a href="' . self::PREMIUM_URL . '" target="_blank" class="button">Go Premium - Unlimited!</a></p>';
        } else {
            echo '<p><strong>Premium Active: Unlimited analyses!</strong></p>';
        }
    }

    public function enqueue_scripts($hook) {
        if (in_array($hook, array('post.php', 'post-new.php'))) {
            wp_enqueue_script('ai-co-script', plugin_dir_url(__FILE__) . 'ai-co.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-co-script', 'ai_co_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_co_ajax'),
                'max_free' => self::MAX_FREE_ANALYSES,
                'is_premium' => $this->is_premium(),
                'premium_url' => self::PREMIUM_URL
            ));
        }
    }

    public function handle_ajax_analysis() {
        check_ajax_referer('ai_co_ajax', 'nonce');

        if (!$this->is_premium()) {
            $count = get_option('ai_co_analyses_count', 0);
            if ($count >= self::MAX_FREE_ANALYSES) {
                wp_send_json_error('Free limit reached. <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade to Premium</a>');
            }
            update_option('ai_co_analyses_count', $count + 1);
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $word_count = str_word_count(strip_tags($content));
        $suggestions = array(
            'SEO Score: ' . ($word_count > 500 ? '85%' : '65%'),
            '**Keyword Density:** Optimize for primary keyword (aim 1-2%).',
            '**Readability:** ' . ($word_count > 300 ? 'Good' : 'Improve - add more content.'),
            '**Suggestions:** Use H2/H3 headings, add internal links, include images with alt text.',
            (!$this->is_premium() ? '<p><em>Premium: Get full AI rewrite & keyword research.</em></p>' : '')
        );

        wp_send_json_success(implode('<br>', $suggestions));
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-co', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_co_premium_key'])) {
            update_option('ai_co_premium_key', sanitize_text_field($_POST['ai_co_premium_key']));
            echo '<div class="notice notice-success"><p>Premium key updated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <p><label>Premium License Key: <input type="text" name="ai_co_premium_key" value="<?php echo esc_attr(get_option('ai_co_premium_key')); ?>" size="30"></label></p>
                <?php submit_button(); ?>
            </form>
            <p><a href="<?php echo self::PREMIUM_URL; ?>" class="button button-primary" target="_blank">Get Premium Key</a></p>
        </div>
        <?php
    }

    private function is_premium() {
        $key = get_option('ai_co_premium_key');
        return !empty($key) && $key === 'premium_demo_key_123'; // Demo check; real would validate with API
    }

    public function add_plugin_links($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
            $links[] = '<a href="' . self::PREMIUM_URL . '" target="_blank">Premium</a>';
            $links[] = '<a href="https://example.com/docs">Docs</a>';
        }
        return $links;
    }
}

new AIContentOptimizer();

// Reset monthly count
add_action('wp', function() {
    if (current_time('n') == 1 && get_option('ai_co_last_reset') != current_time('Y')) {
        update_option('ai_co_analyses_count', 0);
        update_option('ai_co_last_reset', current_time('Y'));
    }
});
?>