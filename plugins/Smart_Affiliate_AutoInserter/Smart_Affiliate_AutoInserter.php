/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content to maximize earnings. Freemium model with Pro upgrades.
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
        $this->options = get_option('smart_affiliate_options', array());
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || !is_single()) return $content;

        $keywords = $this->options['keywords'] ?? array();
        $links = $this->options['links'] ?? array();

        if (empty($keywords) || empty($links)) return $content;

        foreach ($keywords as $index => $keyword) {
            if (isset($links[$index])) {
                $link = $links[$index];
                $pattern = '/\b' . preg_quote($keyword, '/') . '\b/ui';
                $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener sponsored">' . $keyword . '</a>';
                $content = preg_replace($pattern, $replacement, $content, 1);
            }
        }

        return $content;
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
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="">
                <?php wp_nonce_field('smart_affiliate_save', 'smart_affiliate_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Keywords & Links', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <div id="keyword-links">
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                    <p>
                                        <label>Keyword <?php echo $i+1; ?>: <input type="text" name="smart_affiliate_options[keywords][<?php echo $i; ?>]" value="<?php echo esc_attr($options['keywords'][$i] ?? ''); ?>" /></label>
                                        <label>Affiliate Link: <input type="url" name="smart_affiliate_options[links][<?php echo $i; ?>]" value="<?php echo esc_attr($options['links'][$i] ?? ''); ?>" /></label>
                                    </p>
                                <?php endfor; ?>
                            </div>
                            <p><a href="#" id="add-keyword">Add More</a> | <strong>Pro:</strong> Unlimited + AI Auto-Detection</p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Enable Auto-Insertion', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <input type="checkbox" name="smart_affiliate_options[enabled]" <?php checked($options['enabled'] ?? 1); ?> value="1" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div class="pro-upsell">
                <h2>Upgrade to Pro ($49/year)</h2>
                <ul>
                    <li>AI-powered keyword detection</li>
                    <li>Unlimited keywords & links</li>
                    <li>Click analytics & A/B testing</li>
                    <li>Amazon, ShareASale integrations</li>
                </ul>
                <p><a href="https://example.com/pro" class="button button-primary">Get Pro Now</a></p>
            </div>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-keyword').click(function(e) {
                e.preventDefault();
                var index = $('#keyword-links p').length;
                $('#keyword-links').append('<p><label>Keyword ' + (index+1) + ': <input type="text" name="smart_affiliate_options[keywords][' + index + ']" /></label><label>Affiliate Link: <input type="url" name="smart_affiliate_options[links][' + index + ']" /></label></p>');
            });
        });
        </script>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_options', array('enabled' => 1));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

new SmartAffiliateAutoInserter();

// Pro check (simplified)
function is_pro_version() {
    return false; // Set to true for pro
}

// Minified JS for assets/script.js (inline for single file)
/*
$(document).ready(function() {
    // JS code here if needed beyond admin
});
*/