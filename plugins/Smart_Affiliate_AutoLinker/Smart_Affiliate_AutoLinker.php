/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically inserts relevant affiliate links into your WordPress content using keyword matching to boost revenue.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoLinker {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'load_textdomain'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('saal_options', array(
            'enabled' => 1,
            'keywords' => array(
                array('keyword' => 'WordPress', 'url' => 'https://example.com/aff/wp', 'text' => 'WordPress hosting'),
                array('keyword' => 'plugin', 'url' => 'https://example.com/aff/plugin', 'text' => 'best plugin')
            ),
            'max_links' => 3,
            'pro' => false
        ));

        if ($this->options['enabled']) {
            add_filter('the_content', array($this, 'auto_link_content'));
            add_filter('the_excerpt', array($this, 'auto_link_content'));
        }

        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('wp_ajax_saal_save_options', array($this, 'save_options'));
        add_action('wp_ajax_saal_add_keyword', array($this, 'add_keyword'));
    }

    public function load_textdomain() {
        load_plugin_textdomain('smart-affiliate-autolinker', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        if (!get_option('saal_options')) {
            add_option('saal_options', array('enabled' => 1, 'keywords' => array(), 'max_links' => 3, 'pro' => false));
        }
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function auto_link_content($content) {
        if (is_feed() || is_preview() || $this->options['pro']) {
            return $content; // Skip in feeds, previews; pro handles advanced
        }

        $links_inserted = 0;
        $max_links = intval($this->options['max_links']);

        foreach ($this->options['keywords'] as $kw) {
            if ($links_inserted >= $max_links) break;

            $pattern = '/\b' . preg_quote($kw['keyword'], '/') . '\b/i';
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches as $match) {
                    if ($links_inserted >= $max_links) break;
                    $pos = $match[1];
                    $link_text = !empty($kw['text']) ? $kw['text'] : $kw['keyword'];
                    $link = '<a href="' . esc_url($kw['url']) . '" target="_blank" rel="nofollow noopener">' . $link_text . '</a>';
                    $content = substr_replace($content, $link, $pos, strlen($match));
                    $links_inserted++;
                    // Adjust position for next match
                }
            }
        }
        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate Linker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'admin_page')
        );
    }

    public function admin_scripts($hook) {
        if ($hook !== 'settings_page_smart-affiliate-autolinker') return;
        wp_enqueue_script('saal-admin', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('saal-admin', 'saal_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('saal_nonce')));
        wp_enqueue_style('saal-admin', plugin_dir_url(__FILE__) . 'admin.css', array(), '1.0.0');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoLinker', 'smart-affiliate-autolinker'); ?></h1>
            <form id="saal-form">
                <?php wp_nonce_field('saal_options'); ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Auto-Linking', 'smart-affiliate-autolinker'); ?></th>
                        <td>
                            <input type="checkbox" name="enabled" value="1" <?php checked($this->options['enabled']); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Links per Post', 'smart-affiliate-autolinker'); ?></th>
                        <td>
                            <input type="number" name="max_links" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10">
                        </td>
                    </tr>
                </table>
                <h2><?php _e('Keywords', 'smart-affiliate-autolinker'); ?></h2>
                <div id="keywords-list">
                    <?php foreach ($this->options['keywords'] as $i => $kw): ?>
                    <div class="keyword-row">
                        <input type="text" placeholder="Keyword" value="<?php echo esc_attr($kw['keyword']); ?>">
                        <input type="url" placeholder="Affiliate URL" value="<?php echo esc_url($kw['url']); ?>">
                        <input type="text" placeholder="Link Text (optional)" value="<?php echo esc_attr($kw['text']); ?>">
                        <button type="button" class="remove-kw">Remove</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" id="add-keyword"><?php _e('Add Keyword', 'smart-affiliate-autolinker'); ?></button>
                <p class="description">Free version limits to 5 keywords. <strong>Upgrade to Pro</strong> for unlimited, AI matching & analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></p>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-keyword').click(function() {
                $('#keywords-list').append('<div class="keyword-row"><input type="text" placeholder="Keyword"><input type="url" placeholder="Affiliate URL"><input type="text" placeholder="Link Text (optional)"><button type="button" class="remove-kw">Remove</button></div>');
            });
            $(document).on('click', '.remove-kw', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <style>
        .keyword-row { margin-bottom: 10px; padding: 10px; border: 1px solid #ddd; }
        .keyword-row input { margin-right: 10px; }
        </style>
        <?php
    }

    public function save_options() {
        check_ajax_referer('saal_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        $options = array(
            'enabled' => isset($_POST['enabled']) ? 1 : 0,
            'max_links' => intval($_POST['max_links']),
            'keywords' => array(),
            'pro' => false
        );

        parse_str($_POST['form_data'], $form_data);
        foreach ($form_data as $key => $val) {
            if (strpos($key, 'keyword_') === 0) {
                $i = str_replace('keyword_', '', $key);
                if (!empty($val) && isset($form_data['url_' . $i])) {
                    $options['keywords'][] = array(
                        'keyword' => sanitize_text_field($val),
                        'url' => esc_url_raw($form_data['url_' . $i]),
                        'text' => sanitize_text_field($form_data['text_' . $i] ?? '')
                    );
                }
            }
        }

        if (count($options['keywords']) > 5 && !$options['pro']) {
            wp_send_json_error('Free version limited to 5 keywords. Upgrade to Pro!');
        }

        update_option('saal_options', $options);
        wp_send_json_success('Settings saved!');
    }

    public function add_keyword() {
        // AJAX handler if needed
    }
}

new SmartAffiliateAutoLinker();

// Pro upsell notice
function saal_pro_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_smart-affiliate-autolinker') {
        echo '<div class="notice notice-info"><p><strong>Go Pro!</strong> Unlock unlimited keywords, AI-powered matching, detailed analytics, and more. <a href="https://example.com/pro" target="_blank">Upgrade Now</a></p></div>';
    }
}
add_action('admin_notices', 'saal_pro_notice');
?>