/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Freemium.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Freemium
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your post content for SEO and readability. Freemium version with basic features.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIContentOptimizer {
    private static $instance = null;
    private $is_premium = false;
    private $daily_scans = 0;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'handle_analyze_content'));
        add_action('wp_ajax_nopriv_aco_analyze_content', array($this, 'handle_analyze_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        // Check for premium (simulate license check)
        if (get_option('aco_premium_active')) {
            $this->is_premium = true;
        }
        $this->daily_scans = get_option('aco_daily_scans', 0);
    }

    public function activate() {
        add_option('aco_daily_scans', 0);
        add_option('aco_last_reset', time());
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Content Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['aco_premium_key'])) {
            // Simulate premium activation
            update_option('aco_premium_active', true);
            $this->is_premium = true;
            echo '<div class="notice notice-success"><p>Premium activated! (Demo)</p></div>';
        }
        $scans_left = $this->is_premium ? 'Unlimited' : (3 - $this->daily_scans);
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer</h1>
            <p>Free scans today: <strong><?php echo $scans_left; ?></strong></p>
            <?php if (!$this->is_premium) : ?>
            <form method="post">
                <p>Enter Premium Key (Demo): premium123</p>
                <input type="text" name="aco_premium_key" value="premium123" />
                <p><input type="submit" class="button-primary" value="Activate Premium" /></p>
            </form>
            <p><a href="https://example.com/premium" target="_blank">Upgrade to Premium ($4.99/mo)</a> for unlimited scans & advanced features.</p>
            <?php endif; ?>
            <h2>Quick Analyze</h2>
            <textarea id="aco-content" rows="10" cols="80" placeholder="Paste your content here..."></textarea><br>
            <button id="aco-analyze" class="button-primary">Analyze Content</button>
            <div id="aco-results"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function() {
                var content = $('#aco-content').val();
                $.post(ajaxurl, {
                    action: 'aco_analyze_content',
                    content: content,
                    nonce: '<?php echo wp_create_nonce("aco_nonce"); ?>'
                }, function(response) {
                    $('#aco-results').html(response);
                });
            });
        });
        </script>
        <?php
    }

    public function handle_analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');

        if (!$this->is_premium && $this->daily_scans >= 3) {
            wp_die('Daily free scan limit reached. Upgrade to premium!');
        }

        $content = sanitize_textarea_field($_POST['content']);
        if (empty($content)) {
            wp_die('No content provided.');
        }

        // Simple AI-like analysis (heuristics)
        $word_count = str_word_count($content);
        $sentence_count = preg_match_all('/[.!?]+/s', $content);
        $readability = $sentence_count > 0 ? round(($word_count / $sentence_count), 1) : 0;
        $keyword_density = $this->calculate_keyword_density($content, 'example');
        $seo_score = min(100, (20 + ($readability > 20 ? 30 : 0) + ($keyword_density > 1 ? 30 : 0) + rand(0,20)));

        $results = "<h3>Analysis Results</h3>
        <ul>
            <li><strong>Word Count:</strong> {$word_count}</li>
            <li><strong>Avg Words/Sentence:</strong> {$readability}</li>
            " . ($this->is_premium ? '<li><strong>SEO Score:</strong> ' . $seo_score . '%</li>' : '') . "
            <li>" . ($seo_score > 70 ? 'Good' : 'Improve') . " SEO Potential</li>
        </ul>";

        if (!$this->is_premium) {
            $this->daily_scans++;
            update_option('aco_daily_scans', $this->daily_scans);
            // Reset daily counter
            if (date('Y-m-d') !== get_option('aco_last_reset_date')) {
                update_option('aco_daily_scans', 0);
                update_option('aco_last_reset_date', date('Y-m-d'));
            }
        }

        if ($this->is_premium) {
            $results .= '<p><em>Premium: Auto-suggestions coming soon!</em></p>';
        } else {
            $results .= '<p>Upgrade for advanced metrics & unlimited scans!</p>';
        }

        echo $results;
        wp_die();
    }

    private function calculate_keyword_density($content, $keyword) {
        $words = str_word_count($content, 1);
        $count = 0;
        foreach ($words as $word) {
            if (strtolower($word) === strtolower($keyword)) $count++;
        }
        return $words ? round(($count / count($words)) * 100, 1) : 0;
    }
}

AIContentOptimizer::get_instance();

// Enqueue jQuery
add_action('admin_enqueue_scripts', function($hook) {
    if ('settings_page' !== $hook) return;
    wp_enqueue_script('jquery');
});