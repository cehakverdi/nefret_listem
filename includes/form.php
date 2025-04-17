<?php
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode: Açıklama Ekleme Formu
add_shortcode('nefret_aciklama_ekle', 'nefret_aciklama_ekle_shortcode');
function nefret_aciklama_ekle_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Açıklama eklemek için <a href="' . wp_login_url(get_permalink()) . '">giriş yapmalısınız</a>.</p>';
    }

    ob_start();
    nefret_oylama_scriptleri();
    error_log('nefret_aciklama_ekle: Form yüklendi'); // Debug
    ?>
    <div class="nefret-container nefret-form-container">
        <h2>Açıklama Ekle</h2>
        <form id="nefret-aciklama-form" method="post">
            <div class="nefret-form-group">
                <label for="category_id">Kategori</label>
                <select name="category_id" id="category_id" required>
                    <option value="">Kategori Seçin</option>
                    <?php
                    $kategoriler = get_terms(['taxonomy' => 'nefret_kategorisi', 'hide_empty' => false]);
                    error_log('nefret_aciklama_ekle: Kategoriler: ' . print_r($kategoriler, true)); // Debug
                    if (!is_wp_error($kategoriler) && !empty($kategoriler)) {
                        foreach ($kategoriler as $kategori) {
                            echo '<option value="' . esc_attr($kategori->term_id) . '">' . esc_html($kategori->name) . '</option>';
                        }
                    } else {
                        echo '<option value="" disabled>Kategori bulunamadı. Lütfen yöneticiyle iletişime geçin.</option>';
                        error_log('nefret_aciklama_ekle: Kategori alınamadı: ' . (is_wp_error($kategoriler) ? $kategoriler->get_error_message() : 'Boş')); // Debug
                    }
                    ?>
                </select>
            </div>
            <div class="nefret-form-group">
                <label for="aciklama">Açıklama (max 200 karakter)</label>
                <textarea name="aciklama" id="aciklama" rows="3" maxlength="200" required></textarea>
            </div>
            <div class="nefret-form-group">
                <label for="etiketler">Etiketler (virgülle ayırın)</label>
                <input type="text" name="etiketler" id="etiketler" placeholder="örn: trafik,sinir">
                <div id="etiket-onerileri"></div>
            </div>
            <button type="submit" class="nefret-submit-button">Gönder</button>
        </form>
        <div id="nefret-message"></div>
    </div>
    <?php
    return ob_get_clean();
}