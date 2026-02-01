/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress posts and pages using keyword matching to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliates = [];

    public function __construct() {
        add_action('init', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_footer', [$this, 'render_settings']);
        add_filter('the_content', [$this, 'auto_insert_links'], 99);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        $this->load_settings();
    }

    private function load_settings() {
        $this->affiliates = get_option('saa_affiliates', []);
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('saa-frontend', plugin_dir_url(__FILE__) . 'saa.js', ['jquery'], '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (is_admin() || !is_single()) return $content;

        global $post;
        $words = explode(' ', strip_tags($content));
        $inserted = 0;
        $max_inserts = 3;

        foreach ($this->affiliates as $affiliate) {
            foreach ($words as $i => $word) {
                if (stripos($word, $affiliate['keyword']) !== false && $inserted < $max_inserts) {
                    $link = '<a href="' . esc_url($affiliate['url']) . '" target="_blank" rel="nofollow sponsored">' . esc_html($affiliate['keyword']) . '</a>';
                    $words[$i] = str_replace($affiliate['keyword'], $link, $word);
                    $inserted++;
                }
            }
        }

        $content = implode(' ', $words);
        return $content;
    }

    public function render_settings() {
        if (!is_user_logged_in()) return;
        ?>
        <div id="saa-settings" style="display:none;">
            <h3>Affiliate Settings (Admin Only)</h3>
            <textarea id="saa-affiliates" placeholder='[{"keyword":"best shoes","url":"https://affiliate-link.com/shoes"}]'></textarea>
            <button id="saa-save">Save</button>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#saa-save').click(function() {
                $.post(ajaxurl, {
                    action: 'saa_save_affiliates',
                    affiliates: $('#saa-affiliates').val()
                }, function() {
                    location.reload();
                });
            });
        });
        </script>
        <?php
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'Affiliate Inserter', 'manage_options', 'saa-settings', [$this, 'admin_page']);
    }

    public function admin_init() {
        add_action('wp_ajax_saa_save_affiliates', [$this, 'ajax_save_affiliates']);
    }

    public function ajax_save_affiliates() {
        if (!current_user_can('manage_options')) wp_die();
        $affiliates = json_decode(sanitize_text_field($_POST['affiliates']), true);
        update_option('saa_affiliates', $affiliates);
        wp_send_json_success();
    }

    public function admin_page() {
        $affiliates = get_option('saa_affiliates', []);
        echo '<div class="wrap"><h1>Smart Affiliate AutoInserter Settings</h1>';
        echo '<p>Add JSON array of affiliates: <code>[{"keyword":"keyword","url":"link"}, ...]</code></p>';
        echo '<textarea rows="10" cols="80" name="affiliates">' . esc_textarea(json_encode($affiliates)) . '</textarea>';
        echo '<p><input type="button" class="button-primary" value="Save" id="saa-admin-save"></p>';
        echo '</div>';
        echo '<script>jQuery(".button-primary").click(function(){jQuery.post(ajaxurl,{action:"saa_save_affiliates",affiliates:jQuery("textarea[name=affiliates]").val()});});</script>';
    }

    public function activate() {
        update_option('saa_affiliates', []);
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

?>