<?php
/**
 * Link in Bio - Inicialização do Admin
 * 
 * Arquivo principal de administração do plugin Link in Bio
 * Responsável por toda a interface de configuração no painel WordPress
 *
 * @package LinkInBio
 */

// Segurança: Impede acesso direto ao arquivo
defined('ABSPATH') || exit;

// --- Definição de Constantes ---
// Define o caminho absoluto para o diretório do plugin (usado para includes)
if (!defined('LINK_IN_BIO_PLUGIN_DIR')) {
    define('LINK_IN_BIO_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

// Define a URL base para o diretório do plugin (usado para enfileirar assets)
if (!defined('LINK_IN_BIO_PLUGIN_URL')) {
    define('LINK_IN_BIO_PLUGIN_URL', plugin_dir_url(__FILE__));
}

/**
 * Enfileira scripts e estilos para o painel administrativo
 * 
 * Hook: 'admin_enqueue_scripts' - Acionado quando scripts são carregados no admin
 * 
 * @param string $hook Identificador da página admin atual
 */
function link_in_bio_admin_assets($hook) {
    // Carrega assets apenas na página específica do plugin
    if ($hook !== 'toplevel_page_link_in_bio') {
        return;
    }

    // WordPress Media Uploader (necessário para upload de imagens)
    wp_enqueue_media();

    // Tailwind CSS (via CDN - em produção considere compilar localmente)
    wp_enqueue_script(
        'tailwind-cdn-admin',          // Identificador único
        'https://cdn.tailwindcss.com', // CDN do Tailwind
        [],                            // Sem dependências
        null,                          // Sem versão (CDN sempre atual)
        true                           // No footer (melhor performance)
    );

    // CSS personalizado do admin
    wp_enqueue_style(
        'link-in-bio-admin',                          // Handle
        LINK_IN_BIO_PLUGIN_URL . 'assets/css/admin.css', // URL do arquivo
        [],                                           // Sem dependências
        filemtime(LINK_IN_BIO_PLUGIN_DIR . 'assets/css/admin.css') // Versionamento por timestamp
    );

    // JavaScript principal do admin
    wp_enqueue_script(
        'link-in-bio-admin-js',                       // Handle
        LINK_IN_BIO_PLUGIN_URL . 'assets/js/admin.js', // URL do arquivo
        ['jquery'],                                   // Depende do jQuery
        filemtime(LINK_IN_BIO_PLUGIN_DIR . 'assets/js/admin.js'), // Versionamento
        true                                          // No footer
    );

    // Localização de dados para JavaScript (segurança e URLs)
    wp_localize_script('link-in-bio-admin-js', 'LinkInBioData', [
        'ajax_url' => admin_url('admin-ajax.php'),           // Endpoint AJAX
        'nonce'    => wp_create_nonce('link_in_bio_preview_nonce'), // Token de segurança
    ]);
}
add_action('admin_enqueue_scripts', 'link_in_bio_admin_assets');

/**
 * Classe principal do painel administrativo
 * 
 * Organiza toda a lógica de administração em métodos específicos:
 * - Registro de menus
 * - Configuração de opções
 * - Renderização de campos
 * - Pré-visualização AJAX
 */
class Link_In_Bio_Admin {
    /**
     * Construtor - Registra hooks principais
     */
    public function __construct() {
        // Adiciona item de menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Configura sistema de opções
        add_action('admin_init', [$this, 'init_settings']);
        
        // Configura endpoint AJAX para pré-visualização
        add_action('wp_ajax_link_in_bio_preview', [$this, 'ajax_preview']);
    }

    /**
     * Adiciona página ao menu admin
     * 
     * Usa add_menu_page() para criar item principal
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Link in Bio Settings', 'link-in-bio'), // Título da página
            __('Link in Bio', 'link-in-bio'),          // Texto do menu
            'manage_options',                          // Capability requerida
            'link_in_bio',                             // Slug do menu
            [$this, 'render_settings_page'],           // Callback de renderização
            'dashicons-admin-links',                   // Ícone (Dashicons)
            76                                         // Posição no menu
        );
    }

    /**
     * Configura sistema de opções
     * 
     * Usa Settings API para:
     * - Registrar opções
     * - Adicionar seções
     * - Adicionar campos
     */
    public function init_settings() {
        // Registra opção principal
        register_setting(
            'link_in_bio_settings',       // Grupo de opções
            'link_in_bio_options',        // Nome da opção no banco
            [$this, 'sanitize_options']   // Callback de sanitização
        );

        // --- Seção: Perfil ---
        add_settings_section(
            'link_in_bio_profile_section',     // ID
            __('Configuração de Perfil', 'link-in-bio'), // Título
            [$this, 'render_profile_section_callback'], // Callback
            'link_in_bio_profile_tab'          // Página (tab)
        );
        
        // Campos da seção Perfil
        add_settings_field('profile_image', __('Imagem de Perfil', 'link-in-bio'), [$this, 'render_profile_image_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('profile_image_size', __('Tamanho da Imagem (px)', 'link-in-bio'), [$this, 'render_image_size_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('profile_ring_width', __('Tamanho do anel (px)', 'link-in-bio'), [$this, 'render_ring_width_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('profile_title', __('Título Perfil', 'link-in-bio'), [$this, 'render_profile_title_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('title_color', __('Cor do Título', 'link-in-bio'), [$this, 'render_title_color_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('profile_bio', __('Biografia', 'link-in-bio'), [$this, 'render_profile_bio_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('bio_color', __('Cor da Biografia', 'link-in-bio'), [$this, 'render_bio_color_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');

        // --- Seção: Links ---
        add_settings_section(
            'link_in_bio_main_section',
            __('Configurações de Link', 'link-in-bio'),
            [$this, 'render_main_section_callback'],
            'link_in_bio_links_tab'
        );
        
        // Campos da seção Links
        add_settings_field('links_field', __('Seus Links', 'link-in-bio'), [$this, 'render_links_field'], 'link_in_bio_links_tab', 'link_in_bio_main_section');
        add_settings_field('button_color', __('Cor dos Botões', 'link-in-bio'), [$this, 'render_button_color_field'], 'link_in_bio_links_tab', 'link_in_bio_main_section');

        // --- Seção: Estilo ---
        add_settings_section(
            'link_in_bio_style_section',
            __('Estilos Diversos', 'link-in-bio'),
            [$this, 'render_style_section_callback'],
            'link_in_bio_style_tab'
        );
        
        // Campos da seção Estilo
        add_settings_field('background_color', __('Cor de Fundo', 'link-in-bio'), [$this, 'render_background_color_field'], 'link_in_bio_style_tab', 'link_in_bio_style_section');
        add_settings_field('background_image', __('Imagem de Fundo', 'link-in-bio'), [$this, 'render_background_image_field'], 'link_in_bio_style_tab', 'link_in_bio_style_section');
    }

    /**
     * Sanitiza dados do formulário
     * 
     * @param array $input Dados não sanitizados
     * @return array Dados sanitizados
     */
    public function sanitize_options($input) {
        $sanitized = [];

        // Sanitiza links
        if (isset($input['links']) && is_array($input['links'])) {
            foreach ($input['links'] as $index => $link) {
                $sanitized['links'][$index] = [
                    'title' => sanitize_text_field($link['title'] ?? ''),
                    'url'   => esc_url_raw($link['url'] ?? '')
                ];
            }
        } else {
            $sanitized['links'] = [];
        }

        // Sanitiza campos simples
        $sanitized['profile_image']      = esc_url_raw($input['profile_image'] ?? '');
        $sanitized['profile_title']      = sanitize_text_field($input['profile_title'] ?? '');
        $sanitized['profile_bio']        = sanitize_textarea_field($input['profile_bio'] ?? '');
        $sanitized['profile_image_size'] = absint($input['profile_image_size'] ?? 150);
        $sanitized['profile_ring_width'] = absint($input['profile_ring_width'] ?? 4);

        // Sanitiza cores (hexadecimal)
        $sanitized['title_color']      = sanitize_hex_color($input['title_color'] ?? '#000000');
        $sanitized['bio_color']        = sanitize_hex_color($input['bio_color'] ?? '#666666');
        $sanitized['button_color']     = sanitize_hex_color($input['button_color'] ?? '#7c3aed');
        $sanitized['background_color'] = sanitize_hex_color($input['background_color'] ?? '#ffffff');

        // Sanitiza imagem de fundo
        $sanitized['background_image'] = esc_url_raw($input['background_image'] ?? '');

        return $sanitized;
    }

    /**
     * Callbacks de seção (descrições)
     */
    public function render_profile_section_callback() {
        echo '<p class="description">' . __('Configure a imagem, título e biografia para o seu perfil.', 'link-in-bio') . '</p>';
    }

    public function render_main_section_callback() {
        echo '<p class="description">' . __('Adicione e organize os links que aparecerão em sua página.', 'link-in-bio') . '</p>';
    }

    public function render_style_section_callback() {
        echo '<p class="description">' . __('Personalize as cores e imagens de fundo da sua página de links.', 'link-in-bio') . '</p>';
    }

    /**
     * Renderiza página principal de configurações
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Link in Bio - Configurações', 'link-in-bio'); ?></h1>

            <div class="flex flex-wrap gap-8 items-start">
                <!-- Formulário de Configurações -->
                <div class="flex-1 min-w-[300px]">
                    <form method="post" action="options.php" id="link-in-bio-settings-form" class="bg-white p-6 rounded-lg shadow-md">
                        <?php settings_fields('link_in_bio_settings'); ?>

                        <!-- Abas de Navegação -->
                        <h2 class="nav-tab-wrapper">
                            <a href="#tab-profile" class="nav-tab nav-tab-active"><?php _e('Perfil', 'link-in-bio'); ?></a>
                            <a href="#tab-links" class="nav-tab"><?php _e('Links', 'link-in-bio'); ?></a>
                            <a href="#tab-style" class="nav-tab"><?php _e('Estilo', 'link-in-bio'); ?></a>
                        </h2>

                        <!-- Conteúdo das Abas -->
                        <div class="tab-content" id="tab-profile">
                            <?php do_settings_sections('link_in_bio_profile_tab'); ?>
                        </div>

                        <div class="tab-content" id="tab-links" style="display:none;">
                            <?php do_settings_sections('link_in_bio_links_tab'); ?>
                        </div>

                        <div class="tab-content" id="tab-style" style="display:none;">
                            <?php do_settings_sections('link_in_bio_style_tab'); ?>
                        </div>

                        <?php submit_button(__('Salvar Alterações', 'link-in-bio'), 'primary mt-4'); ?>
                    </form>
                </div>

                <!-- Área de Pré-visualização -->
                <div id="link-in-bio-preview" class="flex-1 min-w-[300px] p-4 border border-gray-200 rounded-md bg-gray-50 shadow-md">
                    <h2 class="text-xl font-semibold mb-3 text-gray-800"><?php _e('Pré-visualização Ao Vivo', 'link-in-bio'); ?></h2>
                    <div id="link-in-bio-preview-content">
                        <?php echo do_shortcode('[link_in_bio_page]'); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Atualiza pré-visualização
     */
    public function ajax_preview() {
        // Verifica nonce de segurança
        check_ajax_referer('link_in_bio_preview_nonce', 'nonce');

        // Obtém dados do formulário
        $formData = isset($_POST['formData']) ? wp_unslash($_POST['formData']) : '';
        parse_str($formData, $form_data);

        // Sanitiza dados
        $options_from_form = $this->sanitize_options($form_data['link_in_bio_options'] ?? []);

        // Filtro temporário para pré-visualização
        add_filter('pre_option_link_in_bio_options', function ($value) use ($options_from_form) {
            return $options_from_form;
        });

        // Gera pré-visualização
        echo do_shortcode('[link_in_bio_page]');

        wp_die(); // Finaliza requisição AJAX
    }

    // Métodos de renderização de campos (cada um corresponde a um campo do formulário)
    public function render_profile_image_field() {
        $options = get_option('link_in_bio_options');
        $image_url = $options['profile_image'] ?? '';
        ?>
        <div class="link-in-bio-profile-image flex items-center gap-2">
            <input type="hidden" name="link_in_bio_options[profile_image]" id="link_in_bio_profile_image" value="<?php echo esc_url($image_url); ?>">
            <button type="button" class="button button-secondary" id="link-in-bio-upload-image"><?php _e('Selecionar Imagem', 'link-in-bio'); ?></button>
            <button type="button" class="button button-danger bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md transition duration-150" id="link-in-bio-remove-image" <?php echo empty($image_url) ? ' style="display:none;"' : ''; ?>>
                <?php _e('Remover Imagem', 'link-in-bio'); ?>
            </button>
            <div id="link-in-bio-image-preview" class="ml-4">
                <?php if (!empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" class="max-w-[100px] h-auto rounded-full object-cover shadow-sm">
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public function render_image_size_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="number" name="link_in_bio_options[profile_image_size]" value="<?php echo esc_attr($options['profile_image_size'] ?? 150); ?>" min="50" max="400" class="regular-text">
        <p class="description"><?php _e('Defina o tamanho da sua imagem de perfil em pixels (ex: 150).', 'link-in-bio'); ?></p>
        <?php
    }

    public function render_ring_width_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="number" name="link_in_bio_options[profile_ring_width]" value="<?php echo esc_attr($options['profile_ring_width'] ?? 4); ?>" min="0" max="20" class="regular-text">
        <p class="description"><?php _e('Defina a largura da borda em torno da sua imagem de perfil em pixels (0 para sem borda).', 'link-in-bio'); ?></p>
        <?php
    }

    public function render_profile_title_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="text" name="link_in_bio_options[profile_title]" value="<?php echo esc_attr($options['profile_title'] ?? ''); ?>" class="regular-text widefat">
        <p class="description"><?php _e('Digite seu nome ou título da marca.', 'link-in-bio'); ?></p>
        <?php
    }

    public function render_title_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[title_color]" value="<?php echo esc_attr($options['title_color'] ?? '#000000'); ?>">
        <p class="description"><?php _e('Escolha a cor para o título do seu perfil.', 'link-in-bio'); ?></p>
        <?php
    }

    public function render_profile_bio_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <textarea name="link_in_bio_options[profile_bio]" rows="4" class="large-text code"><?php echo esc_textarea($options['profile_bio'] ?? ''); ?></textarea>
        <p class="description"><?php _e('Escreva uma breve biografia para o seu perfil.', 'link-in-bio'); ?></p>
        <?php
    }

    public function render_bio_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[bio_color]" value="<?php echo esc_attr($options['bio_color'] ?? '#666666'); ?>">
        <p class="description"><?php _e('Escolha a cor para a biografia do seu perfil.', 'link-in-bio'); ?></p>
        <?php
    }

    public function render_links_field() {
        $options = get_option('link_in_bio_options');
        $links = $options['links'] ?? [];
        ?>
        <div id="link-in-bio-links-container" class="space-y-4 mb-4">
            <?php if (!empty($links)) : ?>
                <?php foreach ($links as $index => $link) : ?>
                    <div class="link-in-bio-link p-4 border border-gray-200 rounded-md bg-gray-50 flex flex-wrap items-center gap-2 sm:gap-4">
                        <div class="flex-grow">
                            <label for="link-title-<?php echo $index; ?>" class="sr-only"><?php _e('Título do Link', 'link-in-bio'); ?></label>
                            <input type="text" id="link-title-<?php echo $index; ?>" name="link_in_bio_options[links][<?php echo $index; ?>][title]" value="<?php echo esc_attr($link['title']); ?>" placeholder="<?php _e('Título do Link', 'link-in-bio'); ?>" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm p-2">
                        </div>
                        <div class="flex-grow">
                            <label for="link-url-<?php echo $index; ?>" class="sr-only"><?php _e('URL do Link', 'link-in-bio'); ?></label>
                            <input type="url" id="link-url-<?php echo $index; ?>" name="link_in_bio_options[links][<?php echo $index; ?>][url]" value="<?php echo esc_url($link['url']); ?>" placeholder="https://example.com" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 sm:text-sm p-2">
                        </div>
                        <div class="flex-shrink-0">
                            <button type="button" class="button button-danger bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md transition duration-150 link-in-bio-remove">
                                <?php _e('Remover', 'link-in-bio'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <button type="button" id="link-in-bio-add" class="button button-primary bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded-md transition duration-150 mt-4">
            <?php _e('Adicionar Novo Link', 'link-in-bio'); ?>
        </button>
        <?php
    }

    public function render_button_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[button_color]" value="<?php echo esc_attr($options['button_color'] ?? '#7c3aed'); ?>">
        <p class="description"><?php _e('Escolha a cor de fundo para os botões dos seus links.', 'link-in-bio'); ?></p>
        <?php
    }

    public function render_background_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[background_color]" value="<?php echo esc_attr($options['background_color'] ?? '#ffffff'); ?>">
        <p class="description"><?php _e('Escolha a cor de fundo para toda a sua página Link in Bio.', 'link-in-bio'); ?></p>
        <?php
    }

    public function render_background_image_field() {
        $options = get_option('link_in_bio_options');
        $image_url = $options['background_image'] ?? '';
        ?>
        <div class="link-in-bio-background-image flex items-center gap-2">
            <input type="hidden" name="link_in_bio_options[background_image]" id="link_in_bio_background_image" value="<?php echo esc_url($image_url); ?>">
            <button type="button" class="button button-secondary" id="upload-background-image"><?php _e('Selecionar Imagem', 'link-in-bio'); ?></button>
            <button type="button" class="button button-danger bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-md transition duration-150" id="remove-background-image" <?php echo empty($image_url) ? ' style="display:none;"' : ''; ?>>
                <?php _e('Remover Imagem', 'link-in-bio'); ?>
            </button>
            <div id="link-in-bio-background-preview" class="ml-4">
                <?php if (!empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" class="max-w-[150px] h-auto shadow-sm">
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}

// Inicializa a classe principal do admin
new Link_In_Bio_Admin();