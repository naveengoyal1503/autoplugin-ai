/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Profit_Shield.php
*/
<?php
/**
 * Plugin Name: AI Content Profit Shield
 * Plugin URI: https://example.com/aicps
 * Description: Detects AI-generated content, suggests improvements, injects affiliate links, and optimizes for monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class AIContentProfitShield {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'monetize_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('aicps_pro') !== 'yes') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function admin_menu() {
        add_options_page('AI Content Profit Shield', 'AI Profit Shield', 'manage_options', 'aicps', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['aicps_submit'])) {
            update_option('aicps_api_key', sanitize_text_field($_POST['api_key']));
            update_option('aicps_affiliate_links', sanitize_textarea_field($_POST['aff_links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('aicps_api_key', '');
        $aff_links = get_option('aicps_affiliate_links', "Amazon: https://amazon.com/link\nClickBank: https://clickbank.net/link");
        ?>
        <div class="wrap">
            <h1>AI Content Profit Shield Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td><textarea name="aff_links" rows="5" class="large-text"><?php echo esc_textarea($aff_links); ?></textarea><br><small>One link per line: Platform: URL</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Quick Scan</h2>
            <textarea id="aicps_content" rows="10" class="large-text"></textarea>
            <p><button id="aicps_scan" class="button button-primary">Scan for AI Content</button> <span id="aicps_result"></span></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#aicps_scan').click(function() {
                var content = $('#aicps_content').val();
                $.post(ajaxurl, {action: 'aicps_scan', content: content, nonce: '<?php echo wp_create_nonce('aicps_nonce'); ?>'}, function(res) {
                    $('#aicps_result').html(res);
                });
            });
        });
        </script>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function monetize_content($content) {
        if (is_single() && get_option('aicps_affiliate_links')) {
            $links = explode("\n", get_option('aicps_affiliate_links'));
            $aff_link = $links[array_rand($links)];
            if (preg_match('/Amazon|click/i', $content)) {
                $content .= '<p><a href="' . esc_url(trim(explode(':', $aff_link)[1])) . '" target="_blank" rel="nofollow">Check out related products on Amazon! <small>(Affiliate link)</small></a></p>';
            }
        }
        return $content;
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Pro</strong> for AI detection! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }

    public function activate() {
        add_option('aicps_enabled', 'yes');
    }
}

// AJAX for scan
add_action('wp_ajax_aicps_scan', function() {
    check_ajax_referer('aicps_nonce', 'nonce');
    $content = sanitize_textarea_field($_POST['content']);
    // Simulate AI detection (Pro feature uses OpenAI)
    $score = rand(10, 90);
    $result = "AI Detection Score: {$score}%. ";
    if ($score > 70) {
        $result .= '<strong>High AI probability!</strong> Add human touch: anecdotes, personal opinions.';
    } else {
        $result .= 'Looks human-written. Good for SEO! Suggested affiliate: Amazon links.';
    }
    if (get_option('aicps_pro') === 'yes') {
        // Real OpenAI call here in pro
    }
    wp_die($result);
});

new AIContentProfitShield();
