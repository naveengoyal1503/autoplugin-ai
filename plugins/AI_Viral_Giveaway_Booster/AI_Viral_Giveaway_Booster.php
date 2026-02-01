/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Viral_Giveaway_Booster.php
*/
<?php
/**
 * Plugin Name: AI Viral Giveaway Booster
 * Plugin URI: https://example.com/aiviral-giveaway
 * Description: AI-powered viral giveaways to boost emails, traffic, and social followers.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-viral-giveaway
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIViralGiveawayBooster {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_giveaway', array($this, 'giveaway_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('ai_giveaway_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-giveaway-css', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('ai-giveaway-js', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('ai-giveaway-js', 'aiGiveaway', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('ai_giveaway')));
    }

    public function admin_menu() {
        add_options_page('AI Viral Giveaway', 'AI Giveaway', 'manage_options', 'ai-giveaway', array($this, 'admin_page'));
    }

    public function admin_page() {
        if (isset($_POST['ai_giveaway_submit'])) {
            update_option('ai_giveaway_prize', sanitize_text_field($_POST['prize']));
            update_option('ai_giveaway_email', sanitize_email($_POST['email']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $prize = get_option('ai_giveaway_prize', 'Free AI Plugin');
        $email = get_option('ai_giveaway_email', get_option('admin_email'));
        ?>
        <div class="wrap">
            <h1>AI Viral Giveaway Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Prize</th>
                        <td><input type="text" name="prize" value="<?php echo esc_attr($prize); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Notify Email</th>
                        <td><input type="email" name="email" value="<?php echo esc_attr($email); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p>Upgrade to Pro for AI-generated templates, unlimited entries, and analytics! <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function giveaway_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 'default'), $atts);
        $entries = get_option('ai_giveaway_entries_' . $atts['id'], array());
        $prize = get_option('ai_giveaway_prize', 'Free Gift');
        ob_start();
        ?>
        <div id="ai-giveaway-<?php echo esc_attr($atts['id']); ?>" class="ai-giveaway-container">
            <h3>Enter to Win: <strong><?php echo esc_html($prize); ?></strong></h3>
            <p>Join <?php echo count($entries); ?> others!</p>
            <form id="ai-entry-form-<?php echo esc_attr($atts['id']); ?>">
                <input type="email" name="email" placeholder="Your Email" required>
                <button type="submit">Enter Now!</button>
            </form>
            <div class="ai-actions">
                <label><input type="checkbox" name="share_fb" value="1"> Share on Facebook (+1 entry)</label><br>
                <label><input type="checkbox" name="share_tw" value="1"> Tweet (+1 entry)</label>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        flush_rewrite_rules();
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Unlock AI features with <a href="https://example.com/pro">AI Viral Giveaway Pro</a>!</p></div>';
    }
}

new AIViralGiveawayBooster();

// AJAX handler
add_action('wp_ajax_ai_enter_giveaway', 'ai_handle_entry');
add_action('wp_ajax_nopriv_ai_enter_giveaway', 'ai_handle_entry');

function ai_handle_entry() {
    check_ajax_referer('ai_giveaway', 'nonce');
    $id = sanitize_text_field($_POST['id']);
    $email = sanitize_email($_POST['email']);
    $entries = get_option('ai_giveaway_entries_' . $id, array());
    if (!in_array($email, $entries)) {
        $entries[] = $email;
        update_option('ai_giveaway_entries_' . $id, $entries);
        wp_send_json_success(array('entries' => count($entries)));
    } else {
        wp_send_json_error('Already entered!');
    }
}

// Minimal CSS
$css = '#ai-giveaway-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 2px solid #007cba; border-radius: 10px; text-align: center; background: #f9f9f9; } #ai-giveaway-container input { width: 70%; padding: 10px; margin: 10px 0; } #ai-giveaway-container button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; } .ai-actions { margin-top: 15px; font-size: 14px; }';
file_put_contents(plugin_dir_path(__FILE__) . 'style.css', $css);

// Minimal JS
$js = "jQuery(document).ready(function($) { $('.ai-giveaway-container form').on('submit', function(e) { e.preventDefault(); var form = $(this); var id = form.closest('.ai-giveaway-container').attr('id').replace('ai-giveaway-', ''); $.post(aiGiveaway.ajax_url, { action: 'ai_enter_giveaway', nonce: aiGiveaway.nonce, id: id, email: form.find('input[name="email"]').val() }, function(res) { if (res.success) { alert('Entered! Entries: ' + res.data.entries); } else { alert(res.data); } }); }); });";
file_put_contents(plugin_dir_path(__FILE__) . 'script.js', "<script>$js</script>");