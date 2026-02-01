/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching for passive income.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'load_options'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function load_options() {
        $this->options = get_option('smart_affiliate_options', array(
            'api_key' => '',
            'affiliate_tag' => '',
            'keywords' => array(),
            'products' => array(),
            'max_links' => 3,
            'pro' => false
        ));
    }

    public function activate() {
        add_option('smart_affiliate_options');
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || !$this->options['api_key'] || !$this->options['affiliate_tag']) {
            return $content;
        }

        $max_links = intval($this->options['max_links']);
        $inserted = 0;
        $words = explode(' ', $content);
        $new_content = '';

        foreach ($words as $word) {
            $new_content .= $word . ' ';
            foreach ($this->options['keywords'] as $keyword => $asin) {
                if (stripos($word, $keyword) !== false && $inserted < $max_links) {
                    $link = $this->get_affiliate_link($asin);
                    $new_content .= $link . ' ';
                    $inserted++;
                    break;
                }
            }
        }

        return $inserted > 0 ? $new_content : $content;
    }

    private function get_affiliate_link($asin) {
        $url = 'https://www.amazon.com/dp/' . $asin . '?tag=' . $this->options['affiliate_tag'];
        return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener">' . $this->get_product_title($asin) . '</a>';
    }

    private function get_product_title($asin) {
        // Mock product titles; Pro version would use real API
        $titles = array(
            'B08N5WRWNW' => 'Wireless Earbuds',
            'B07RF1XD36' => 'Smartwatch',
            'B08L5LG4D9' => 'Bluetooth Speaker'
        );
        return isset($titles[$asin]) ? $titles[$asin] : 'Product';
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate Inserter', 'manage_options', 'smart-affiliate', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('smart_affiliate_options', 'smart_affiliate_options');
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->options['api_key'] = sanitize_text_field($_POST['api_key']);
            $this->options['affiliate_tag'] = sanitize_text_field($_POST['affiliate_tag']);
            $this->options['max_links'] = intval($_POST['max_links']);
            $this->options['keywords'] = array_map('sanitize_text_field', $_POST['keywords']);
            $this->options['products'] = array_map('sanitize_text_field', $_POST['products']);
            update_option('smart_affiliate_options', $this->options);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="affiliate_tag" value="<?php echo esc_attr($this->options['affiliate_tag']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Amazon API Key (Pro)</th>
                        <td><input type="text" name="api_key" value="<?php echo esc_attr($this->options['api_key']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="max_links" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Keywords & ASINs</th>
                        <td>
                            <?php foreach ($this->options['keywords'] as $i => $kw): $asin = isset($this->options['products'][$i]) ? $this->options['products'][$i] : ''; ?>
                            <p><input type="text" name="keywords[]" placeholder="keyword" value="<?php echo esc_attr($kw); ?>" /> -> <input type="text" name="products[]" placeholder="ASIN" value="<?php echo esc_attr($asin); ?>" /></p>
                            <?php endforeach; ?>
                            <p><em>Add keyword -> ASIN pairs (e.g., earbuds -> B08N5WRWNW)</em></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro</strong> for real-time API lookups, analytics, A/B testing, and more! <a href="https://example.com/pro">Get Pro</a></p>
        </div>
        <?php
    }
}

new SmartAffiliateAutoInserter();