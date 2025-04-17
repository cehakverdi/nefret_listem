<?php
if (!defined('ABSPATH')) {
    exit;
}

// Eklenti Kurulumu
register_activation_hook(__FILE__, 'nefret_oylama_install');
function nefret_oylama_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        category_id bigint(20) NOT NULL,
        aciklama text NOT NULL,
        durum varchar(20) DEFAULT 'beklemede',
        nefret_sayisi bigint(20) DEFAULT 0,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Taksonomileri kaydet
    nefret_oylama_taksonomileri_kaydet();
    flush_rewrite_rules();
    error_log('nefret_oylama_install: Taksonomiler kaydedildi');
}

// Taksonomileri Kaydet
function nefret_oylama_taksonomileri_kaydet() {
    $labels_kategori = [
        'name' => 'Nefret Kategorileri',
        'singular_name' => 'Nefret Kategorisi',
        'search_items' => 'Kategorileri Ara',
        'all_items' => 'Tüm Kategoriler',
        'edit_item' => 'Kategoriyi Düzenle',
        'update_item' => 'Kategoriyi Güncelle',
        'add_new_item' => 'Yeni Kategori Ekle',
        'new_item_name' => 'Yeni Kategori Adı',
        'menu_name' => 'Nefret Kategorileri',
    ];

    register_taxonomy('nefret_kategorisi', 'nefret_aciklama', [
        'hierarchical' => true,
        'labels' => $labels_kategori,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'nefret-kategorisi', 'with_front' => false],
        'public' => true,
    ]);

    $labels_etiket = [
        'name' => 'Nefret Etiketleri',
        'singular_name' => 'Nefret Etiketi',
        'search_items' => 'Etiketleri Ara',
        'all_items' => 'Tüm Etiketler',
        'edit_item' => 'Etiketi Düzenle',
        'update_item' => 'Etiketi Güncelle',
        'add_new_item' => 'Yeni Etiket Ekle',
        'new_item_name' => 'Yeni Etiket Adı',
        'menu_name' => 'Nefret Etiketleri',
    ];

    register_taxonomy('nefret_etiketleri', 'nefret_aciklama', [
        'hierarchical' => false,
        'labels' => $labels_etiket,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'nefret-etiketi', 'with_front' => false],
        'public' => true,
    ]);

    error_log('nefret_oylama_taksonomileri_kaydet: Kategoriler ve etiketler kaydedildi');
}

// Taksonomileri init kancasında kaydet
add_action('init', 'nefret_oylama_taksonomileri_kaydet', 1);

// Custom Post Type
add_action('init', 'nefret_oylama_post_type');
function nefret_oylama_post_type() {
    register_post_type('nefret_aciklama', [
        'labels' => [
            'name' => 'Nefret Açıklamaları',
            'singular_name' => 'Nefret Açıklaması',
        ],
        'public' => true,
        'show_ui' => true,
        'supports' => ['title', 'author'],
        'taxonomies' => ['nefret_kategorisi', 'nefret_etiketleri'],
    ]);
}

// Şablonları Yükle
add_filter('template_include', 'nefret_oylama_template_include');
function nefret_oylama_template_include($template) {
    if (is_tax('nefret_kategorisi')) {
        $new_template = plugin_dir_path(__FILE__) . '../templates/taxonomy-nefret_kategorisi.php';
        if (file_exists($new_template)) {
            error_log('Kategori şablonu yüklendi: ' . $new_template);
            return $new_template;
        }
    }
    if (is_tax('nefret_etiketleri')) {
        $new_template = plugin_dir_path(__FILE__) . '../templates/taxonomy-nefret_etiketleri.php';
        if (file_exists($new_template)) {
            error_log('Etiket şablonu yüklendi: ' . $new_template);
            return $new_template;
        }
    }
    return $template;
}

// Script ve Stiller
function nefret_oylama_scriptleri() {
    wp_enqueue_style('nefret-oylama', plugin_dir_url(__FILE__) . '../css/nefret-oylama.css', [], '1.4');
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
    wp_enqueue_script('nefret-oylama', plugin_dir_url(__FILE__) . '../js/nefret-oylama.js', ['jquery'], '1.4', true);
    wp_localize_script('nefret-oylama', 'nefret_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('nefret_oylama_nonce'),
        'logged_in' => is_user_logged_in(),
        'login_url' => wp_login_url(get_permalink()),
    ]);
}

// Etikete ait toplam nefret sayısını getir (önbellekli)
function nefret_get_etiket_nefret_sayisi($term_taxonomy_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';
    $transient_key = 'nefret_etiket_nefret_' . $term_taxonomy_id;

    // Önbellekten kontrol et
    $nefret_sayisi = get_transient($transient_key);
    if ($nefret_sayisi === false) {
        $nefret_sayisi = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(a.nefret_sayisi) 
                FROM $table_name a 
                INNER JOIN wp_term_relationships tr ON a.id = tr.object_id 
                WHERE a.durum = 'onaylandi' AND tr.term_taxonomy_id = %d",
                $term_taxonomy_id
            )
        );
        // 1 saat önbelleğe al
        set_transient($transient_key, intval($nefret_sayisi), HOUR_IN_SECONDS);
        error_log('nefret_get_etiket_nefret_sayisi: term_taxonomy_id=' . $term_taxonomy_id . ', nefret_sayisi=' . $nefret_sayisi);
    }
    return intval($nefret_sayisi);
}

// AJAX İşleyicisi: Açıklama Kaydetme
add_action('wp_ajax_nefret_aciklama_kaydet', 'nefret_aciklama_kaydet_handler');
function nefret_aciklama_kaydet_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';

    check_ajax_referer('nefret_oylama_nonce', 'nonce');

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $aciklama = isset($_POST['aciklama']) ? sanitize_textarea_field($_POST['aciklama']) : '';
    $etiketler = isset($_POST['etiketler']) ? array_map('sanitize_text_field', array_filter(explode(',', $_POST['etiketler']))) : [];

    error_log('nefret_aciklama_kaydet: category_id=' . $category_id . ', aciklama=' . $aciklama);

    if ($category_id <= 0 || empty(trim($aciklama))) {
        wp_send_json_error(['message' => 'Kategori ve açıklama zorunlu!']);
    }

    if (strlen($aciklama) > 200) {
        wp_send_json_error(['message' => 'Açıklama 200 karakterden uzun olamaz!']);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Giriş yapmalısınız']);
    }

    $user_id = get_current_user_id();
    $post_id = wp_insert_post([
        'post_title' => 'Açıklama #' . time(),
        'post_type' => 'nefret_aciklama',
        'post_status' => 'publish',
        'post_author' => $user_id,
    ]);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Post oluşturulamadı!']);
    }

    $wpdb->insert($table_name, [
        'id' => $post_id,
        'user_id' => $user_id,
        'category_id' => $category_id,
        'aciklama' => $aciklama,
        'durum' => 'beklemede',
        'nefret_sayisi' => 0
    ]);

    if ($wpdb->insert_id || $wpdb->rows_affected) {
        wp_set_object_terms($post_id, [$category_id], 'nefret_kategorisi', false);
        $term_ids = [];
        foreach ($etiketler as $etiket) {
            $etiket = trim($etiket);
            if ($etiket) {
                $term = wp_insert_term($etiket, 'nefret_etiketleri', ['slug' => sanitize_title($etiket)]);
                if (!is_wp_error($term)) {
                    $term_ids[] = $term['term_id'];
                } else {
                    $term_ids[] = $term->get_error_data();
                }
            }
        }
        if ($term_ids) {
            wp_set_object_terms($post_id, $term_ids, 'nefret_etiketleri', false);
        }
        // Önbelleği temizle
        foreach ($term_ids as $term_id) {
            $term = get_term($term_id, 'nefret_etiketleri');
            if (!is_wp_error($term)) {
                delete_transient('nefret_etiket_nefret_' . $term->term_taxonomy_id);
            }
        }
        wp_send_json_success(['message' => 'Açıklama kaydedildi ve onay bekliyor!', 'aciklama' => $aciklama]);
    } else {
        wp_delete_post($post_id, true);
        wp_send_json_error(['message' => 'Açıklama kaydedilemedi!']);
    }
}

// AJAX İşleyicisi: Oylama
add_action('wp_ajax_nefret_oyla', 'nefret_oyla_handler');
add_action('wp_ajax_nopriv_nefret_oyla', 'nefret_oyla_handler');
function nefret_oyla_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';

    check_ajax_referer('nefret_oylama_nonce', 'nonce');

    $aciklama_id = intval($_POST['aciklama_id']);
    $vote_type = sanitize_text_field($_POST['vote_type']);
    $valid_vote_types = ['hate'];

    error_log('nefret_oyla: aciklama_id=' . $aciklama_id . ', vote_type=' . $vote_type);

    if (!$aciklama_id || !in_array($vote_type, $valid_vote_types)) {
        wp_send_json_error(['message' => 'Geçersiz veri']);
    }

    $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM $table_name WHERE id = %d", $aciklama_id));
    if (!$existing) {
        wp_send_json_error(['message' => 'Geçersiz açıklama ID!']);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Giriş yapmalısınız']);
    }

    $user_id = get_current_user_id();
    $user_votes = get_user_meta($user_id, 'nefret_oylari', true) ?: [];
    $user_hated_items = array_filter($user_votes, function($vote) {
        return $vote === 'hate';
    });

    if ($vote_type === 'hate' && !isset($user_votes[$aciklama_id]) && count($user_hated_items) >= 10) {
        wp_send_json_error(['message' => 'En fazla 10 açıklamadan nefret edebilirsiniz!']);
    }

    if (isset($user_votes[$aciklama_id]) && $user_votes[$aciklama_id] === 'hate') {
        wp_send_json_error(['message' => 'Bu açıklamadan zaten nefret ettiniz!']);
    }

    $wpdb->query("UPDATE $table_name SET nefret_sayisi = nefret_sayisi + 1 WHERE id = $aciklama_id");

    $user_votes[$aciklama_id] = $vote_type;
    update_user_meta($user_id, 'nefret_oylari', $user_votes);

    // Etiket önbelleğini temizle
    $etiketler = wp_get_object_terms($aciklama_id, 'nefret_etiketleri');
    foreach ($etiketler as $etiket) {
        delete_transient('nefret_etiket_nefret_' . $etiket->term_taxonomy_id);
    }

    $new_hate_count = $wpdb->get_var("SELECT nefret_sayisi FROM $table_name WHERE id = $aciklama_id");
    wp_send_json_success(['hate' => $new_hate_count]);
}

// AJAX İşleyicisi: Etiket Arama
add_action('wp_ajax_nefret_etiket_ara', 'nefret_etiket_ara_handler');
add_action('wp_ajax_nopriv_nefret_etiket_ara', 'nefret_etiket_ara_handler');
function nefret_etiket_ara_handler() {
    error_log('nefret_etiket_ara_handler çağrıldı, term: ' . $_POST['term']);
    check_ajax_referer('nefret_oylama_nonce', 'nonce');
    $term = sanitize_text_field($_POST['term']);
    $terms = get_terms([
        'taxonomy' => 'nefret_etiketleri',
        'hide_empty' => false,
        'name__like' => $term,
        'fields' => 'names',
    ]);
    error_log('nefret_etiket_ara_handler: terms=' . print_r($terms, true));
    if (!is_wp_error($terms)) {
        wp_send_json_success($terms);
    } else {
        error_log('nefret_etiket_ara_handler: Hata: ' . print_r($terms->get_error_message(), true));
        wp_send_json_error(['message' => 'Etiketler alınamadı']);
    }
}

// AJAX İşleyicisi: Açıklama Düzenleme
add_action('wp_ajax_nefret_aciklama_duzenle', 'nefret_aciklama_duzenle_handler');
function nefret_aciklama_duzenle_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';

    check_ajax_referer('nefret_oylama_nonce', 'nonce');

    $aciklama_id = intval($_POST['aciklama_id']);
    $category_id = intval($_POST['category_id']);
    $aciklama = sanitize_textarea_field($_POST['aciklama']);
    $etiketler = isset($_POST['etiketler']) ? array_map('sanitize_text_field', array_filter(explode(',', $_POST['etiketler']))) : [];

    if (!$aciklama_id || !$category_id || !$aciklama) {
        wp_send_json_error(['message' => 'Tüm alanlar zorunlu!']);
    }

    if (strlen($aciklama) > 200) {
        wp_send_json_error(['message' => 'Açıklama 200 karakterden uzun olamaz!']);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Giriş yapmalısınız']);
    }

    $user_id = get_current_user_id();
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $aciklama_id), ARRAY_A);
    if (!$existing || $existing['user_id'] != $user_id) {
        wp_send_json_error(['message' => 'Bu açıklamayı düzenleme yetkiniz yok!']);
    }

    $wpdb->update($table_name, [
        'category_id' => $category_id,
        'aciklama' => $aciklama,
        'durum' => 'beklemede'
    ], ['id' => $aciklama_id]);

    if (!$wpdb->last_error) {
        wp_set_object_terms($aciklama_id, [$category_id], 'nefret_kategorisi', false);
        $term_ids = [];
        foreach ($etiketler as $etiket) {
            $etiket = trim($etiket);
            if ($etiket) {
                $term = wp_insert_term($etiket, 'nefret_etiketleri', ['slug' => sanitize_title($etiket)]);
                if (!is_wp_error($term)) {
                    $term_ids[] = $term['term_id'];
                } else {
                    $term_ids[] = $term->get_error_data();
                }
            }
        }
        wp_set_object_terms($aciklama_id, $term_ids ?: [], 'nefret_etiketleri', false);
        // Önbelleği temizle
        foreach ($term_ids as $term_id) {
            $term = get_term($term_id, 'nefret_etiketleri');
            if (!is_wp_error($term)) {
                delete_transient('nefret_etiket_nefret_' . $term->term_taxonomy_id);
            }
        }
        wp_send_json_success(['message' => 'Açıklama güncellendi ve onay bekliyor!']);
    } else {
        wp_send_json_error(['message' => 'Açıklama güncellenemedi: ' . $wpdb->last_error]);
    }
}

// AJAX İşleyicisi: Açıklama Silme
add_action('wp_ajax_nefret_aciklama_sil', 'nefret_aciklama_sil_handler');
function nefret_aciklama_sil_handler() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';

    check_ajax_referer('nefret_oylama_nonce', 'nonce');

    $aciklama_id = intval($_POST['aciklama_id']);
    if (!$aciklama_id) {
        wp_send_json_error(['message' => 'Geçersiz açıklama ID!']);
    }

    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Giriş yapmalısınız']);
    }

    $user_id = get_current_user_id();
    $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $aciklama_id), ARRAY_A);
    if (!$existing || $existing['user_id'] != $user_id) {
        wp_send_json_error(['message' => 'Bu açıklamayı silme yetkiniz yok!']);
    }

    $wpdb->delete($table_name, ['id' => $aciklama_id]);
    wp_delete_post($aciklama_id, true);

    // Etiket önbelleğini temizle
    $etiketler = wp_get_object_terms($aciklama_id, 'nefret_etiketleri');
    foreach ($etiketler as $etiket) {
        delete_transient('nefret_etiket_nefret_' . $etiket->term_taxonomy_id);
    }

    if (!$wpdb->last_error) {
        wp_send_json_success(['message' => 'Açıklama silindi!']);
    } else {
        wp_send_json_error(['message' => 'Açıklama silinemedi: ' . $wpdb->last_error]);
    }
}


// Mevcut core.php içeriği korunacak, sadece aşağıdaki eklenir
// Test Nefret Shortcode
add_shortcode('nefret_tum_ogeler_test', 'nefret_tum_ogeler_test_shortcode');
function nefret_tum_ogeler_test_shortcode() {
    error_log('nefret_tum_ogeler_test_shortcode: Test shortcode çağrıldı, tarih: ' . date('Y-m-d H:i:s')); // Debug
    return '<p>Test Shortcode Nefret Öğeleri: ' . date('Y-m-d H:i:s') . '</p>' . do_shortcode('[nefret_tum_ogeler]');
}
