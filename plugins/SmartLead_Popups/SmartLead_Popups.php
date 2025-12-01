<?php
/*
Plugin Name: SmartLead Popups
Description: AI-powered popup builder for personalized lead capture.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartLead_Popups.php
*/

if (!defined('ABSPATH')) exit;

class SmartLeadPopups {
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_popup'));
        add_action('wp_ajax_smartlead_submit', array($this, 'handle_form_submission'));
        add_action('wp_ajax_nopriv_smartlead_submit', array($this, 'handle_form_submission'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('smartlead-popup-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('smartlead-popup-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
        wp_localize_script('smartlead-popup-script', 'smartLeadAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('smartlead_nonce')
        ));
    }

    public function render_popup() {
        ?>
        <div id="smartlead-popup" style="display:none;">
            <div id="smartlead-popup-content">
                <h2 id="smartlead-popup-heading">Join our newsletter</h2>
                <p id="smartlead-popup-subheading">Get exclusive updates and offers.</p>
                <form id="smartlead-form">
                    <input type="email" name="email" placeholder="Your email address" required />
                    <input type="hidden" name="action" value="smartlead_submit" />
                    <button type="submit">Subscribe</button>
                </form>
                <div id="smartlead-message"></div>
                <button id="smartlead-close">×</button>
            </div>
        </div>
        <?php
    }

    public function handle_form_submission() {
        check_ajax_referer('smartlead_nonce', 'nonce');

        if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            wp_send_json_error('Invalid email address.');
        }

        $email = sanitize_email($_POST['email']);

        // Store email or send to integration, here just simulate success
        // TODO: Integrate with email marketing services (MailChimp, etc.)

        wp_send_json_success('Thank you for subscribing!');
    }
}

new SmartLeadPopups();

/* CSS as inline style (style.css content) */
add_action('wp_head', function() {
    ?>
    <style>
    #smartlead-popup {
      position: fixed;
      top: 20%;
      left: 50%;
      transform: translateX(-50%);
      background: #fff;
      border: 2px solid #0073aa;
      padding: 20px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.2);
      z-index: 999999;
      width: 320px;
      font-family: Arial, sans-serif;
      border-radius: 8px;
    }
    #smartlead-popup-content h2 {
      margin-top: 0;
      color: #0073aa;
    }
    #smartlead-popup-content p {
      font-size: 14px;
      color: #333;
      margin-bottom: 15px;
    }
    #smartlead-form input[type='email'] {
      width: calc(100% - 90px);
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
      margin-right: 8px;
      box-sizing: border-box;
      font-size: 14px;
    }
    #smartlead-form button {
      padding: 9px 14px;
      background-color: #0073aa;
      border: none;
      color: #fff;
      cursor: pointer;
      border-radius: 4px;
      font-weight: bold;
      font-size: 14px;
    }
    #smartlead-message {
      margin-top: 10px;
      font-size: 13px;
      color: green;
    }
    #smartlead-close {
      position: absolute;
      top: 6px;
      right: 10px;
      background: transparent;
      border: none;
      font-size: 20px;
      cursor: pointer;
      color: #999;
    }
    </style>
    <?php
});

/* JavaScript as inline script (script.js content) */
add_action('wp_footer', function() {
    ?>
    <script>
    (function($){
      function showPopup() {
        $('#smartlead-popup').fadeIn();
      }
      function hidePopup() {
        $('#smartlead-popup').fadeOut();
      }

      $(document).ready(function(){
        // Show popup after 5 seconds delay
        setTimeout(showPopup, 5000);

        $('#smartlead-close').on('click', function(){
          hidePopup();
        });

        $('#smartlead-form').on('submit', function(e){
          e.preventDefault();
          var email = $(this).find('input[name="email"]').val();
          var nonce = smartLeadAjax.nonce;
          var data = {
            action: 'smartlead_submit',
            email: email,
            nonce: nonce
          };
          $('#smartlead-message').text('Submitting...').css('color', 'black');
          $.post(smartLeadAjax.ajax_url, data, function(response){
            if(response.success) {
              $('#smartlead-message').text(response.data).css('color', 'green');
              $('#smartlead-form').hide();
              setTimeout(hidePopup, 3000);
            } else {
              $('#smartlead-message').text(response.data).css('color', 'red');
            }
          });
        });
      });
    })(jQuery);
    </script>
    <?php
});