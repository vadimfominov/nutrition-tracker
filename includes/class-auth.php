<?php

class NutritionAuth
{

	public function __construct()
	{
		$this->init_ajax_handlers();
	}

	private function init_ajax_handlers()
	{
		// Обработчики для авторизации и регистрации
		add_action('wp_ajax_nopriv_register_user', array($this, 'handle_register_user'));
		add_action('wp_ajax_nopriv_login_user', array($this, 'handle_login_user'));
		add_action('wp_ajax_logout_user', array($this, 'handle_logout_user'));
	}

	/**
	 * Регистрация пользователя
	 */
	// public function handle_register_user()
	// {
	// 	check_ajax_referer('nutrition_nonce', 'nonce');

	// 	if (!isset($_POST['username']) || !isset($_POST['email']) || !isset($_POST['password']) || !isset($_POST['confirm_password'])) {
	// 		wp_send_json_error('Все поля обязательны для заполнения');
	// 	}

	// 	$username = sanitize_user($_POST['username']);
	// 	$email = sanitize_email($_POST['email']);
	// 	$password = $_POST['password'];
	// 	$confirm_password = $_POST['confirm_password'];

	// 	// Валидация
	// 	if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
	// 		wp_send_json_error('Все поля обязательны для заполнения');
	// 	}

	// 	if ($password !== $confirm_password) {
	// 		wp_send_json_error('Пароли не совпадают');
	// 	}

	// 	if (!is_email($email)) {
	// 		wp_send_json_error('Некорректный email адрес');
	// 	}

	// 	if (username_exists($username)) {
	// 		wp_send_json_error('Имя пользователя уже занято');
	// 	}

	// 	if (email_exists($email)) {
	// 		wp_send_json_error('Email уже используется');
	// 	}

	// 	// Создаем пользователя
	// 	$user_id = wp_create_user($username, $password, $email);

	// 	if (is_wp_error($user_id)) {
	// 		wp_send_json_error($user_id->get_error_message());
	// 	}

	// 	// Авторизуем пользователя
	// 	wp_set_current_user($user_id);
	// 	wp_set_auth_cookie($user_id);

	// 	wp_send_json_success('Регистрация успешна!');
	// }

	/**
	 * Авторизация пользователя
	 */
	// public function handle_login_user()
	// {
	// 	check_ajax_referer('nutrition_nonce', 'nonce');

	// 	if (!isset($_POST['username']) || !isset($_POST['password'])) {
	// 		wp_send_json_error('Введите имя пользователя и пароль');
	// 	}

	// 	$username = sanitize_user($_POST['username']);
	// 	$password = $_POST['password'];
	// 	$remember = isset($_POST['remember']) ? true : false;

	// 	// Пробуем авторизовать по email или username
	// 	$user = is_email($username) ? get_user_by('email', $username) : get_user_by('login', $username);

	// 	if (!$user) {
	// 		wp_send_json_error('Неверное имя пользователя или пароль');
	// 	}

	// 	$credentials = array(
	// 		'user_login' => $user->user_login,
	// 		'user_password' => $password,
	// 		'remember' => $remember
	// 	);

	// 	$user = wp_signon($credentials, false);

	// 	if (is_wp_error($user)) {
	// 		wp_send_json_error('Неверное имя пользователя или пароль');
	// 	}

	// 	wp_set_current_user($user->ID);
	// 	wp_send_json_success('Вход выполнен успешно!');
	// }

	/**
	 * Выход пользователя
	 */
	 public function handle_logout_user()
	{
	 	check_ajax_referer('nutrition_nonce', 'nonce');

		wp_logout();
	 	wp_send_json_success('Выход выполнен успешно');
	 }

	/**
	 * Проверка авторизации
	 */
	public function is_user_logged_in()
	{
		return is_user_logged_in();
	}

	/**
	 * Получение данных текущего пользователя
	 */
	public function get_current_user_data()
	{
		if (!is_user_logged_in()) {
			return false;
		}

		$user = wp_get_current_user();

		return array(
			'id' => $user->ID,
			'username' => $user->user_login,
			'email' => $user->user_email,
			'display_name' => $user->display_name,
			'first_name' => $user->first_name,
			'age' => get_user_meta($user->ID, 'age', true),
			'weight' => get_user_meta($user->ID, 'weight', true)
		);
	}
}
