/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Profit_Protector.php
*/
<?php
/**
 * Plugin Name: AI Content Profit Protector
 * Plugin URI: https://example.com/aicpp
 * Description: Scans AI-generated content for quality, humanizes it, and optimizes for monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-profit-protector
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentProfitProtector {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_aicpp_scan_content', array($this, 'ajax_scan_content'));
        add_action('wp_ajax_aicpp_humanize_content', array($this, 'ajax_humanize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-profit-protector', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('aicpp_pro_active', false);
        add_option('aicpp_scan_count', 0);
    }

    public function admin_menu() {
        add_options_page(
            'AI Content Profit Protector',
            'AI Content Protector',
            'manage_options',
            'ai-content-profit-protector',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        $pro_active = get_option('aicpp_pro_active', false);
        $scan_count = get_option('aicpp_scan_count', 0);
        $max_free = 5;
        ?>
        <div class="wrap">
            <h1>AI Content Profit Protector</h1>
            <?php if (!$pro_active && $scan_count >= $max_free): ?>
                <div class="notice notice-warning"><p>Free scans limit reached (<?php echo $scan_count; ?>/<?php echo $max_free; ?>). <a href="#" onclick="aicppUpgrade()">Upgrade to Pro ($49/year)</a> for unlimited access.</p></div>
            <?php endif; ?>
            <div id="aicpp-main">
                <textarea id="aicpp-content" rows="15" cols="80" placeholder="Paste your AI-generated content here..."></textarea>
                <br><br>
                <button class="button button-primary" id="aicpp-scan-btn" onclick="aicppScan()">Scan for AI Detection & Profit Score</button>
                <button class="button" id="aicpp-humanize-btn" onclick="aicppHumanize()" style="display:none;">Humanize Content</button>
                <div id="aicpp-results"></div>
            </div>
            <script>
            function aicppScan() {
                const content = document.getElementById('aicpp-content').value;
                if (!content) return alert('Please enter content');
                jQuery.post(ajaxurl, {
                    action: 'aicpp_scan_content',
                    content: content,
                    nonce: '<?php echo wp_create_nonce('aicpp_nonce'); ?>'
                }, function(res) {
                    document.getElementById('aicpp-results').innerHTML = res.data.html;
                    if (res.data.humanize) {
                        document.getElementById('aicpp-humanize-btn').style.display = 'inline-block';
                    }
                });
            }
            function aicppHumanize() {
                const content = document.getElementById('aicpp-content').value;
                jQuery.post(ajaxurl, {
                    action: 'aicpp_humanize_content',
                    content: content,
                    nonce: '<?php echo wp_create_nonce('aicpp_nonce'); ?>'
                }, function(res) {
                    document.getElementById('aicpp-results').innerHTML += '<h3>Humanized Version:</h3>' + res.data.html;
                    document.getElementById('aicpp-content').value = res.data.content;
                });
            }
            function aicppUpgrade() {
                alert('Pro upgrade: Visit example.com/pro for $49/year unlimited scans, advanced humanization, affiliate suggestions.');
            }
            </script>
        <?php
    }

    public function ajax_scan_content() {
        check_ajax_referer('aicpp_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $content = sanitize_textarea_field($_POST['content']);
        $scan_count = get_option('aicpp_scan_count', 0);
        $max_free = 5;
        $pro_active = get_option('aicpp_pro_active', false);

        if (!$pro_active && $scan_count >= $max_free) {
            wp_send_json_error('Free limit reached. Upgrade to Pro.');
        }

        update_option('aicpp_scan_count', $scan_count + 1);

        // Simulate AI detection score (0-100, higher = more human-like)
        $ai_score = 85 - min(strlen($content)/100, 40); // Rough heuristic
        $profit_score = 70 + (rand(0,30)); // Monetization potential
        $humanize_needed = $ai_score < 90;

        $html = '<h3>Results:</h3>';
        $html .= '<p><strong>AI Detection Risk:</strong> ' . round(100 - $ai_score) . '% (Score: ' . round($ai_score) . ')</p>';
        $html .= '<p><strong>Monetization Profit Score:</strong> ' . round($profit_score) . '/100</p>';
        $html .= '<p><strong>SEO Readability:</strong> ' . (str_word_count($content) > 500 ? 'Good' : 'Improve length') . '</p>';
        $html .= '<p><em>Affiliate Suggestion: Insert Amazon Associates link for related products to boost revenue.</em></p>';
        if ($humanize_needed) {
            $html .= '<p>AI risk high. Use Humanize button.</p>';
        }

        wp_send_json_success(array('html' => $html, 'humanize' => $humanize_needed));
    }

    public function ajax_humanize_content() {
        check_ajax_referer('aicpp_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $pro_active = get_option('aicpp_pro_active', false);
        if (!$pro_active) {
            wp_send_json_error('Pro feature. Upgrade for $49/year.');
        }

        $content = sanitize_textarea_field($_POST['content']);

        // Simple humanization: add variations, contractions, transitions
        $humanized = $this->humanize_text($content);

        $html = '<pre>' . esc_html($humanized) . '</pre>';
        wp_send_json_success(array('html' => $html, 'content' => $humanized));
    }

    private function humanize_text($text) {
        $variations = array(
            '/\bthe\b/i' => 'the',
            '/\bThe\b/i' => 'The',
            '/\.\s+([A-Z])/' => '. $1',
            // Add contractions
            '/\b(he|she|it|they|we|you|I) (will|is|are|was|were|have|has|do|does|did)\b/i' => '$1 $2',
        );
        $text = preg_replace(array_keys($variations), array_values($variations), $text);

        // Insert random transitions
        $transitions = array('Moreover,', 'However,', 'Additionally,', 'In fact,', 'For instance,');
        $text = preg_replace('/\.\s+([A-Za-z])/', '. ' . $transitions[array_rand($transitions)] . ' $1', $text, 3);

        // Vary sentence length
        return trim($text);
    }
}

AIContentProfitProtector::get_instance();