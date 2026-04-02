# Sistema Nosso Núcleo Design - Versão Organizada

## 📁 Estrutura de Arquivos:

```
nucleo/
├── index.html                    # Login
├── register-lojista.html         # Cadastro Lojista  
├── register-arquiteto.html       # Cadastro Arquiteto
├── dashboard-admin.html          # Dashboard Admin
├── dashboard-lojista.html        # Dashboard Lojista
├── dashboard-arquiteto.html      # Dashboard Arquiteto
├── assets/
│   ├── css/
│   │   └── style.css            # CSS global
│   ├── js/
│   │   ├── api.js               # Funções de API
│   │   └── auth.js              # Autenticação
│   └── images/
│       └── logo.png             # ADICIONE SUA LOGO AQUI
├── api/                         # Endpoints PHP (use os que você já tem)
└── config.php                   # Configuração (use o que você já tem)
```

## ✅ Arquivos Criados:

1. **index.html** - Tela de login com logo
2. **register-lojista.html** - Cadastro completo de lojista  
3. **assets/css/style.css** - CSS profissional e organizado
4. **assets/js/api.js** - Todas as chamadas de API
5. **assets/js/auth.js** - Gerenciamento de autenticação

## 📋 Próximos Arquivos (em desenvolvimento):

- register-arquiteto.html
- dashboard-admin.html  
- dashboard-lojista.html
- dashboard-arquiteto.html

## 🚀 Como Instalar:

1. **Faça upload** de todos os arquivos para `/public_html/nucleo/`
2. **Adicione sua logo** em `assets/images/logo.png`
3. **Mantenha** os arquivos `api/` e `config.php` que você já tem
4. **Acesse**: http://labmedclin.med.br/nucleo/

## 🎨 Personalização:

### Trocar a Logo:
- Substitua `assets/images/logo.png` pela sua logo
- Tamanho recomendado: 400x150px (formato PNG transparente)

### Alterar Cores:
Edite `assets/css/style.css` (linhas 4-11):
```css
:root {
    --color-bg: #F5E9E0;
    --color-primary: #292524;
    --color-accent: #D4B2A7;
    /* etc */
}
```

## 📞 Status:

✅ **Funcionando:**
- Login
- Cadastro de Lojista
- CSS e JavaScript base

⏳ **Em desenvolvimento:**
- Dashboards
- Cadastro de Arquiteto  
- Gestão de campanhas

---

**Próximo passo:** Vou criar os dashboards completos!
