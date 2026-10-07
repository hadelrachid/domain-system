<?php

namespace DomainSystem\Core\Contracts;

/**
 * ════════════════════════════════════════════════════════════════════════════
 * INTERFACE: SessionManagerInterface (O Contrato da Sessão)
 * ════════════════════════════════════════════════════════════════════════════
 *
 * OBJETIVO ARQUITETURAL:
 * ──────────────────────
 * Esta interface define o contrato oficial para o gerenciamento de sessões no
 * DomainSystem OS. Ela é a "tomada fêmea" (Interface/Contrato) que qualquer
 * implementação concreta de gerenciador de sessão deve respeitar.
 *
 * POR QUE UMA INTERFACE?
 * ──────────────────────
 * Seguindo rigorosamente o Princípio da Inversão de Dependência (DIP - o "D"
 * do SOLID), todo o sistema depende desta INTERFACE, e não da implementação
 * concreta (`SessionManager`). Isso traz benefícios diretos:
 *
 *   1. TESTABILIDADE: Nos testes unitários, podemos criar mocks/stubs desta
 *      interface (`$this->createMock(SessionManagerInterface::class)`) sem
 *      precisar iniciar uma sessão PHP real.
 *
 *   2. DESACOPLAMENTO: Se um dia precisarmos trocar a implementação (ex: usar
 *      Redis, memcached ou uma API de sessão externa), basta criar uma nova
 *      classe que implemente esta interface. Nenhum código consumidor muda.
 *
 *   3. AUDITORIA: As testes arquiteturais (`NoSuperglobalsInControllersTest`)
 *      podem verificar que nenhum controlador acessa `$_SESSION` diretamente,
 *      forçando o uso deste contrato.
 *
 * REGISTRO NO CONTAINER:
 * ──────────────────────
 * No `CoreServiceProvider::register()`, este contrato é vinculado à sua
 * implementação concreta como um singleton:
 *
 *   $container->singleton(
 *       SessionManagerInterface::class,
 *       SessionManager::class
 *   );
 *
 * Assim, quando qualquer classe pede `SessionManagerInterface` via injeção
 * de dependência, o Container entrega automaticamente a instância correta.
 *
 * PADRÃO DE PROJETO: Strategy / Dependency Inversion
 * PRINCÍPIO SOLID:   ISP (Interface Segregation) + DIP
 *
 * IMPLEMENTAÇÃO CONCRETA:
 *   → src/Core/Http/SessionManager.php
 */
interface SessionManagerInterface
{
    /**
     * Inicia a sessão de forma segura.
     *
     * Implementações devem:
     *   - Configurar cookies com flags seguras (HttpOnly, SameSite, Secure).
     *   - Ativar o modo estrito do PHP (`session.use_strict_mode`).
     *   - Isolar o nome da sessão para evitar conflitos.
     *   - Garantir a existência de um token CSRF inicial.
     *
     * Este método é chamado pelo Kernel no início do boot (`Application::boot()`),
     * antes que qualquer plugin seja carregado.
     *
     * @return void
     */
    public function start(): void;

    /**
     * Define um valor na sessão.
     *
     * @param string $key   A chave (identificador) do valor.
     * @param mixed  $value O valor a ser armazenado (string, array, objeto, etc).
     * @return void
     */
    public function set(string $key, $value): void;

    /**
     * Recupera um valor da sessão.
     *
     * @param string $key     A chave a ser buscada.
     * @param mixed  $default Valor padrão retornado caso a chave não exista.
     * @return mixed O valor armazenado ou o valor padrão.
     */
    public function get(string $key, $default = null);

    /**
     * Verifica se uma chave existe na sessão.
     *
     * Diferente de `get()`, este método permite distinguir entre um valor
     * nulo e uma chave inexistente (útil em fluxos de autenticação).
     *
     * @param string $key A chave a ser verificada.
     * @return bool True se a chave existir, False caso contrário.
     */
    public function has(string $key): bool;

    /**
     * Remove uma chave específica da sessão.
     *
     * Usado, por exemplo, para limpar dados temporários (como o estado
     * de um fluxo de 2FA) sem destruir a sessão inteira.
     *
     * @param string $key A chave a ser removida.
     * @return void
     */
    public function remove(string $key): void;

    /**
     * Destrói completamente a sessão atual.
     *
     * Implementações devem, idealmente, também limpar o cookie de sessão
     * do navegador. Usado tipicamente no logout.
     *
     * @return void
     */
    public function destroy(): void;

    /**
     * Regenera o ID da sessão, preservando os dados.
     *
     * MEDIDA DE SEGURANÇA CRÍTICA: Deve ser chamado imediatamente após
     * o login bem-sucedido de um usuário, para prevenir ataques de
     * "Session Fixation" (onde um atacante força um ID de sessão conhecido
     * antes da vítima se autenticar).
     *
     * @return void
     */
    public function regenerate(): void;

    /**
     * Define uma mensagem "flash" (temporária) na sessão.
     *
     * Mensagens flash são exibidas UMA ÚNICA VEZ ao usuário e depois
     * removidas automaticamente. São ideais para feedback após
     * redirecionamentos HTTP (ex: "Usuário criado com sucesso!").
     *
     * @param string $type    O tipo da mensagem (ex: 'success', 'error', 'warning', 'info').
     * @param string $message O conteúdo da mensagem.
     * @return void
     */
    public function setFlash(string $type, string $message): void;

    /**
     * Recupera E REMOVE a mensagem flash atual da sessão.
     *
     * O comportamento "destrutivo" é intencional: ao ler a flash, ela é
     * imediatamente apagada para garantir que não seja exibida novamente
     * em requisições subsequentes.
     *
     * @return array|null Retorna um array com as chaves 'type' e 'msg',
     *                    ou null se não houver mensagem flash.
     */
    public function getFlash(): ?array;

    /**
     * Retorna o token CSRF atual da sessão.
     *
     * Este token é usado para proteger o sistema contra ataques CSRF
     * (Cross-Site Request Forgery). Ele deve ser embutido em todos os
     * formulários e requisições de mutação (POST, PUT, PATCH, DELETE).
     *
     * @return string O token CSRF armazenado na sessão.
     */
    public function getCsrfToken(): string;

    /**
     * Valida um token CSRF recebido contra o armazenado na sessão.
     *
     * Implementações DEVEM usar comparação de tempo constante (`hash_equals`)
     * para prevenir ataques de "timing attack" (onde um atacante mede o tempo
     * de resposta para deduzir o token correto caractere por caractere).
     *
     * Este método é consumido pelo `CsrfMiddleware`, que intercepta todas
     * as requisições de mutação antes que elas cheguem aos controladores.
     *
     * @param string|null $token O token a ser validado (pode vir do corpo, query ou header).
     * @return bool True se o token for válido, False caso contrário.
     */
    public function validateCsrfToken(?string $token): bool;
}

