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
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('wp_ajax_aco_upgrade', array($this, 'handle_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->is_pro = get_option('aco_pro_license_valid', false);
        if (current_user_can('edit_posts')) {
            add_action('add_meta_boxes', array($this, 'add_meta_box'));
        }
    }

    public function activate() {
        add_option('aco_pro_license_key', '');
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer Settings', 'AI Content Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function add_meta_box() {
        add_meta_box('ai-content-optimizer', 'AI Content Optimizer', array($this, 'meta_box_callback'), 'post', 'side', 'high');
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-results"></div>';
        echo '<button id="aco-analyze" class="button button-secondary">Analyze Content</button>';
        echo '<p><small>Free: Basic score. <strong>Pro:</strong> AI optimizations & rewrites ($9/mo).</small></p>';
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('#aco-analyze').click(function() {
                var content = $('#content').val() || '<?php echo esc_js($content); ?>';
                $.post(ajaxurl, {
                    action: 'aco_analyze_content',
                    content: content,
                    nonce: '<?php echo wp_create_nonce("aco_ajax"); ?>'
                }, function(response) {
                    $('#aco-results').html(response);
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_analyze_content() {
        check_ajax_referer('aco_ajax', 'nonce');
        $content = sanitize_textarea_field($_POST['content']);

        // Basic free analysis
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? round(180 - (1.43 * (120 / ($word_count / $sentence_count))), 2) : 0; // Approx Flesch score
        $score = min(100, max(0, ($readability / 2) + 30));

        $output = '<div class="notice notice-info"><p><strong>Readability Score:</strong> ' . $score . '/100</p>';
        $output .= '<p>Words: ' . $word_count . ' | Sentences: ' . $sentence_count . '</p>';

        if (!$this->is_pro) {
            $output .= '<p><em>Upgrade to Pro for AI-powered rewrites, SEO keywords, and engagement boosters!</em></p>';
            $output .= $this->get_upgrade_button();
        } else {
            // Pro feature: Simulated AI optimization
            $suggestions = $this->pro_ai_suggestions($content);
            $output .= '<p><strong>AI Suggestions:</strong><br>' . implode('<br>', $suggestions) . '</p>';
        }
        $output .= '</div>';

        echo $output;
        wp_die();
    }

    private function pro_ai_suggestions($content) {
        // Simulated AI suggestions (in real pro version, integrate OpenAI API)
        return [
            'Shorten long sentences for better flow.',
            'Add 2-3 target keywords like "WordPress plugin".',
            'Improve SEO: Use H2 tags and lists.',
            'Rewritten intro: "Discover how this plugin boosts your site!"'
        ];
    }

    private function get_upgrade_button() {
        return '<a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '" class="button button-primary">Upgrade to Pro</a>';
    }

    public function settings_page() {
        if (isset($_POST['aco_license_key'])) {
            $license_key = sanitize_text_key($_POST['aco_license_key']);
            // Simulate license validation (in real: API call to your server)
            $valid = substr($license_key, 0, 5) === 'PRO01';
            update_option('aco_pro_license_key', $license_key);
            update_option('aco_pro_license_valid', $valid);
            $this->is_pro = $valid;
            echo '<div class="notice notice-success"><p>License ' . ($valid ? 'activated!' : 'invalid.') . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>License Key</th>
                        <td><input type="text" name="aco_license_key" value="<?php echo esc_attr(get_option('aco_pro_license_key')); ?>" class="regular-text" placeholder="Enter PRO01XXXXX" /></td>
                    </tr>
                </table>
                <?php submit_button('Activate Pro'); ?>
            </form>
            <?php if ($this->is_pro) { echo '<p class="notice notice-success"><strong>Pro activated! Premium features unlocked.</strong></p>'; } ?>
            <h2>Features</h2>
            <ul>
                <li><strong>Free:</strong> Basic readability scoring, word/sentence count.</li>
                <li><strong>Pro:</strong> AI content rewriting, SEO keyword suggestions, engagement analysis, auto-optimizations.</li>
            </ul>
            <p><em>Pro pricing: $9/month or $99/year. <a href="#" onclick="alert('Redirect to Stripe checkout in full version');">Buy Now</a></em></p>
        </div>
        <?php
    }

    public function handle_upgrade() {
        // Placeholder for Stripe/PayPal integration
        wp_die('Upgrade handled via external gateway.');
    }
}

AIContentOptimizer::get_instance();

// Enqueue jQuery
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_script('jquery');
});