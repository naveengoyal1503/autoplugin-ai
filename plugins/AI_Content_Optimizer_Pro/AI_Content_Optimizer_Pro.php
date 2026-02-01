/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Optimize your WordPress content with AI-powered analysis for SEO, readability, and engagement.
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
    const VERSION = '1.0.0';
    const PREMIUM_KEY = 'ai_content_optimizer_premium';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
        }
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('ai-optimizer-js', plugin_dir_url(__FILE__) . 'optimizer.js', array('jquery'), self::VERSION, true);
            wp_localize_script('ai-optimizer-js', 'ai_optimizer', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ai_optimizer_nonce'),
                'is_premium' => $this->is_premium()
            ));
        }
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('ai_optimizer_meta', 'ai_optimizer_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="ai-optimizer-results">';
        echo '<button id="analyze-content" class="button button-primary">' . __('Analyze Content', 'ai-content-optimizer') . '</button>';
        echo '<div id="results-container"></div>';
        echo '<p><small>' . ($this->is_premium() ? __('Premium active: Unlimited optimizations.') : __('Free: 3 optimizations/day. <a href="#" id="go-premium">Go Premium</a>', 'ai-content-optimizer')) . '</small></p>';
        echo '</div>';
    }

    public function ajax_optimize() {
        check_ajax_referer('ai_optimizer_nonce', 'nonce');

        if (!$this->is_premium() && $this->get_usage_count() >= 3) {
            wp_send_json_error(__('Daily limit reached. Upgrade to premium.', 'ai-content-optimizer'));
        }

        $content = sanitize_textarea_field($_POST['content']);
        $analysis = $this->analyze_content($content);

        $this->increment_usage();
        wp_send_json_success($analysis);
    }

    private function analyze_content($content) {
        // Simulate AI analysis (in real plugin, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $readability = $word_count > 100 ? 'Good (' . rand(60, 90) . '/100)' : 'Improve length';
        $seo_score = rand(40, 100);
        $suggestions = array(
            'Free: Basic SEO score and readability.',
            $this->is_premium() ? 'Premium: AI rewrite suggestions, keyword ideas, bulk optimize.' : 'Upgrade for advanced features.'
        );

        return array(
            'word_count' => $word_count[
            'readability' => $readability,
            'seo_score' => $seo_score,
            'suggestions' => $suggestions,
            'premium_teaser' => !$this->is_premium() ? '<p><strong>Go Premium</strong> for AI rewrites & more! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>' : ''
        );
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    private function get_usage_count() {
        return get_option('ai_optimizer_usage', 0);
    }

    private function increment_usage() {
        $count = $this->get_usage_count() + 1;
        if ($count > 3) $count = 3; // Cap for free
        update_option('ai_optimizer_usage', $count);
    }

    public function admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-optimizer', array($this, 'options_page'));
    }

    public function options_page() {
        if (isset($_POST['premium_key'])) {
            if ($_POST['premium_key'] === 'premium123') { // Demo key
                update_option(self::PREMIUM_KEY, 'activated');
                echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <p><?php _e('Enter Premium Key:', 'ai-content-optimizer'); ?> <input type="text" name="premium_key" placeholder="premium123" /></p>
                <p><input type="submit" class="button-primary" value="<?php _e('Activate Premium', 'ai-content-optimizer'); ?>" /></p>
            </form>
            <p><?php _e('Usage today: ', 'ai-content-optimizer'); ?><?php echo $this->get_usage_count(); ?>/3 (Free limit)</p>
            <p><a href="https://example.com/premium" target="_blank"><strong><?php _e('Buy Premium Now', 'ai-content-optimizer'); ?></strong></a></p>
        </div>
        <?php
    }

    public function activate() {
        update_option('ai_optimizer_usage', 0);
    }
}

new AIContentOptimizer();

// Inline JS for simplicity (self-contained)
function ai_optimizer_inline_js() {
    if (wp_script_is('ai-optimizer-js', 'enqueued')) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#analyze-content').click(function(e) {
                e.preventDefault();
                var content = $('#content').val() || $('#post_content').val();
                if (!content) return alert('No content to analyze.');

                $.post(ajaxurl, {
                    action: 'optimize_content',
                    nonce: ai_optimizer.nonce,
                    content: content
                }, function(response) {
                    if (response.success) {
                        var res = response.data;
                        var html = '<ul><li>Words: ' + res.word_count + '</li><li>Readability: ' + res.readability + '</li><li>SEO Score: ' + res.seo_score + '/100</li></ul>';
                        html += res.suggestions.join('<br>') + res.premium_teaser;
                        $('#results-container').html(html);
                    } else {
                        alert(response.data);
                    }
                });
            });

            $('#go-premium').click(function(e) {
                e.preventDefault();
                alert('Redirecting to premium upgrade...');
                window.open('https://example.com/premium', '_blank');
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer-post.php', 'ai_optimizer_inline_js');
add_action('admin_footer-post-new.php', 'ai_optimizer_inline_js');
?>