jQuery(document).ready(function ($) {
    let mediaFrame;

    /**
     * Adiciona novo link
     */
    $('#link-in-bio-add').on('click', function () {
        const container = $('#link-in-bio-links-container');
        const index = Date.now(); // Timestamp único para garantir ids/nomes únicos

        // O HTML foi atualizado com classes Tailwind para responsividade
        const html = `
        <div class="link-in-bio-link p-4 border border-gray-200 rounded-md bg-gray-50 flex flex-wrap items-center gap-2 sm:gap-4">
            <div class="flex-grow">
                <label for="link-title-${index}" class="sr-only">${wp.i18n.__('Título do Link', 'link-in-bio')}</label>
                <input type="text"
                       id="link-title-${index}"
                       name="link_in_bio_options[links][${index}][title]"
                       placeholder="${wp.i18n.__('Título do Link', 'link-in-bio')}"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm p-2"
                       required>
            </div>
            <div class="flex-grow">
                <label for="link-url-${index}" class="sr-only">${wp.i18n.__('URL do Link', 'link-in-bio')}</label>
                <input type="url"
                       id="link-url-${index}"
                       name="link_in_bio_options[links][${index}][url]"
                       placeholder="https://exemplo.com"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm p-2"
                       required>
            </div>
            <div class="flex-shrink-0">
                <button type="button" class="button button-danger bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md transition duration-150 link-in-bio-remove">
                    ${wp.i18n.__('Remover', 'link-in-bio')}
                </button>
            </div>
        </div>`;

        container.append(html);
        // Animação para scrollar até o novo link
        container.animate({ scrollTop: container.prop("scrollHeight") }, 300);
    });

    /**
     * Remover link
     */
    $(document).on('click', '.link-in-bio-remove', function () {
        if (confirm(wp.i18n.__('Tem certeza que deseja remover este link?', 'link-in-bio'))) {
            $(this).closest('.link-in-bio-link').fadeOut(300, function () {
                $(this).remove();
                // Chama updatePreview após a remoção e animação
                setTimeout(updatePreview, 0); // O timeout 0 garante que ele seja agendado após o 'remove()'
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
            $(fieldId).val(attachment.url).trigger('change'); // Adicionado .trigger('change')
            $(previewSelector).html(`<img src="${attachment.url}" alt="" class="max-w-[150px] h-auto rounded-md object-cover shadow-sm">`); // Classes Tailwind para preview
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
            $('#link_in_bio_profile_image').val('').trigger('change'); // Adicionado .trigger('change')
            $('#link-in-bio-image-preview').empty();
            $(this).hide();
        }
    });

    /**
     * Imagem de fundo
     */
    $('#upload-background-image').on('click', function (e) {
        e.preventDefault();
        openMediaUploader('#link_in_bio_background_image', '#link-in-bio-background-preview', '#remove-background-image'); // Adicionado removeButtonSelector
    });

    $('#remove-background-image').on('click', function (e) {
        e.preventDefault();
        if (confirm(wp.i18n.__('Remover imagem de fundo?', 'link-in-bio'))) {
            $('#link_in_bio_background_image').val('').trigger('change'); // Adicionado .trigger('change')
            $('#link-in-bio-background-preview').empty();
            $(this).hide(); // Esconder o botão de remover imagem de fundo
        }
    });

    // Esconde o botão de remover imagem de perfil se não houver imagem
    // Isso deve ser feito na carga, não no mediaUploader
    if (!$('#link_in_bio_profile_image').val()) {
        $('#link-in-bio-remove-image').hide();
    }
    // Esconde o botão de remover imagem de fundo se não houver imagem
    if (!$('#link_in_bio_background_image').val()) {
        $('#remove-background-image').hide();
    }


    const $form = $('#link-in-bio-settings-form');
    let previewTimeout;

    function updatePreview() {
        // Limpa qualquer timeout anterior para evitar chamadas múltiplas
        clearTimeout(previewTimeout);

        // Define um novo timeout para chamar o AJAX após um breve atraso
        previewTimeout = setTimeout(function() {
            const formData = $form.serialize();

            $.post(LinkInBioData.ajax_url, {
                action: 'link_in_bio_preview',
                nonce: LinkInBioData.nonce,
                formData: formData
            }, function(response) {
                $('#link-in-bio-preview-content').html(response);
            });
        }, 300); // Pequeno delay de 300ms para aguardar entradas do usuário ou animações
    }

    // Atualiza o preview ao alterar qualquer campo
    // Usa 'input' para capturar mudanças em tempo real em campos de texto
    // e 'change' para outros tipos como select, checkbox, radio
    $form.on('change input', 'input, select, textarea', function () {
        updatePreview();
    });

    // Oculta o botão de remover imagem de fundo se não houver imagem
    $('#link-in-bio-background-preview:empty').each(function() {
        if ($(this).children().length === 0) {
            $('#remove-background-image').hide();
        }
    });


    // Inicia a pré-visualização na carga da página
    updatePreview();


    //navegação em abas.
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').hide();
        const target = $(this).attr('href');
        $(target).show();

        // Garante que o preview seja atualizado ao mudar de aba
        updatePreview();
    });

    // Ativa a primeira aba ao carregar a página
    $('.nav-tab-wrapper a').first().click();

});