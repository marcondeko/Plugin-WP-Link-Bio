<?php
/**
 * Link in Bio Uninstall
 *
 * Script de desinstalação para o plugin Link in Bio.
 * Este script é executado quando o usuário clica em "Excluir" no painel de plugins do WordPress.
 *
 * @package LinkInBio
 * @version 1.1.0
 */

// Garante que o script não seja chamado diretamente.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// --- Limpeza de Opções ---

// Nome da opção a ser removida do banco de dados.
$option_name = 'link_in_bio_options';

// Remove a opção do banco de dados.
delete_option($option_name);

// Para instalações multisite, você pode querer remover a opção de todos os sites.
// delete_site_option($option_name);


// --- Limpeza de Conteúdo (Páginas) ---

// Obtém as opções do plugin para encontrar o slug da página.
$options = get_option('link_in_bio_options');
$page_slug = !empty($options['page_slug']) ? $options['page_slug'] : 'meus-links';

// Busca a página pelo seu slug.
$page_object = get_page_by_path($page_slug, OBJECT, 'page');

// Se a página existir, remove permanentemente.
if ($page_object) {
    // O `true` no final força a exclusão permanente (sem passar pela lixeira).
    wp_delete_post($page_object->ID, true);
}

// --- Limpeza de Metadados (Opcional) ---
// Se o plugin salvasse metadados de posts, usuários ou comentários,
// a lógica para removê-los estaria aqui.
// Exemplo: delete_post_meta_by_key('_meu_meta_key');
