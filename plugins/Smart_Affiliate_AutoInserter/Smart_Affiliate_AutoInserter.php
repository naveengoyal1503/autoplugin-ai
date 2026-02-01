/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content to boost revenue. Freemium model with premium add-ons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array(
            'api_key' => '',
            'affiliate_links' => array(),
            'enabled' => true,
            'max_links' => 3,
            'pro_nag' => true
        ));
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (!$this->options['enabled'] || is_admin() || in_the_loop()) {
            return $content;
        }

        $paragraphs = explode('</p>', $content);
        $link_count = 0;
        $max_links = intval($this->options['max_links']);

        foreach ($paragraphs as &$paragraph) {
            if ($link_count >= $max_links) break;

            $link = $this->find_relevant_link($paragraph);
            if ($link && stripos($paragraph, 'rel="nofollow"') === false) {
                $anchor = $this->get_anchor_text($paragraph);
                $paragraph = str_replace($anchor, '<a href="' . esc_url($link['url']) . '" rel="nofollow" target="_blank">' . esc_html($anchor) . '</a>', $paragraph);
                $link_count++;
            }
        }

        return implode('</p>', $paragraphs);
    }

    private function find_relevant_link($text) {
        $keywords = array('buy', 'purchase', 'best', 'review', 'top', 'amazon', 'clickbank');
        foreach ($keywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                return $this->options['affiliate_links'][$keyword] ?? array('url' => '#', 'text' => 'Affiliate Product');
            }
        }
        return false;
    }

    private function get_anchor_text($text) {
        preg_match('/\b\w+\b/', strip_tags($text), $matches);
        return $matches ?? 'Learn More';
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function admin_page() {
        if (isset($_POST['submit'])) {
            update_option('smart_affiliate_settings', $_POST['smart_affiliate_settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="">
                <?php settings_fields('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="smart_affiliate_settings[enabled]" value="1" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_settings[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links (Keyword: URL)</th>
                        <td>
                            <?php
                            $links = $this->options['affiliate_links'];
                            $i = 0;
                            foreach ($links as $kw => $data) {
                                echo '<p><input type="text" name="smart_affiliate_settings[affiliate_links][' . $i . '][keyword]" placeholder="Keyword" value="' . esc_attr($kw) . '" style="width:150px;"> : <input type="url" name="smart_affiliate_settings[affiliate_links][' . $i . '][url]" placeholder="Affiliate URL" value="' . esc_url($data['url']) . '" style="width:300px;"></p>';
                                $i++;
                            }
                            ?>
                            <p><em>Add more: keyword=url in settings.</em></p>
                            <p><strong>Upgrade to Pro for AI keyword detection & analytics!</strong></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_settings', array(
            'enabled' => true,
            'max_links' => 3,
            'affiliate_links' => array(
                'amazon' => array('url' => 'https://amazon.com'),
                'buy' => array('url' => 'https://example-affiliate.com')
            )
        ));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new SmartAffiliateAutoInserter();

// Pro upsell nag
function smart_affiliate_nag() {
    if (current_user_can('manage_options') && !defined('SMART_AFFILIATE_PRO')) {
        echo '<div class="notice notice-info"><p>Unlock AI features and unlimited links with <a href="https://example.com/pro" target="_blank">Smart Affiliate Pro</a> - Starting at $49/year!</p></div>';
    }
}
add_action('admin_notices', 'smart_affiliate_nag');

// Create assets dir placeholder (in real plugin, include files)
if (!file_exists(plugin_dir_path(__FILE__) . 'assets')) {
    mkdir(plugin_dir_path(__FILE__) . 'assets', 0755, true);
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/frontend.js', '// Frontend JS for tracking clicks\nconsole.log("Smart Affiliate loaded");');
}
?>