/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO using AI-powered insights. Freemium model with premium upgrades.
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
        add_action('init', array($this, 'init'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('wp_ajax_aco_analyze_content', array($this, 'analyze_content'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            $this->check_premium();
        }
    }

    public function enqueue_scripts() {
        if (is_singular('post')) {
            wp_enqueue_script('aco-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_enqueue_scripts($hook) {
        if ('post.php' === $hook || 'post-new.php' === $hook) {
            wp_enqueue_script('aco-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_localize_script('aco-admin', 'aco_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('aco_nonce'),
                'is_premium' => $this->is_premium()
            ));
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'aco-content-analysis',
            __('AI Content Optimizer', 'ai-content-optimizer'),
            array($this, 'render_meta_box'),
            'post',
            'side',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('aco_meta_box', 'aco_meta_box_nonce');
        $content = get_post_field('post_content', $post->ID);
        echo '<div id="aco-analysis-result">';
        if ($this->is_premium()) {
            echo '<button id="aco-analyze-btn" class="button button-primary">' . __('Analyze Content (Premium)', 'ai-content-optimizer') . '</button>';
        } else {
            echo '<p>' . __('Basic analysis: ') . $this->basic_analysis($content) . '</p>';
            echo '<p><a href="' . $this->get_premium_url() . '" target="_blank">' . __('Upgrade to Premium for AI Analysis & Rewrites', 'ai-content-optimizer') . '</a></p>';
        }
        echo '</div>';
    }

    private function basic_analysis($content) {
        $word_count = str_word_count(strip_tags($content));
        $has_headings = preg_match('/<h[1-6]/', $content);
        $score = ($word_count > 300 && $has_headings) ? 75 : 50;
        return sprintf(__('SEO Score: %d%% (Free version limited to basic checks)', 'ai-content-optimizer'), $score);
    }

    public function analyze_content() {
        check_ajax_referer('aco_nonce', 'nonce');
        if (!$this->is_premium()) {
            wp_die(__('Premium feature required.', 'ai-content-optimizer'));
        }
        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);
        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $analysis = array(
            'score' => rand(60, 95),
            'suggestions' => array(
                'Add more keywords',
                'Improve readability',
                'Add internal links'
            ),
            'rewrite' => $this->mock_rewrite($content)
        );
        wp_send_json_success($analysis);
    }

    private function mock_rewrite($content) {
        return substr($content, 0, 200) . '... (Premium AI Rewrite)';
    }

    public function add_settings_page() {
        add_options_page(
            __('AI Content Optimizer', 'ai-content-optimizer'),
            __('ACO Pro', 'ai-content-optimizer'),
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['aco_license_key'])) {
            update_option('aco_license_key', sanitize_text_field($_POST['aco_license_key']));
            echo '<div class="notice notice-success"><p>' . __('License activated!', 'ai-content-optimizer') . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Settings', 'ai-content-optimizer'); ?></h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Premium License Key', 'ai-content-optimizer'); ?></th>
                        <td>
                            <input type="text" name="aco_license_key" value="<?php echo esc_attr(get_option('aco_license_key')); ?>" class="regular-text" />
                            <p class="description"><?php _e('Enter your premium license key to unlock advanced features.', 'ai-content-optimizer'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); />
            </form>
            <?php if (!$this->is_premium()) : ?>
            <div class="card">
                <h2><?php _e('Go Premium!', 'ai-content-optimizer'); ?></h2>
                <p><?php _e('Unlock unlimited AI analysis, content rewriting, and priority support for $9/month.', 'ai-content-optimizer'); ?></p>
                <a href="<?php echo $this->get_premium_url(); ?>" class="button button-primary button-large" target="_blank"><?php _e('Upgrade Now', 'ai-content-optimizer'); ?></a>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private function is_premium() {
        $license = get_option('aco_license_key');
        return !empty($license) && hash('sha256', $license) === 'premium_verified_hash'; // Mock validation
    }

    private function get_premium_url() {
        return 'https://example.com/premium-upgrade'; // Replace with real premium sales page
    }

    private function check_premium() {
        if (!$this->is_premium()) {
            add_action('admin_notices', array($this, 'premium_nag'));
        }
    }

    public function premium_nag() {
        echo '<div class="notice notice-info"><p>';
        printf(
            __('Upgrade to %sAI Content Optimizer Pro%s for unlimited features!', 'ai-content-optimizer'),
            '<a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">',
            '</a>'
        );
        echo '</p></div>';
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

AIContentOptimizer::get_instance();

// Create assets directories if they don't exist (for JS/CSS)
$upload_dir = wp_upload_dir();
$assets_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($assets_dir)) {
    wp_mkdir_p($assets_dir);
}

// Minimal JS files (base64 encoded for single file)
file_put_contents($assets_dir . '/admin.js', "jQuery(document).ready(function($){ $('#aco-analyze-btn').click(function(){ $.post(aco_ajax.ajax_url, {action:'aco_analyze_content', post_id: $('input[name=\"post_ID\"]').val(), nonce: aco_ajax.nonce}, function(res){ if(res.success){ $('#aco-analysis-result').html('<p>Score: '+res.data.score+'%</p><ul><li>'+res.data.suggestions.join('</li><li>')+'</li></ul><p>AI Rewrite: '+res.data.rewrite+'</p>'); } }); }); });");
file_put_contents($assets_dir . '/frontend.js', "console.log('ACO Frontend loaded');");

?>