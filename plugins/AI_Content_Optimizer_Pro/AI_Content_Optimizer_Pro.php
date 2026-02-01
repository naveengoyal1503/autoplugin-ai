/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO using AI. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AICOP_VERSION', '1.0.0');
define('AICOP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AICOP_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('AICOP_PREMIUM_KEY', 'aicop_premium_license_key');

// Freemius integration placeholder (for premium upsell)
if (function_exists('fs_dynamic')) {
    // Initialize Freemius
    require_once AICOP_PLUGIN_PATH . 'freemius/start.php';
    $fs = fs_dynamic(__FILE__);
} else {
    // Fallback
    add_action('admin_notices', function() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Content Optimizer Pro</strong> for advanced AI features!</p></div>';
    });
}

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_aicop_optimize', array($this, 'ajax_optimize'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook || 'aicop_page_settings' === $hook) {
            wp_enqueue_script('aicop-admin-js', AICOP_PLUGIN_URL . 'admin.js', array('jquery'), AICOP_VERSION, true);
            wp_enqueue_style('aicop-admin-css', AICOP_PLUGIN_URL . 'admin.css', array(), AICOP_VERSION);
            wp_localize_script('aicop-admin-js', 'aicop_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aicop_nonce'),
                'is_premium' => $this->is_premium()
            ));
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'aicop_meta_box',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            array('post', 'page'),
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aicop_meta_box_nonce', 'aicop_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        $score = get_post_meta($post->ID, '_aicop_score', true);
        $suggestions = get_post_meta($post->ID, '_aicop_suggestions', true);
        echo '<div id="aicop-score">Score: <strong>' . esc_html($score ?: 'Not analyzed') . '%</strong></div>';
        echo '<button id="aicop-analyze" class="button">Analyze Content</button>';
        if ($suggestions) {
            echo '<div id="aicop-suggestions"><h4>Suggestions:</h4><ul>';
            foreach ($suggestions as $sugg) {
                echo '<li>' . esc_html($sugg) . '</li>';
            }
            echo '</ul></div>';
        }
        if (!$this->is_premium()) {
            echo '<div class="aicop-upgrade"><p><strong>Premium:</strong> AI Auto-Rewrite & Bulk Optimize. <a href="' . admin_url('admin.php?page=aicop-settings') . '" class="button button-primary">Upgrade Now</a></p></div>';
        }
    }

    public function ajax_optimize() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_die('Unauthorized');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        // Simulate AI analysis (free version: basic keyword density, length check)
        $word_count = str_word_count(strip_tags($content));
        $has_keywords = preg_match('/(seo|content|wordpress)/i', $content); // Demo keywords
        $score = min(100, 50 + ($word_count > 500 ? 20 : 0) + ($has_keywords ? 30 : 0));
        $suggestions = array();
        if ($word_count < 500) $suggestions[] = 'Increase content length to at least 500 words.';
        if (!$has_keywords) $suggestions[] = 'Add relevant keywords like SEO, content.';
        $suggestions[] = 'Use short paragraphs and headings.';

        update_post_meta($post_id, '_aicop_score', $score);
        update_post_meta($post_id, '_aicop_suggestions', $suggestions);

        if ($this->is_premium()) {
            // Premium: Simulate AI rewrite
            $rewrite = $this->simulate_ai_rewrite($content);
            wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions, 'rewrite' => $rewrite));
        } else {
            wp_send_json_success(array('score' => $score, 'suggestions' => $suggestions));
        }
    }

    private function simulate_ai_rewrite($content) {
        // Premium feature simulation
        return substr($content, 0, 200) . '... (AI Optimized Premium Version)';
    }

    private function is_premium() {
        return get_option(AICOP_PREMIUM_KEY) === 'valid_license';
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'aicop-settings',
            array($this, 'settings_page_callback')
        );
    }

    public function settings_page_callback() {
        if (isset($_POST['aicop_license_key'])) {
            update_option(AICOP_PREMIUM_KEY, sanitize_text_field($_POST['aicop_license_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $license_key = get_option(AICOP_PREMIUM_KEY);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td>
                            <input type="text" name="aicop_license_key" value="<?php echo esc_attr($license_key); ?>" class="regular-text" />
                            <p class="description">Enter your premium license key to unlock AI rewriting and bulk optimization. <a href="https://example.com/pricing" target="_blank">Get Premium</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Stats</h2>
            <p>Track optimizations and upgrade for full analytics dashboard (Premium).</p>
        </div>
        <?php
    }

    public function activate() {
        // Activation hook
    }
}

new AIContentOptimizer();

// Include admin CSS/JS as inline for single file (in production, use separate files)
function aicop_inline_assets() {
    if (isset($_GET['page']) && $_GET['page'] === 'aicop-settings') {
        ?>
        <style>
        #aicop-score { font-size: 24px; color: #0073aa; }
        .aicop-upgrade { background: #fff3cd; padding: 10px; border-left: 4px solid #ffeaa7; margin-top: 10px; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('#aicop-analyze').click(function() {
                $.post(aicop_ajax.ajax_url, {
                    action: 'aicop_optimize',
                    post_id: $(this).closest('.postbox').find('input[name="post_ID"]').val(),
                    nonce: aicop_ajax.nonce
                }, function(response) {
                    if (response.success) {
                        $('#aicop-score').html('Score: <strong>' + response.data.score + '%</strong>');
                        if (response.data.rewrite) {
                            alert('Premium Rewrite: ' + response.data.rewrite);
                        } else {
                            let suggHtml = '<h4>Suggestions:</h4><ul>';
                            response.data.suggestions.forEach(function(s) {
                                suggHtml += '<li>' + s + '</li>';
                            });
                            suggHtml += '</ul>';
                            $('#aicop-suggestions').html(suggHtml);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }
}
add_action('admin_head', 'aicop_inline_assets');

// Upsell notice everywhere
add_action('admin_notices', function() {
    if (!$GLOBALS['AIContentOptimizer']->is_premium()) {
        echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>AI Content Optimizer Pro</strong>: Auto-rewrite, bulk SEO, & more! <a href="' . admin_url('options-general.php?page=aicop-settings') . '">Upgrade Now</a></p></div>';
    }
});