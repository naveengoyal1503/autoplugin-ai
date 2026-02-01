/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into posts and pages using keyword matching to maximize commissions.
 * Version: 1.0.0
 * Author: Your Name
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
        add_action('wp_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
        add_filter('widget_text', array($this, 'insert_affiliate_links'), 99);
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('smart_affiliate_options', array());
    }

    public function load_textdomain() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function activate() {
        add_option('smart_affiliate_options', array(
            'affiliates' => array(),
            'enabled' => true,
            'max_links' => 3,
            'pro' => false
        ));
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function admin_menu() {
        add_options_page(
            __('Smart Affiliate Settings', 'smart-affiliate-autoinserter'),
            __('Affiliate AutoInserter', 'smart-affiliate-autoinserter'),
            'manage_options',
            'smart-affiliate',
            array($this, 'admin_page')
        );
    }

    public function admin_init() {
        register_setting('smart_affiliate_options_group', 'smart_affiliate_options', array($this, 'sanitize_options'));
    }

    public function sanitize_options($input) {
        $input['enabled'] = isset($input['enabled']);
        $input['affiliates'] = array_map(function($aff) {
            $aff['keyword'] = sanitize_text_field($aff['keyword']);
            $aff['link'] = esc_url_raw($aff['link']);
            $aff['text'] = sanitize_text_field($aff['text']);
            return $aff;
        }, $input['affiliates'] ?? array());
        $input['max_links'] = intval($input['max_links'] ?? 3);
        return $input;
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('smart_affiliate_options_group');
                do_settings_sections('smart_affiliate_options_group');
                ?>
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enabled', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <input type="checkbox" name="smart_affiliate_options[enabled]" value="1" <?php checked($this->options['enabled']); ?> />
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Max Links per Post', 'smart-affiliate-autoinserter'); ?></th>
                        <td>
                            <input type="number" name="smart_affiliate_options[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10" />
                        </td>
                    </tr>
                </table>
                <h2><?php _e('Affiliate Rules', 'smart-affiliate-autoinserter'); ?></h2>
                <div id="affiliate-rules">
                    <?php $this->render_rules(); ?>
                </div>
                <p>
                    <button type="button" class="button" id="add-rule"><?php _e('Add Rule', 'smart-affiliate-autoinserter'); ?></button>
                </p>
                <?php submit_button(); ?>
            </form>
            <script>
            jQuery(document).ready(function($) {
                $('#add-rule').click(function() {
                    var index = $('#affiliate-rules .affiliate-rule').length;
                    $('#affiliate-rules').append(
                        '<div class="affiliate-rule">' +
                        '<p><label><?php _e("Keyword", "smart-affiliate-autoinserter"); ?></label> <input type="text" name="smart_affiliate_options[affiliates][" + index + "][keyword]" /></p>' +
                        '<p><label><?php _e("Link Text", "smart-affiliate-autoinserter"); ?></label> <input type="text" name="smart_affiliate_options[affiliates][" + index + "][text]" /></p>' +
                        '<p><label><?php _e("Affiliate URL", "smart-affiliate-autoinserter"); ?></label> <input type="url" name="smart_affiliate_options[affiliates][" + index + "][link]" /></p>' +
                        '<p><button type="button" class="button button-secondary remove-rule"><?php _e("Remove", "smart-affiliate-autoinserter"); ?></button></p>' +
                        '</div>'
                    );
                });
                $(document).on('click', '.remove-rule', function() {
                    $(this).closest('.affiliate-rule').remove();
                });
            });
            </script>
        </div>
        <?php
    }

    private function render_rules() {
        if (empty($this->options['affiliates'])) return;
        foreach ($this->options['affiliates'] as $index => $aff) {
            echo '<div class="affiliate-rule">';
            echo '<p><label>' . __('Keyword', 'smart-affiliate-autoinserter') . '</label> <input type="text" name="smart_affiliate_options[affiliates][' . $index . '][keyword]" value="' . esc_attr($aff['keyword']) . '" /></p>';
            echo '<p><label>' . __('Link Text', 'smart-affiliate-autoinserter') . '</label> <input type="text" name="smart_affiliate_options[affiliates][' . $index . '][text]" value="' . esc_attr($aff['text']) . '" /></p>';
            echo '<p><label>' . __('Affiliate URL', 'smart-affiliate-autoinserter') . '</label> <input type="url" name="smart_affiliate_options[affiliates][' . $index . '][link]" value="' . esc_url($aff['link']) . '" /></p>';
            echo '<p><button type="button" class="button button-secondary remove-rule">' . __('Remove', 'smart-affiliate-autoinserter') . '</button></p>';
            echo '</div>';
        }
    }

    public function insert_affiliate_links($content) {
        if (!is_single() && !is_page() || !$this->options['enabled'] || is_admin()) {
            return $content;
        }

        $affiliates = $this->options['affiliates'] ?? array();
        if (empty($affiliates)) return $content;

        $inserted = 0;
        $max_links = intval($this->options['max_links']);

        foreach ($affiliates as $aff) {
            if ($inserted >= $max_links) break;

            $keyword = preg_quote($aff['keyword'], '/');
            $regex = '/\b' . $keyword . '\b/i';
            if (preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches as $match) {
                    if ($inserted >= $max_links) break;
                    $pos = $match[1];
                    $link_text = !empty($aff['text']) ? $aff['text'] : $match;
                    $link = '<a href="' . esc_url($aff['link']) . '" target="_blank" rel="nofollow sponsored">' . esc_html($link_text) . '</a>';
                    $content = substr_replace($content, $link, $pos, strlen($match));
                    $inserted++;
                    // Adjust position for next match
                    foreach ($matches as &$m) {
                        if ($m[1] > $pos) $m[1] += strlen($link) - strlen($match);
                    }
                }
            }
        }

        return $content;
    }
}

new SmartAffiliateAutoInserter();

?>