<?php
/*
Plugin Name: Smart Content Booster
Description: AI-powered content optimization that dynamically adjusts headlines, keywords, and layouts based on visitor behavior.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Booster.php
License: GPLv2 or later
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartContentBooster {
    private $option_name = 'scb_settings';
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'optimize_content'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        // AJAX handler for visitor event tracking
        add_action('wp_ajax_scb_track_event', array($this, 'track_event'));
        add_action('wp_ajax_nopriv_scb_track_event', array($this, 'track_event'));
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('scb-script', plugin_dir_url(__FILE__) . 'scb-script.js', array('jquery'), '1.0', true);
            wp_localize_script('scb-script', 'scb_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        }
    }

    // Simulated AI optimization for demo purposes
    public function optimize_content($content) {
        if (!is_singular('post') && !is_singular('page')) {
            return $content;
        }
        // Load visitor behavior data (in real case connect to AI service or analyze saved data)
        $keyword = get_option('scb_keyword') ?: 'Optimize';
        $headline = get_option('scb_headline') ?: 'Boost Your Content Now!';
        // Modify content headings
        $content = preg_replace('/<h1>(.*?)<\/h1>/', '<h1>' . esc_html($headline) . '</h1>', $content);
        // Add keyword usage in content for SEO
        $content .= '<p><em>Keyword focus: ' . esc_html($keyword) . '</em></p>';
        return $content;
    }

    public function add_admin_menu() {
        add_options_page('Smart Content Booster', 'Smart Content Booster', 'manage_options', 'smart-content-booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('scb_settings_group', $this->option_name);

        add_settings_section('scb_section_main', 'Main Settings', null, 'smart-content-booster');

        add_settings_field(
            'scb_keyword',
            'Primary Keyword',
            array($this, 'keyword_render'),
            'smart-content-booster',
            'scb_section_main'
        );

        add_settings_field(
            'scb_headline',
            'Headline Override',
            array($this, 'headline_render'),
            'smart-content-booster',
            'scb_section_main'
        );
    }

    public function keyword_render() {
        $options = get_option($this->option_name);
        ?><input type='text' name='<?php echo $this->option_name; ?>[keyword]' value='<?php echo isset($options['keyword']) ? esc_attr($options['keyword']) : ''; ?>' placeholder='e.g. AI content optimization'><?php
    }

    public function headline_render() {
        $options = get_option($this->option_name);
        ?><input type='text' name='<?php echo $this->option_name; ?>[headline]' value='<?php echo isset($options['headline']) ? esc_attr($options['headline']) : ''; ?>' placeholder='e.g. Get Higher Engagement!'><?php
    }

    public function options_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        $options = get_option($this->option_name);
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('scb_settings_save', 'scb_nonce')) {
            $keyword = sanitize_text_field($_POST[$this->option_name]['keyword']);
            $headline = sanitize_text_field($_POST[$this->option_name]['headline']);
            update_option('scb_keyword', $keyword);
            update_option('scb_headline', $headline);
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Content Booster Settings</h1>
            <form method="post" action="">
                <?php wp_nonce_field('scb_settings_save', 'scb_nonce'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Primary Keyword</th>
                        <td><input type="text" name="scb_settings[keyword]" value="<?php echo esc_attr(get_option('scb_keyword', '')); ?>" placeholder="e.g. AI content optimization" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Headline Override</th>
                        <td><input type="text" name="scb_settings[headline]" value="<?php echo esc_attr(get_option('scb_headline', '')); ?>" placeholder="e.g. Boost Your Content Now!" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Settings'); ?>
            </form>
        </div>
        <?php
    }

    public function track_event() {
        // For simplicity, just respond OK
        wp_send_json_success('Event tracked');
    }
}

new SmartContentBooster();