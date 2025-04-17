<?php
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode: Kullanıcı Profili
add_shortcode('nefret_profil', 'nefret_profil_shortcode');
function nefret_profil_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Açıklama eklemek ve düzenlemek için <a href="' . wp_login_url(get_permalink()) . '">giriş yapmalısınız</a>.</p>';
    }

    ob_start();
    nefret_oylama_scriptleri();
    $user_id = get_current_user_id();
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';
    $aciklamalar = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE user_id = %d ORDER BY id DESC", $user_id), ARRAY_A);
    ?>
    <div class="nefret-container">
        <h2>Açıklamalarım</h2>
        <?php if (!empty($aciklamalar)) : ?>
            <table class="nefret-profile-table">
                <thead>
                    <tr>
                        <th>Açıklama</th>
                        <th>Kategori</th>
                        <th>Etiketler</th>
                        <th>Durum</th>
                        <th>İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aciklamalar as $aciklama) :
                        $kategori = taxonomy_exists('nefret_kategorisi') ? get_term($aciklama['category_id'], 'nefret_kategorisi') : null;
                        $etiketler = taxonomy_exists('nefret_etiketleri') ? wp_get_object_terms($aciklama['id'], 'nefret_etiketleri') : [];
                        error_log('nefret_profil: aciklama_id=' . $aciklama['id'] . ', kategori=' . print_r($kategori, true) . ', etiketler=' . print_r($etiketler, true)); // Debug
                    ?>
                        <tr data-id="<?php echo esc_attr($aciklama['id']); ?>">
                            <td><?php echo esc_html($aciklama['aciklama']); ?></td>
                            <td><?php echo is_wp_error($kategori) || !$kategori ? 'Bilinmiyor' : esc_html($kategori->name); ?></td>
                            <td>
                                <?php
                                if (!is_wp_error($etiketler) && !empty($etiketler)) {
                                    $etiket_names = array_map(function($term) {
                                        $nefret_sayisi = nefret_get_etiket_nefret_sayisi($term->term_taxonomy_id);
                                        return esc_html($term->name) . ' (' . $nefret_sayisi . ')';
                                    }, $etiketler);
                                    echo implode(', ', $etiket_names);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($aciklama['durum']); ?></td>
                            <td>
                                <div class="nefret-actions">
                                    <button class="nefret-edit-button" data-id="<?php echo esc_attr($aciklama['id']); ?>" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="nefret-delete-button" data-id="<?php echo esc_attr($aciklama['id']); ?>" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>Henüz açıklamanız yok.</p>
        <?php endif; ?>

        <!-- Düzenleme Formu -->
        <div id="nefret-duzenle-form" style="display: none; margin-top: 20px;">
            <h3>Açıklamayı Düzenle</h3>
            <form id="nefret-duzenle-aciklama-form" method="post">
                <input type="hidden" name="duzenle_aciklama_id" id="duzenle_aciklama_id">
                <div class="nefret-form-group">
                    <label for="duzenle_category_id">Kategori</label>
                    <select name="duzenle_category_id" id="duzenle_category_id" required>
                        <option value="">Kategori Seçin</option>
                        <?php
                        $kategoriler = taxonomy_exists('nefret_kategorisi') ? get_terms(['taxonomy' => 'nefret_kategorisi', 'hide_empty' => false]) : [];
                        error_log('nefret_profil: Düzenleme kategoriler: ' . print_r($kategoriler, true)); // Debug
                        if (!is_wp_error($kategoriler) && !empty($kategoriler)) {
                            foreach ($kategoriler as $kategori) {
                                echo '<option value="' . esc_attr($kategori->term_id) . '">' . esc_html($kategori->name) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>Kategori bulunamadı. Lütfen yöneticiyle iletişime geçin.</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="nefret-form-group">
                    <label for="duzenle_aciklama">Açıklama (max 200 karakter)</label>
                    <textarea name="duzenle_aciklama" id="duzenle_aciklama" rows="3" maxlength="200" required></textarea>
                </div>
                <div class="nefret-form-group">
                    <label for="duzenle_etiketler">Etiketler (virgülle ayırın)</label>
                    <input type="text" name="duzenle_etiketler" id="duzenle_etiketler" placeholder="örn: trafik,sinir">
                    <div id="duzenle_etiket-onerileri"></div>
                </div>
                <button type="submit" class="nefret-submit-button">Kaydet</button>
                <button type="button" class="nefret-cancel-button">İptal</button>
            </form>
            <div id="nefret-duzenle-message"></div>
        </div>

        <!-- Yeni Açıklama Ekleme Formu -->
        <div class="nefret-yeni-aciklama" style="margin-top: 40px;">
            <?php echo do_shortcode('[nefret_aciklama_ekle]'); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}