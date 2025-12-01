<?php
/*
Plugin Name: Affiliate Deal Vault
Description: Curate and display affiliate coupons and deals with easy shortcode integration.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Vault.php
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class AffiliateDealVault {
  private $deals_option_name = 'adv_deal_vault_deals';

  public function __construct() {
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'settings_init'));
    add_shortcode('adv_deal_vault', array($this, 'render_deals'));
  }

  public function add_admin_menu() {
    add_menu_page('Affiliate Deal Vault', 'Deal Vault', 'manage_options', 'adv_deal_vault', array($this,'options_page'), 'dashicons-tag', 26);
  }

  public function settings_init() {
    register_setting('advDealVault', $this->deals_option_name, array($this, 'sanitize_deals'));

    add_settings_section('advDealVault_section', __('Manage Affiliate Deals', 'advDealVault'), null, 'advDealVault');

    add_settings_field(
      'advDealVault_deals',
      __('Deals JSON', 'advDealVault'),
      array($this, 'deals_field_render'),
      'advDealVault',
      'advDealVault_section'
    );
  }

  public function deals_field_render() {
    $options = get_option($this->deals_option_name, '[]');
    echo '<textarea cols=80 rows=10 name="' . esc_attr($this->deals_option_name) . '">' . esc_textarea(is_array($options) ? json_encode($options) : $options) . '</textarea>';
    echo '<p class="description">Enter deals as JSON array. Example:<br>[{"title":"10% Off Store X","url":"https://affiliatelink.com/storex","expiry":"2025-12-31"}]</p>';
  }

  public function sanitize_deals($input) {
    $decoded = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
      add_settings_error($this->deals_option_name, 'invalid_json', 'Invalid JSON format for deals.');
      return get_option($this->deals_option_name);
    }
    // Validate each deal
    foreach($decoded as &$deal) {
      if (!isset($deal['title']) || !isset($deal['url'])) continue;
      $deal['title'] = sanitize_text_field($deal['title']);
      $deal['url'] = esc_url_raw($deal['url']);
      if (isset($deal['expiry'])) {
        $deal['expiry'] = sanitize_text_field($deal['expiry']);
      }
    }
    return $decoded;
  }

  public function options_page() {
    ?>
    <form action='options.php' method='post'>
      <h1>Affiliate Deal Vault</h1>
      <?php
      settings_fields('advDealVault');
      do_settings_sections('advDealVault');
      submit_button();
      ?>
    </form>
    <?php
  }

  public function render_deals() {
    $deals = get_option($this->deals_option_name, array());
    if (empty($deals)) return '<p>No deals available at the moment.</p>';
    $output = '<div class="adv-deal-vault"><ul style="list-style:none;padding-left:0;">';
    $today = date('Y-m-d');
    foreach($deals as $deal) {
      if (isset($deal['expiry']) && $deal['expiry'] < $today) continue;
      $title = esc_html($deal['title']);
      $url = esc_url($deal['url']);
      $output .= '<li style="margin-bottom:10px;"><a href="' . $url . '" target="_blank" rel="nofollow noopener">' . $title . '</a></li>';
    }
    $output .= '</ul></div>';
    return $output;
  }
}

new AffiliateDealVault();