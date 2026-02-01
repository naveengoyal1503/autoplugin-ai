/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant Amazon affiliate links into posts and pages based on keyword matching. Boost your affiliate earnings effortlessly.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_filter('widget_text', array($this, 'insert_affiliate_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_settings', array());
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_settings_group', 'smart_affiliate_settings', array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $input['keywords'] = array_map('sanitize_text_field', $input['keywords'] ?? array());
        $input['links'] = array_map('esc_url_raw', $input['links'] ?? array());
        $input['max_links'] = max(1, intval($input['max_links'] ?? 3));
        $input['enabled'] = isset($input['enabled']) ? 1 : 0;
        return $input;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_settings_group'); ?>
                <?php do_settings_sections('smart_affiliate_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="enabled"><?php _e('Enable Auto-Insertion', 'smart-affiliate-autoinserter'); ?></label></th>
                        <td><input type="checkbox" id="enabled" name="smart_affiliate_settings[enabled]" value="1" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Keywords & Links', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <table id="keyword-links-table">
                                <thead>
                                    <tr><th><?php _e('Keyword', 'smart-affiliate-autoinserter'); ?></th><th><?php _e('Affiliate Link', 'smart-affiliate-autoinserter'); ?></th><th></th></tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $keywords = $this->options['keywords'] ?? array('');
                                    $links = $this->options['links'] ?? array('');
                                    for ($i = 0; $i < max(count($keywords), count($links)); $i++) {
                                        echo '<tr><td><input type="text" name="smart_affiliate_settings[keywords][]" value="' . esc_attr($keywords[$i] ?? '') . '" /></td>';
                                        echo '<td><input type="url" name="smart_affiliate_settings[links][]" value="' . esc_url($links[$i] ?? '') . '" /></td>';
                                        echo '<td><button type="button" class="button remove-row">Remove</button></td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <button type="button" id="add-row" class="button">Add Row</button>
                            <p class="description"><?php _e('Enter keywords to match in content and corresponding Amazon affiliate links.', 'smart-affiliate-autoinserter'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_links"><?php _e('Max Links per Post', 'smart-affiliate-autoinserter'); ?></label></th>
                        <td><input type="number" id="max_links" name="smart_affiliate_settings[max_links]" value="<?php echo esc_attr($this->options['max_links'] ?? 3); ?>" min="1" max="10" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2><?php _e('Pro Features', 'smart-affiliate-autoinserter'); ?></h2>
            <p><?php _e('Upgrade to Pro for auto-keyword discovery, A/B testing, and analytics. <a href="#pro">Get Pro</a>', 'smart-affiliate-autoinserter'); ?></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-row').click(function() {
                $('#keyword-links-table tbody').append('<tr><td><input type="text" name="smart_affiliate_settings[keywords][]" /></td><td><input type="url" name="smart_affiliate_settings[links][]" /></td><td><button type="button" class="button remove-row">Remove</button></td></tr>');
            });
            $(document).on('click', '.remove-row', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (!isset($this->options['enabled']) || !$this->options['enabled'] || is_admin()) {
            return $content;
        }

        $keywords = $this->options['keywords'] ?? array();
        $links = $this->options['links'] ?? array();
        $max_links = intval($this->options['max_links'] ?? 3);
        $inserted = 0;

        if (empty($keywords) || count($keywords) !== count($links)) {
            return $content;
        }

        $words = explode(' ', $content);
        foreach ($words as $index => &$word) {
            if ($inserted >= $max_links) break;

            foreach ($keywords as $k_index => $keyword) {
                if (stripos($word, $keyword) !== false && !empty($links[$k_index])) {
                    $link_html = '<a href="' . esc_url($links[$k_index]) . '" target="_blank" rel="nofollow sponsored">' . esc_html($word) . '</a> ';
                    $word = $link_html;
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $words);
    }

    public function activate() {
        add_option('smart_affiliate_settings', array(
            'enabled' => 1,
            'max_links' => 3,
            'keywords' => array(''),
            'links' => array('')
        ));
    }
}

new SmartAffiliateAutoInserter();

// Pro upsell notice
function smart_affiliate_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>' . __('Upgrade to <strong>Smart Affiliate AutoInserter Pro</strong> for advanced features like auto-keyword discovery and analytics!', 'smart-affiliate-autoinserter') . '</p></div>';
}
add_action('admin_notices', 'smart_affiliate_pro_notice');
?>