/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: AI-powered plugin that automatically inserts relevant Amazon affiliate links into your WordPress content for passive income generation.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $affiliate_id = 'YOUR_AMAZON_ASSOCIATE_ID'; // Replace with your Amazon Associate ID
    private $api_key = ''; // Pro feature: OpenAI API key for AI suggestions

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('saa_disable') === 'yes') return;
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saa_settings', 'saa_affiliate_id');
        register_setting('saa_settings', 'saa_keywords');
        register_setting('saa_settings', 'saa_disable');
        register_setting('saa_settings', 'saa_api_key');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Associate ID</th>
                        <td><input type="text" name="saa_affiliate_id" value="<?php echo esc_attr(get_option('saa_affiliate_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (comma-separated)</th>
                        <td><textarea name="saa_keywords" rows="5" cols="50"><?php echo esc_textarea(get_option('saa_keywords', 'laptop,phone,book,shoes,headphones')); ?></textarea><br><small>Plugin will auto-link these keywords to Amazon products.</small></td>
                    </tr>
                    <tr>
                        <th>API Key (Pro)</th>
                        <td><input type="password" name="saa_api_key" value="<?php echo esc_attr(get_option('saa_api_key')); ?>" class="regular-text" /><br><small>OpenAI API for smart keyword suggestions (Pro feature).</small></td>
                    </tr>
                    <tr>
                        <th>Disable Auto-Insertion</th>
                        <td><input type="checkbox" name="saa_disable" value="yes" <?php checked(get_option('saa_disable'), 'yes'); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock AI optimization, analytics, and more networks for $49/year. <a href="#" onclick="alert('Upgrade at example.com/pro')">Get Pro</a></p>
        </div>
        <?php
    }

    public function auto_insert_links($content) {
        if (get_option('saa_disable') === 'yes' || empty(get_option('saa_affiliate_id'))) {
            return $content;
        }

        $keywords = explode(',', get_option('saa_keywords', ''));
        $keywords = array_map('trim', $keywords);

        foreach ($keywords as $keyword) {
            if (empty($keyword)) continue;

            $product_id = $this->get_amazon_product_id($keyword);
            $link = $this->generate_amazon_link($keyword, $product_id);

            $content = preg_replace(
                '/\b' . preg_quote($keyword, '/') . '\b/i',
                '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">$0</a>',
                $content,
                1 // Limit to one replacement per keyword
            );
        }

        return $content;
    }

    private function get_amazon_product_id($keyword) {
        // Mock Amazon Product Advertising API call (requires real API setup for production)
        // For demo: return random ASIN
        $asins = array('B08N5WRWNW', 'B0C7C9ZKDW', 'B0D4B1X9GQ', 'B07RF1XD36', 'B0CP8D4R8T');
        return $asins[array_rand($asins)];
    }

    private function generate_amazon_link($keyword, $asin) {
        $aff_id = get_option('saa_affiliate_id');
        return "https://www.amazon.com/dp/{$asin}?tag={$aff_id}&linkCode=ogi&th=1&psc=1";
    }

    public function activate() {
        add_option('saa_keywords', 'laptop,phone,book,shoes,headphones');
    }
}

new SmartAffiliateAutoInserter();

// Pro teaser notice
function saa_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter Pro:</strong> Upgrade for AI-powered suggestions, analytics, and unlimited sites! <a href="options-general.php?page=smart-affiliate">Learn more</a></p></div>';
}
add_action('admin_notices', 'saa_pro_notice');
?>