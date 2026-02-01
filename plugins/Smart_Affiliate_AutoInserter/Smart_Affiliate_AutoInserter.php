/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your posts and pages using keyword matching.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $affiliate_id = '';
    private $keywords = [];
    private $products = [];

    public function __construct() {
        add_action('init', [$this, 'init']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_filter('the_content', [$this, 'insert_affiliate_links']);
        register_activation_hook(__FILE__, [$this, 'activate']);
    }

    public function init() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->keywords = get_option('saa_keywords', ['laptop', 'phone', 'book', 'headphones']);
        $this->products = get_option('saa_products', [
            'laptop' => 'https://amazon.com/dp/B08N5WRWNW?tag=YOURAFFILIATEID',
            'phone' => 'https://amazon.com/dp/B0BHHD8K2H?tag=YOURAFFILIATEID',
            'book' => 'https://amazon.com/dp/059308189X?tag=YOURAFFILIATEID',
            'headphones' => 'https://amazon.com/dp/B08PZD4Q5T?tag=YOURAFFILIATEID'
        ]);
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('saa-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'smart-affiliate', [$this, 'settings_page']);
    }

    public function admin_init() {
        register_setting('saa_options', 'saa_affiliate_id');
        register_setting('saa_options', 'saa_keywords');
        register_setting('saa_options', 'saa_products');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_options'); ?>
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate ID</th>
                        <td><input type="text" name="saa_affiliate_id" value="<?php echo esc_attr(get_option('saa_affiliate_id')); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (comma-separated)</th>
                        <td><input type="text" name="saa_keywords" value="<?php echo esc_attr(implode(',', get_option('saa_keywords', []))); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Products (JSON: {"keyword":"affiliate_url"})</th>
                        <td><textarea name="saa_products" class="large-text" rows="10"><?php echo esc_textarea(json_encode(get_option('saa_products', []))); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited keywords, AI auto-matching, click tracking & analytics for $49/year. <a href="#" onclick="alert('Pro features coming soon!')">Upgrade Now</a></p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || empty($this->affiliate_id)) return $content;

        global $post;
        $words = explode(' ', $content);
        $inserted = 0;
        $max_inserts = 3;

        foreach ($words as $key => &$word) {
            foreach ($this->keywords as $keyword) {
                if (stripos($word, $keyword) !== false && $inserted < $max_inserts) {
                    if (isset($this->products[$keyword])) {
                        $link = str_replace('YOURAFFILIATEID', $this->affiliate_id, $this->products[$keyword]);
                        $word = str_replace($keyword, '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">' . $keyword . '</a>', $word);
                        $inserted++;
                    }
                }
            }
        }
        return implode(' ', $words);
    }

    public function activate() {
        add_option('saa_affiliate_id', '');
        add_option('saa_keywords', ['laptop', 'phone', 'book', 'headphones']);
        add_option('saa_products', [
            'laptop' => 'https://amazon.com/dp/B08N5WRWNW?tag=YOURAFFILIATEID',
            'phone' => 'https://amazon.com/dp/B0BHHD8K2H?tag=YOURAFFILIATEID',
            'book' => 'https://amazon.com/dp/059308189X?tag=YOURAFFILIATEID',
            'headphones' => 'https://amazon.com/dp/B08PZD4Q5T?tag=YOURAFFILIATEID'
        ]);
    }
}

new SmartAffiliateAutoInserter();

// Pro Upsell Notice
function saa_pro_upsell_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter Pro:</strong> Upgrade for AI-powered matching, unlimited sites, and analytics! <a href="https://example.com/pro">Get Pro ($49/year)</a></p></div>';
}
add_action('admin_notices', 'saa_pro_upsell_notice');
?>