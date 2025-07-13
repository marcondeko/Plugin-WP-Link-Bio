<?php
/**
 * Plugin Name: Link in Bio
 * Plugin URI: https://diegomarcondes.com/plugin/link-in-bio
 * Description: Crie uma página elegante com seus links importantes no estilo "Link in Bio".
 * Version: 1.0.0
 * Author: Diego Marcondes
 * Author URI: https://diegomarcondes.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: link-in-bio
 */

defined('ABSPATH') || exit;

// Constantes
define('LINK_IN_BIO_VERSION', '1.0.0');
define('LINK_IN_BIO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LINK_IN_BIO_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Ativação
 */
function link_in_bio_activate() {
    global $wp_version;

    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    if (!function_exists('get_page_by_path')) {
        require_once ABSPATH . 'wp-admin/includes/post.php';
    }

    if (version_compare($wp_version, '5.0', '<')) {
        deactivate_plugins(basename(__FILE__));
        wp_die(__('O plugin Link in Bio requer WordPress 5.0 ou superior.', 'link-in-bio'));
    }

    link_in_bio_create_page();
}
register_activation_hook(__FILE__, 'link_in_bio_activate');

/**
 * Desativação
 */
function link_in_bio_deactivate() {
    if (!function_exists('wp_delete_post')) {
        require_once ABSPATH . 'wp-admin/includes/post.php';
    }
    if (!function_exists('get_page_by_path')) {
        require_once ABSPATH . 'wp-admin/includes/post.php';
    }

    $existing_page = get_page_by_path('meus-links');

    if ($existing_page) {
        wp_delete_post($existing_page->ID, false);
    }
}
register_deactivation_hook(__FILE__, 'link_in_bio_deactivate');

/**
 * Criação da página "Meus Links"
 */
function link_in_bio_create_page() {
    $slug = 'meus-links';
    $existing = get_page_by_path($slug);

    if (!$existing) {
        $page_id = wp_insert_post([
            'post_title'   => __('Meus Links', 'link-in-bio'),
            'post_name'    => $slug,
            'post_content' => '[link_in_bio_page]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        ]);

        if (!is_wp_error($page_id)) {
            update_post_meta($page_id, '_wp_page_template', 'link-in-bio-template.php');
        }
    }
}

/**
 * Inicialização
 */
function link_in_bio_init() {
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
 * Estilos
 */
function link_in_bio_assets() {
    $css = LINK_IN_BIO_PLUGIN_DIR . 'assets/style.css';
    if (file_exists($css)) {
        wp_enqueue_style(
            'link-in-bio-style',
            LINK_IN_BIO_PLUGIN_URL . 'assets/style.css',
            [],
            filemtime($css)
        );
    }
}
add_action('wp_enqueue_scripts', 'link_in_bio_assets');

/**
 * Torna o template disponível no seletor do editor de páginas
 */
add_filter('theme_page_templates', function($templates) {
    $templates['link-in-bio-template.php'] = __('Link in Bio Page (Full Screen)', 'link-in-bio');
    return $templates;
});

/**
 * Diz ao WP onde está o template se ele for usado
 */
add_filter('template_include', function($template) {
    if (is_page()) {
        $selected = get_page_template_slug(get_queried_object_id());
        if ($selected === 'link-in-bio-template.php') {
            $plugin_template = LINK_IN_BIO_PLUGIN_DIR . 'templates/link-in-bio-template.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
    }
    return $template;
});
