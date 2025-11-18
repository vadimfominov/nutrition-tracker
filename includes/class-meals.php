<?php

class NutritionMeals
{

	public function __construct()
	{
		// AJAX обработчики теперь в основном классе NutritionTracker
		// Удаляем все add_action для AJAX
	}

	/**
	 * Добавить прием пищи
	 */
	public function add_user_meal($user_id, $product_id, $grams, $meal_date, $meal_type)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'nutrition_meals';

		return $wpdb->insert(
			$table_name,
			array(
				'user_id' => $user_id,
				'product_id' => $product_id,
				'grams' => $grams,
				'meal_date' => $meal_date,
				'meal_type' => $meal_type
			),
			array('%d', '%d', '%f', '%s', '%s')
		);
	}

	/**
	 * Получить приемы пищи за дату
	 */
	public function get_user_meals_by_date($user_id, $date)
	{
		global $wpdb;
		$meals_table = $wpdb->prefix . 'nutrition_meals';
		$products_table = $wpdb->prefix . 'nutrition_products';

		return $wpdb->get_results($wpdb->prepare(
			"SELECT m.*, p.name, p.proteins, p.carbs, p.fats, p.calories 
             FROM $meals_table m 
             LEFT JOIN $products_table p ON m.product_id = p.id 
             WHERE m.user_id = %d AND m.meal_date = %s 
             ORDER BY m.meal_type, m.created_at",
			$user_id,
			$date
		));
	}
}
