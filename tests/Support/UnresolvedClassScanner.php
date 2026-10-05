<?php

namespace DomainSystem\Tests\Support;

/**
 * Detector estático de "Class not found" por falta de `use`.
 *
 * Para cada nome de classe NÃO qualificado usado em `new X`, `X::`, `instanceof X`
 * ou como tipo antes de uma variável (`X $param`), verifica se ele é resolvível:
 *   1. está importado por `use`;
 *   2. é declarado no próprio arquivo;
 *   3. existe um arquivo X.php no mesmo namespace (PSR-4 sob src/).
 * Caso contrário o PHP procuraria "Namespace\X" e falharia em tempo de execução.
 */
final class UnresolvedClassScanner
{
    private const BUILTIN_TYPES = [
        'int', 'float', 'string', 'bool', 'array', 'callable', 'iterable', 'object',
        'mixed', 'void', 'null', 'never', 'false', 'true', 'self', 'static', 'parent',
    ];

    /** @var array<string, array<string, true>> cache: dir => [nome minúsculo => true] */
    private static array $dirCache = [];

    /**
     * @return array<int, array{line:int, name:string}>
     */
    public static function scan(string $code, string $srcRoot): array
    {
        $tokens = [];
        foreach (token_get_all($code) as $tok) {
            if (is_array($tok)) {
                if (in_array($tok[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG], true)) {
                    continue;
                }
                $tokens[] = ['id' => $tok[0], 'text' => $tok[1], 'line' => $tok[2]];
            } else {
                $tokens[] = ['id' => $tok, 'text' => $tok, 'line' => $tokens ? end($tokens)['line'] : 1];
            }
        }

        $n = count($tokens);
        $namespace = null;
        $imports = [];   // alias minúsculo => true
        $declared = [];  // nomes declarados no arquivo (minúsculo)
        $inImport = [];  // índices que pertencem a instruções use de topo
        $depth = 0;

        for ($i = 0; $i < $n; $i++) {
            $id = $tokens[$i]['id'];

            if ($id === T_NAMESPACE && isset($tokens[$i + 1]) && in_array($tokens[$i + 1]['id'], [T_STRING, T_NAME_QUALIFIED], true)) {
                $namespace = $tokens[$i + 1]['text'];
            }

            if ($id === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $depth++;
            } elseif ($id === '}') {
                $depth--;
            }

            if ($id === T_USE && $depth === 0) {
                $isFnOrConst = isset($tokens[$i + 1]) && in_array($tokens[$i + 1]['id'], [T_FUNCTION, T_CONST], true);
                for ($k = $i; $k < $n && $tokens[$k]['id'] !== ';'; $k++) {
                    $inImport[$k] = true;
                    if ($isFnOrConst) {
                        continue;
                    }
                    if (in_array($tokens[$k]['id'], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                        $parts = explode('\\', $tokens[$k]['text']);
                        $imports[strtolower(end($parts))] = true;
                    }
                }
            }

            if (in_array($id, [T_CLASS, T_INTERFACE, T_TRAIT], true) || (defined('T_ENUM') && $id === T_ENUM)) {
                $prev = $tokens[$i - 1]['id'] ?? null;
                if ($prev !== T_NEW && $prev !== T_DOUBLE_COLON && ($tokens[$i + 1]['id'] ?? null) === T_STRING) {
                    $declared[strtolower($tokens[$i + 1]['text'])] = true;
                }
            }
        }

        if ($namespace === null) {
            return []; // sem namespace: nomes resolvem no global
        }

        $findings = [];
        for ($i = 0; $i < $n; $i++) {
            if (isset($inImport[$i]) || $tokens[$i]['id'] !== T_STRING) {
                continue;
            }
            $name = $tokens[$i]['text'];
            $prev = $tokens[$i - 1]['id'] ?? null;
            $next = $tokens[$i + 1]['id'] ?? null;

            $isClassRef =
                $prev === T_NEW
                || $prev === T_INSTANCEOF
                || ($next === T_DOUBLE_COLON && !in_array($prev, [T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NS_SEPARATOR, T_NULLSAFE_OBJECT_OPERATOR], true))
                || ($next === T_VARIABLE && !in_array($prev, [T_OBJECT_OPERATOR, T_DOUBLE_COLON], true))
                || ($next === '&' && ($tokens[$i + 2]['id'] ?? null) === T_VARIABLE)
                || ($next === T_ELLIPSIS && ($tokens[$i + 2]['id'] ?? null) === T_VARIABLE);

            if (!$isClassRef) {
                continue;
            }
            $lower = strtolower($name);
            if (in_array($lower, self::BUILTIN_TYPES, true) || isset($imports[$lower]) || isset($declared[$lower])) {
                continue;
            }
            if (self::existsInNamespace($namespace, $name, $srcRoot)) {
                continue;
            }
            $findings[] = ['line' => $tokens[$i]['line'], 'name' => $name];
        }

        return $findings;
    }

    private static function existsInNamespace(string $namespace, string $name, string $srcRoot): bool
    {
        if (!str_starts_with($namespace, 'DomainSystem\\') && $namespace !== 'DomainSystem') {
            return false;
        }
        $relative = str_replace('\\', '/', substr($namespace, strlen('DomainSystem')));
        $dir = rtrim($srcRoot . $relative, '/');

        if (!isset(self::$dirCache[$dir])) {
            self::$dirCache[$dir] = [];
            if (is_dir($dir)) {
                foreach (scandir($dir) as $entry) {
                    if (str_ends_with($entry, '.php')) {
                        self::$dirCache[$dir][strtolower(substr($entry, 0, -4))] = true;
                    }
                }
            }
        }
        return isset(self::$dirCache[$dir][strtolower($name)]);
    }
}
