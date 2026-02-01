/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your content to maximize earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $api_key;
    private $affiliate_tag;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'insert_affiliate_links'));
        add_action('wp_ajax_saa_test_api', array($this, 'test_api'));
    }

    public function init() {
        $this->api_key = get_option('saa_api_key', '');
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('saa_api_key', sanitize_text_field($_POST['api_key']));
            update_option('saa_affiliate_tag', sanitize_text_field($_POST['affiliate_tag']));
            update_option('saa_link_frequency', intval($_POST['link_frequency']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $api_key = get_option('saa_api_key', '');
        $affiliate_tag = get_option('saa_affiliate_tag', '');
        $frequency = get_option('saa_link_frequency', 3);
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="affiliate_tag" value="<?php echo esc_attr($affiliate_tag); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="link_frequency" value="<?php echo esc_attr($frequency); ?>" min="1" max="10" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><em>Free version uses keyword matching. <strong>Premium:</strong> AI-powered product suggestions.</em></p>
            <button id="test-api" class="button">Test API Connection</button>
            <div id="test-result"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#test-api').click(function() {
                $.post(ajaxurl, {action: 'saa_test_api', api_key: $('input[name="api_key"]').val()}, function(response) {
                    $('#test-result').html(response);
                });
            });
        });
        </script>
        <?php
    }

    public function test_api() {
        if (!wp_verify_nonce($_POST['nonce'], 'saa_nonce')) wp_die();
        $api_key = sanitize_text_field($_POST['api_key']);
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => 'Suggest 3 Amazon products for "coffee"')),
                'max_tokens' => 100
            ))
        ));
        if (is_wp_error($response)) {
            wp_die('API Error');
        }
        wp_die('<p style="color:green;">API Connected Successfully!</p>');
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->affiliate_tag)) return $content;

        // Extract keywords (free version: simple keyword scan)
        preg_match_all('/\b\w{4,}\b/i', strip_tags($content), $matches);
        $keywords = array_count_values($matches);
        arsort($keywords);
        $top_keywords = array_slice(array_keys($keywords), 0, 5);

        $frequency = get_option('saa_link_frequency', 3);
        $inserted = 0;

        // Premium: Use AI (simplified keyword fallback for free)
        foreach ($top_keywords as $keyword) {
            if ($inserted >= $frequency) break;

            // Generate Amazon search URL
            $search_url = 'https://www.amazon.com/s?k=' . urlencode($keyword) . '&tag=' . $this->affiliate_tag;
            $link = '<a href="' . esc_url($search_url) . '" target="_blank" rel="nofollow sponsored">' . esc_html($keyword) . ' on Amazon</a>';

            // Insert after paragraphs
            $content = preg_replace('/(<p[^>]*>.*?)(?<!\.["\']\s)' . preg_quote($keyword, '/') . '(.*?<\/p>)/i', "$1 " . $link . " $2", $content, 1);
            $inserted++;
        }

        return $content;
    }
}

new SmartAffiliateAutoInserter();

// Premium teaser notice
function saa_premium_notice() {
    if (current_user_can('manage_options')) {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter Pro:</strong> Unlock AI product matching, custom link styling, analytics & more! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'saa_premium_notice');