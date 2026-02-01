/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak and track affiliate links with analytics. Premium features available.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloaker {
    private static $instance = null;
    public $is_premium = false;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sac_track_click', array($this, 'track_click'));
        add_shortcode('sac_link', array($this, 'shortcode_link'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        $this->check_premium();
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-cloaker');
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sac-script', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sac-script', 'sac_ajax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    private function check_premium() {
        $license = get_option('sac_license_key');
        $this->is_premium = !empty($license) && hash('sha256', $license) === 'premiumdemo'; // Demo check
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'text' => 'Click Here',
            'url' => ''
        ), $atts);

        if (empty($atts['id']) || empty($atts['url'])) return 'Invalid shortcode';

        $links = get_option('sac_links', array());
        if (!isset($links[$atts['id']])) return 'Link not found';

        $link_data = $links[$atts['id']];
        $cloak_url = add_query_arg('sac', $atts['id'], home_url('/go/'));

        ob_start();
        echo '<a href="' . esc_url($cloak_url) . '" class="sac-link" data-id="' . esc_attr($atts['id']) . '" rel="nofollow">' . esc_html($atts['text']) . '</a>';
        return ob_get_clean();
    }

    public function track_click() {
        check_ajax_referer('sac_nonce', 'nonce');
        $id = sanitize_text_field($_POST['id']);
        $links = get_option('sac_links', array());
        if (isset($links[$id])) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            $clicks = get_option('sac_clicks', array());
            $clicks[$id][] = array('ip' => $ip, 'ua' => substr($user_agent, 0, 100), 'time' => current_time('mysql'));
            update_option('sac_clicks', $clicks);

            if ($this->is_premium && isset($links[$id]['rotation'])) {
                $rot = $links[$id]['rotation'];
                $index = isset($_COOKIE['sac_rot_' . $id]) ? intval($_COOKIE['sac_rot_' . $id]) : 0;
                $final_url = $rot[$index % count($rot)];
                setcookie('sac_rot_' . $id, ($index + 1), time() + 86400 * 30);
            } else {
                $final_url = $links[$id]['url'];
            }

            wp_redirect(esc_url_raw($final_url), 301);
            exit;
        }
        wp_die('Invalid link');
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Cloaker', 'Affiliate Cloaker', 'manage_options', 'sac-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sac_save'])) {
            check_admin_referer('sac_save');
            $links = array();
            $ids = $_POST['link_id'] ?? array();
            $urls = $_POST['link_url'] ?? array();
            $count = count($ids);
            for ($i = 0; $i < $count; $i++) {
                if (!empty($ids[$i]) && !empty($urls[$i])) {
                    $links[$ids[$i]] = array('url' => esc_url_raw($urls[$i]));
                    if ($this->is_premium && !empty($_POST['link_rot'][$i])) {
                        $rots = array_map('esc_url_raw', explode(',', $_POST['link_rot'][$i]));
                        $links[$ids[$i]]['rotation'] = $rots;
                    }
                }
            }
            update_option('sac_links', $links);
            if (!empty($_POST['license_key'])) {
                update_option('sac_license_key', sanitize_text_field($_POST['license_key']));
            }
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Cloaker Settings</h1>
            <?php if (!$this->is_premium): ?>
            <div class="notice notice-info"><p><strong>Upgrade to Premium</strong> for link rotation, A/B testing & more! <a href="https://example.com/premium" target="_blank">Get Premium ($9/mo)</a></p></div>
            <?php endif; ?>
            <form method="post">
                <?php wp_nonce_field('sac_save'); ?>
                <table class="form-table">
                    <tr>
                        <th>License Key (Premium)</th>
                        <td><input type="text" name="license_key" value="<?php echo esc_attr(get_option('sac_license_key', '')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <h2>Add Links</h2>
                <div id="links-list">
                    <div class="link-row">
                        <input type="text" name="link_id[]" placeholder="Link ID (e.g. product1)" class="regular-text">
                        <input type="url" name="link_url[]" placeholder="Affiliate URL" class="regular-text">
                        <?php if ($this->is_premium): ?>
                        <input type="text" name="link_rot[]" placeholder="Rotation URLs (comma sep)" class="regular-text">
                        <?php endif; ?>
                        <button type="button" class="button button-secondary remove-link">Remove</button>
                    </div>
                </div>
                <p><button type="button" id="add-link" class="button button-secondary">Add Link</button></p>
                <?php submit_button('Save Settings', 'primary', 'sac_save'); ?>
            </form>
            <h2>Analytics</h2>
            <?php $clicks = get_option('sac_clicks', array());
            if (!empty($clicks)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Clicks</th><th>Details</th></tr></thead>
                <tbody>
                <?php foreach ($clicks as $id => $data): ?>
                    <tr>
                        <td><?php echo esc_html($id); ?></td>
                        <td><?php echo count($data); ?></td>
                        <td><?php echo esc_html(count($data) . ' clicks'); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p>No clicks yet.</p>
            <?php endif; ?>
        </div>
        <script>
        jQuery(document).ready(function($) {
            $('#add-link').click(function() {
                var row = $('.link-row:first').clone();
                row.find('input').val('');
                $('#links-list').append(row);
            });
            $(document).on('click', '.remove-link', function() {
                if ($('.link-row').length > 1) $(this).closest('.link-row').remove();
            });
        });
        </script>
        <?php
    }

    public function activate() {
        if (!get_option('sac_links')) update_option('sac_links', array());
        if (!get_option('sac_clicks')) update_option('sac_clicks', array());
    }
}

SmartAffiliateCloaker::get_instance();

add_action('template_redirect', function() {
    if (isset($_GET['sac'])) {
        $id = sanitize_text_field($_GET['sac']);
        wp_redirect(home_url('/'), 302);
        exit;
    }
});

// JS file content (embedded for single file)
/* sac-script.js equivalent:
jQuery(document).ready(function($) {
    $('.sac-link').click(function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post(sac_ajax.ajaxurl, {
            action: 'sac_track_click',
            id: id,
            nonce: sac_ajax.nonce
        });
    });
});
*/
?>