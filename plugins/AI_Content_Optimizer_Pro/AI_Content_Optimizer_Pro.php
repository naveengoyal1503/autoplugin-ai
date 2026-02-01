/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability. Free basic features; premium for advanced AI tools.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Prevent direct access
class AIContentOptimizerPro {
    private static $instance = null;
    private $is_premium = false;
    private $license_key = '';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_post'));
        add_action('wp_ajax_aco_optimize', array($this, 'ajax_optimize'));
        add_action('wp_ajax_aco_upgrade', array($this, 'ajax_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->is_premium = get_option('aco_premium_active', false);
        $this->license_key = get_option('aco_license_key', '');
        wp_enqueue_script('aco-admin-js', plugin_dir_url(__FILE__) . 'aco-admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('aco-admin-js', 'aco_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aco_nonce'),
            'is_premium' => $this->is_premium
        ));
    }

    public function activate() {
        add_option('aco_premium_active', false);
    }

    public function add_admin_menu() {
        add_options_page('AI Content Optimizer', 'AI Content Optimizer', 'manage_options', 'ai-content-optimizer', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aco_license_key'])) {
            update_option('aco_license_key', sanitize_text_field($_POST['aco_license_key']));
            if ($this->validate_license($_POST['aco_license_key'])) {
                update_option('aco_premium_active', true);
                echo '<div class="notice notice-success"><p>Premium activated!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Invalid license key.</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro Settings</h1>
            <form method="post">
                <p><label>License Key (Premium):</label> <input type="text" name="aco_license_key" value="<?php echo esc_attr($this->license_key); ?>" /></p>
                <?php submit_button(); ?>
            </form>
            <p><a href="https://example.com/premium" target="_blank">Upgrade to Premium ($4.99/month)</a></p>
        </div>
        <?php
    }

    private function validate_license($key) {
        // Simulate license validation (in real: API call)
        return hash('sha256', $key) === 'demo_valid_key_hash'; // Replace with real validation
    }

    public function add_meta_box() {
        add_meta_box('aco_optimize', 'AI Content Optimizer', array($this, 'meta_box_content'), 'post', 'side');
    }

    public function meta_box_content($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_nonce');
        $optimized_score = get_post_meta($post->ID, '_aco_score', true);
        echo '<p>SEO Score: <strong>' . ($optimized_score ?: 'Not analyzed') . '%</strong></p>';
        echo '<button id="aco-analyze" class="button">Analyze Content</button>';
        if ($this->is_premium) {
            echo '<button id="aco-optimize-pro" class="button button-primary" disabled>AI Optimize (Premium)</button>';
        } else {
            echo '<p><em>Premium: AI Rewrite & Bulk Optimize</em></p>';
        }
    }

    public function save_post($post_id) {
        if (!isset($_POST['aco_meta_nonce']) || !wp_verify_nonce($_POST['aco_meta_nonce'], 'aco_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    public function ajax_optimize() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!$this->is_premium && $_POST['action'] === 'aco_optimize' && isset($_POST['premium'])) {
            wp_die(json_encode(array('error' => 'Premium required')));
        }
        $post_id = intval($_POST['post_id']);
        $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
        $word_count = str_word_count($content);
        $score = min(100, 50 + ($word_count / 10) + (rand(0, 30))); // Simulated basic analysis
        update_post_meta($post_id, '_aco_score', $score);
        if ($this->is_premium && isset($_POST['optimize'])) {
            // Simulated AI optimization
            $optimized = $this->simulate_ai_optimize($content);
            wp_update_post(array('ID' => $post_id, 'post_content' => $optimized));
        }
        wp_die(json_encode(array('score' => $score)));
    }

    public function ajax_upgrade() {
        check_ajax_referer('aco_nonce', 'nonce');
        // Simulate upgrade nudge
        wp_die(json_encode(array('message' => 'Upgrade to premium for AI features!')));
    }

    private function simulate_ai_optimize($content) {
        // Simulated AI rewrite (in real: integrate OpenAI API or similar)
        return $content . '\n\n*Optimized by AI Content Optimizer Pro*';
    }
}

// Single file plugin - enqueue dummy JS
add_action('admin_enqueue_scripts', function() {
    wp_add_inline_script('jquery', 
        "jQuery(document).ready(function($){
            $('#aco-analyze').click(function(){
                $.post(aco_ajax.ajaxurl, {
                    action: 'aco_optimize',
                    nonce: aco_ajax.nonce,
                    post_id: $(this).closest('.postbox').find('input[name=\"post_ID\"]').val()
                }, function(res){
                    if(res.score) {
                        $(this).closest('.postbox').find('strong').text(res.score + '%');
                    }
                });
            });
            $('#aco-optimize-pro').click(function(){
                alert('Premium feature!');
            });
        });"
    );
});

AIContentOptimizerPro::get_instance();