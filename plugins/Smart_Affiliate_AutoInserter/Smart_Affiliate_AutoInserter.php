/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your content to maximize earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private $api_key = '';
    private $affiliates = [];
    private $max_links_free = 5;
    private $is_premium = false;

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        $this->load_settings();
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('the_content', [$this, 'insert_affiliate_links'], 99);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
        add_action('wp_ajax_saa_check_premium', [$this, 'check_premium']);
    }

    private function load_settings() {
        $this->api_key = get_option('saa_api_key', '');
        $this->affiliates = get_option('saa_affiliates', []);
        $this->is_premium = get_option('saa_premium', false);
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-frontend', plugin_dir_url(__FILE__) . 'saa.js', ['jquery'], '1.0.0', true);
        wp_localize_script('saa-frontend', 'saa_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('saa_nonce')
        ]);
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || empty($this->affiliates)) {
            return $content;
        }

        $words = explode(' ', $content);
        $inserted = 0;
        $max_links = $this->is_premium ? 999 : $this->max_links_free;

        foreach ($words as $index => &$word) {
            if ($inserted >= $max_links) break;

            foreach ($this->affiliates as $aff) {
                if (stripos($word, $aff['keyword']) !== false) {
                    $link = '<a href="' . esc_url($aff['url']) . '" target="_blank" rel="nofollow noopener" class="saa-aff-link">' . $word . '</a>';
                    $word = $link;
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $words);
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            [$this, 'admin_page']
        );
    }

    public function admin_init() {
        register_setting('saa_settings', 'saa_api_key');
        register_setting('saa_settings', 'saa_affiliates');
        register_setting('saa_settings', 'saa_premium');
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>API Key (Premium)</th>
                        <td><input type="text" name="saa_api_key" value="<?php echo esc_attr($this->api_key); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th>Affiliates</th>
                        <td>
                            <div id="saa-affiliates">
                                <?php $this->render_affiliates(); ?>
                            </div>
                            <button type="button" id="saa-add-aff">Add Affiliate</button>
                        </td>
                    </tr>
                    <tr>
                        <th>Premium Status</th>
                        <td>
                            <label><input type="checkbox" name="saa_premium" value="1" <?php checked($this->is_premium); ?> /> Premium Active (Unlimited Links)</label>
                            <p class="description">Upgrade at <a href="https://example.com/premium" target="_blank">example.com/premium</a></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#saa-add-aff').click(function() {
                $('#saa-affiliates').append(
                    '<div class="saa-aff-row">' +
                    '<input type="text" name="saa_affiliates[][keyword]" placeholder="Keyword" /> ' +
                    '<input type="url" name="saa_affiliates[][url]" placeholder="Affiliate URL" /> ' +
                    '<button type="button" class="saa-remove">Remove</button>' +
                    '</div>'
                );
            });
            $(document).on('click', '.saa-remove', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }

    private function render_affiliates() {
        foreach ($this->affiliates as $i => $aff) {
            echo '<div class="saa-aff-row">';
            echo '<input type="text" name="saa_affiliates[' . $i . '][keyword]" value="' . esc_attr($aff['keyword']) . '" placeholder="Keyword" /> ';
            echo '<input type="url" name="saa_affiliates[' . $i . '][url]" value="' . esc_url($aff['url']) . '" placeholder="Affiliate URL" /> ';
            echo '<button type="button" class="saa-remove">Remove</button>';
            echo '</div>';
        }
    }

    public function check_premium() {
        check_ajax_referer('saa_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();
        wp_send_json_success($this->is_premium);
    }

    public function activate() {
        add_option('saa_affiliates', []);
    }

    public function deactivate() {}
}

new SmartAffiliateAutoInserter();

// Freemium nag
add_action('admin_notices', function() {
    $is_premium = get_option('saa_premium', false);
    if (!$is_premium && did_action('wp_loaded')) {
        echo '<div class="notice notice-info"><p>Upgrade <strong>Smart Affiliate AutoInserter</strong> to Premium for unlimited links and AI matching! <a href="https://example.com/premium">Get Premium</a></p></div>';
    }
});