<?php
/**
 * Link in Bio - Inicialização do Admin
 *
 * @package LinkInBio
 */

// Garante que o script não seja acessado diretamente. Se for, termina a execução.
defined('ABSPATH') || exit;

// --- Definição de Constantes ---
// Define o caminho absoluto para o diretório do plugin.
if (!defined('LINK_IN_BIO_PLUGIN_DIR')) {
    define('LINK_IN_BIO_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
// Define a URL base para o diretório do plugin.
if (!defined('LINK_IN_BIO_PLUGIN_URL')) {
    define('LINK_IN_BIO_PLUGIN_URL', plugin_dir_url(__FILE__));
}

/**
 * Enfileira os scripts e estilos necessários para o painel administrativo.
 * Esta função é acionada pelo hook 'admin_enqueue_scripts'.
 *
 * @param string $hook O nome da página de administração atual.
 */
function link_in_bio_admin_assets($hook) {
    // Carrega os assets (CSS/JS) apenas na nossa página de administração específica.
    // 'toplevel_page_link_in_bio' é o hook gerado pelo add_menu_page com o slug 'link_in_bio'.
    if ($hook !== 'toplevel_page_link_in_bio') {
        return;
    }

    // Enfileira o Media Uploader do WordPress. Essencial para o upload de imagens.
    wp_enqueue_media();

    // Enfileira o Tailwind CSS CDN para estilização rápida no admin.
    // Em um ambiente de produção real, seria mais otimizado compilar o Tailwind para um arquivo CSS local.
    wp_enqueue_script(
        'tailwind-cdn-admin',          // Handle (nome) do script
        'https://cdn.tailwindcss.com', // URL do script
        [],                            // Sem dependências diretas para o CDN
        null,                          // Sem número de versão específico
        true                           // Carrega o script no rodapé (melhor para performance)
    );

    // Enfileira o arquivo CSS de estilos personalizados do admin.
    wp_enqueue_style(
        'link-in-bio-admin',                          // Handle (nome) do estilo
        LINK_IN_BIO_PLUGIN_URL . 'assets/css/admin.css', // URL do arquivo CSS
        [],                                           // Sem dependências
        filemtime(LINK_IN_BIO_PLUGIN_DIR . 'assets/css/admin.css') // Versão baseada na data de modificação do arquivo
    );

    // Enfileira o arquivo JavaScript principal do admin.
    wp_enqueue_script(
        'link-in-bio-admin-js',                       // Handle (nome) do script
        LINK_IN_BIO_PLUGIN_URL . 'assets/js/admin.js', // URL do arquivo JS
        ['jquery'],                                   // Depende do jQuery (assegura que jQuery seja carregado primeiro)
        filemtime(LINK_IN_BIO_PLUGIN_DIR . 'assets/js/admin.js'), // Versão baseada na data de modificação do arquivo
        true                                          // Carrega o script no rodapé
    );

    // Localiza dados (variáveis PHP) para serem usados pelo script JavaScript.
    // Isso permite que o JS acesse a URL do AJAX do WordPress e um nonce de segurança.
    wp_localize_script('link-in-bio-admin-js', 'LinkInBioData', [
        'ajax_url' => admin_url('admin-ajax.php'),           // URL para requisições AJAX do WordPress
        'nonce'    => wp_create_nonce('link_in_bio_preview_nonce'), // Nonce de segurança para validação AJAX
    ]);
}
// Adiciona a função ao hook do WordPress que enfileira scripts para o admin.
add_action('admin_enqueue_scripts', 'link_in_bio_admin_assets');

/**
 * Classe principal para gerenciar o painel administrativo do plugin Link in Bio.
 * Esta classe encapsula toda a lógica relacionada à interface de administração.
 */
class Link_In_Bio_Admin {
    /**
     * Construtor da classe.
     * Registra os hooks de ação do WordPress.
     */
    public function __construct() {
        // Hook para adicionar a página de configurações ao menu do admin.
        add_action('admin_menu', [$this, 'add_admin_menu']);
        // Hook para inicializar as configurações do plugin.
        add_action('admin_init', [$this, 'init_settings']);
        // Hook para lidar com a requisição AJAX de pré-visualização.
        // wp_ajax_ (para usuários logados) + nome da action no JS.
        add_action('wp_ajax_link_in_bio_preview', [$this, 'ajax_preview']);
    }

    /**
     * Adiciona a página de configurações do plugin ao menu administrativo do WordPress.
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Link in Bio Settings', 'link-in-bio'), // Título que aparece na tag <title> da página
            __('Link in Bio', 'link-in-bio'),          // Título que aparece no menu lateral
            'manage_options',                          // Capacidade mínima necessária para acessar a página (gerenciar opções)
            'link_in_bio',                             // Slug único da página do menu
            [$this, 'render_settings_page'],           // Função de callback que renderiza o conteúdo da página
            'dashicons-admin-links',                   // Ícone do Dashicons para o menu
            76                                         // Posição no menu (ajuste conforme necessário)
        );
    }

    /**
     * Inicializa as configurações, seções e campos do formulário usando a Settings API do WordPress.
     * Isso organiza os campos do formulário em abas lógicas.
     */
    public function init_settings() {
        // Registra o grupo de opções principal para o plugin.
        // 'link_in_bio_settings' é o nome do grupo, 'link_in_bio_options' é o nome da opção no banco de dados.
        register_setting('link_in_bio_settings', 'link_in_bio_options', [$this, 'sanitize_options']);

        // --- Seções e campos para a aba 'Perfil' ---
        // Adiciona uma seção para as configurações de perfil.
        add_settings_section(
            'link_in_bio_profile_section',     // ID único da seção
            __('Configuração de Perfil', 'link-in-bio'), // Título da seção
            [$this, 'render_profile_section_callback'], // Callback para a descrição da seção
            'link_in_bio_profile_tab'          // "Página" de configurações à qual esta seção pertence (aba de perfil)
        );
        // Adiciona campos de configuração à seção de perfil.
        add_settings_field('profile_image', __('Imagem de Perfil', 'link-in-bio'), [$this, 'render_profile_image_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('profile_image_size', __('Tamanho da Imagem de Perfil (px)', 'link-in-bio'), [$this, 'render_image_size_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('profile_ring_width', __('Tamanho do anel em torno da imagem de perfil (px)', 'link-in-bio'), [$this, 'render_ring_width_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('profile_title', __('Título Perfil', 'link-in-bio'), [$this, 'render_profile_title_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('title_color', __('Cor do Título ', 'link-in-bio'), [$this, 'render_title_color_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('profile_bio', __('Biografia', 'link-in-bio'), [$this, 'render_profile_bio_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');
        add_settings_field('bio_color', __('Cor da Biografia', 'link-in-bio'), [$this, 'render_bio_color_field'], 'link_in_bio_profile_tab', 'link_in_bio_profile_section');

        // --- Seções e campos para a aba 'Links' ---
        // Adiciona uma seção para as configurações de links.
        add_settings_section(
            'link_in_bio_main_section',
            __('Configurações de Link', 'link-in-bio'),
            [$this, 'render_main_section_callback'],
            'link_in_bio_links_tab' // Aba de links
        );
        // Adiciona campos de configuração à seção de links.
        add_settings_field('links_field', __('Seus Links', 'link-in-bio'), [$this, 'render_links_field'], 'link_in_bio_links_tab', 'link_in_bio_main_section');
        add_settings_field('button_color', __('Cor dos Botões de Link', 'link-in-bio'), [$this, 'render_button_color_field'], 'link_in_bio_links_tab', 'link_in_bio_main_section');

        // --- Seções e campos para a aba 'Estilo' ---
        // Adiciona uma seção para as configurações de estilo visual.
        add_settings_section(
            'link_in_bio_style_section',
            __('Estilos Diversos', 'link-in-bio'),
            [$this, 'render_style_section_callback'],
            'link_in_bio_style_tab' // Aba de estilo
        );
        // Adiciona campos de configuração à seção de estilo.
        add_settings_field('background_color', __('Cor de Fundo da Página', 'link-in-bio'), [$this, 'render_background_color_field'], 'link_in_bio_style_tab', 'link_in_bio_style_section');
        add_settings_field('background_image', __('Imagem de Fundo da Página', 'link-in-bio'), [$this, 'render_background_image_field'], 'link_in_bio_style_tab', 'link_in_bio_style_section');
    }

    /**
     * Sanitiza e valida as opções do plugin antes de serem salvas no banco de dados.
     * Esta função é o callback de 'register_setting'.
     *
     * @param array $input As opções submetidas pelo formulário.
     * @return array As opções sanitizadas.
     */
    public function sanitize_options($input) {
        $sanitized = []; // Array para armazenar as opções limpas.

        // Sanitiza a lista de links.
        if (isset($input['links']) && is_array($input['links'])) {
            foreach ($input['links'] as $index => $link) {
                $sanitized['links'][$index] = [
                    'title' => sanitize_text_field($link['title'] ?? ''), // Limpa o texto do título
                    'url'   => esc_url_raw($link['url'] ?? '')            // Limpa e valida a URL
                ];
            }
        } else {
            $sanitized['links'] = []; // Garante que 'links' seja um array vazio se nada for enviado.
        }

        // Sanitiza campos de perfil.
        $sanitized['profile_image']      = esc_url_raw($input['profile_image'] ?? '');       // URL da imagem de perfil
        $sanitized['profile_title']      = sanitize_text_field($input['profile_title'] ?? ''); // Título do perfil
        $sanitized['profile_bio']        = sanitize_textarea_field($input['profile_bio'] ?? ''); // Biografia do perfil
        $sanitized['profile_image_size'] = absint($input['profile_image_size'] ?? 150);     // Tamanho da imagem (número inteiro positivo)
        $sanitized['profile_ring_width'] = absint($input['profile_ring_width'] ?? 4);       // Largura do anel (número inteiro positivo)

        // Sanitiza campos de cor, garantindo que estejam no formato hexadecimal correto.
        $sanitized['title_color']      = sanitize_hex_color($input['title_color'] ?? '#000000');   // Cor do título
        $sanitized['bio_color']        = sanitize_hex_color($input['bio_color'] ?? '#666666');     // Cor da biografia
        $sanitized['button_color']     = sanitize_hex_color($input['button_color'] ?? '#7c3aed');  // Cor do botão
        $sanitized['background_color'] = sanitize_hex_color($input['background_color'] ?? '#ffffff'); // Cor de fundo

        // Sanitiza a imagem de fundo.
        $sanitized['background_image'] = esc_url_raw($input['background_image'] ?? ''); // URL da imagem de fundo

        return $sanitized; // Retorna o array de opções sanitizadas.
    }

    /**
     * Callback para exibir uma descrição na seção de Configurações de Perfil.
     */
    public function render_profile_section_callback() {
        echo '<p class="description">' . __('Configure a imagem, título e biografia para o seu perfil.', 'link-in-bio') . '</p>';
    }

    /**
     * Callback para exibir uma descrição na seção de Configurações de Links.
     */
    public function render_main_section_callback() {
        echo '<p class="description">' . __('Adicione e organize os links que aparecerão em sua página.', 'link-in-bio') . '</p>';
    }

    /**
     * Callback para exibir uma descrição na seção de Estilo Visual.
     */
    public function render_style_section_callback() {
        echo '<p class="description">' . __('Personalize as cores e imagens de fundo da sua página de links.', 'link-in-bio') . '</p>';
    }

    /**
     * Renderiza a página completa de configurações do plugin, incluindo as abas e a pré-visualização.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Link in Bio - Configurações', 'link-in-bio'); ?></h1>

            <div class="flex flex-wrap gap-8 items-start">
                <div class="flex-1 min-w-[300px]">
                    <form method="post" action="options.php" id="link-in-bio-settings-form" class="bg-white p-6 rounded-lg shadow-md">
                        <?php settings_fields('link_in_bio_settings'); // Inclui campos ocultos necessários para salvar as opções ?>

                        <h2 class="nav-tab-wrapper">
                            <a href="#tab-profile" class="nav-tab nav-tab-active"><?php _e('Perfil', 'link-in-bio'); ?></a>
                            <a href="#tab-links" class="nav-tab"><?php _e('Links', 'link-in-bio'); ?></a>
                            <a href="#tab-style" class="nav-tab"><?php _e('Estilo', 'link-in-bio'); ?></a>
                        </h2>

                        <div class="tab-content" id="tab-profile">
                            <?php do_settings_sections('link_in_bio_profile_tab'); // Renderiza todas as seções e campos registrados para esta aba ?>
                        </div>

                        <div class="tab-content" id="tab-links" style="display:none;">
                            <?php do_settings_sections('link_in_bio_links_tab'); // Renderiza todas as seções e campos registrados para esta aba ?>
                        </div>

                        <div class="tab-content" id="tab-style" style="display:none;">
                            <?php do_settings_sections('link_in_bio_style_tab'); // Renderiza todas as seções e campos registrados para esta aba ?>
                        </div>

                        <?php submit_button(__('Salvar Alterações', 'link-in-bio'), 'primary mt-4'); // Botão de envio do formulário ?>
                    </form>
                </div>

                <div id="link-in-bio-preview" class="flex-1 min-w-[300px] p-4 border border-gray-200 rounded-md bg-gray-50 shadow-md">
                    <h2 class="text-xl font-semibold mb-3 text-gray-800"><?php _e('Pré-visualização Ao Vivo', 'link-in-bio'); ?></h2>
                    <div id="link-in-bio-preview-content">
                        <?php
                        // No carregamento inicial da página, exibe a pré-visualização usando as opções salvas.
                        // As atualizações dinâmicas serão feitas via AJAX pelo JavaScript.
                        echo do_shortcode('[link_in_bio_page]');
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Callback AJAX para atualizar o conteúdo da pré-visualização dinamicamente.
     * Esta função é acionada por uma requisição AJAX do JavaScript.
     */
    public function ajax_preview() {
        // Verifica o nonce de segurança para prevenir ataques CSRF.
        check_ajax_referer('link_in_bio_preview_nonce', 'nonce');

        // Obtém os dados do formulário serializados enviados via POST.
        $formData = isset($_POST['formData']) ? wp_unslash($_POST['formData']) : '';
        // Desserializa a string de dados do formulário em um array PHP.
        parse_str($formData, $form_data);

        // Sanitiza os dados recebidos do formulário usando a mesma função de sanitização
        // que é usada para salvar as opções, garantindo segurança e consistência.
        $options_from_form = $this->sanitize_options($form_data['link_in_bio_options'] ?? []);

        // Temporariamente substitui as opções salvas no banco de dados pelas opções
        // enviadas pelo formulário (que ainda não foram salvas). Isso faz com que
        // o shortcode '[link_in_bio_page]' utilize essas novas opções para a pré-visualização.
        add_filter('pre_option_link_in_bio_options', function ($value) use ($options_from_form) {
            return $options_from_form;
        });

        // Executa o shortcode '[link_in_bio_page]' e imprime seu HTML.
        // Este HTML será então inserido na div de pré-visualização no navegador.
        echo do_shortcode('[link_in_bio_page]');

        wp_die(); // É crucial usar wp_die() no final de qualquer callback AJAX do WordPress.
    }

    /**
     * Renderiza o campo de upload e pré-visualização da imagem de perfil.
     */
    public function render_profile_image_field() {
        $options = get_option('link_in_bio_options'); // Obtém as opções salvas.
        $image_url = $options['profile_image'] ?? ''; // URL da imagem de perfil.
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

    /**
     * Renderiza o campo de entrada para o tamanho da imagem de perfil.
     */
    public function render_image_size_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="number" name="link_in_bio_options[profile_image_size]" value="<?php echo esc_attr($options['profile_image_size'] ?? 150); ?>" min="50" max="400" class="regular-text">
        <p class="description"><?php _e('Defina o tamanho da sua imagem de perfil em pixels (ex: 150).', 'link-in-bio'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de entrada para a espessura do anel em volta da imagem de perfil.
     */
    public function render_ring_width_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="number" name="link_in_bio_options[profile_ring_width]" value="<?php echo esc_attr($options['profile_ring_width'] ?? 4); ?>" min="0" max="20" class="regular-text">
        <p class="description"><?php _e('Defina a largura da borda em torno da sua imagem de perfil em pixels (0 para sem borda).', 'link-in-bio'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de entrada para o título do perfil.
     */
    public function render_profile_title_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="text" name="link_in_bio_options[profile_title]" value="<?php echo esc_attr($options['profile_title'] ?? ''); ?>" class="regular-text widefat">
        <p class="description"><?php _e('Digite seu nome ou título da marca.', 'link-in-bio'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de seleção de cor para o título do perfil.
     */
    public function render_title_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[title_color]" value="<?php echo esc_attr($options['title_color'] ?? '#000000'); ?>">
        <p class="description"><?php _e('Escolha a cor para o título do seu perfil.', 'link-in-bio'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de área de texto para a biografia do perfil.
     */
    public function render_profile_bio_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <textarea name="link_in_bio_options[profile_bio]" rows="4" class="large-text code"><?php echo esc_textarea($options['profile_bio'] ?? ''); ?></textarea>
        <p class="description"><?php _e('Escreva uma breve biografia para o seu perfil.', 'link-in-bio'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de seleção de cor para a biografia do perfil.
     */
    public function render_bio_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[bio_color]" value="<?php echo esc_attr($options['bio_color'] ?? '#666666'); ?>">
        <p class="description"><?php _e('Escolha a cor para a biografia do seu perfil.', 'link-in-bio'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo dinâmico para adicionar, editar e remover links.
     */
    public function render_links_field() {
        $options = get_option('link_in_bio_options');
        $links = $options['links'] ?? []; // Obtém os links salvos ou um array vazio.
        ?>
        <div id="link-in-bio-links-container" class="space-y-4 mb-4">
            <?php if (!empty($links)) : // Verifica se existem links para exibir ?>
                <?php foreach ($links as $index => $link) : // Loop através de cada link salvo ?>
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

    /**
     * Renderiza o campo de seleção de cor para os botões de link.
     */
    public function render_button_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[button_color]" value="<?php echo esc_attr($options['button_color'] ?? '#7c3aed'); ?>">
        <p class="description"><?php _e('Escolha a cor de fundo para os botões dos seus links.', 'link-in-bio'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de seleção de cor para o fundo da página.
     */
    public function render_background_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[background_color]" value="<?php echo esc_attr($options['background_color'] ?? '#ffffff'); ?>">
        <p class="description"><?php _e('Escolha a cor de fundo para toda a sua página Link in Bio.', 'link-in-bio'); ?></p>
        <?php
    }

    /**
     * Renderiza o campo de upload e pré-visualização da imagem de fundo.
     */
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

// Cria uma nova instância da classe Link_In_Bio_Admin para inicializar o painel administrativo.
new Link_In_Bio_Admin();