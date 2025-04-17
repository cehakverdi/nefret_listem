<?php
/*
Plugin Name: Nefret Oylama
Description: Nefret Öğeleri ve özel Nefret Kategorileri için oylama sistemi
Version: 2.13
Author: xAI & DeepSeek
*/

if (!defined('ABSPATH')) {
    exit;
}

// Dosyaları dahil et
require_once plugin_dir_path(__FILE__) . 'includes/core.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/profile.php';
require_once plugin_dir_path(__FILE__) . 'includes/form.php';
