<?php
if (!defined('ABSPATH')) {
    exit;
}

// Admin Panel Menüsü
add_action('admin_menu', 'nefret_aciklama_admin_menu');
function nefret_aciklama_admin_menu() {
    // Ana menü: Nefret Açıklamaları
    add_menu_page(
        'Nefret Açıklamaları',
        'Nefret Açıklamaları',
        'manage_options',
        'nefret-aciklamalari',
        'nefret_aciklama_admin_page',
        'dashicons-thumbs-down',
        6
    );

    // Alt menü: Kategoriler
    add_submenu_page(
        'nefret-aciklamalari',
        'Nefret Kategorileri',
        'Kategoriler',
        'manage_options',
        'nefret-kategorileri',
        'nefret_kategori_yonetim_page'
    );

    // Alt menü: Etiketler
    add_submenu_page(
        'nefret-aciklamalari',
        'Nefret Etiketleri',
        'Etiketler',
        'manage_options',
        'nefret-etiketleri',
        'nefret_etiket_yonetim_page'
    );
}

// Ana Açıklama Sayfası
function nefret_aciklama_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'nefret_aciklamalar';

    // Onaylama, Reddetme, Silme ve Düzenleme İşlemleri
    if (isset($_GET['action']) && isset($_GET['id']) && current_user_can('manage_options')) {
        $id = intval($_GET['id']);
        $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

        if ($_GET['action'] === 'onayla') {
            $wpdb->update($table_name, ['durum' => 'onaylandi'], ['id' => $id]);
            $message = $wpdb->last_error ? 'Veritabanı hatası: ' . $wpdb->last_error : 'Açıklama onaylandı';
            $status = $wpdb->last_error ? 'error' : 'updated';
        } elseif ($_GET['action'] === 'reddet') {
            $wpdb->update($table_name, ['durum' => 'reddedildi'], ['id' => $id]);
            $message = $wpdb->last_error ? 'Veritabanı hatası: ' . $wpdb->last_error : 'Açıklama reddedildi';
            $status = $wpdb->last_error ? 'error' : 'updated';
        } elseif ($_GET['action'] === 'sil') {
            $wpdb->delete($table_name, ['id' => $id]);
            wp_delete_post($id, true);
            $message = $wpdb->last_error ? 'Veritabanı hatası: ' . $wpdb->last_error : 'Açıklama silindi';
            $status = $wpdb->last_error ? 'error' : 'updated';
        }

        if (isset($message)) {
            echo '<div class="' . $status . '"><p>' . esc_html($message) . '</p></div>';
        }
    }

    // Admin Açıklama Ekleme
    if (isset($_POST['nefret_aciklama_ekle']) && current_user_can('manage_options')) {
        $category_id = intval($_POST['category_id']);
        $aciklama = sanitize_textarea_field($_POST['aciklama']);
        $etiketler = isset($_POST['etiketler']) ? sanitize_text_field($_POST['etiketler']) : '';
        if ($category_id && $aciklama && strlen($aciklama) <= 200) {
            $post_id = wp_insert_post([
                'post_title' => 'Açıklama #' . time(),
                'post_type' => 'nefret_aciklama',
                'post_status' => 'publish',
                'post_author' => get_current_user_id(),
            ]);
            if (!is_wp_error($post_id)) {
                $wpdb->insert($table_name, [
                    'id' => $post_id,
                    'user_id' => get_current_user_id(),
                    'category_id' => $category_id,
                    'aciklama' => $aciklama,
                    'durum' => 'onaylandi'
                ]);
                if ($wpdb->insert_id || $wpdb->rows_affected) {
                    wp_set_object_terms($post_id, [$category_id], 'nefret_kategorisi', false);
                    if ($etiketler) {
                        $etiket_listesi = array_map('trim', explode(',', $etiketler));
                        $term_ids = [];
                        foreach ($etiket_listesi as $etiket) {
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
                    }
                    $message = 'Açıklama eklendi ve onaylandı!';
                    $status = 'updated';
                } else {
                    wp_delete_post($post_id, true);
                    $message = 'Açıklama eklenemedi!';
                    $status = 'error';
                }
            } else {
                $message = 'Post oluşturulamadı!';
                $status = 'error';
            }
        } else {
            $message = 'Açıklama eklenemedi: Kategori seçilmemiş veya açıklama geçersiz!';
            $status = 'error';
        }
        echo '<div class="' . $status . '"><p>' . esc_html($message) . '</p></div>';
    }

    // Admin Açıklama Düzenleme
    if (isset($_POST['nefret_aciklama_duzenle']) && current_user_can('manage_options')) {
        $id = intval($_POST['aciklama_id']);
        $category_id = intval($_POST['category_id']);
        $aciklama = sanitize_textarea_field($_POST['aciklama']);
        $etiketler = isset($_POST['etiketler']) ? sanitize_text_field($_POST['etiketler']) : '';
        if ($id && $category_id && $aciklama && strlen($aciklama) <= 200) {
            $wpdb->update($table_name, [
                'category_id' => $category_id,
                'aciklama' => $aciklama,
            ], ['id' => $id]);
            if (!$wpdb->last_error) {
                wp_set_object_terms($id, [$category_id], 'nefret_kategorisi', false);
                $term_ids = [];
                if ($etiketler) {
                    $etiket_listesi = array_map('trim', explode(',', $etiketler));
                    foreach ($etiket_listesi as $etiket) {
                        if ($etiket) {
                            $term = wp_insert_term($etiket, 'nefret_etiketleri', ['slug' => sanitize_title($etiket)]);
                            if (!is_wp_error($term)) {
                                $term_ids[] = $term['term_id'];
                            } else {
                                $term_ids[] = $term->get_error_data();
                            }
                        }
                    }
                }
                wp_set_object_terms($id, $term_ids ?: [], 'nefret_etiketleri', false);
                $message = 'Açıklama güncellendi!';
                $status = 'updated';
            } else {
                $message = 'Açıklama güncellenemedi: ' . $wpdb->last_error;
                $status = 'error';
            }
        } else {
            $message = 'Açıklama güncellenemedi: Kategori seçilmemiş veya açıklama geçersiz!';
            $status = 'error';
        }
        echo '<div class="' . $status . '"><p>' . esc_html($message) . '</p></div>';
    }

    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    $where = $category_id ? $wpdb->prepare("WHERE category_id = %d", $category_id) : '';
    $aciklamalar = $wpdb->get_results("SELECT * FROM $table_name $where ORDER BY id DESC", ARRAY_A);

    // Düzenleme için açıklama detayları
    $edit_aciklama = null;
    if (isset($_GET['action']) && $_GET['action'] === 'duzenle' && isset($_GET['id'])) {
        $edit_id = intval($_GET['id']);
        $edit_aciklama = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $edit_id), ARRAY_A);
    }
    ?>
    <div class="wrap">
        <h1>Nefret Açıklamaları</h1>
        <p><a href="<?php echo admin_url('admin.php?page=nefret-kategorileri'); ?>">Kategorilere Git</a> | <a href="<?php echo admin_url('admin.php?page=nefret-etiketleri'); ?>">Etiketlere Git</a></p>
        <?php if ($category_id) : ?>
            <h2><?php
                $kategori = get_term($category_id, 'nefret_kategorisi');
                echo esc_html(is_wp_error($kategori) ? 'Kategori Bulunamadı' : $kategori->name);
            ?> Açıklamaları</h2>
        <?php endif; ?>

        <!-- Açıklama Ekleme veya Düzenleme Formu -->
        <h3><?php echo $edit_aciklama ? 'Açıklama Düzenle' : 'Yeni Açıklama Ekle'; ?></h3>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="category_id">Nefret Kategorisi</label></th>
                    <td>
                        <select name="category_id" id="category_id">
                            <option value="">Kategori Seçin</option>
                            <?php
                            $kategoriler = get_terms(['taxonomy' => 'nefret_kategorisi', 'hide_empty' => false]);
                            foreach ($kategoriler as $kategori) {
                                $selected = ($edit_aciklama && $edit_aciklama['category_id'] == $kategori->term_id) ? 'selected' : '';
                                echo '<option value="' . $kategori->term_id . '" ' . $selected . '>' . esc_html($kategori->name) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="aciklama">Açıklama</label></th>
                    <td>
                        <textarea name="aciklama" id="aciklama" rows="3" maxlength="200" placeholder="Açıklama (max 200 karakter)"><?php echo $edit_aciklama ? esc_textarea($edit_aciklama['aciklama']) : ''; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="etiketler">Etiketler</label></th>
                    <td>
                        <input type="text" name="etiketler" id="etiketler" placeholder="örn: trafik,sinir (virgülle ayırın)" value="<?php
                            if ($edit_aciklama) {
                                $etiketler = wp_get_object_terms($edit_aciklama['id'], 'nefret_etiketleri');
                                if (!is_wp_error($etiketler)) {
                                    $etiket_names = array_map(function($term) { return $term->name; }, $etiketler);
                                    echo esc_attr(implode(',', $etiket_names));
                                }
                            }
                        ?>">
                        <div id="etiket-onerileri" style="display: none;"></div>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <?php if ($edit_aciklama) : ?>
                    <input type="hidden" name="aciklama_id" value="<?php echo $edit_aciklama['id']; ?>">
                    <input type="submit" name="nefret_aciklama_duzenle" class="button button-primary" value="Güncelle">
                    <a href="<?php echo admin_url('admin.php?page=nefret-aciklamalari'); ?>" class="button button-secondary">İptal</a>
                <?php else : ?>
                    <input type="submit" name="nefret_aciklama_ekle" class="button button-primary" value="Ekle ve Onayla">
                <?php endif; ?>
            </p>
        </form>

        <!-- Açıklama Listesi -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Kullanıcı</th>
                    <th>Kategori</th>
                    <th>Açıklama</th>
                    <th>Etiketler</th>
                    <th>Durum</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($aciklamalar)) {
                    foreach ($aciklamalar as $aciklama) {
                        $user = get_userdata($aciklama['user_id']);
                        $kategori = isset($aciklama['category_id']) ? get_term($aciklama['category_id'], 'nefret_kategorisi') : null;
                        $etiketler = wp_get_object_terms($aciklama['id'], 'nefret_etiketleri');
                        $base_url = admin_url('admin.php?page=nefret-aciklamalari');
                        $category_id_param = $category_id ? '&category_id=' . $category_id : '';
                        ?>
                        <tr>
                            <td><?php echo esc_html($user ? $user->user_login : 'Bilinmeyen Kullanıcı'); ?></td>
                            <td>
                                <?php if (isset($aciklama['category_id']) && !is_wp_error($kategori)) : ?>
                                    <a href="<?php echo esc_url(add_query_arg('category_id', $aciklama['category_id'], $base_url)); ?>">
                                        <?php echo esc_html($kategori->name); ?>
                                    </a>
                                <?php else : ?>
                                    Kategori Bulunamadı
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($aciklama['aciklama']); ?></td>
                            <td>
                                <?php
                                if (!is_wp_error($etiketler) && !empty($etiketler)) {
                                    $etiket_names = array_map(function($term) { return esc_html($term->name); }, $etiketler);
                                    echo implode(', ', $etiket_names);
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($aciklama['durum']); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(array('action' => 'duzenle', 'id' => $aciklama['id']), $base_url) . $category_id_param); ?>" class="button button-secondary">Düzenle</a>
                                <?php if ($aciklama['durum'] === 'beklemede') : ?>
                                    <a href="<?php echo esc_url(add_query_arg(array('action' => 'onayla', 'id' => $aciklama['id']), $base_url) . $category_id_param); ?>" class="button button-primary">Onayla</a>
                                    <a href="<?php echo esc_url(add_query_arg(array('action' => 'reddet', 'id' => $aciklama['id']), $base_url) . $category_id_param); ?>" class="button button-secondary">Reddet</a>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(add_query_arg(array('action' => 'sil', 'id' => $aciklama['id']), $base_url) . $category_id_param); ?>" class="button button-danger" onclick="return confirm('Bu açıklamayı silmek istediğinizden emin misiniz?');">Sil</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="6">Henüz açıklama yok.</td></tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Kategori Yönetim Sayfası
function nefret_kategori_yonetim_page() {
    global $wpdb;
    $taxonomy = 'nefret_kategorisi';

    // Kategori Ekleme
    if (isset($_POST['nefret_kategori_ekle']) && current_user_can('manage_options')) {
        $name = sanitize_text_field($_POST['kategori_adi']);
        $slug = sanitize_title($_POST['kategori_slug']);
        if ($name) {
            $term = wp_insert_term($name, $taxonomy, ['slug' => $slug]);
            if (!is_wp_error($term)) {
                echo '<div class="updated"><p>Kategori eklendi!</p></div>';
            } else {
                echo '<div class="error"><p>Kategori eklenemedi: ' . esc_html($term->get_error_message()) . '</p></div>';
            }
        }
    }

    // Kategori Düzenleme
    if (isset($_POST['nefret_kategori_duzenle']) && current_user_can('manage_options')) {
        $term_id = intval($_POST['term_id']);
        $name = sanitize_text_field($_POST['kategori_adi']);
        $slug = sanitize_title($_POST['kategori_slug']);
        if ($term_id && $name) {
            $term = wp_update_term($term_id, $taxonomy, ['name' => $name, 'slug' => $slug]);
            if (!is_wp_error($term)) {
                echo '<div class="updated"><p>Kategori güncellendi!</p></div>';
            } else {
                echo '<div class="error"><p>Kategori güncellenemedi: ' . esc_html($term->get_error_message()) . '</p></div>';
            }
        }
    }

    // Kategori Silme
    if (isset($_GET['action']) && $_GET['action'] === 'sil' && isset($_GET['term_id']) && current_user_can('manage_options')) {
        $term_id = intval($_GET['term_id']);
        $result = wp_delete_term($term_id, $taxonomy);
        if ($result && !is_wp_error($result)) {
            echo '<div class="updated"><p>Kategori silindi!</p></div>';
        } else {
            echo '<div class="error"><p>Kategori silinemedi!</p></div>';
        }
    }

    $kategoriler = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    $edit_term = null;
    if (isset($_GET['action']) && $_GET['action'] === 'duzenle' && isset($_GET['term_id'])) {
        $edit_term = get_term(intval($_GET['term_id']), $taxonomy);
    }
    ?>
    <div class="wrap">
        <h1>Nefret Kategorileri</h1>
        <h3><?php echo $edit_term ? 'Kategori Düzenle' : 'Yeni Kategori Ekle'; ?></h3>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="kategori_adi">Kategori Adı</label></th>
                    <td><input type="text" name="kategori_adi" id="kategori_adi" value="<?php echo $edit_term ? esc_attr($edit_term->name) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="kategori_slug">Slug</label></th>
                    <td><input type="text" name="kategori_slug" id="kategori_slug" value="<?php echo $edit_term ? esc_attr($edit_term->slug) : ''; ?>"></td>
                </tr>
            </table>
            <p class="submit">
                <?php if ($edit_term) : ?>
                    <input type="hidden" name="term_id" value="<?php echo $edit_term->term_id; ?>">
                    <input type="submit" name="nefret_kategori_duzenle" class="button button-primary" value="Güncelle">
                    <a href="<?php echo admin_url('admin.php?page=nefret-kategorileri'); ?>" class="button button-secondary">İptal</a>
                <?php else : ?>
                    <input type="submit" name="nefret_kategori_ekle" class="button button-primary" value="Kategori Ekle">
                <?php endif; ?>
            </p>
        </form>

        <h3>Mevcut Kategoriler</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Adı</th>
                    <th>Slug</th>
                    <th>Kullanım</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($kategoriler)) : ?>
                    <?php foreach ($kategoriler as $kategori) : ?>
                        <tr>
                            <td><?php echo esc_html($kategori->name); ?></td>
                            <td><?php echo esc_html($kategori->slug); ?></td>
                            <td><?php echo intval($kategori->count); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'duzenle', 'term_id' => $kategori->term_id], admin_url('admin.php?page=nefret-kategorileri'))); ?>" class="button button-secondary">Düzenle</a>
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'sil', 'term_id' => $kategori->term_id], admin_url('admin.php?page=nefret-kategorileri'))); ?>" class="button button-danger" onclick="return confirm('Bu kategoriyi silmek istediğinizden emin misiniz?');">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="4">Henüz kategori yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Etiket Yönetim Sayfası
function nefret_etiket_yonetim_page() {
    global $wpdb;
    $taxonomy = 'nefret_etiketleri';

    // Etiket Ekleme
    if (isset($_POST['nefret_etiket_ekle']) && current_user_can('manage_options')) {
        $name = sanitize_text_field($_POST['etiket_adi']);
        $slug = sanitize_title($_POST['etiket_slug']);
        if ($name) {
            $term = wp_insert_term($name, $taxonomy, ['slug' => $slug]);
            if (!is_wp_error($term)) {
                echo '<div class="updated"><p>Etiket eklendi!</p></div>';
            } else {
                echo '<div class="error"><p>Etiket eklenemedi: ' . esc_html($term->get_error_message()) . '</p></div>';
            }
        }
    }

    // Etiket Düzenleme
    if (isset($_POST['nefret_etiket_duzenle']) && current_user_can('manage_options')) {
        $term_id = intval($_POST['term_id']);
        $name = sanitize_text_field($_POST['etiket_adi']);
        $slug = sanitize_title($_POST['etiket_slug']);
        if ($term_id && $name) {
            $term = wp_update_term($term_id, $taxonomy, ['name' => $name, 'slug' => $slug]);
            if (!is_wp_error($term)) {
                echo '<div class="updated"><p>Etiket güncellendi!</p></div>';
            } else {
                echo '<div class="error"><p>Etiket güncellenemedi: ' . esc_html($term->get_error_message()) . '</p></div>';
            }
        }
    }

    // Etiket Silme
    if (isset($_GET['action']) && $_GET['action'] === 'sil' && isset($_GET['term_id']) && current_user_can('manage_options')) {
        $term_id = intval($_GET['term_id']);
        $result = wp_delete_term($term_id, $taxonomy);
        if ($result && !is_wp_error($result)) {
            echo '<div class="updated"><p>Etiket silindi!</p></div>';
        } else {
            echo '<div class="error"><p>Etiket silinemedi!</p></div>';
        }
    }

    $etiketler = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
    $edit_term = null;
    if (isset($_GET['action']) && $_GET['action'] === 'duzenle' && isset($_GET['term_id'])) {
        $edit_term = get_term(intval($_GET['term_id']), $taxonomy);
    }
    ?>
    <div class="wrap">
        <h1>Nefret Etiketleri</h1>
        <h3><?php echo $edit_term ? 'Etiket Düzenle' : 'Yeni Etiket Ekle'; ?></h3>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="etiket_adi">Etiket Adı</label></th>
                    <td><input type="text" name="etiket_adi" id="etiket_adi" value="<?php echo $edit_term ? esc_attr($edit_term->name) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="etiket_slug">Slug</label></th>
                    <td><input type="text" name="etiket_slug" id="etiket_slug" value="<?php echo $edit_term ? esc_attr($edit_term->slug) : ''; ?>"></td>
                </tr>
            </table>
            <p class="submit">
                <?php if ($edit_term) : ?>
                    <input type="hidden" name="term_id" value="<?php echo $edit_term->term_id; ?>">
                    <input type="submit" name="nefret_etiket_duzenle" class="button button-primary" value="Güncelle">
                    <a href="<?php echo admin_url('admin.php?page=nefret-etiketleri'); ?>" class="button button-secondary">İptal</a>
                <?php else : ?>
                    <input type="submit" name="nefret_etiket_ekle" class="button button-primary" value="Etiket Ekle">
                <?php endif; ?>
            </p>
        </form>

        <h3>Mevcut Etiketler</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Adı</th>
                    <th>Slug</th>
                    <th>Kullanım</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($etiketler)) : ?>
                    <?php foreach ($etiketler as $etiket) : ?>
                        <tr>
                            <td><?php echo esc_html($etiket->name); ?></td>
                            <td><?php echo esc_html($etiket->slug); ?></td>
                            <td><?php echo intval($etiket->count); ?></td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'duzenle', 'term_id' => $etiket->term_id], admin_url('admin.php?page=nefret-etiketleri'))); ?>" class="button button-secondary">Düzenle</a>
                                <a href="<?php echo esc_url(add_query_arg(['action' => 'sil', 'term_id' => $etiket->term_id], admin_url('admin.php?page=nefret-etiketleri'))); ?>" class="button button-danger" onclick="return confirm('Bu etiketi silmek istediğinizden emin misiniz?');">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="4">Henüz etiket yok.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}