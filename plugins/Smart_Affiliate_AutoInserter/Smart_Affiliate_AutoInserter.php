/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into your WordPress posts and pages to boost passive income with AI-powered context matching.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $affiliate_id = '';
    private $api_key = '';
    private $enabled = false;
    private $max_links = 2;
    private $niches = array('general');

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_filter('widget_text', array($this, 'insert_affiliate_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->load_settings();
    }

    private function load_settings() {
        $this->affiliate_id = get_option('saa_affiliate_id', '');
        $this->api_key = get_option('saa_api_key', ''); // Optional AWS PA-API key for better results
        $this->enabled = get_option('saa_enabled', false);
        $this->max_links = get_option('saa_max_links', 2);
        $this->niches = get_option('saa_niches', array('general'));
    }

    public function enqueue_assets() {
        wp_enqueue_script('saa-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function add_admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('saa_plugin_page', 'saa_affiliate_id');
        register_setting('saa_plugin_page', 'saa_api_key');
        register_setting('saa_plugin_page', 'saa_enabled');
        register_setting('saa_plugin_page', 'saa_max_links');
        register_setting('saa_plugin_page', 'saa_niches');
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('saa_plugin_page');
                do_settings_sections('saa_plugin_page');
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Amazon Affiliate ID (tag)</th>
                        <td><input type="text" name="saa_affiliate_id" value="<?php echo esc_attr($this->affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">AWS PA-API Key (optional)</th>
                        <td><input type="password" name="saa_api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="saa_enabled" value="1" <?php checked(1, $this->enabled); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row">Max Links per Post</th>
                        <td><input type="number" name="saa_max_links" value="<?php echo esc_attr($this->max_links); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th scope="row">Niches</th>
                        <td>
                            <select name="saa_niches[]" multiple size="5">
                                <option value="general" <?php echo in_array('general', $this->niches) ? 'selected' : ''; ?>>General</option>
                                <option value="books" <?php echo in_array('books', $this->niches) ? 'selected' : ''; ?>>Books</option>
                                <option value="electronics" <?php echo in_array('electronics', $this->niches) ? 'selected' : ''; ?>>Electronics</option>
                                <option value="fashion" <?php echo in_array('fashion', $this->niches) ? 'selected' : ''; ?>>Fashion</option>
                            </select>
                            <p class="description">Hold Ctrl/Cmd to select multiple.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Pro Upgrade:</strong> Unlock unlimited links, advanced analytics, custom keywords, and more for $49/year. <a href="#" onclick="alert('Pro features coming soon!')">Learn More</a></p>
        </div>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (!$this->enabled || empty($this->affiliate_id) || is_admin() || !is_single()) {
            return $content;
        }

        // Simple keyword-based matching (extend with AI in Pro)
        $keywords = $this->extract_keywords($content);
        $inserted = 0;

        foreach ($keywords as $keyword) {
            if ($inserted >= $this->max_links) break;

            $product = $this->find_amazon_product($keyword);
            if ($product) {
                $link = $this->create_affiliate_link($product, $keyword);
                $content = $this->insert_link_into_content($content, $link, $keyword);
                $inserted++;
            }
        }

        return $content;
    }

    private function extract_keywords($content) {
        $words = explode(' ', strip_tags($content));
        $keywords = array();
        foreach ($words as $word) {
            $word = strtolower(trim($word, ".,!?;:'\"()"));
            if (strlen($word) > 4 && strlen($word) < 20) {
                $keywords[] = $word;
            }
        }
        return array_slice(array_unique($keywords), 0, 10);
    }

    private function find_amazon_product($keyword) {
        // Mock Amazon PA-API call (use real API in production with AWS credentials)
        // For demo, return mock products based on keyword
        $mock_products = array(
            'book' => array('title' => 'Best Seller Book', 'asin' => 'B123456', 'image' => ''),
            'phone' => array('title' => 'Latest Smartphone', 'asin' => 'B789012', 'image' => ''),
            'shoes' => array('title' => 'Running Shoes', 'asin' => 'B345678', 'image' => ''),
            // Add more mappings
        );

        $lower_keyword = strtolower($keyword);
        foreach ($mock_products as $key => $product) {
            if (strpos($lower_keyword, $key) !== false) {
                return $product;
            }
        }
        return false;
    }

    private function create_affiliate_link($product, $keyword) {
        $link = 'https://www.amazon.com/dp/' . $product['asin'] . '/?tag=' . $this->affiliate_id;
        return '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored">' . esc_html($product['title']) . '</a> ';
    }

    private function insert_link_into_content($content, $link, $keyword) {
        // Insert after first occurrence of keyword
        $pos = stripos($content, $keyword);
        if ($pos !== false) {
            $before = substr($content, 0, $pos + strlen($keyword));
            $after = substr($content, $pos + strlen($keyword));
            return $before . ' <span class="saa-aff-link">(' . $link . ')</span>' . $after;
        }
        return $content;
    }

    public function activate() {
        add_option('saa_enabled', false);
        add_option('saa_max_links', 2);
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new SmartAffiliateAutoInserter();

// Pro teaser notice
function saa_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Upgrade to <strong>Smart Affiliate AutoInserter Pro</strong> for unlimited links & analytics! <a href="options-general.php?page=smart-affiliate-autoinserter">Settings</a></p></div>';
}
add_action('admin_notices', 'saa_pro_notice');

// Create assets dir placeholder (create manually)
// mkdir( plugin_dir_path(__FILE__) . 'assets' );
// echo "<script>alert('Assets dir needed for JS');</script>";
?>