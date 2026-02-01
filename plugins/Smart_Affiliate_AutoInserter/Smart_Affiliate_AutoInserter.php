/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into your WordPress content to maximize commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateAutoInserter {
    private $affiliates = [];

    public function __construct() {
        add_action('init', [$this, 'init});
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_filter('the_content', [$this, 'insert_affiliate_links']);
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
    }

    public function activate() {
        add_option('saai_affiliates', []);
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_script('saai-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', ['jquery'], '1.0.0', true);
    }

    public function insert_affiliate_links($content) {
        if (is_admin() || !is_single()) return $content;

        $this->load_affiliates();
        $words = explode(' ', $content);
        $inserted = 0;
        $max_links = 3;

        foreach ($words as $key => &$word) {
            if ($inserted >= $max_links) break;

            foreach ($this->affiliates as $keyword => $link) {
                if (stripos($word, $keyword) !== false) {
                    $word = str_ireplace($keyword, '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow noopener" class="saai-link">' . $keyword . '</a>', $word);
                    $inserted++;
                    break;
                }
            }
        }

        return implode(' ', $words);
    }

    private function load_affiliates() {
        $this->affiliates = get_option('saai_affiliates', [
            'phone' => 'https://amazon.com/affiliate-link-phone?tag=yourtag',
            'laptop' => 'https://amazon.com/affiliate-link-laptop?tag=yourtag',
            'book' => 'https://amazon.com/affiliate-link-book?tag=yourtag'
        ]);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Settings', 'Affiliate AutoInserter', 'manage_options', 'saai-settings', [$this, 'settings_page']);
    }

    public function admin_init() {
        register_setting('saai_settings', 'saai_affiliates');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Smart Affiliate AutoInserter Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('saai_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Keyword to Affiliate Link</th>
                        <td>
                            <?php $affiliates = get_option('saai_affiliates', []); ?>
                            <div id="affiliate-list">
                                <?php foreach ($affiliates as $keyword => $link): ?>
                                    <p><input type="text" name="saai_affiliates[<?php echo esc_attr($keyword); ?>][keyword]" value="<?php echo esc_attr($keyword); ?>" placeholder="Keyword"> 
                                    <input type="url" name="saai_affiliates[<?php echo esc_attr($keyword); ?>][link]" value="<?php echo esc_url($link); ?>" placeholder="Affiliate URL"> <button type="button" class="button button-secondary saai-remove">Remove</button></p>
                                <?php endforeach; ?>
                            </div>
                            <p><button type="button" id="saai-add" class="button button-primary">Add New</button></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Upgrade to Pro</h2>
            <p>Unlock AI keyword matching, click tracking, A/B testing, and 50+ integrations for $49/year!</p>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let counter = <?php echo count($affiliates); ?>;
            $('#saai-add').click(function() {
                counter++;
                $('#affiliate-list').append('<p><input type="text" name="saai_affiliates[' + counter + '][keyword]" placeholder="Keyword"> <input type="url" name="saai_affiliates[' + counter + '][link]" placeholder="Affiliate URL"> <button type="button" class="button button-secondary saai-remove">Remove</button></p>');
            });
            $(document).on('click', '.saai-remove', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }
}

new SmartAffiliateAutoInserter();

// Pro upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter Pro:</strong> Upgrade for AI optimization, analytics, and more! <a href="https://example.com/pro" target="_blank">Get Pro Now</a></p></div>';
});

// Track clicks (basic)
add_action('wp_ajax_saai_track_click', function() {
    // Pro feature placeholder
    wp_die();
});