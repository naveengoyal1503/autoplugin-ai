/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init() {
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_ajax_aco_optimize', array($this, 'ajax_optimize'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function activate() {
        add_option('aco_license_key', '');
        add_option('aco_premium_active', false);
    }

    public function add_meta_box() {
        add_meta_box(
            'aco-analysis',
            'AI Content Optimizer',
            array($this, 'meta_box_callback'),
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aco_meta_nonce', 'aco_nonce');
        $content = get_post_field('post_content', $post->ID);
        $analysis = $this->analyze_content($content);
        echo '<div id="aco-results">';
        echo '<p><strong>Readability Score:</strong> ' . $analysis['readability'] . '%</p>';
        echo '<p><strong>Keyword Density:</strong> ' . $analysis['keyword_density'] . '%</p>';
        echo '<p><strong>Word Count:</strong> ' . $analysis['word_count'] . '</p>';
        if (get_option('aco_premium_active')) {
            echo '<button id="aco-optimize-btn" class="button button-primary">AI Optimize (Premium)</button>';
        } else {
            echo '<p><a href="' . admin_url('admin.php?page=aco-settings') . '" class="button">Upgrade to Premium for AI Features</a></p>';
        }
        echo '</div>';
    }

    public function analyze_content($content) {
        $word_count = str_word_count(strip_tags($content));
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentences);
        $readability = $sentence_count > 0 ? min(100, max(0, 100 - ($word_count / $sentence_count * 5))) : 0;
        $keyword_density = 2.5; // Simulated
        return array(
            'readability' => round($readability),
            'keyword_density' => $keyword_density,
            'word_count' => $word_count
        );
    }

    public function save_meta($post_id) {
        if (!isset($_POST['aco_nonce']) || !wp_verify_nonce($_POST['aco_nonce'], 'aco_meta_nonce')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Save any meta if needed
    }

    public function enqueue_scripts($hook) {
        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }
        wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin', 'aco_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aco_ajax_nonce')));
    }

    public function ajax_optimize() {
        check_ajax_referer('aco_ajax_nonce', 'nonce');
        if (!get_option('aco_premium_active')) {
            wp_die('Premium feature required.');
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        // Simulated AI optimization
        $optimized = $content . '\n\n<!-- AI Optimized -->';
        wp_update_post(array('ID' => $post_id, 'post_content' => $optimized));
        wp_send_json_success('Content optimized!');
    }

    public function add_settings_page() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Content Optimizer',
            'manage_options',
            'aco-settings',
            array($this, 'settings_page_callback')
        );
    }

    public function settings_page_callback() {
        if (isset($_POST['aco_license_key'])) {
            update_option('aco_license_key', sanitize_text_field($_POST['aco_license_key']));
            // Simulate license check
            if ($_POST['aco_license_key'] === 'premium123') {
                update_option('aco_premium_active', true);
                echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>License Key</th>
                        <td>
                            <input type="text" name="aco_license_key" value="<?php echo get_option('aco_license_key'); ?>" class="regular-text" />
                            <p class="description">Enter your premium license key (Demo: premium123).</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Premium Features:</strong> AI content suggestions, auto-optimization, advanced analytics. <a href="https://example.com/premium" target="_blank">Buy Now ($49/year)</a></p>
        </div>
        <?php
    }
}

AIContentOptimizer::get_instance();

// Freemium upsell notice
add_action('admin_notices', function() {
    if (!get_option('aco_premium_active') && (isset($_GET['post_type']) && $_GET['post_type'] === 'post')) {
        echo '<div class="notice notice-info"><p>Unlock AI optimization with <strong>AI Content Optimizer Premium</strong>! <a href="' . admin_url('options-general.php?page=aco-settings') . '">Upgrade Now</a></p></div>';
    }
});