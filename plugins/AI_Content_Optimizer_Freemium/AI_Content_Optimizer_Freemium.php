/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability. Freemium version with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentOptimizer {
    const PREMIUM_KEY = 'ai_content_optimizer_premium';
    const FREE_LIMIT = 5;

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_post']);
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('wp_ajax_aco_analyze', [$this, 'ajax_analyze']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_settings_link']);
    }

    public function init() {
        wp_register_style('aco-admin', plugin_dir_url(__FILE__) . 'aco-style.css', [], '1.0');
        wp_register_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-script.js', ['jquery'], '1.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'limit' => self::FREE_LIMIT
        ]);
    }

    public function add_meta_box() {
        add_meta_box('aco-analysis', 'AI Content Optimizer', [$this, 'meta_box_content'], 'post', 'side', 'high');
        add_meta_box('aco-analysis', 'AI Content Optimizer', [$this, 'meta_box_content'], 'page', 'side', 'high');
    }

    public function meta_box_content($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $analysis = get_post_meta($post->ID, '_aco_analysis', true);
        $count = get_option('aco_free_count', 0);
        $is_premium = $this->is_premium();
        echo '<div id="aco-meta"><p>Analyses used this month: <strong>' . $count . '/' . self::FREE_LIMIT . '</strong></p>';
        if (!$is_premium && $count >= self::FREE_LIMIT) {
            echo '<p><strong>Upgrade to premium for unlimited analyses!</strong> <a href="https://example.com/premium" target="_blank">Get Premium</a></p>';
        } else {
            echo '<button id="aco-analyze" class="button button-primary">Analyze Content</button>';
            if ($analysis) {
                echo '<div id="aco-result"><h4>Score: ' . $analysis['score'] . '%</h4><ul>';
                foreach ($analysis['tips'] as $tip) echo '<li>' . esc_html($tip) . '</li>';
                echo '</ul></div>';
            }
        }
        echo '</div>';
    }

    public function save_post($post_id) {
        if (!isset($_POST['aco_meta_box_nonce']) || !wp_verify_nonce($_POST['aco_meta_box_nonce'], 'aco_meta_box')) return;
        // Analysis saved via AJAX
    }

    public function ajax_analyze() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!$this->is_premium()) {
            $count = get_option('aco_free_count', 0);
            if ($count >= self::FREE_LIMIT) {
                wp_die(json_encode(['error' => 'Free limit reached. Upgrade to premium!']));
            }
            update_option('aco_free_count', $count + 1);
        }
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        // Simulated AI analysis (in real version, integrate OpenAI API or similar for premium)
        $word_count = str_word_count($content);
        $score = min(100, 50 + ($word_count / 10));
        $tips = [
            'Improve readability: Aim for 150-200 words per sentence block.',
            'SEO: Include keyword in first 100 words.',
            'Length: ' . ($word_count < 1000 ? 'Add more content for better engagement.' : 'Good length!'),
            'Premium: Unlock auto-optimization and keyword suggestions.'
        ];
        if (!$this->is_premium()) {
            $tips[] = 'Upgrade for advanced AI insights and auto-fixes.';
        }
        $analysis = ['score' => $score, 'tips' => $tips];
        update_post_meta($post_id, '_aco_analysis', $analysis);
        wp_die(json_encode($analysis));
    }

    private function is_premium() {
        return get_option(self::PREMIUM_KEY) === 'activated';
    }

    public function add_settings_page() {
        add_options_page('AI Content Optimizer', 'AI Content Optimizer', 'manage_options', 'ai-content-optimizer', [$this, 'settings_page']);
    }

    public function settings_page() {
        if (isset($_POST['aco_premium_key'])) {
            if ($_POST['aco_premium_key'] === 'premium123') { // Simulated license check
                update_option(self::PREMIUM_KEY, 'activated');
                echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
            }
        }
        $is_premium = $this->is_premium();
        echo '<div class="wrap"><h1>AI Content Optimizer Settings</h1>';
        echo '<form method="post"><table class="form-table">';
        echo '<tr><th>Premium Key</th><td><input type="text" name="aco_premium_key" value="" placeholder="Enter premium key" /> <p class="description">Get your key at <a href="https://example.com/premium" target="_blank">example.com/premium</a></p></td></tr>';
        echo '</table><p><input type="submit" class="button-primary" value="Activate Premium" /></p></form>';
        if ($is_premium) {
            echo '<p><strong>Premium active! Unlimited analyses and advanced features unlocked.</strong></p>';
        } else {
            echo '<p><strong>Free version limited to ' . self::FREE_LIMIT . ' analyses/month. <a href="https://example.com/premium" target="_blank">Upgrade now</a> for $9/month.</strong></p>';
        }
        echo '<p>Reset free count: <a href="?page=ai-content-optimizer&reset=1">Reset</a></p>';
        if (isset($_GET['reset'])) update_option('aco_free_count', 0);
        echo '</div>';
    }

    public function add_settings_link($links) {
        $links[] = '<a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Settings</a>';
        $links[] = '<a href="https://example.com/premium" target="_blank">Premium</a>';
        return $links;
    }
}

new AIContentOptimizer();

// Enqueue styles/scripts only on post edit screens
function aco_enqueue_assets($hook) {
    if (in_array($hook, ['post.php', 'post-new.php'])) {
        wp_enqueue_style('aco-admin');
        wp_enqueue_script('aco-admin');
    }
}
add_action('admin_enqueue_scripts', 'aco_enqueue_assets');

/*
Placeholder for CSS/JS files - In production, create aco-style.css and aco-script.js
#aco-analyze { width: 100%; margin: 5px 0; }
#aco-result { background: #f9f9f9; padding: 10px; margin-top: 10px; }
*/
?>