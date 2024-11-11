<?php
/*
Plugin Name: WooCommerce Product Comparison
Description: Плагин для сравнения товаров WooCommerce с использованием AJAX и модального окна.
Version: 1.0
Author: vaddy
*/

if (!defined('ABSPATH')) {
  exit; // Защита от прямого доступа
}

class WC_Product_Comparison
{

  public function __construct()
  {
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    add_action('woocommerce_after_shop_loop_item', array($this, 'add_compare_button'));
    add_action('wp_footer', array($this, 'render_comparison_popup')); // Добавляем в футер

    add_action('wp_ajax_add_to_compare', array($this, 'add_to_compare'));
    add_action('wp_ajax_nopriv_add_to_compare', array($this, 'add_to_compare'));
    add_action('wp_ajax_remove_from_compare', array($this, 'remove_from_compare'));
    add_action('wp_ajax_nopriv_remove_from_compare', array($this, 'remove_from_compare'));
    add_action('wp_ajax_load_compare_items', array($this, 'load_compare_items'));
    add_action('wp_ajax_nopriv_load_compare_items', array($this, 'load_compare_items'));
  }

  public function enqueue_scripts()
  {
    // Подключение стилей и скриптов в футере
    wp_enqueue_style('compare-style', plugin_dir_url(__FILE__) . 'compare.css', array(), null, 'all');
    wp_enqueue_script('compare-script', plugin_dir_url(__FILE__) . 'compare.js', array('jquery'), null, true);
    wp_localize_script('compare-script', 'my_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
  }

  public function add_compare_button()
  {
    echo '<button class="compare-button" data-product-id="' . get_the_ID() . '">Добавить к сравнению</button>';
  }

  public function render_comparison_popup()
  {
    echo '
      <button class="open-comparison-popup">Просмотр сравнения (<span id="compare-count">0</span>)</button>
      <div id="comparison-popup" class="comparison-popup">
        <div class="comparison-popup-content">
          <span class="close-comparison-popup">&times;</span>
          <div id="compare-items"></div>
        </div>
      </div>
    ';
  }

  public function add_to_compare()
  {
    $product_id = intval($_POST['product_id']);
    $compare_list = isset($_COOKIE['compare_list']) ? explode(',', $_COOKIE['compare_list']) : array();

    // Ограничение на 4 товара в списке сравнения
    if (count($compare_list) >= 4) {
      wp_send_json_error(array('message' => 'Вы достигли лимита товаров для сравнения (максимум 4)'));
    }

    if (!in_array($product_id, $compare_list)) {
      $compare_list[] = $product_id;
      setcookie('compare_list', implode(',', $compare_list), time() + 3600, '/');
      wp_send_json_success(array('message' => 'Товар добавлен в сравнение'));
    } else {
      wp_send_json_error(array('message' => 'Товар уже добавлен в список сравнения'));
    }
    wp_die();
  }

  public function remove_from_compare()
  {
    $product_id = intval($_POST['product_id']);
    $compare_list = isset($_COOKIE['compare_list']) ? explode(',', $_COOKIE['compare_list']) : array();

    if (($key = array_search($product_id, $compare_list)) !== false) {
      unset($compare_list[$key]);
      setcookie('compare_list', implode(',', $compare_list), time() + 3600, '/');
      wp_send_json_success(array('message' => 'Товар удален из сравнения'));
    } else {
      wp_send_json_error(array('message' => 'Товар не найден в списке сравнения'));
    }
    wp_die();
  }

  public function load_compare_items()
  {
    $compare_list = isset($_COOKIE['compare_list']) ? explode(',', $_COOKIE['compare_list']) : array();
    ob_start();

    if (!empty($compare_list)) {
      echo '<div class="compare-table">';
      foreach ($compare_list as $product_id) {
        $product = wc_get_product($product_id);
        echo '<div class="compare-item">';
        echo '<a href="' . get_permalink($product_id) . '">';
        echo $product->get_image() . '<br>';
        echo '<h3>' . $product->get_name() . '</h3>';
        echo '<p>Цена: ' . $product->get_price_html() . '</p>';
        echo '</a>';
        echo '<button class="remove-compare" data-product-id="' . $product_id . '">Удалить</button>';
        echo '</div>';
      }
      echo '</div>';
    } else {
      echo '<p>Список сравнения пуст</p>';
    }

    $output = ob_get_clean();
    wp_send_json_success(array('html' => $output));
  }
}

new WC_Product_Comparison();
