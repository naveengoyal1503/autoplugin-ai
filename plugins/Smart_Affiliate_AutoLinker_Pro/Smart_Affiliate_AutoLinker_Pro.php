/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically converts keywords to affiliate links. Freemium model with premium add-ons.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
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
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sa_save_settings', array($this, 'save_settings'));
        add_filter('the_content', array($this, 'auto_link_content'));
        add_filter('widget_text', array($this, 'auto_link_content'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolinker', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        $this->options = get_option('sa_settings', array(
            'keywords' => array('wordpress' => 'https://amazon.com/wordpress-book?tag=youraffiliateid'),
            'nofollow' => 1,
            'limit_per_post' => 3,
            'pro_nag' => true
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sa-admin-js', plugin_dir_url(__FILE__) . 'admin.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sa-admin-js', 'sa_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoLinker',
            'Affiliate AutoLinker',
            'manage_options',
            'smart-affiliate-autolinker',
            array($this, 'settings_page')
        );
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        include plugin_dir_path(__FILE__) . 'settings-page.php';
    }

    public function save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        update_option('sa_settings', $_POST['sa_settings']);
        wp_send_json_success('Settings saved!');
    }

    public function auto_link_content($content) {
        if (is_admin() || !is_main_query()) {
            return $content;
        }

        $keywords = $this->options['keywords'];
        $limit = intval($this->options['limit_per_post']);
        $used = 0;

        foreach ($keywords as $keyword => $url) {
            if ($used >= $limit) break;

            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            $replacement = '<a href="' . esc_url($url) . '" rel="' . ($this->options['nofollow'] ? 'nofollow' : '') . '" target="_blank">' . $keyword . '</a>';
            $content = preg_replace($pattern, $replacement, $content, 1, $count);

            if ($count > 0) {
                $used++;
            }
        }

        // Premium nag
        if ($this->options['pro_nag']) {
            $content .= '<p><em>Upgrade to <strong>Pro</strong> for A/B testing & analytics! <a href="https://example.com/pro" target="_blank">Get Pro</a></em></p>';
        }

        return $content;
    }
}

SmartAffiliateAutoLinker::get_instance();

// Settings page template (embedded)
function sa_settings_template() { ob_start(); ?>
<div class="wrap">
    <h1>Smart Affiliate AutoLinker Settings</h1>
    <form method="post" id="sa-form">
        <table class="form-table">
            <tr>
                <th>Keywords & Links</th>
                <td>
                    <div id="keyword-list">
                        <?php foreach (get_option('sa_settings', array())['keywords'] ?? array() as $k => $v): ?>
                        <p><input type="text" name="sa_settings[keywords][<?php echo esc_attr($k); ?>][keyword]" value="<?php echo esc_attr($k); ?>" placeholder="Keyword"><input type="url" name="sa_settings[keywords][<?php echo esc_attr($k); ?>][url]" value="<?php echo esc_url($v); ?>" placeholder="Affiliate URL"></p>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" id="add-keyword">Add Keyword</button>
                </td>
            </tr>
            <tr>
                <th>Links Per Post</th>
                <td><input type="number" name="sa_settings[limit_per_post]" value="<?php echo esc_attr(get_option('sa_settings')['limit_per_post'] ?? 3); ?>"></td>
            </tr>
            <tr>
                <th>Add nofollow</th>
                <td><input type="checkbox" name="sa_settings[nofollow]" <?php checked((get_option('sa_settings')['nofollow'] ?? 1)); ?>></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
    <div class="sa-pro-upsell">
        <h3>Go Pro!</h3>
        <p>Unlock A/B testing, click analytics, 50+ affiliate networks, and more for $49/year.</p>
        <a href="https://example.com/pro" class="button button-primary">Upgrade Now</a>
    </div>
</div>
<script>
    jQuery(document).ready(function($) {
        $('#add-keyword').click(function() {
            $('#keyword-list').append('<p><input type="text" name="sa_settings[keywords][new' + Date.now() + '][keyword]" placeholder="Keyword"><input type="url" name="sa_settings[keywords][new' + Date.now() + '][url]" placeholder="Affiliate URL"><button type="button" class="remove-kw">Remove</button></p>');
        });
        $(document).on('click', '.remove-kw', function() {
            $(this).parent().remove();
        });
        $('#sa-form').submit(function(e) {
            e.preventDefault();
            $.post(sa_ajax.ajax_url, {action: 'sa_save_settings', sa_settings: $(this).serializeArray()}, function() {
                alert('Settings saved!');
            });
        });
    });
</script>
<?php return ob_get_clean(); }

// Output settings in admin
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'smart-affiliate-autolinker') {
        echo sa_settings_template();
    }
});