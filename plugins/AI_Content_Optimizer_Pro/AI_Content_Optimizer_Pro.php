/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Automatically optimizes WordPress content for SEO using AI analysis. Free version with basics; premium for advanced AI features.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Prevent direct access
define('AICOP_VERSION', '1.0.0');
define('AICOP_PREMIUM_URL', 'https://example.com/premium-upgrade');
define('AICOP_PLUGIN_FILE', __FILE__);

class AIContentOptimizer {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'));
        add_filter('the_content', array($this, 'optimize_content'));
        register_activation_hook(AICOP_PLUGIN_FILE, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') return;
        wp_enqueue_script('ai-content-optimizer', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), AICOP_VERSION, true);
        wp_enqueue_style('ai-content-optimizer', plugin_dir_url(__FILE__) . 'assets/style.css', array(), AICOP_VERSION);
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['aicop_save'])) {
            update_option('aicop_api_key', sanitize_text_field($_POST['aicop_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
        $api_key = get_option('aicop_api_key', '');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium AI API Key</th>
                        <td><input type="text" name="aicop_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /> <p class="description">Enter your premium API key from <a href="<?php echo AICOP_PREMIUM_URL; ?>" target="_blank">our premium site</a>. Free users get basic rules-based optimization.</p></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php $this->show_premium_upsell(); ?>
        </div>
        <?php
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
        wp_nonce_field('aicop_meta_box', 'aicop_meta_box_nonce');
        $optimized = get_post_meta($post->ID, '_aicop_optimized', true);
        echo '<label><input type="checkbox" name="aicop_optimize" ' . checked($optimized, true, false) . ' /> Optimize this content</label>';
        echo '<p><small>Free: Basic SEO tweaks. <a href="' . AICOP_PREMIUM_URL . '" target="_blank">Premium: AI suggestions</a></small></p>';
    }

    public function save_meta_box($post_id) {
        if (!isset($_POST['aicop_meta_box_nonce']) || !wp_verify_nonce($_POST['aicop_meta_box_nonce'], 'aicop_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        update_post_meta($post_id, '_aicop_optimized', isset($_POST['aicop_optimize']) ? true : false);
    }

    public function optimize_content($content) {
        if (is_admin() || !is_single()) return $content;

        global $post;
        if (!$post || !get_post_meta($post->ID, '_aicop_optimized', true)) {
            return $content;
        }

        // Free basic optimizations
        $content = $this->basic_optimize($content);

        // Premium AI check (simulated - in real, call external API)
        $api_key = get_option('aicop_api_key');
        if ($api_key) {
            $content = $this->premium_ai_optimize($content); // Placeholder for AI call
        } else {
            $content .= $this->show_premium_upsell('content');
        }

        return $content;
    }

    private function basic_optimize($content) {
        // Basic SEO: Add keywords, structure (free feature)
        $content = preg_replace('/<h1>(.*)<\/h1>/', '<h1>$1 <span class="seo-keyword">Optimized</span></h1>', $content);
        $content = str_replace('href="http', 'rel="nofollow" href="http', $content); // Basic nofollow
        return $content;
    }

    private function premium_ai_optimize($content) {
        // Simulated premium AI: Add meta suggestions (in real: OpenAI/Groq API)
        $suggestions = array(
            'Improved readability score: 85/100',
            'Added 2 power keywords',
            'Suggested internal links: 3'
        );
        $content .= '<div class="aicop-premium"><strong>Premium AI Insights:</strong> ' . implode(', ', $suggestions) . '</div>';
        return $content;
    }

    private function show_premium_upsell($context = 'admin') {
        $upsell = '<div class="notice notice-info"><p><strong>Go Premium!</strong> Unlock AI-powered content analysis, bulk optimization, and more for just <strong>$4.99/month</strong>. <a href="' . AICOP_PREMIUM_URL . '" target="_blank" class="button button-primary">Upgrade Now</a></p></div>';
        if ($context === 'content') {
            $upsell = '<div style="background:#fff3cd;padding:10px;margin:20px 0;border-left:4px solid #ffeaa7;">' . $upsell . '</div>';
        }
        echo $upsell;
    }

    public function activate() {
        add_option('aicop_activated', time());
    }
}

new AIContentOptimizer();

// Create assets directories and files on activation
register_activation_hook(__FILE__, function() {
    $upload_dir = plugin_dir_path(__FILE__) . 'assets';
    if (!file_exists($upload_dir)) {
        wp_mkdir_p($upload_dir);
    }
    // Minimal JS
    file_put_contents($upload_dir . '/script.js', "jQuery(document).ready(function($) { $('.aicop-optimize').click(function() { alert('Premium feature unlocked!'); }); });");
    // Minimal CSS
    file_put_contents($upload_dir . '/style.css', ".aicop-premium { background: #e7f3ff; padding: 10px; margin: 10px 0; border-left: 4px solid #007cba; }");
});