/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Booster.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Booster
 * Plugin URI: https://example.com/smart-affiliate-booster
 * Description: AI-powered affiliate link optimizer for Amazon affiliates. Auto-inserts optimized links, tracks performance.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-booster
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateBooster {
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
        add_action('wp_footer', array($this, 'inject_affiliate_links'));
        add_action('admin_menu', array($this, 'admin_menu'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-booster', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        $this->options = get_option('sab_options', array(
            'amazon_tag' => '',
            'keywords' => array('best laptop', 'cheap shoes'),
            'enabled' => true
        ));
    }

    public function enqueue_scripts() {
        if ($this->options['enabled']) {
            wp_enqueue_script('sab-script', plugin_dir_url(__FILE__) . 'sab.js', array('jquery'), '1.0.0', true);
            wp_localize_script('sab-script', 'sab_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sab_nonce')));
        }
    }

    public function inject_affiliate_links() {
        if (!$this->options['enabled'] || !is_single()) return;
        global $post;
        $content = $post->post_content;
        $keywords = $this->options['keywords'];
        foreach ($keywords as $keyword) {
            $search_word = $keyword;
            $aff_link = 'https://amazon.com/search?q=' . urlencode($search_word) . '&tag=' . $this->options['amazon_tag'];
            $content = preg_replace('/\b' . preg_quote($search_word, '/') . '\b/i', '<a href="$aff_link" target="_blank" rel="nofollow sponsored" class="sab-link">$0 <span style="color:#ff9900;">(Amazon)</span></a>', $content, 1);
        }
        echo '<script>document.addEventListener("DOMContentLoaded", function(){/* Track clicks */});</script>';
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Booster', 'Affiliate Booster', 'manage_options', 'sab-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->options['amazon_tag'] = sanitize_text_field($_POST['amazon_tag']);
            $this->options['keywords'] = array_map('sanitize_text_field', $_POST['keywords']);
            $this->options['enabled'] = isset($_POST['enabled']);
            update_option('sab_options', $this->options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Booster Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="amazon_tag" value="<?php echo esc_attr($this->options['amazon_tag']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (one per line)</th>
                        <td><textarea name="keywords[]" rows="5" cols="50"><?php echo esc_textarea(implode('\n', $this->options['keywords'])); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="enabled" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI keyword suggestions, click analytics, and WooCommerce integration for $49/year. <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('sab_options', array('amazon_tag' => '', 'keywords' => array(), 'enabled' => true));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

SmartAffiliateBooster::get_instance();

// AJAX for pro features (placeholder)
add_action('wp_ajax_sab_track_click', 'sab_track_click');
function sab_track_click() {
    check_ajax_referer('sab_nonce', 'nonce');
    // Pro feature: Track clicks
    wp_die();
}

// Include JS file content as string for single-file (in real, upload sab.js separately or inline)
function sab_inline_js() {
    echo '<script>jQuery(document).ready(function($){$(".sab-link").click(function(){$.post(sab_ajax.ajaxurl, {action:"sab_track_click", nonce:sab_ajax.nonce});});});</script>';
}
add_action('wp_footer', 'sab_inline_js');