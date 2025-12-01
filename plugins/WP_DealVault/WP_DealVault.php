<?php
/*
Plugin Name: WP DealVault
Description: Curated coupon and deal marketplace for affiliate revenue and engagement.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_DealVault.php
*/

if (!defined('ABSPATH')) exit;

class WP_DealVault {
  private $version = '1.0';
  private $post_type = 'wpdeal';

  function __construct() {
    add_action('init', [$this, 'register_deal_post_type']);
    add_action('add_meta_boxes', [$this, 'add_deal_meta_boxes']);
    add_action('save_post', [$this, 'save_deal_meta']);
    add_shortcode('wpdealvault_display', [$this, 'display_deals_shortcode']);
    add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
  }

  function register_deal_post_type() {
    $labels = [
      'name' => 'Deals',
      'singular_name' => 'Deal',
      'add_new' => 'Add New Deal',
      'add_new_item' => 'Add New Deal',
      'edit_item' => 'Edit Deal',
      'new_item' => 'New Deal',
      'view_item' => 'View Deal',
      'search_items' => 'Search Deals',
      'not_found' => 'No deals found',
      'not_found_in_trash' => 'No deals found in Trash',
      'all_items' => 'All Deals'
    ];

    $args = [
      'labels' => $labels,
      'public' => true,
      'has_archive' => true,
      'rewrite' => ['slug' => 'deals'],
      'supports' => ['title','editor','thumbnail'],
      'menu_icon' => 'dashicons-tag',
    ];

    register_post_type($this->post_type, $args);
  }

  function add_deal_meta_boxes() {
    add_meta_box('wpdeal_meta', 'Deal Details', [$this, 'render_deal_meta_box'], $this->post_type, 'normal', 'high');
  }

  function render_deal_meta_box($post) {
    wp_nonce_field('wpdeal_save_meta', 'wpdeal_nonce');
    $affiliate_link = get_post_meta($post->ID, '_wpdeal_affiliate_link', true);
    $deal_expiry = get_post_meta($post->ID, '_wpdeal_expiry_date', true);

    echo '<p><label for="wpdeal_affiliate_link">Affiliate URL:</label><br/><input type="url" style="width:100%;" id="wpdeal_affiliate_link" name="wpdeal_affiliate_link" value="' . esc_attr($affiliate_link) . '" placeholder="https://example.com/affiliate-link"/></p>';
    echo '<p><label for="wpdeal_expiry_date">Expiry Date (optional):</label><br/><input type="date" id="wpdeal_expiry_date" name="wpdeal_expiry_date" value="' . esc_attr($deal_expiry) . '"/></p>';
  }

  function save_deal_meta($post_id) {
    if (!isset($_POST['wpdeal_nonce']) || !wp_verify_nonce($_POST['wpdeal_nonce'], 'wpdeal_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['wpdeal_affiliate_link'])) {
      $affiliate_link = esc_url_raw($_POST['wpdeal_affiliate_link']);
      update_post_meta($post_id, '_wpdeal_affiliate_link', $affiliate_link);
    }

    if (isset($_POST['wpdeal_expiry_date'])) {
      $expiry_date = sanitize_text_field($_POST['wpdeal_expiry_date']);
      update_post_meta($post_id, '_wpdeal_expiry_date', $expiry_date);
    }
  }

  function enqueue_scripts() {
    wp_enqueue_style('wpdealvault-style', plugin_dir_url(__FILE__) . 'wpdealvault-style.css');
  }

  function display_deals_shortcode($atts) {
    $atts = shortcode_atts(['count' => 10], $atts, 'wpdealvault_display');
    $today = date('Y-m-d');

    $args = [
      'post_type' => $this->post_type,
      'posts_per_page' => intval($atts['count']),
      'meta_query' => [
        'relation' => 'OR',
        ['key' => '_wpdeal_expiry_date', 'value' => $today, 'compare' => '>='],
        ['key' => '_wpdeal_expiry_date', 'compare' => 'NOT EXISTS'],
      ],
      'meta_key' => '_wpdeal_expiry_date',
      'orderby' => 'meta_value',
      'order' => 'ASC'
    ];

    $deals = new WP_Query($args);
    if (!$deals->have_posts()) {
      return '<p>No deals available at the moment. Please check back later.</p>';
    }

    $output = '<div class="wpdealvault-container">';
    while ($deals->have_posts()) {
      $deals->the_post();
      $affiliate_link = get_post_meta(get_the_ID(), '_wpdeal_affiliate_link', true);
      $expiry_date = get_post_meta(get_the_ID(), '_wpdeal_expiry_date', true);

      $expiry_display = $expiry_date ? 'Expires on ' . esc_html(date('F j, Y', strtotime($expiry_date))) : '';
      $output .= '<div class="wpdealvault-deal">';
      $output .= '<h3><a href="' . esc_url($affiliate_link) . '" target="_blank" rel="nofollow noopener">' . get_the_title() . '</a></h3>';
      $output .= '<div class="wpdealvault-description">' . get_the_excerpt() . '</div>';
      if ($expiry_display) {
        $output .= '<div class="wpdealvault-expiry">' . $expiry_display . '</div>';
      }
      $output .= '<a class="wpdealvault-cta" href="' . esc_url($affiliate_link) . '" target="_blank" rel="nofollow noopener">Get Deal</a>';
      $output .= '</div>';
    }
    wp_reset_postdata();
    $output .= '</div>';
    return $output;
  }
}

new WP_DealVault();
