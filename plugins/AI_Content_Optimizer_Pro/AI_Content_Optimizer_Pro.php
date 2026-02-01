/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement.
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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('add_meta_boxes_post', array($this, 'add_meta_box'));
        add_action('wp_ajax_analyze_content', array($this, 'analyze_content'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_optimizer_nonce'),
            'is_premium' => $this->is_premium()
        ));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="ai-optimizer-results">';
        echo '<button id="analyze-btn" class="button button-primary">Analyze Content</button>';
        echo '<div id="results"></div>';
        echo '<p><small><strong>Premium:</strong> Unlock AI rewrites & advanced SEO tips for $4.99/mo <a href="#" id="upgrade-link">Upgrade Now</a></small></p>';
        echo '</div>';
    }

    public function analyze_content() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);
        $word_count = str_word_count($content);
        $readability = $this->calculate_flesch_reading_ease($content);
        $keyword_density = $this->calculate_keyword_density($content, sanitize_text_field($_POST['keyword']));

        $results = array(
            'word_count' => $word_count,
            'readability' => round($readability, 2),
            'readability_grade' => $this->get_readability_grade($readability),
            'keyword_density' => $keyword_density,
            'premium_teaser' => !$this->is_premium() ? 'Upgrade for AI-powered rewrites and full SEO audit!' : 'Premium active!'
        );

        wp_send_json_success($results);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/s', $text);
        $sentence_count = max(1, $sentence_count);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = preg_replace('/[^a-z]/i', '', $text);
        $vowels = preg_match_all('/[aeiouy]/i', $text);
        return max(1, $vowels);
    }

    private function get_readability_grade($score) {
        if ($score > 90) return 'Very Easy';
        if ($score > 80) return 'Easy';
        if ($score > 70) return 'Fairly Easy';
        if ($score > 60) return 'Standard';
        if ($score > 50) return 'Fairly Difficult';
        if ($score > 30) return 'Difficult';
        return 'Very Difficult';
    }

    private function calculate_keyword_density($content, $keyword) {
        if (empty($keyword)) return 0;
        $word_count = str_word_count($content);
        preg_match_all('/\b' . preg_quote($keyword, '/') . '\b/ui', $content, $matches);
        $count = count($matches);
        return $word_count > 0 ? round(($count / $word_count) * 100, 2) : 0;
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['premium_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['premium_key']));
            echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <p>Enter Premium Key: <input type="text" name="premium_key" value="<?php echo esc_attr(get_option(self::PREMIUM_KEY)); ?>" /></p>
                <p><input type="submit" class="button-primary" value="Activate Premium" /></p>
            </form>
            <p><strong>Upgrade:</strong> Get your key at <a href="https://example.com/premium" target="_blank">example.com/premium</a> for $4.99/month.</p>
        </div>
        <?php
    }

    public function activate() {
        add_option('ai_optimizer_version', '1.0.0');
    }

    public function deactivate() {}
}

new AIContentOptimizer();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#analyze-btn').click(function() {
            var content = $('#content').val() || '';
            var keyword = prompt('Enter main keyword (optional):') || '';
            $.post(ai_optimizer.ajax_url, {
                action: 'analyze_content',
                nonce: ai_optimizer.nonce,
                content: content,
                keyword: keyword
            }, function(response) {
                if (response.success) {
                    var r = response.data;
                    $('#results').html(
                        '<p><strong>Words:</strong> ' + r.word_count + '</p>' +
                        '<p><strong>Readability:</strong> ' + r.readability + ' (' + r.readability_grade + ')</p>' +
                        '<p><strong>Keyword Density:</strong> ' + r.keyword_density + '%</p>' +
                        '<p>' + r.premium_teaser + '</p>'
                    );
                }
            });
        });
        $('#upgrade-link').click(function(e) {
            e.preventDefault();
            alert('Redirecting to premium upgrade... (Integrate Stripe/PayPal here for real monetization)');
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');
?>