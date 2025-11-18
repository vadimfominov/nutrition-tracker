<?php

class NutritionTelegramAuth
{

	public function __construct()
	{
		$this->init_ajax_handlers();
	}

	private function init_ajax_handlers()
	{
		add_action('wp_ajax_nopriv_telegram_auto_auth', array($this, 'handle_telegram_auto_auth'));
		add_action('wp_ajax_telegram_get_user', array($this, 'handle_get_user'));

		add_action('wp_ajax_telegram_logout', array($this, 'handle_telegram_logout'));
		add_action('wp_ajax_nopriv_telegram_logout', array($this, 'handle_telegram_logout'));
	}

	/**
	 * Автоматическая авторизация через Telegram ID
	 */
	public function handle_telegram_auto_auth()
	{
		// Для Telegram можно убрать nonce проверку или использовать initData от Telegram
		// check_ajax_referer('nutrition_nonce', 'nonce');

		error_log('Telegram Auto Auth: Starting...');

		if (!isset($_POST['telegram_id'])) {
			error_log('Telegram Auto Auth: No telegram_id received');
			wp_send_json_error('Telegram ID не получен');
		}

		$telegram_id = intval($_POST['telegram_id']);
		$first_name = isset($_POST['first_name']) ? sanitize_text_field($_POST['first_name']) : 'User';
		$last_name = isset($_POST['last_name']) ? sanitize_text_field($_POST['last_name']) : '';
		$username = isset($_POST['username']) ? sanitize_text_field($_POST['username']) : '';

		error_log("Telegram Auto Auth: Processing user - ID: $telegram_id, Name: $first_name");

		// Проверяем, существует ли пользователь
		$existing_user_id = $this->get_user_by_telegram_id($telegram_id);

		if ($existing_user_id) {
			// Пользователь существует - логиним
			error_log("Telegram Auto Auth: User exists, logging in - WP User ID: $existing_user_id");
			return $this->login_existing_user($existing_user_id);
		} else {
			// Создаем нового пользователя
			error_log("Telegram Auto Auth: Creating new user");
			return $this->create_telegram_user($telegram_id, $first_name, $last_name, $username);
		}
	}

	/**
	 * Поиск пользователя по Telegram ID
	 */
	private function get_user_by_telegram_id($telegram_id)
	{
		$users = get_users(array(
			'meta_key' => 'telegram_id',
			'meta_value' => $telegram_id,
			'fields' => 'ID'
		));

		return !empty($users) ? $users[0] : false;
	}

	/**
	 * Создание нового пользователя из данных Telegram
	 */
	private function create_telegram_user($telegram_id, $first_name, $last_name = '', $username = '')
	{
		// Генерируем уникальный username
		$wp_username = $this->generate_username($telegram_id, $username, $first_name);
		$email = $telegram_id . '@telegram.nutrition';
		$password = wp_generate_password(12, true);

		error_log("Creating WordPress user - Username: $wp_username, Email: $email");

		// Создаем пользователя
		$user_id = wp_create_user($wp_username, $password, $email);

		if (is_wp_error($user_id)) {
			error_log('Error creating user: ' . $user_id->get_error_message());
			wp_send_json_error('Ошибка создания пользователя: ' . $user_id->get_error_message());
		}

		// Сохраняем метаданные Telegram
		$this->save_telegram_user_data($user_id, $telegram_id, $first_name, $last_name, $username);

		// Логиним пользователя
		$this->login_user($user_id);

		error_log("User created successfully - WP User ID: $user_id");

		wp_send_json_success(array(
			'message' => 'Аккаунт создан автоматически!',
			'user_id' => $user_id,
			'first_name' => $first_name,
			'wp_username' => $wp_username
		));
	}

	/**
	 * Логин существующего пользователя
	 */
	private function login_existing_user($user_id)
	{
		$this->login_user($user_id);

		$user_data = get_userdata($user_id);
		$first_name = get_user_meta($user_id, 'first_name', true) ?: $user_data->display_name;

		error_log("User logged in successfully - WP User ID: $user_id");

		wp_send_json_success(array(
			'message' => 'Автоматический вход выполнен!',
			'user_id' => $user_id,
			'first_name' => $first_name
		));
	}

	/**
	 * Логин пользователя
	 */
	private function login_user($user_id)
	{
		wp_set_current_user($user_id);
		wp_set_auth_cookie($user_id);

		// Обновляем время последнего входа
		update_user_meta($user_id, 'last_login', current_time('mysql'));
	}

	/**
	 * Генерация username
	 */
	private function generate_username($telegram_id, $telegram_username, $first_name)
	{
		// Пробуем использовать telegram username
		if (!empty($telegram_username)) {
			$username = sanitize_user($telegram_username);
			if (!username_exists($username)) {
				return $username;
			}
		}

		// Пробуем комбинацию имени и ID
		$base_username = sanitize_user($first_name . '_' . $telegram_id);
		if (!username_exists($base_username)) {
			return $base_username;
		}

		// Добавляем суффикс если username занят
		$username = $base_username;
		$counter = 1;

		while (username_exists($username)) {
			$username = $base_username . '_' . $counter;
			$counter++;
		}

		return $username;
	}

	/**
	 * Сохранение данных Telegram
	 */
	private function save_telegram_user_data($user_id, $telegram_id, $first_name, $last_name, $username)
	{
		update_user_meta($user_id, 'telegram_id', $telegram_id);
		update_user_meta($user_id, 'first_name', $first_name);

		if (!empty($last_name)) {
			update_user_meta($user_id, 'last_name', $last_name);
		}

		if (!empty($username)) {
			update_user_meta($user_id, 'telegram_username', $username);
		}

		// Устанавливаем display_name
		$display_name = !empty($last_name)
			? $first_name . ' ' . $last_name
			: $first_name;

		wp_update_user(array(
			'ID' => $user_id,
			'display_name' => $display_name,
			'first_name' => $first_name,
			'last_name' => $last_name
		));

		error_log("Saved user meta - Telegram ID: $telegram_id, Display Name: $display_name");
	}

	/**
	 * Получение данных текущего пользователя
	 */
	public function handle_get_user()
	{
		if (!is_user_logged_in()) {
			wp_send_json_error('Пользователь не авторизован');
		}

		$user_id = get_current_user_id();
		$user_data = $this->get_user_profile_data($user_id);

		wp_send_json_success($user_data);
	}

	/**
	 * Получение данных профиля
	 */
	public function get_user_profile_data($user_id)
	{
		$user = get_userdata($user_id);

		return array(
			'id' => $user_id,
			'telegram_id' => get_user_meta($user_id, 'telegram_id', true),
			'username' => $user->user_login,
			'display_name' => $user->display_name,
			'first_name' => get_user_meta($user_id, 'first_name', true),
			'last_name' => get_user_meta($user_id, 'last_name', true),
			'telegram_username' => get_user_meta($user_id, 'telegram_username', true)
		);
	}


	/**
	 * Logout при закрытии приложения
	 */
	public function handle_telegram_logout()
	{
		error_log('Telegram Logout: Starting logout process');

		if (is_user_logged_in()) {
			$user_id = get_current_user_id();
			$user_data = get_userdata($user_id);

			error_log("Telegram Logout: Logging out user ID: $user_id, Username: " . $user_data->user_login);

			// Логируем время выхода
			update_user_meta($user_id, 'last_logout', current_time('mysql'));

			// Выполняем logout
			wp_logout();

			// Уничтожаем все сессии
			wp_destroy_all_sessions();

			error_log("Telegram Logout: User $user_id successfully logged out");

			wp_send_json_success('Logout successful');
		} else {
			error_log('Telegram Logout: No user logged in');
			wp_send_json_success('No user to logout');
		}
	}
}
