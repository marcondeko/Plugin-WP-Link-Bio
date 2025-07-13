jQuery(document).ready(function($) {
    // Variável para o frame de mídia
    var mediaFrame;
    
    // Adicionar novo link - Melhorado com validação básica
    $('#link-in-bio-add').on('click', function() {
        const container = $('#link-in-bio-links-container');
        const index = Date.now();
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
        
        // Rolagem suave para o novo elemento
        container.animate({
            scrollTop: container.prop("scrollHeight")
        }, 500);
    });
    
    // Remover link - Melhorado com confirmação
    $(document).on('click', '.link-in-bio-remove', function() {
        if (confirm(wp.i18n.__('Tem certeza que deseja remover este link?', 'link-in-bio'))) {
            $(this).closest('.link-in-bio-link').fadeOut(300, function() {
                $(this).remove();
            });
        }
    });
    
    // Uploader de imagem - Melhorado com tipos específicos
    $('#link-in-bio-upload-image').on('click', function(e) {
        e.preventDefault();
        
        // Se o frame já existir, reabre
        if (mediaFrame) {
            mediaFrame.open();
            return;
        }
        
        // Cria um novo frame de mídia com configurações específicas
        mediaFrame = wp.media({
            title: wp.i18n.__('Selecione ou envie a imagem de perfil', 'link-in-bio'),
            button: {
                text: wp.i18n.__('Usar esta imagem', 'link-in-bio')
            },
            library: {
                type: ['image'] // Somente imagens
            },
            multiple: false
        });
        
        // Quando uma imagem é selecionada
        mediaFrame.on('select', function() {
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            
            // Atualiza o campo e a pré-visualização
            $('#link_in_bio_profile_image').val(attachment.url);
            
            // Mostra a imagem com alternativa se não carregar
            $('#link-in-bio-image-preview').html(`
                <img src="${attachment.url}" 
                     alt="${wp.i18n.__('Imagem de perfil', 'link-in-bio')}"
                     style="max-width: 150px; height: auto;">
            `);
            
            // Mostra o botão de remover
            $('#link-in-bio-remove-image').show();
        });
        
        mediaFrame.open();
    });
    
    // Remover imagem - Melhorado com confirmação
    $('#link-in-bio-remove-image').on('click', function(e) {
        e.preventDefault();
        
        if (confirm(wp.i18n.__('Tem certeza que deseja remover a imagem de perfil?', 'link-in-bio'))) {
            $('#link_in_bio_profile_image').val('');
            $('#link-in-bio-image-preview').empty();
            $(this).hide();
        }
    });
    
    // Esconde o botão de remover imagem se não houver imagem
    if (!$('#link_in_bio_profile_image').val()) {
        $('#link-in-bio-remove-image').hide();
    }
});