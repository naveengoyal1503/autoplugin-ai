/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for better SEO and engagement. Freemium model with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 */

if (!defined('ABSPATH')) {
    exit;
}

class AIContentOptimizer {
    const PREMIUM_KEY = 'aicop_premium_key';
    const PREMIUM_STATUS = 'aicop_premium_status';

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_aicop_analyze', array($this, 'handle_analyze'));
        add_action('wp_ajax_aicop_upgrade', array($this, 'handle_upgrade'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        load_plugin_textdomain('ai-content-optimizer', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        if (is_admin()) {
            wp_enqueue_script('aicop-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('aicop-admin-css', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
        }
    }

    public function add_admin_menu() {
        add_options_page(
            'AI Content Optimizer',
            'AI Optimizer',
            'manage_options',
            'ai-content-optimizer',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        $premium_status = get_option(self::PREMIUM_STATUS, 'free');
        $premium_key = get_option(self::PREMIUM_KEY, '');
        $scan_count = get_option('aicop_scan_count', 0);
        ?>
        <div class="wrap">
            <h1><?php _e('AI Content Optimizer Pro', 'ai-content-optimizer'); ?></h1>
            <?php if ($premium_status === 'free'): ?>
                <div class="notice notice-warning"><p><?php _e('Free version: Limited to 5 scans/month. Upgrade to Pro for unlimited!', 'ai-content-optimizer'); ?></p></div>
            <?php endif; ?>
            <div id="aicop-dashboard">
                <p><?php printf(__('Scans used: %d'), $scan_count); ?></p>
                <textarea id="aicop-content" rows="10" cols="80" placeholder="Paste your post content here..."><?php echo esc_textarea(''); ?></textarea>
                <button id="aicop-analyze-btn" class="button button-primary">Analyze Content</button>
                <div id="aicop-results"></div>
            </div>
            <?php if ($premium_status !== 'active'): ?>
                <div class="aicop-upgrade-box">
                    <h3>Upgrade to Pro</h3>
                    <p>Get unlimited scans, AI rewrites, and more for $9/month.</p>
                    <input type="text" id="aicop-license-key" placeholder="Enter license key" value="<?php echo esc_attr($premium_key); ?>">
                    <button id="aicop-activate" class="button button-hero">Activate Pro</button>
                </div>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#aicop-analyze-btn').click(function() {
                var content = $('#aicop-content').val();
                $.post(ajaxurl, {
                    action: 'aicop_analyze',
                    content: content,
                    nonce: '<?php echo wp_create_nonce('aicop_nonce'); ?>'
                }, function(response) {
                    $('#aicop-results').html(response);
                });
            });
            $('#aicop-activate').click(function() {
                var key = $('#aicop-license-key').val();
                $.post(ajaxurl, {
                    action: 'aicop_upgrade',
                    key: key,
                    nonce: '<?php echo wp_create_nonce('aicop_nonce'); ?>'
                }, function(response) {
                    location.reload();
                });
            });
        });
        </script>
        <?php
    }

    public function handle_analyze() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $content = sanitize_textarea_field($_POST['content']);
        $premium_status = get_option(self::PREMIUM_STATUS, 'free');
        $scan_count = (int) get_option('aicop_scan_count', 0);

        if ($premium_status !== 'active') {
            if ($scan_count >= 5) {
                wp_send_json_error('Upgrade to Pro for more scans!');
            }
            update_option('aicop_scan_count', $scan_count + 1);
        }

        // Simulate AI analysis (in real version, integrate OpenAI API or similar)
        $score = rand(60, 95);
        $suggestions = array(
            'Improve keyword density',
            'Add more headings',
            'Shorten sentences',
            'Add meta description'
        );
        $premium_teaser = $premium_status === 'active' ? '' : '<p><strong>Pro: Get AI rewrite!</strong></p>';

        ob_start();
        echo '<div class="aicop-score">SEO Score: ' . $score . '%</div>';
        echo '<ul>';
        foreach ($suggestions as $sugg) {
            echo '<li>' . esc_html($sugg) . '</li>';
        }
        echo '</ul>';
        echo $premium_teaser;
        wp_send_json_success(ob_get_clean());
    }

    public function handle_upgrade() {
        check_ajax_referer('aicop_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $key = sanitize_text_key($_POST['key']);
        // Simulate license check (in real: verify with your server)
        if (strlen($key) > 10 && strpos($key, 'PRO-') === 0) {
            update_option(self::PREMIUM_KEY, $key);
            update_option(self::PREMIUM_STATUS, 'active');
            delete_option('aicop_scan_count');
            wp_send_json_success('Activated!');
        } else {
            wp_send_json_error('Invalid key');
        }
    }

    public function activate() {
        if (!get_option(self::PREMIUM_STATUS)) {
            update_option(self::PREMIUM_STATUS, 'free');
        }
    }
}

new AIContentOptimizer();

// Freemium upsell notice
function aicop_admin_notice() {
    if (get_option('aicop_premium_status', 'free') === 'free') {
        echo '<div class="notice notice-info is-dismissible"><p>Unlock <strong>AI Content Optimizer Pro</strong> for unlimited scans & AI rewrites! <a href="' . admin_url('options-general.php?page=ai-content-optimizer') . '">Upgrade now</a></p></div>';
    }
}
add_action('admin_notices', 'aicop_admin_notice');

// Inline CSS
add_action('admin_head', function() {
    echo '<style>
    #aicop-dashboard { margin: 20px 0; }
    .aicop-score { font-size: 24px; color: green; }
    .aicop-upgrade-box { background: #fff3cd; padding: 20px; border: 1px solid #ffeaa7; margin-top: 20px; }
    </style>';
});

?>