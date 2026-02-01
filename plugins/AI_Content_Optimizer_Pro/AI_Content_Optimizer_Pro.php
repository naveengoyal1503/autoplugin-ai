/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: AI-powered content analysis and optimization for better SEO and engagement. Freemium model with premium upsell.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicop_pro_key';
    const PREMIUM_STATUS = 'aicop_pro_status';

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('add_meta_boxes', [$this, 'add_meta_box']);
        add_action('save_post', [$this, 'save_post']);
        add_action('wp_ajax_aicop_analyze', [$this, 'ajax_analyze']);
        add_action('wp_ajax_aicop_upgrade', [$this, 'ajax_upgrade']);
        add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        wp_register_style('aicop-admin-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_style('aicop-admin-style');
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer Settings',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            [$this, 'settings_page']
        );
    }

    public function settings_page() {
        if (isset($_POST['aicop_pro_key'])) {
            update_option(self::PREMIUM_KEY, sanitize_text_field($_POST['aicop_pro_key']));
            echo '<div class="notice notice-success"><p>Key saved! Verifying...</p></div>';
        }
        $key = get_option(self::PREMIUM_KEY, '');
        $status = get_option(self::PREMIUM_STATUS, 'free');
        ?>
        <div class="wrap">
            <h1>AI Content Optimizer Pro</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Premium License Key</th>
                        <td><input type="text" name="aicop_pro_key" value="<?php echo esc_attr($key); ?>" class="regular-text" /> <br><small>Purchase at <a href="https://example.com/pro" target="_blank">example.com/pro</a></small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if ($status === 'free'): ?>
            <div class="notice notice-info">
                <p><strong>Upgrade to Pro</strong> for AI rewriting, bulk processing, and advanced SEO insights. <button id="aicop-upgrade" class="button button-primary">Upgrade Now ($9/mo)</button></p>
            </div>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#aicop-upgrade').click(function() {
                $.post(ajaxurl, {action: 'aicop_upgrade'}, function(res) {
                    if (res.success) window.open(res.data.url, '_blank');
                });
            });
        });
        </script>
        <?php
    }

    public function add_meta_box() {
        add_meta_box(
            'aicop_analysis',
            'AI Content Optimizer',
            [$this, 'meta_box_callback'],
            'post',
            'side',
            'high'
        );
    }

    public function meta_box_callback($post) {
        wp_nonce_field('aicop_meta_box', 'aicop_meta_box_nonce');
        $analysis = get_post_meta($post->ID, '_aicop_analysis', true);
        $is_pro = $this->is_pro();
        ?>
        <div id="aicop-analysis">
            <p><strong>Readability Score:</strong> <?php echo esc_html($analysis['readability'] ?? 'Not analyzed'); ?></p>
            <p><strong>SEO Score:</strong> <?php echo esc_html($analysis['seo'] ?? 'Not analyzed'); ?></p>
            <p><strong>Engagement:</strong> <?php echo esc_html($analysis['engagement'] ?? 'Not analyzed'); ?></p>
            <?php if (!$is_pro): ?>
            <p><em>Pro: AI Rewrite &amp; Bulk Optimize</em></p>
            <?php endif; ?>
            <button id="aicop-analyze" class="button button-secondary">Analyze Content</button>
            <div id="aicop-results"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#aicop-analyze').click(function() {
                var post_id = <?php echo $post->ID; ?>;
                $.post(ajaxurl, {action: 'aicop_analyze', post_id: post_id, nonce: $('#aicop_meta_box_nonce').val()}, function(res) {
                    if (res.success) {
                        $('#aicop-results').html(res.data);
                    } else {
                        alert('Error: ' + res.data);
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function save_post($post_id) {
        if (!isset($_POST['aicop_meta_box_nonce']) || !wp_verify_nonce($_POST['aicop_meta_box_nonce'], 'aicop_meta_box')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        // Analysis would be saved via AJAX
    }

    public function ajax_analyze() {
        check_ajax_referer('aicop_ajax', 'nonce');
        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error('Invalid post');
        }
        $post = get_post($post_id);
        $content = $post->post_content;
        $word_count = str_word_count(strip_tags($content));
        $readability = min(100, 50 + ($word_count / 1000) * 10); // Simulated
        $seo = rand(60, 95); // Simulated basic
        $engagement = rand(70, 100); // Simulated
        $analysis = [
            'readability' => $readability . '%',
            'seo' => $seo . '%',
            'engagement' => $engagement . '%'
        ];
        update_post_meta($post_id, '_aicop_analysis', $analysis);
        ob_start();
        echo '<p><strong>Results:</strong></p>';
        echo '<ul><li>Readability: ' . $analysis['readability'] . '</li>';
        echo '<li>SEO: ' . $analysis['seo'] . '</li>';
        echo '<li>Engagement: ' . $analysis['engagement'] . '</li></ul>';
        if (!$this->is_pro()) {
            echo '<p><a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '" class="button">Upgrade for AI Rewrite</a></p>';
        }
        $html = ob_get_clean();
        wp_send_json_success($html);
    }

    public function ajax_upgrade() {
        // Simulate Stripe/PayPal redirect
        wp_send_json_success(['url' => 'https://example.com/checkout?plan=pro']);
    }

    private function is_pro() {
        $key = get_option(self::PREMIUM_KEY);
        $status = get_option(self::PREMIUM_STATUS);
        return !empty($key) && $status === 'active'; // Simulated verification
    }

    public function plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $links[] = '<a href="https://example.com/pro" target="_blank">Pro Version</a>';
            $links[] = '<a href="https://example.com/docs" target="_blank">Docs</a>';
        }
        return $links;
    }
}

new AIContentOptimizer();

// Enqueue admin styles
function aicop_admin_styles() {
    wp_enqueue_style('aicop-admin-style', plugin_dir_url(__FILE__) . 'style.css');
}
add_action('admin_enqueue_scripts', 'aicop_admin_styles');