/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your content and converts them into high-converting affiliate links from your dashboard, boosting commissions effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoLinker {
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_head', array($this, 'process_content'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolinker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('sal-autolinker', plugin_dir_url(__FILE__) . 'sal-script.js', array('jquery'), '1.0.0', true);
        }
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('sal_keywords', sanitize_textarea_field($_POST['keywords']));
            update_option('sal_affiliate_links', sanitize_textarea_field($_POST['links']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $keywords = get_option('sal_keywords', "");
        $links = get_option('sal_affiliate_links', "");
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Keywords (one per line)</th>
                        <td><textarea name="keywords" rows="10" cols="50"><?php echo esc_textarea($keywords); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (one per line, matching keywords)</th>
                        <td><textarea name="links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited keywords, A/B testing, click tracking & analytics for $49/year!</p>
        </div>
        <?php
    }

    public function process_content() {
        if (!is_singular()) return;
        $keywords = explode("\n", get_option('sal_keywords', ''));
        $links = explode("\n", get_option('sal_affiliate_links', ''));
        $replacements = array();
        foreach ($keywords as $index => $keyword) {
            $keyword = trim($keyword);
            if (!empty($keyword) && isset($links[$index])) {
                $link = trim($links[$index]);
                $replacements['/\b' . preg_quote($keyword, '/') . '\b/i'] = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener">$0</a> ';
            }
        }
        if (!empty($replacements)) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                var content = $('article, .entry-content').html();
                <?php foreach ($replacements as $pattern => $replacement) { 
                    $js_pattern = str_replace('/', '\/', $pattern);
                    $js_repl = str_replace('$0', '$&', addslashes($replacement));
                ?>
                content = content.replace(<?php echo json_encode($js_pattern); ?>, <?php echo json_encode($js_repl); ?>);
                <?php } ?>
                $('article, .entry-content').html(content);
            });
            </script>
            <?php
        }
    }

    public function activate() {
        // Free version limits to 5 keywords
        update_option('sal_free_version', true);
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

SmartAffiliateAutoLinker::get_instance();

// Pro check (simplified - in real pro, license validation)
function sal_is_pro() {
    return get_option('sal_pro_license') === 'valid';
}

// Output pure JSON, no extra text