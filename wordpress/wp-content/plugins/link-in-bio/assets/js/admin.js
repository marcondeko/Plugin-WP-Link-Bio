jQuery(document).ready(function ($) {
    let mediaFrame;

    /**
     * Adiciona novo link à lista dinamicamente
     */
    $('#link-in-bio-add').on('click', function () {
        const container = $('#link-in-bio-links-container');
        const index = Date.now(); // Usa timestamp como identificador único

        // HTML do novo bloco de link, com classes Tailwind para estilização
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

        container.append(html); // Adiciona o novo HTML
        container.animate({ scrollTop: container.prop("scrollHeight") }, 300); // Animação para rolar até o final
    });

    /**
     * Remove link existente ao clicar no botão "Remover"
     */
    $(document).on('click', '.link-in-bio-remove', function () {
        if (confirm(wp.i18n.__('Tem certeza que deseja remover este link?', 'link-in-bio'))) {
            $(this).closest('.link-in-bio-link').fadeOut(300, function () {
                $(this).remove(); // Remove após fade
                setTimeout(updatePreview, 0); // Atualiza preview após remoção
            });
        }
    });

    /**
     * Função genérica para abrir o seletor de mídia do WordPress
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
            $(fieldId).val(attachment.url).trigger('change');
            $(previewSelector).html(`<img src="${attachment.url}" alt="" class="max-w-[150px] h-auto rounded-md object-cover shadow-sm">`);
            if (removeButtonSelector) {
                $(removeButtonSelector).show();
            }
        });

        mediaFrame.open();
    }

    /**
     * Upload de imagem de perfil
     */
    $('#link-in-bio-upload-image').on('click', function (e) {
        e.preventDefault();
        openMediaUploader('#link_in_bio_profile_image', '#link-in-bio-image-preview', '#link-in-bio-remove-image');
    });

    /**
     * Remoção da imagem de perfil
     */
    $('#link-in-bio-remove-image').on('click', function (e) {
        e.preventDefault();
        if (confirm(wp.i18n.__('Remover imagem de perfil?', 'link-in-bio'))) {
            $('#link_in_bio_profile_image').val('').trigger('change');
            $('#link-in-bio-image-preview').empty();
            $(this).hide();
        }
    });

    /**
     * Upload da imagem de fundo
     */
    $('#upload-background-image').on('click', function (e) {
        e.preventDefault();
        openMediaUploader('#link_in_bio_background_image', '#link-in-bio-background-preview', '#remove-background-image');
    });

    /**
     * Remoção da imagem de fundo
     */
    $('#remove-background-image').on('click', function (e) {
        e.preventDefault();
        if (confirm(wp.i18n.__('Remover imagem de fundo?', 'link-in-bio'))) {
            $('#link_in_bio_background_image').val('').trigger('change');
            $('#link-in-bio-background-preview').empty();
            $(this).hide();
        }
    });

    /**
     * Esconde botões de "Remover imagem" caso não haja imagem definida
     */
    if (!$('#link_in_bio_profile_image').val()) {
        $('#link-in-bio-remove-image').hide();
    }
    if (!$('#link_in_bio_background_image').val()) {
        $('#remove-background-image').hide();
    }

    const $form = $('#link-in-bio-settings-form');
    let previewTimeout;

    /**
     * Atualiza o preview com AJAX após mudanças no formulário
     */
    function updatePreview() {
        clearTimeout(previewTimeout); // Cancela execuções anteriores
        previewTimeout = setTimeout(function() {
            const formData = $form.serialize();
            $.post(LinkInBioData.ajax_url, {
                action: 'link_in_bio_preview',
                nonce: LinkInBioData.nonce,
                formData: formData
            }, function(response) {
                $('#link-in-bio-preview-content').html(response);
            });
        }, 300); // Delay de 300ms para evitar chamadas excessivas
    }

    /**
     * Escuta alterações no formulário para atualizar o preview em tempo real
     */
    $form.on('change input', 'input, select, textarea', function () {
        updatePreview();
    });

    /**
     * Oculta botão de remover imagem de fundo se não houver imagem no carregamento
     */
    $('#link-in-bio-background-preview:empty').each(function() {
        if ($(this).children().length === 0) {
            $('#remove-background-image').hide();
        }
    });

    /**
     * Inicializa a visualização de preview ao carregar a página
     */
    updatePreview();

    /**
     * Navegação entre abas nas configurações do admin
     */
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').hide();
        const target = $(this).attr('href');
        $(target).show();

        updatePreview(); // Atualiza preview ao trocar de aba
    });

    // Ativa a primeira aba automaticamente ao carregar a página
    $('.nav-tab-wrapper a').first().click();
});
