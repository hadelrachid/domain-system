<?php

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * ARQUIVO: bootstrap.php
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 🐝 O NASCIMENTO DA RAINHA — A INICIALIZAÇÃO DO KERNEL
 *
 * Este arquivo é o ponto de partida do Domain System. É aqui que tudo
 * começa: a partir deste arquivo, o Kernel (Application) é criado, os
 * serviços essenciais são montados, e o sistema está pronto para o boot.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: O NASCIMENTO DA RAINHA
 * ───────────────────────────────────────────────────────────────────────────
 *
 * Este arquivo é como o "nascimento da rainha" na colméia. Antes dele,
 * não há colméia. Depois dele, a rainha existe e a colméia pode começar
 * a funcionar.
 *
 *   1. Prepara o ambiente (variáveis de ambiente, fuso horário).
 *   2. Carrega as ferramentas (autoload, helpers).
 *   3. Monta o sistema imunológico (ErrorHandler, CircuitBreaker).
 *   4. Cria os serviços essenciais (Container, EventDispatcher).
 *   5. Cria a rainha (Application) — o Kernel Singleton.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * A ANALOGIA DO COMPUTADOR: O BOOT DO SISTEMA OPERACIONAL
 * ───────────────────────────────────────────────────────────────────────────
 *
 * Este arquivo é como a BIOS/UEFI de um computador. Antes dele, o hardware
 * está ligado, mas nada funciona. Depois dele:
 *
 *   1. POST (Power-On Self-Test)      → Carrega variáveis e configurações.
 *   2. BOOTLOADER                     → Carrega o autoload e helpers.
 *   3. KERNEL INIT                    → Cria o Container e o Dispatcher.
 *   4. KERNEL BOOT                    → Cria o Application (Singleton).
 *   5. RETORNA O KERNEL               → Pronto para o boot final.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * O QUE É RETORNADO?
 * ───────────────────────────────────────────────────────────────────────────
 *
 * Este arquivo RETORNA a instância única do Application (Kernel Singleton).
 * O arquivo `public/index.php` recebe esse retorno e chama `$app->boot()`
 * para inicializar o sistema.
 *
 * ───────────────────────────────────────────────────────────────────────────
 * CONEXÃO COM O SINGLETON
 * ───────────────────────────────────────────────────────────────────────────
 *
 * Este arquivo é o ÚNICO lugar do sistema que cria o Application com 'new'.
 * Em todos os outros lugares, o Application é obtido via:
 *
 *   • Application::getInstance()   → Acesso direto (Singleton).
 *   • Container DI                 → Injeção de dependência.
 *
 * Isso garante a UNICIDADE da rainha: apenas o bootstrap pode criá-la.
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */

// ═══════════════════════════════════════════════════════════════════════════
// 1️⃣ IMPORTAÇÕES (USE STATEMENTS)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Importa a classe Application (o Kernel Singleton).
 *
 * O Kernel é a rainha da colméia: único, imortal e inviolável.
 * Ele será criado no final deste arquivo e retornado para o front controller.
 */
use DomainSystem\Core\Application;

/**
 * Importa a classe Container (o Container de Injeção de Dependências).
 *
 * O Container é o "Almoxarifado" do sistema: ele gerencia a criação
 * e distribuição de objetos (serviços, repositórios, controladores).
 */
use DomainSystem\Core\Container\Container;

/**
 * Importa a classe EventDispatcher (o Despachante de Eventos).
 *
 * O Dispatcher é o "Rádio Comunicador" do sistema: ele permite que
 * plugins se comuniquem sem se conhecerem, via eventos (dispatch)
 * e ouvintes (listeners).
 */
use DomainSystem\Core\Events\EventDispatcher;

// ═══════════════════════════════════════════════════════════════════════════
// 2️⃣ DEFINIÇÃO DA RAIZ DO SISTEMA
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Define a constante DOMAIN_SYSTEM_ROOT como o diretório atual.
 *
 * Essa constante é usada em todo o sistema para referenciar caminhos
 * absolutos, evitando problemas com caminhos relativos.
 *
 * @example DOMAIN_SYSTEM_ROOT . '/src/Plugins'
 * @example DOMAIN_SYSTEM_ROOT . '/config/plugins.json'
 * @example DOMAIN_SYSTEM_ROOT . '/themes/admin'
 */
define('DOMAIN_SYSTEM_ROOT', __DIR__);

// ═══════════════════════════════════════════════════════════════════════════
// 3️⃣ CARREGAMENTO DE VARIÁVEIS DE AMBIENTE
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Configuração do banco de dados principal (COMENTADO).
 *
 * Antigamente, o banco de dados SQLite era configurado aqui, com um
 * caminho fixo. Agora, ele é lido do arquivo .env, permitindo que
 * o sistema seja configurado em diferentes ambientes (dev, prod, test).
 *
 * ───────────────────────────────────────────────────────────────────────
 * POR QUE ISSO FOI COMENTADO?
 * ───────────────────────────────────────────────────────────────────────
 *
 * Porque agora usamos o arquivo .env para configurar o banco de dados.
 * Isso permite que o sistema seja configurado sem alterar o código.
 * É uma aplicação do princípio "Configuração sobre Código" (OCP).
 */
// $dbPath = DOMAIN_SYSTEM_ROOT . '/database.sqlite';
// putenv("DB_DSN=sqlite:{$dbPath}");

/**
 * Carrega as variáveis de ambiente do arquivo .env.
 *
 * O arquivo .env contém configurações sensíveis (chaves de API, credenciais
 * de banco de dados, chaves de criptografia) que NÃO devem estar no código.
 *
 * ───────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: O AMBIENTE DA COLMÉIA
 * ───────────────────────────────────────────────────────────────────────
 *
 * O arquivo .env é como o ambiente da colméia: temperatura, umidade,
 * disponibilidade de alimento. Cada colméia tem seu próprio ambiente,
 * e cada instalação do Domain System tem seu próprio .env.
 *
 * ───────────────────────────────────────────────────────────────────────
 * INI_SCANNER_RAW
 * ───────────────────────────────────────────────────────────────────────
 *
 * Usa INI_SCANNER_RAW para evitar que valores como "true" ou "false"
 * sejam convertidos automaticamente para booleanos. Isso garante que
 * o valor lido seja EXATAMENTE o valor escrito no arquivo .env.
 */
$envFile = DOMAIN_SYSTEM_ROOT . '/.env';

/**
 * Verifica se o arquivo .env existe.
 *
 * Se não existir, o sistema continua com valores padrão (fallback).
 * Em produção, o servidor deve prover as variáveis de ambiente.
 */
if (file_exists($envFile)) {

    /**
     * Faz o parse do arquivo .env, retornando um array associativo.
     *
     * @example ['APP_KEY' => 'base64:...', 'DB_DSN' => 'sqlite:...']
     */
    $envVariables = parse_ini_file($envFile, false, INI_SCANNER_RAW);

    /**
     * Verifica se o parse foi bem-sucedido (retornou um array).
     */
    if (is_array($envVariables)) {

        /**
         * Itera sobre cada variável de ambiente e a define no sistema.
         */
        foreach ($envVariables as $key => $value) {

            /**
             * Remove aspas simples e duplas ao redor do valor.
             *
             * @example '"valor"' → 'valor'
             * @example "'valor'" → 'valor'
             */
            $value = trim($value, '"\'');

            /**
             * Define a variável via putenv() — acessível via getenv().
             */
            putenv("$key=$value");

            /**
             * Define a variável em $_ENV — acessível como array superglobal.
             */
            $_ENV[$key] = $value;

            /**
             * Define a variável em $_SERVER — acessível como array superglobal.
             */
            $_SERVER[$key] = $value;
        }
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// 4️⃣ CONFIGURAÇÃO DE FUSO HORÁRIO
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Configura o fuso horário padrão do sistema.
 *
 * Lê a variável APP_TIMEZONE do .env, ou usa 'America/Sao_Paulo' como padrão.
 *
 * ───────────────────────────────────────────────────────────────────────
 * POR QUE ISSO É IMPORTANTE?
 * ───────────────────────────────────────────────────────────────────────
 *
 * Sem isso, datas e horas seriam calculadas com o fuso horário do servidor,
 * o que poderia causar inconsistências (ex: consultas agendadas para
 * "ontem" quando o servidor está em outro fuso).
 */
$timezone = getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo';
date_default_timezone_set($timezone);

// ═══════════════════════════════════════════════════════════════════════════
// 5️⃣ CARREGAMENTO DO AUTOLOAD DO COMPOSER
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Carrega o autoload do Composer.
 *
 * O autoload registra todas as classes do projeto (src/Core, src/Plugins)
 * e das dependências externas (vendor/) para que sejam carregadas
 * automaticamente quando usadas.
 *
 * ───────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: O MAPA GENÉTICO
 * ───────────────────────────────────────────────────────────────────────
 *
 * O autoload é como o DNA da colméia: ele sabe onde encontrar cada
 * "gene" (classe) quando precisa. Sem ele, o sistema não saberia
 * onde procurar as classes.
 */
require_once __DIR__ . '/vendor/autoload.php';

// ═══════════════════════════════════════════════════════════════════════════
// 6️⃣ AUTOLOADER CUSTOMIZADO (BUNDLED PLUGINS)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Registra um autoloader customizado para resolver classes dentro
 * dos bundled_plugins sem depender do composer dump-autoload.
 *
 * ───────────────────────────────────────────────────────────────────────
 * POR QUE ISSO É NECESSÁRIO?
 * ───────────────────────────────────────────────────────────────────────
 *
 * Os bundled_plugins são plugins que vivem dentro do clinic_pack,
 * empacotados como um "kit" de plugins. Eles NÃO são registrados
 * no autoload do Composer, pois são descobertos dinamicamente pelo
 * PluginManager.
 *
 * Este autoloader permite que o PluginManager os descubra e carregue
 * sem precisar rodar "composer dump-autoload" a cada novo plugin.
 *
 * ───────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: O RECONHECIMENTO DE FEROMÔNIOS
 * ───────────────────────────────────────────────────────────────────────
 *
 * Este autoloader é como o sistema de reconhecimento de feromônios
 * das abelhas: quando uma abelha encontra um feromônio desconhecido,
 * ela sabe onde procurar sua origem. Aqui, quando o sistema encontra
 * uma classe de bundled_plugin, ele sabe onde procurá-la.
 */
spl_autoload_register(function ($class) {

    /**
     * Prefixo do namespace dos plugins.
     */
    $prefix = 'DomainSystem\\Plugins\\';

    /**
     * Verifica se a classe pertence ao namespace de plugins.
     */
    if (str_starts_with($class, $prefix)) {

        /**
         * Remove o prefixo do namespace, deixando apenas o caminho relativo.
         *
         * @example 'DomainSystem\Plugins\appointments\Plugin'
         *          → 'appointments\Plugin'
         */
        $relativeClass = substr($class, strlen($prefix));

        /**
         * Monta o caminho do arquivo dentro de bundled_plugins.
         *
         * @example __DIR__ . '/src/Plugins/clinic_pack/bundled_plugins/appointments/Plugin.php'
         */
        $file = __DIR__ . '/src/Plugins/clinic_pack/bundled_plugins/' . str_replace('\\', '/', $relativeClass) . '.php';

        /**
         * Se o arquivo existir, carrega-o.
         */
        if (file_exists($file)) {
            require $file;
        }
    }
});

// ═══════════════════════════════════════════════════════════════════════════
// 7️⃣ CARREGAMENTO DOS HELPERS GLOBAIS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Carrega as funções helper globais do sistema.
 *
 * Essas funções são utilitários que podem ser usados em qualquer lugar
 * do código, como:
 *
 *   • encrypt_string()  → Criptografa uma string.
 *   • decrypt_string()  → Descriptografa uma string.
 *
 * ───────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: AS GLÂNDULAS DE FEROMÔNIO
 * ───────────────────────────────────────────────────────────────────────
 *
 * Os helpers são como as glândulas de feromônio das abelhas: pequenas
 * ferramentas que produzem mensagens úteis para o sistema. Eles estão
 * disponíveis em qualquer lugar, sem precisar de configuração especial.
 */
require_once __DIR__ . '/src/Core/helpers.php';

// ═══════════════════════════════════════════════════════════════════════════
// 8️⃣ INICIALIZAÇÃO DO ERROR HANDLER (SISTEMA IMUNOLÓGICO)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Define os caminhos para os arquivos de log e configuração de erro.
 */
$errorLogPath = DOMAIN_SYSTEM_ROOT . '/temp/error_logs.json';
$configPath = DOMAIN_SYSTEM_ROOT . '/config/plugins.json';
$disarmedPath = DOMAIN_SYSTEM_ROOT . '/temp/disarmed.json';

/**
 * Inicializa o ErrorLogger — o "Diário de Bordo" do sistema.
 *
 * O ErrorLogger registra todos os erros críticos em um arquivo JSON,
 * para que possam ser analisados posteriormente pelo Painel de Supervisão.
 *
 * ───────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: A MEMÓRIA DA COLMEIA
 * ───────────────────────────────────────────────────────────────────────
 *
 * O ErrorLogger é como a memória da colméia: ele registra tudo o que
 * aconteceu de errado, para que a rainha (Kernel) possa tomar decisões.
 */
$logger = new \DomainSystem\Core\Error\ErrorLogger($errorLogPath);

/**
 * Inicializa o CircuitBreaker — o "Disjuntor" do sistema.
 *
 * O CircuitBreaker detecta plugins que causam erros fatais e os
 * desativa automaticamente, protegendo o resto do sistema.
 *
 * ───────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: O SISTEMA IMUNOLÓGICO
 * ───────────────────────────────────────────────────────────────────────
 *
 * O CircuitBreaker é como o sistema imunológico da colméia: quando
 * detecta uma ameaça (plugin defeituoso), ele o isola e o elimina,
 * protegendo o resto da colméia.
 */
$circuitBreaker = new \DomainSystem\Core\Error\CircuitBreaker($configPath, $disarmedPath);

/**
 * Inicializa o ErrorRenderer — o "Tradutor Visual" dos erros.
 *
 * O ErrorRenderer transforma erros crípticos em telas amigáveis para
 * o usuário, com informações úteis para debugging.
 */
$renderer = new \DomainSystem\Core\Error\ErrorRenderer();

/**
 * Inicializa o ErrorHandler — o "Gerente de Crises" do sistema.
 *
 * O ErrorHandler coordena o ErrorLogger, o CircuitBreaker e o ErrorRenderer,
 * registrando erros, ativando o disjuntor e renderizando telas amigáveis.
 *
 * ───────────────────────────────────────────────────────────────────────
 * register()
 * ───────────────────────────────────────────────────────────────────────
 *
 * O método register() conecta o ErrorHandler aos mecanismos nativos
 * do PHP (set_error_handler, set_exception_handler, register_shutdown_function),
 * garantindo que NENHUM erro passe despercebido.
 */
$errorHandler = new \DomainSystem\Core\Error\ErrorHandler($logger, $circuitBreaker, $renderer);
$errorHandler->register();

// ═══════════════════════════════════════════════════════════════════════════
// 9️⃣ CRIAÇÃO DOS SERVIÇOS ESSENCIAIS
// ═══════════════════════════════════════════════════════════════════════════

/**
 * Cria o Container de Injeção de Dependências.
 *
 * O Container é o "Almoxarifado" do sistema: gerencia a criação e
 * distribuição de objetos (serviços, repositórios, controladores).
 *
 * ───────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: O CORPO DA RAINHA
 * ───────────────────────────────────────────────────────────────────────
 *
 * O Container é como o corpo da rainha: ele contém todos os órgãos
 * (serviços) necessários para o funcionamento da colméia. Cada órgão
 * tem sua função específica, e a rainha coordena todos eles.
 */
$container = new Container();

/**
 * Cria o Despachante de Eventos.
 *
 * O Dispatcher é o "Rádio Comunicador" do sistema: permite que
 * plugins se comuniquem sem se conhecerem, via eventos.
 *
 * ───────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: O SISTEMA DE FEROMÔNIOS
 * ───────────────────────────────────────────────────────────────────────
 *
 * O Dispatcher é como o sistema de feromônios da colméia: as abelhas
 * (plugins) se comunicam via mensagens químicas (eventos), sem
 * precisar se conhecerem diretamente.
 */
$dispatcher = new EventDispatcher();

/**
 * Registra o DashboardWidgetRegistry no Container como singleton.
 *
 * O DashboardWidgetRegistry é um serviço que permite que plugins
 * registrem widgets no dashboard do admin. Ele é registrado aqui
 * (antes do Application) para estar disponível durante o boot.
 */
$container->singleton(\DomainSystem\Core\Registry\DashboardWidgetRegistry::class, function() {
    return new \DomainSystem\Core\Registry\DashboardWidgetRegistry();
});

// ═══════════════════════════════════════════════════════════════════════════
// 🔟 CRIAÇÃO DO KERNEL (O NASCIMENTO DA RAINHA — SINGLETON)
// ═══════════════════════════════════════════════════════════════════════════

/**
 * 🐝 CRIA O KERNEL (APPLICATION) — O NASCIMENTO DA RAINHA!
 *
 * ───────────────────────────────────────────────────────────────────────
 * ESTE É O MOMENTO MAIS IMPORTANTE DO BOOTSTRAP
 * ───────────────────────────────────────────────────────────────────────
 *
 * Aqui, o Kernel (Application) é criado. Ele é a rainha da colméia:
 * único, imortal e inviolável. A partir deste momento:
 *
 *   1. O Singleton é ativado (self::$instance = $this dentro do construtor).
 *   2. O Kernel se registra no Container (para ser distribuído via DI).
 *   3. Os serviços essenciais são inicializados (Container, Dispatcher,
 *      SessionManager, PluginManager, Router, ThemeManager, etc.).
 *   4. O sistema está pronto para o boot final ($app->boot()).
 *
 * ───────────────────────────────────────────────────────────────────────
 * POR QUE O CONSTRUTOR É PÚBLICO?
 * ───────────────────────────────────────────────────────────────────────
 *
 * Em um Singleton clássico, o construtor seria privado. Aqui, ele é
 * público porque o BOOTSTRAP precisa criar o Kernel. Mas a unicidade
 * é garantida de outra forma:
 *
 *   • Este é o ÚNICO lugar do sistema que cria o Kernel com 'new'.
 *   • Após a criação, self::$instance armazena a instância.
 *   • O Container DI registra o Kernel como singleton.
 *
 * Portanto, mesmo com o construtor público, o Kernel permanece ÚNICO
 * na prática. Este arquivo é o "útero" da rainha: apenas ele pode
 * criá-la.
 *
 * ───────────────────────────────────────────────────────────────────────
 * EAGER LOADING
 * ───────────────────────────────────────────────────────────────────────
 *
 * O Kernel é criado IMEDIATAMENTE (Eager Loading), sem esperar.
 * Isso porque:
 *
 *   1. O Kernel será usado SEMPRE (cada requisição precisa dele).
 *   2. A criação não é pesada (não carrega todos os plugins, só a estrutura).
 *   3. Precisa estar disponível imediatamente (o front controller precisa dele).
 *
 * ───────────────────────────────────────────────────────────────────────
 * PARÂMETROS
 * ───────────────────────────────────────────────────────────────────────
 *
 * @param Container       $container   O Container de Injeção de Dependências.
 * @param EventDispatcher $dispatcher  O Despachante de Eventos.
 * @param string          $basePath    O caminho base do projeto (DOMAIN_SYSTEM_ROOT).
 */
$app = new Application($container, $dispatcher, DOMAIN_SYSTEM_ROOT);

// ═══════════════════════════════════════════════════════════════════════════
// 1️⃣1️⃣ RETORNO DO KERNEL
// ═══════════════════════════════════════════════════════════════════════════

/**
 * 🐝 RETORNA A RAINHA PARA O FRONT CONTROLLER
 *
 * Este arquivo retorna a instância do Kernel (Application) para quem
 * o incluiu. No caso, o arquivo `public/index.php` faz:
 *
 *     $app = require_once dirname(__DIR__) . '/bootstrap.php';
 *
 * E então chama `$app->boot()` para inicializar o sistema.
 *
 * ───────────────────────────────────────────────────────────────────────
 * A ANALOGIA DA NATUREZA: A RAINHA PRONTA PARA REINAR
 * ───────────────────────────────────────────────────────────────────────
 *
 * A rainha nasceu (Singleton ativado), mas ainda não começou a reinar.
 * O retorno deste arquivo é como a rainha sendo apresentada à colméia:
 * ela está pronta, mas o reinado (boot) ainda não começou.
 *
 * O `public/index.php` é quem dará o sinal para a rainha começar a
 * reinar, chamando `$app->boot()`.
 */
return $app;



