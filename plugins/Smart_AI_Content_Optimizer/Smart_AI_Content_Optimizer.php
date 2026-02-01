/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart AI Content Optimizer
 * Plugin URI: https://example.com/smart-ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAIContentOptimizer {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->is_premium = get_option('saico_premium_key') !== false;
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_saico_analyze', array($this, 'ajax_analyze'));
        add_action('admin_notices', array($this, 'premium_notice'));
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('saico-admin', plugin_dir_url(__FILE__) . 'saico-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('saico-admin', 'saico_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('saico_nonce'),
            'is_premium' => $this->is_premium
        ));
    }

    public function add_meta_box() {
        add_meta_box('saico-analysis', 'AI Content Optimizer', array($this, 'meta_box_html'), 'post', 'side', 'high');
        add_meta_box('saico-analysis', 'AI Content Optimizer', array($this, 'meta_box_html'), 'page', 'side', 'high');
    }

    public function meta_box_html($post) {
        wp_nonce_field('saico_meta_box', 'saico_meta_box_nonce');
        $analysis = get_post_meta($post->ID, '_saico_analysis', true);
        echo '<div id="saico-results">';
        if ($analysis) {
            echo '<p><strong>Score:</strong> ' . esc_html($analysis['score']) . '%</p>';
            echo '<p><strong>SEO:</strong> ' . esc_html($analysis['seo']) . '</p>';
            echo '<p><strong>Readability:</strong> ' . esc_html($analysis['readability']) . '</p>';
        }
        echo '<button id="saico-analyze" class="button button-primary">Analyze Content</button>';
        echo '<div id="saico-loading" style="display:none;">Analyzing...</div>';
        if (!$this->is_premium) {
            echo '<p style="color:orange;"><strong>Premium:</strong> Unlock AI suggestions & auto-fix for $4.99/mo! <a href="https://example.com/premium" target="_blank">Upgrade</a></p>';
        }
        echo '</div>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['saico_meta_box_nonce']) || !wp_verify_nonce($_POST['saico_meta_box_nonce'], 'saico_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function ajax_analyze() {
        check_ajax_referer('saico_nonce', 'nonce');
        if (!$this->is_premium && get_transient('saico_free_limit_' . get_current_user_id()) > 5) {
            wp_die('Free limit reached. Upgrade to premium!');
        }

        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));

        // Simple mock AI analysis (in real plugin, integrate OpenAI API or similar)
        $word_count = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $word_count > 0 ? round(180 - 120 * ($sentence_count / $word_count), 1) : 0;
        $seo_score = min(100, round(($word_count / 10) * 5));
        $score = round(($readability + $seo_score) / 2);

        $analysis = array(
            'score' => $score,
            'seo' => $word_count > 300 ? 'Good' : 'Improve keywords',
            'readability' => $readability > 60 ? 'Good' : 'Simplify sentences'
        );

        update_post_meta($post_id, '_saico_analysis', $analysis);

        if (!$this->is_premium) {
            set_transient('saico_free_limit_' . get_current_user_id(), (get_transient('saico_free_limit_' . get_current_user_id()) ?: 0) + 1, DAY_IN_SECONDS);
        }

        wp_send_json_success($analysis);
    }

    public function premium_notice() {
        if (!$this->is_premium && current_user_can('manage_options')) {
            echo '<div class="notice notice-info"><p>Unlock <strong>Smart AI Content Optimizer Premium</strong> for AI suggestions, unlimited scans & more! <a href="https://example.com/premium">Get it now ($4.99/mo)</a></p></div>';
        }
    }

    public function activate() {
        // Activation code
    }

    public function deactivate() {
        // Deactivation code
    }
}

SmartAIContentOptimizer::get_instance();

// Premium key check (mock)
if (isset($_POST['saico_premium_key']) && wp_verify_nonce($_POST['saico_nonce'], 'saico_premium')) {
    update_option('saico_premium_key', sanitize_text_field($_POST['saico_premium_key']));
}

// Admin menu for premium (mock)
add_action('admin_menu', function() {
    add_options_page('Smart AI Optimizer', 'AI Optimizer', 'manage_options', 'saico-premium', function() {
        echo '<div class="wrap"><h1>Premium Upgrade</h1><p>Enter key or <a href="https://example.com/premium">buy now</a>.</p><form method="post">';
        wp_nonce_field('saico_premium');
        echo '<input type="text" name="saico_premium_key" placeholder="Premium Key"><input type="submit" class="button-primary" value="Activate">';
        echo '</form></div>';
    });
});

// JS file content would be enqueued, but for single file, inline it
add_action('admin_footer', function() {
    if (isset($_GET['post_type']) && ($_GET['post_type'] === 'post' || $_GET['post_type'] === 'page')) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#saico-analyze').click(function() {
                var btn = $(this);
                var loading = $('#saico-loading');
                btn.prop('disabled', true);
                loading.show();
                $.post(saico_ajax.ajax_url, {
                    action: 'saico_analyze',
                    nonce: saico_ajax.nonce,
                    post_id: $('#post_ID').val()
                }, function(resp) {
                    if (resp.success) {
                        $('#saico-results').html(
                            '<p><strong>Score:</strong> ' + resp.data.score + '%</p>' +
                            '<p><strong>SEO:</strong> ' + resp.data.seo + '</p>' +
                            '<p><strong>Readability:</strong> ' + resp.data.readability + '</p>'
                        );
                    } else {
                        alert(resp.data);
                    }
                    btn.prop('disabled', false);
                    loading.hide();
                });
            });
        });
        </script>
        <?php
    }
});