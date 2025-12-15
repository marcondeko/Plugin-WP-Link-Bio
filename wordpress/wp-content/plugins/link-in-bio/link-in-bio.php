<?php
/**
 * Plugin Name: Link in Bio
 * Plugin URI: https://diegomarcondes.com/plugin/link-in-bio
 * Description: Crie uma página elegante com seus links importantes no estilo "Link in Bio".
 * Version: 1.0.0
 * Author: Diego Marcondes
 * Author URI: https://diegomarcondes.com
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 * Text Domain: link-in-bio
 */

defined('ABSPATH') || exit; // Impede acesso direto ao arquivo

// Define constantes úteis para reutilização
define('LINK_IN_BIO_VERSION', '1.0.0');
define('LINK_IN_BIO_PLUGIN_DIR', plugin_dir_path(__FILE__));   // Caminho absoluto do plugin
define('LINK_IN_BIO_PLUGIN_URL', plugin_dir_url(__FILE__));    // URL base do plugin

/**
 * Função executada na ativação do plugin.
 * - Verifica a versão mínima do WordPress.
 * - Cria a página "Meus Links" com shortcode e aplica um template customizado.
 */
function link_in_bio_activate() {
    global $wp_version;

    // Garante que as funções necessárias estão carregadas
    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    if (!function_exists('get_page_by_path')) {
        require_once ABSPATH . 'wp-admin/includes/post.php';
    }

    // Verifica a versão do WP
    if (version_compare($wp_version, '5.0', '<')) {
        deactivate_plugins(basename(__FILE__));
        wp_die(__('O plugin Link in Bio requer WordPress 5.0 ou superior.', 'link-in-bio'));
    }

    // Cria a página
    link_in_bio_create_page();
}
register_activation_hook(__FILE__, 'link_in_bio_activate');

/**
 * Função executada na desativação do plugin.
 * - A desativação não remove mais dados para evitar perda acidental.
 *   A remoção de dados foi movida para o arquivo uninstall.php.
 */
function link_in_bio_deactivate() {
    // Não faz nada na desativação para proteger os dados do usuário.
}
register_deactivation_hook(__FILE__, 'link_in_bio_deactivate');

/**
 * Obtém o slug da página a partir das opções salvas.
 *
 * @return string O slug da página.
 */
function link_in_bio_get_page_slug() {
    $options = get_option('link_in_bio_options');
    return !empty($options['page_slug']) ? $options['page_slug'] : 'meus-links';
}

/**
 * Cria a página "Meus Links" se ela ainda não existir.
 * - Define o conteúdo como um shortcode.
 * - Aplica o template customizado ao criar.
 */
function link_in_bio_create_page() {
    $slug = link_in_bio_get_page_slug();
    $existing = get_page_by_path($slug);

    // Se não existir, cria
    if (!$existing) {
        $page_id = wp_insert_post([
            'post_title'     => __('Meus Links', 'link-in-bio'),
            'post_name'      => $slug,
            'post_content'   => '[link_in_bio_page]',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
            'ping_status'    => 'closed'
        ]);

        // Define o template da página
        if (!is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'link-in-bio-template.php');
        }
    }
}

/**
 * Inicializa o plugin após o carregamento do WordPress.
 * - Carrega as funções de template.
 * - Carrega o painel de administração, se necessário.
 */
function link_in_bio_init() {
    // Carrega o text domain para traduções
    load_plugin_textdomain('link-in-bio', false, dirname(plugin_basename(__FILE__)) . '/languages');

    $functions = LINK_IN_BIO_PLUGIN_DIR . 'includes/template-functions.php';
    if (file_exists($functions)) {
        require_once $functions;
    }

    if (is_admin()) {
        $admin = LINK_IN_BIO_PLUGIN_DIR . 'admin/admin-init.php';
        if (file_exists($admin)) {
            require_once $admin;
        }
    }
}
add_action('plugins_loaded', 'link_in_bio_init');

/**
 * Enfileira os estilos do plugin no front-end.
 * - Garante cache busting com `filemtime`.
 */
function link_in_bio_assets() {
    // Enfileira o estilo principal do front-end
    $style_path = 'assets/css/style.css';
    if (file_exists(LINK_IN_BIO_PLUGIN_DIR . $style_path)) {
        wp_enqueue_style(
            'link-in-bio-style',
            LINK_IN_BIO_PLUGIN_URL . $style_path,
            [],
            filemtime(LINK_IN_BIO_PLUGIN_DIR . $style_path)
        );
    }
}
add_action('wp_enqueue_scripts', 'link_in_bio_assets');

/**
 * Adiciona o template customizado à lista de templates do editor de páginas.
 */
add_filter('theme_page_templates', function($templates) {
    $templates['link-in-bio-template.php'] = __('Link in Bio Page (Full Screen)', 'link-in-bio');
    return $templates;
});

/**
 * Diz ao WordPress onde encontrar o template customizado se ele for selecionado.
 */
add_filter('template_include', function($template) {
    // Se não for uma página, não faz nada
    if (!is_page()) {
        return $template;
    }

    // Obtém o slug do template da página atual
    $template_slug = get_page_template_slug();

    // Se o template selecionado for o nosso, carrega o arquivo do plugin
    if ($template_slug === 'templates/link-in-bio-template.php' || $template_slug === 'link-in-bio-template.php') {
        $plugin_template = LINK_IN_BIO_PLUGIN_DIR . 'templates/link-in-bio-template.php';

        // Retorna nosso template apenas se ele existir
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }

    return $template;
});
