/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Content_Unlocker_Pro.php
*/
<?php
/**
 * Plugin Name: Content Unlocker Pro
 * Description: Tiered content unlocking with subscriptions and microtransaction support.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class ContentUnlockerPro {
    public function __construct() {
        add_shortcode('content_unlocker', [$this, 'shortcode_content_unlocker']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_cup_purchase', [$this, 'ajax_purchase']);
        add_action('wp_ajax_nopriv_cup_purchase', [$this, 'ajax_purchase']);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('cup-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('cup-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], null, true);
        wp_localize_script('cup-script', 'CUP_Ajax', ['ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('cup_nonce')]);
    }

    public function shortcode_content_unlocker($atts, $content = null) {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            $level = get_user_meta($user->ID, 'cup_membership_level', true);
            $atts = shortcode_atts(['level' => 'free'], $atts);

            if ($this->check_access($level, $atts['level'])) {
                return do_shortcode($content);
            } else {
                return $this->show_paywall($atts['level']);
            }
        } else {
            return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">login</a> to access this content.</p>';
        }
    }

    private function check_access($user_level, $required_level) {
        $levels = ['free' => 0, 'silver' => 1, 'gold' => 2, 'platinum' => 3];
        return isset($levels[$user_level]) && isset($levels[$required_level]) && $levels[$user_level] >= $levels[$required_level];
    }

    private function show_paywall($level) {
        ob_start();
        ?>
        <div class="cup-paywall">
            <p>This content is available for <strong><?php echo esc_html(ucfirst($level)); ?></strong> members only.</p>
            <button class="cup-purchase-button" data-level="<?php echo esc_attr($level); ?>">Unlock Now</button>
            <div class="cup-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_purchase() {
        check_ajax_referer('cup_nonce', 'nonce');
        $level = sanitize_text_field($_POST['level']);

        if (!in_array($level, ['silver', 'gold', 'platinum'])) {
            wp_send_json_error('Invalid membership level.');
        }

        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in.');
        }

        $user_id = get_current_user_id();
        update_user_meta($user_id, 'cup_membership_level', $level);

        // Here you would integrate payment gateway logic.
        // For this mock plugin, assume payment is successful immediately.

        wp_send_json_success('Content unlocked successfully!');
    }
}

new ContentUnlockerPro();

// Minimal CSS and JS inline here for single file self-containment
add_action('wp_head', function() {
    echo '<style>.cup-paywall { padding: 20px; background: #f7f7f7; border: 1px solid #ddd; max-width: 400px; margin: 1em 0; } .cup-purchase-button { background: #0073aa; color: #fff; border: none; padding: 10px 15px; cursor: pointer; } .cup-purchase-button:hover { background: #005177; } .cup-message { margin-top: 10px; color: green; }</style>';
});

add_action('wp_footer', function() {
    ?>
    <script>
    (function($){
        $('.cup-purchase-button').on('click', function() {
            var button = $(this);
            var level = button.data('level');
            var messageDiv = button.siblings('.cup-message');
            button.prop('disabled', true).text('Processing...');

            $.post(CUP_Ajax.ajax_url, {
                action: 'cup_purchase',
                level: level,
                nonce: CUP_Ajax.nonce
            }, function(response) {
                if(response.success) {
                    messageDiv.text(response.data);
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    messageDiv.text(response.data || 'Error occurred.');
                    button.prop('disabled', false).text('Unlock Now');
                }
            });
        });
    })(jQuery);
    </script>
    <?php
});