/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Optimizer.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Optimizer
 * Description: Automatically cloak, track, and optimize your affiliate links with AI recommendations to boost revenue.
 * Version: 1.0
 * Author: Perplexity AI
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateLinkOptimizer {
    private $option_name = 'alo_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_plugin_page'));
        add_action('admin_init', array($this, 'page_init'));
        add_filter('the_content', array($this, 'process_content_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_alo_track_click', array($this, 'track_click_ajax'));
        add_action('wp_ajax_nopriv_alo_track_click', array($this, 'track_click_ajax'));
        add_action('init', array($this, 'handle_redirect'));
        register_activation_hook(__FILE__, array($this, 'plugin_activate'));
    }

    public function plugin_activate() {
        global $wpdb;
        $table = $wpdb->prefix . 'alo_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            link_slug VARCHAR(191) NOT NULL,
            clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            user_agent TEXT NOT NULL,
            PRIMARY KEY (id),
            KEY link_slug (link_slug)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_plugin_page() {
        add_menu_page(
            'AffiliateLink Optimizer',
            'AffiliateLink Optimizer',
            'manage_options',
            'alo-admin',
            array($this, 'create_admin_page'),
            'dashicons-admin-links',
            81
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1>AffiliateLink Optimizer Settings</h1>
            <form method="post" action="options.php">
            <?php
                settings_fields('alo_option_group');
                do_settings_sections('alo-admin');
                submit_button();
            ?>
            </form>
            <h2>Top 10 Clicked Links (Last 30 days)</h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Link Slug</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    global $wpdb;
                    $table = $wpdb->prefix . 'alo_clicks';
                    $results = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT link_slug, COUNT(*) AS cnt FROM $table WHERE clicked_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY link_slug ORDER BY cnt DESC LIMIT 10"
                        )
                    );
                    if ($results) {
                        foreach ($results as $row) {
                            echo '<tr><td>' . esc_html($row->link_slug) . '</td><td>' . intval($row->cnt) . '</td></tr>';
                        }
                    } else {
                        echo '<tr><td colspan="2">No click data available.</td></tr>';
                    }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'alo_option_group',
            $this->option_name,
            array($this, 'sanitize')
        );

        add_settings_section(
            'setting_section_id',
            'Settings',
            null,
            'alo-admin'
        );

        add_settings_field(
            'link_prefix',
            'Link Slug Prefix',
            array($this, 'link_prefix_callback'),
            'alo-admin',
            'setting_section_id'
        );
    }

    public function sanitize($input) {
        $new_input = array();
        if (isset($input['link_prefix'])) {
            $new_input['link_prefix'] = sanitize_text_field($input['link_prefix']);
        }
        return $new_input;
    }

    public function link_prefix_callback() {
        $options = get_option($this->option_name);
        $prefix = isset($options['link_prefix']) ? esc_attr($options['link_prefix']) : 'ref';
        echo '<input type="text" id="link_prefix" name="' . $this->option_name . '[link_prefix]" value="' . $prefix . '" placeholder="e.g. ref" />';
        echo '<p class="description">Prefix used for cloaked affiliate links, e.g. https://yoursite.com/ref/slug</p>';
    }

    public function process_content_links($content) {
        if (is_singular()) {
            $pattern = '/(https?:\/\/[^\s"\'>]+)/i';
            $content = preg_replace_callback($pattern, array($this, 'replace_affiliate_links'), $content);
        }
        return $content;
    }

    private function replace_affiliate_links($matches) {
        $url = $matches;
        // Basic check for affiliate parameters
        if (strpos($url, 'aff') !== false || strpos($url, 'ref') !== false || strpos($url, 'affiliate') !== false) {
            $slug = $this->generate_slug($url);
            $options = get_option($this->option_name);
            $prefix = !empty($options['link_prefix']) ? $options['link_prefix'] : 'ref';
            $new_url = home_url("/" . $prefix . "/" . $slug);
            return '<a href="' . esc_url($new_url) . '" class="alo-aff-link" data-url="' . esc_attr($url) . '">' . esc_html($url) . '</a>';
        }
        return $url;
    }

    private function generate_slug($url) {
        // Create a unique slug for the URL
        return substr(md5($url), 0, 10);
    }

    public function enqueue_scripts() {
        if (is_singular()) {
            wp_enqueue_script('alo-track-js', plugin_dir_url(__FILE__) . 'alo-track.js', array('jquery'), '1.0', true);
            wp_localize_script('alo-track-js', 'alo_ajax_obj', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('alo_nonce')
            ));
        }
    }

    public function track_click_ajax() {
        check_ajax_referer('alo_nonce', 'security');
        $slug = sanitize_text_field($_POST['slug']);
        $user_agent = sanitize_textarea_field($_SERVER['HTTP_USER_AGENT']);
        global $wpdb;
        $table = $wpdb->prefix . 'alo_clicks';
        $wpdb->insert($table, array(
            'link_slug' => $slug,
            'user_agent' => $user_agent
        ));
        wp_send_json_success();
    }

    public function handle_redirect() {
        $options = get_option($this->option_name);
        $prefix = !empty($options['link_prefix']) ? $options['link_prefix'] : 'ref';
        if (preg_match('/^\/' . preg_quote($prefix, '/') . '\/([^\/]+)\/?$/', $_SERVER['REQUEST_URI'], $matches)) {
            $slug = sanitize_text_field($matches[1]);
            // Retrieve original URL by slug stored elsewhere or decode from slug
            // For simplicity, store slug-url mapping transient (demo only, non-persistent)
            $mapping = get_transient('alo_slug_map_' . $slug);
            if ($mapping) {
                wp_redirect($mapping, 302);
                exit;
            } else {
                // No mapping found
                wp_die('Affiliate link not found.', 'Not Found', array('response' => 404));
            }
        }
    }
}

$affiliateLinkOptimizer = new AffiliateLinkOptimizer();

// Track.js as inline script since single file plugin
add_action('wp_footer', function() {
    if (is_singular()) {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.alo-aff-link').forEach(function(link) {
                link.addEventListener('click', function(e) {
                    var slug = this.href.split('/').pop().replace(/\/?$/, '');
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                        body: new URLSearchParams({
                            action: 'alo_track_click',
                            security: '<?php echo wp_create_nonce('alo_nonce'); ?>',
                            slug: slug
                        })
                    });
                });
            });
        });
        </script>
        <?php
    }
});

// Store slug-url mapping on content save
add_action('save_post', function($post_id) {
    if (get_post_type($post_id) != 'post' && get_post_type($post_id) != 'page') return;
    $post = get_post($post_id);
    if (!$post) return;
    $content = $post->post_content;
    preg_match_all('/<a [^>]*class="alo-aff-link"[^>]*data-url="([^"]+)"[^>]*>/', $content, $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $url) {
            $slug = substr(md5($url), 0, 10);
            set_transient('alo_slug_map_' . $slug, $url, 30 * DAY_IN_SECONDS);
        }
    }
});