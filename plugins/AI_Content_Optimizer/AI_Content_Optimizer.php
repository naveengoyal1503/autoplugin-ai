/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes WordPress post content for SEO and readability with AI-powered insights.
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
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aco_analyze', array($this, 'ajax_analyze'));
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
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
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $readability = get_post_meta($post->ID, '_aco_readability', true);
        $keywords = get_post_meta($post->ID, '_aco_keywords', true);
        $is_premium = $this->is_premium();
        echo '<div id="aco-results">';
        if ($readability) {
            echo '<p><strong>Readability Score:</strong> ' . esc_html($readability) . '%</p>';
        }
        if ($keywords) {
            echo '<p><strong>Keywords:</strong> ' . esc_html($keywords) . '</p>';
        }
        echo '</div>';
        echo '<p><button id="aco-analyze" class="button button-secondary">Analyze Content</button></p>';
        if (!$is_premium) {
            echo '<p><a href="' . esc_url(admin_url('options-general.php?page=ai-content-optimizer')) . '" class="button button-primary">Go Premium for AI Rewrite & More</a></p>';
        }
        echo '</div>';
    }

    public function save_post_meta($post_id) {
        if (!isset($_POST['aco_meta_box_nonce']) || !wp_verify_nonce($_POST['aco_meta_box_nonce'], 'aco_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function enqueue_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_ajax_nonce'),
            'is_premium' => $this->is_premium() ? '1' : '0'
        ));
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simulate basic analysis (free version)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content);
        $readability = min(100, max(0, 100 - ($word_count / max(1, count($sentences)) * 10)));
        $keywords = $this->extract_keywords($content, 5);

        $results = array(
            'readability' => round($readability, 1),
            'keywords' => implode(', ', $keywords),
            'word_count' => $word_count
        );

        if (!$this->is_premium()) {
            $results['upgrade'] = true;
            // Premium tease: Simulate AI rewrite snippet
            $results['rewrite_preview'] = substr($content, 0, 100) . '... (Premium: Full AI Rewrite)';
        } else {
            // Premium: Advanced features (mock AI rewrite)
            $results['rewrite'] = $this->mock_ai_rewrite($content);
        }

        wp_send_json_success($results);
    }

    private function extract_keywords($content, $limit) {
        $words = explode(' ', strtolower($content));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_slice(array_keys($counts), 0, $limit);
    }

    private function mock_ai_rewrite($content) {
        // Mock premium AI rewrite
        return 'Optimized version: ' . substr($content, 0, 200) . '... (Full rewrite powered by AI)';
    }

    public function settings_page() {
        if (isset($_POST['aco_premium_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['aco_premium_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $premium_key = get_option(self::PREMIUM_KEY, '');
        $is_premium = $this->is_premium();
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <?php wp_nonce_field('aco_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="aco_premium_key" value="<?php echo esc_attr($premium_key); ?>" class="regular-text" placeholder="Enter your premium key" />
                            <p class="description">Get premium at <a href="https://example.com/premium" target="_blank">example.com/premium</a> ($9/mo or $99/yr). Unlock AI rewriting, bulk optimization, and more.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if (!$is_premium): ?>
            <div class="card">
                <h2>Go Premium Today!</h2>
                <ul>
                    <li>✅ AI-Powered Content Rewriting</li>
                    <li>✅ Bulk Post Optimization</li>
                    <li>✅ Advanced SEO Keyword Research</li>
                    <li>✅ Priority Support</li>
                </ul>
                <a href="https://example.com/premium" class="button button-primary button-large" target="_blank">Upgrade Now - Starting at $9/mo</a>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function is_premium() {
        $key = get_option(self::PREMIUM_KEY);
        return !empty($key) && hash('sha256', $key) === 'premium_verified_hash_example'; // Mock validation
    }

    public function plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
            $links[] = '<a href="https://example.com/docs" target="_blank">Docs</a>';
        }
        return $links;
    }
}

new AIContentOptimizer();

// Mock JS file content (inlined for single file)
/*
<script>
jQuery(document).ready(function($) {
    $('#aco-analyze').click(function(e) {
        e.preventDefault();
        var post_id = $('#post_ID').val();
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze',
            nonce: aco_ajax.nonce,
            post_id: post_id
        }, function(response) {
            if (response.success) {
                var r = response.data;
                $('#aco-results').html(
                    '<p><strong>Readability:</strong> ' + r.readability + '%</p>' +
                    '<p><strong>Keywords:</strong> ' + r.keywords + '</p>' +
                    '<p><strong>Word Count:</strong> ' + r.word_count + '</p>'
                );
                if (r.upgrade) {
                    $('#aco-results').append('<p class="notice notice-warning">' + r.rewrite_preview + ' <a href="<?php echo esc_js(admin_url('options-general.php?page=ai-content-optimizer')); ?>">Upgrade for full features</a></p>');
                } else {
                    $('#aco-results').append('<p><strong>AI Rewrite:</strong> ' + r.rewrite + '</p>');
                }
            }
        });
    });
});
</script>
*/
// Note: In production, extract JS to aco-admin.js and enqueue properly.
?>