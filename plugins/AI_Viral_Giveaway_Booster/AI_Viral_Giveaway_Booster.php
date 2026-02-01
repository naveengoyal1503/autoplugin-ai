/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Viral_Giveaway_Booster.php
*/
<?php
/**
 * Plugin Name: AI Viral Giveaway Booster
 * Plugin URI: https://example.com/ai-viral-giveaway-booster
 * Description: Automate viral giveaways to grow your email list and traffic with AI-optimized campaigns.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-viral-giveaway
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AIViralGiveawayBooster {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
        }
        $this->load_textdomain();
    }

    public function enqueue_scripts() {
        if (isset($_GET['giveaway']) && is_numeric($_GET['giveaway'])) {
            wp_enqueue_script('jquery');
            wp_enqueue_style('ai-viral-giveaway-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
            wp_enqueue_script('ai-viral-giveaway-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
            wp_localize_script('ai-viral-giveaway-script', 'giveaway_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('giveaway_nonce')));
        }
    }

    public function admin_menu() {
        add_menu_page(
            'AI Viral Giveaway',
            'Giveaways',
            'manage_options',
            'ai-viral-giveaways',
            array($this, 'admin_page'),
            'dashicons-gift',
            30
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('ai_giveaway_data', $_POST['giveaway_data']);
            echo '<div class="notice notice-success"><p>Giveaway saved!</p></div>';
        }
        $data = get_option('ai_giveaway_data', array('title' => 'Win a Free Gift!', 'description' => 'Enter to win!', 'prize' => 'iPhone 15', 'end_date' => date('Y-m-d H:i:s', strtotime('+7 days')), 'max_entries' => 10000));
        ?>
        <div class="wrap">
            <h1>AI Viral Giveaway Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr><th>Title</th><td><input type="text" name="giveaway_data[title]" value="<?php echo esc_attr($data['title']); ?>" class="regular-text" /></td></tr>
                    <tr><th>Description</th><td><textarea name="giveaway_data[description]" class="large-text"><?php echo esc_textarea($data['description']); ?></textarea></td></tr>
                    <tr><th>Prize</th><td><input type="text" name="giveaway_data[prize]" value="<?php echo esc_attr($data['prize']); ?>" class="regular-text" /></td></tr>
                    <tr><th>End Date</th><td><input type="datetime-local" name="giveaway_data[end_date]" value="<?php echo esc_attr($data['end_date']); ?>" /></td></tr>
                    <tr><th>Shortcode</th><td><code>[ai_viral_giveaway id="1"]</code></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Features:</strong> Unlimited entries, email integrations, A/B testing, analytics.</p>
        </div>
        <?php
    }

    public function admin_init() {
        // Pro upsell notice
        add_action('admin_notices', array($this, 'pro_notice'));
    }

    public function pro_notice() {
        if (!get_option('ai_giveaway_pro')) {
            echo '<div class="notice notice-info"><p>Upgrade to <strong>AI Viral Giveaway Booster Pro</strong> for unlimited entries and more! <a href="https://example.com/pro" target="_blank">Get Pro ($49/year)</a></p></div>';
        }
    }

    public function activate() {
        add_option('ai_giveaway_entries', 0);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function load_textdomain() {
        load_plugin_textdomain('ai-viral-giveaway', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

// Shortcode
add_shortcode('ai_viral_giveaway', 'ai_viral_giveaway_shortcode');
function ai_viral_giveaway_shortcode($atts) {
    $atts = shortcode_atts(array('id' => 1), $atts);
    $data = get_option('ai_giveaway_data', array());
    $entries = get_option('ai_giveaway_entries', 0);
    $is_pro = false; // Check license in pro
    $max_entries = $is_pro ? 999999 : 500;

    if (strtotime($data['end_date']) < current_time('timestamp') || $entries >= $max_entries) {
        return '<div class="giveaway-ended">Giveaway ended! Thank you for participating.</div>';
    }

    ob_start();
    ?>
    <div id="ai-giveaway" class="ai-viral-giveaway" data-id="<?php echo $atts['id']; ?>">
        <div class="giveaway-header">
            <h2><?php echo esc_html($data['title']); ?></h2>
            <p><?php echo esc_html($data['description']); ?></p>
            <div class="prize">Prize: <?php echo esc_html($data['prize']); ?></div>
            <div class="timer" data-end="<?php echo esc_attr($data['end_date']); ?>"></div>
            <div class="entries">Entries: <span id="entry-count"><?php echo $entries; ?></span>/<?php echo $max_entries; ?></div>
        </div>
        <div class="entry-actions">
            <input type="email" id="entry-email" placeholder="Enter your email" required>
            <button id="submit-entry">Enter Now!</button>
            <div class="social-entries">
                <button class="social-btn facebook">Share on Facebook (+1)</button>
                <button class="social-btn twitter">Tweet (+1)</button>
            </div>
        </div>
        <div id="entry-message"></div>
    </div>
    <?php
    return ob_get_clean();
}

// AJAX Entry
add_action('wp_ajax_submit_entry', 'handle_giveaway_entry');
add_action('wp_ajax_nopriv_submit_entry', 'handle_giveaway_entry');
function handle_giveaway_entry() {
    check_ajax_referer('giveaway_nonce', 'nonce');
    $email = sanitize_email($_POST['email']);
    if (!is_email($email)) {
        wp_die('Invalid email');
    }

    $entries = get_option('ai_giveaway_entries', 0);
    $is_pro = false;
    $max = $is_pro ? 999999 : 500;

    if ($entries >= $max) {
        wp_send_json_error('Max entries reached. Upgrade to Pro!');
    }

    // Simulate unique check (pro would use DB)
    update_option('ai_giveaway_entries', $entries + 1);
    wp_send_json_success('Entry submitted! Share for more chances.');
}

AIViralGiveawayBooster::get_instance();

// Inline styles and scripts for single file
add_action('wp_head', 'ai_viral_giveaway_styles');
function ai_viral_giveaway_styles() {
    if (isset($_GET['giveaway'])) {
        echo '<style>
.ai-viral-giveaway { max-width: 500px; margin: 50px auto; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.3); font-family: Arial, sans-serif; }
.giveaway-header { text-align: center; }
.prize { font-size: 1.5em; font-weight: bold; margin: 20px 0; }
.timer { background: rgba(255,255,255,0.2); padding: 10px; border-radius: 10px; margin: 10px 0; }
.entry-actions { margin-top: 20px; }
#entry-email { width: 70%; padding: 12px; border: none; border-radius: 5px; }
#submit-entry, .social-btn { padding: 12px 20px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; background: #ff6b6b; color: white; font-weight: bold; }
.social-btn { background: #4ecdc4; width: 48%; display: inline-block; }
#entry-message { margin-top: 20px; padding: 10px; background: rgba(255,255,255,0.2); border-radius: 5px; }
.giveaway-ended { text-align: center; padding: 40px; background: #f8f9fa; color: #6c757d; border-radius: 10px; }
        </style>';
    }
}

add_action('wp_footer', 'ai_viral_giveaway_scripts');
function ai_viral_giveaway_scripts() {
    if (isset($_GET['giveaway'])) {
        ?>
        <script>
jQuery(document).ready(function($) {
    // Countdown timer
    function updateTimer() {
        var end = new Date($('.timer').data('end')).getTime();
        var now = new Date().getTime();
        var distance = end - now;
        if (distance > 0) {
            var days = Math.floor(distance / (1000 * 60 * 60 * 24));
            var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            var seconds = Math.floor((distance % (1000 * 60)) / 1000);
            $('.timer').html(days + 'd ' + hours + 'h ' + minutes + 'm ' + seconds + 's');
        } else {
            $('.timer').html('Expired!');
        }
    }
    setInterval(updateTimer, 1000);
    updateTimer();

    // Entry submission
    $('#submit-entry').click(function(e) {
        e.preventDefault();
        var email = $('#entry-email').val();
        $.post(giveaway_ajax.ajax_url, {
            action: 'submit_entry',
            nonce: giveaway_ajax.nonce,
            email: email
        }, function(res) {
            $('#entry-message').html(res.success ? '<span style="color:green;">' + res.data + '</span>' : '<span style="color:red;">' + res.data + '</span>');
            if (res.success) $('#entry-email').val('');
        });
    });

    // Social buttons (simulate)
    $('.social-btn').click(function() {
        alert('Shared! +1 entry (Pro unlocks real sharing tracking)');
        $('#entry-count').text(parseInt($('#entry-count').text()) + 1);
    });
});
        </script>
        <?php
    }
}
