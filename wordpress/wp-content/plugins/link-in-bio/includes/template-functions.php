<?php
/**
 * Link in Bio - Template Functions
 *
 * @package LinkInBio
 */

// Impede o acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Exibe a página Link in Bio personalizada.
 *
 * @return string HTML gerado
 */
function link_in_bio_display_links() {
    $options = wp_parse_args(
        get_option('link_in_bio_options', []),
        [
            'links'              => [],
            'profile_image'      => '',
            'profile_title'      => 'Seu Nome ou Marca',
            'profile_bio'        => 'Bem-vindo(a) à minha página de links! Aqui você encontra os principais acessos.',
            'background_color'   => '#f3f4f6',
            'background_image'   => '',
            'title_color'        => '#1f2937',
            'bio_color'          => '#4b5563',
            'button_color'       => '#3b82f6',
            'profile_image_size' => '120',
            'profile_ring_width' => '4',
        ]
    );

    $title       = sanitize_text_field($options['profile_title']);
    $bio         = sanitize_textarea_field($options['profile_bio']);
    $title_color = esc_attr($options['title_color']);
    $bio_color   = esc_attr($options['bio_color']);
    $button_color = esc_attr($options['button_color']);
    $image_size  = max(50, absint($options['profile_image_size']));
    $ring_width  = max(0, absint($options['profile_ring_width']));

    $styles = sprintf('background-color: %s; min-height: 100vh;', esc_attr($options['background_color']));
    if (!empty($options['background_image'])) {
        $styles .= sprintf(' background-image: url(%s); background-size: cover; background-position: center;', esc_url($options['background_image']));
    }

    $description = wp_trim_words($bio, 24, '...');
    $canonical_url = get_permalink();

    ob_start();
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html($title); ?></title>
        <meta name="description" content="<?php echo esc_attr($description); ?>">
        <link rel="canonical" href="<?php echo esc_url($canonical_url); ?>">

        <meta property="og:type" content="profile">
        <meta property="og:title" content="<?php echo esc_attr($title); ?>">
        <meta property="og:description" content="<?php echo esc_attr($description); ?>">
        <meta property="og:url" content="<?php echo esc_url($canonical_url); ?>">

        <?php if (!empty($options['profile_image'])) : ?>
            <meta property="og:image" content="<?php echo esc_url($options['profile_image']); ?>">
        <?php endif; ?>

        <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">

        <style>
            @media (max-width: 640px) {
                .link-in-bio-container {
                    padding-left: 1rem;
                    padding-right: 1rem;
                }
                .link-in-bio-image {
                    width: <?php echo esc_html((string) ($image_size * 0.8)); ?>px !important;
                    height: <?php echo esc_html((string) ($image_size * 0.8)); ?>px !important;
                }
                .link-in-bio-button {
                    padding-top: 0.75rem !important;
                    padding-bottom: 0.75rem !important;
                }
            }
        </style>
    </head>
    <body>
    <main id="link-in-bio-container" style="<?php echo esc_attr($styles); ?>" class="link-in-bio-container py-8 px-4 text-center flex flex-col items-center justify-center">
        <section class="max-w-md w-full mx-auto" aria-label="Link in Bio">

            <?php if (!empty($options['profile_image'])) : ?>
                <div class="mx-auto mb-6 link-in-bio-image" style="width: <?php echo esc_attr((string) $image_size); ?>px; height: <?php echo esc_attr((string) $image_size); ?>px;">
                    <img src="<?php echo esc_url($options['profile_image']); ?>"
                         alt="<?php echo esc_attr($title); ?>"
                         class="rounded-full w-full h-full object-cover"
                         loading="lazy"
                         decoding="async"
                         width="<?php echo esc_attr((string) $image_size); ?>"
                         height="<?php echo esc_attr((string) $image_size); ?>"
                         style="border: <?php echo esc_attr((string) $ring_width); ?>px solid <?php echo esc_attr($button_color); ?>;">
                </div>
            <?php endif; ?>

            <h1 class="text-3xl sm:text-4xl font-bold mb-4 px-4" style="color: <?php echo esc_attr($title_color); ?>;">
                <?php echo esc_html($title); ?>
            </h1>

            <p class="text-lg sm:text-xl mb-8 px-4" style="color: <?php echo esc_attr($bio_color); ?>;">
                <?php echo esc_html($bio); ?>
            </p>

            <div class="flex flex-col gap-4 px-4" role="navigation" aria-label="Links principais">
                <?php if (!empty($options['links']) && is_array($options['links'])) : ?>
                    <?php foreach ($options['links'] as $link) :
                        $link_title = sanitize_text_field($link['title'] ?? '');
                        $link_url   = esc_url($link['url'] ?? '');
                        if (empty($link_title) || empty($link_url)) {
                            continue;
                        }
                        ?>
                        <a href="<?php echo $link_url; ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           aria-label="<?php echo esc_attr(sprintf(__('Abrir link: %s', 'link-in-bio'), $link_title)); ?>"
                           class="link-in-bio-button py-3 px-6 rounded-full text-white font-medium transition hover:opacity-90 transform hover:scale-105"
                           style="background-color: <?php echo esc_attr($button_color); ?>;">
                            <?php echo esc_html($link_title); ?>
                        </a>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p class="text-sm" style="color: <?php echo esc_attr($bio_color); ?>;">
                        <?php esc_html_e('Nenhum link foi adicionado ainda. Configure seus links no painel do WordPress.', 'link-in-bio'); ?>
                    </p>
                <?php endif; ?>
            </div>
        </section>
    </main>
    </body>
    </html>
    <?php

    return ob_get_clean();
}

add_shortcode('link_in_bio_page', 'link_in_bio_display_links');
