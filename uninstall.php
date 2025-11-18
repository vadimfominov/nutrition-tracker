<?php
// Если uninstall.php не был вызван WordPress, выходим
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

global $wpdb;

// Удаляем созданные таблицы
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nutrition_products");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nutrition_meals");

// Удаляем метаданные пользователей
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ('age', 'weight', 'photo')");
