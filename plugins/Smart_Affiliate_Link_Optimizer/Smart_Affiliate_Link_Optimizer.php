/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Optimizer
 * Plugin URI: https://example.com/smart-affiliate-optimizer
 * Description: Automatically optimizes and tracks affiliate links in posts, cloaks them for better conversions, and displays performance stats to boost earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class SmartAffiliateOptimizer {
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
        add_filter('the_content', array($this, 'cloak_affiliate_links'));
        add_action('wp_head', array($this, 'add_tracking_script'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Initialize settings
        $this->options = get_option('saol_settings', array(
            'cloak_links' => true,
            'track_clicks' => true,
            'api_key' => '' // For premium
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saol-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('saol-tracker', 'saol_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('saol_nonce')));
    }

    public function cloak_affiliate_links($content) {
        if (!$this->options['cloak_links']) return $content;

        // Regex to find affiliate links (customize patterns for Amazon, etc.)
        $patterns = array(
            '/https?:\/\/(?:www\.)?(amazon|clickbank|shareasale|commissionjunction)\.[a-z\/\.]+/i',
            '/\b(?:aff|ref|tag)=[a-z0-9]+/i'
        );

        foreach ($patterns as $pattern) {
            $content = preg_replace_callback($pattern, array($this, 'cloak_callback'), $content);
        }
        return $content;
    }

    private function cloak_callback($matches) {
        $original = $matches;
        $id = uniqid('saol_');
        $cloaked = admin_url('?saol_redirect=' . base64_encode($original));

        return '<a href="' . esc_url($cloaked) . '" class="saol-link" data-original="' . esc_attr($original) . '" data-id="' . $id . '">' . $original . '</a>';
    }

    public function add_tracking_script() {
        if (!$this->options['track_clicks']) return;
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.saol-link').on('click', function(e) {
                var original = $(this).data('original');
                var id = $(this).data('id');
                $.post(saol_ajax.ajax_url, {
                    action: 'saol_track_click',
                    nonce: saol_ajax.nonce,
                    link_id: id,
                    original: original
                });
            });
        });
        </script>
        <?php
    }

    public function admin_menu() {
        add_options_page('Smart Affiliate Optimizer', 'SAO Settings', 'manage_options', 'saol-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('saol_settings', $_POST['saol_settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $options = $this->options;
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Optimizer Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Cloak Links</th>
                        <td><input type="checkbox" name="saol_settings[cloak_links]" <?php checked($options['cloak_links']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Track Clicks</th>
                        <td><input type="checkbox" name="saol_settings[track_clicks]" <?php checked($options['track_clicks']); ?> /></td>
                    </tr>
                    <tr>
                        <th>Premium API Key</th>
                        <td><input type="text" name="saol_settings[api_key]" value="<?php echo esc_attr($options['api_key']); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <h2>Stats</h2>
            <p>Total clicks tracked: <?php echo get_option('saol_clicks', 0); ?></p>
            <p><strong>Upgrade to Premium for detailed analytics and A/B testing!</strong></p>
        </div>
        <?php
    }

    public static function activate() {
        add_option('saol_settings', array('cloak_links' => true, 'track_clicks' => true));
    }
}

// AJAX handler
add_action('wp_ajax_saol_track_click', 'saol_handle_click');
function saol_handle_click() {
    check_ajax_referer('saol_nonce', 'nonce');
    $clicks = get_option('saol_clicks', 0) + 1;
    update_option('saol_clicks', $clicks);
    wp_die('Tracked');
}

// Handle redirects
add_action('init', 'saol_handle_redirect');
function saol_handle_redirect() {
    if (isset($_GET['saol_redirect'])) {
        $original = base64_decode(sanitize_url($_GET['saol_redirect']));
        wp_redirect(esc_url_raw($original));
        exit;
    }
}

// Prevent direct JS file, but for single-file, inline it
add_action('wp_footer', 'saol_inline_tracker');
function saol_inline_tracker() {
    if (!$GLOBALS['SmartAffiliateOptimizer']->options['track_clicks']) return;
    ?>
    <script>jQuery(document).ready(function($) { $('.saol-link').on('click', function() { var data = {action: 'saol_track_click', nonce: '<?php echo wp_create_nonce('saol_nonce'); ?>', link_id: $(this).data('id') }; $.post('<?php echo admin_url('admin-ajax.php'); ?>', data); }); });</script>
    <?php
}

SmartAffiliateOptimizer::get_instance();

// Premium teaser
add_action('admin_notices', 'saol_premium_notice');
function saol_premium_notice() {
    if (!get_option('saol_settings')['api_key']) {
        echo '<div class="notice notice-info"><p>Unlock advanced features with <a href="https://example.com/premium">Smart Affiliate Optimizer Premium</a>! Track conversions, A/B test links, and more.</p></div>';
    }
}
?>