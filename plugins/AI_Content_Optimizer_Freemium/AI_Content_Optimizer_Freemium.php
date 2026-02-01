/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Free version with limits; premium for advanced features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_URL = 'https://example.com/premium-upgrade';
    private $daily_limit = 3;
    private $user_scans = 0;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
            add_action('save_post', array($this, 'save_post'));
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('admin_menu', array($this, 'add_settings_page'));
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
        }
        $this->check_daily_limit();
    }

    public function activate() {
        add_option('ai_co_daily_scans_' . date('Y-m-d'), 0);
    }

    public function deactivate() {
        // Cleanup optional
    }

    private function check_daily_limit() {
        $today = date('Y-m-d');
        $key = 'ai_co_daily_scans_' . $today;
        $this->user_scans = (int) get_option($key, 0);

        if ($this->user_scans >= $this->daily_limit) {
            add_action('admin_notices', array($this, 'limit_notice'));
        }
        // Reset daily count at midnight
        if (get_option('ai_co_last_reset') != $today) {
            delete_option($key);
            update_option('ai_co_last_reset', $today);
        }
    }

    public function limit_notice() {
        echo '<div class="notice notice-warning"><p><strong>AI Content Optimizer:</strong> Daily free scan limit reached. <a href="' . self::PREMIUM_URL . '" target="_blank">Upgrade to Premium</a> for unlimited access!</p></div>';
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_content'), 'post', 'side', 'high');
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_content'), 'page', 'side', 'high');
    }

    public function meta_box_content($post) {
        wp_nonce_field('ai_co_optimize', 'ai_co_nonce');
        echo '<p><button type="button" id="ai-co-analyze" class="button button-primary">Analyze Content</button></p>';
        echo '<div id="ai-co-results"></div>';
        echo '<p id="ai-co-status"></p>';
    }

    public function enqueue_scripts($hook) {
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_script('ai-co-js', plugin_dir_url(__FILE__) . 'ai-co.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-co-js', 'ai_co_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_co_ajax'),
                'limit_reached' => $this->user_scans >= $this->daily_limit,
                'premium_url' => self::PREMIUM_URL
            ));
        }
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Content Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <p>Free version: Limited to <?php echo $this->daily_limit; ?> scans per day.</p>
            <p><a href="<?php echo self::PREMIUM_URL; ?>" class="button button-primary" target="_blank">Upgrade to Premium</a></p>
        </div>
        <?php
    }

    public function add_action_links($links) {
        $links[] = '<a href="' . self::PREMIUM_URL . '" target="_blank">Premium</a>';
        $links[] = '<a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Settings</a>';
        return $links;
    }

    // AJAX handler for analysis
    public function init_ajax() {
        add_action('wp_ajax_ai_co_analyze', array($this, 'handle_analyze'));
    }
    add_action('init', array(new self(), 'init_ajax'));

    public function handle_analyze() {
        check_ajax_referer('ai_co_ajax', 'nonce');

        if ($this->user_scans >= $this->daily_limit) {
            wp_send_json_error('Daily limit reached. Upgrade to premium!');
        }

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (in real plugin, integrate OpenAI API or similar for premium)
        $word_count = str_word_count(strip_tags($content));
        $readability = $this->calculate_flesch_reading_ease($content);
        $seo_score = min(100, 50 + ($word_count / 10) + ($readability / 2));

        // Free basic suggestions
        $suggestions = array(
            'Word count: ' . $word_count . ' (Aim for 1000+ for SEO)',
            'Readability score: ' . round($readability, 1) . '/100',
            'SEO Score: ' . round($seo_score) . '/100',
            'Free tip: Add more headings and keywords. <a href="' . self::PREMIUM_URL . '" target="_blank">Premium: AI keyword suggestions & rewrites</a>'
        );

        // Increment scan count
        $today = date('Y-m-d');
        $key = 'ai_co_daily_scans_' . $today;
        $scans = (int) get_option($key, 0) + 1;
        update_option($key, $scans);

        wp_send_json_success(array('suggestions' => $suggestions));
    }

    private function calculate_flesch_reading_ease($text) {
        $text = strip_tags($text);
        $sentences = preg_split('/[.!?]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $words = str_word_count($text, 0);
        $syllables = $this->count_syllables($text);

        if ($sentence_count == 0 || $words == 0) return 0;

        $asl = $words / $sentence_count;
        $asw = $syllables / $words;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $words = explode(' ', $text);
        $syllables = 0;
        foreach ($words as $word) {
            $syllables += preg_match_all('/[aeiouy]{2}/', $word) + preg_match_all('/[aeiouy]/', $word) - preg_match_all('/ed|ing/', $word);
        }
        return max(1, $syllables);
    }
}

new AIContentOptimizer();

// Dummy JS file content (in real, separate file)
/*
To make self-contained, inline JS:
*/
function aiCoAnalyze() {
    jQuery(document).ready(function($) {
        $('#ai-co-analyze').click(function() {
            var post_id = $('#post_ID').val();
            $('#ai-co-status').html('Analyzing...');
            $.post(ai_co_ajax.ajax_url, {
                action: 'ai_co_analyze',
                nonce: ai_co_ajax.nonce,
                post_id: post_id
            }, function(response) {
                if (response.success) {
                    $('#ai-co-results').html('<ul>' + response.data.suggestions.map(s => '<li>' + s + '</li>').join('') + '</ul>');
                    $('#ai-co-status').html('');
                } else {
                    $('#ai-co-results').html('');
                    $('#ai-co-status').html(response.data);
                }
            });
        });
    });
}
add_action('admin_footer-post.php', 'aiCoAnalyze');
add_action('admin_footer-post-new.php', 'aiCoAnalyze');
?>