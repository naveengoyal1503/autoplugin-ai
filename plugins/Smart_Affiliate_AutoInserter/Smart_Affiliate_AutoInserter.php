/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using keyword matching to boost commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_filter('widget_text_content', array($this, 'insert_affiliate_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('smart-affiliate-js', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_options_page(
            __('Smart Affiliate Settings', 'smart-affiliate-autoinserter'),
            __('Affiliate Inserter', 'smart-affiliate-autoinserter'),
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_settings', 'smart_affiliate_options', array($this, 'sanitize_settings'));
        add_settings_section('main_section', __('Main Settings', 'smart-affiliate-autoinserter'), null, 'smart-affiliate');
        add_settings_field('affiliates', __('Affiliate Keywords & Links', 'smart-affiliate-autoinserter'), array($this, 'affiliates_field'), 'smart-affiliate', 'main_section');
        add_settings_field('max_links', __('Max Links per Post', 'smart-affiliate-autoinserter'), array($this, 'max_links_field'), 'smart-affiliate', 'main_section');
        add_settings_field('pro_nag', __('Upgrade to Pro', 'smart-affiliate-autoinserter'), array($this, 'pro_nag_field'), 'smart-affiliate', 'main_section');
    }

    public function sanitize_settings($input) {
        $sanitized = array();
        $sanitized['affiliates'] = array();
        if (isset($input['affiliates']) && is_array($input['affiliates'])) {
            foreach ($input['affiliates'] as $aff) {
                if (!empty($aff['keyword']) && !empty($aff['link'])) {
                    $sanitized['affiliates'][] = array(
                        'keyword' => sanitize_text_field($aff['keyword']),
                        'link' => esc_url_raw($aff['link'])
                    );
                }
            }
        }
        $sanitized['max_links'] = max(1, min(10, intval($input['max_links'] ?? 3)));
        return $sanitized;
    }

    public function affiliates_field() {
        $options = get_option('smart_affiliate_options', array('affiliates' => array(), 'max_links' => 3));
        $affiliates = $options['affiliates'] ?? array();
        echo '<div id="affiliates-list">';
        if (empty($affiliates)) {
            $affiliates = array(array('keyword' => '', 'link' => ''));
        }
        foreach ($affiliates as $index => $aff) {
            echo '<div class="affiliate-row">';
            echo '<input type="text" name="smart_affiliate_options[affiliates][' . $index . '][keyword]" placeholder="Keyword (e.g. laptop)" value="' . esc_attr($aff['keyword']) . '" style="width:200px;"> ';
            echo '<input type="url" name="smart_affiliate_options[affiliates][' . $index . '][link]" placeholder="Affiliate Link" value="' . esc_attr($aff['link']) . '" style="width:300px;"> ';
            echo '<button type="button" class="remove-affiliate">Remove</button>';
            echo '</div>';
        }
        echo '</div>';
        echo '<p><button type="button" id="add-affiliate">Add Affiliate</button></p>';
        echo '<p class="pro-teaser">Pro: Supports Amazon, ClickBank, 20+ networks & auto-keyword discovery.</p>';
    }

    public function max_links_field() {
        $options = get_option('smart_affiliate_options', array('max_links' => 3));
        echo '<input type="number" name="smart_affiliate_options[max_links]" value="' . esc_attr($options['max_links']) . '" min="1" max="10" />';
    }

    public function pro_nag_field() {
        echo '<p><a href="https://example.com/pro" target="_blank" class="button button-primary">Upgrade to Pro - Unlimited Links & Analytics ($49/year)</a></p>';
        echo '<p>Pro features: AI keyword matching, performance tracking, A/B testing, and more.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_settings');
                do_settings_sections('smart-affiliate');
                submit_button();
                ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-affiliate').click(function() {
                var index = $('#affiliates-list .affiliate-row').length;
                $('#affiliates-list').append(
                    '<div class="affiliate-row">' +
                    '<input type="text" name="smart_affiliate_options[affiliates][' + index + '][keyword]" placeholder="Keyword"> ' +
                    '<input type="url" name="smart_affiliate_options[affiliates][' + index + '][link]" placeholder="Affiliate Link"> ' +
                    '<button type="button" class="remove-affiliate">Remove</button>' +
                    '</div>'
                );
            });
            $(document).on('click', '.remove-affiliate', function() {
                $(this).closest('.affiliate-row').remove();
            });
        });
        </script>
        <?php
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || is_feed() || empty($content)) {
            return $content;
        }
        $options = get_option('smart_affiliate_options', array('affiliates' => array(), 'max_links' => 3));
        $max_links = $options['max_links'];
        $affiliates = $options['affiliates'];
        if (empty($affiliates)) {
            return $content;
        }
        $inserted = 0;
        $words = explode(' ', $content);
        foreach ($words as $index => &$word) {
            if ($inserted >= $max_links) {
                break;
            }
            foreach ($affiliates as $aff) {
                if (stripos($word, $aff['keyword']) !== false) {
                    $link = '<a href="' . esc_url($aff['link']) . '" target="_blank" rel="nofollow sponsored">' . $word . '</a> ';
                    $word = $link;
                    $inserted++;
                    break 2;
                }
            }
        }
        return implode(' ', $words);
    }

    public function activate() {
        add_option('smart_affiliate_options', array('max_links' => 3));
    }

    public function deactivate() {}
}

SmartAffiliateAutoInserter::get_instance();

// Pro upsell notice
function smart_affiliate_admin_notice() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_smart-affiliate-autoinserter') return;
    echo '<div class="notice notice-info"><p>Supercharge <strong>Smart Affiliate AutoInserter</strong> with Pro: Unlimited links, analytics & AI matching. <a href="' . admin_url('options-general.php?page=smart-affiliate-autoinserter') . '">Upgrade now</a> →</p></div>';
}
add_action('admin_notices', 'smart_affiliate_admin_notice');

// Prevent direct access to assets folder - but since single file, no assets yet
// Add assets dir simulation if needed
if (!file_exists(plugin_dir_path(__FILE__) . 'assets')) {
    mkdir(plugin_dir_path(__FILE__) . 'assets', 0755, true);
    file_put_contents(plugin_dir_path(__FILE__) . 'assets/script.js', '// Placeholder for JS');
}
?>