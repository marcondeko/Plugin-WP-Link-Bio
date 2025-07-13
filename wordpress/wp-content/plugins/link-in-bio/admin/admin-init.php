<?php
defined('ABSPATH') || exit;

/**
 * Carrega os assets do admin
 */
function link_in_bio_admin_assets($hook) {
    if ($hook !== 'toplevel_page_link_in_bio') {
        return;
    }

    // Carrega a API de mídia do WordPress
    wp_enqueue_media();

    // CSS do admin
    wp_enqueue_style(
        'link-in-bio-admin',
        LINK_IN_BIO_PLUGIN_URL . 'assets/css/admin.css',
        [],
        filemtime(LINK_IN_BIO_PLUGIN_DIR . 'assets/css/admin.css')
    );
    
    // JavaScript do admin
    wp_enqueue_script(
        'link-in-bio-admin-js',
        LINK_IN_BIO_PLUGIN_URL . 'assets/js/admin.js',
        ['jquery'],
        filemtime(LINK_IN_BIO_PLUGIN_DIR . 'assets/js/admin.js'),
        true
    );
}
add_action('admin_enqueue_scripts', 'link_in_bio_admin_assets');

/**
 * Classe principal do admin
 */
class Link_In_Bio_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'init_settings']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Link in Bio', 
            'Link in Bio', 
            'manage_options', 
            'link_in_bio', 
            [$this, 'render_settings_page'], 
            'dashicons-admin-links',
            76
        );
    }
    
    public function init_settings() {
        register_setting('link_in_bio_settings', 'link_in_bio_options', [$this, 'sanitize_options']);
                
        // Seção de perfil
        add_settings_section(
            'link_in_bio_profile_section',
            'Configurações de Perfil',
            [$this, 'render_profile_section'],
            'link_in_bio'
        );
        
        // Campos de perfil
        add_settings_field(
            'profile_image',
            'Imagem de Perfil',
            [$this, 'render_profile_image_field'],
            'link_in_bio',
            'link_in_bio_profile_section'
        );

        // Tamanho da imagem
        add_settings_field(
            'profile_image_size',
            'Tamanho da Imagem de Perfil (px)',
            [$this, 'render_image_size_field'],
            'link_in_bio',
            'link_in_bio_profile_section'
        );

        // Largura do anel
        add_settings_field(
            'profile_ring_width',
            'Espessura do Anel (px)',
            [$this, 'render_ring_width_field'],
            'link_in_bio',
            'link_in_bio_profile_section'
        );
        
        add_settings_field(
            'profile_title',
            'Título do Perfil',
            [$this, 'render_profile_title_field'],
            'link_in_bio',
            'link_in_bio_profile_section'
        );

        add_settings_field(
            'profile_bio',
            'Bio do Perfil',
            [$this, 'render_profile_bio_field'],
            'link_in_bio',
            'link_in_bio_profile_section'
        );


        // Links
        add_settings_section(
            'link_in_bio_main_section',
            'Configurações dos Links',
            [$this, 'render_main_section'],
            'link_in_bio'
        );
        
        // Campo de links
        add_settings_field(
            'links_field',
            'Seus Links',
            [$this, 'render_links_field'],
            'link_in_bio',
            'link_in_bio_main_section'
        );

        // Seção de estilo visual
        add_settings_section(
            'link_in_bio_style_section',
            'Estilo Visual',
            null,
            'link_in_bio'
        );

        // Cor de fundo
        add_settings_field(
            'background_color',
            'Cor de Fundo',
            [$this, 'render_background_color_field'],
            'link_in_bio',
            'link_in_bio_style_section'
        );

        // Imagem de fundo
        add_settings_field(
            'background_image',
            'Imagem de Fundo',
            [$this, 'render_background_image_field'],
            'link_in_bio',
            'link_in_bio_style_section'
        );

        // Cor do título
        add_settings_field(
            'title_color',
            'Cor do Título',
            [$this, 'render_title_color_field'],
            'link_in_bio',
            'link_in_bio_profile_section'
        );

        // Cor da bio
        add_settings_field(
            'bio_color',
            'Cor da Biografia',
            [$this, 'render_bio_color_field'],
            'link_in_bio',
            'link_in_bio_profile_section'
        );

        // Cor dos botões
        add_settings_field(
            'button_color',
            'Cor dos Botões de Link',
            [$this, 'render_button_color_field'],
            'link_in_bio',
            'link_in_bio_main_section'
        );

    }
    
    public function sanitize_options($input) {
        $sanitized = [];
        
        // Sanitize links
        if (!empty($input['links'])) {
            foreach ($input['links'] as $index => $link) {
                $sanitized['links'][$index] = [
                    'title' => sanitize_text_field($link['title']),
                    'url' => esc_url_raw($link['url'])
                ];
            }
        }
        
        // Sanitize profile fields
        $sanitized['profile_image'] = esc_url_raw($input['profile_image'] ?? '');
        $sanitized['profile_title'] = sanitize_text_field($input['profile_title'] ?? '');
        $sanitized['profile_bio'] = sanitize_textarea_field($input['profile_bio'] ?? '');

        // Sanitize customize fields
        $sanitized['background_color'] = sanitize_hex_color($input['background_color'] ?? '#ffffff');
        $sanitized['background_image'] = esc_url_raw($input['background_image'] ?? '');

        $sanitized['profile_image_size'] = absint($input['profile_image_size'] ?? 150);
        $sanitized['profile_ring_width'] = absint($input['profile_ring_width'] ?? 4);

        $sanitized['title_color'] = sanitize_hex_color($input['title_color'] ?? '#000000');
        $sanitized['bio_color'] = sanitize_hex_color($input['bio_color'] ?? '#666666');
        $sanitized['button_color'] = sanitize_hex_color($input['button_color'] ?? '#7c3aed');

        
        return $sanitized;
    }
    
    public function render_main_section() {
        echo '<p>Adicione e organize os links que aparecerão na sua página.</p>';
    }
    
    public function render_profile_section() {
        echo '<p>Configure as informações do seu perfil.</p>';
    }
    
    public function render_links_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <div id="link-in-bio-links-container" class="link-in-bio-links-container">
            <?php if (!empty($options['links'])) : ?>
                <?php foreach ($options['links'] as $index => $link) : ?>
                    <div class="link-in-bio-link">
                        <div class="link-in-bio-link-row">
                            <input type="text" 
                                   name="link_in_bio_options[links][<?php echo $index; ?>][title]" 
                                   value="<?php echo esc_attr($link['title']); ?>" 
                                   placeholder="Título do Link"
                                   class="regular-text">
                            
                            <input type="url" 
                                   name="link_in_bio_options[links][<?php echo $index; ?>][url]" 
                                   value="<?php echo esc_url($link['url']); ?>" 
                                   placeholder="https://exemplo.com"
                                   class="regular-text">
                            
                            <button type="button" class="button link-in-bio-remove btn-link-in-bio-remove">
                                Remover
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <button type="button" id="link-in-bio-add" class="button button-primary mt-4">
            Adicionar Novo Link
        </button>
        <?php
    }
    
    public function render_profile_image_field() {
        $options = get_option('link_in_bio_options');
        $image_url = $options['profile_image'] ?? '';
        ?>
        <div class="link-in-bio-profile-image">
            <input type="hidden" 
                name="link_in_bio_options[profile_image]" 
                id="link_in_bio_profile_image"
                value="<?php echo esc_url($image_url); ?>">
            
            <button type="button" class="button" id="link-in-bio-upload-image">
                <?php esc_html_e('Selecionar Imagem', 'link-in-bio'); ?>
            </button>
            
            <?php if (!empty($image_url)) : ?>
                <button type="button" class="button button-danger" id="link-in-bio-remove-image">
                    <?php esc_html_e('Remover Imagem', 'link-in-bio'); ?>
                </button>
            <?php endif; ?>
            
            <div id="link-in-bio-image-preview" class="mt-2">
                <?php if (!empty($image_url)) : ?>
                    <img src="<?php echo esc_url($image_url); ?>" 
                        style="max-width: 150px; height: auto;">
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    public function render_profile_title_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="text" 
               name="link_in_bio_options[profile_title]" 
               value="<?php echo esc_attr($options['profile_title'] ?? 'Seu Nome'); ?>" 
               class="regular-text">
        <?php
    }

    public function render_profile_bio_field() {
    $options = get_option('link_in_bio_options');
    ?>
    <textarea name="link_in_bio_options[profile_bio]" 
              rows="4" 
              cols="50"
              class="large-text"><?php echo esc_textarea($options['profile_bio'] ?? 'Sua Biografia'); ?></textarea>
    <p class="description">Escreva uma breve biografia para seu perfil</p>
    <?php
    }
    
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Link in Bio - Configurações</h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('link_in_bio_settings');
                do_settings_sections('link_in_bio');
                submit_button('Salvar Configurações');
                ?>
            </form>
        </div>
        <?php
    }

    public function render_background_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[background_color]" value="<?php echo esc_attr($options['background_color'] ?? '#ffffff'); ?>">
        <?php
    }

    public function render_background_image_field() {
        $options = get_option('link_in_bio_options');
        $image_url = $options['background_image'] ?? '';
        ?>
        <div>
            <input type="hidden" 
                name="link_in_bio_options[background_image]" 
                id="link_in_bio_background_image"
                value="<?php echo esc_url($image_url); ?>">
            <button type="button" class="button" id="upload-background-image">Selecionar Imagem</button>
            <?php if ($image_url) : ?>
                <button type="button" class="button" id="remove-background-image">Remover Imagem</button>
                <div><img src="<?php echo esc_url($image_url); ?>" style="max-width: 200px; margin-top: 10px;"></div>
            <?php endif; ?>
        </div>
        <?php
    }

    public function render_image_size_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="number" name="link_in_bio_options[profile_image_size]" value="<?php echo esc_attr($options['profile_image_size'] ?? 150); ?>" min="50" max="400">
        <?php
    }

    public function render_ring_width_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="number" name="link_in_bio_options[profile_ring_width]" value="<?php echo esc_attr($options['profile_ring_width'] ?? 4); ?>" min="0" max="20">
        <?php
    }

    public function render_title_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[title_color]" value="<?php echo esc_attr($options['title_color'] ?? '#000000'); ?>">
        <?php
    }

    public function render_bio_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[bio_color]" value="<?php echo esc_attr($options['bio_color'] ?? '#666666'); ?>">
        <?php
    }

    public function render_button_color_field() {
        $options = get_option('link_in_bio_options');
        ?>
        <input type="color" name="link_in_bio_options[button_color]" value="<?php echo esc_attr($options['button_color'] ?? '#7c3aed'); ?>">
        <?php
    }


    
}

// Inicializa a classe do admin
new Link_In_Bio_Admin();