<?php
error_log('taxonomy-nefret_etiketleri.php yüklendi'); // Debug
get_header();

$term = get_queried_object();
error_log('Etiket terimi: ' . print_r($term, true)); // Debug

if (!is_a($term, 'WP_Term') || $term->taxonomy !== 'nefret_etiketleri') {
    echo '<p class="nefret-uyari">Geçersiz etiket!</p>';
    error_log('Geçersiz etiket: ' . print_r($term, true)); // Debug
    get_footer();
    return;
}

global $wpdb;
$table_name = $wpdb->prefix . 'nefret_aciklamalar';

// Etikete ait açıklamaları çek
$aciklamalar = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT a.* FROM $table_name a 
        INNER JOIN wp_term_relationships tr ON a.id = tr.object_id 
        WHERE a.durum = 'onaylandi' AND tr.term_taxonomy_id = %d 
        ORDER BY a.nefret_sayisi DESC, a.id DESC",
        $term->term_taxonomy_id
    ),
    ARRAY_A
);

error_log('Etiket açıklamaları: ' . print_r($aciklamalar, true)); // Debug
?>
<div class="nefret-container nefret-oylama">
    <div class="nefret-tablo-wrapper">
        <h1 class="nefret-baslik"><?php echo esc_html($term->name); ?></h1>
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
                        error_log('Etiket açıklama ID: ' . $aciklama['id']); // Debug
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
                                    $link = get_term_link($kategori, 'nefret_kategorisi');
                                    echo '<a href="' . esc_url($link) . '">' . esc_html($kategori->name) . '</a>';
                                } else {
                                    echo 'Bilinmiyor';
                                }
                                ?>
                            </td>
                            <td class="oge-etiketler">
                                <?php
                                if (!is_wp_error($etiketler) && !empty($etiketler)) {
                                    $etiket_names = array_map(function($term) use ($wpdb, $table_name) {
                                        $link = get_term_link($term, 'nefret_etiketleri');
                                        // Etikete ait toplam nefret sayısını hesapla
                                        $nefret_sayisi = $wpdb->get_var(
                                            $wpdb->prepare(
                                                "SELECT SUM(a.nefret_sayisi) 
                                                FROM $table_name a 
                                                INNER JOIN wp_term_relationships tr ON a.id = tr.object_id 
                                                WHERE a.durum = 'onaylandi' AND tr.term_taxonomy_id = %d",
                                                $term->term_taxonomy_id
                                            )
                                        );
                                        return '<a href="' . esc_url($link) . '">' . esc_html($term->name) . ' (' . intval($nefret_sayisi) . ')</a>';
                                    }, $etiketler);
                                    echo implode(', ', $etiket_names);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($user ? $user->user_login : 'Bilinmeyen'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p class="nefret-uyari">Bu etikette henüz onaylanmış açıklama bulunmamaktadır.</p>
        <?php endif; ?>
    </div>
</div>
<?php
nefret_oylama_scriptleri();
get_footer();
