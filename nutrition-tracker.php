<?php

/**
 * Plugin Name: Nutrition Tracker
 * Description: Приложение для учета питания и нутриентов
 * Version: 1.1.1
 * Author: Vadim Fominov
 */

// Безопасность
if (!defined('ABSPATH')) {
	exit;
}

// Определяем константы
define('NUTRITION_TRACKER_PATH', plugin_dir_path(__FILE__));
define('NUTRITION_TRACKER_URL', plugin_dir_url(__FILE__));

class NutritionTracker
{

	public $products;
	public $meals;
	public $statistics;
	public $profile;
	public $telegram_auth;
	public $auth;
	public $admin;

	public function __construct()
	{
		// Подключаем классы после инициализации WordPress
		add_action('init', array($this, 'init'));

		register_activation_hook(__FILE__, array($this, 'activate'));
		register_deactivation_hook(__FILE__, array($this, 'deactivate'));

		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_shortcode('nutrition_tracker', array($this, 'display_tracker'));

		// Инициализируем AJAX обработчики (кроме auth)
		$this->init_ajax_handlers();
	}

	public function init()
	{
		// Подключаем классы
		require_once NUTRITION_TRACKER_PATH . 'includes/class-telegram-auth.php';

		require_once NUTRITION_TRACKER_PATH . 'includes/class-auth.php';
		require_once NUTRITION_TRACKER_PATH . 'includes/class-products.php';
		require_once NUTRITION_TRACKER_PATH . 'includes/class-meals.php';
		require_once NUTRITION_TRACKER_PATH . 'includes/class-statistics.php';
		require_once NUTRITION_TRACKER_PATH . 'includes/class-profile.php';

		require_once NUTRITION_TRACKER_PATH . 'includes/admin-settings.php';



		// Инициализируем классы
		$this->telegram_auth = new NutritionTelegramAuth();

		$this->auth = new NutritionAuth();
		$this->products = new NutritionProducts();
		$this->meals = new NutritionMeals();
		$this->statistics = new NutritionStatistics();
		$this->profile = new NutritionProfile();

		// Инициализируем админ-панель после загрузки классов
		if (!isset($this->admin)) {
			$this->admin = new NutritionTrackerAdmin();
		}
	}

	private function init_ajax_handlers()
	{
		// Продукты
		add_action('wp_ajax_add_product', array($this, 'handle_add_product'));
		add_action('wp_ajax_get_products', array($this, 'handle_get_products'));

		// Приемы пищи
		add_action('wp_ajax_add_meal', array($this, 'handle_add_meal'));
		add_action('wp_ajax_get_today_meals', array($this, 'handle_get_today_meals'));

		// Статистика
		add_action('wp_ajax_get_weekly_stats', array($this, 'handle_get_weekly_stats'));

		// Профиль
		add_action('wp_ajax_update_profile', array($this, 'handle_update_profile'));

		// Админские обработчики для продуктов
		add_action('wp_ajax_nutrition_admin_update_product', array($this, 'handle_admin_update_product'));
		add_action('wp_ajax_nutrition_admin_delete_product', array($this, 'handle_admin_delete_product'));
	}

	public function activate()
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Таблица продуктов
		$products_table = $wpdb->prefix . 'nutrition_products';
		$sql_products = "CREATE TABLE $products_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            name varchar(255) NOT NULL,
            proteins decimal(5,2) NOT NULL,
            carbs decimal(5,2) NOT NULL,
            fats decimal(5,2) NOT NULL,
            calories decimal(7,2) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

		// Таблица приемов пищи
		$meals_table = $wpdb->prefix . 'nutrition_meals';
		$sql_meals = "CREATE TABLE $meals_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id mediumint(9) NOT NULL,
            product_id mediumint(9) NOT NULL,
            grams decimal(7,2) NOT NULL,
            meal_date date NOT NULL,
            meal_type varchar(20) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql_products);
		dbDelta($sql_meals);
	}

	public function deactivate()
	{
		// Очистка при деактивации (если необходимо)
	}

	public function enqueue_scripts()
	{
		$version = time();

		// Подключаем Telegram Web App SDK
		wp_enqueue_script(
			'telegram-webapp',
			'https://telegram.org/js/telegram-web-app.js',
			array(),
			null,
			[
				'in_footer' => true,
				'strategy' => 'async'
			]
		);

		wp_enqueue_style('nutrition-tracker-style', NUTRITION_TRACKER_URL . 'assets/style.css', [], $version, 'screen');
		wp_enqueue_script('nutrition-tracker-script', NUTRITION_TRACKER_URL . 'assets/script.js', array('telegram-webapp'), $version, true);

		// Передаем AJAX URL в JavaScript
		wp_localize_script('nutrition-tracker-script', 'nutrition_ajax', array(
			'ajaxurl' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('nutrition_nonce'),
			'version' => $version
		));
	}

	public function display_tracker()
	{
		ob_start();
		include NUTRITION_TRACKER_PATH . 'templates/main-template.php';
		return ob_get_clean();
	}

	// AJAX обработчики (без auth)
	public function handle_add_product()
	{
		check_ajax_referer('nutrition_nonce', 'nonce');

		if (!is_user_logged_in()) {
			wp_send_json_error('Для добавления продуктов необходимо авторизоваться');
		}

		if (!isset($_POST['name']) || !isset($_POST['proteins']) || !isset($_POST['carbs']) || !isset($_POST['fats']) || !isset($_POST['calories'])) {
			wp_send_json_error('Не все поля заполнены');
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

	public function handle_get_products()
	{
		check_ajax_referer('nutrition_nonce', 'nonce');

		if (!is_user_logged_in()) {
			wp_send_json_error('Необходимо авторизоваться');
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

	public function handle_add_meal()
	{
		check_ajax_referer('nutrition_nonce', 'nonce');

		if (!is_user_logged_in()) {
			wp_send_json_error('Необходимо авторизоваться');
		}

		if (!isset($_POST['product_id']) || !isset($_POST['grams']) || !isset($_POST['meal_type']) || !isset($_POST['meal_date'])) {
			wp_send_json_error('Не все поля заполнены');
		}

		$user_id = get_current_user_id();
		$product_id = intval($_POST['product_id']);
		$grams = floatval($_POST['grams']);
		$meal_type = sanitize_text_field($_POST['meal_type']);
		$meal_date = sanitize_text_field($_POST['meal_date']);

		global $wpdb;
		$table_name = $wpdb->prefix . 'nutrition_meals';

		$result = $wpdb->insert(
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

		if ($result) {
			wp_send_json_success('Прием пищи добавлен');
		} else {
			wp_send_json_error('Ошибка при добавлении приема пищи');
		}
	}

	public function handle_get_today_meals()
	{
		check_ajax_referer('nutrition_nonce', 'nonce');

		if (!is_user_logged_in()) {
			wp_send_json_error('Необходимо авторизоваться');
		}

		if (!isset($_POST['date'])) {
			wp_send_json_error('Дата не указана');
		}

		$user_id = get_current_user_id();
		$date = sanitize_text_field($_POST['date']);

		global $wpdb;
		$meals_table = $wpdb->prefix . 'nutrition_meals';
		$products_table = $wpdb->prefix . 'nutrition_products';

		$meals = $wpdb->get_results($wpdb->prepare(
			"SELECT m.*, p.name, p.proteins, p.carbs, p.fats, p.calories 
             FROM $meals_table m 
             LEFT JOIN $products_table p ON m.product_id = p.id 
             WHERE m.user_id = %d AND m.meal_date = %s 
             ORDER BY m.meal_type, m.created_at",
			$user_id,
			$date
		));

		wp_send_json_success($meals);
	}

	public function handle_update_profile()
	{
		check_ajax_referer('nutrition_nonce', 'nonce');

		if (!is_user_logged_in()) {
			wp_send_json_error('Необходимо авторизоваться');
		}

		$user_id = get_current_user_id();
		$age = isset($_POST['age']) ? intval($_POST['age']) : 0;
		$weight = isset($_POST['weight']) ? floatval($_POST['weight']) : 0;
		$first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : '';

		update_user_meta($user_id, 'age', $age);
		update_user_meta($user_id, 'weight', $weight);
		update_user_meta($user_id, 'first_name', $first_name);

		// Обработка загрузки фото
		if (!empty($_FILES['photo'])) {
			require_once(ABSPATH . 'wp-admin/includes/file.php');
			require_once(ABSPATH . 'wp-admin/includes/image.php');
			require_once(ABSPATH . 'wp-admin/includes/media.php');

			$attachment_id = media_handle_upload('photo', 0);

			if (!is_wp_error($attachment_id)) {
				update_user_meta($user_id, 'photo', $attachment_id);
			}
		}

		wp_send_json_success('Профиль обновлен');
	}

	/**
	 * Получить статистику за неделю
	 */
	public function handle_get_weekly_stats()
	{
		check_ajax_referer('nutrition_nonce', 'nonce');

		if (!is_user_logged_in()) {
			wp_send_json_error('Необходимо авторизоваться');
		}

		// Убеждаемся, что класс статистики инициализирован
		if (!isset($this->statistics)) {
			$this->init();
		}

		if (!isset($_POST['week'])) {
			wp_send_json_error('Неделя не указана');
		}

		$week = sanitize_text_field($_POST['week']);
		$stats = $this->statistics->get_weekly_statistics($week);

		if ($stats) {
			wp_send_json_success($stats);
		} else {
			wp_send_json_error('Не удалось загрузить статистику за неделю');
		}
	}

	/**
	 * Обновление продукта из админки
	 */
	public function handle_admin_update_product()
	{
		// Проверяем права администратора
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Недостаточно прав для выполнения этого действия');
		}

		check_ajax_referer('nutrition_admin_nonce', 'nonce');

		if (!isset($_POST['product_id']) || !isset($_POST['name']) || !isset($_POST['proteins']) || !isset($_POST['carbs']) || !isset($_POST['fats']) || !isset($_POST['calories'])) {
			wp_send_json_error('Не все поля заполнены');
		}

		$product_id = intval($_POST['product_id']);
		$name = sanitize_text_field($_POST['name']);
		$proteins = floatval($_POST['proteins']);
		$carbs = floatval($_POST['carbs']);
		$fats = floatval($_POST['fats']);
		$calories = floatval($_POST['calories']);

		global $wpdb;
		$table_name = $wpdb->prefix . 'nutrition_products';

		$result = $wpdb->update(
			$table_name,
			array(
				'name' => $name,
				'proteins' => $proteins,
				'carbs' => $carbs,
				'fats' => $fats,
				'calories' => $calories
			),
			array('id' => $product_id),
			array('%s', '%f', '%f', '%f', '%f'),
			array('%d')
		);

		if ($result !== false) {
			wp_send_json_success('Продукт успешно обновлен');
		} else {
			wp_send_json_error('Ошибка при обновлении продукта');
		}
	}

	/**
	 * Удаление продукта из админки
	 */
	public function handle_admin_delete_product()
	{
		// Проверяем права администратора
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Недостаточно прав для выполнения этого действия');
		}

		check_ajax_referer('nutrition_admin_nonce', 'nonce');

		if (!isset($_POST['product_id'])) {
			wp_send_json_error('ID продукта не указан');
		}

		$product_id = intval($_POST['product_id']);

		global $wpdb;
		$table_name = $wpdb->prefix . 'nutrition_products';

		// Проверяем, есть ли приемы пищи, связанные с этим продуктом
		$meals_table = $wpdb->prefix . 'nutrition_meals';
		$meals_count = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM $meals_table WHERE product_id = %d",
			$product_id
		));

		if ($meals_count > 0) {
			// Удаляем связанные приемы пищи
			$wpdb->delete(
				$meals_table,
				array('product_id' => $product_id),
				array('%d')
			);
		}

		// Удаляем продукт
		$result = $wpdb->delete(
			$table_name,
			array('id' => $product_id),
			array('%d')
		);

		if ($result !== false) {
			wp_send_json_success('Продукт успешно удален');
		} else {
			wp_send_json_error('Ошибка при удалении продукта');
		}
	}
}

new NutritionTracker();
