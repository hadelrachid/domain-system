
    <header>
        <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/index_en" class="lang-toggle">🇺🇸 English</a>
        <img src="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/images/logo.svg" alt="Cockpit Logo" class="logo-docs">
        <h1>Domain-System Documentação</h1>
        <p>O Kernel PHP Moderno, Modular e Orientado a Eventos.</p>
    </header>

    <div class="content">
        <h2>Índice Oficial</h2>
        <p>Bem-vindo à documentação oficial do <strong>Domain-System</strong>. Escolha um dos tópicos abaixo para
            explorar a arquitetura e aprender como utilizar e estender o nosso motor.</p>

        <ul>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/proposito">Visão, Propósito e Público-Alvo</a></strong><br>
                Entenda por que criamos este sistema, que dor ele resolve e para quem ele é feito.</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/arquitetura">Como o Motor Funciona (Arquitetura)</a></strong><br>
                A visão macro do sistema: Container de Injeção de Dependência, Roteamento, MVC e SSR.</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/contratos">Contratos e Interfaces (SOLID)</a></strong><br>
                Como as regras rígidas mantêm o núcleo blindado. (PluginInterface, ExtractorInterface, etc).</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/circuit-breaker">Resiliência & Circuit Breaker ⚡</a></strong><br>
                Como o motor intercepta erros fatais e desarma plugins sozinhos para proteger o núcleo.</li>
        </ul>
        
        <h2>Domain-System OS (Novo Padrão 2.0)</h2>
        <p>A nova geração da arquitetura baseada no Princípio do Menor Privilégio e no Ciclo de Confiança Verificável.</p>
        
        <ul>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/tutoriais/01-instalacao">Comece por Aqui: Download e Instalação 🚀</a></strong><br>
                Guia de como baixar do GitHub e testar localmente no XAMPP ou publicar na Hostinger.</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/tutorial-dev">Guia do Desenvolvedor: Construindo Plugins 🧩</a></strong><br>
                Tutorial interativo de como construir a lógica de negócios para o sistema usando a nova <strong>OsExtensionInterface</strong> e Injeção de Dependências.</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/tutorial-theme">Guia do Desenvolvedor: Construindo Temas 🎨</a></strong><br>
                Tutorial interativo de como construir layouts de alta performance com MVC, Output Buffering e SEO Automático.</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/tutoriais/02-a-escotilha-de-emergencia">Sistema de Prioridades e Firewalls 🛡️</a></strong><br>
                Entenda a prioridade dos Hooks e o padrão "Escotilha de Emergência".</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/tutoriais/03-pacotes-de-plugins">Pacotes de Plugins (Hubs) 📦</a></strong><br>
                Aprenda a estruturar de forma limpa um pacote que agrupa múltiplos módulos independentes.</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/lifecycle">O Fluxo de Vida do Plugin (Analogia Visual) 🛂</a></strong><br>
                Entenda a abstração do Kernel e o processo de boot usando a didática "Analogia da Imigração".</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/01-o-contrato-do-sistema">Manifesto: O Contrato do Sistema</a></strong><br>
                Leia o porquê adotamos Injeção de Dependências em vez da caça por variáveis globais.</li>
            <br>
            <li><strong><a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/docs/architecture/01-ciclo-de-confianca-verificavel">O Ciclo de Confiança Verificável (IA)</a></strong><br>
                A fundação arquitetural que permite a futuras IAs criarem e auditarem plugins com 100% de segurança.</li>
        </ul>
    </div>

    <footer>
        &copy; 2026 Desenvolvido com foco absoluto em qualidade e SOLID.
    </footer>
