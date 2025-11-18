<?php

class NutritionProducts
{

	public function __construct()
	{
		// add_action('wp_ajax_add_product', array($this, 'add_product'));
		// add_action('wp_ajax_get_products', array($this, 'get_products'));
	}

	public function add_product()
	{
		check_ajax_referer('nutrition_nonce', 'nonce');

		if (!is_user_logged_in()) {
			wp_die('Для добавления продуктов необходимо авторизоваться');
		}

		$user_id = get_current_user_id();
		$name = sanitize_text_field($_POST['name']);
		$proteins = floatval($_POST['proteins']);
		$carbs = floatval($_POST['carbs']);
		$fats = floatval($_POST['fats']);
		$calories = floatval($_POST['calories']);

		global $wpdb;
		$table_name = $wpdb->prefix . 'nutrition_products';

		$result = $wpdb->insert(
			$table_name,
			array(
				'user_id' => $user_id,
				'name' => $name,
				'proteins' => $proteins,
				'carbs' => $carbs,
				'fats' => $fats,
				'calories' => $calories
			),
			array('%d', '%s', '%f', '%f', '%f', '%f')
		);

		if ($result) {
			wp_send_json_success('Продукт успешно добавлен');
		} else {
			wp_send_json_error('Ошибка при добавлении продукта');
		}
	}

	public function get_products()
	{
		if (!is_user_logged_in()) {
			wp_die('Необходимо авторизоваться');
		}

		$user_id = get_current_user_id();
		global $wpdb;
		$table_name = $wpdb->prefix . 'nutrition_products';

		$products = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM $table_name WHERE user_id = %d ORDER BY name ASC",
			$user_id
		));

		wp_send_json_success($products);
	}
}
