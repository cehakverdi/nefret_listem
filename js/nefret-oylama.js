jQuery(document).ready(function($) {
    console.log('nefret-oylama.js yüklendi'); // Debug

    // Açıklama Ekleme
    $('#nefret-aciklama-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var messageDiv = $('#nefret-form-message');
        var data = {
            action: 'nefret_aciklama_kaydet',
            nonce: nefret_ajax.nonce,
            category_id: form.find('#category_id').val(),
            aciklama: form.find('#aciklama').val().trim(),
            etiketler: form.find('#etiketler').val()
        };
        console.log('Gönderilen veri:', data); // Debug

        $.ajax({
            url: nefret_ajax.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                console.log('Kaydetme yanıtı:', response); // Debug
                if (response.success) {
                    messageDiv.html('<p class="success">' + response.data.message + '</p>');
                    form[0].reset();
                } else {
                    messageDiv.html('<p class="error">' + response.data.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.log('Kaydetme hatası:', status, error); // Debug
                messageDiv.html('<p class="error">Bir hata oluştu!</p>');
            }
        });
    });

    // Oylama (nefret_oylama için)
    $('.vote-button').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var aciklama_id = button.data('id');
        var vote_type = button.data('type');

        console.log('Oylama tıklandı, ID:', aciklama_id, 'Tip:', vote_type); // Debug

        if (!nefret_ajax.logged_in) {
            console.log('Giriş yapılmamış, yönlendiriliyor:', nefret_ajax.login_url);
            window.location.href = nefret_ajax.login_url;
            return;
        }

        $.ajax({
            url: nefret_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'nefret_oyla',
                nonce: nefret_ajax.nonce,
                aciklama_id: aciklama_id,
                vote_type: vote_type
            },
            success: function(response) {
                console.log('Oylama yanıtı:', response); // Debug
                if (response.success) {
                    button.text(vote_type === 'hate' ? 'Nefret Et (' + response.data.hate + ')' : button.text());
                    button.addClass('active');
                } else {
                    alert(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Oylama hatası:', status, error); // Debug
                alert('Oylama sırasında bir hata oluştu!');
            }
        });
    });

    // Oylama (nefret_tum_ogeler için)
    $('.oy-btn.hate').on('click', function(e) {
        e.preventDefault();
        var button = $(this);
        var aciklama_id = button.data('id');
        var vote_type = button.data('oy-tipi');

        console.log('Oylama tıklandı, ID:', aciklama_id, 'Tip:', vote_type); // Debug

        if (!nefret_ajax.logged_in) {
            console.log('Giriş yapılmamış, yönlendiriliyor:', nefret_ajax.login_url);
            window.location.href = nefret_ajax.login_url;
            return;
        }

        $.ajax({
            url: nefret_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'nefret_oyla',
                nonce: nefret_ajax.nonce,
                aciklama_id: aciklama_id,
                vote_type: vote_type
            },
            success: function(response) {
                console.log('Oylama yanıtı:', response); // Debug
                if (response.success) {
                    button.find('.oy-sayisi').text(response.data.hate);
                    button.addClass('active');
                } else {
                    alert(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Oylama hatası:', status, error); // Debug
                alert('Oylama sırasında bir hata oluştu!');
            }
        });
    });

    // Etiket Önerileri (Ekleme Formu)
    $('#etiketler').on('input', function() {
        var term = $(this).val().split(',').pop().trim();
        console.log('Aranan terim:', term); // Debug

        if (term.length < 2) {
            $('#etiket-onerileri').hide().empty();
            return;
        }

        $.ajax({
            url: nefret_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'nefret_etiket_ara',
                nonce: nefret_ajax.nonce,
                term: term
            },
            success: function(response) {
                console.log('AJAX yanıtı:', response); // Debug
                $('#etiket-onerileri').empty();
                if (response.success && response.data && response.data.length) {
                    var suggestions = response.data.map(function(tag) {
                        return '<div class="etiket-oneri" data-tag="' + tag + '">' + tag + '</div>';
                    }).join('');
                    $('#etiket-onerileri').html(suggestions).show();
                } else {
                    $('#etiket-onerileri').hide();
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX hatası:', status, error); // Debug
                $('#etiket-onerileri').hide();
            }
        });
    });

    // Etiket Önerileri (Düzenleme Formu)
    $('#duzenle_etiketler').on('input', function() {
        var term = $(this).val().split(',').pop().trim();
        console.log('Aranan terim:', term); // Debug

        if (term.length < 2) {
            $('#duzenle_etiket-onerileri').hide().empty();
            return;
        }

        $.ajax({
            url: nefret_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'nefret_etiket_ara',
                nonce: nefret_ajax.nonce,
                term: term
            },
            success: function(response) {
                console.log('AJAX yanıtı:', response); // Debug
                $('#duzenle_etiket-onerileri').empty();
                if (response.success && response.data && response.data.length) {
                    var suggestions = response.data.map(function(tag) {
                        return '<div class="etiket-oneri" data-tag="' + tag + '">' + tag + '</div>';
                    }).join('');
                    $('#duzenle_etiket-onerileri').html(suggestions).show();
                } else {
                    $('#duzenle_etiket-onerileri').hide();
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX hatası:', status, error); // Debug
                $('#duzenle_etiket-onerileri').hide();
            }
        });
    });

    // Öneri Tıklama
    $(document).on('click', '.etiket-oneri', function() {
        var tag = $(this).data('tag');
        var target = $(this).closest('.nefret-form-group').find('input');
        var current = target.val();
        var tags = current.split(',').filter(function(t) { return t.trim(); });
        tags.pop();
        tags.push(tag);
        target.val(tags.join(', ') + ', ');
        target.next('.etiket-onerileri').hide();
    });

    // Önerileri Gizle
    $(document).click(function(e) {
        if (!$(e.target).closest('#etiketler, #duzenle_etiketler, #etiket-onerileri, #duzenle_etiket-onerileri').length) {
            $('#etiket-onerileri, #duzenle_etiket-onerileri').hide();
        }
    });

     // Düzenleme Butonu
$('.nefret-edit-button').on('click', function() {
    var id = $(this).data('id');
    var row = $('tr[data-id="' + id + '"]');
    var aciklama = row.find('td:eq(0)').text();
    var kategori = row.find('td:eq(1)').text();
    var etiketler = row.find('td:eq(2)').text();

    $('#duzenle_aciklama_id').val(id);
    $('#duzenle_aciklama').val(aciklama);
    $('#duzenle_etiketler').val(etiketler === '-' ? '' : etiketler);
    $('#duzenle_category_id option').each(function() {
        if ($(this).text() === kategori) {
            $(this).prop('selected', true);
        }
    });

    $('#nefret-duzenle-form').show();
});

// İptal Butonu
$('.nefret-cancel-button').on('click', function() {
    $('#nefret-duzenle-form').hide();
    $('#nefret-duzenle-aciklama-form')[0].reset();
    $('#nefret-duzenle-message').empty();
});

// Açıklama Düzenleme
$('#nefret-duzenle-aciklama-form').on('submit', function(e) {
    e.preventDefault();
    var form = $(this);
    var messageDiv = $('#nefret-duzenle-message');

    $.ajax({
        url: nefret_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'nefret_aciklama_duzenle',
            nonce: nefret_ajax.nonce,
            aciklama_id: form.find('#duzenle_aciklama_id').val(),
            category_id: form.find('#duzenle_category_id').val(),
            aciklama: form.find('#duzenle_aciklama').val(),
            etiketler: form.find('#duzenle_etiketler').val()
        },
        success: function(response) {
            if (response.success) {
                messageDiv.html('<p class="success">Açıklama güncellendi!</p>');
                var id = form.find('#duzenle_aciklama_id').val();
                var row = $('tr[data-id="' + id + '"]');
                row.find('td:eq(0)').text(form.find('#duzenle_aciklama').val());
                row.find('td:eq(1)').text(form.find('#duzenle_category_id option:selected').text());
                row.find('td:eq(2)').text(form.find('#duzenle_etiketler').val() || '-');
                $('#nefret-duzenle-form').hide();
                form[0].reset();
            } else {
                messageDiv.html('<p class="error">' + response.data.message + '</p>');
            }
        },
        error: function(xhr, status, error) {
            console.log('Düzenleme hatası:', status, error); // Debug
            messageDiv.html('<p class="error">Bir hata oluştu!</p>');
        }
    });
});

// Açıklama Silme
$('.nefret-delete-button').on('click', function() {
    if (!confirm('Bu açıklamayı silmek istediğinizden emin misiniz?')) {
        return;
    }

    var id = $(this).data('id');
    $.ajax({
        url: nefret_ajax.ajaxurl,
        type: 'POST',
        data: {
            action: 'nefret_aciklama_sil',
            nonce: nefret_ajax.nonce,
            aciklama_id: id
        },
        success: function(response) {
            if (response.success) {
                $('tr[data-id="' + id + '"]').remove();
            } else {
                alert(response.data.message);
            }
        },
        error: function(xhr, status, error) {
            console.log('Silme hatası:', status, error); // Debug
            alert('Silme sırasında bir hata oluştu!');
        }
    });
});
});