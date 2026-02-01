/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content using smart keyword matching and context analysis.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $options;

    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        $this->options = get_option('saai_options', array(
            'api_key' => '',
            'affiliates' => array(),
            'enabled' => 1,
            'max_links' => 3,
            'min_words' => 100
        ));

        if ($this->options['enabled']) {
            add_filter('the_content', array($this, 'insert_affiliate_links'), 99);
            add_action('admin_menu', array($this, 'admin_menu'));
            add_action('admin_init', array($this, 'admin_init'));
        }
    }

    public function activate() {
        add_option('saai_options', array(
            'api_key' => '',
            'affiliates' => array(),
            'enabled' => 1,
            'max_links' => 3,
            'min_words' => 100
        ));
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function insert_affiliate_links($content) {
        if (!is_single() || empty($this->options['affiliates'])) {
            return $content;
        }

        $word_count = str_word_count(strip_tags($content));
        if ($word_count < $this->options['min_words']) {
            return $content;
        }

        $keywords = $this->extract_keywords($content);
        $links_inserted = 0;
        $max_links = intval($this->options['max_links']);

        foreach ($this->options['affiliates'] as $affiliate) {
            if ($links_inserted >= $max_links) break;

            foreach ($keywords as $keyword) {
                if (stripos($content, $keyword) !== false && $links_inserted < $max_links) {
                    $link_html = '<a href="' . esc_url($affiliate['url']) . '" target="_blank" rel="nofollow sponsored">' . esc_html($affiliate['text']) . '</a> ';
                    $content = preg_replace('/\b' . preg_quote($keyword, '/') . '\b/i', $link_html, $content, 1);
                    $links_inserted++;
                }
            }
        }

        return $content;
    }

    private function extract_keywords($content) {
        $content = strip_tags($content);
        $words = explode(' ', strtolower($content));
        $keywords = array_count_values(array_filter($words, function($w) { return strlen($w) > 4; }));
        arsort($keywords);
        return array_keys(array_slice($keywords, 0, 10, true));
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate AutoInserter', 'Affiliate Inserter', 'manage_options', 'saai-settings', array($this, 'settings_page'));
    }

    public function admin_init() {
        register_setting('saai_options_group', 'saai_options', array($this, 'sanitize_options'));
    }

    public function sanitize_options($input) {
        $input['enabled'] = isset($input['enabled']) ? 1 : 0;
        $input['max_links'] = max(1, intval($input['max_links']));
        $input['min_words'] = max(50, intval($input['min_words']));
        $input['affiliates'] = array_map(function($aff) {
            $aff['url'] = esc_url_raw($aff['url']);
            $aff['text'] = sanitize_text_field($aff['text']);
            return $aff;
        }, $input['affiliates'] ?? array());
        return $input;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saai_options_group'); ?>
                <?php do_settings_sections('saai_options_group'); ?>
                <table class="form-table">
                    <tr>
                        <th>Enable Auto-Insertion</th>
                        <td><input type="checkbox" name="saai_options[enabled]" value="1" <?php checked($this->options['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td><input type="number" name="saai_options[max_links]" value="<?php echo esc_attr($this->options['max_links']); ?>" min="1" max="10" /></td>
                    </tr>
                    <tr>
                        <th>Min Words per Post</th>
                        <td><input type="number" name="saai_options[min_words]" value="<?php echo esc_attr($this->options['min_words']); ?>" min="50" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Links</th>
                        <td>
                            <div id="affiliates-list">
                                <?php foreach ($this->options['affiliates'] as $i => $aff): ?>
                                    <div class="affiliate-row">
                                        <input type="text" name="saai_options[affiliates][<?php echo $i; ?>][text]" placeholder="Anchor Text" value="<?php echo esc_attr($aff['text']); ?>" />
                                        <input type="url" name="saai_options[affiliates][<?php echo $i; ?>][url]" placeholder="Affiliate URL" value="<?php echo esc_attr($aff['url']); ?>" />
                                        <button type="button" class="button remove-aff">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-affiliate" class="button">Add Affiliate</button>
                            <p class="description">Plugin matches post keywords to these anchor texts automatically.</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let rowIndex = <?php echo count($this->options['affiliates']); ?>;
            $('#add-affiliate').click(function() {
                $('#affiliates-list').append(
                    '<div class="affiliate-row">' +
                    '<input type="text" name="saai_options[affiliates][" + rowIndex + '][text]" placeholder="Anchor Text" />' +
                    '<input type="url" name="saai_options[affiliates][" + rowIndex + '][url]" placeholder="Affiliate URL" />' +
                    '<button type="button" class="button remove-aff">Remove</button>' +
                    '</div>'
                );
                rowIndex++;
            });
            $(document).on('click', '.remove-aff', function() {
                $(this).closest('.affiliate-row').remove();
            });
        });
        </script>
        <?php
    }
}

new SmartAffiliateAutoInserter();

// Premium upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    $screen = get_current_screen();
    if ($screen->id === 'settings_page_saai-settings') {
        echo '<div class="notice notice-info"><p>Upgrade to <strong>Pro</strong> for AI keyword analysis, analytics dashboard, and premium integrations! <a href="https://example.com/pro">Get Pro</a></p></div>';
    }
});