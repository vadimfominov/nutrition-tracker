<?php

class NutritionStatistics
{

	public function __construct()
	{
		// AJAX обработчики теперь в основном классе
	}

	public function get_daily_statistics($date = null)
	{
		if (!is_user_logged_in()) {
			return false;
		}

		if (!$date) {
			$date = current_time('Y-m-d');
		}

		$user_id = get_current_user_id();
		global $wpdb;
		$meals_table = $wpdb->prefix . 'nutrition_meals';
		$products_table = $wpdb->prefix . 'nutrition_products';

		$results = $wpdb->get_results($wpdb->prepare(
			"SELECT m.grams, p.proteins, p.carbs, p.fats, p.calories 
             FROM $meals_table m 
             LEFT JOIN $products_table p ON m.product_id = p.id 
             WHERE m.user_id = %d AND m.meal_date = %s",
			$user_id,
			$date
		));

		$stats = array(
			'proteins' => 0,
			'carbs' => 0,
			'fats' => 0,
			'calories' => 0
		);

		foreach ($results as $meal) {
			// Проверяем на NULL значения
			$grams = floatval($meal->grams ?? 0);
			$proteins = floatval($meal->proteins ?? 0);
			$carbs = floatval($meal->carbs ?? 0);
			$fats = floatval($meal->fats ?? 0);
			$calories = floatval($meal->calories ?? 0);

			if ($grams > 0) {
				$ratio = $grams / 100;
				$stats['proteins'] += $proteins * $ratio;
				$stats['carbs'] += $carbs * $ratio;
				$stats['fats'] += $fats * $ratio;
				$stats['calories'] += $calories * $ratio;
			}
		}

		// Округляем значения
		$stats['proteins'] = round($stats['proteins'], 1);
		$stats['carbs'] = round($stats['carbs'], 1);
		$stats['fats'] = round($stats['fats'], 1);
		$stats['calories'] = round($stats['calories'], 1);

		return $stats;
	}

	/**
	 * Получить статистику за неделю
	 */
	public function get_weekly_statistics($week)
	{
		if (!is_user_logged_in()) {
			return false;
		}

		// Парсим неделю из формата YYYY-WWW
		preg_match('/(\d{4})-W(\d{2})/', $week, $matches);
		if (!$matches) {
			return false;
		}

		$year = $matches[1];
		$weekNum = $matches[2];

		// Получаем даты начала и конца недели
		$startDate = date('Y-m-d', strtotime($year . 'W' . $weekNum . '1')); // Понедельник
		$endDate = date('Y-m-d', strtotime($year . 'W' . $weekNum . '7')); // Воскресенье

		$user_id = get_current_user_id();
		$days = array();
		$total_calories = 0;
		$total_proteins = 0;
		$total_carbs = 0;
		$total_fats = 0;

		// Получаем данные для каждого дня недели
		$currentDate = $startDate;
		for ($i = 0; $i < 7; $i++) {
			$dayStats = $this->get_daily_statistics($currentDate);

			// Если статистика не получена, используем нулевые значения
			if (!$dayStats || !is_array($dayStats)) {
				$dayStats = array(
					'calories' => 0,
					'proteins' => 0,
					'carbs' => 0,
					'fats' => 0
				);
			}

			$days[] = array(
				'date' => $currentDate,
				'calories' => $dayStats['calories'],
				'proteins' => $dayStats['proteins'],
				'carbs' => $dayStats['carbs'],
				'fats' => $dayStats['fats'],
				'calorie_goal' => $this->get_user_calorie_goal($user_id)
			);

			$total_calories += $dayStats['calories'];
			$total_proteins += $dayStats['proteins'];
			$total_carbs += $dayStats['carbs'];
			$total_fats += $dayStats['fats'];

			$currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
		}

		return array(
			'days' => $days,
			'summary' => array(
				'total_calories' => $total_calories,
				'total_proteins' => $total_proteins,
				'total_carbs' => $total_carbs,
				'total_fats' => $total_fats,
				'calorie_goal' => $this->get_user_calorie_goal($user_id)
			)
		);
	}

	/**
	 * Получить цель по калориям для пользователя
	 */
	private function get_user_calorie_goal($user_id)
	{
		// Пока возвращаем фиксированное значение
		// В будущем можно добавить настройки целей
		return 2000; // ккал в день
	}
}
