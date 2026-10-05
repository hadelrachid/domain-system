> **ATENÇÃO ARQUITETURAL / PARADIGM SHIFT (2026-10-04)**
> 
> O Domain System **NÃO** é mais um sistema Sistema Operacional Web. Ele evoluiu para se tornar um **Sistema Operacional Web (Web OS) independente**.
> 
> No passado, o núcleo foi desenhado focado em separar ambientes de SaaS, mas esse acoplamento limitava o projeto. Hoje, o Domain System funciona como um Sistema Operacional puro (como Linux/Windows), que pode ser instalado em uma máquina ou container.
> Se for desejado que ele preste serviços Multi-Tenant, isso deverá ser feito virtualmente ou através de um aplicativo (Plugin Ring 3) construído especificamente para isso, **sem afetar o Micro-Kernel (Ring 0)**.

# 🛡️ Auditoria Arquitetural e de Segurança (Domain System OS v2.1)

**Última Atualização:** 05 de Outubro de 2026

Este documento reflete a transição arquitetural histórica do Domain System, evoluindo de uma plataforma procedimental de caso único para um **Micro-Kernel de Sistema Operacional Web estruturado em SOLID**.

---

## 🏛️ Evolução Arquitetural (Da v1.0 à v2.0)

O núcleo do sistema sofreu uma reformulação profunda em todos os seus pilares estruturais para garantir compatibilidade com padrões de mercado (SOLID, PSRs, Design Patterns avançados).

### 1. Desacoplamento Absoluto (Zero Lógica de Negócio no Core)
- **Antes (v1.0):** O Kernel e a autenticação conheciam conceitos de negócio (Tabelas de médicos, clínicas, pacientes, e roles hardcoded).
- **Agora (v2.0):** O Kernel é 100% agnóstico. Roles genéricas (`admin`, `manager`, `subscriber`, `user`) controlam o RBAC universal. Toda a lógica de negócio foi extraída e transferida para a camada de plugins.

### 2. Inversão de Controle e Injeção de Dependências (DIP)
- **Antes:** Amplo uso de `new Classe()` e Service Locators como `$this->db()` espalhados pelos controllers.
- **Agora:** O sistema conta com um Container de Injeção de Dependência (DI Container) de alto nível. Controllers e Repositórios declaram contratos (Interfaces) no construtor e o Micro-Kernel injeta a instância concreta automaticamente.

### 3. Eliminação de Superglobais
- **Antes:** Amplo acesso bruto à `$_SESSION`, `$_POST`, `$_GET`.
- **Agora:** Todas as comunicações fluem de maneira imutável e higienizada através da classe `Request` e do gerenciador `SessionManager`, facilitando a criação de testes de unidade e o processamento middleware.

---

## 🔒 Auditoria Cirúrgica e Hardening (Patch 2.1.0 - Out/2026)

Após uma devassa completa no núcleo da aplicação em outubro de 2026, novas diretrizes rígidas foram implementadas para garantir proteção em tempo de execução e no armazenamento:

### 1. Proteção de Colisão de Roteador (Ring 0)
O roteador dinâmico do sistema passou a travar a imutabilidade das rotas primárias. Foi mitigada a falha em que plugins "Ring 3" (camada de usuário) poderiam sequestrar ou sobrescrever rotas críticas do "Ring 0" (como `/login` e painéis de admin). O roteador agora lança `RouteAlreadyRegisteredException` nesses casos de invasão de espaço.

### 2. Migração Criptográfica: AES-256-GCM
A biblioteca de helpers abandonou a cifra antiga `AES-256-CBC`, avançando para a moderna criptografia autenticada **GCM**. Isso blinda os dados sensíveis contra ataques como o *Padding Oracle*. A função agora gera e exige uma tag matemática de integridade para cada encriptação; qualquer byte adulterado no payload destrói a transação.

### 3. Hardening de Sessão HTTP
O motor nativo `SessionManager` blinda a sessão via servidor injetando dinamicamente:
- `HttpOnly`: Impede roubo de cookies via XSS.
- `SameSite=Strict`: Impede o transporte do cookie via iframes externos ou ataques CSRF.
- `Use Strict Mode`: Bloqueia injeções de *Session Fixation*.
- Transição automática para `Secure` Cookie quando tráfego SSL é detectado.

### 4. Modo Desenvolvedor do Disjuntor (Emergency Hatch)
Caso um plugin cause danos irreparáveis e seja isolado pelo *Circuit Breaker*, o Administrador pode reativá-lo temporariamente logando com senha no **Modo Desenvolvedor**, contornando o desligamento apenas na sua sessão de análise, mantendo os usuários isolados do crash durante a correção.

---

## 🔒 Auditoria de Segurança e Resiliência (Patch 2.0)

Baseado em auditorias externas (incluindo varreduras rigorosas focadas em OWASP), as seguintes medidas de proteção foram integradas definitivamente ao SO:

### 1. No-Break Shield (Circuit Breaker Inteligente)
Implementado em nível de Kernel, esse design pattern previne o "Efeito Dominó" e a "Tela Branca da Morte" (WSOD). Se um plugin apresentar um Fatal Error ou vazamento de memória, o disjuntor intercepta a chamada, isola o componente em milissegundos e renderiza um dashboard imutável para o Super Admin sem afetar os processos dos outras instâncias ou aplicativos virtuais no servidor.

### 2. Blindagem contra "Zip Slip"
- **Falha Anterior:** Instalação de plugins via `.zip` sem validação de caminho, permitindo sobrescrita do núcleo.
- **Correção:** A classe `ZipArchiveExtractor` agora implementa uma dupla camada: extração em zona de quarentena (diretório `temp/`) e normalização rigorosa de `realpath()` para impedir ataques de *Directory Traversal*.

### 3. Proteção Global CSRF Automática
Tokens Anti-CSRF (`Cross-Site Request Forgery`) agora são emitidos e injetados de forma imperceptível via middlewares e pelo próprio motor de templates PHP em todas as sessões, blindando formulários do Painel Admin sem necessidade de ação direta por parte do desenvolvedor de terceiros.

### 4. Dicionário de Códigos de Erros (Telemetria)
Criação do artefato de Troubleshooting (`ERROR_DICTIONARY.md`), empacotando exceções puras do PHP em códigos categorizados legíveis para máquinas (como `ERR_SYS_01`, `ERR_PLG_03`), pavimentando a rodovia para a intervenção automatizada por Inteligências Artificiais no SO.

---

## 📈 Conclusão

O **Domain System OS v2.1.0** eleva o padrão de exigência. Seu Micro-Kernel agnóstico está blindado por testes unitários e CI (GitHub Actions), selado contra vazamento de criptografia, protegido contra roubo de sessões e imune a quedas em cascata, solidificando-se como uma plataforma **Web OS Enterprise-Ready**.


