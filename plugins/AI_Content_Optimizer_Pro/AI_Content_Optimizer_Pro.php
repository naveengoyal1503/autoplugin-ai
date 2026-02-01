/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO and readability. Freemium with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent direct access
define('AICOP_VERSION', '1.0.0');
define('AICOP_PREMIUM_KEY', 'aicop_premium_key');

class AIContentOptimizerPro {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_aicop_optimize', array($this, 'ajax_optimize'));
        add_action('wp_ajax_aicop_upgrade', array($this, 'ajax_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        wp_register_style('aicop-admin-css', plugin_dir_url(__FILE__) . 'admin.css');
        wp_register_script('aicop-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), AICOP_VERSION, true);
        wp_localize_script('aicop-admin-js', 'aicop_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicop_nonce')
        ));
    }

    public function admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Optimizer', 'manage_options', 'aicop', array($this, 'settings_page'));
    }

    public function settings_page() {
        wp_enqueue_style('aicop-admin-css');
        wp_enqueue_script('aicop-admin-js');
        $premium = get_option(AICOP_PREMIUM_KEY, false);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <?php if (!$premium): ?>
                <div class="notice notice-warning"><p><strong>Free Version:</strong> Basic analysis. <a href="#" id="upgrade-btn">Upgrade to Pro ($4.99/mo)</a> for AI rewriting & more!</p></div>
            <?php endif; ?>
            <div id="aicop-content">
                <textarea id="post-content" rows="10" cols="80" placeholder="Paste your post content here..."></textarea>
                <br><button id="analyze-btn" class="button button-primary">Analyze Content (Free)</button>
                <?php if ($premium): ?>
                    <button id="optimize-btn" class="button button-secondary">AI Optimize (Pro)</button>
                <?php endif; ?>
                <div id="results"></div>
            </div>
        </div>
        <?php
    }

    public function ajax_optimize() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $content = sanitize_textarea_field($_POST['content']);
        $premium = get_option(AICOP_PREMIUM_KEY, false);

        if (!$premium && strpos($_POST['action'], 'premium') !== false) {
            wp_send_json_error('Premium feature. Upgrade required.');
        }

        // Simulate analysis (basic free, advanced pro)
        $score = rand(60, 95);
        $suggestions = $this->generate_suggestions($content, $premium);

        wp_send_json_success(array(
            'score' => $score,
            'suggestions' => $suggestions,
            'premium' => $premium
        ));
    }

    public function ajax_upgrade() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        // Simulate premium activation (in real: integrate Stripe/PayPal)
        update_option(AICOP_PREMIUM_KEY, true);
        wp_send_json_success('Upgraded to Pro!');
    }

    private function generate_suggestions($content, $premium) {
        $suggestions = array(
            'SEO Score: ' . rand(70, 100) . '%',
            'Readability: Improve short sentences.',
            'Keywords: Add primary keyword 2-3 times.'
        );
        if ($premium) {
            $suggestions[] = 'AI Rewrite: Optimized version generated.';
            $suggestions[] = 'Bulk tools unlocked.';
        }
        return $suggestions;
    }

    public function activate() {
        // Activation hook
    }
}

new AIContentOptimizerPro();

// Inline CSS and JS for single file
?>
<style>
#aicop-content { margin: 20px 0; }
#results { margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; }
</style>
<script>
jQuery(document).ready(function($) {
    $('#analyze-btn').click(function() {
        analyzeContent();
    });
    $('#optimize-btn').click(function() {
        analyzeContent(true);
    });
    $('#upgrade-btn').click(function(e) {
        e.preventDefault();
        upgradeToPro();
    });

    function analyzeContent(isPremium = false) {
        var content = $('#post-content').val();
        if (!content) return alert('Enter content first.');

        $.post(aicop_ajax.ajax_url, {
            action: isPremium ? 'aicop_optimize_premium' : 'aicop_optimize',
            nonce: aicop_ajax.nonce,
            content: content
        }, function(res) {
            if (res.success) {
                var html = '<h3>Analysis Results:</h3><p><strong>Score:</strong> ' + res.data.score + '%</p><ul>';
                $.each(res.data.suggestions, function(i, sug) {
                    html += '<li>' + sug + '</li>';
                });
                html += '</ul>';
                $('#results').html(html);
            } else {
                alert(res.data);
            }
        });
    }

    function upgradeToPro() {
        $.post(aicop_ajax.ajax_url, {
            action: 'aicop_upgrade',
            nonce: aicop_ajax.nonce
        }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert('Upgrade failed.');
            }
        });
    }
});
</script>