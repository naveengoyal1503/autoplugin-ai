/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching. Boost your affiliate earnings effortlessly.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private static $instance = null;
    private $api_key;
    private $affiliate_tag;
    private $keywords;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_settings'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->api_key = get_option('saa_api_key', '');
        $this->affiliate_tag = get_option('saa_affiliate_tag', '');
        $this->keywords = get_option('saa_keywords', array(
            'laptop' => 'B08N5WRWNW',
            'phone' => 'B0C7W46YXK',
            'book' => 'B0B1L8ZJ8Q'
        )); // Default keywords with sample ASINs
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->affiliate_tag)) {
            return $content;
        }

        global $post;
        if (!$post || in_array($post->post_status, array('draft', 'private'))) {
            return $content;
        }

        $words = explode(' ', $content);
        $inserted = 0;
        $max_links = 3; // Free version limit

        foreach ($words as $key => $word) {
            foreach ($this->keywords as $keyword => $asin) {
                if (stripos($word, $keyword) !== false && $inserted < $max_links) {
                    $link = $this->get_amazon_link($asin);
                    $words[$key] = str_replace($word, '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">' . esc_html($word) . '</a>', $word);
                    $inserted++;
                }
            }
        }

        return implode(' ', $words);
    }

    private function get_amazon_link($asin) {
        $url = 'https://www.amazon.com/dp/' . $asin . '?tag=' . $this->affiliate_tag;
        return $url;
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('saa_api_key', sanitize_text_field($_POST['api_key']));
            update_option('saa_affiliate_tag', sanitize_text_field($_POST['affiliate_tag']));
            update_option('saa_keywords', $_POST['keywords']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="affiliate_tag" value="<?php echo esc_attr($this->affiliate_tag); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Keywords (JSON: {"keyword":"ASIN"})</th>
                        <td><textarea name="keywords" rows="10" cols="50"><?php echo esc_textarea(json_encode($this->keywords)); ?></textarea><br>
                        <small>Free version limits to 3 links per post. Upgrade to Pro for unlimited!</small></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlimited links, analytics, A/B testing. <a href="https://example.com/pro" target="_blank">Get Pro ($29/year)</a></p>
        </div>
        <?php
    }

    public function admin_settings() {
        register_setting('saa_options', 'saa_api_key');
        register_setting('saa_options', 'saa_affiliate_tag');
        register_setting('saa_options', 'saa_keywords');
    }

    public function activate() {
        add_option('saa_keywords', array('laptop' => 'B08N5WRWNW'));
    }

    public function deactivate() {}
}

SmartAffiliateAutoInserter::get_instance();

// Pro upsell notice
function saa_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock unlimited links with <a href="' . admin_url('options-general.php?page=smart-affiliate-autoinserter') . '">Smart Affiliate AutoInserter Pro</a> - Only $29/year!</p></div>';
}
add_action('admin_notices', 'saa_pro_notice');
?>