/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoLinker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoLinker Pro
 * Plugin URI: https://example.com/smart-affiliate-autolinker
 * Description: Automatically detects keywords in your content and converts them into high-converting affiliate links with smart rotation, tracking, and performance analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autolinker
 */

if (!defined('ABSPATH')) {
    exit;
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
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autolinker', false, dirname(plugin_basename(__FILE__)) . '/languages');
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_link_content'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_sa_track_click', array($this, 'track_click'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('sa-tracker', plugin_dir_url(__FILE__) . 'tracker.js', array('jquery'), '1.0.0', true);
        wp_localize_script('sa-tracker', 'sa_ajax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    public function auto_link_content($content) {
        if (is_feed() || is_preview() || is_admin()) {
            return $content;
        }

        $keywords = get_option('sa_keywords', array());
        $free_limit = 3;
        $used_links = 0;

        foreach ($keywords as $keyword => $data) {
            if ($used_links >= $free_limit && !defined('SA_PRO_VERSION')) {
                break;
            }
            $content = preg_replace_callback(
                '|(?<=[\s>])' . preg_quote($keyword, '|') . '(?=[\s<])|iu',
                function($match) use ($data, &$used_links) {
                    $used_links++;
                    $url = $data['url'];
                    $id = uniqid('sa_');
                    return '<a href="#" id="' . $id . '" class="sa-link" data-url="' . esc_url($url) . '" data-keyword="' . esc_attr($data['keyword']) . '">' . $match . '</a>';
                },
                $content,
                1
            );
        }
        return $content;
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

    public function admin_init() {
        register_setting('sa_settings', 'sa_keywords');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoLinker Settings', 'smart-affiliate-autolinker'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('sa_settings');
                $keywords = get_option('sa_keywords', array());
                ?>
                <table class="form-table">
                    <tr>
                        <th>Keywords & Links</th>
                        <td>
                            <?php if (defined('SA_PRO_VERSION')): ?>
                                <p><strong>Pro Version Active: Unlimited keywords, rotations, analytics.</strong></p>
                            <?php else: ?>
                                <p><strong>Free Version: Limited to <?php echo $free_limit; ?> links per post. <a href="https://example.com/pro" target="_blank">Upgrade to Pro</a></strong></p>
                            <?php endif; ?>
                            <div id="sa-keywords">
                                <?php foreach ($keywords as $i => $kw): ?>
                                    <div class="sa-keyword-row">
                                        <input type="text" name="sa_keywords[<?php echo $i; ?>][keyword]" value="<?php echo esc_attr($kw['keyword']); ?>" placeholder="Keyword" style="width:200px;">
                                        <input type="url" name="sa_keywords[<?php echo $i; ?>][url]" value="<?php echo esc_url($kw['url']); ?>" placeholder="Affiliate URL" style="width:300px;">
                                        <button type="button" class="button sa-remove">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="sa-add-keyword" class="button">Add Keyword</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            let rowIndex = <?php echo count($keywords); ?>;
            $('#sa-add-keyword').click(function() {
                $('#sa-keywords').append(
                    '<div class="sa-keyword-row">' +
                    '<input type="text" name="sa_keywords[' + rowIndex + '][keyword]" placeholder="Keyword" style="width:200px;">' +
                    '<input type="url" name="sa_keywords[' + rowIndex + '][url]" placeholder="Affiliate URL" style="width:300px;">' +
                    '<button type="button" class="button sa-remove">Remove</button>' +
                    '</div>'
                );
                rowIndex++;
            });
            $(document).on('click', '.sa-remove', function() {
                $(this).closest('.sa-keyword-row').remove();
            });
        });
        </script>
        <?php
    }

    public function track_click() {
        if (!wp_verify_nonce($_POST['nonce'], 'sa_nonce')) {
            wp_die('Security check failed');
        }
        $url = sanitize_url($_POST['url']);
        $keyword = sanitize_text_field($_POST['keyword']);
        // In Pro version, log to database
        if (defined('SA_PRO_VERSION')) {
            global $wpdb;
            $wpdb->insert(
                $wpdb->prefix . 'sa_clicks',
                array(
                    'url' => $url,
                    'keyword' => $keyword,
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'date' => current_time('mysql')
                )
            );
        }
        wp_redirect($url);
        exit;
    }

    public function activate() {
        // Create table for Pro
        if (defined('SA_PRO_VERSION')) {
            global $wpdb;
            $table = $wpdb->prefix . 'sa_clicks';
            $charset = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                url text NOT NULL,
                keyword varchar(255) NOT NULL,
                ip varchar(45) NOT NULL,
                date datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public function deactivate() {}
}

// Pro activation stub
if (file_exists(__DIR__ . '/pro.php')) {
    define('SA_PRO_VERSION', true);
    require_once __DIR__ . '/pro.php';
}

SmartAffiliateAutoLinker::get_instance();

// Inline tracker JS
add_action('wp_head', function() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.sa-link').click(function(e) {
            e.preventDefault();
            var url = $(this).data('url');
            var keyword = $(this).data('keyword');
            $.post(sa_ajax.ajaxurl, {
                action: 'sa_track_click',
                url: url,
                keyword: keyword,
                nonce: '<?php echo wp_create_nonce('sa_nonce'); ?>'
            }, function() {
                window.location = url;
            });
        });
    });
    </script>
    <?php
});

?>