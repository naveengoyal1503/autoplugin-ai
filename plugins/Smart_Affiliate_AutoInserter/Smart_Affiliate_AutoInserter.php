/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using AI to maximize earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $api_key;
    private $affiliate_links;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_generate_links', array($this, 'ajax_generate_links'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->api_key = get_option('saai_openai_key', '');
        $this->affiliate_links = get_option('saai_affiliate_links', array());
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saai-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('saai-admin', 'saai_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('saai_nonce')));
    }

    public function add_admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'Affiliate AI', 'manage_options', 'smart-affiliate-ai', array($this, 'admin_page'));
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saai_options'); ?>
                <?php do_settings_sections('saai_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>OpenAI API Key</th>
                        <td><input type="password" name="saai_openai_key" value="<?php echo esc_attr(get_option('saai_openai_key')); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON)</th>
                        <td>
                            <textarea name="saai_affiliate_links" rows="10" cols="50"><?php echo esc_textarea(json_encode(get_option('saai_affiliate_links', array()))); ?></textarea>
                            <p class="description">Enter JSON array: {"keyword": "affiliate_url"}</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Generate Links for Post</h2>
            <select id="saai_post_select">
                <?php
                $posts = get_posts(array('numberposts' => 50, 'post_status' => 'publish'));
                foreach ($posts as $post) {
                    echo '<option value="' . $post->ID . '">' . esc_html($post->post_title) . '</option>';
                }
                ?>
            </select>
            <button id="saai_generate">Generate & Insert Links</button>
            <div id="saai_results"></div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#saai_generate').click(function() {
                var postId = $('#saai_post_select').val();
                $.post(saai_ajax.ajax_url, {
                    action: 'generate_links',
                    post_id: postId,
                    nonce: saai_ajax.nonce
                }, function(response) {
                    $('#saai_results').html(response);
                });
            });
        });
        </script>
        <?php
    }

    public function ajax_generate_links() {
        check_ajax_referer('saai_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $post_id = intval($_POST['post_id']);
        $content = get_post_field('post_content', $post_id);

        $prompt = "Extract 3 key topics from this content and suggest relevant affiliate products: " . substr($content, 0, 2000);
        $suggestions = $this->call_openai($prompt);

        echo '<p>AI Suggestions: ' . esc_html($suggestions) . '</p>';
        wp_die();
    }

    public function insert_affiliate_links($content) {
        if (empty($this->api_key) || empty($this->affiliate_links)) return $content;

        // Simple keyword replacement for demo (extend with AI in Pro)
        foreach ($this->affiliate_links as $keyword => $link) {
            $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow">$0</a>', $content, 2);
        }
        return $content;
    }

    private function call_openai($prompt) {
        if (empty($this->api_key)) return 'API key required';

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-3.5-turbo',
                'messages' => array(array('role' => 'user', 'content' => $prompt)),
                'max_tokens' => 150
            )),
            'timeout' => 30
        ));

        if (is_wp_error($response)) return 'API Error';
        $body = json_decode(wp_remote_retrieve_body($response), true);
        return $body['choices']['message']['content'] ?? 'No response';
    }

    public function activate() {
        add_option('saai_openai_key', '');
        add_option('saai_affiliate_links', array(
            'WordPress' => 'https://example.com/aff/wp',
            'hosting' => 'https://example.com/aff/hosting'
        ));
    }
}

new SmartAffiliateAutoInserter();

// Freemium upsell notice
function saai_upsell_notice() {
    if (!get_option('saai_pro_activated')) {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Pro</strong> for unlimited AI insertions and analytics! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'saai_upsell_notice');
?>