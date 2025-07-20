<?php
/**
 * Template Name: Link in Bio Page (Full Screen)
 * Template Post Type: page
 *
 * Este é um template personalizado do WordPress que:
 * 1. Cria um layout de tela cheia para páginas do tipo 'page'
 * 2. É ativado quando selecionado no editor de páginas do WordPress
 * 3. Projetado para trabalhar com o shortcode [link_in_bio_page]
 *
 * O template não possui estilos próprios, servindo principalmente como container
 * para o shortcode que fará a renderização do conteúdo.
 */
 
// ABSPATH é a constante que define o caminho absoluto para o diretório do WordPress
// Esta verificação previne acesso direto ao arquivo PHP
defined('ABSPATH') || exit;
?>

<!-- 
  DIV Container principal:
  - Classe 'lib-fullscreen-wrapper' pode ser usada para estilização CSS
  - Atua como container flexível para o conteúdo gerado pelo shortcode
  - Não impõe estilos diretamente, permitindo total customização via CSS
-->
<div class="lib-fullscreen-wrapper">
    <?php 
    // do_shortcode() é a função do WordPress que:
    // 1. Interpreta o shortcode [link_in_bio_page]
    // 2. Executa a função associada a esse shortcode
    // 3. Retorna o HTML gerado
    // O shortcode deve estar registrado previamente no functions.php ou em um plugin
    echo do_shortcode('[link_in_bio_page]'); 
    ?>
</div>