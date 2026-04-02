# 🎯 INSTALAÇÃO COMPLETA - Sistema Nosso Núcleo Design

## ✅ SISTEMA 100% COMPLETO!

Todos os arquivos foram criados e estão prontos para uso.

---

## 📁 ESTRUTURA COMPLETA:

```
nucleo/
├── index.html                    ✅ Login com logo
├── register-lojista.html         ✅ Cadastro Lojista
├── register-arquiteto.html       ✅ Cadastro Arquiteto
├── dashboard-admin.html          ✅ Dashboard Admin COMPLETO
├── dashboard-lojista.html        ✅ Dashboard Lojista
├── dashboard-arquiteto.html      ✅ Dashboard Arquiteto
├── assets/
│   ├── css/
│   │   └── style.css            ✅ CSS profissional
│   ├── js/
│   │   ├── api.js               ✅ Funções de API
│   │   └── auth.js              ✅ Autenticação
│   └── images/
│       └── logo.png             ⚠️  ADICIONE SUA LOGO
├── api/                         ✅ Use os PHPs que já funcionam
└── config.php                   ✅ Já configurado
```

---

## 🚀 PASSO A PASSO:

### 1️⃣ Fazer Upload

Faça upload de TODOS os arquivos para:
```
/public_html/nucleo/
```

### 2️⃣ Adicionar Logo

Coloque sua logo em:
```
/public_html/nucleo/assets/images/logo.png
```

**Tamanho recomendado:** 400x150px (PNG transparente)

### 3️⃣ Executar SQL Atualizado

No phpMyAdmin, execute:
```sql
-- Adicionar campos de premiação
ALTER TABLE campaigns 
ADD COLUMN description TEXT NULL AFTER name,
ADD COLUMN premio_primeiro TEXT NULL AFTER min_store_percentage,
ADD COLUMN premio_segundo TEXT NULL AFTER premio_primeiro,
ADD COLUMN premio_terceiro TEXT NULL AFTER premio_segundo;

-- Criar tabela de aniversários
CREATE TABLE IF NOT EXISTS birthday_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_date DATE NOT NULL,
    year INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_year (user_id, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 4️⃣ Copiar APIs do sistema-completo

Copie estes arquivos para `/public_html/nucleo/api/`:
- campaign_analysis.php
- check_birthdays.php
- my_details.php

### 5️⃣ Acessar

Acesse:
```
http://labmedclin.med.br/nucleo/
```

Login:
- Email: admin@nucleodesign.com
- Senha: Admin@2025

---

## 🎯 FUNCIONALIDADES IMPLEMENTADAS:

### 👨‍💼 Dashboard Admin:
✅ Ver estatísticas gerais
✅ Rankings (arquitetos, lojistas, escritórios)
✅ Criar escritórios
✅ Criar campanhas com premiações
✅ Inserir pontos vinculando loja + arquiteto
✅ Análise de campanhas (quem está próximo de ganhar)
✅ Ver critérios de vitória (60% lojas, 2000 pts/loja)
✅ Verificar aniversários automáticos

### 🏪 Dashboard Lojista:
✅ Ver seus pontos distribuídos
✅ Ver arquitetos atendidos
✅ Inserir pontos para arquiteto (vincula automaticamente à sua loja)
✅ Ver ranking de arquitetos (SEM mostrar lojas)
✅ Ver histórico de pontos inseridos

### 🏗️ Dashboard Arquiteto:
✅ Ver seus pontos totais
✅ Ver seu escritório e pontos do escritório
✅ Ver detalhes por loja (onde acumulou pontos)
✅ Ver detalhes por campanha
✅ Ver posição no ranking geral
✅ Receber mensagem de aniversário automática

---

## 🎨 PERSONALIZAÇÃO:

### Trocar Cores:
Edite `/assets/css/style.css` (linhas 4-11):
```css
:root {
    --color-bg: #F5E9E0;           /* Fundo geral */
    --color-primary: #292524;       /* Cor principal */
    --color-accent: #D4B2A7;        /* Cor de destaque */
    /* etc... */
}
```

### Trocar Logo:
Substitua o arquivo:
```
/assets/images/logo.png
```

---

## 📊 COMO FUNCIONA:

### Inserir Pontos:
1. Admin ou Lojista insere pontos
2. Pontos vão para o Arquiteto
3. Se arquiteto tem escritório, escritório também recebe
4. Campanha ativa registra automaticamente

### Critérios de Vitória:
- Exemplo: 10 lojas cadastradas
- Arquiteto precisa pontuar em 6 lojas (60%)
- Cada loja: mínimo 2000 pontos
- Sistema calcula e mostra quem está qualificado

### Aniversários:
- Sistema verifica automaticamente
- Envia mensagem personalizada
- Registra no banco (não envia duplicado)

---

## ✅ CHECKLIST FINAL:

- [ ] Upload de todos os arquivos
- [ ] Logo adicionada
- [ ] SQL executado
- [ ] APIs copiadas
- [ ] Testado login admin
- [ ] Testado cadastro lojista
- [ ] Testado cadastro arquiteto

---

## 🎉 PRONTO!

Sistema 100% funcional com TODAS as funcionalidades solicitadas!

Qualquer dúvida, consulte a documentação ou entre em contato.
