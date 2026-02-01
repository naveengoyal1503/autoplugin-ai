/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content to boost earnings. Freemium model.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'auto_insert_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (!session_id()) {
            session_start();
        }
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
        wp_enqueue_style('smart-affiliate-css', plugin_dir_url(__FILE__) . 'assets/style.css', array(), '1.0.0');
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_affiliate_links', sanitize_textarea_field($_POST['affiliate_links']));
            update_option('smart_affiliate密度', intval($_POST['density']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $links = get_option('smart_affiliate_links', "amazon:https://amazon.com:keyword:Your Amazon Affiliate Link\nclickbank:https://clickbank.net:weightloss:Your ClickBank Link");
        $density = get_option('smart_affiliate_density', 5);
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Affiliate Links (format: platform:url:keywords:affiliate_url)</th>
                        <td><textarea name="affiliate_links" rows="10" cols="50"><?php echo esc_textarea($links); ?></textarea></td>
                    </tr>
                    <tr>
                        <th>Insertion Density (% of paragraphs)</th>
                        <td><input type="number" name="density" value="<?php echo esc_attr($density); ?>" min="1" max="20" /> %</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> AI keyword matching, analytics, A/B testing. <a href="#" onclick="alert('Pro features coming soon!')">Get Pro</a></p>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (is_admin() || !is_single()) return $content;

        $links_str = get_option('smart_affiliate_links', '');
        if (empty($links_str)) return $content;

        $links = explode("\n", $links_str);
        $affiliates = array();
        foreach ($links as $link) {
            $parts = explode(':', trim($link), 4);
            if (count($parts) == 4) {
                $affiliates[] = array(
                    'platform' => $parts,
                    'base_url' => $parts[1],
                    'keywords' => explode(',', $parts[2]),
                    'aff_url' => $parts[3]
                );
            }
        }

        $paragraphs = explode("\n\n", $content);
        $density = get_option('smart_affiliate_density', 5) / 100;
        $total = count($paragraphs);
        $insert_count = max(1, intval($total * $density));

        $inserted = 0;
        for ($i = 0; $i < $total && $inserted < $insert_count; $i++) {
            if (rand(1, 100) <= 30 && strlen($paragraphs[$i]) > 50) { // 30% chance per para
                $para_lower = strtolower($paragraphs[$i]);
                foreach ($affiliates as $aff) {
                    foreach ($aff['keywords'] as $kw) {
                        if (strpos($para_lower, strtolower($kw)) !== false) {
                            $link_text = ucfirst($kw);
                            $link_html = '<a href="' . esc_url($aff['aff_url']) . '" target="_blank" rel="nofollow sponsored">' . esc_html($link_text) . '</a>';
                            $paragraphs[$i] = preg_replace('/\b' . preg_quote(strtolower($kw), '/') . '\b/i', $link_html, $paragraphs[$i], 1);
                            $inserted++;
                            break 2;
                        }
                    }
                }
            }
        }

        return implode("\n\n", $paragraphs);
    }

    public function activate() {
        if (!get_option('smart_affiliate_links')) {
            update_option('smart_affiliate_links', "amazon:https://amazon.com:weight loss,fitness,diet:YOUR_AMAZON_LINK\nclickbank:https://clickbank.net:make money,passive income:YOUR_CLICKBANK_LINK");
        }
        if (!get_option('smart_affiliate_density')) {
            update_option('smart_affiliate_density', 5);
        }
    }

    public function deactivate() {
        // No-op
    }
}

SmartAffiliateAutoInserter::get_instance();

// Pro teaser notice
function smart_affiliate_admin_notice() {
    if (!current_user_can('manage_options')) return;
    ?>
    <div class="notice notice-info is-dismissible">
        <p><strong>Smart Affiliate AutoInserter Pro:</strong> Unlock AI optimization & analytics! <a href="#" onclick="alert('Visit our site for Pro upgrade')">Upgrade Now</a></p>
    </div>
    <?php
}
add_action('admin_notices', 'smart_affiliate_admin_notice');

// Prevent direct access
if (!defined('ABSPATH')) exit;
?>