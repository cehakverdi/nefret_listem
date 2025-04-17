<?php
if (!defined('ABSPATH')) {
    exit;
}

// Shortcode: Nefret Açıklamaları
add_shortcode('nefret_oylama', 'nefret_oylama_shortcode');
function nefret_oylama_shortcode($atts) {
    ob_start();
    nefret_oylama_scriptleri();
    ?>
    <div class="nefret-container">
        <h2>Nefret Açıklamaları</h2>
        <div id="nefret-aciklama-listesi">
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'nefret_aciklamalar';
            $aciklamalar = $wpdb->get_results("SELECT * FROM $table_name WHERE durum = 'onaylandi' ORDER BY nefret_sayisi DESC, id DESC", ARRAY_A);
            if (!empty($aciklamalar)) {
                foreach ($aciklamalar as $aciklama) {
                    $user = get_userdata($aciklama['user_id']);
                    $kategori = taxonomy_exists('nefret_kategorisi') ? get_term($aciklama['category_id'], 'nefret_kategorisi') : null;
                    $etiketler = taxonomy_exists('nefret_etiketleri') ? wp_get_object_terms($aciklama['id'], 'nefret_etiketleri') : [];
                    error_log('nefret_oylama: aciklama_id=' . $aciklama['id'] . ', kategori=' . print_r($kategori, true) . ', etiketler=' . print_r($etiketler, true)); // Debug
                    ?>
                    <div class="nefret-item" data-id="<?php echo esc_attr($aciklama['id']); ?>">
                        <div class="nefret-content">
                            <p><strong><?php echo esc_html($user ? $user->user_login : 'Bilinmeyen Kullanıcı'); ?>:</strong>
                            <?php echo esc_html($aciklama['aciklama']); ?></p>
                            <p><small>Kategori: <?php echo is_wp_error($kategori) || !$kategori ? 'Bilinmiyor' : esc_html($kategori->name); ?></small></p>
                            <p><small>Etiketler: <?php
                                if (!is_wp_error($etiketler) && !empty($etiketler)) {
                                    $etiket_names = array_map(function($term) { return esc_html($term->name); }, $etiketler);
                                    echo implode(', ', $etiket_names);
                                } else {
                                    echo '-';
                                }
                            ?></small></p>
                        </div>
                        <div class="nefret-vote">
                            <button class="vote-button hate-button" data-id="<?php echo esc_attr($aciklama['id']); ?>" data-type="hate">
                                Nefret Et (<?php echo intval($aciklama['nefret_sayisi']); ?>)
                            </button>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<p>Henüz onaylanmış açıklama yok.</p>';
            }
            ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Shortcode: Tüm Nefret Öğeleri
add_shortcode('nefret_tum_ogeler', 'nefret_tum_ogeler_shortcode');
function nefret_tum_ogeler_shortcode() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';
    $aciklamalar = $wpdb->get_results("SELECT * FROM $table_name WHERE durum = 'onaylandi' ORDER BY nefret_sayisi DESC, id DESC", ARRAY_A);

    ob_start();
    nefret_oylama_scriptleri();
    ?>
    <div class="nefret-container nefret-oylama">
        <div class="nefret-tablo-wrapper">
            <h1 class="nefret-baslik">Tüm Nefret Öğeleri</h1>
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
                        <?php 
                        foreach ($aciklamalar as $aciklama) {
                            $user = get_userdata($aciklama['user_id']);
                            $kategori = taxonomy_exists('nefret_kategorisi') ? get_term($aciklama['category_id'], 'nefret_kategorisi') : null;
                            $etiketler = taxonomy_exists('nefret_etiketleri') ? wp_get_object_terms($aciklama['id'], 'nefret_etiketleri') : [];
                            error_log('nefret_tum_ogeler: aciklama_id=' . $aciklama['id'] . ', kategori=' . print_r($kategori, true) . ', etiketler=' . print_r($etiketler, true)); // Debug
                            $user_vote = null;
                            if (is_user_logged_in()) {
                                $user_votes = get_user_meta(get_current_user_id(), 'nefret_oylari', true);
                                $user_vote = is_array($user_votes) && isset($user_votes[$aciklama['id']]) ? $user_votes[$aciklama['id']] : null;
                            }
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
                                    <?php echo is_wp_error($kategori) || !$kategori ? 'Bilinmiyor' : esc_html($kategori->name); ?>
                                </td>
                                <td class="oge-etiketler">
                                    <?php
                                    if (!is_wp_error($etiketler) && !empty($etiketler)) {
                                        $etiket_names = array_map(function($term) { return esc_html($term->name); }, $etiketler);
                                        echo implode(', ', $etiket_names);
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo esc_html($user ? $user->user_login : 'Bilinmeyen'); ?></td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="nefret-uyari">Henüz onaylanmış açıklama bulunmamaktadır.</p>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}