<?php

/**
 * Admin Settings for Nutrition Tracker
 */

class NutritionTrackerAdmin
{

	public function __construct()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
	}

	public function add_admin_menu()
	{
		add_options_page(
			'Nutrition Tracker Settings',
			'Nutrition Tracker',
			'manage_options',
			'nutrition-tracker',
			array($this, 'admin_page')
		);
	}

	public function enqueue_admin_scripts($hook)
	{
		// Загружаем скрипты только на странице настроек плагина
		if ($hook !== 'settings_page_nutrition-tracker') {
			return;
		}

		// jQuery уже доступен в админке WordPress
		wp_enqueue_script('jquery');

		// Добавляем стили
		wp_add_inline_style('wp-admin', $this->get_admin_styles());
	}

	private function get_admin_styles()
	{
		return '
			.nutrition-products-table {
				width: 100%;
				border-collapse: collapse;
				margin-top: 20px;
			}
			.nutrition-products-table th,
			.nutrition-products-table td {
				padding: 12px;
				text-align: left;
				border-bottom: 1px solid #ddd;
			}
			.nutrition-products-table th {
				background-color: #f5f5f5;
				font-weight: 600;
			}
			.nutrition-products-table tr:hover {
				background-color: #f9f9f9;
			}
			.nutrition-edit-form {
				display: none;
				margin-top: 10px;
				padding: 15px;
				background: #f9f9f9;
				border: 1px solid #ddd;
				border-radius: 4px;
			}
			.nutrition-edit-form.active {
				display: block;
			}
			.nutrition-edit-form input {
				width: 100%;
				padding: 8px;
				margin: 5px 0;
				border: 1px solid #ddd;
				border-radius: 4px;
			}
			.nutrition-edit-form .form-row {
				display: grid;
				grid-template-columns: repeat(4, 1fr);
				gap: 10px;
				margin-top: 10px;
			}
			.nutrition-actions {
				display: flex;
				gap: 5px;
			}
			.nutrition-actions button {
				padding: 6px 12px;
				border: none;
				border-radius: 4px;
				cursor: pointer;
				font-size: 13px;
			}
			.button-edit {
				background: #2271b1;
				color: white;
			}
			.button-edit:hover {
				background: #135e96;
			}
			.button-delete {
				background: #dc3232;
				color: white;
			}
			.button-delete:hover {
				background: #a00;
			}
			.button-save {
				background: #00a32a;
				color: white;
			}
			.button-save:hover {
				background: #008a20;
			}
			.button-cancel {
				background: #646970;
				color: white;
			}
			.button-cancel:hover {
				background: #50575e;
			}
			.nutrition-user-info {
				color: #646970;
				font-size: 12px;
			}
		';
	}

	public function admin_page()
	{
		global $wpdb;
		$products_table = $wpdb->prefix . 'nutrition_products';

		// Получаем все продукты с информацией о пользователях
		$products = $wpdb->get_results(
			"SELECT p.*, u.display_name, u.user_email 
			FROM $products_table p 
			LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
			ORDER BY p.created_at DESC"
		);

?>
		<div class="wrap">
			<h1>Nutrition Tracker - Управление продуктами</h1>
			<p>Плагин для учета питания и нутриентов. Используйте шорткод <code>[nutrition_tracker]</code> на любой странице для отображения трекера.</p>

			<h2>Список продуктов</h2>
			
			<?php if (empty($products)): ?>
				<p>Продукты не найдены.</p>
			<?php else: ?>
				<table class="nutrition-products-table">
					<thead>
						<tr>
							<th>ID</th>
							<th>Название</th>
							<th>Белки (г)</th>
							<th>Углеводы (г)</th>
							<th>Жиры (г)</th>
							<th>Калории</th>
							<th>Пользователь</th>
							<th>Дата создания</th>
							<th>Действия</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($products as $product): ?>
							<tr data-product-id="<?php echo esc_attr($product->id); ?>">
								<td><?php echo esc_html($product->id); ?></td>
								<td>
									<strong class="product-name"><?php echo esc_html($product->name); ?></strong>
									<div class="nutrition-edit-form" id="edit-form-<?php echo esc_attr($product->id); ?>">
										<input type="text" name="name" value="<?php echo esc_attr($product->name); ?>" placeholder="Название продукта" required>
										<div class="form-row">
											<input type="number" step="0.1" name="proteins" value="<?php echo esc_attr($product->proteins); ?>" placeholder="Белки (г)" required>
											<input type="number" step="0.1" name="carbs" value="<?php echo esc_attr($product->carbs); ?>" placeholder="Углеводы (г)" required>
											<input type="number" step="0.1" name="fats" value="<?php echo esc_attr($product->fats); ?>" placeholder="Жиры (г)" required>
											<input type="number" step="0.1" name="calories" value="<?php echo esc_attr($product->calories); ?>" placeholder="Калории" required>
										</div>
										<div class="nutrition-actions" style="margin-top: 10px;">
											<button type="button" class="button-save" onclick="saveProduct(<?php echo esc_attr($product->id); ?>)">Сохранить</button>
											<button type="button" class="button-cancel" onclick="cancelEdit(<?php echo esc_attr($product->id); ?>)">Отмена</button>
										</div>
									</div>
								</td>
								<td class="product-proteins"><?php echo esc_html($product->proteins); ?></td>
								<td class="product-carbs"><?php echo esc_html($product->carbs); ?></td>
								<td class="product-fats"><?php echo esc_html($product->fats); ?></td>
								<td class="product-calories"><?php echo esc_html($product->calories); ?></td>
								<td>
									<div class="nutrition-user-info">
										<?php echo esc_html($product->display_name ? $product->display_name : 'ID: ' . $product->user_id); ?>
										<?php if ($product->user_email): ?>
											<br><small><?php echo esc_html($product->user_email); ?></small>
										<?php endif; ?>
									</div>
								</td>
								<td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($product->created_at))); ?></td>
								<td>
									<div class="nutrition-actions">
										<button type="button" class="button-edit" onclick="editProduct(<?php echo esc_attr($product->id); ?>)">Редактировать</button>
										<button type="button" class="button-delete" onclick="deleteProduct(<?php echo esc_attr($product->id); ?>)">Удалить</button>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<script type="text/javascript">
		var nutritionAdmin = {
			ajaxurl: '<?php echo admin_url('admin-ajax.php'); ?>',
			nonce: '<?php echo wp_create_nonce('nutrition_admin_nonce'); ?>'
		};

		function editProduct(productId) {
			// Скрываем все формы редактирования
			var forms = document.querySelectorAll('.nutrition-edit-form');
			for (var i = 0; i < forms.length; i++) {
				forms[i].classList.remove('active');
			}
			
			// Показываем форму редактирования для выбранного продукта
			var form = document.getElementById('edit-form-' + productId);
			if (form) {
				form.classList.add('active');
			}
		}

		function cancelEdit(productId) {
			var form = document.getElementById('edit-form-' + productId);
			if (form) {
				form.classList.remove('active');
			}
		}

		function saveProduct(productId) {
			var form = document.getElementById('edit-form-' + productId);
			if (!form) return;

			var formData = {
				action: 'nutrition_admin_update_product',
				nonce: nutritionAdmin.nonce,
				product_id: productId,
				name: form.querySelector('input[name="name"]').value,
				proteins: form.querySelector('input[name="proteins"]').value,
				carbs: form.querySelector('input[name="carbs"]').value,
				fats: form.querySelector('input[name="fats"]').value,
				calories: form.querySelector('input[name="calories"]').value
			};

			jQuery.post(nutritionAdmin.ajaxurl, formData, function(response) {
				if (response.success) {
					// Обновляем данные в таблице
					var row = document.querySelector('tr[data-product-id="' + productId + '"]');
					if (row) {
						row.querySelector('.product-name').textContent = formData.name;
						row.querySelector('.product-proteins').textContent = formData.proteins;
						row.querySelector('.product-carbs').textContent = formData.carbs;
						row.querySelector('.product-fats').textContent = formData.fats;
						row.querySelector('.product-calories').textContent = formData.calories;
					}
					form.classList.remove('active');
					alert('Продукт успешно обновлен!');
				} else {
					alert('Ошибка: ' + (response.data || 'Не удалось обновить продукт'));
				}
			});
		}

		function deleteProduct(productId) {
			if (!confirm('Вы уверены, что хотите удалить этот продукт? Это действие также удалит все связанные приемы пищи.')) {
				return;
			}

			var formData = {
				action: 'nutrition_admin_delete_product',
				nonce: nutritionAdmin.nonce,
				product_id: productId
			};

			jQuery.post(nutritionAdmin.ajaxurl, formData, function(response) {
				if (response.success) {
					// Удаляем строку из таблицы с анимацией
					var row = document.querySelector('tr[data-product-id="' + productId + '"]');
					if (row) {
						row.style.opacity = '0.5';
						row.style.transition = 'opacity 0.3s';
						setTimeout(function() {
							row.remove();
							// Проверяем, не пуста ли таблица
							var tbody = document.querySelector('.nutrition-products-table tbody');
							if (tbody && tbody.children.length === 0) {
								location.reload();
							}
						}, 300);
					}
					alert('Продукт успешно удален!');
				} else {
					alert('Ошибка: ' + (response.data || 'Не удалось удалить продукт'));
				}
			});
		}
		</script>
<?php
	}
}
