<?php
/**
 * Link in Bio - Template Functions
 *
 * @package LinkInBio
 */

// Impede o acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Exibe a página Link in Bio personalizada
 *
 * @return string HTML gerado
 */
function link_in_bio_display_links() {
    // Recupera as opções salvas no banco de dados, definindo valores padrão caso não existam
    $options = wp_parse_args(
        get_option('link_in_bio_options', []),
        [
            'links' => [],
            'profile_image' => '',
            'profile_title' => 'Seu Nome ou Marca',
            'profile_bio' => 'Bem-vindo(a) à minha página de links! Aqui você encontra os principais acessos.',
            'background_color' => '#f3f4f6',
            'background_image' => '',
            'title_color' => '#1f2937',
            'bio_color' => '#4b5563',
            'button_color' => '#3b82f6',
            'profile_image_size' => '120',
            'profile_ring_width' => '4'
        ]
    );

    // Define estilos inline para o background (cor + imagem de fundo)
    $styles = sprintf(
        'background-color: %s; background-image: url(%s); background-size: cover; background-position: center; min-height: 100vh;',
        esc_attr($options['background_color']),
        esc_url($options['background_image'])
    );

    // Variáveis de estilo individuais
    $title_color = esc_attr($options['title_color']);
    $bio_color = esc_attr($options['bio_color']);
    $button_color = esc_attr($options['button_color']);
    $image_size = absint($options['profile_image_size']);
    $ring_width = absint($options['profile_ring_width']);

    // Início da captura do output HTML
    ob_start();
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html($options['profile_title']); ?></title>
        
        <!-- Tailwind CSS via CDN -->
        <script src="https://cdn.tailwindcss.com"></script>

        <!-- Estilos responsivos personalizados -->
        <style>
            @media (max-width: 640px) {
                .link-in-bio-container {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }
                .link-in-bio-image {
                    width: <?php echo $image_size * 0.8; ?>px !important;
                    height: <?php echo $image_size * 0.8; ?>px !important;
                }
                .link-in-bio-button {
                    padding-top: 0.75rem !important;
                    padding-bottom: 0.75rem !important;
                }
            }
        </style>
    </head>
    <body>
    <!-- Container principal -->
    <div id="link-in-bio-container" style="<?php echo $styles; ?>" class="link-in-bio-container py-8 px-4 text-center flex flex-col items-center justify-center">
        <div class="max-w-md w-full mx-auto">

            <!-- Imagem de perfil -->
            <?php if (!empty($options['profile_image'])) : ?>
                <div class="mx-auto mb-6 link-in-bio-image" style="width: <?php echo $image_size; ?>px; height: <?php echo $image_size; ?>px;">
                    <img src="<?php echo esc_url($options['profile_image']); ?>" 
                         alt="<?php echo esc_attr($options['profile_title']); ?>" 
                         class="rounded-full w-full h-full object-cover"
                         style="border: <?php echo $ring_width; ?>px solid <?php echo $button_color; ?>;">
                </div>
            <?php endif; ?>

            <!-- Título / nome -->
            <h1 class="text-3xl sm:text-4xl font-bold mb-4 px-4" style="color: <?php echo $title_color; ?>;">
                <?php echo esc_html($options['profile_title']); ?>
            </h1>
            
            <!-- Biografia / descrição -->
            <p class="text-lg sm:text-xl mb-8 px-4" style="color: <?php echo $bio_color; ?>;">
                <?php echo esc_html($options['profile_bio']); ?>
            </p>

            <!-- Botões de link personalizados -->
            <div class="flex flex-col gap-4 px-4">
                <?php foreach ($options['links'] as $link) : ?>
                    <a href="<?php echo esc_url($link['url']); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="link-in-bio-button py-3 px-6 rounded-full text-white font-medium transition hover:opacity-90 transform hover:scale-105"
                       style="background-color: <?php echo $button_color; ?>;">
                        <?php echo esc_html($link['title']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Ícones sociais (se existirem) -->
            <?php if (!empty($options['social_links'])) : ?>
                <div class="flex justify-center gap-4 mt-8">
                    <?php foreach ($options['social_links'] as $social) : ?>
                        <a href="<?php echo esc_url($social['url']); ?>" 
                           target="_blank" 
                           rel="noopener noreferrer"
                           class="text-2xl hover:opacity-70 transition"
                           style="color: <?php echo $button_color; ?>;">
                            <?php echo esc_html($social['icon']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </body>
    </html>
    <?php

    // Retorna o HTML capturado
    return ob_get_clean();
}

// Adiciona shortcode [link_in_bio_page] para exibir a página no frontend
add_shortcode('link_in_bio_page', 'link_in_bio_display_links');
