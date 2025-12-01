/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartLead_AI.php
*/
<?php
/**
 * Plugin Name: SmartLead AI
 * Description: AI-powered lead generation plugin that dynamically shows personalized opt-in forms.
 * Version: 1.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

class SmartLeadAI {
    private $option_name = 'smartlead_ai_options';

    public function __construct() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_footer', [$this, 'output_optin_form']);
        add_action('wp_ajax_smartlead_submit', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_smartlead_submit', [$this, 'handle_form_submission']);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smartlead-style', plugins_url('/style.css', __FILE__));
        wp_enqueue_script('smartlead-script', plugins_url('/smartlead.js', __FILE__), ['jquery'], null, true);
        wp_localize_script('smartlead-script', 'smartlead_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smartlead_nonce')
        ]);
    }

    public function output_optin_form() {
        if (!$this->should_show_form()) return;
        $headline = $this->generate_dynamic_headline();
        $button_text = $this->generate_dynamic_button_text();
        echo '<div id="smartlead-popup" style="display:none;">';
        echo '<div class="smartlead-inner">';
        echo '<h2>' . esc_html($headline) . '</h2>';
        echo '<form id="smartlead-form">';
        echo '<input type="email" name="email" placeholder="Enter your email" required />';
        echo '<button type="submit">' . esc_html($button_text) . '</button>';
        echo '<div class="smartlead-message"></div>';
        echo '</form>';
        echo '<button id="smartlead-close">×</button>';
        echo '</div></div>';
    }

    private function should_show_form() {
        // Basic check: don't show for logged-in users or if cookie set
        if (is_user_logged_in()) return false;
        if (isset($_COOKIE['smartlead_seen'])) return false;
        return true;
    }

    private function generate_dynamic_headline() {
        // Simple AI simulation: customize headline based on page category or URL
        if (is_home() || is_front_page()) {
            return 'Join 10,000+ subscribers getting Weekly Tips!';
        } elseif (is_category()) {
            $cat = single_cat_title('', false);
            return 'Exclusive insights on ' . $cat . ' - Subscribe now!';
        } elseif (is_singular('post')) {
            $title = get_the_title();
            return 'Like "' . $title . '"? Get similar tips by email!';
        } else {
            return 'Stay updated with our latest news';
        }
    }

    private function generate_dynamic_button_text() {
        $texts = ["Subscribe Now", "Get Access", "Join Free", "Sign Up"];
        return $texts[array_rand($texts)];
    }

    public function handle_form_submission() {
        check_ajax_referer('smartlead_nonce', 'nonce');
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email.']);
        }

        // Here: Integrate with email marketing API or store email locally
        // For demo, just set cookie and respond success
        setcookie('smartlead_seen', 1, time() + 86400 * 30, COOKIEPATH, COOKIE_DOMAIN);
        wp_send_json_success(['message' => 'Thank you for subscribing!']);
    }
}

new SmartLeadAI();

// Inline minimal CSS and JS injection for this demo:
add_action('wp_footer', function() {
    ?>
    <style>
        #smartlead-popup {position: fixed; bottom: 20px; right: 20px; background: #fff; border: 1px solid #ccc; box-shadow: 0 0 15px rgba(0,0,0,0.2); padding: 20px; z-index: 9999; width: 300px; font-family: Arial,sans-serif;}
        #smartlead-popup .smartlead-inner {position: relative;}
        #smartlead-popup h2 {font-size: 18px; margin-bottom: 10px;}
        #smartlead-popup input[type="email"] {width: 100%; padding: 8px; margin-bottom: 10px; border:1px solid #ccc; border-radius: 3px;}
        #smartlead-popup button[type="submit"] {background: #0073aa; color: #fff; border:none; padding: 10px; cursor: pointer; width: 100%;}
        #smartlead-close {position: absolute; top: 5px; right: 10px; background: none; border: none; font-size: 20px; cursor: pointer; color: #999;}
        .smartlead-message {margin-top: 10px; font-size: 14px; color: green;}
    </style>
    <script>
    jQuery(document).ready(function($) {
        $('#smartlead-popup').fadeIn();
        $('#smartlead-close').click(function() { $('#smartlead-popup').fadeOut(); });
        $('#smartlead-form').submit(function(e) {
            e.preventDefault();
            var email = $(this).find('input[name="email"]').val();
            var data = {
                action: 'smartlead_submit',
                email: email,
                nonce: smartlead_ajax.nonce
            };
            $.post(smartlead_ajax.ajax_url, data, function(response) {
                if(response.success) {
                    $('.smartlead-message').css('color','green').text(response.data.message);
                    $('#smartlead-form').reset();
                    setTimeout(function(){ $('#smartlead-popup').fadeOut(); }, 3000);
                } else {
                    $('.smartlead-message').css('color','red').text(response.data.message);
                }
            });
        });
    });
    </script>
    <?php
});
