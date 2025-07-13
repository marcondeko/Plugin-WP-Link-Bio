jQuery(document).ready(function ($) {
    let mediaFrame;

    /**
     * Adiciona novo link
     */
    $('#link-in-bio-add').on('click', function () {
        const container = $('#link-in-bio-links-container');
        const index = Date.now(); // Timestamp único

        const html = `
        <div class="link-in-bio-link">
            <div class="link-in-bio-link-row">
                <input type="text" 
                       name="link_in_bio_options[links][${index}][title]" 
                       placeholder="Título do Link" 
                       class="regular-text" required>
                <input type="url" 
                       name="link_in_bio_options[links][${index}][url]" 
                       placeholder="https://exemplo.com" 
                       class="regular-text" required>
                <button type="button" class="button button-secondary link-in-bio-remove">
                    ${wp.i18n.__('Remover', 'link-in-bio')}
                </button>
            </div>
        </div>`;

        container.append(html);
        container.animate({ scrollTop: container.prop("scrollHeight") }, 300);
    });

    /**
     * Remover link
     */
    $(document).on('click', '.link-in-bio-remove', function () {
        if (confirm(wp.i18n.__('Tem certeza que deseja remover este link?', 'link-in-bio'))) {
            $(this).closest('.link-in-bio-link').fadeOut(300, function () {
                $(this).remove();
            });
        }
    });

    /**
     * Abrir uploader de mídia
     */
    function openMediaUploader(fieldId, previewSelector, removeButtonSelector) {
        mediaFrame = wp.media({
            title: wp.i18n.__('Selecionar imagem', 'link-in-bio'),
            button: { text: wp.i18n.__('Usar esta imagem', 'link-in-bio') },
            library: { type: ['image'] },
            multiple: false,
        });

        mediaFrame.on('select', function () {
            const attachment = mediaFrame.state().get('selection').first().toJSON();
            $(fieldId).val(attachment.url);
            $(previewSelector).html(`<img src="${attachment.url}" alt="" style="max-width: 150px; height: auto;">`);
            if (removeButtonSelector) {
                $(removeButtonSelector).show();
            }
        });

        mediaFrame.open();
    }

    /**
     * Imagem de perfil
     */
    $('#link-in-bio-upload-image').on('click', function (e) {
        e.preventDefault();
        openMediaUploader('#link_in_bio_profile_image', '#link-in-bio-image-preview', '#link-in-bio-remove-image');
    });

    $('#link-in-bio-remove-image').on('click', function (e) {
        e.preventDefault();
        if (confirm(wp.i18n.__('Remover imagem de perfil?', 'link-in-bio'))) {
            $('#link_in_bio_profile_image').val('');
            $('#link-in-bio-image-preview').empty();
            $(this).hide();
        }
    });

    /**
     * Imagem de fundo
     */
    $('#upload-background-image').on('click', function (e) {
        e.preventDefault();
        openMediaUploader('#link_in_bio_background_image', '#background-image-preview');
    });

    $('#remove-background-image').on('click', function (e) {
        e.preventDefault();
        if (confirm(wp.i18n.__('Remover imagem de fundo?', 'link-in-bio'))) {
            $('#link_in_bio_background_image').val('');
            $('#background-image-preview').empty();
        }
    });

    // Esconde o botão de remover imagem de perfil se não houver imagem
    if (!$('#link_in_bio_profile_image').val()) {
        $('#link-in-bio-remove-image').hide();
    }
});
