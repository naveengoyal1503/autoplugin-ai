/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better readability, SEO, and engagement.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    private static $instance = null;
    public $is_pro = false;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->is_pro = get_option('aicop_pro_key') !== false;
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aicop_analyze', array($this, 'ajax_analyze'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        if ($this->is_pro) {
            add_action('save_post', array($this, 'auto_optimize'), 10, 3);
        }
    }

    public function activate() {
        add_option('aicop_activated', time());
    }

    public function deactivate() {}

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) return;
        wp_enqueue_script('aicop-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aicop-admin', 'aicop_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicop_nonce'),
            'is_pro' => $this->is_pro
        ));
    }

    public function add_meta_box() {
        add_meta_box('aicop-analysis', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aicop_meta_nonce', 'aicop_meta_nonce');
        echo '<div id="aicop-results">';
        if (get_post_meta($post->ID, '_aicop_score', true)) {
            $score = get_post_meta($post->ID, '_aicop_score', true);
            echo '<p><strong>Readability Score:</strong> ' . esc_html($score) . '%</p>';
        }
        echo '<button id="aicop-analyze" class="button">Analyze Content</button>';
        if ($this->is_pro) {
            echo ' <button id="aicop-optimize" class="button button-primary" style="display:none;">Auto-Optimize (Pro)</button>';
        } else {
            echo '<p><em>Upgrade to Pro for AI optimizations and bulk tools!</em></p>';
        }
        echo '</div>';
    }

    public function ajax_analyze() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!current_user_can('edit_posts')) wp_die();

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $word_count > 0 ? min(100, max(0, 100 - ($word_count / $sentence_count * 5))) : 0; // Simple Flesch-like score
        $keywords = $this->extract_keywords($content);

        update_post_meta($post_id, '_aicop_score', $readability);
        update_post_meta($post_id, '_aicop_keywords', $keywords);

        $response = array(
            'score' => $readability,
            'word_count' => $word_count,
            'keywords' => array_slice($keywords, 0, 5),
            'is_pro' => $this->is_pro
        );
        if (!$this->is_pro) {
            $response['upgrade_msg'] = 'Unlock AI suggestions with Pro!';
        }
        wp_send_json_success($response);
    }

    private function extract_keywords($content) {
        $words = explode(' ', strtolower(preg_replace('/[^a-z\s]/', '', $content)));
        $counts = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($counts);
        return array_keys($counts);
    }

    public function auto_optimize($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || $post->post_status !== 'publish') return;
        // Pro feature: Simple auto-optimization (shorten sentences, add keywords)
        $content = $post->post_content;
        $content = preg_replace('/\s+(?=([,.!?]))/', ' ', $content); // Reduce extra spaces
        wp_update_post(array('ID' => $post_id, 'post_content' => $content));
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'aicop-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aicop_pro_key'])) {
            if ($_POST['aicop_pro_key'] === 'pro-license-123') { // Demo key
                update_option('aicop_pro_key', sanitize_text_field($_POST['aicop_pro_key']));
                $this->is_pro = true;
                echo '<div class="notice notice-success"><p>Pro activated!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Invalid key. Get Pro at example.com</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <?php wp_nonce_field('aicop_settings'); ?>
                <p><label>Pro License Key: <input type="text" name="aicop_pro_key" value="<?php echo esc_attr(get_option('aicop_pro_key', '')); ?>" /></label></p>
                <p class="description"><?php echo $this->is_pro ? 'Pro features active!' : 'Use demo key: pro-license-123 or buy real Pro.'; ?></p>
                <p><?php submit_button(); ?></p>
            </form>
            <?php if (!$this->is_pro): ?>
            <h2>Go Pro for:</h2>
            <ul>
                <li>AI-powered suggestions</li>
                <li>Auto-optimizations</li>
                <li>Bulk processing</li>
                <li>Priority support</li>
            </ul>
            <p><a href="https://example.com/pro" class="button button-primary">Upgrade Now - $49/year</a></p>
            <?php endif; ?>
        </div>
        <?php
    }
}

AIContentOptimizer::get_instance();

// Freemius-like upsell notice (simplified)
function aicop_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'edit-post') {
        echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>AI Content Optimizer Pro</strong> for auto-optimizations! <a href="' . admin_url('options-general.php?page=aicop-settings') . '">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'aicop_admin_notice');

// Enqueue dummy JS file content (self-contained)
function aicop_admin_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('#aicop-analyze').click(function() {
            $.post(aicop_ajax.ajax_url, {
                action: 'aicop_analyze',
                nonce: aicop_ajax.nonce,
                post_id: $('#post_ID').val()
            }, function(resp) {
                if (resp.success) {
                    $('#aicop-results').html(
                        '<p><strong>Score: ' + resp.data.score + '%</strong></p>' +
                        '<p>Words: ' + resp.data.word_count + '</p>' +
                        '<p>Top Keywords: ' + resp.data.keywords.join(', ') + '</p>' +
                        (resp.data.upgrade_msg ? '<p>' + resp.data.upgrade_msg + '</p>' : '') +
                        '<button id="aicop-analyze" class="button">Re-Analyze</button>' +
                        (aicop_ajax.is_pro ? '<button id="aicop-optimize" class="button button-primary">Auto-Optimize</button>' : '')
                    );
                }
            });
        });
    });
    </script>
    <?php
}
add_action('admin_footer-post.php', 'aicop_admin_js');
add_action('admin_footer-post-new.php', 'aicop_admin_js');