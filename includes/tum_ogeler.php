<?php
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode: Tüm Öğeler
add_shortcode('nefret_tum_ogeler', 'nefret_tum_ogeler_shortcode');
function nefret_tum_ogeler_shortcode() {
    error_log('nefret_tum_ogeler_shortcode: Shortcode çağrıldı, dosya sürümü: 2025-04-14-v5'); // Debug
    ob_start();
    nefret_oylama_scriptleri();
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';
    $aciklamalar = $wpdb->get_results(
        "SELECT * FROM $table_name WHERE durum = 'onaylandi' ORDER BY nefret_sayisi DESC, id DESC",
        ARRAY_A
    );
    error_log('nefret_tum_ogeler: Açıklamalar çekildi, toplam: ' . count($aciklamalar)); // Debug
    ?>
    <div class="nefret-container nefret-oylama">
        <div class="nefret-tablo-wrapper">
            <h1 class="nefret-baslik">En Beğenilenler</h1>
            <?php if (!empty($aciklamalar)) : ?>
                <table class="nefret-tablo nefret-tablo-genis">
                    <thead>
                        <tr>
                            <th>Nefret</th>
                            <th>Açıklama</th>
                            <th>Kategori</th>
                            <th>Etiketler</th>
                            <th>Yazar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($aciklamalar as $aciklama) :
                            $user = get_userdata($aciklama['user_id']);
                            $kategori = get_term($aciklama['category_id'], 'nefret_kategorisi');
                            $etiketler = wp_get_object_terms($aciklama['id'], 'nefret_etiketleri');
                            $user_vote = null;
                            if (is_user_logged_in()) {
                                $user_votes = get_user_meta(get_current_user_id(), 'nefret_oylari', true);
                                $user_vote = is_array($user_votes) && isset($user_votes[$aciklama['id']]) ? $user_votes[$aciklama['id']] : null;
                            }
                            error_log('nefret_tum_ogeler: Açıklama ID=' . $aciklama['id'] . ', kategori=' . ($kategori && !is_wp_error($kategori) ? $kategori->name : 'yok') . ', etiketler=' . (empty($etiketler) ? 'yok' : implode(',', wp_list_pluck($etiketler, 'name')))); // Debug
                            ?>
                            <tr class="nefret-oge" data-post-id="<?php echo esc_attr($aciklama['id']); ?>">
                                <td>
                                    <button class="oy-btn hate <?php echo ($user_vote === 'hate') ? 'active' : ''; ?>" 
                                            data-oy-tipi="hate" data-id="<?php echo esc_attr($aciklama['id']); ?>">
                                        😡 <span class="oy-sayisi"><?php echo intval($aciklama['nefret_sayisi']); ?></span>
                                    </button>
                                </td>
                                <td class="oge-aciklama"><?php echo esc_html($aciklama['aciklama']); ?></td>
                                <td class="oge-kategori">
                                    <?php
                                    if (!is_wp_error($kategori) && $kategori) {
                                        $kategori_link = get_term_link($kategori->term_id, 'nefret_kategorisi');
                                        if (!is_wp_error($kategori_link)) {
                                            echo '<a href="' . esc_url($kategori_link) . '">' . esc_html($kategori->name) . '</a>';
                                            error_log('nefret_tum_ogeler: Kategori linki, term_id=' . $kategori->term_id . ', link=' . $kategori_link); // Debug
                                        } else {
                                            echo esc_html($kategori->name);
                                            error_log('nefret_tum_ogeler: Kategori link hatası, term_id=' . $kategori->term_id); // Debug
                                        }
                                    } else {
                                        echo 'Bilinmiyor';
                                        error_log('nefret_tum_ogeler: Kategori yok, category_id=' . $aciklama['category_id']); // Debug
                                    }
                                    ?>
                                </td>
                                <td class="oge-etiketler">
                                    <?php
                                    if (!is_wp_error($etiketler) && !empty($etiketler)) {
                                        $etiket_outputs = [];
                                        foreach ($etiketler as $etiket) {
                                            $etiket_link = get_term_link($etiket->term_id, 'nefret_etiketleri');
                                            $nefret_sayisi = function_exists('nefret_get_etiket_nefret_sayisi') ? nefret_get_etiket_nefret_sayisi($etiket->term_taxonomy_id) : 0;
                                            if (!is_wp_error($etiket_link)) {
                                                $etiket_outputs[] = '<a href="' . esc_url($etiket_link) . '">' . esc_html($etiket->name) . ' (' . $nefret_sayisi . ')</a>';
                                                error_log('nefret_tum_ogeler: Etiket linki, term_id=' . $etiket->term_id . ', link=' . $etiket_link . ', nefret_sayisi=' . $nefret_sayisi); // Debug
                                            } else {
                                                $etiket_outputs[] = esc_html($etiket->name) . ' (' . $nefret_sayisi . ')';
                                                error_log('nefret_tum_ogeler: Etiket link hatası, term_id=' . $etiket->term_id); // Debug
                                            }
                                        }
                                        echo implode(', ', $etiket_outputs);
                                    } else {
                                        echo '-';
                                        error_log('nefret_tum_ogeler: Etiket yok, aciklama_id=' . $aciklama['id']); // Debug
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($user ? $user->user_login : 'Bilinmeyen'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="nefret-uyari">Henüz onaylanmış açıklama bulunmamaktadır.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    error_log('nefret_tum_ogeler: HTML çıktı oluşturuldu, dosya sürümü: 2025-04-14-v5'); // Debug
    return ob_get_clean();
}