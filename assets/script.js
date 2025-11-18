document.addEventListener('DOMContentLoaded', function () {

	console.log('Nutrition Tracker loaded');

	const shouldLogout = sessionStorage.getItem('should_logout');
	if (shouldLogout) {
		console.log('🔄 Skipping auth due to recent logout');
		sessionStorage.removeItem('should_logout');
		sessionStorage.removeItem('telegram_auth_completed');
		return; // Пропускаем всю инициализацию
	}

	const nutritionTracker = document.querySelector('.nutrition-tracker');
	if (!nutritionTracker) {
		return;
	}

	if (document.querySelector('.nutrition-tabs')) {
		initTabs();
		initProductForm();
		initMealForm();
		initStats();
		initProfileForm();
		loadProducts();
	}

	function initTabs() {
		const tabButtons = document.querySelectorAll('.tab-button');

		tabButtons.forEach(button => {
			button.addEventListener('click', function (e) {
				e.preventDefault();
				const tab = this.dataset.tab;
				switchTab(tab);
			});
		});
	}

	function initProductForm() {
	    initProductPopup();
	}

	function initStats() {
		// Загружаем недельную статистику при выборе недели
		const weekSelector = document.getElementById('week-selector');
		if (weekSelector) {
			weekSelector.addEventListener('change', loadWeeklyStats);
		}
	}

	function initProfileForm() {
		const form = document.getElementById('profile-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			updateProfile();
		});
	}

	function ajaxRequest(data, successCallback, errorCallback) {
		var formData = new FormData();

		// Добавляем данные
		for (var key in data) {
			if (data.hasOwnProperty(key)) {
				formData.append(key, data[key]);
			}
		}

		fetch(nutrition_ajax.ajaxurl, {
			method: 'POST',
			body: formData
		})
			.then(function (response) {
				return response.json();
			})
			.then(successCallback)
			.catch(function (error) {
				console.error('AJAX Error:', error);
				if (errorCallback) errorCallback(error);
			});
	}

	function addProduct() {
		const form = document.getElementById('add-product-form');
		const formData = new FormData(form);

		const data = {
			action: 'add_product',
			nonce: nutrition_ajax.nonce
		};

		// Добавляем данные формы
		for (const [key, value] of formData.entries()) {
			data[key] = value;
		}

		ajaxRequest(data, function (response) {
			if (response.success) {
				showNotification('Продукт добавлен!', 'success');
				form.reset();
				loadProducts();
			} else {
				showNotification('Ошибка: ' + response.data, 'error');
			}
		});
	}

	function loadProducts() {
		const data = {
			action: 'get_products',
			nonce: nutrition_ajax.nonce
		};

		ajaxRequest(data, function (response) {
			const container = document.getElementById('products-list');
			if (!container) return;

			if (response.success) {
				const products = response.data;
				let html = '<div class="products-grid">';

				if (products.length > 0) {
					products.forEach(product => {
						html += `
									<div class="product-card">
										 <h4>${product.name}</h4>
										 <p data-value="${product.proteins}г">Белки: ${product.proteins}г</p>
										 <p data-value="${product.carbs}г">Углеводы: ${product.carbs}г</p>
										 <p data-value="${product.fats}г">Жиры: ${product.fats}г</p>
										 <p data-value="${product.calories}г">Калории: ${product.calories}</p>
									</div>
							  `;
					});
				} else {
					html += '<p>Пока нет добавленных продуктов</p>';
				}

				html += '</div>';
				container.innerHTML = html;
			} else {
				container.innerHTML = '<p>Ошибка при загрузке продуктов</p>';
			}
		});
	}

	function updateProfile() {
		const form = document.getElementById('profile-form');
		const formData = new FormData(form);

		formData.append('action', 'update_profile');
		formData.append('nonce', nutrition_ajax.nonce);

		fetch(nutrition_ajax.ajaxurl, {
			method: 'POST',
			body: formData
		})
			.then(response => response.json())
			.then(response => {
				if (response.success) {
					showNotification('Профиль обновлён!', 'success');
				} else {
					showNotification('Ошибка: ' + response.data, 'error');
				}
			})
			.catch(error => {
				console.error('Error updating profile:', error);
				showNotification('Ошибка при обновлении профиля', 'error');
			});
	}

	function loadProductsSelection() {
		const data = {
			action: 'get_products',
			nonce: nutrition_ajax.nonce
		};

		ajaxRequest(data, function (response) {
			const container = document.getElementById('products-selection');
			if (!container) return;

			if (response.success) {
				const products = response.data;
				let html = '';

				if (products.length > 0) {
					products.forEach(product => {
						const nutritionInfo = `Б: ${product.proteins}г, У: ${product.carbs}г, Ж: ${product.fats}г, К: ${product.calories}`;

						html += `
							  <div class="product-selection-item">
									<input type="checkbox" name="selected_products[]" value="${product.id}" 
											 data-proteins="${product.proteins}" 
											 data-carbs="${product.carbs}" 
											 data-fats="${product.fats}" 
											 data-calories="${product.calories}">
									<div class="product-name">${product.name}</div>
									<div class="product-grams">
										 <input type="number" name="grams_${product.id}" value="" min="0" max="1000" 
												  placeholder="Граммы" class="grams-input">
									</div>
							  </div>
						 `;
					});
				} else {
					html = '<p>У вас пока нет продуктов. Добавьте продукты во вкладке "Продукты".</p>';
				}

				container.innerHTML = html;

				// Добавляем обработчики для чекбоксов
				addCheckboxHandlers();
			} else {
				container.innerHTML = '<p>Ошибка при загрузке продуктов</p>';
			}
		});
	}

	function addCheckboxHandlers() {
		const checkboxes = document.querySelectorAll('input[name="selected_products[]"]');

		checkboxes.forEach(checkbox => {
			checkbox.addEventListener('change', function () {
				const item = this.closest('.product-selection-item');
				if (this.checked) {
					item.classList.add('selected');
				} else {
					item.classList.remove('selected');
				}
			});
		});

		// Добавляем обработчики для полей ввода граммов
		const gramInputs = document.querySelectorAll('.grams-input');
		gramInputs.forEach(input => {
			input.addEventListener('input', function () {
				if (this.value < 1) this.value = 1;
				if (this.value > 1000) this.value = 1000;
			});
		});
	}

	function addMeal() {
		const form = document.getElementById('add-meal-form');
		const formData = new FormData(form);

		// Получаем выбранные продукты
		const selectedProducts = [];
		const checkboxes = document.querySelectorAll('input[name="selected_products[]"]:checked');

		if (checkboxes.length === 0) {
			showNotification('Выберите хотя бы один продукт', 'error');
			return;
		}

		checkboxes.forEach(checkbox => {
			const productId = checkbox.value;
			const gramsInput = document.querySelector(`input[name="grams_${productId}"]`);
			const grams = gramsInput ? gramsInput.value : '100';

			selectedProducts.push({
				product_id: productId,
				grams: grams
			});
		});

		// Отправляем каждый продукт отдельным запросом
		let requestsCompleted = 0;
		let successfulRequests = 0;

		selectedProducts.forEach(product => {
			const data = {
				action: 'add_meal',
				nonce: nutrition_ajax.nonce,
				product_id: product.product_id,
				grams: product.grams,
				meal_type: formData.get('meal_type'),
				meal_date: formData.get('meal_date')
			};

			ajaxRequest(data, function (response) {
				requestsCompleted++;

				if (response.success) {
					successfulRequests++;
				}

				// Когда все запросы завершены
				if (requestsCompleted === selectedProducts.length) {
					if (successfulRequests === selectedProducts.length) {
						showNotification(`Все продукты (${successfulRequests}) успешно добавлены в прием пищи!`, 'success');
						form.reset();
						loadTodayMeals();

						// Сбрасываем чекбоксы
						document.querySelectorAll('input[name="selected_products[]"]').forEach(cb => {
							cb.checked = false;
							cb.closest('.product-selection-item').classList.remove('selected');
						});

						// Сбрасываем граммы на 100
						document.querySelectorAll('.grams-input').forEach(input => {
							input.value = '100';
						});
					} else {
						showNotification(`Добавлено ${successfulRequests} из ${selectedProducts.length} продуктов.`, 'success');
						loadTodayMeals();
					}
				}
			});
		});
	}

	function loadTodayMeals() {
		const dateInput = document.getElementById('view-meals-date');
		const date = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];

		// Обновляем отображаемую дату
		const currentDateElement = document.getElementById('current-date');
		if (currentDateElement) {
			const dateObj = new Date(date);
			currentDateElement.textContent = dateObj.toLocaleDateString('ru-RU');
		}

		const data = {
			action: 'get_today_meals',
			nonce: nutrition_ajax.nonce,
			date: date
		};

		ajaxRequest(data, function (response) {
			const container = document.getElementById('today-meals');
			if (!container) return;

			if (response.success) {
				const meals = response.data;

				if (meals.length > 0) {
					// Группируем по типам приемов пищи
					const mealsByType = {};
					meals.forEach(meal => {
						if (!mealsByType[meal.meal_type]) {
							mealsByType[meal.meal_type] = [];
						}
						mealsByType[meal.meal_type].push(meal);
					});

					let html = '';
					const mealTypes = {
						'breakfast': 'Завтрак',
						'lunch': 'Обед',
						'dinner': 'Ужин',
						'snack': 'Перекус'
					};

					for (const [mealType, typeMeals] of Object.entries(mealsByType)) {
						const mealTypeName = mealTypes[mealType] || mealType;

						html += `
							  <div class="meals-by-type">
									<div class="meal-type-title">${mealTypeName}</div>
						 `;

						typeMeals.forEach(meal => {
							const proteins = (meal.proteins * meal.grams / 100).toFixed(1);
							const carbs = (meal.carbs * meal.grams / 100).toFixed(1);
							const fats = (meal.fats * meal.grams / 100).toFixed(1);
							const calories = (meal.calories * meal.grams / 100).toFixed(1);

							html += `
									<div class="meal-item">
										 <div class="meal-item-info">
											  <div class="meal-item-name">${meal.name}</div>
											  <div class="meal-item-details">
													Белки: ${proteins}г | Углеводы: ${carbs}г | Жиры: ${fats}г <br>Калории: ${calories}
											  </div>
										 </div>
										 <div class="meal-item-grams">${meal.grams}г</div>
									</div>
							  `;
						});

						html += `</div>`;
					}

					container.innerHTML = html;
				} else {
					container.innerHTML = '<div class="no-meals">Нет приемов пищи за выбранную дату</div>';
				}
			} else {
				container.innerHTML = '<div class="no-meals">Ошибка при загрузке приемов пищи</div>';
			}
		});
	}

	function initMealForm() {
		const form = document.getElementById('add-meal-form');
		if (!form) return;

		form.addEventListener('submit', function (e) {
			e.preventDefault();
			addMeal();
		});

		// Загружаем список продуктов при инициализации
		loadProductsSelection();

		// Обработчик для кнопки загрузки приемов пищи
		const loadMealsBtn = document.getElementById('load-meals-btn');
		if (loadMealsBtn) {
			loadMealsBtn.addEventListener('click', loadTodayMeals);
		}

		// Обработчик изменения даты для просмотра
		const viewDateInput = document.getElementById('view-meals-date');
		if (viewDateInput) {
			viewDateInput.addEventListener('change', loadTodayMeals);
		}
	}

	function switchTab(tabName) {
		// Убираем активные классы
		document.querySelectorAll('.tab-button').forEach(btn => {
			btn.classList.remove('active');
		});
		document.querySelectorAll('.tab-content').forEach(content => {
			content.classList.remove('active');
		});

		// Добавляем активные классы
		const activeButton = document.querySelector(`[data-tab="${tabName}"]`);
		const activeContent = document.getElementById(`${tabName}-tab`);

		if (activeButton) activeButton.classList.add('active');
		if (activeContent) activeContent.classList.add('active');

		// Загружаем данные для вкладки
		switch (tabName) {
			case 'products':
				loadProducts();
				break;
			case 'meals':
				loadProductsSelection(); // Заменяем на новую функцию
				loadTodayMeals();
				break;
			case 'statistics':
				// loadDailyStats();
				loadWeeklyStats();
				break;
		}
	}

	function loadWeeklyStats() {
		const weekSelector = document.getElementById('week-selector');
		const weekValue = weekSelector ? weekSelector.value : getCurrentWeek();

		const data = {
			action: 'get_weekly_stats',
			nonce: nutrition_ajax.nonce,
			week: weekValue
		};

		ajaxRequest(data, function (response) {
			if (response.success) {
				renderWeeklyStats(response.data);
			} else {
				showNotification('Ошибка: ' + response.data, 'error');
			}
		});
	}

	function renderWeeklyStats(weekData) {
		renderWeeklyChart(weekData.days);
		renderWeeklyDetails(weekData.days);
		renderWeeklySummary(weekData.summary);
	}

	function renderWeeklyChart(days) {
		const container = document.getElementById('weekly-calories-chart');
		if (!container) return;

		let html = '';
		const dayNames = ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'];

		// Находим максимальное значение для масштабирования
		const maxCalories = Math.max(...days.map(day => Math.max(day.calories, day.calorie_goal || 0))) * 1.1;

		days.forEach((day, index) => {
			const dayName = dayNames[index];
			const actualHeight = (day.calories / maxCalories) * 100;
			const goalHeight = ((day.calorie_goal || 0) / maxCalories) * 100;

			html += `
			  <div class="chart-bar-container">
					<div class="chart-day-label">${dayName}<br>${day.date.split('-')[2]}</div>
					<div class="chart-bars">
						 <div class="chart-bar actual" style="height: ${actualHeight}%"></div>
						 <div class="chart-bar goal" style="height: ${goalHeight}%"></div>
					</div>
					<div class="chart-value">${Math.round(day.calories)}</div>
			  </div>
		 `;
		});

		container.innerHTML = html;
	}

	function renderWeeklyDetails(days) {
		const container = document.getElementById('weekly-stats-details');
		if (!container) return;

		let html = '';
		const dayNames = ['Понедельник', 'Вторник', 'Среда', 'Четверг', 'Пятница', 'Суббота', 'Воскресенье'];

		days.forEach((day, index) => {
			const dayName = dayNames[index];
			const dateObj = new Date(day.date);
			const dateFormatted = dateObj.toLocaleDateString('ru-RU');

			const progressClass = day.calories <= (day.calorie_goal || 0) ? 'good' : 'exceeded';

			html += `
			  <div class="day-stats-card">
					<div class="day-stats-header">
						 <div class="day-date">${dayName} (${dateFormatted})</div>
						 <div class="day-total-calories ${progressClass}">${Math.round(day.calories)} ккал</div>
					</div>
					<div class="day-nutrition">
						 <div class="nutrition-item">
							  <span>Белки:</span>
							  <span class="nutrition-value">${day.proteins.toFixed(1)}г</span>
						 </div>
						 <div class="nutrition-item">
							  <span>Углеводы:</span>
							  <span class="nutrition-value">${day.carbs.toFixed(1)}г</span>
						 </div>
						 <div class="nutrition-item">
							  <span>Жиры:</span>
							  <span class="nutrition-value">${day.fats.toFixed(1)}г</span>
						 </div>
						 <div class="nutrition-item">
							  <span>Цель:</span>
							  <span class="nutrition-value">${day.calorie_goal ? Math.round(day.calorie_goal) + ' ккал' : 'Не задана'}</span>
						 </div>
					</div>
			  </div>
		 `;
		});

		container.innerHTML = html;
	}

	function renderWeeklySummary(summary) {
		const container = document.getElementById('weekly-summary');
		if (!container) return;

		const avgCalories = Math.round(summary.total_calories / 7);
		const goalCompletion = summary.calorie_goal ? Math.round((summary.total_calories / (summary.calorie_goal * 7)) * 100) : 0;

		html = `
		 <h5>Сводка за неделю</h5>
		 <div class="summary-stats">
			  <div class="summary-stat">
					<div class="summary-value">${Math.round(summary.total_calories)}</div>
					<div class="summary-label">Всего калорий</div>
			  </div>
			  <div class="summary-stat">
					<div class="summary-value">${avgCalories}</div>
					<div class="summary-label">Среднее в день</div>
			  </div>
			  <div class="summary-stat">
					<div class="summary-value">${Math.round(summary.total_proteins)}г</div>
					<div class="summary-label">Всего белков</div>
			  </div>
			  <div class="summary-stat">
					<div class="summary-value">${Math.round(summary.total_carbs)}г</div>
					<div class="summary-label">Всего углеводов</div>
			  </div>
			  <div class="summary-stat">
					<div class="summary-value">${Math.round(summary.total_fats)}г</div>
					<div class="summary-label">Всего жиров</div>
			  </div>
			  ${summary.calorie_goal ? `
			  <div class="summary-stat">
					<div class="summary-value ${goalCompletion <= 100 ? 'good' : 'exceeded'}">${goalCompletion}%</div>
					<div class="summary-label">Выполнение цели</div>
			  </div>
			  ` : ''}
		 </div>
	`;

		container.innerHTML = html;
	}

	function getCurrentWeek() {
		const now = new Date();
		const year = now.getFullYear();
		const firstDay = new Date(year, 0, 1);
		const days = Math.floor((now - firstDay) / (24 * 60 * 60 * 1000));
		const week = Math.ceil((days + firstDay.getDay() + 1) / 7);
		return `${year}-W${week.toString().padStart(2, '0')}`;
	}

	function logoutUser() {
		const data = {
			action: 'logout_user',
			nonce: nutrition_ajax.nonce
		};

		if (confirm('Вы уверены, что хотите выйти?')) {
			ajaxRequest(data, function (response) {
				if (response.success) {
					location.reload();
				}
			});
		}
	}

	const logoutBtn = document.getElementById('logout-btn');
	if (logoutBtn) {
		logoutBtn.addEventListener('click', function (e) {
			e.preventDefault();
			logoutUser();
		});
	}

	let isAuthenticated = false;
	let authInProgress = false;

	function initTelegramAuth() {
		console.log('🔐 Starting Telegram auto-auth...');

		// Проверяем, не авторизуемся ли мы уже
		if (authInProgress) {
			console.log('Auth already in progress, skipping...');
			return;
		}

		if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
			console.error('Telegram WebApp not detected');
			showAuthError('Это приложение работает только в Telegram');
			return;
		}

		const tg = Telegram.WebApp;
		const tgUser = tg.initDataUnsafe.user;

		console.log('Telegram WebApp detected:', tg);
		console.log('Telegram user data:', tgUser);

		if (!tgUser || !tgUser.id) {
			console.error('No Telegram user ID available');
			showAuthError('Не удалось получить данные пользователя Telegram');
			return;
		}

		// Проверяем, не авторизованы ли мы уже
		if (isAuthenticated) {
			console.log('✅ Already authenticated, skipping auth');
			return;
		}

		authInProgress = true;

		// Отправляем только основные данные на сервер
		const userData = {
			telegram_id: tgUser.id,
			first_name: tgUser.first_name || 'User',
			last_name: tgUser.last_name || '',
			username: tgUser.username || ''
		};

		console.log('Sending to server:', userData);

		// Отправляем запрос на авторизацию
		fetch(nutrition_ajax.ajaxurl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/x-www-form-urlencoded',
			},
			body: new URLSearchParams({
				'action': 'telegram_auto_auth',
				'telegram_id': userData.telegram_id,
				'first_name': userData.first_name,
				'last_name': userData.last_name,
				'username': userData.username,
				'nonce': nutrition_ajax.nonce
			})
		})
			.then(response => {
				console.log('Server response status:', response.status);
				if (!response.ok) {
					throw new Error('Network response was not ok');
				}
				return response.json();
			})
			.then(data => {
				console.log('Server response data:', data);
				authInProgress = false;

				if (data.success) {
					console.log('✅ Auth successful:', data.data.message);
					isAuthenticated = true;
					updateAuthStatus('✅ ' + data.data.message);

					// Перезагружаем страницу только если это первый вход
					if (!sessionStorage.getItem('telegram_auth_completed')) {
						sessionStorage.setItem('telegram_auth_completed', 'true');
						console.log('Reloading page to apply auth...');
						setTimeout(() => {
							window.location.reload();
						}, 1000);
					}
				} else {
					console.error('❌ Auth failed:', data.data);
					showAuthError('Ошибка авторизации: ' + data.data);
				}
			})
			.catch(error => {
				console.error('❌ Network error:', error);
				authInProgress = false;
				showAuthError('Ошибка сети: ' + error.message);
			});
	}

	function initTelegramLogout() {
		if (typeof Telegram === 'undefined' || !Telegram.WebApp) {
			console.log('Telegram WebApp not available');
			return;
		}

		const tg = Telegram.WebApp;

		// Флаг чтобы избежать множественных вызовов
		let logoutTriggered = false;

		function safeLogout(source) {
			if (logoutTriggered) {
				console.log('Logout already triggered, skipping...');
				return;
			}

			logoutTriggered = true;
			console.log(`🔄 App is closing (${source}), initiating logout...`);
			handleAppClose();
		}

		// 1. Обработчик закрытия Mini App - только при полном закрытии
		tg.onEvent('close', () => {
			safeLogout('close_event');
		});

		// 2. Обработчик изменения размера - только если приложение полностью скрыто
		tg.onEvent('viewportChanged', (data) => {
			// Логируем только при полном скрытии, не при обычном сворачивании
			if (data.is_state_stable && !data.is_expanded) {
				safeLogout('viewport_changed');
			}
		});

		// 3. УБИРАЕМ обработчик visibilitychange - он вызывает проблемы
		// 4. УБИРАЕМ обработчик beforeunload - он вызывает проблемы
	}

	function handleAppClose() {
		// Устанавливаем флаг в sessionStorage что нужно выйти
		sessionStorage.setItem('should_logout', 'true');

		const data = new URLSearchParams({
			'action': 'telegram_logout',
			'nonce': nutrition_ajax.nonce,
			'close_event': 'true'
		});

		if (navigator.sendBeacon) {
			navigator.sendBeacon(nutrition_ajax.ajaxurl, data);
			console.log('✅ Logout request sent via Beacon');
		} else {
			const xhr = new XMLHttpRequest();
			xhr.open('POST', nutrition_ajax.ajaxurl, false);
			xhr.send(data);
			console.log('✅ Logout request sent via sync XHR');
		}
	}

	function updateAuthStatus(message) {
		const statusElement = document.getElementById('auth-status');
		if (statusElement) {
			statusElement.textContent = message;
		}
		console.log('Auth Status:', message);
	}

	function showAuthError(message) {
		updateAuthStatus('❌ ' + message);
		const spinner = document.querySelector('.spinner');
		if (spinner) {
			spinner.style.display = 'none';
		}

		// Показываем кнопку повтора
		const loadingElement = document.querySelector('.telegram-auth-loading');
		if (loadingElement) {
			const retryButton = document.createElement('button');
			retryButton.textContent = 'Повторить';
			retryButton.style.marginTop = '20px';
			retryButton.style.padding = '10px 20px';
			retryButton.style.background = '#007cba';
			retryButton.style.color = 'white';
			retryButton.style.border = 'none';
			retryButton.style.borderRadius = '5px';
			retryButton.onclick = function () {
				window.location.reload();
			};
			loadingElement.appendChild(retryButton);
		}
	}

	initTelegramLogout();

	initTelegramAuth();

	function showNotification(message, type = 'success') {
		const notification = document.createElement('div');
		notification.className = `notification ${type}`;
		notification.textContent = message;
		notification.style.cssText = `
			position: fixed; top: 20px; right: 20px; 
			padding: 15px; border-radius: 5px; z-index: 10000;
			background: ${type === 'success' ? '#4CAF50' : '#f44336'};
			color: white;
			`;
		document.body.appendChild(notification);
		setTimeout(() => notification.remove(), 3000);
	}

// Функции для управления попапом продуктов
function initProductPopup() {
    const openBtn = document.getElementById('open-product-popup');
    const closeBtn = document.getElementById('close-product-popup');
    const cancelBtn = document.getElementById('cancel-product-form');
    const popup = document.getElementById('product-popup');
    const form = document.getElementById('add-product-form');

    // Открытие попапа
    if (openBtn) {
        openBtn.addEventListener('click', openProductPopup);
    }

    // Закрытие попапа
    if (closeBtn) {
        closeBtn.addEventListener('click', closeProductPopup);
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeProductPopup);
    }

    // Закрытие по клику на overlay
    if (popup) {
        popup.addEventListener('click', function(e) {
            if (e.target === popup) {
                closeProductPopup();
            }
        });
    }

    // Закрытие по ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && popup.classList.contains('active')) {
            closeProductPopup();
        }
    });

    // Обработка отправки формы
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            addProduct();
        });
    }
}

function openProductPopup() {
    const popup = document.getElementById('product-popup');
    const form = document.getElementById('add-product-form');
    
    if (popup && form) {
        popup.classList.add('active');
        // Очищаем форму при открытии
        form.reset();
        // Фокусируемся на первом поле
        const firstInput = form.querySelector('input[name="name"]');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }
}

function closeProductPopup() {
    const popup = document.getElementById('product-popup');
    if (popup) {
        popup.classList.remove('active');
    }
}

// Обновленная функция addProduct для работы с попапом
function addProduct() {
    const form = document.getElementById('add-product-form');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    if (!form) return;

    const formData = new FormData(form);

    // Валидация данных
    if (!validateProductForm(formData)) {
        return;
    }

    // Показываем индикатор загрузки
    submitBtn.textContent = 'Добавление...';
    submitBtn.disabled = true;

    const data = {
        action: 'add_product',
        nonce: nutrition_ajax.nonce
    };

    // Добавляем данные формы
    for (const [key, value] of formData.entries()) {
        data[key] = value;
    }

    ajaxRequest(data, function(response) {
        // Восстанавливаем кнопку
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;

        if (response.success) {
            showNotification('Продукт успешно добавлен!', 'success');
            form.reset();
            closeProductPopup();
            loadProducts(); // Перезагружаем список продуктов
        } else {
            showNotification('Ошибка: ' + response.data, 'error');
        }
    }, function(error) {
        // Восстанавливаем кнопку при ошибке
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        showNotification('Ошибка сети при добавлении продукта', 'error');
    });
}

// Функция валидации формы
function validateProductForm(formData) {
    const name = formData.get('name')?.toString().trim();
    const proteins = parseFloat(formData.get('proteins'));
    const carbs = parseFloat(formData.get('carbs'));
    const fats = parseFloat(formData.get('fats'));
    const calories = parseFloat(formData.get('calories'));

    if (!name || name.length < 2) {
        showNotification('Название продукта должно быть не менее 2 символов', 'error');
        return false;
    }

    if (isNaN(proteins) || proteins < 0) {
        showNotification('Белки должны быть положительным числом', 'error');
        return false;
    }

    if (isNaN(carbs) || carbs < 0) {
        showNotification('Углеводы должны быть положительным числом', 'error');
        return false;
    }

    if (isNaN(fats) || fats < 0) {
        showNotification('Жиры должны быть положительным числом', 'error');
        return false;
    }

    if (isNaN(calories) || calories < 0) {
        showNotification('Калории должны быть положительным числом', 'error');
        return false;
    }

    return true;
}

});