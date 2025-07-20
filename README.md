# 🔗 Link in Bio – WordPress Plugin

Crie uma página personalizada estilo "Link in Bio", perfeita para divulgar seus links nas redes sociais (como Instagram, TikTok ou Twitter), diretamente do painel do WordPress. Este plugin permite que você personalize facilmente a aparência da sua página com **preview ao vivo**.

---

## 📦 Recursos

- ✅ Criação automática de página de Link in Bio.
- 🎨 Personalização visual com:
  - Cor ou imagem de plano de fundo.
  - Imagem de perfil com controle de tamanho e anel decorativo.
  - Cores do título, bio e botões.
- ⚙️ Interface de administração moderna com Tailwind CSS.
- 🔄 Visualização em tempo real no painel (Live Preview).
- 🖱️ Upload de imagens com integração à biblioteca de mídia do WordPress.
- 🔗 Adição, edição e reordenação de links customizados.

---

## 🚀 Instalação

1. Clone o repositório no diretório de plugins do WordPress:
   ```bash
   git clone https://github.com/seu-usuario/link-in-bio.git wp-content/plugins/link-in-bio

2. Ative o plugin no painel do WordPress:
Plugins > Link in Bio > Ativar

3. Acesse o menu Link in Bio no admin do WordPress e personalize sua página.

---

## 📁 Estrutura do Plugin

```text
link-in-bio/
├── admin/
│   ├── admin-init.php
│   └── settings-page.php 
├── assets/
│   ├── css/
│   │   └── admin.css
│   ├── js/
│   │   └── admin.js
│   └── link-in-bio-template.php
├── includes/
│   └── template-functions.php
├── templates/
│   └── link-in-bio-template.php
├── link-in-bio.php
└── README.md
```


---

## 🛠️ Desenvolvimento
Este plugin usa:

🌀 Tailwind CSS via CDN para estilos no painel.

🔄 AJAX para atualizar a visualização sem salvar.

📂 WordPress Settings API para persistência de dados.

📸 wp.media para upload de imagens.

---

📘 FAQ
1. O plugin cria a página automaticamente?
Sim, ao ativar, ele cria a página com o modelo correto.

2. Posso alterar a URL da página?
Sim. A página pode ser renomeada como desejar no painel de páginas.

3. Funciona com qualquer tema?
Funciona melhor com temas que suportam page templates. Temas padrão podem não reconhecer automaticamente o modelo de página incluído pelo plugin.

---

## 📄 Licença  
Este projeto está licenciado sob a [MIT License](LICENSE).

---

## 🤝 Contribuindo
1. Contribuições são bem-vindas! Para contribuir:

2. Fork este repositório.

3. Crie uma branch para sua feature ou correção.

4. Envie um Pull Request.

---

## 👤 Autor
Desenvolvido por **Diego Marcondes**

Contato: **eu@diegomarcondes.com**
