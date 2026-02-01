/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability with AI insights. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizerPro {
    const PREMIUM_KEY = 'aicop_pro_key';
    const PREMIUM_STATUS = 'aicop_pro_status';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_notices', array($this, 'premium_nag'));
        }
    }

    public function enqueue_scripts() {
        if (is_singular('post')) {
            wp_enqueue_script('aicop-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook === 'post.php' || $hook === 'post-new.php') {
            wp_enqueue_script('aicop-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('aicop-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.0.0');
        }
    }

    public function admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aicop_pro_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['aicop_pro_key']));
            update_option(self::PREMIUM_STATUS, 'valid');
            echo '<div class="notice notice-success"><p>Premium key activated!</p></div>';
        }
        $pro_key = get_option(self::PREMIUM_KEY, '');
        $pro_status = get_option(self::PREMIUM_STATUS, 'invalid');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="aicop_pro_key" value="<?php echo esc_attr($pro_key); ?>" class="regular-text" />
                            <p class="description">Enter your premium key from <a href="https://example.com/pricing" target="_blank">our site</a>. Free version limited to 5 scans/month.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if ($pro_status === 'invalid') : ?>
            <div class="notice notice-info">
                <p><strong>Upgrade to Pro:</strong> Unlimited scans, AI rewrites, and more! <a href="https://example.com/pricing" target="_blank">Get it now for $9/mo</a>.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function premium_nag() {
        if (get_option(self::PREMIUM_STATUS, 'invalid') === 'invalid' && current_user_can('manage_options')) {
            echo '<div class="notice notice-warning"><p>Unlock <strong>AI Content Optimizer Pro</strong> for advanced features! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade now</a>.</p></div>';
        }
    }

    public function add_meta_box() {
        add_meta_box('aicop-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aicop_meta_nonce', 'aicop_meta_nonce');
        $scans = get_post_meta($post->ID, '_aicop_scans', true);
        $is_pro = get_option(self::PREMIUM_STATUS, 'invalid') === 'valid';
        $scan_limit = $is_pro ? 'Unlimited' : '5/month';
        echo '<p><strong>Scans used:</strong> ' . intval($scans) . '</p>';
        echo '<p><strong>Plan:</strong> ' . ($is_pro ? 'Pro' : 'Free') . ' (' . $scan_limit . ')</p>';
        echo '<button id="aicop-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="aicop-results"></div>';
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aicop_meta_nonce']) || !wp_verify_nonce($_POST['aicop_meta_nonce'], 'aicop_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function analyze_content() {
        check_ajax_referer('aicop_ajax_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $scans = get_option('aicop_free_scans', 0);
        $is_pro = get_option(self::PREMIUM_STATUS, 'invalid') === 'valid';
        if (!$is_pro && $scans >= 5) {
            wp_send_json_error('Free limit reached. <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade to Pro</a>');
        }
        $post = get_post($post_id);
        $content = $post->post_content;
        $word_count = str_word_count(strip_tags($content));
        $readability = $this->calculate_flesch_reading_ease($content);
        $keywords = $this->extract_keywords($content);
        $score = min(100, 50 + ($readability / 2) + (count($keywords) / 10));
        $results = array(
            'score' => round($score, 1),
            'word_count' => $word_count,
            'readability' => round($readability, 1),
            'keywords' => array_slice($keywords, 0, 5),
            'suggestions' => $is_pro ? array('AI rewrite suggestion: Improve intro with hook.', 'Add more subheadings.') : array('Upgrade for detailed suggestions.'),
        );
        if (!$is_pro) {
            update_option('aicop_free_scans', $scans + 1);
        }
        update_post_meta($post_id, '_aicop_score', $score);
        wp_send_json_success($results);
    }

    private function calculate_flesch_reading_ease($text) {
        $sentence_count = preg_match_all('/[.!?]+/', $text);
        $word_count = str_word_count($text);
        $syllable_count = $this->count_syllables($text);
        if ($sentence_count == 0 || $word_count == 0) return 0;
        $asl = $word_count / $sentence_count;
        $asw = $syllable_count / $word_count;
        return 206.835 - (1.015 * $asl) - (84.6 * $asw);
    }

    private function count_syllables($text) {
        $text = strtolower(preg_replace('/[^a-z\s]/', '', $text));
        $count = 0;
        $words = explode(' ', $text);
        foreach ($words as $word) {
            $vowels = preg_match_all('/[aeiouy]/', $word);
            $count += max(1, $vowels);
        }
        return $count;
    }

    private function extract_keywords($text) {
        $words = explode(' ', strtolower(strip_tags($text)));
        $freq = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($freq);
        return array_keys($freq);
    }

    public function activate() {
        add_option('aicop_free_scans', 0);
    }
}

// AJAX handlers
add_action('wp_ajax_aicop_analyze', array(new AIContentOptimizerPro(), 'analyze_content'));

new AIContentOptimizerPro();

// Assets (base64 or simple placeholders; in real use, create /assets/ folder)
// Note: For full deployment, add actual JS/CSS files. Here, inline for single-file.
function aicop_inline_scripts($hook) {
    if ($hook === 'post.php' || $hook === 'post-new.php') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#aicop-analyze').click(function(e) {
                e.preventDefault();
                var postId = $('#post_ID').val();
                $.post(ajaxurl, {
                    action: 'aicop_analyze',
                    post_id: postId,
                    nonce: '<?php echo wp_create_nonce("aicop_ajax_nonce"); ?>'
                }, function(res) {
                    if (res.success) {
                        var html = '<p><strong>Score: ' + res.data.score + '/100</strong></p>' +
                                   '<p>Words: ' + res.data.word_count + '</p>' +
                                   '<p>Readability: ' + res.data.readability + '</p>' +
                                   '<ul>';
                        res.data.keywords.forEach(function(kw) { html += '<li>' + kw + '</li>'; });
                        html += '</ul><p>Suggestions: ' + res.data.suggestions.join(' ') + '</p>';
                        $('#aicop-results').html(html);
                    } else {
                        $('#aicop-results').html('<p class="error">' + res.data + '</p>');
                    }
                });
            });
        });
        </script>
        <style>
        #aicop-results { margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; }
        #aicop-results ul { margin: 0; }
        </style>
        <?php
    }
}
add_action('admin_footer', 'aicop_inline_scripts');
?>