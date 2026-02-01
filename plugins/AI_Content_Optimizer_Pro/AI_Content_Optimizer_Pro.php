/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/aicontentoptimizer
 * Description: Analyzes and optimizes WordPress post content for SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
        add_action('wp_ajax_upgrade_to_pro', array($this, 'ajax_upgrade_to_pro'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        $this->is_premium = get_option('aicop_pro_license_valid', false);
    }

    public function activate() {
        if (!get_option('aicop_activated')) {
            add_option('aicop_activated', true);
        }
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Content Opt',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'options_page')
        );
    }

    public function options_page() {
        if (isset($_POST['submit'])) {
            update_option('aicop_api_key', sanitize_text_field($_POST['api_key']));
        }
        $api_key = get_option('aicop_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="api_key" value="<?php echo esc_attr($api_key); ?>" size="50" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php $this->pro_nag(); ?>
        </div>
        <?php
    }

    public function add_meta_box() {
        add_meta_box(
            'ai-content-optimizer',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_content_optimizer_nonce', 'ai_content_optimizer_nonce');
        $score = get_post_meta($post->ID, '_aicop_score', true);
        $suggestions = get_post_meta($post->ID, '_aicop_suggestions', true);
        echo '<p><strong>SEO Score:</strong> ' . ($score ? $score . '%' : 'Not analyzed') . '</p>';
        if ($suggestions) {
            echo '<p><em>' . esc_html($suggestions) . '</em></p>';
        }
        echo '<button type="button" id="analyze-btn" class="button">Analyze Content</button> ';
        if ($this->is_premium) {
            echo '<button type="button" id="optimize-btn" class="button button-primary">Optimize (Pro)</button>';
        } else {
            echo '<button type="button" id="upgrade-btn" class="button button-primary">Upgrade to Pro</button>';
        }
    }

    public function save_post($post_id) {
        if (!isset($_POST['ai_content_optimizer_nonce']) || !wp_verify_nonce($_POST['ai_content_optimizer_nonce'], 'ai_content_optimizer_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
    }

    public function enqueue_scripts() {
        if (is_singular('post')) {
            wp_enqueue_script('aicop-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_enqueue_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('aicop-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aicop-admin', 'aicop_ajax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aicop_nonce'),
                'is_premium' => $this->is_premium
            ));
        }
    }

    public function ajax_optimize_content() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!$this->is_premium) {
            wp_send_json_error('Premium feature');
        }
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        $api_key = get_option('aicop_api_key');
        if (!$api_key) {
            wp_send_json_error('API key not set');
        }
        $prompt = "Optimize this content for SEO and readability: " . substr($content, 0, 2000);
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 1000
            ))
        ));
        if (is_wp_error($response)) {
            wp_send_json_error('API error');
        }
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['choices']['message']['content'])) {
            wp_update_post(array('ID' => $post_id, 'post_content' => $body['choices']['message']['content']));
            wp_send_json_success('Optimized!');
        } else {
            wp_send_json_error('Optimization failed');
        }
    }

    public function ajax_upgrade_to_pro() {
        check_ajax_referer('aicop_nonce', 'nonce');
        // Simulate license check - in real, integrate with payment processor
        if (isset($_POST['license_key']) && $_POST['license_key'] === 'pro123') {
            update_option('aicop_pro_license_valid', true);
            $this->is_premium = true;
            wp_send_json_success('Upgraded to Pro!');
        } else {
            wp_send_json_error('Invalid license');
        }
    }

    private function pro_nag() {
        if (!$this->is_premium) {
            echo '<div class="notice notice-info"><p><strong>Go Pro!</strong> Unlock AI rewriting, bulk optimization, and more for $4.99/month. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
        }
    }
}

AIContentOptimizer::get_instance();

// Basic analysis function (free)
function aicop_analyze_content($content) {
    $word_count = str_word_count($content);
    $sentences = preg_split('/[.!?]+/', $content);
    $readability = $word_count > 0 ? round(206.835 - 1.015 * (avg_words_per_sentence($sentences)) - 84.6 * (flesch_kincaid_grade($content)), 2) : 0;
    $score = min(100, max(0, ($readability / 100) * 100));
    $suggestions = $score < 70 ? 'Improve readability and add keywords.' : 'Good! Consider more headings.';
    return array('score' => $score, 'suggestions' => $suggestions);
}

function avg_words_per_sentence($sentences) {
    $total_words = 0;
    $count = 0;
    foreach ($sentences as $s) {
        $words = str_word_count(trim($s));
        if ($words > 0) {
            $total_words += $words;
            $count++;
        }
    }
    return $count > 0 ? $total_words / $count : 0;
}

function flesch_kincaid_grade($text) {
    $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    $sentence_count = count($sentences);
    $word_count = str_word_count($text);
    $syllables = 0; // Simplified syllable count
    $words = str_word_count($text, 2);
    foreach ($words as $word) {
        $syllables += max(1, preg_match_all('/[aeiouy]/', strtolower($word)) - preg_match_all('/e$/', strtolower($word)));
    }
    $asl = $word_count / max(1, $sentence_count);
    $asw = $syllables / $word_count;
    return 0.39 * $asl + 11.8 * $asw - 15.59;
}

// AJAX for free analysis
add_action('wp_ajax_analyze_content_free', 'aicop_ajax_analyze');
function aicop_ajax_analyze() {
    $content = sanitize_textarea_field($_POST['content']);
    $result = aicop_analyze_content($content);
    wp_send_json_success($result);
}

?>