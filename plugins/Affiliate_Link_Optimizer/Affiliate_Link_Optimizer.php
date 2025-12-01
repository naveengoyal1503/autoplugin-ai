/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Optimizer.php
*/
<?php
/**
 * Plugin Name: Affiliate Link Optimizer
 * Description: Automatically detect, cloak, and track affiliate links with analytics and keyword suggestions.
 * Version: 1.0
 * Author: Plugin Dev
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateLinkOptimizer {
    private $option_name = 'alo_affiliate_links';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_filter('the_content', array($this, 'auto_cloak_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_alo_track_click', array($this, 'track_click_ajax'));
        add_action('wp_ajax_nopriv_alo_track_click', array($this, 'track_click_ajax'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Link Optimizer', 'Affiliate Link Optimizer', 'manage_options', 'affiliate-link-optimizer', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('alo_settings', 'alo_settings');

        add_settings_section('alo_section', 'Affiliate Links Settings', null, 'alo_settings');

        add_settings_field(
            'alo_affiliate_domains',
            'Affiliate Domains (comma separated)',
            array($this, 'domains_render'),
            'alo_settings',
            'alo_section'
        );
    }

    public function domains_render() {
        $options = get_option('alo_settings');
        ?>
        <input type='text' name='alo_settings[affiliate_domains]' value='<?php echo isset($options['affiliate_domains']) ? esc_attr($options['affiliate_domains']) : '';?>' style='width:100%;' placeholder='example.com, anotheraffiliate.com'>
        <p class='description'>Enter domains to cloak (e.g. amazon.com, commissionjunction.com). Separate multiple domains by commas.</p>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Link Optimizer Settings</h2>
            <?php
            settings_fields('alo_settings');
            do_settings_sections('alo_settings');
            submit_button();
            ?>
        </form>
        <h3>Top Affiliate Link Clicks</h3>
        <?php $this->render_click_report(); ?>
        <?php
    }

    private function render_click_report() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alo_clicks';
        $results = $wpdb->get_results("SELECT link, COUNT(*) as clicks FROM {$table_name} GROUP BY link ORDER BY clicks DESC LIMIT 10");

        if(empty($results)) {
            echo '<p>No clicks recorded yet.</p>';
            return;
        }

        echo '<table style="width:100%; border-collapse: collapse;"><thead><tr><th style="border:1px solid #ccc;padding:8px;">Affiliate Link</th><th style="border:1px solid #ccc;padding:8px;">Clicks</th></tr></thead><tbody>';
        foreach($results as $row) {
            echo '<tr><td style="border:1px solid #ccc;padding:8px;font-size:14px;">' . esc_html($row->link) . '</td><td style="border:1px solid #ccc;padding:8px;text-align:center;">' . intval($row->clicks) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    public function auto_cloak_links($content) {
        $options = get_option('alo_settings');
        if(empty($options['affiliate_domains'])) {
            return $content;
        }

        $domains = explode(',', $options['affiliate_domains']);
        $domains = array_map('trim', $domains);

        if(empty($domains)) return $content;

        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->loadHTML('<?xml encoding="utf-8" ?>' . $content);
        $links = $doc->getElementsByTagName('a');

        foreach($links as $link) {
            $href = $link->getAttribute('href');
            foreach($domains as $domain) {
                if(stripos($href, $domain) !== false) {
                    // Cloak link with plugin redirect
                    $encoded_url = urlencode($href);
                    $redirect_url = admin_url('admin-ajax.php?action=alo_redirect&url=' . $encoded_url);
                    $link->setAttribute('href', $redirect_url);
                    $link->setAttribute('rel','nofollow sponsored');
                    $link->setAttribute('class','alo-affiliate-link');
                    $link->setAttribute('target','_blank');
                    break;
                }
            }
        }

        // Save the updated HTML
        $body = $doc->getElementsByTagName('body')->item(0);
        $new_content = '';
        foreach ($body->childNodes as $child) {
            $new_content .= $doc->saveHTML($child);
        }

        return $new_content;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('alo_script', plugin_dir_url(__FILE__) . 'alo_script.js', array('jquery'), '1.0', true);
        wp_localize_script('alo_script', 'alo_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function track_click_ajax() {
        if(!isset($_POST['link'])) {
            wp_send_json_error('No link');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'alo_clicks';
        $link = esc_url_raw($_POST['link']);
        $wpdb->insert($table_name, array('link' => $link, 'clicked_at' => current_time('mysql')));
        wp_send_json_success();
    }

    public function redirect_handler() {
        if(!isset($_GET['action']) || $_GET['action'] !== 'alo_redirect') return;

        if(!isset($_GET['url'])) {
            wp_die('No URL specified');
        }

        $url = esc_url_raw($_GET['url']);

        // Record click
        global $wpdb;
        $table_name = $wpdb->prefix . 'alo_clicks';
        $wpdb->insert($table_name, array('link' => $url, 'clicked_at' => current_time('mysql')));

        // Redirect with 302 temporary redirect
        wp_redirect($url, 302);
        exit;
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'alo_clicks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            link text NOT NULL,
            clicked_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

$alo = new AffiliateLinkOptimizer();
register_activation_hook(__FILE__, array($alo, 'activate'));
add_action('init', array($alo, 'redirect_handler'));