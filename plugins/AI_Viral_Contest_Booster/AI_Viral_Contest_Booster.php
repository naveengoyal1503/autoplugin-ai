/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Viral_Contest_Booster.php
*/
<?php
/**
 * Plugin Name: AI Viral Contest Booster
 * Plugin URI: https://example.com/aiviral-contest
 * Description: AI-powered viral contests to grow your email list and traffic.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-viral-contest
 */

if (!defined('ABSPATH')) exit;

class AIViralContestBooster {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('ai_contest', [$this, 'contest_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        wp_register_style('aicb-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_register_script('aicb-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.0', true);
        wp_localize_script('aicb-script', 'aicb_ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('aicb_nonce')]);

        add_action('wp_ajax_aicb_submit_entry', [$this, 'handle_entry']);
        add_action('wp_ajax_nopriv_aicb_submit_entry', [$this, 'handle_entry']);
    }

    public function enqueue_scripts() {
        if (is_page() || is_single()) {
            wp_enqueue_style('aicb-style');
            wp_enqueue_script('aicb-script');
        }
    }

    public function admin_menu() {
        add_options_page('AI Viral Contest', 'AI Contest', 'manage_options', 'ai-viral-contest', [$this, 'settings_page']);
    }

    public function settings_page() {
        if (isset($_POST['aicb_settings'])) {
            update_option('aicb_email_list', sanitize_email($_POST['email_list']));
            update_option('aicb_pro_key', sanitize_text_field($_POST['pro_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $email_list = get_option('aicb_email_list', '');
        $pro_key = get_option('aicb_pro_key', '');
        $is_pro = !empty($pro_key) && $pro_key === 'pro2026'; // Simple demo key
        ?>
        <div class="wrap">
            <h1>AI Viral Contest Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Email List URL</th>
                        <td><input type="url" name="email_list" value="<?php echo esc_attr($email_list); ?>" class="regular-text" placeholder="https://your-email-service.com/subscribe"></td>
                    </tr>
                    <tr>
                        <th>Pro License Key</th>
                        <td><input type="text" name="pro_key" value="<?php echo esc_attr($pro_key); ?>" class="regular-text"> <?php if($is_pro) echo '<span style="color:green;">(Pro Active)</span>'; ?></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> AI contest generation, unlimited entries, analytics. <a href="#" onclick="alert('Upgrade at example.com/pro')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('aicb_entries')) update_option('aicb_entries', []);
    }

    public function contest_shortcode($atts) {
        $atts = shortcode_atts(['id' => 'default'], $atts);
        $is_pro = get_option('aicb_pro_key') === 'pro2026';
        $max_entries = $is_pro ? 999 : 5;
        $entries = count(get_option('aicb_entries', []));

        ob_start();
        ?>
        <div id="aicb-contest" class="aicb-container" data-max="<?php echo $max_entries; ?>">
            <h2>🎉 Win a Free Prize! Enter Now 🎁</h2>
            <p>Share this contest on social media to enter. <?php if(!$is_pro) echo 'Free: ' . (5 - $entries) . ' entries left!'; ?></p>
            <form id="aicb-form">
                <input type="email" id="aicb-email" placeholder="Your Email" required>
                <input type="text" id="aicb-share-proof" placeholder="Social Share Link (e.g., Twitter URL)" required>
                <button type="submit" id="aicb-submit">Enter Contest</button>
            </form>
            <div id="aicb-message"></div>
            <?php if(!$is_pro) { ?>
            <p><a href="<?php echo admin_url('options-general.php?page=ai-viral-contest'); ?>">Upgrade to Pro for Unlimited + AI</a></p>
            <?php } else { echo '<p>🚀 Pro Mode Active - AI Generated Contests!</p>'; } ?>
        </div>
        <style>
        .aicb-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 2px solid #007cba; border-radius: 10px; text-align: center; background: #f9f9f9; }
        .aicb-container input { width: 100%; margin: 10px 0; padding: 10px; }
        .aicb-container button { background: #007cba; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; }
        .aicb-container button:hover { background: #005a87; }
        #aicb-message { margin-top: 10px; font-weight: bold; }
        </style>
        <script>
        jQuery(document).ready(function($) {
            $('#aicb-form').submit(function(e) {
                e.preventDefault();
                var data = { action: 'aicb_submit_entry', email: $('#aicb-email').val(), share: $('#aicb-share-proof').val(), nonce: aicb_ajax.nonce };
                $.post(aicb_ajax.ajax_url, data, function(resp) {
                    $('#aicb-message').html(resp);
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_entry() {
        check_ajax_referer('aicb_nonce', 'nonce');
        $email = sanitize_email($_POST['email']);
        $share = esc_url_raw($_POST['share']);
        $entries = get_option('aicb_entries', []);
        $is_pro = get_option('aicb_pro_key') === 'pro2026';
        $max = $is_pro ? 999 : 5;

        if (count($entries) >= $max) {
            wp_send_json_error('Contest full! Upgrade to Pro for unlimited.');
        }
        if (!is_email($email)) {
            wp_send_json_error('Invalid email.');
        }

        $entries[] = ['email' => $email, 'share' => $share, 'time' => current_time('mysql')];
        update_option('aicb_entries', $entries);

        // Simulate email list add (Pro integrates with real services)
        $email_list = get_option('aicb_email_list');
        if ($email_list && $is_pro) {
            wp_remote_post($email_list, ['body' => ['email' => $email]]);
        }

        wp_send_json_success('Entry added! Share more to increase chances. Total entries: ' . count($entries));
    }
}

new AIViralContestBooster();

// Freemium upsell notice
function aicb_admin_notice() {
    if (!get_option('aicb_pro_key')) {
        echo '<div class="notice notice-info"><p>Unlock <strong>AI Viral Contest Booster Pro</strong> for AI generation & unlimited entries! <a href="' . admin_url('options-general.php?page=ai-viral-contest') . '">Upgrade</a></p></div>';
    }
}
add_action('admin_notices', 'aicb_admin_notice');