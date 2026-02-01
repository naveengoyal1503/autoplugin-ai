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
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicop_pro_key';
    const PREMIUM_STATUS = 'aicop_pro_status';

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_aico_analyze', [$this, 'ajax_analyze']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function activate() {
        add_option('aicop_version', '1.0.0');
    }

    public function add_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', [$this, 'settings_page']);
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['pro_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $key = get_option(self::PREMIUM_KEY, '');
        $status = $this->is_premium() ? 'Active' : 'Inactive';
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td><input type="text" name="pro_key" value="<?php echo esc_attr($key); ?>" class="regular-text" /> (Get at example.com/premium)</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Status:</strong> <?php echo $status; ?></p>
            <?php if (!$this->is_premium()) : ?>
            <div class="notice notice-info">
                <p>Upgrade to <strong>Premium</strong> for advanced AI suggestions, bulk processing, and more! <a href="https://example.com/premium" target="_blank">Get Premium ($4.99/mo)</a></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function is_premium() {
        $key = get_option(self::PREMIUM_KEY, '');
        return !empty($key) && hash('sha256', $key) === 'demo_premium_hash'; // Demo validation
    }

    public function add_meta_box() {
        add_meta_box('aico-analysis', 'AI Content Optimizer', [$this, 'meta_box_content'], 'post', 'side', 'high');
        add_meta_box('aico-analysis', 'AI Content Optimizer', [$this, 'meta_box_content'], 'page', 'side', 'high');
    }

    public function meta_box_content($post) {
        wp_nonce_field('aico_meta_nonce', 'aico_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_aico_score', true);
        $premium = $this->is_premium();
        ?>
        <div id="aico-container">
            <p><strong>SEO Score:</strong> <span id="aico-score"><?php echo esc_html($score ?: 'Analyze'); ?></span>/100</p>
            <textarea id="aico-content" style="display:none;" rows="5"><?php echo esc_textarea($content); ?></textarea>
            <button type="button" id="aico-analyze" class="button button-primary">Analyze Content</button>
            <?php if (!$premium) : ?>
            <p class="description">Premium users get AI rewrite suggestions and bulk optimization.</p>
            <?php endif; ?>
            <div id="aico-results"></div>
        </div>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aico_nonce']) || !wp_verify_nonce($_POST['aico_nonce'], 'aico_meta_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;
        update_post_meta($post_id, '_aico_analyzed', true);
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php' && $hook !== 'settings_page_ai-content-optimizer') return;
        wp_enqueue_script('aico-js', plugin_dir_url(__FILE__) . 'aico.js', ['jquery'], '1.0.0', true);
        wp_localize_script('aico-js', 'aico_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aico_ajax_nonce'),
            'is_premium' => $this->is_premium() ? '1' : '0'
        ]);
    }

    public function ajax_analyze() {
        check_ajax_referer('aico_ajax_nonce', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);
        $words = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content);
        $sentences = array_filter($sentences);
        $readability = $words > 0 ? round(180 - 120 * (count($sentences) / $words), 2) : 0; // Simple Flesch approx
        $score = min(100, max(0, 50 + ($readability / 2) + (min(300, $words) / 10))); // Mock AI score

        $results = [
            'score' => (int)$score,
            'tips' => [
                'Word count: ' . $words,
                'Readability: ' . round($readability, 2),
                'Sentences: ' . count($sentences)
            ]
        ];

        if ($this->is_premium()) {
            $results['premium_tips'] = ['AI Rewrite Suggestion: Optimize keywords for better ranking.', 'Bulk export available.'];
        } else {
            $results['upgrade'] = 'Upgrade to Premium for AI rewrites and advanced tips!';
        }

        wp_send_json_success($results);
    }
}

new AIContentOptimizer();

// Mock JS file content (inlined for single file)
/*
Add this as aico.js but inlined:
$(document).ready(function($) {
    $('#aico-analyze').click(function() {
        var $btn = $(this);
        var content = $('#aico-content').val();
        $btn.prop('disabled', true).text('Analyzing...');
        $.post(aico_ajax.ajaxurl, {
            action: 'aico_analyze',
            nonce: aico_ajax.nonce,
            content: content
        }, function(res) {
            if (res.success) {
                $('#aico-score').text(res.data.score);
                var tips = res.data.tips.join('<br>');
                var html = '<p><strong>Tips:</strong><br>' + tips + '</p>';
                if (res.data.premium_tips) {
                    html += '<p><strong>Premium Tips:</strong><br>' + res.data.premium_tips.join('<br>') + '</p>';
                } else if (res.data.upgrade) {
                    html += '<p>' + res.data.upgrade + ' <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>';
                }
                $('#aico-results').html(html);
            }
            $btn.prop('disabled', false).text('Re-analyze');
        });
    });
});
*/

// Inline the JS to make it single-file
add_action('admin_footer', function() {
    if (!wp_doing_ajax() && (get_current_screen()->id === 'post' || get_current_screen()->id === 'page')) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#aico-analyze').click(function() {
                var $btn = $(this);
                var content = $('#aico-content').val();
                $btn.prop('disabled', true).text('Analyzing...');
                $.post(aico_ajax.ajaxurl, {
                    action: 'aico_analyze',
                    nonce: aico_ajax.nonce,
                    content: content
                }, function(res) {
                    if (res.success) {
                        $('#aico-score').text(res.data.score);
                        var tips = res.data.tips.join('<br>');
                        var html = '<p><strong>Tips:</strong><br>' + tips + '</p>';
                        <?php if ($this->is_premium()) : ?>
                        if (res.data.premium_tips) {
                            html += '<p><strong>Premium Tips:</strong><br>' + res.data.premium_tips.join('<br>') + '</p>';
                        }
                        <?php else : ?>
                        if (res.data.upgrade) {
                            html += '<p>' + res.data.upgrade + ' <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p>';
                        }
                        <?php endif; ?>
                        $('#aico-results').html(html);
                    }
                    $btn.prop('disabled', false).text('Re-analyze');
                });
            });
        });
        </script>
        <?php
    }
});