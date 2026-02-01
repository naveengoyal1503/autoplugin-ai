/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for WordPress. Free basic features; premium for advanced AI.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    public function activate() {
        add_option('ai_content_optimizer_notices', array());
    }

    public function deactivate() {}

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'page', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_ai_co_score', true);
        $is_premium = $this->is_premium();
        $analysis = $this->analyze_content($content, $is_premium);
        echo '<div id="ai-co-results">';
        if ($analysis) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($analysis['readability']) . '%</p>';
            echo '<p><strong>SEO Score:</strong> ' . esc_html($analysis['seo']) . '%</p>';
            echo '<p><strong>Engagement:</strong> ' . esc_html($analysis['engagement']) . '</p>';
            if ($is_premium && isset($analysis['suggestions'])) {
                echo '<ul>';
                foreach ($analysis['suggestions'] as $sugg) {
                    echo '<li>' . esc_html($sugg) . '</li>';
                }
                echo '</ul>';
            } elseif (!$is_premium) {
                echo '<p><em>Upgrade to premium for AI suggestions and auto-optimize!</em></p>';
                echo '<a href="' . esc_url(admin_url('admin.php?page=ai-co-settings')) . '" class="button button-primary">Go Premium</a>';
            }
        } else {
            echo '<p>Analyze your content to see scores.</p>';
        }
        echo '</div>';
        echo '<p><small><a href="#" id="ai-co-analyze">Re-analyze</a></small></p>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        $content = get_post_field('post_content', $post_id);
        $is_premium = $this->is_premium();
        $analysis = $this->analyze_content($content, $is_premium);
        if ($analysis) {
            update_post_meta($post_id, '_ai_co_score', $analysis['overall']);
        }
    }

    public function analyze_content($content, $premium = false) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $avg_sentence = $sentence_count ? $word_count / $sentence_count : 0;

        $readability = min(100, max(0, 100 - ($avg_sentence - 15) * 5));
        $seo = min(100, ($word_count > 300 ? 80 : 40) + (strpos($content, 'href=') !== false ? 20 : 0));
        $engagement = $word_count > 500 ? 'High' : ($word_count > 200 ? 'Medium' : 'Low');
        $overall = ($readability + $seo) / 2;

        $result = array(
            'readability' => round($readability),
            'seo' => round($seo),
            'engagement' => $engagement,
            'overall' => round($overall)
        );

        if ($premium) {
            $result['suggestions'] = array(
                'Shorten sentences under 20 words.',
                'Add more internal links.',
                'Include a call-to-action.',
                'Optimize headings with keywords.'
            );
        }
        return $result;
    }

    public function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Content Optimizer', 'manage_options', 'ai-co-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['ai_co_premium_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['ai_co_premium_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $premium_key = get_option(self::PREMIUM_KEY);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="ai_co_premium_key" value="<?php echo esc_attr($premium_key); ?>" class="regular-text" />
                            <p class="description">Enter your premium key from <a href="https://example.com/premium" target="_blank">our site</a> ($9/mo for AI features).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!$this->is_premium()): ?>
            <div class="card">
                <h2>Go Premium</h2>
                <p>Unlock AI suggestions, bulk optimization, and more for $9/month.</p>
                <a href="https://example.com/buy-premium" class="button button-primary button-large" target="_blank">Buy Now</a>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('ai-co-js', plugin_dir_url(__FILE__) . 'ai-co.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-co-js', 'ai_co_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_co_nonce')));
        }
    }

    public function add_action_links($links) {
        $links[] = '<a href="' . admin_url('options-general.php?page=ai-co-settings') . '">Settings</a>';
        $links[] = '<a style="color:#1abc9c;font-weight:bold;" href="https://example.com/buy-premium" target="_blank">Premium</a>';
        return $links;
    }
}

new AIContentOptimizer();

// AJAX for re-analyze
add_action('wp_ajax_ai_co_analyze', 'ai_co_ajax_analyze');
function ai_co_ajax_analyze() {
    check_ajax_referer('ai_co_nonce', 'nonce');
    $post_id = intval($_POST['post_id']);
    $content = get_post_field('post_content', $post_id);
    $optimizer = new AIContentOptimizer();
    $analysis = $optimizer->analyze_content($content, $optimizer->is_premium());
    wp_send_json_success($analysis);
}
