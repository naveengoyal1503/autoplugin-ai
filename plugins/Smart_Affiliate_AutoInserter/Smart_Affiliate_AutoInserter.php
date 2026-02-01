/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages using keyword matching to maximize affiliate earnings.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array(
            'amazon_tag' => '',
            'keywords' => array(),
            'enabled' => true,
            'max_links' => 3,
            'pro' => false
        ));
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (!is_single() || !$this->options['enabled'] || empty($this->options['amazon_tag'])) {
            return $content;
        }

        $words = explode(' ', strip_tags($content));
        $inserted = 0;
        $max = intval($this->options['max_links']);

        foreach ($words as $index => &$word) {
            foreach ($this->options['keywords'] as $keyword => $product) {
                if (stripos($word, $keyword) !== false && $inserted < $max) {
                    $aff_link = $this->get_affiliate_link($product, $this->options['amazon_tag']);
                    $word = '<a href="' . esc_url($aff_link) . '" target="_blank" rel="nofollow sponsored">' . esc_html($product['name']) . '</a> ';
                    $inserted++;
                }
            }
        }

        return implode(' ', $words);
    }

    private function get_affiliate_link($product, $tag) {
        // Simple keyword-based Amazon search link
        $search = urlencode($product['name']);
        return "https://www.amazon.com/s?k={$search}&tag={$tag}";
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate Settings',
            'Affiliate AutoInserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_options_group', 'smart_affiliate_options');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_affiliate_options', $_POST['smart_affiliate_options']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = $this->options;
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="">
                <?php settings_fields('smart_affiliate_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_options[enabled]" <?php checked($options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Amazon Affiliate Tag</th>
                        <td><input type="text" name="smart_affiliate_options[amazon_tag]" value="<?php echo esc_attr($options['amazon_tag']); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_options[max_links]" value="<?php echo esc_attr($options['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Keywords & Products</th>
                        <td>
                            <div id="keywords-container">
                                <?php
                                if (!empty($options['keywords'])) {
                                    foreach ($options['keywords'] as $kw => $prod) {
                                        echo $this->render_keyword_row($kw, $prod);
                                    }
                                } else {
                                    echo $this->render_keyword_row('', array('name' => '', 'asin' => ''));
                                }
                                ?>
                            </div>
                            <button type="button" id="add-keyword">Add Keyword</button>
                            <p class="description">Enter keyword to match and product details. Pro version supports ASIN direct links and AI suggestions.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <script>
            jQuery(document).ready(function($) {
                $('#add-keyword').click(function() {
                    $('#keywords-container').append(<?php echo json_encode($this->render_keyword_row('', array('name' => '', 'asin' => ''))); ?>);
                });
            });
            </script>
        </div>
        <?php
    }

    private function render_keyword_row($keyword, $product) {
        return '<div class="keyword-row">'
            . '<input type="text" name="smart_affiliate_options[keywords][' . esc_attr($keyword) . '][name]" placeholder="Product Name" value="' . esc_attr($product['name']) . '" /> '
            . '<input type="text" name="smart_affiliate_options[keywords][' . esc_attr($keyword) . '][asin]" placeholder="ASIN (Pro)" value="' . esc_attr($product['asin']) . '" /> '
            . '<input type="text" placeholder="Keyword" value="' . esc_attr($keyword) . '" onchange="updateKey(this.value)" /> '
            . '<button type="button" class="remove-row">Remove</button></div>';
    }

    public function activate() {
        add_option('smart_affiliate_options', array(
            'amazon_tag' => '',
            'keywords' => array(),
            'enabled' => true,
            'max_links' => 3,
            'pro' => false
        ));
    }
}

new SmartAffiliateAutoInserter();

// Freemium notice
function smart_affiliate_freemium_notice() {
    if (!get_option('smart_affiliate_options')['pro']) {
        echo '<div class="notice notice-info"><p>Upgrade to Pro for advanced features like AI keyword matching and analytics! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
}
add_action('admin_notices', 'smart_affiliate_freemium_notice');
?>