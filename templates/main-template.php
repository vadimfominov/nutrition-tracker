<div class="nutrition-tracker">
	<?php if (!is_user_logged_in()): ?>

		<div class="telegram-auth-loading">
			<div class="loading-spinner">
				<div id="auth-status">🔐 Автоматическая авторизация...</div>
				<div class="spinner"></div>
			</div>
		</div>

	<?php else: ?>

		<!-- Вкладка продуктов -->
		<div id="products-tab" class="tab-content active">
			<div class="products-header">
				<h3>Мои продукты</h3>
				<button type="button" class="btn-primary" id="open-product-popup">
					+ Добавить продукт
				</button>
			</div>

			<div id="products-list"></div>
		</div>

		<!-- Попап для добавления продукта -->
		<div id="product-popup" class="popup-overlay">
			<div class="popup-content">
				<div class="popup-header">
					<h3>Добавить новый продукт</h3>
					<button type="button" class="popup-close" id="close-product-popup">&times;</button>
				</div>

				<form id="add-product-form">
					<div class="form-group">
						<input type="text" name="name" required placeholder="Название продукта">
					</div>

					<div class="form-row">
						<div class="form-group">
							<input type="number" step="0.1" name="proteins" required placeholder="Белки (г)" min="0">
						</div>
						<div class="form-group">
							<input type="number" step="0.1" name="fats" required placeholder="Жиры (г)" min="0">
						</div>

					</div>

					<div class="form-row">
						<div class="form-group">
							<input type="number" step="0.1" name="carbs" required placeholder="Углеводы (г)" min="0">
						</div>
						<div class="form-group">
							<input type="number" step="0.1" name="calories" required placeholder="Калории" min="0">
						</div>
					</div>

					<div class="form-actions">
						<button type="submit" class="btn-primary">Добавить продукт</button>
					</div>
				</form>
			</div>
		</div>

		<!-- Вкладка приемов пищи -->
		<div id="meals-tab" class="tab-content">
			<div class="meals-header">
				<h3>Мои приемы пищи</h3>
				<button type="button" class="btn-primary" id="open-meals-popup">
					+ Добавить приём пищи
				</button>
			</div>

			<div id="meals-popup" class="popup-overlay">
				<div class="popup-content">
					<div class="popup-header">
						<h3>Добавить новый приём пищи</h3>
						<button type="button" class="popup-close" id="close-meals-popup">&times;</button>
					</div>

					<form id="add-meal-form">
						<!-- Шаг 1: Дата и тип приема пищи -->
						<div class="step step1 active">
							<div class="form-row">
								<div class="form-group">
									<label>Дата:</label>
									<input type="date" name="meal_date" value="<?php echo date('Y-m-d'); ?>" required>
								</div>

								<div class="form-group">
									<label>Тип приема пищи:</label>
									<div class="radio-group">
										<input type="radio" id="breakfast" name="meal_type" value="breakfast" class="radio-option" required>
										<label for="breakfast" class="radio-label">Завтрак</label>

										<input type="radio" id="snack1" name="meal_type" value="snack" class="radio-option">
										<label for="snack1" class="radio-label">Перекус</label>

										<input type="radio" id="lunch" name="meal_type" value="lunch" class="radio-option">
										<label for="lunch" class="radio-label">Обед</label>

										<input type="radio" id="dinner" name="meal_type" value="dinner" class="radio-option">
										<label for="dinner" class="radio-label">Ужин</label>

										<input type="radio" id="snack2" name="meal_type" value="snack" class="radio-option">
										<label for="snack2" class="radio-label">Перекус 19.30</label>
									</div>
								</div>
							</div>

							<div class="navigation-buttons">
								<button type="button" class="nav-btn next-btn">Далее</button>
							</div>
						</div>

						<!-- Шаг 2: Выбор продуктов -->
						<div class="step step2">
							<button type="button" class="nav-btn prev-btn">Назад</button>
							<h4>Выберите продукты:</h4>
							<div id="products-selection" class="products-selection">
								<!-- Список продуктов будет загружен здесь -->
								<p>Загрузка продуктов...</p>
							</div>

							<div class="navigation-buttons">

								<button type="submit" class="add-meal-btn">Добавить в прием пищи</button>
							</div>
						</div>
					</form>

				</div>
			</div>

			<div class="meals-filter">
				<h3 style="margin-top: 20px;">Приемы пищи за <span id="current-date"><?php echo date('d.m.Y'); ?></span></h3>
				<div class="form-group">
					<input type="date" id="view-meals-date" value="<?php echo date('Y-m-d'); ?>">
				</div>
			</div>

			<div id="today-meals"></div>
		</div>

		<!-- Вкладка статистики -->
		<div id="statistics-tab" class="tab-content">
			<h3>Статистика питания</h3>

			<!-- Статистика за неделю -->
			<div id="weekly-stats-content" class="stats-content">

				<!-- График калорий за неделю -->
				<div class="weekly-chart-container">
					<h5>Калории по дням недели</h5>
					<div class="chart-legend">
						<div class="legend-item">
							<span class="legend-color actual-color"></span>
							<span>Фактические калории</span>
						</div>
						<div class="legend-item">
							<span class="legend-color goal-color"></span>
							<span>Целевые калории</span>
						</div>
					</div>
					<div id="weekly-calories-chart" class="calories-chart"></div>
				</div>

				<div class="weekly-stats-form">
					<div class="week-selector-container">
						<div class="form-group">
							<label>Выберите неделю:</label>
							<input type="week" id="week-selector" value="<?php echo date('Y-\WW'); ?>">
						</div>
					</div>
				</div>

				<!-- Детальная статистика по дням -->
				<div id="weekly-stats-details" class="weekly-details"></div>

				<!-- Сводка за неделю -->
				<div id="weekly-summary" class="weekly-summary"></div>
			</div>
		</div>

		<!-- Вкладка профиля -->
		<div id="profile-tab" class="tab-content">
			<h3>Мой профиль</h3>
			<form id="profile-form">

				<div class="form-group">
					<label>Имя:</label>
					<input type="text" name="first_name" value="<?php echo wp_get_current_user()->first_name; ?>">
				</div>
				<div class="form-group">
					<label>Возраст:</label>
					<input type="number" name="age" value="<?php echo get_user_meta(get_current_user_id(), 'age', true); ?>">
				</div>
				<div class="form-group">
					<label>Вес (кг):</label>
					<input type="number" step="0.1" name="weight" value="<?php echo get_user_meta(get_current_user_id(), 'weight', true); ?>">
				</div>
				<button type="submit">Обновить профиль</button>
			</form>
		</div>

		<div class="nutrition-tabs">
			<button class="tab-button active" data-tab="products">Продукты</button>
			<button class="tab-button" data-tab="meals">Приемы пищи</button>
			<button class="tab-button" data-tab="statistics">Статистика</button>
			<button class="tab-button" data-tab="profile">Профиль</button>
		</div>

	<?php endif; ?>
</div>