<?php
/*
Plugin Name: WP SmartPaywall
Description: Unlock premium content based on engagement, referrals, or micro-payments.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_SmartPaywall.php
*/

if (!defined('ABSPATH')) exit;

// Main plugin class
class WPSmartPaywall {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_unlock_content', array($this, 'unlock_content'));
        add_action('wp_ajax_nopriv_unlock_content', array($this, 'unlock_content'));
        add_filter('the_content', array($this, 'apply_paywall'));
    }

    public function init() {
        // Register settings
        register_setting('wp_smartpaywall', 'wp_smartpaywall_rules');
        add_option('wp_smartpaywall_rules', array('min_views' => 3, 'min_referrals' => 2, 'min_payment' => 0.50));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wp-smartpaywall', plugins_url('/js/smartpaywall.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('wp-smartpaywall', 'smartpaywall_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function apply_paywall($content) {
        if (is_admin() || !is_singular('post')) return $content;

        $rules = get_option('wp_smartpaywall_rules', array());
        $post_id = get_the_ID();
        $user_id = get_current_user_id();

        // Check if user has unlocked content
        $unlocked = get_user_meta($user_id, 'smartpaywall_unlocked_' . $post_id, true);
        if ($unlocked) return $content;

        // Apply paywall logic
        $views = get_post_meta($post_id, 'views_' . $user_id, true);
        $referrals = get_user_meta($user_id, 'referrals', true);
        $payment = get_user_meta($user_id, 'payment_' . $post_id, true);

        if (($views >= $rules['min_views']) || ($referrals >= $rules['min_referrals']) || ($payment >= $rules['min_payment'])) {
            update_user_meta($user_id, 'smartpaywall_unlocked_' . $post_id, true);
            return $content;
        }

        // Show paywall
        $paywall = '<div class="smartpaywall-overlay">
            <p>This content is locked. Unlock by:
            <ul>
                <li>Reading ' . $rules['min_views'] . ' articles</li>
                <li>Referring ' . $rules['min_referrals'] . ' friends</li>
                <li>Pay $' . $rules['min_payment'] . '</li>
            </ul>
            <button onclick="unlockContent(' . $post_id . ')">Unlock Now</button>
            </div>';
        return $paywall . $content;
    }

    public function unlock_content() {
        $post_id = intval($_POST['post_id']);
        $user_id = get_current_user_id();
        $method = sanitize_text_field($_POST['method']);

        if ($method === 'payment') {
            // Simulate payment
            update_user_meta($user_id, 'payment_' . $post_id, 0.50);
        } elseif ($method === 'referral') {
            $referrals = get_user_meta($user_id, 'referrals', true);
            update_user_meta($user_id, 'referrals', $referrals + 1);
        }

        update_user_meta($user_id, 'smartpaywall_unlocked_' . $post_id, true);
        wp_die('success');
    }
}

new WPSmartPaywall();

// Add settings page
add_action('admin_menu', function() {
    add_options_page('WP SmartPaywall', 'SmartPaywall', 'manage_options', 'wp-smartpaywall', function() {
        $rules = get_option('wp_smartpaywall_rules', array());
        if (isset($_POST['submit'])) {
            $rules['min_views'] = intval($_POST['min_views']);
            $rules['min_referrals'] = intval($_POST['min_referrals']);
            $rules['min_payment'] = floatval($_POST['min_payment']);
            update_option('wp_smartpaywall_rules', $rules);
        }
        ?>
        <div class="wrap">
            <h1>WP SmartPaywall Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr><th>Minimum Views</th><td><input type="number" name="min_views" value="<?php echo $rules['min_views']; ?>" /></td></tr>
                    <tr><th>Minimum Referrals</th><td><input type="number" name="min_referrals" value="<?php echo $rules['min_referrals']; ?>" /></td></tr>
                    <tr><th>Minimum Payment ($)</th><td><input type="number" step="0.01" name="min_payment" value="<?php echo $rules['min_payment']; ?>" /></td></tr>
                </table>
                <input type="submit" name="submit" class="button button-primary" value="Save" />
            </form>
        </div>
        <?php
    });
});

// JS for unlocking
add_action('wp_footer', function() {
    if (is_singular('post')) {
        ?>
        <script>
        function unlockContent(postId) {
            var method = prompt('Choose unlock method: views, referral, payment');
            jQuery.post(smartpaywall_ajax.ajax_url, {
                action: 'unlock_content',
                post_id: postId,
                method: method
            }, function(res) {
                if (res === 'success') {
                    location.reload();
                }
            });
        }
        </script>
        <?php
    }
});
?>