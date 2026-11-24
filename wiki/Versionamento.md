# 📐 Política de Versionamento SemVer & Ciclo de Entregas

O ecossistema **Domain System** segue a especificação internacional **Semantic Versioning 2.0.0 (SemVer)**:

$$\text{v} \, \mathbf{X} \, . \, \mathbf{Y} \, . \, \mathbf{Z}$$

Exemplo: **`v2.8.3`**

---

## 🧭 Os Três Níveis de Evolução

| Dígito | Classificação | O que significa na prática | Quando muda | Risco de quebra |
| :---: | :---: | :--- | :--- | :---: |
| **Z** | **PATCH** | **Ajustes e Correções** | Correções de pequenos bugs, melhorias visuais, polimento de telas e ajustes de estabilidade. | **Risco Zero** |
| **Y** | **MINOR** | **Novos Recursos** | Lançamento de novas telas, novas rotas, tabelas de banco e novos recursos sem quebrar nada do que já existia. | **Compatível** |
| **X** | **MAJOR** | **Nova Geração** | Mudanças profundas no motor do sistema (Kernel) que exigem atualização planejada. | **Migração Guiada** |

---

## 🏛️ Versionamento Independente nos Três Pilares

Para evitar que uma melhoria em um plugin force uma nova versão do sistema todo, cada pilar tem sua própria vida:

1. **O Núcleo (`domain-system`):**  
   Controla a versão do sistema operacional (ex: `v2.8.3`). Os módulos vitais do `SystemApps` e os plugins de composição interna (`core: true`) acompanham estritamente essa versão.
2. **Plugins Agregados (`domain-system-plugins`):**  
   Módulos externos como `domain_drop`, `commerce` e `academy` têm seu próprio número de versão. Um ajuste na mineração do AliExpress pode subir a versão do `domain_drop` sem precisar alterar a versão do Core.
3. **Temas Agregados (`domain-system-themes`):**  
   Temas visuais como o `rachidd_store` possuem versionamento próprio, iniciando na versão estável **`v1.0.0`**.

---

## 🔒 Como uma Nova Versão é Publicada

1. **Conferência de Versão:**  
   O número da versão é registrado de forma consistente na tag do Git, no `bootstrap.php` e no `composer.json`.
2. **Testes Automatizados:**  
   A suíte completa de **760 testes no PHPUnit** precisa passar com 100% de sucesso no GitHub Actions.
3. **Pacote Automático:**  
   O envio da tag para o GitHub gera automaticamente o pacote compactado oficial (.zip) assinado com código criptográfico SHA-256 no GitHub Releases.
4. **Atualização no Painel:**  
   O painel administrativo identifica a nova versão e permite aplicar a atualização com 1 clique, fazendo backup preventivo de segurança antes de qualquer alteração.
