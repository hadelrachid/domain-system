<?php

namespace DomainSystem\Core;

use DomainSystem\Core\Contracts\ThemeManagerInterface;

use DomainSystem\Core\Contracts\ContainerInterface;
use DomainSystem\Core\Contracts\EventDispatcherInterface;
use DomainSystem\Core\Plugin\PluginManager;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Workspace\WorkspaceManager;

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * CLASSE: Application
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 🐝 O KERNEL DO DOMAIN SYSTEM — O PADRÃO SINGLETON
 *
 * Esta classe é a RAINHA da colméia. Ela é o coração do sistema, o núcleo
 * que orquestra todos os serviços essenciais: Container, Router, PluginManager,
 * ThemeManager, WorkspaceManager, entre outros.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * SINGLETON: A GARANTIA DE UNICIDADE
 * ───────────────────────────────────────────────────────────────────────────
 *
 * Esta classe implementa o padrão SINGLETON, garantindo que:
 *
 *   1. Exista APENAS UMA instância de Application em todo o sistema.
 *   2. O acesso a essa instância seja feito via um ponto único: getInstance().
 *   3. Ninguém possa criar uma nova instância com 'new' fora da classe.
 *   4. Ninguém possa clonar a instância existente.
 *   5. Ninguém possa desserializar uma nova instância.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: A RAINHA DA COLMÉIA
 * ───────────────────────────────────────────────────────────────────────────
 *
 * Assim como uma colméia tem APENAS UMA RAINHA, o Domain System tem
 * APENAS UM KERNEL. A rainha:
 *
 *   • É única por natureza (Singleton).
 *   • Controla a reprodução (construtor privado).
 *   • É protegida pelas operárias (clone e wakeup privados).
 *   • É o centro nervoso da colméia (orquestra todos os serviços).
 *
 * Se houvesse duas rainhas, a colméia entraria em colapso.
 * Se houvesse dois Kernels, o sistema entraria em colapso.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * A ANALOGIA DO COMPUTADOR: O KERNEL DO SISTEMA OPERACIONAL
 * ───────────────────────────────────────────────────────────────────────────
 *
 * Assim como um computador tem APENAS UM KERNEL rodando, o Domain System
 * tem APENAS UM Application. O Kernel:
 *
 *   • É o primeiro a ser criado (construtor público para o bootstrap).
 *   • Gerencia a memória (Container, SessionManager).
 *   • Gerencia os processos (PluginManager, Router).
 *   • É o último a ser destruído.
 *
 * Se houvesse dois Kernels, o computador entraria em colapso.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * DECISÃO ARQUITETURAL: EAGER LOADING vs LAZY LOADING
 * ───────────────────────────────────────────────────────────────────────────
 *
 * Esta implementação usa EAGER LOADING (carregamento ansioso), pois:
 *
 *   1. O Kernel será usado SEMPRE (cada requisição precisa dele).
 *   2. A criação não é pesada (não carrega todos os plugins, só a estrutura).
 *   3. Precisa estar disponível imediatamente (o bootstrap precisa dele).
 *   4. É essencial — sem Kernel, não há sistema.
 *
 * O construtor é PÚBLICO porque o bootstrap precisa criar o Kernel.
 * Após a criação, self::$instance armazena a instância única.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * BLINDAGEM DO SINGLETON
 * ───────────────────────────────────────────────────────────────────────────
 *
 * O Singleton está BLINDADO contra:
 *
 *   • new        → Construtor público (bootstrap cria), mas apenas uma vez.
 *   • clone      → __clone() privado lança exceção.
 *   • unserialize → __wakeup() privado lança exceção.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * CONEXÃO COM O CONTAINER DI
 * ───────────────────────────────────────────────────────────────────────────
 *
 * O Singleton NÃO substitui o Container DI. Eles são PARCEIROS:
 *
 *   • O Singleton garante a UNICIDADE do Kernel.
 *   • O Container DI garante a DISTRIBUIÇÃO do Kernel.
 *
 * Após a criação, o Kernel se registra no Container, para que qualquer
 * classe possa obtê-lo via injeção de dependência, sem precisar chamar
 * Application::getInstance() diretamente.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
class Application
{
    // ═══════════════════════════════════════════════════════════════════════
    // 1️⃣ A ÚNICA INSTÂNCIA (SINGLETON)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * A única instância da classe Application.
     *
     * • static: pertence à classe, não ao objeto.
     * • private: ninguém fora da classe pode acessar diretamente.
     * • ?Application: pode ser null (antes da primeira criação) ou Application.
     * • null: indica que ainda não foi instanciada.
     *
     * Este é o coração do Singleton: a variável que guarda a rainha.
     */
    private static ?Application $instance = null;

    // ═══════════════════════════════════════════════════════════════════════
    // 2️⃣ SERVIÇOS ESSENCIAIS DO KERNEL
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * O Container de Injeção de Dependências.
     *
     * O "Almoxarifado" que gerencia a criação e distribuição de objetos.
     * Usa a INTERFACE (ContainerInterface), não a implementação concreta,
     * respeitando o Princípio da Inversão de Dependência (DIP).
     */
    private ContainerInterface $container;

    /**
     * O Despachante de Eventos (Event Dispatcher).
     *
     * O "Rádio Comunicador" do sistema. Permite que plugins se comuniquem
     * sem se conhecerem, via eventos (dispatch) e ouvintes (listeners).
     * Também usa a INTERFACE, respeitando DIP.
     */
    private EventDispatcherInterface $dispatcher;

    /**
     * O Gerenciador de Plugins (Plugin Manager).
     *
     * Escaneia a pasta de plugins, valida os manifestos (plugin.json),
     * e ativa/desativa os módulos. É o "Slot PCI-Express" do sistema.
     */
    private PluginManager $pluginManager;

    /**
     * O Roteador (Router).
     *
     * Mapeia URLs para Controllers. Age como o "Porteiro Global"
     * (Gatekeeper), verificando permissões antes de chamar os Controllers.
     */
    private Router $router;

    /**
     * O Gerenciador de Temas (Theme Manager).
     *
     * Gerencia o Front-end. Permite que múltiplos temas (CockPITs)
     * coexistam no mesmo sistema.
     */
    private ThemeManagerInterface $themeManager;

    /**
     * O Gerenciador de Shortcodes (Shortcode Manager).
     *
     * Permite que plugins injetem conteúdo dinâmico nos temas
     * sem que o tema precise conhecer o plugin.
     */
    private \DomainSystem\Core\Theme\ShortcodeManager $shortcodeManager;

    /**
     * O Gerenciador de Workspaces.
     *
     * Define o layout visual baseado no perfil do usuário
     * (admin, manager, user, etc.).
     */
    private WorkspaceManager $workspaceManager;

    /**
     * O Gerenciador de Sessões (Session Manager).
     *
     * Gerencia o estado do usuário (sessão) de forma abstrata,
     * evitando acesso direto à superglobal $_SESSION.
     */
    private \DomainSystem\Core\Http\SessionManager $sessionManager;

    /**
     * O Registro de Cockpits.
     *
     * Registra os diferentes painéis (cockpits) para cada perfil
     * de usuário, permitindo que cada um tenha sua própria interface.
     */
    private \DomainSystem\Core\Cockpit\CockpitRegistry $cockpitRegistry;

    /**
     * O caminho base do projeto.
     *
     * Usado para localizar plugins, temas, configurações, etc.
     */
    private string $basePath;



    // ═══════════════════════════════════════════════════════════════════════
    // 3️⃣ CONSTRUTOR PÚBLICO (MAS COM CONTROLE DE SINGLETON)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Construtor da classe Application.
     *
     * ───────────────────────────────────────────────────────────────────────
     * POR QUE O CONSTRUTOR É PÚBLICO?
     * ───────────────────────────────────────────────────────────────────────
     *
     * Em um Singleton clássico, o construtor é PRIVADO para impedir 'new'.
     * Aqui, ele é PÚBLICO porque o bootstrap precisa criar o Kernel.
     *
     * Mas o Singleton é garantido de outra forma:
     *   • Após a criação, self::$instance armazena a instância.
     *   • O método getInstance() sempre retorna essa instância.
     *   • O Container DI registra o Kernel como singleton.
     *
     * Portanto, mesmo com o construtor público, o Kernel permanece ÚNICO
     * na prática, pois o bootstrap só o cria uma vez.
     *
     * ───────────────────────────────────────────────────────────────────────
     * EAGER LOADING: CRIAÇÃO IMEDIATA
     * ───────────────────────────────────────────────────────────────────────
     *
     * Este é o coração do Eager Loading. No final do construtor, fazemos:
     *     self::$instance = $this;
     *
     * Isso significa que, assim que o Kernel é criado, ele já se registra
     * como a instância única. Não há "espera" — ele nasce pronto.
     *
     * ───────────────────────────────────────────────────────────────────────
     * PARÂMETROS
     * ───────────────────────────────────────────────────────────────────────
     *
     * @param ContainerInterface       $container   O Container de Injeção de Dependências.
     * @param EventDispatcherInterface $dispatcher  O Despachante de Eventos.
     * @param string                   $basePath    O caminho base do projeto.
     */
    public function __construct(ContainerInterface $container, EventDispatcherInterface $dispatcher, string $basePath)
    {
        // ─────────────────────────────────────────────────────────────────
        // Armazena as dependências essenciais
        // ─────────────────────────────────────────────────────────────────
        $this->container = $container;
        $this->dispatcher = $dispatcher;
        $this->basePath = $basePath;

        // ═════════════════════════════════════════════════════════════════
        // 🔵 AUTOINSTANCIAÇÃO — O CORAÇÃO DO SINGLETON (EAGER LOADING)
        // ═════════════════════════════════════════════════════════════════
        self::$instance = $this;

        // Registra a si mesmo no Container
        $this->container->singleton(Application::class, function() {
            return $this;
        });

        // ─────────────────────────────────────────────────────────────────
        // CARREGA TODOS OS SERVIÇOS DO KERNEL VIA SERVICE PROVIDER (SRP)
        // ─────────────────────────────────────────────────────────────────
        $provider = new \DomainSystem\Core\Providers\CoreServiceProvider();
        $provider->register($this->container, $this->dispatcher, $this->basePath);

        // ─────────────────────────────────────────────────────────────────
        // INSTANCIA AS PROPRIEDADES VIA INJEÇÃO DE DEPENDÊNCIA (DIP)
        // ─────────────────────────────────────────────────────────────────
        $this->sessionManager = $this->container->make(\DomainSystem\Core\Http\SessionManager::class);
        $this->pluginManager = $this->container->make(\DomainSystem\Core\Plugin\PluginManager::class);
        $this->router = $this->container->make(\DomainSystem\Core\Contracts\RouterInterface::class);
        $this->shortcodeManager = $this->container->make(\DomainSystem\Core\Theme\ShortcodeManager::class);
        $this->themeManager = $this->container->make(\DomainSystem\Core\Theme\ThemeManager::class);
        $this->workspaceManager = $this->container->make(\DomainSystem\Core\Workspace\WorkspaceManager::class);
        $this->cockpitRegistry = $this->container->make(\DomainSystem\Core\Contracts\CockpitRegistryInterface::class);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 4️⃣ MÉTODO DE ACESSO (SINGLETON) — O ÚNICO PONTO DE ENTRADA
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Retorna a ÚNICA instância de Application.
     *
     * Este é o ponto de acesso global ao Kernel (Singleton).
     * Qualquer classe pode chamar Application::getInstance() para obter
     * a rainha da colméia.
     *
     * ───────────────────────────────────────────────────────────────────────
     * POR QUE ISSO É IMPORTANTE?
     * ───────────────────────────────────────────────────────────────────────
     *
     * Sem isso, cada classe poderia criar sua própria instância do Kernel,
     * causando o caos das "duas rainhas". Com isso, todos compartilham
     * a mesma instância, garantindo consistência e unicidade.
     *
     * ───────────────────────────────────────────────────────────────────────
     * EAGER LOADING vs LAZY LOADING
     * ───────────────────────────────────────────────────────────────────────
     *
     * Aqui, NÃO fazemos "if (self::$instance === null) { ... }" como
     * no Lazy Loading. Em vez disso, apenas retornamos self::$instance,
     * pois o construtor já a criou (Eager Loading).
     *
     * @return ?Application A instância única do Kernel (ou null).
     */
    public static function getInstance(): ?Application
    {
        return self::$instance;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 5️⃣ GETTERS — ACESSO AOS SERVIÇOS DO KERNEL
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Retorna o Container de Injeção de Dependências.
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * Retorna o Despachante de Eventos.
     */
    public function getDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    /**
     * Retorna o Gerenciador de Plugins.
     */
    public function getPluginManager(): PluginManager
    {
        return $this->pluginManager;
    }

    /**
     * Retorna o Roteador.
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Retorna o Gerenciador de Temas.
     */
    public function getThemeManager(): ThemeManager
    {
        return $this->themeManager;
    }

    /**
     * Retorna o Gerenciador de Shortcodes.
     */
    public function getShortcodeManager(): \DomainSystem\Core\Theme\ShortcodeManager
    {
        return $this->shortcodeManager;
    }

    /**
     * Retorna o Gerenciador de Workspaces.
     */
    public function getWorkspaceManager(): WorkspaceManager
    {
        return $this->workspaceManager;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // 6️⃣ MÉTODO boot() — A INICIALIZAÇÃO DO SISTEMA
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Inicializa o sistema: descobre plugins, carrega-os, e dispara eventos.
     *
     * Este método é o "boot" do Kernel. Ele é chamado pelo bootstrap após
     * a criação do Kernel (Eager Loading). Aqui, o Kernel:
     *
     *   1. Descobre todos os plugins ativos.
     *   2. Carrega-os (chama register() e boot() de cada plugin).
     *   3. Dispara eventos de inicialização.
     *   4. Registra shortcodes e workspaces.
     *
     * ───────────────────────────────────────────────────────────────────────
     * A ANALOGIA DA COLMÉIA
     * ───────────────────────────────────────────────────────────────────────
     *
     * Isso é como a rainha acordando a colméia:
     *   • Descobre as operárias (plugins ativos).
     *   • Dá ordens a cada uma (register + boot).
     *   • Comunica via feromônio (dispatch de eventos).
     *   • Distribui tarefas (shortcodes e workspaces).
     */
    public function boot(): void
    {
        // Inicia a sessão nativa do Kernel antes de qualquer plugin
        $this->sessionManager->start();

        // ─────────────────────────────────────────────────────────────────
        // Define os caminhos dos plugins e da configuração
        // ─────────────────────────────────────────────────────────────────
        $pluginsPath = $this->basePath . '/src/Plugins';
        $configPath = $this->basePath . '/config/plugins.json';

        // ─────────────────────────────────────────────────────────────────
        // Descobre os plugins (lê a pasta e valida os manifestos)
        // ─────────────────────────────────────────────────────────────────
        // 1. Carrega os Aplicativos do Sistema (Protegidos/Core)
        $systemAppsPath = dirname(__DIR__) . '/SystemApps';
        $this->pluginManager->discoverPlugins($systemAppsPath, $configPath, true, true);

        // 2. Carrega os Plugins de Usuário
        $this->pluginManager->discoverPlugins($pluginsPath, $configPath, false, false);

        // ─────────────────────────────────────────────────────────────────
        // Carrega os plugins (chama register() e boot() de cada um)
        // ─────────────────────────────────────────────────────────────────
        $this->pluginManager->bootPlugins();

        // ─────────────────────────────────────────────────────────────────
        // Dispara o evento 'init', permitindo que plugins se inicializem
        // ─────────────────────────────────────────────────────────────────
        $this->dispatcher->dispatch('init');

        // ─────────────────────────────────────────────────────────────────
        // Dispara o evento 'shortcodes.register', permitindo que plugins
        // registrem seus shortcodes
        // ─────────────────────────────────────────────────────────────────
        $this->dispatcher->dispatch('shortcodes.register', $this->shortcodeManager);

        // ─────────────────────────────────────────────────────────────────
        // Dispara o evento 'workspace.register', permitindo que plugins
        // registrem seus workspaces (layouts por perfil de usuário)
        // ─────────────────────────────────────────────────────────────────
        $this->dispatcher->dispatch('workspace.register', $this->workspaceManager);
    }
}



