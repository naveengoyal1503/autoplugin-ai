/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Automatically fetch and display affiliate coupons and deals with click tracking.
 * Version: 1.0
 * Author: Plugin Dev
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class Affiliate_Deal_Booster {
    private $table_name;

    public function __construct(){
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'adb_clicks';
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_deals', array($this, 'display_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_adb_track_click', array($this, 'track_click')); 
        add_action('wp_ajax_nopriv_adb_track_click', array($this, 'track_click'));
    }

    public function activate(){
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
          id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          deal_url varchar(255) NOT NULL,
          clicked_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          user_ip varchar(100) DEFAULT NULL,
          PRIMARY KEY  (id)
        ) $charset_collate;";
        dbDelta($sql);
    }

    public function enqueue_scripts(){
        wp_enqueue_script('adb_main_js', plugin_dir_url(__FILE__) . 'adb_main.js', array('jquery'), '1.0', true);
        wp_localize_script('adb_main_js', 'adb_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        wp_enqueue_style('adb_styles', plugin_dir_url(__FILE__) . 'adb_styles.css');
    }

    public function admin_menu(){
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deals', 'manage_options', 'affiliate-deal-booster', array($this, 'admin_page'), 'dashicons-megaphone', 26);
    }

    public function admin_page(){
        global $wpdb;
        echo '<div class="wrap"><h1>Affiliate Deal Booster - Click Stats</h1>';
        $results = $wpdb->get_results("SELECT deal_url, COUNT(*) as clicks FROM $this->table_name GROUP BY deal_url ORDER BY clicks DESC LIMIT 20");
        echo '<table class="widefat"><thead><tr><th>Deal URL</th><th>Clicks</th></tr></thead><tbody>';
        if($results){
            foreach($results as $row){
                $safe_url = esc_url($row->deal_url);
                echo "<tr><td><a href='$safe_url' target='_blank'>$safe_url</a></td><td>{$row->clicks}</td></tr>";
            }
        } else {
            echo '<tr><td colspan="2">No clicks recorded yet.</td></tr>';
        }
        echo '</tbody></table></div>';
    }

    public function track_click(){
        if(empty($_POST['url'])) wp_send_json_error('No URL');
        $url = esc_url_raw($_POST['url']);
        global $wpdb;
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '';
        $wpdb->insert($this->table_name, array('deal_url' => $url, 'user_ip' => $ip), array('%s', '%s'));
        wp_send_json_success();
    }

    public function display_deals_shortcode($atts){
        $deals = array(
            array(
                'title' => 'Save 20% on Hosting',
                'url' => 'https://example-affiliate.com/deal1?ref=yourID',
                'desc' => 'Get 20% off your hosting with this exclusive coupon!'
            ),
            array(
                'title' => '50% Discount on SEO Tool',
                'url' => 'https://another-affiliate.com/deal2?ref=yourID',
                'desc' => 'Boost your SEO with half price on this pro tool.'
            ),
            array(
                'title' => 'Buy One Get One Free Plugin',
                'url' => 'https://plugin-affiliate.com/deal3?ref=yourID',
                'desc' => 'Exclusive offer - BOGO deal on popular WordPress plugin.'
            )
        );

        $html = '<div class="adb-deals-container">';
        foreach($deals as $deal){
            $title = esc_html($deal['title']);
            $desc = esc_html($deal['desc']);
            $url = esc_url($deal['url']);
            $html .= "<div class='adb-deal'><h3>$title</h3><p>$desc</p><a href='$url' target='_blank' class='adb-link' data-url='$url'>Grab Deal</a></div>";
        }
        $html .= '</div>';

        return $html;
    }
}

new Affiliate_Deal_Booster();

// Inline JS file content (adb_main.js) must be saved alongside plugin for click tracking:
// (This string is for demonstration only; in reality, plugin would instruct to create adb_main.js file.)

/*
jQuery(document).ready(function($){
  $('.adb-link').on('click', function(e){
    var clickUrl = $(this).data('url');
    $.post(adb_ajax.ajax_url, {action: 'adb_track_click', url: clickUrl});
  });
});
*/

// Inline CSS file content (adb_styles.css) must be saved alongside plugin for minimal styling:
// (This string is for demonstration only; in reality, plugin would instruct to create adb_styles.css file.)

/*
.adb-deals-container { display: flex; flex-wrap: wrap; gap: 15px; }
.adb-deal { border: 1px solid #ccc; padding: 15px; width: 30%; box-sizing: border-box; background: #fafafa; }
.adb-deal h3 { margin-top: 0; }
.adb-link { display: inline-block; margin-top: 10px; padding: 8px 12px; background: #0073aa; color: white; text-decoration: none; border-radius: 3px; }
.adb-link:hover { background: #005177; }
*/