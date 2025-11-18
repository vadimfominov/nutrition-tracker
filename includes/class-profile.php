<?php

class NutritionProfile
{

	public function __construct()
	{
		// AJAX обработчики теперь в основном классе
		// Оставляем только хуки для админки
		add_action('show_user_profile', array($this, 'add_profile_fields'));
		add_action('edit_user_profile', array($this, 'add_profile_fields'));
		add_action('personal_options_update', array($this, 'save_profile_fields'));
		add_action('edit_user_profile_update', array($this, 'save_profile_fields'));
	}

	public function add_profile_fields($user)
	{
?>
		<h3>Данные для трекера питания</h3>
		<table class="form-table">
			<tr>
				<th><label for="age">Возраст</label></th>
				<td>
					<input type="number" name="age" id="age"
						value="<?php echo esc_attr(get_the_author_meta('age', $user->ID)); ?>"
						class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="weight">Вес (кг)</label></th>
				<td>
					<input type="number" step="0.1" name="weight" id="weight"
						value="<?php echo esc_attr(get_the_author_meta('weight', $user->ID)); ?>"
						class="regular-text" />
				</td>
			</tr>
			<tr>
				<th><label for="photo">Фото профиля</label></th>
				<td>
					<?php
					$photo_id = get_the_author_meta('photo', $user->ID);
					if ($photo_id) {
						echo wp_get_attachment_image($photo_id, 'thumbnail');
					}
					?>
					<input type="file" name="photo" id="photo" />
				</td>
			</tr>
		</table>
<?php
	}

	public function save_profile_fields($user_id)
	{
		if (!current_user_can('edit_user', $user_id)) {
			return false;
		}

		if (isset($_POST['age'])) {
			update_user_meta($user_id, 'age', intval($_POST['age']));
		}

		if (isset($_POST['weight'])) {
			update_user_meta($user_id, 'weight', floatval($_POST['weight']));
		}
	}

	public function get_profile_data($user_id = null)
	{
		if (!$user_id) {
			$user_id = get_current_user_id();
		}

		$user_data = get_userdata($user_id);

		return array(
			'name' => $user_data->display_name,
			'age' => get_user_meta($user_id, 'age', true),
			'weight' => get_user_meta($user_id, 'weight', true),
			'photo' => get_user_meta($user_id, 'photo', true)
		);
	}

	/**
	 * Обновить профиль пользователя
	 */
	public function update_user_profile($user_id, $age, $weight, $photo = null)
	{
		update_user_meta($user_id, 'age', $age);
		update_user_meta($user_id, 'weight', $weight);

		if ($photo) {
			update_user_meta($user_id, 'photo', $photo);
		}

		return true;
	}
}
?>