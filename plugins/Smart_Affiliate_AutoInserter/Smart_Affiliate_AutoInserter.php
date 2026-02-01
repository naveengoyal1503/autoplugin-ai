/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts and cloaks affiliate links in posts for optimal monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_shortcode('afflink', array($this, 'afflink_shortcode'));
    }

    public function activate() {
        add_option('saai_affiliates', array());
        add_option('saai_max_links', 3);
        add_option('saai_enabled', 'yes');
    }

    public function deactivate() {
        // Do nothing
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('saai-script', plugin_dir_url(__FILE__) . 'saai.js', array('jquery'), '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (get_option('saai_enabled') !== 'yes' || is_admin()) return $content;

        $affiliates = get_option('saai_affiliates', array());
        if (empty($affiliates)) return $content;

        $max_links = intval(get_option('saai_max_links', 3));
        $inserted = 0;

        foreach ($affiliates as $aff) {
            if ($inserted >= $max_links) break;

            $keyword = $aff['keyword'];
            $link = $aff['link'];
            $cloaked = $aff['cloaked'];

            if (stripos($content, $keyword) !== false && stripos($content, $link) === false) {
                $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener" class="saai-link">' . esc_html($keyword) . '</a>';
                if ($cloaked) {
                    $replacement = '<a href="' . esc_url($cloaked) . '" target="_blank" rel="nofollow noopener" class="saai-link">' . esc_html($keyword) . '</a>';
                }
                $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $replacement, $content, 1);
                $inserted++;
            }
        }

        return $content;
    }

    public function afflink_shortcode($atts) {
        $atts = shortcode_atts(array('url' => '', 'text' => ''), $atts);
        if (empty($atts['url'])) return '';
        return '<a href="' . esc_url($atts['url']) . '" target="_blank" rel="nofollow noopener" class="saai-link">' . esc_html($atts['text'] ?: $atts['url']) . '</a>';
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'Affiliate Inserter', 'manage_options', 'saai-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saai_settings', 'saai_affiliates');
        register_setting('saai_settings', 'saai_max_links');
        register_setting('saai_settings', 'saai_enabled');
    }

    public function settings_page() {
        if (isset($_POST['saai_submit'])) {
            update_option('saai_affiliates', sanitize_text_field_deep($_POST['saai_affiliates']));
            update_option('saai_max_links', intval($_POST['saai_max_links']));
            update_option('saai_enabled', sanitize_text_field($_POST['saai_enabled']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="">
                <?php settings_fields('saai_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td>
                            <select name="saai_enabled">
                                <option value="yes" <?php selected(get_option('saai_enabled'), 'yes'); ?>>Yes</option>
                                <option value="no" <?php selected(get_option('saai_enabled'), 'no'); ?>>No</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="saai_max_links" value="<?php echo esc_attr(get_option('saai_max_links', 3)); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td>
                            <table id="saai-affiliates">
                                <thead><tr><th>Keyword</th><th>Affiliate URL</th><th>Cloaked URL (optional)</th><th>Action</th></tr></thead>
                                <tbody>
<?php
        $affiliates = get_option('saai_affiliates', array());
        foreach ($affiliates as $i => $aff) {
            echo '<tr>
                <td><input type="text" name="saai_affiliates[' . $i . '][keyword]" value="' . esc_attr($aff['keyword']) . '" /></td>
                <td><input type="url" name="saai_affiliates[' . $i . '][link]" value="' . esc_url($aff['link']) . '" /></td>
                <td><input type="url" name="saai_affiliates[' . $i . '][cloaked]" value="' . esc_url($aff['cloaked']) . '" /></td>
                <td><button type="button" class="button button-secondary saai-remove">Remove</button></td>
            </tr>';
        }
?>
                                </tbody>
                            </table>
                            <button type="button" id="saai-add-row" class="button">Add Affiliate</button>
                            <p>Use [afflink url="https://example.com" text="Buy Now"] for manual links.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let rowCount = <?php echo count($affiliates); ?>;
            $('#saai-add-row').click(function() {
                let row = '<tr><td><input type="text" name="saai_affiliates[' + rowCount + '][keyword]" /></td><td><input type="url" name="saai_affiliates[' + rowCount + '][link]" /></td><td><input type="url" name="saai_affiliates[' + rowCount + '][cloaked]" /></td><td><button type="button" class="button button-secondary saai-remove">Remove</button></td></tr>';
                $('#saai-affiliates tbody').append(row);
                rowCount++;
            });
            $(document).on('click', '.saai-remove', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <style>
        #saai-affiliates input { width: 100%; max-width: 200px; }
        .saai-link { color: #0073aa; text-decoration: underline; }
        </style>
        <?php
    }
}

SmartAffiliateAutoInserter::get_instance();

// Premium teaser
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p>Unlock <strong>unlimited links, AI keyword suggestions, click tracking analytics</strong> with Smart Affiliate AutoInserter Pro! <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>;
});