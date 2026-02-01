/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Plugin URI: https://example.com/smart-affiliate
 * Description: Automatically cloak, track, and optimize affiliate links for maximum conversions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateManager {
    private static $instance = null;
    public $is_pro = false;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    private function __construct() {
        $this->is_pro = get_option('sam_pro_activated', false);
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sam_track_click', array($this, 'track_click'));
        add_filter('the_content', array($this, 'cloak_links'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        if (get_option('sam_api_key')) {
            // Pro check
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sam-script', plugin_dir_url(__FILE__) . 'sam.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sam-script', 'sam_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function cloak_links($content) {
        if (is_admin()) return $content;
        $patterns = '/\b(https?:\/\/(?:[^\s]+\.)?(amazon|clickbank|shareasale|commissionjunction)[^\s]+)\b/i';
        $content = preg_replace_callback($patterns, array($this, 'replace_link'), $content);
        return $content;
    }

    private function replace_link($matches) {
        $shortcode = '[sam_link url="' . esc_attr($matches) . '"]';
        return do_shortcode($shortcode);
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Manager', 'SAM Pro', 'manage_options', 'sam-pro', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['sam_api_key'])) {
            update_option('sam_api_key', sanitize_text_field($_POST['sam_api_key']));
            $this->is_pro = true;
            echo '<div class="notice notice-success"><p>Pro activated!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Manager Pro</h1>
            <form method="post">
                <p><label>Pro License Key: <input type="text" name="sam_api_key" value="<?php echo get_option('sam_api_key'); ?>" /></label></p>
                <p class="description"><?php if (!$this->is_pro) echo 'Enter key to unlock Pro features.'; ?></p>
                <?php if (!$this->is_pro): ?><p><a href="https://example.com/buy-pro" target="_blank">Buy Pro ($49/year)</a></p><?php endif; ?>
                <p><input type="submit" class="button-primary" value="Save" /></p>
            </form>
            <h2>Stats</h2>
            <div id="sam-stats">Loading...</div>
        </div>
        <?php
    }

    public function track_click() {
        $url = sanitize_url($_POST['url']);
        error_log('SAM Click: ' . $url);
        wp_redirect($url);
        exit;
    }

    public function activate() {
        add_option('sam_pro_activated', false);
    }
}

// Shortcode
function sam_link_shortcode($atts) {
    $atts = shortcode_atts(array('url' => ''), $atts);
    $slug = 'sam-' . md5($atts['url']);
    ob_start();
    ?>
    <a href="<?php echo admin_url('admin-ajax.php?action=sam_track_click&url=' . urlencode($atts['url'])); ?>" class="sam-link" data-slug="<?php echo $slug; ?>" rel="nofollow">Click here for offer</a>
    <?php
    return ob_get_clean();
}
add_shortcode('sam_link', 'sam_link_shortcode');

// JS for analytics (Pro feature)
function sam_js() {
    if (!wp_script_is('sam-script', 'enqueued')) return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sam-link').on('click', function(e) {
            $.post(sam_ajax.ajaxurl, {action: 'sam_track_click', url: $(this).data('url')});
        });
        if (sam_pro) {
            // Load analytics chart
            $('#sam-stats').load('?page=sam-pro #stats-data');
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'sam_js');

SmartAffiliateManager::get_instance();

// Pro upsell notice
function sam_upsell_notice() {
    if (!get_option('sam_pro_activated')) {
        echo '<div class="notice notice-info"><p>Unlock Pro features like A/B testing and analytics: <a href="' . admin_url('options-general.php?page=sam-pro') . '">Upgrade Now</a></p></div>;
    }
}
add_action('admin_notices', 'sam_upsell_notice');