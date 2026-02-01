/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using keyword matching and AI optimization (Pro).
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

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter');
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function auto_insert_links($content) {
        if (is_admin() || !is_single()) return $content;

        $settings = get_option('smart_affiliate_settings', array());
        $affiliates = $settings['affiliates'] ?? array();
        if (empty($affiliates)) return $content;

        $words = explode(' ', strip_tags($content));
        $inserted = 0;
        $max_inserts = $settings['max_inserts'] ?? 3;

        foreach ($affiliates as $aff) {
            if ($inserted >= $max_inserts) break;
            $keyword = strtolower($aff['keyword']);
            $link = $aff['link'];
            $text = $aff['text'] ?? $keyword;

            foreach ($words as $i => $word) {
                if (strpos(strtolower($word), $keyword) !== false && rand(1, 3) === 1) { // 33% chance to insert
                    $words[$i] = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener">' . esc_html($text) . '</a>';
                    $inserted++;
                    break;
                }
            }
        }

        $content = implode(' ', $words);
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
        register_setting('smart_affiliate_group', 'smart_affiliate_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('smart_affiliate_group'); ?>
                <?php do_settings_sections('smart_affiliate_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="smart_affiliate_settings[max_inserts]" value="<?php echo esc_attr((get_option('smart_affiliate_settings')['max_inserts'] ?? 3)); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td>
                            <div id="affiliate-list">
                                <?php
                                $settings = get_option('smart_affiliate_settings', array());
                                $affiliates = $settings['affiliates'] ?? array();
                                foreach ($affiliates as $i => $aff) {
                                    echo '<div class="affiliate-item">';
                                    echo '<input type="text" name="smart_affiliate_settings[affiliates][' . $i . '][keyword]" placeholder="Keyword" value="' . esc_attr($aff['keyword'] ?? '') . '" />';
                                    echo '<input type="text" name="smart_affiliate_settings[affiliates][' . $i . '][text]" placeholder="Link Text" value="' . esc_attr($aff['text'] ?? '') . '" />';
                                    echo '<input type="url" name="smart_affiliate_settings[affiliates][' . $i . '][link]" placeholder="Affiliate URL" value="' . esc_url($aff['link'] ?? '') . '" />';
                                    echo '<button type="button" class="button remove-aff">Remove</button>';
                                    echo '</div>';
                                }
                                ?>
                            </div>
                            <button type="button" id="add-affiliate" class="button">Add Affiliate</button>
                            <p class="description">Pro version includes AI keyword suggestions and performance analytics.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <p><strong>Upgrade to Pro:</strong> Unlock AI optimization, unlimited affiliates, and analytics for $9/month. <a href="#" onclick="alert('Pro upgrade link placeholder')">Get Pro</a></p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let counter = <?php echo count($affiliates); ?>;
            $('#add-affiliate').click(function() {
                $('#affiliate-list').append(
                    '<div class="affiliate-item">' +
                    '<input type="text" name="smart_affiliate_settings[affiliates][" + counter + '][keyword]" placeholder="Keyword" />' +
                    '<input type="text" name="smart_affiliate_settings[affiliates][" + counter + '][text]" placeholder="Link Text" />' +
                    '<input type="url" name="smart_affiliate_settings[affiliates][" + counter + '][link]" placeholder="Affiliate URL" />' +
                    '<button type="button" class="button remove-aff">Remove</button>' +
                    '</div>'
                );
                counter++;
            });
            $(document).on('click', '.remove-aff', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <style>
        .affiliate-item { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .affiliate-item input { margin-right: 10px; width: 20%; }
        </style>
        <?php
    }

    public function activate() {
        add_option('smart_affiliate_settings', array('max_inserts' => 3, 'affiliates' => array()));
    }

    public function deactivate() {
        // Cleanup if needed
    }
}

SmartAffiliateAutoInserter::get_instance();

// Create assets dir placeholder (in real dist, include files)
if (!file_exists(plugin_dir_path(__FILE__) . 'assets')) {
    mkdir(plugin_dir_path(__FILE__) . 'assets', 0755, true);
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/script.js', '// Placeholder JS\njQuery(function($){});');
}
?>