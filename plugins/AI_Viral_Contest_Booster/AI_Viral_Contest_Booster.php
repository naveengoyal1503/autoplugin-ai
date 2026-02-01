/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Viral_Contest_Booster.php
*/
<?php
/**
 * Plugin Name: AI Viral Contest Booster
 * Plugin URI: https://example.com/ai-viral-contest-booster
 * Description: AI-powered viral contests to grow your email list and traffic.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-viral-contest-booster
 */

if (!defined('ABSPATH')) exit;

class AI_Viral_Contest_Booster {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('ai_contest', [$this, 'contest_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-contest-css', plugin_dir_url(__FILE__) . 'style.css', [], '1.0');
        wp_enqueue_script('ai-contest-js', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.0', true);
    }

    public function admin_menu() {
        add_options_page('AI Viral Contest', 'AI Contest', 'manage_options', 'ai-viral-contest', [$this, 'settings_page']);
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_contest_settings', $_POST['settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = get_option('ai_contest_settings', []);
        ?>
        <div class="wrap">
            <h1>AI Viral Contest Booster Settings</h1>
            <form method="post">
                <?php wp_nonce_field('ai_contest_save'); ?>
                <table class="form-table">
                    <tr>
                        <th>Prize Text</th>
                        <td><input type="text" name="settings[prize]" value="<?php echo esc_attr($settings['prize'] ?? 'iPhone 15'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Email Placeholder</th>
                        <td><input type="text" name="settings[email_placeholder]" value="<?php echo esc_attr($settings['email_placeholder'] ?? 'Enter your email'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Max Entries (Free)</th>
                        <td><input type="number" name="settings[max_entries]" value="<?php echo esc_attr($settings['max_entries'] ?? 100); ?>" /></td>
                    </tr>
                </table>
                <p class="submit"><input type="submit" name="submit" class="button-primary" value="Save Settings" /></p>
            </form>
        </div>
        <?php
    }

    public function contest_shortcode($atts) {
        $atts = shortcode_atts(['id' => 'default'], $atts);
        $settings = get_option('ai_contest_settings', []);
        $entries = get_option('ai_contest_entries_' . $atts['id'], []);
        $count = count($entries);

        ob_start();
        ?>
        <div id="ai-contest-<?php echo esc_attr($atts['id']); ?>" class="ai-contest-container" data-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="contest-header">
                <h2>🚀 Win <?php echo esc_html($settings['prize'] ?? 'Amazing Prize'); ?>!</h2>
                <p>Enter now for a chance to win. <?php echo $count; ?> entries so far!</p>
            </div>
            <form class="contest-form">
                <input type="email" placeholder="<?php echo esc_attr($settings['email_placeholder'] ?? 'Enter your email'); ?>" required>
                <button type="submit" class="contest-submit">Enter Now 🎉</button>
            </form>
            <div class="contest-actions">
                <button class="share-facebook">Share on Facebook</button>
                <button class="share-twitter">Tweet</button>
            </div>
            <div class="contest-stats">Viral Score: <span class="viral-score">0</span></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AI_Viral_Contest_Booster();

// Pro check (simulate freemium)
function is_pro_version() {
    return false; // Change to true for pro
}

// AJAX handler
add_action('wp_ajax_ai_contest_entry', 'ai_contest_ajax_entry');
add_action('wp_ajax_nopriv_ai_contest_entry', 'ai_contest_ajax_entry');

function ai_contest_ajax_entry() {
    check_ajax_referer('ai_contest_nonce', 'nonce');
    $id = sanitize_text_field($_POST['id']);
    $email = sanitize_email($_POST['email']);
    if (!$email) wp_die('Invalid email');

    $entries = get_option('ai_contest_entries_' . $id, []);
    $max = get_option('ai_contest_settings', [])['max_entries'] ?? 100;
    if (count($entries) >= $max && !is_pro_version()) {
        wp_send_json_error('Free limit reached. Upgrade to Pro!');
    }

    $entries[] = $email;
    update_option('ai_contest_entries_' . $id, $entries);
    wp_send_json_success(['count' => count($entries)]);
}

// Minimal CSS (inline for single file)
add_action('wp_head', function() { ?>
<style>
.ai-contest-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 2px solid #007cba; border-radius: 10px; background: #f9f9f9; font-family: Arial; }
.contest-header h2 { color: #007cba; margin: 0 0 10px; }
.contest-form input { width: 100%; padding: 10px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
.contest-submit { width: 100%; padding: 12px; background: #007cba; color: white; border: none; border-radius: 5px; font-size: 16px; cursor: pointer; }
.contest-submit:hover { background: #005a87; }
.contest-actions { margin: 15px 0; }
.contest-actions button { display: block; width: 100%; margin: 5px 0; padding: 8px; background: #4267b2; color: white; border: none; border-radius: 5px; cursor: pointer; }
.share-twitter { background: #1da1f2; }
.viral-score { font-weight: bold; color: #28a745; }
</style>
<?php });

// Minimal JS (inline)
add_action('wp_footer', function() { ?>
<script>jQuery(document).ready(function($) {
    $('.contest-form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this), $container = $form.closest('.ai-contest-container');
        $.post(ajaxurl, {
            action: 'ai_contest_entry',
            nonce: '<?php echo wp_create_nonce("ai_contest_nonce"); ?>',
            id: $container.data('id'),
            email: $form.find('input[type="email"]').val()
        }, function(res) {
            if (res.success) {
                $container.find('.contest-stats span').text(res.data.count * 2); // Fake viral boost
                $form.html('<p style="color: green;">Entry added! Share to boost chances! 🎉</p>');
            } else {
                alert(res.data);
            }
        });
    });
    $('.share-facebook, .share-twitter').on('click', function() {
        alert('Sharing enabled in Pro version!');
    });
});</script>
<?php });