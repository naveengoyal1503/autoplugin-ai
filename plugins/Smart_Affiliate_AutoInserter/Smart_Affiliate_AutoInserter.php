/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages based on content keywords.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $affiliate_tag;
    private $keywords;
    private $products;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
        $this->keywords = get_option('saa_keywords', array('laptop' => 'https://amazon.com/dp/B08N5WRWNW?tag=YOURTAG'));
        $this->products = get_option('saa_products', array());
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (is_admin() || empty($this->affiliate_tag)) {
            return $content;
        }

        $words = explode(' ', strtolower(strip_tags($content)));
        foreach ($this->keywords as $keyword => $link) {
            if (in_array(strtolower($keyword), $words)) {
                $link_text = ucfirst($keyword);
                $aff_link = '<a href="' . esc_url($link . ($this->affiliate_tag ? '&tag=' . $this->affiliate_tag : '')) . '" target="_blank" rel="nofollow sponsored">' . esc_html($link_text) . '</a>';
                $content = preg_replace('/\b' . preg_quote(strtolower($keyword), '/') . '\b/i', $aff_link, $content, 1);
            }
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saa_settings', 'saa_affiliate_tag');
        register_setting('saa_settings', 'saa_keywords');
        register_setting('saa_settings', 'saa_products');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="saa_affiliate_tag" value="<?php echo esc_attr($this->affiliate_tag); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords & Links (JSON: {"keyword":"amazon-link"})</th>
                        <td><textarea name="saa_keywords" rows="10" cols="50"><?php echo esc_textarea(json_encode($this->keywords)); ?></textarea><br>
                        Example: {"laptop":"https://amazon.com/dp/B08N5WRWNW","phone":"https://amazon.com/dp/B0B2M1G3K1"}</td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Premium:</strong> Advanced keyword matching, analytics, and more! <a href="https://example.com/premium">Get Premium</a></p>
        </div>
        <?php
    }

    public function activate() {
        add_option('saa_affiliate_tag', '');
        add_option('saa_keywords', json_encode(array('laptop' => 'https://amazon.com/dp/B08N5WRWNW', 'phone' => 'https://amazon.com/dp/B0B2M1G3K1')));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new SmartAffiliateAutoInserter();

// Freemium notice
function saa_freemium_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock advanced features with <a href="' . admin_url('options-general.php?page=smart-affiliate') . '">Smart Affiliate Pro</a>! Analytics, A/B testing & more.</p></div>';
}
add_action('admin_notices', 'saa_freemium_notice');

// Create assets dir placeholder (in real plugin, include files)
if (!file_exists(plugin_dir_path(__FILE__) . 'assets')) {
    mkdir(plugin_dir_path(__FILE__) . 'assets', 0755, true);
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/script.js', '// Premium JS features\nconsole.log("Smart Affiliate Active");');
}
?>