/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects content keywords and converts them into high-converting affiliate links with performance tracking.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoLinker {
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
        add_filter('the_content', array($this, 'auto_link_affiliates'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolinker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saal-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function auto_link_affiliates($content) {
        if (is_admin() || !is_single()) {
            return $content;
        }

        $keywords = get_option('saal_keywords', array());
        $settings = get_option('saal_settings', array('max_links' => 3));

        foreach ($keywords as $keyword => $link) {
            $regex = '/\b' . preg_quote($keyword, '/') . '\b/i';
            preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE);
            $link_count = 0;

            foreach ($matches as $match) {
                if ($link_count >= $settings['max_links']) break;
                $pos = $match[1];
                $len = strlen($match);
                $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener" class="saal-link" data-keyword="' . esc_attr($keyword) . '">' . $match . '</a>';
                $content = substr_replace($content, $replacement, $pos, $len);
                $link_count++;
            }
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('saal_settings_group', 'saal_keywords');
        register_setting('saal_settings_group', 'saal_settings');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saal_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Keywords & Links</th>
                        <td>
                            <div id="saal-keywords">
                                <?php
                                $keywords = get_option('saal_keywords', array());
                                foreach ($keywords as $k => $url) {
                                    echo '<div class="saal-row"><input type="text" name="saal_keywords[' . $k . '][keyword]" value="' . esc_attr($k) . '" placeholder="Keyword" /> <input type="url" name="saal_keywords[' . $k . '][url]" value="' . esc_url($url) . '" placeholder="Affiliate URL" /> <button type="button" class="saal-remove">Remove</button></div>';
                                }
                                ?>
                            </div>
                            <button type="button" id="saal-add-row">Add Keyword</button>
                            <p class="description">Free version: Up to 5 keywords. <strong>Pro: Unlimited + tracking dashboard.</strong></p>
                        </td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="saal_settings[max_links]" value="<?php echo esc_attr(get_option('saal_settings', array('max_links' => 3))['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Get click tracking, A/B testing, premium affiliate network integrations, and priority support for $49/year.</p>
            <a href="https://example.com/pro" class="button button-primary">Get Pro Now</a>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let rowIndex = <?php echo count($keywords); ?>;
            $('#saal-add-row').click(function() {
                if (rowIndex < 5) { // Free limit
                    $('#saal-keywords').append('<div class="saal-row"><input type="text" name="saal_keywords[' + rowIndex + '][keyword]" placeholder="Keyword" /> <input type="url" name="saal_keywords[' + rowIndex + '][url]" placeholder="Affiliate URL" /> <button type="button" class="saal-remove">Remove</button></div>');
                    rowIndex++;
                } else {
                    alert('Upgrade to Pro for unlimited keywords!');
                }
            });
            $(document).on('click', '.saal-remove', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <style>
        .saal-row { margin-bottom: 10px; }
        .saal-row input { width: 200px; margin-right: 10px; }
        </style>
        <?php
    }

    public function activate() {
        add_option('saal_keywords', array());
        add_option('saal_settings', array('max_links' => 3));
    }

    public function deactivate() {}
}

SmartAffiliateAutoLinker::get_instance();

// Pro upsell notice
function saal_pro_notice() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoLinker Pro:</strong> Unlock unlimited keywords, click tracking, A/B testing & more! <a href="' . admin_url('options-general.php?page=smart-affiliate-autolinker') . '">Upgrade Now</a></p></div>';
}
add_action('admin_notices', 'saal_pro_notice');

// Track clicks (free version logs to console, pro saves to DB)
add_action('wp_ajax_saal_track_click', 'saal_track_click');
function saal_track_click() {
    // Pro feature
    wp_die('Pro feature');
}

?>