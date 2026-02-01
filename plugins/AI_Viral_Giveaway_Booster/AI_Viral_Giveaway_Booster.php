/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Viral_Giveaway_Booster.php
*/
<?php
/**
 * Plugin Name: AI Viral Giveaway Booster
 * Plugin URI: https://example.com/aiviralgiveaway
 * Description: AI-powered viral giveaways to boost emails, traffic, and social shares.
 * Version: 1.0.0
 * Author: YourName
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AI_Viral_Giveaway_Booster {
    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_shortcode('ai_giveaway', [$this, 'giveaway_shortcode']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        if (get_option('ai_vgb_api_key') === false) {
            add_option('ai_vgb_api_key', '');
        }
        if (get_option('ai_vgb_campaigns') === false) {
            add_option('ai_vgb_campaigns', []);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-vgb-style', plugin_dir_url(__FILE__) . 'style.css', [], '1.0.0');
        wp_enqueue_script('ai-vgb-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], '1.0.0', true);
        wp_localize_script('ai-vgb-script', 'aiVGB', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_vgb_nonce')
        ]);
    }

    public function admin_menu() {
        add_options_page('AI Viral Giveaway', 'AI Giveaway', 'manage_options', 'ai-vgb', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (isset($_POST['ai_vgb_save'])) {
            update_option('ai_vgb_api_key', sanitize_text_field($_POST['api_key']));
            echo '<div class="notice notice-success"><p>Saved!</p></div>';
        }
        $api_key = get_option('ai_vgb_api_key');
        ?>
        <div class="wrap">
            <h1>AI Viral Giveaway Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'ai_vgb_save'); ?>
            </form>
            <h2>Create Campaign</h2>
            <p>Use shortcode: <code>[ai_giveaway id="1"]</code></p>
        </div>
        <?php
    }

    public function giveaway_shortcode($atts) {
        $atts = shortcode_atts(['id' => '1'], $atts);
        $campaigns = get_option('ai_vgb_campaigns', []);
        if (!isset($campaigns[$atts['id']])) return 'Campaign not found.';
        $campaign = $campaigns[$atts['id']];
        $entries = get_option('ai_vgb_entries_' . $atts['id'], []);
        $total_entries = count($entries);

        ob_start();
        ?>
        <div id="ai-vgb-<?php echo esc_attr($atts['id']); ?>" class="ai-vgb-container" data-id="<?php echo esc_attr($atts['id']); ?>">
            <div class="ai-vgb-header">
                <h3><?php echo esc_html($campaign['prize']); ?></h3>
                <p>Ends in <span class="countdown">7</span> days | <?php echo $total_entries; ?> entries</p>
            </div>
            <div class="ai-vgb-actions">
                <input type="email" id="ai-vgb-email" placeholder="Enter email to enter">
                <button id="ai-vgb-enter">Enter Now!</button>
            </div>
            <div class="ai-vgb-entries">
                <h4>Bonus Entries</h4>
                <button class="entry-action" data-type="facebook">Share on Facebook (+5)</button>
                <button class="entry-action" data-type="twitter">Tweet (+3)</button>
                <button class="entry-action" data-type="refer">Refer Friend (+10)</button>
            </div>
            <div class="ai-vgb-leaderboard">
                <h4>Top Entrants</h4>
                <ul><?php foreach (array_slice($entries, 0, 5, true) as $email => $count) { echo '<li>' . esc_html($email) . ' - ' . $count . '</li>'; } ?></ul>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_enter_giveaway() {
        check_ajax_referer('ai_vgb_nonce', 'nonce');
        $id = intval($_POST['id']);
        $email = sanitize_email($_POST['email']);
        if (!is_email($email)) wp_die('Invalid email');

        $entries = get_option('ai_vgb_entries_' . $id, []);
        if (!isset($entries[$email])) $entries[$email] = 0;
        $entries[$email] += 1;
        update_option('ai_vgb_entries_' . $id, $entries);

        // Simulate AI optimization (Pro feature)
        $this->ai_optimize_campaign($id);

        wp_send_json_success(['entries' => $entries[$email]]);
    }

    private function ai_optimize_campaign($id) {
        // Mock AI call - Pro integrates with OpenAI
        $api_key = get_option('ai_vgb_api_key');
        if ($api_key) {
            // Real implementation: Call OpenAI for entry optimization suggestions
        }
    }

    public function activate() {
        flush_rewrite_rules();
    }
}

new AI_Viral_Giveaway_Booster();

add_action('wp_ajax_ai_vgb_enter', function() {
    $instance = new AI_Viral_Giveaway_Booster();
    $instance->ajax_enter_giveaway();
});

add_action('wp_ajax_nopriv_ai_vgb_enter', function() {
    $instance = new AI_Viral_Giveaway_Booster();
    $instance->ajax_enter_giveaway();
});

// Minimal CSS
/*
.ai-vgb-container { max-width: 400px; border: 2px solid #007cba; padding: 20px; border-radius: 10px; font-family: Arial; }
.ai-vgb-header { text-align: center; }
.ai-vgb-actions input { width: 60%; padding: 10px; }
.ai-vgb-actions button { width: 38%; padding: 10px; background: #007cba; color: white; border: none; }
.entry-action { display: block; width: 100%; margin: 5px 0; padding: 8px; background: #f1f1f1; border: 1px solid #ddd; }
*/

// Minimal JS
/*
jQuery(document).ready(function($) {
    $('.entry-action').click(function() {
        var type = $(this).data('type');
        // Simulate share
        alert('Shared on ' + type + '! +entries added');
    });

    $('#ai-vgb-enter').click(function() {
        var email = $('#ai-vgb-email').val();
        var id = $(this).closest('.ai-vgb-container').data('id');
        $.post(aiVGB.ajaxurl, {action: 'ai_vgb_enter', id: id, email: email, nonce: aiVGB.nonce}, function(res) {
            if (res.success) alert('Entered! Entries: ' + res.data.entries);
        });
    });
});
*/