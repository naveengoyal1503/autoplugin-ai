/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateDeal_Tracker.php
*/
<?php
/**
 * Plugin Name: AffiliateDeal Tracker
 * Description: Auto-aggregates affiliate deals and coupons from multiple networks with customizable display widgets.
 * Version: 1.0
 * Author: Perplexity AI
 * License: GPL2
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealTracker {
    private static $instance = null;

    private $deals_option_name = 'adt_deals_cache';
    private $cache_expiry = 3600; // 1 hour cache

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('widgets_init', function() {
            register_widget('ADT_Deals_Widget');
        });

        add_action('adt_fetch_deals_cron', array($this, 'fetch_and_cache_deals'));

        if (!wp_next_scheduled('adt_fetch_deals_cron')) {
            wp_schedule_event(time(), 'hourly', 'adt_fetch_deals_cron');
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adt-style', plugin_dir_url(__FILE__).'style.css');
    }

    // Example fetch function - in reality, API keys and endpoints needed
    public function fetch_and_cache_deals() {
        $deals = array();

        // Simulated affiliate deals data from multiple networks
        $deals[] = array(
            'title' => '50% Off Summer Sale',
            'url' => 'https://affiliate-network1.com/deal123?aff_id=001',
            'network' => 'Network1',
            'expires' => strtotime('+7 days'),
        );

        $deals[] = array(
            'title' => 'Buy One Get One Free',
            'url' => 'https://affiliate-network2.com/deal456?aff_id=002',
            'network' => 'Network2',
            'expires' => strtotime('+3 days'),
        );

        // More deals can be added here or pulled from APIs

        update_option($this->deals_option_name, ['data' => $deals, 'timestamp' => time()]);
    }

    public function get_cached_deals() {
        $cache = get_option($this->deals_option_name);
        if (!$cache || (time() - $cache['timestamp'] > $this->cache_expiry)) {
            $this->fetch_and_cache_deals();
            $cache = get_option($this->deals_option_name);
        }
        return $cache['data'];
    }
}

class ADT_Deals_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'adt_deals_widget',
            __('Affiliate Deal Tracker', 'adt'),
            array('description' => __('Displays affiliate deals and coupons.', 'adt'))
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];

        $title = apply_filters('widget_title', $instance['title'] ?? 'Featured Deals');
        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        $deals = AffiliateDealTracker::get_instance()->get_cached_deals();

        if (!empty($deals)) {
            echo '<ul class="adt-deals-list">';
            foreach ($deals as $deal) {
                $ttl = esc_html($deal['title']);
                $link = esc_url($deal['url']);
                $exp = date_i18n(get_option('date_format'), $deal['expires']);
                echo "<li><a href='{$link}' target='_blank' rel='nofollow noopener noreferrer'>{$ttl}</a> <small>(Expires: {$exp})</small></li>";
            }
            echo '</ul>';
        } else {
            echo '<p>' . __('No deals available at the moment.', 'adt') . '</p>';
        }

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : __('Featured Deals', 'adt');
        ?>
        <p>
        <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', 'adt'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" 
            name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

// Initialize plugin
AffiliateDealTracker::get_instance();