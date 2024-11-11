jQuery(document).ready(function ($) {
  // Обработчик для добавления товара в список сравнения
  $('.compare-button').click(function () {
    var productId = $(this).data('product-id');
    var button = $(this);

    // Проверяем, есть ли уже товар в списке
    if (button.hasClass('added')) {
      return; // Если товар уже в списке, не делаем ничего
    }

    $.ajax({
      url: my_ajax_object.ajax_url,
      type: 'POST',
      data: {
        action: 'add_to_compare',
        product_id: productId
      },
      success: function (response) {
        if (response.success) {
          alert(response.data.message);
          updateCompareCount();
          button.addClass('added'); // Добавляем класс для товара в списке
        } else {
          alert(response.data.message);
        }
      }
    });
  });

  // Обновление количества товаров в списке сравнения
  function updateCompareCount() {
    $.ajax({
      url: my_ajax_object.ajax_url,
      type: 'POST',
      data: {
        action: 'load_compare_items'
      },
      success: function (response) {
        if (response.success) {
          var compareCount = response.data.html.split('compare-item').length - 1;
          $('#compare-count').text(compareCount);
        }
      }
    });
  }

  // Открытие модального окна сравнения
  $('.open-comparison-popup').click(function () {
    $('#comparison-popup').fadeIn();
    loadCompareItems();
  });

  // Закрытие модального окна
  $('.close-comparison-popup').click(function () {
    $('#comparison-popup').fadeOut();
  });

  // Загрузка товаров для сравнения
  function loadCompareItems() {
    $.ajax({
      url: my_ajax_object.ajax_url,
      type: 'POST',
      data: {
        action: 'load_compare_items'
      },
      success: function (response) {
        if (response.success) {
          $('#compare-items').html(response.data.html);
        }
      }
    });
  }

  // Удаление товара из списка сравнения
  $(document).on('click', '.remove-compare', function () {
    var productId = $(this).data('product-id');
    var button = $(this);

    $.ajax({
      url: my_ajax_object.ajax_url,
      type: 'POST',
      data: {
        action: 'remove_from_compare',
        product_id: productId
      },
      success: function (response) {
        if (response.success) {
          alert(response.data.message);
          loadCompareItems();
          updateCompareCount();

          // Удаляем класс с кнопки
          var compareButton = $('.compare-button[data-product-id="' + productId + '"]');
          compareButton.removeClass('added');
        } else {
          alert(response.data.message);
        }
      }
    });
  });
  updateCompareCount();
});