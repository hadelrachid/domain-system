<?php

namespace DomainSystem\Tests\Support;

/**
 * Detector estático de "Undefined variable".
 *
 * Analisa cada função/método/closure de forma isolada (como o PHP faz) e reporta
 * variáveis LIDAS que nunca foram definidas naquele escopo (parâmetro, use(),
 * atribuição, foreach, catch, global, static, list(), by-ref).
 *
 * É propositalmente permissivo (não considera a ordem do fluxo) para evitar
 * falsos positivos: o objetivo é pegar erros estruturais, como esquecer o
 * `use ($runtime)` em um closure aninhado ou usar `$session` sem declará-lo.
 */
final class UndefinedVariableScanner
{
    private const IGNORED = [
        'this', 'GLOBALS', '_GET', '_POST', '_SERVER', '_SESSION', '_COOKIE',
        '_FILES', '_REQUEST', '_ENV', 'argv', 'argc', 'http_response_header',
    ];

    /** Funções que preenchem variáveis passadas por referência. */
    private const BYREF_FUNCS = [
        'preg_match', 'preg_match_all', 'exec', 'parse_str', 'getimagesize',
        'similar_text', 'openssl_sign', 'proc_open', 'stream_select', 'sscanf',
        'str_replace', 'preg_replace', 'preg_replace_callback', 'is_callable',
        'openssl_random_pseudo_bytes', 'stream_socket_client', 'fsockopen',
        'pfsockopen', 'stream_socket_server', 'getmxrr', 'dns_get_record',
        'mb_parse_str', 'array_multisort', 'passthru', 'system',
    ];

    private const ASSIGN_OPS = [
        '=', T_PLUS_EQUAL, T_MINUS_EQUAL, T_MUL_EQUAL, T_DIV_EQUAL, T_CONCAT_EQUAL,
        T_MOD_EQUAL, T_POW_EQUAL, T_AND_EQUAL, T_OR_EQUAL, T_XOR_EQUAL,
        T_SL_EQUAL, T_SR_EQUAL, T_COALESCE_EQUAL,
    ];

    /** @var array<int, array{id:int|string, text:string, line:int}> */
    private array $t = [];

    /** @var array<int, array{line:int, var:string}> */
    private array $findings = [];

    /**
     * @return array<int, array{line:int, var:string}>
     */
    public static function scan(string $code): array
    {
        $scanner = new self();
        $scanner->load($code);
        $scanner->walkTopLevel();
        return $scanner->findings;
    }

    private function load(string $code): void
    {
        foreach (token_get_all($code) as $tok) {
            if (is_array($tok)) {
                if (in_array($tok[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG], true)) {
                    continue;
                }
                $this->t[] = ['id' => $tok[0], 'text' => $tok[1], 'line' => $tok[2]];
            } else {
                $line = $this->t ? end($this->t)['line'] : 1;
                $this->t[] = ['id' => $tok, 'text' => $tok, 'line' => $line];
            }
        }
    }

    private function walkTopLevel(): void
    {
        $n = count($this->t);
        for ($i = 0; $i < $n;) {
            if ($this->t[$i]['id'] === T_FUNCTION && !$this->isUseImport($i)) {
                [$i] = $this->parseFunction($i);
                continue;
            }
            $i++;
        }
    }

    private function isUseImport(int $i): bool
    {
        return $i > 0 && $this->t[$i - 1]['id'] === T_USE;
    }

    /**
     * @return array{0:int, 1:array<int, array{line:int, var:string}>} [próximo índice, variáveis do use()]
     */
    private function parseFunction(int $i): array
    {
        $j = $i + 1;
        if ($this->t[$j]['id'] === '&') {
            $j++;
        }
        if ($this->t[$j]['id'] !== '(') {
            $j++; // nome da função/método
        }

        $params = [];
        $j = $this->collectParenVars($j, $params);

        $uses = [];
        if (($this->t[$j]['id'] ?? null) === T_USE) {
            $j++;
            $j = $this->collectParenVars($j, $uses);
        }

        $n = count($this->t);
        while ($j < $n && $this->t[$j]['id'] !== '{' && $this->t[$j]['id'] !== ';') {
            $j++;
        }
        if ($j >= $n || $this->t[$j]['id'] === ';') {
            return [$j + 1, $this->toReads($uses)]; // abstrata/interface
        }

        $close = $this->findClose($j);
        $defined = array_merge(array_column($params, 'var'), array_column($uses, 'var'));
        $this->analyzeBody($j + 1, $close, $defined);

        return [$close + 1, $this->toReads($uses)];
    }

    /** @param array<int, array{line:int, var:string}> $uses */
    private function toReads(array $uses): array
    {
        return $uses;
    }

    private function analyzeBody(int $from, int $to, array $defined): void
    {
        $defs = array_fill_keys($defined, true);
        $reads = [];
        $defIdx = [];      // índices de tokens que são definições
        $lenientIdx = [];  // índices dentro de isset()/empty()/unset()
        $skip = false;
        $anonBodies = []; // [índice do '{'] => índice do '}' de classes anônimas

        for ($i = $from; $i < $to;) {
            $tk = $this->t[$i];
            $id = $tk['id'];

            // Corpo de classe anônima: só os métodos têm escopo próprio a analisar.
            if (isset($anonBodies[$i])) {
                $end = $anonBodies[$i];
                for ($j = $i + 1; $j < $end;) {
                    if ($this->t[$j]['id'] === T_FUNCTION && !$this->isUseImport($j)) {
                        [$j] = $this->parseFunction($j);
                        continue;
                    }
                    $j++;
                }
                $i = $end + 1;
                continue;
            }

            if ($id === T_CLASS && ($this->t[$i - 1]['id'] ?? null) === T_NEW) {
                for ($k = $i + 1; $k < $to; $k++) {
                    if ($this->t[$k]['id'] === '(') {
                        $k = $this->findClose($k);
                        continue;
                    }
                    if ($this->t[$k]['id'] === '{') {
                        $anonBodies[$k] = $this->findClose($k);
                        break;
                    }
                }
            }

            // Closure/função aninhada: escopo próprio. As variáveis do use() são LEITURAS aqui.
            if ($id === T_FUNCTION && !$this->isUseImport($i)) {
                [$next, $uses] = $this->parseFunction($i);
                foreach ($uses as $u) {
                    $reads[] = $u;
                }
                $i = $next;
                continue;
            }

            // Arrow fn: parâmetros viram definições (aproximação permissiva).
            if ($id === T_FN) {
                $j = $i + 1;
                if ($this->t[$j]['id'] === '&') {
                    $j++;
                }
                $p = [];
                $j = $this->collectParenVars($j, $p);
                foreach ($p as $pv) {
                    $defs[$pv['var']] = true;
                }
                $i = $j;
                continue;
            }

            if (in_array($id, [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE, T_EVAL], true)
                || $id === '$'
                || ($id === T_STRING && in_array(strtolower($tk['text']), ['extract', 'get_defined_vars'], true))) {
                $skip = true;
            }

            if ($id === T_FOREACH) {
                $open = $i + 1;
                $close = $this->findClose($open);
                $as = null;
                for ($k = $open; $k < $close; $k++) {
                    if ($this->t[$k]['id'] === T_AS) {
                        $as = $k;
                        break;
                    }
                }
                if ($as !== null) {
                    for ($k = $as; $k < $close; $k++) {
                        $defIdx[$k] = true;
                    }
                }
            }

            if ($id === T_CATCH) {
                $open = $i + 1;
                $close = $this->findClose($open);
                for ($k = $open; $k < $close; $k++) {
                    $defIdx[$k] = true;
                }
            }

            if ($id === T_GLOBAL) {
                for ($k = $i; $k < $to && $this->t[$k]['id'] !== ';'; $k++) {
                    $defIdx[$k] = true;
                }
            }

            if ($id === T_LIST && ($this->t[$i + 1]['id'] ?? null) === '(') {
                $close = $this->findClose($i + 1);
                for ($k = $i + 1; $k < $close; $k++) {
                    $defIdx[$k] = true;
                }
            }

            if ($id === '[') {
                $prev = $this->t[$i - 1]['id'] ?? null;
                $isOffset = in_array($prev, [T_VARIABLE, ']', ')', T_STRING, '}', T_OBJECT_OPERATOR, T_DOUBLE_COLON], true);
                $close = $this->findClose($i);
                if (!$isOffset && ($this->t[$close + 1]['id'] ?? null) === '=') {
                    for ($k = $i; $k < $close; $k++) {
                        $defIdx[$k] = true;
                    }
                }
            }

            if (in_array($id, [T_ISSET, T_EMPTY, T_UNSET], true) && ($this->t[$i + 1]['id'] ?? null) === '(') {
                $close = $this->findClose($i + 1);
                for ($k = $i + 1; $k < $close; $k++) {
                    $lenientIdx[$k] = true;
                }
            }

            if ($id === T_STRING && ($this->t[$i + 1]['id'] ?? null) === '('
                && in_array(strtolower($tk['text']), self::BYREF_FUNCS, true)) {
                $close = $this->findClose($i + 1);
                for ($k = $i + 1; $k < $close; $k++) {
                    $defIdx[$k] = true;
                }
            }

            if ($id === T_VARIABLE) {
                $name = substr($tk['text'], 1);
                $prevId = $this->t[$i - 1]['id'] ?? null;

                if (in_array($name, self::IGNORED, true) || $prevId === T_DOUBLE_COLON) {
                    $i++;
                    continue;
                }
                if ($prevId === T_STATIC) {
                    $defs[$name] = true;
                    $i++;
                    continue;
                }
                if ($prevId === '&' && in_array($this->t[$i - 2]['id'] ?? null, ['(', ','], true)) {
                    $defs[$name] = true;
                    $i++;
                    continue;
                }
                if (isset($defIdx[$i])) {
                    $defs[$name] = true;
                    $i++;
                    continue;
                }

                // $x = ...  |  $x['a'][] = ...
                $k = $i + 1;
                while (($this->t[$k]['id'] ?? null) === '[') {
                    $k = $this->findClose($k) + 1;
                }
                $nextId = $this->t[$k]['id'] ?? null;
                if (in_array($nextId, self::ASSIGN_OPS, true)) {
                    $defs[$name] = true;
                    $i++;
                    continue;
                }

                if (isset($lenientIdx[$i]) || ($this->t[$i + 1]['id'] ?? null) === T_COALESCE) {
                    $i++;
                    continue;
                }

                $reads[] = ['line' => $tk['line'], 'var' => $name];
            }

            $i++;
        }

        if ($skip) {
            return;
        }
        foreach ($reads as $r) {
            if (!isset($defs[$r['var']])) {
                $this->findings[] = $r;
            }
        }
    }

    /**
     * Coleta variáveis dentro de um par de parênteses que começa em $j.
     * @param array<int, array{line:int, var:string}> $out
     */
    private function collectParenVars(int $j, array &$out): int
    {
        if (($this->t[$j]['id'] ?? null) !== '(') {
            return $j;
        }
        $close = $this->findClose($j);
        for ($k = $j + 1; $k < $close; $k++) {
            if ($this->t[$k]['id'] === T_VARIABLE) {
                $out[] = ['line' => $this->t[$k]['line'], 'var' => substr($this->t[$k]['text'], 1)];
            }
        }
        return $close + 1;
    }

    private function findClose(int $open): int
    {
        $depth = 0;
        $n = count($this->t);
        for ($i = $open; $i < $n; $i++) {
            $id = $this->t[$i]['id'];
            if (in_array($id, ['(', '[', '{', T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)) {
                $depth++;
            } elseif (in_array($id, [')', ']', '}'], true)) {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }
        return $n - 1;
    }
}
