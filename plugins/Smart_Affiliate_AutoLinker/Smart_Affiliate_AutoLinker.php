/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in posts and converts them to affiliate links for easy monetization.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoLinker {
    private $keywords = [];
    private $affiliate_links = [];

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('the_content', [$this, 'auto_link_keywords']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolinker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        $this->keywords = get_option('sal_keywords', []);
        $this->affiliate_links = get_option('sal_links', []);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sal-frontend', plugin_dir_url(__FILE__) . 'sal-frontend.js', ['jquery'], '1.0.0', true);
    }

    public function auto_link_keywords($content) {
        if (is_admin() || empty($this->keywords)) {
            return $content;
        }
        global $post;
        if (in_array($post->post_type, ['post', 'page'])) {
            foreach ($this->keywords as $keyword => $link_id) {
                if (isset($this->affiliate_links[$link_id])) {
                    $link = $this->affiliate_links[$link_id];
                    $regex = '/\b' . preg_quote($keyword, '/') . '\b/i';
                    $content = preg_replace($regex, '<a href="' . esc_url($link['url']) . '" target="_blank" rel="nofollow sponsored">$0</a>', $content, 1);
                }
            }
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            [$this, 'admin_page']
        );
    }

    public function admin_init() {
        register_setting('sal_options', 'sal_keywords');
        register_setting('sal_options', 'sal_links');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('sal_keywords', sanitize_text_field($_POST['sal_keywords']));
            update_option('sal_links', $_POST['sal_links']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $keywords = get_option('sal_keywords', 'WordPress,plugin,theme');
        $links = get_option('sal_links', []);
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Keywords (comma-separated)</th>
                        <td><input type="text" name="sal_keywords" value="<?php echo esc_attr($keywords); ?>" class="regular-text" placeholder="WordPress, plugin, theme" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (JSON)</th>
                        <td>
                            <textarea name="sal_links" class="large-text" rows="10" placeholder='{"0":{"url":"https://amazon.com/affiliate-link1","text":"Amazon Link"},"1":{"url":"https://amazon.com/affiliate-link2","text":"Another Link"}}'><?php echo esc_textarea(json_encode($links)); ?></textarea>
                            <p class="description">Enter JSON object with link IDs matching keywords index.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Pro Features</h2>
            <p>Upgrade to Pro for unlimited keywords, analytics, and more networks. <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        if (!get_option('sal_keywords')) {
            update_option('sal_keywords', 'WordPress,plugin,theme');
        }
    }

    public function deactivate() {
        // Cleanup optional
    }
}

new SmartAffiliateAutoLinker();

// Pro upsell notice
function sal_admin_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock unlimited features with <strong>Smart Affiliate AutoLinker Pro</strong>! <a href="https://example.com/pro">Upgrade now</a></p></div>';
}
add_action('admin_notices', 'sal_admin_notice');
?>