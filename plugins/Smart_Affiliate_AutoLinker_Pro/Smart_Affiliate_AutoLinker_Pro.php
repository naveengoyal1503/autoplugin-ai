/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically turns keywords into affiliate links with tracking and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) exit;

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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        } else {
            add_filter('the_content', array($this, 'auto_link_content'));
            add_filter('widget_text', array($this, 'auto_link_content'));
        }

        // Pro check (simulate with option)
        $this->is_pro = get_option('saal_pro_active', false);
    }

    public function activate() {
        add_option('saal_keywords', array());
        add_option('saal_pro_active', false);
        add_option('saal_clicks', array());
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function admin_menu() {
        add_options_page('Affiliate AutoLinker', 'AutoLinker', 'manage_options', 'smart-affiliate-autolinker', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saal_settings', 'saal_keywords', array('sanitize_callback' => array($this, 'sanitize_keywords')));
        register_setting('saal_settings', 'saal_pro_active');
    }

    public function sanitize_keywords($input) {
        $sanitized = array();
        foreach ($input as $item) {
            if (!empty($item['keyword']) && !empty($item['url'])) {
                $sanitized[] = array(
                    'keyword' => sanitize_text_field($item['keyword']),
                    'url' => esc_url_raw($item['url']),
                    'nofollow' => isset($item['nofollow']) ? 1 : 0,
                    'newtab' => isset($item['newtab']) ? 1 : 0
                );
            }
        }
        if (!$this->is_pro && count($sanitized) > 10) {
            $sanitized = array_slice($sanitized, 0, 10);
        }
        return $sanitized;
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page') return;
        wp_enqueue_script('saal-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('saal-admin', 'saal_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('saal_nonce')));
        wp_enqueue_style('saal-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function settings_page() {
        $keywords = get_option('saal_keywords', array());
        $is_pro = get_option('saal_pro_active', false);
        $clicks = get_option('saal_clicks', array());
        include 'settings-page.html'; // Inline HTML below
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoLinker <?php echo $is_pro ? '<span style="color:green;">[PRO]</span>' : '[FREE]'; ?></h1>
            <?php if (!$is_pro): ?>
            <div class="notice notice-info"><p>Upgrade to Pro for unlimited keywords, analytics, and more! <a href="#" onclick="alert('Pro upgrade link here')">Get Pro</a></p></div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php settings_fields('saal_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Keywords & Links</th>
                        <td>
                            <div id="keyword-list">
                                <?php foreach ($keywords as $i => $kw): ?>
                                <div class="keyword-row">
                                    <input type="text" name="saal_keywords[<?php echo $i; ?>][keyword]" placeholder="Keyword" value="<?php echo esc_attr($kw['keyword']); ?>" />
                                    <input type="url" name="saal_keywords[<?php echo $i; ?>][url]" placeholder="Affiliate URL" value="<?php echo esc_attr($kw['url']); ?>" />
                                    <label><input type="checkbox" name="saal_keywords[<?php echo $i; ?>][nofollow]" <?php checked($kw['nofollow']); ?> /> nofollow</label>
                                    <label><input type="checkbox" name="saal_keywords[<?php echo $i; ?>][newtab]" <?php checked($kw['newtab']); ?> /> new tab</label>
                                    <button type="button" class="button remove-kw">Remove</button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-keyword" class="button">Add Keyword</button>
                            <p class="description"><?php echo $is_pro ? 'Unlimited' : 'Max 10 in free version'; ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <?php if ($is_pro): ?>
            <h2>Click Analytics</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Keyword</th><th>Clicks</th><th>Last Click</th></tr></thead>
                <tbody>
                    <?php foreach ($clicks as $kw => $data): ?>
                    <tr><td><?php echo esc_html($kw); ?></td><td><?php echo $data['count']; ?></td><td><?php echo esc_html($data['last']); ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let rowIndex = <?php echo count($keywords); ?>;
            $('#add-keyword').click(function() {
                if (!<?php echo $is_pro ? 'true' : 'false'; ?> && rowIndex >= 10) {
                    alert('Upgrade to Pro for more keywords!');
                    return;
                }
                $('#keyword-list').append(
                    '<div class="keyword-row">' +
                    '<input type="text" name="saal_keywords[' + rowIndex + '][keyword]" placeholder="Keyword" />' +
                    '<input type="url" name="saal_keywords[' + rowIndex + '][url]" placeholder="Affiliate URL" />' +
                    '<label><input type="checkbox" name="saal_keywords[' + rowIndex + '][nofollow]" /> nofollow</label> ' +
                    '<label><input type="checkbox" name="saal_keywords[' + rowIndex + '][newtab]" /> new tab</label> ' +
                    '<button type="button" class="button remove-kw">Remove</button>' +
                    '</div>'
                );
                rowIndex++;
            });
            $(document).on('click', '.remove-kw', function() {
                $(this).closest('.keyword-row').remove();
            });
        });
        </script>
        <style>
        .keyword-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .keyword-row input { margin-right: 5px; width: 150px; }
        </style>
        <?php
    }

    public function auto_link_content($content) {
        $keywords = get_option('saal_keywords', array());
        if (empty($keywords)) return $content;

        $is_pro = get_option('saal_pro_active', false);

        foreach ($keywords as $kw) {
            $keyword = preg_quote($kw['keyword'], '/');
            $link_attrs = '';
            if ($kw['nofollow']) $link_attrs .= ' rel="nofollow"';
            if ($kw['newtab']) $link_attrs .= ' target="_blank"';

            // Track clicks in Pro
            if ($is_pro) {
                $track_url = add_query_arg(array('saal_track' => urlencode($kw['keyword'])), $kw['url']);
                $replacement = '<a href="' . esc_url($track_url) . '"' . $link_attrs . '>' . $kw['keyword'] . '</a>';
            } else {
                $replacement = '<a href="' . esc_url($kw['url']) . '"' . $link_attrs . '>' . $kw['keyword'] . '</a>';
            }

            $content = preg_replace('/\b' . $keyword . '\b/i', $replacement, $content, 1);
        }
        return $content;
    }

    public function track_click() {
        if (!isset($_GET['saal_track']) || !get_option('saal_pro_active', false)) {
            wp_redirect(remove_query_arg('saal_track'));
            exit;
        }

        $keyword = sanitize_text_field($_GET['saal_track']);
        $clicks = get_option('saal_clicks', array());
        if (!isset($clicks[$keyword])) {
            $clicks[$keyword] = array('count' => 0, 'last' => '');
        }
        $clicks[$keyword]['count']++;
        $clicks[$keyword]['last'] = current_time('mysql');
        update_option('saal_clicks', $clicks);

        $url = remove_query_arg('saal_track');
        wp_redirect($url);
        exit;
    }
}

SmartAffiliateAutoLinker::get_instance();

add_action('init', function() {
    if (isset($_GET['saal_track'])) {
        $linker = SmartAffiliateAutoLinker::get_instance();
        $linker->track_click();
    }
});

// Freemium nag
add_action('admin_notices', function() {
    if (!get_option('saal_pro_active', false) && get_option('saal_keywords')) {
        echo '<div class="notice notice-upgrade"><p>Unlock unlimited keywords and analytics with <strong>Smart Affiliate AutoLinker Pro</strong>! <a href="#pro">Upgrade now</a></p></div>';
    }
});