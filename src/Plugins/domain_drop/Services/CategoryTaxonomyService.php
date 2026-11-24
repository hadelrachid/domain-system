<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\CategoryTaxonomyInterface;

/**
 * Serviço de Taxonomia Canônica Oficial, Classificação Semântica e Filtros SQL (SOLID / SRP).
 * 
 * Totalmente alinhado às 548 categorias oficiais da AliExpress Open Platform (IOP SDK)
 * para produtos em território nacional brasileiro, sem invenção de termos.
 */
class CategoryTaxonomyService implements CategoryTaxonomyInterface
{
    // Categorias Oficiais alinhadas 1:1 com a AliExpress Open Platform
    public const CAT_TECH         = 'Eletrônicos & Telefonia';          // IDs 44, 509, 7
    public const CAT_SMARTWATCH   = 'Relógios & Smartwatches';          // ID 1511
    public const CAT_HOME         = 'Casa, Iluminação & Eletro';        // IDs 15, 39, 6, 13
    public const CAT_TOOLS        = 'Ferramentas & Utilidades';         // IDs 1420, 30
    public const CAT_BEAUTY       = 'Beleza, Saúde & Maquiagem';        // IDs 66, 660103
    public const CAT_PETS         = 'Pet Shop';                         // ID 100006664 (Casa e jardim)
    public const CAT_AUTO         = 'Peças & Acessórios Automotivos';   // IDs 34, 201355758
    public const CAT_FASHION      = 'Roupas, Bolsas & Calçados';        // IDs 200000345, 200000343, 1524, 322
    public const CAT_LINGERIE     = 'Roupa Íntima & Pijamas';           // ID 200574005 (feminina, masculina, pijamas)
    public const CAT_ADULT        = 'Novidades & Uso Especial';         // ID 200000532

    public function getCanonicalCategories(): array
    {
        return [
            self::CAT_TECH,
            self::CAT_SMARTWATCH,
            self::CAT_HOME,
            self::CAT_TOOLS,
            self::CAT_BEAUTY,
            self::CAT_PETS,
            self::CAT_AUTO,
            self::CAT_FASHION,
            self::CAT_LINGERIE,
            self::CAT_ADULT,
        ];
    }

    /**
     * Retorna os IDs numéricos oficiais correspondentes na árvore de categorias da AliExpress Open Platform.
     * Alinhado a categoria-e-atributos.pdf e dropshiper.pdf.
     *
     * @param string $category
     * @return array<int, int>
     */
    public function getAliExpressCategoryIds(string $category): array
    {
        $canonical = $this->normalizeCategory($category);
        return match ($canonical) {
            self::CAT_TECH       => [44, 509, 7],
            self::CAT_SMARTWATCH => [1511],
            self::CAT_HOME       => [15, 39, 6],
            self::CAT_TOOLS      => [1420, 30],
            self::CAT_BEAUTY     => [66],
            self::CAT_PETS       => [100006664, 15],
            self::CAT_AUTO       => [34],
            self::CAT_FASHION    => [200000345, 200000343, 1524, 322],
            self::CAT_LINGERIE   => [200574005],
            self::CAT_ADULT      => [200000532],
            default              => [44],
        };
    }

    /**
     * Retorna termos de busca otimizados em português para consulta no AliExpress.
     *
     * @param string $category
     * @return array<int, string>
     */
    public function getAliExpressSearchKeywords(string $category): array
    {
        $canonical = $this->normalizeCategory($category);
        return match ($canonical) {
            self::CAT_TECH       => ['fone bluetooth tws', 'carregador turbo usb c', 'fone sem fio', 'power bank'],
            self::CAT_SMARTWATCH => ['smartwatch', 'relogio inteligente', 'pulseira smartwatch'],
            self::CAT_HOME       => ['fita led rgb', 'luminaria tuya inteligente', 'lampada inteligente wifi', 'aspirador portatil'],
            self::CAT_TOOLS      => ['alicate crimpador decapador', 'jogo chave precisao catraca', 'ferramentas reparo manual'],
            self::CAT_BEAUTY     => ['escova secadora modeladora', 'maquiagem paleta', 'barbeador eletrico recarregavel'],
            self::CAT_PETS       => ['coleira led cachorro', 'escova pelos pet gato cão', 'brinquedo interativo pet'],
            self::CAT_AUTO       => ['suporte celular veicular', 'acessorios carro suporte', 'led veicular automotivo'],
            self::CAT_FASHION    => ['mochila executiva impermeavel', 'tenis casual confortavel', 'bolsa transversal feminina'],
            self::CAT_LINGERIE   => ['lingerie renda sexy', 'sutia sem costura push up', 'pijama cetim seda'],
            self::CAT_ADULT      => ['massageador muscular eletrico', 'rolo massagem relaxamento'],
            default              => ['eletronicos'],
        };
    }

    public function normalizeCategory(string $category): string
    {
        $raw = trim($category);
        if ($raw === '') {
            return self::CAT_TECH;
        }

        $lower = mb_strtolower($raw, 'UTF-8');
        $clean = preg_replace('/[^\p{L}\p{N}\s&]/u', ' ', $lower) ?? $lower;

        if (str_contains($clean, 'geral')) {
            return self::CAT_TOOLS;
        }

        // 1. Roupa Íntima & Pijamas (ID 200574005)
        if (str_contains($clean, 'lingerie') || str_contains($clean, 'sutia') || str_contains($clean, 'sutiã') || str_contains($clean, 'calcinha') || str_contains($clean, 'intima') || str_contains($clean, 'íntima') || str_contains($clean, 'pijama') || str_contains($clean, 'cueca') || str_contains($clean, 'boxer') || str_contains($clean, 'espartilho') || str_contains($clean, 'corset')) {
            return self::CAT_LINGERIE;
        }

        // 2. Novidades & Uso Especial / Adulto (ID 200000532)
        if (str_contains($clean, 'sex shop') || str_contains($clean, 'sexshop') || str_contains($clean, 'adulto') || str_contains($clean, '18') || str_contains($clean, 'vibrat') || str_contains($clean, 'massageador') || str_contains($clean, 'novidade') || str_contains($clean, 'especial')) {
            return self::CAT_ADULT;
        }

        // 3. Roupas, Bolsas & Calçados (IDs 200000345, 200000343, 1524, 322)
        if (str_contains($clean, 'roupa') || str_contains($clean, 'vestuario') || str_contains($clean, 'vestuário') || str_contains($clean, 'casaco') || str_contains($clean, 'jaqueta') || str_contains($clean, 'mochila') || str_contains($clean, 'bolsa') || str_contains($clean, 'carteira') || str_contains($clean, 'sapato') || str_contains($clean, 'tenis') || str_contains($clean, 'tênis') || str_contains($clean, 'calcado') || str_contains($clean, 'calçado') || (str_contains($clean, 'moda') && !str_contains($clean, 'intima') && !str_contains($clean, 'íntima'))) {
            return self::CAT_FASHION;
        }

        // 4. Pet Shop (ID 100006664)
        if (str_contains($clean, 'pet') || str_contains($clean, 'cachorro') || str_contains($clean, 'gato') || str_contains($clean, 'cao') || str_contains($clean, 'cão')) {
            return self::CAT_PETS;
        }

        // 5. Peças & Acessórios Automotivos (ID 34)
        if (str_contains($clean, 'veiculo') || str_contains($clean, 'veículo') || str_contains($clean, 'carro') || str_contains($clean, 'automotivo') || str_contains($clean, 'auto') || str_contains($clean, 'moto')) {
            return self::CAT_AUTO;
        }

        // 6. Ferramentas & Utilidades (ID 1420, 30)
        if (str_contains($clean, 'alicate') || str_contains($clean, 'ferramenta') || str_contains($clean, 'crimpador') || str_contains($clean, 'decapador') || str_contains($clean, 'chave de fenda') || str_contains($clean, 'utilidade') || str_contains($clean, 'segurança')) {
            return self::CAT_TOOLS;
        }

        // 7. Relógios & Smartwatches (ID 1511)
        if (str_contains($clean, 'smartwatch') || str_contains($clean, 'relogio') || str_contains($clean, 'relógio') || str_contains($clean, 'pulseira inteligente') || str_contains($clean, 'apple watch')) {
            return self::CAT_SMARTWATCH;
        }

        // 8. Beleza, Saúde & Maquiagem (ID 66, 660103)
        if (str_contains($clean, 'beleza') || str_contains($clean, 'cabelo') || str_contains($clean, 'pele') || str_contains($clean, 'secador') || str_contains($clean, 'escova') || str_contains($clean, 'saude') || str_contains($clean, 'saúde') || str_contains($clean, 'maquiagem') || str_contains($clean, 'batom')) {
            return self::CAT_BEAUTY;
        }

        // 9. Casa, Iluminação & Eletro (ID 15, 39, 6)
        if (str_contains($clean, 'casa') || str_contains($clean, 'led') || str_contains($clean, 'luminaria') || str_contains($clean, 'luminária') || str_contains($clean, 'lampada') || str_contains($clean, 'lâmpada') || str_contains($clean, 'iluminação') || str_contains($clean, 'eletro') || str_contains($clean, 'tuya')) {
            return self::CAT_HOME;
        }

        // 10. Eletrônicos & Telefonia (ID 44, 509, 7)
        return self::CAT_TECH;
    }

    public function classifyFromText(string $title, ?string $rawCategory = null): string
    {
        $t = mb_strtolower(trim($title), 'UTF-8');

        // 1. Roupa Íntima & Pijamas (Precedência alta sobre Moda geral para evitar falso-positivo)
        if (preg_match('/\b(lingerie|sutiã|sutia|calcinha|calcinhas|bojo|push up|push-up|guipir|bralette|camisola|baby doll|pijama|pijamas|renda sexy|natalina sexy|meia-calça|meias-calça|meia calça|meias calça|meia calca|meia-calca|meia arrastão|meia arrastao|cueca|cuecas|boxer|slip|corpete|espartilho|espartilhos|corset|cinta modeladora|peças íntimas|pecas intimas|roupa íntima)\b/iu', $t)) {
            return self::CAT_LINGERIE;
        }

        // 2. Novidades & Uso Especial / Adulto (+18)
        if (preg_match('/\b(sex shop|sexshop|adulto|\+18|vibratório|vibratorio|vibratória|vibratoria|vibração|vibracao|massageador|amendoim|estimulador|plug|lubrificante|uso especial)\b/iu', $t)) {
            return self::CAT_ADULT;
        }

        // 3. Roupas, Bolsas & Calçados
        if (preg_match('/\b(casaco|casacos|jaqueta|jaquetas|corta-vento|corta vento|ciclismo|corrida secagem|impermeável masculino|capuz capa|moletom|calça|calca|camisa|camiseta|vestido|saia|bermuda|roupa|roupas|vestuário|vestuario|mochila|mochilas|bolsa|bolsas|carteira|carteiras|pochete|tênis|tenis|sapato|sapatos|sandália|sandalia|chinelo|chinelos|bota|botas|mocassim|calçado|calçados|calcado|calcados|meia|meias|invisível|invisivel|chaveiro|chaveiros|cinto|cintos|boné|bone|óculos|oculos)\b/iu', $t)) {
            return self::CAT_FASHION;
        }

        // 4. Pet Shop
        if (preg_match('/\b(pet|pets|cachorro|cachorros|cão|cães|gato|gatos|coleira|trela|fita led noite segurança|fibra ótica recarregável cães)\b/iu', $t)) {
            return self::CAT_PETS;
        }

        // 5. Peças & Acessórios Automotivos
        if (preg_match('/\b(veicular|veículo|veiculo|carro|automotivo|ventilação de ar|suporte celular carro|suporte automotivo|telemóvel com ventosa|suporte magnético 360|peças automotivas)\b/iu', $t)) {
            return self::CAT_AUTO;
        }

        // 6. Ferramentas & Utilidades
        if (preg_match('/\b(alicate|alicates|crimpador|decapador|desencapar fios|chaves de fenda|chave de fenda|catraca|ferramentas|ferramenta|chaves de precisão|bits)\b/iu', $t)) {
            return self::CAT_TOOLS;
        }

        // 7. Relógios & Smartwatches
        if (preg_match('/\b(smartwatch|smartwatches|relógio inteligente|relogio inteligente|relógio|relogio|apple watch|amazfit|aurafit|pulseira homem de ferro)\b/iu', $t)) {
            return self::CAT_SMARTWATCH;
        }

        // 8. Beleza, Saúde & Maquiagem
        if (preg_match('/\b(secador|escova secadora|escova alisadora|alisador|modelador de cabelo|cabelo|curling|barbeador|barba|depilador|maquiagem|facial|pele|batom|batons|gloss|rímel|rimel|delineador|base líquida|base liquida|corretivo|pó compacto|po compacto|paleta de sombras|paleta sombras|blush|pincel|pincéis|pinceis|esponja maquiagem|lip tint)\b/iu', $t)) {
            return self::CAT_BEAUTY;
        }

        // 9. Casa, Iluminação & Eletro
        if (preg_match('/\b(fita de led|fita led|rgbic|tuya|luminária|luminaria|lâmpada|lampada|barra de luz|monitor luzes|iluminação|teto|armário|quarto|cozinha|aspirador|eletrodoméstico|eletrodomestico|liquidificador portátil)\b/iu', $t)) {
            return self::CAT_HOME;
        }

        // 10. Se houver categoria bruta informada da API oficial, normaliza diretamente
        if ($rawCategory !== null && trim($rawCategory) !== '' && trim($rawCategory) !== 'Geral') {
            return $this->normalizeCategory($rawCategory);
        }

        return self::CAT_TECH;
    }

    public function buildSqlFilter(string $requestedCategory, array &$params): string
    {
        $raw = trim($requestedCategory);
        $lower = mb_strtolower($raw, 'UTF-8');
        $clean = preg_replace('/[^\p{L}\p{N}\s&]/u', ' ', $lower) ?? $lower;

        // 0. Filtro específico de Áudio & Fones (Prioridade alta antes de eletrônicos gerais)
        if ($clean === 'audio' || $clean === 'áudio' || str_starts_with($clean, 'fone') || str_contains($clean, 'ouvido') || str_contains($clean, 'headphone')) {
            return "(
                (
                    category IN ('Áudio', 'Audio', 'Fones Bluetooth TWS')
                    OR title LIKE '%Fone%'
                    OR title LIKE '%Tws%'
                    OR title LIKE '%Headphone%'
                    OR title LIKE '%Earphone%'
                    OR title LIKE '%Microfone%'
                )
                AND title NOT LIKE '%Smartwatch%'
                AND title NOT LIKE '%Relógio%'
                AND title NOT LIKE '%Pulseira%'
                AND title NOT LIKE '%Chave%'
                AND title NOT LIKE '%Reparo%'
            )";
        }

        // 0.1 Filtro específico de Celular / Smartphone
        if ($clean === 'celular' || $clean === 'smartphone' || $clean === 'celulares') {
            return "(
                category IN ('Telefones e telecomunicações', 'Celulares', 'Smartphones')
                OR title LIKE '%Celular%'
                OR title LIKE '%Telefone%'
                OR title LIKE '%Smartphone%'
            )";
        }

        // 1. Roupa Íntima & Pijamas (ID 200574005)
        if (str_contains($clean, 'lingerie') || str_contains($clean, 'intima') || str_contains($clean, 'íntima') || str_contains($clean, 'pijama') || str_contains($clean, 'sutia') || str_contains($clean, 'sutiã') || str_contains($clean, 'meia calca') || str_contains($clean, 'meia calça') || str_contains($clean, 'cueca')) {
            return "(
                category IN ('Roupa Íntima & Pijamas', 'Roupa íntima', 'Moda Íntima, Lingeries & Pijamas', 'Moda Íntima', 'Lingeries', 'Lingerie')
                OR category LIKE '%Íntima%'
                OR category LIKE '%Intima%'
                OR category LIKE '%Lingerie%'
                OR title LIKE '%Lingerie%'
                OR title LIKE '%Sutiã%'
                OR title LIKE '%Sutia%'
                OR title LIKE '%Calcinha%'
                OR title LIKE '%Push Up%'
                OR title LIKE '%Push-Up%'
                OR title LIKE '%Pijama%'
                OR title LIKE '%Baby Doll%'
                OR title LIKE '%Renda Natalina%'
                OR title LIKE '%Meia-Calça%'
                OR title LIKE '%Meia Calça%'
                OR title LIKE '%Meia Calca%'
                OR title LIKE '%Arrastão%'
                OR title LIKE '%Arrastao%'
                OR title LIKE '%Cueca%'
                OR title LIKE '%Boxer%'
            )";
        }

        // 2. Novidades & Uso Especial / Adulto (+18) (ID 200000532)
        if (str_contains($clean, 'sex shop') || str_contains($clean, 'sexshop') || str_contains($clean, 'adulto') || str_contains($clean, '18') || str_contains($clean, 'vibrat') || str_contains($clean, 'massageador') || str_contains($clean, 'novidade') || str_contains($clean, 'especial')) {
            return "(
                category IN ('Novidades & Uso Especial', 'Novidades e  uso especial', 'Sex Shop & Produtos Adultos (+18)', 'Sex Shop')
                OR category LIKE '%Novidades%'
                OR category LIKE '%Especial%'
                OR category LIKE '%Sex Shop%'
                OR category LIKE '%Adulto%'
                OR category LIKE '%+18%'
                OR title LIKE '%Vibrat%'
                OR title LIKE '%Massageador%'
                OR title LIKE '%Amendoim%'
                OR title LIKE '%Relaxamento Muscular%'
                OR title LIKE '%Rolo De Massagem%'
                OR title LIKE '%Estimulador%'
                OR title LIKE '%Sensual%'
                OR title LIKE '%Erótica%'
                OR title LIKE '%Erotica%'
            )";
        }

        // 3. Roupas, Bolsas & Calçados (IDs 200000345, 200000343, 1524, 322)
        if (str_contains($clean, 'roupa') || str_contains($clean, 'vestuario') || str_contains($clean, 'vestuário') || str_contains($clean, 'moda') || str_contains($clean, 'casaco') || str_contains($clean, 'jaqueta') || str_contains($clean, 'mochila') || str_contains($clean, 'bolsa') || str_contains($clean, 'carteira') || str_contains($clean, 'tenis') || str_contains($clean, 'tênis') || str_contains($clean, 'sapato') || str_contains($clean, 'calcado') || str_contains($clean, 'calçado')) {
            return "(
                (
                    category IN ('Roupas, Bolsas & Calçados', 'Moda, Roupas & Vestuário', 'Roupas femininas', 'Roupas masculinas', 'Bolsas e malas', 'Sapatos', 'Roupas', 'Vestuário', 'Moda')
                    OR (category LIKE '%Moda%' AND category NOT LIKE '%Íntima%' AND category NOT LIKE '%Intima%')
                    OR (category LIKE '%Roupa%' AND category NOT LIKE '%Íntima%' AND category NOT LIKE '%Intima%')
                    OR category LIKE '%Bolsa%'
                    OR category LIKE '%Sapato%'
                    OR category LIKE '%Calçado%'
                    OR title LIKE '%Casaco%'
                    OR title LIKE '%Jaqueta%'
                    OR title LIKE '%Corta-Vento%'
                    OR title LIKE '%Ciclismo%'
                    OR title LIKE '%Moletom%'
                    OR title LIKE '%Camisa%'
                    OR title LIKE '%Calça%'
                    OR title LIKE '%Mochila%'
                    OR title LIKE '%Bolsa%'
                    OR title LIKE '%Carteira%'
                    OR title LIKE '%Tênis%'
                    OR title LIKE '%Tenis%'
                    OR title LIKE '%Sapato%'
                    OR title LIKE '%Meia%'
                    OR title LIKE '%Chaveiro%'
                )
                AND title NOT LIKE '%Lingerie%'
                AND title NOT LIKE '%Sutiã%'
                AND title NOT LIKE '%Calcinha%'
                AND title NOT LIKE '%Meia-Calça%'
                AND title NOT LIKE '%Meia Calça%'
                AND title NOT LIKE '%Cueca%'
            )";
        }

        // 4. Pet Shop (ID 100006664)
        if (str_contains($clean, 'pet') || str_contains($clean, 'cachorro') || str_contains($clean, 'gato') || str_contains($clean, 'cao') || str_contains($clean, 'cão')) {
            return "(
                category IN ('Pet Shop', 'Pet Shop & Pets Virais')
                OR category LIKE '%Pet%'
                OR title LIKE '%Trela%'
                OR title LIKE '%Coleira%'
                OR title LIKE '%Cachorro%'
                OR title LIKE '% Cães%'
                OR title LIKE '% Cão %'
                OR title LIKE '% Cão,%'
                OR title LIKE '% Cão'
                OR title LIKE 'Cão %'
                OR title LIKE '%Gato%'
            )";
        }

        // 5. Peças & Acessórios Automotivos (ID 34)
        if (str_contains($clean, 'veiculo') || str_contains($clean, 'veículo') || str_contains($clean, 'carro') || str_contains($clean, 'automotivo') || str_contains($clean, 'auto') || str_contains($clean, 'moto') || str_contains($clean, 'peça')) {
            return "(
                category IN ('Peças & Acessórios Automotivos', 'Peças e acessórios automotivos', 'Acessórios para Veículos', 'Acessórios Auto')
                OR category LIKE '%Automotiv%'
                OR category LIKE '%Veículo%'
                OR category LIKE '%Veiculo%'
                OR category LIKE '%Auto%'
                OR title LIKE '%Veicular%'
                OR title LIKE '%Carro%'
                OR title LIKE '%Ventilação De Ar%'
                OR title LIKE '%Suporte Automotivo%'
                OR title LIKE '%Telemóvel Com Ventosa%'
            )";
        }

        // 6. Ferramentas & Utilidades (ID 1420, 30)
        if (str_contains($clean, 'alicate') || str_contains($clean, 'ferramenta') || str_contains($clean, 'crimpador') || str_contains($clean, 'decapador') || str_contains($clean, 'chave') || str_contains($clean, 'utilidade') || str_contains($clean, 'segurança')) {
            return "(
                category IN ('Ferramentas & Utilidades', 'Ferramentas', 'Segurança e proteção', 'Acessórios Virais & Ferramentas', 'Acessórios Virais')
                OR category LIKE '%Ferramenta%'
                OR category LIKE '%Alicate%'
                OR title LIKE '%Alicate%'
                OR title LIKE '%Crimpador%'
                OR title LIKE '%Decapador%'
                OR title LIKE '%Chave De Fenda%'
                OR title LIKE '%Chaves De Precisão%'
                OR title LIKE '%Catraca%'
            )";
        }

        // 7. Relógios & Smartwatches (ID 1511)
        if (str_contains($clean, 'smartwatch') || str_contains($clean, 'relogio') || str_contains($clean, 'relógio') || str_contains($clean, 'pulseira') || str_contains($clean, 'watch')) {
            return "(
                category IN ('Relógios & Smartwatches', 'Relógios', 'Smartwatches & Áudio', 'Smartwatches')
                OR category LIKE '%Relógio%'
                OR category LIKE '%Relogio%'
                OR category LIKE '%Smartwatch%'
                OR title LIKE '%Smartwatch%'
                OR title LIKE '%Relógio%'
                OR title LIKE '%Relogio%'
                OR title LIKE '%Apple Watch%'
                OR title LIKE '%Pulseira%'
            )";
        }

        // 8. Beleza, Saúde & Maquiagem (ID 66, 660103)
        if (str_contains($clean, 'beleza') || str_contains($clean, 'cabelo') || str_contains($clean, 'secador') || str_contains($clean, 'escova') || str_contains($clean, 'saude') || str_contains($clean, 'saúde') || str_contains($clean, 'cuidado') || str_contains($clean, 'maquiagem') || str_contains($clean, 'batom')) {
            return "(
                category IN ('Beleza, Saúde & Maquiagem', 'Beleza e saúde', 'Maquiagem', 'Beleza, Cuidados & Estilo', 'Saúde & Cuidados')
                OR category LIKE '%Beleza%'
                OR category LIKE '%Saúde%'
                OR category LIKE '%Saude%'
                OR category LIKE '%Maquiagem%'
                OR title LIKE '%Secador%'
                OR title LIKE '%Escova%'
                OR title LIKE '%Cabelo%'
                OR title LIKE '%Modelador%'
                OR title LIKE '%Barbeador%'
                OR title LIKE '%Maquiagem%'
                OR title LIKE '%Batom%'
                OR title LIKE '%Gloss%'
                OR title LIKE '%Rímel%'
                OR title LIKE '%Rimel%'
                OR title LIKE '%Paleta%'
                OR title LIKE '%Pincel%'
                OR title LIKE '%Pincéis%'
            )";
        }

        // 9. Casa, Iluminação & Eletro (ID 15, 39, 6, 13)
        if (str_contains($clean, 'casa') || str_contains($clean, 'led') || str_contains($clean, 'luminaria') || str_contains($clean, 'luminária') || str_contains($clean, 'lampada') || str_contains($clean, 'lâmpada') || str_contains($clean, 'iluminação') || str_contains($clean, 'eletro')) {
            return "(
                (
                    category IN ('Casa, Iluminação & Eletro', 'Casa e jardim', 'Luz e iluminação', 'Eletrodomésticos e Portáteis', 'Melhorias domésticas', 'Casa Inteligente & Organização', 'Casa Inteligente')
                    OR category LIKE '%Casa%'
                    OR category LIKE '%Iluminação%'
                    OR category LIKE '%Iluminacao%'
                    OR category LIKE '%Luz%'
                    OR category LIKE '%Eletro%'
                    OR title LIKE '%Led%'
                    OR title LIKE '%Luminária%'
                    OR title LIKE '%Luminaria%'
                    OR title LIKE '%Lâmpada%'
                    OR title LIKE '%Lampada%'
                    OR title LIKE '%Tuya%'
                    OR title LIKE '%Monitor%'
                )
                AND title NOT LIKE '%Trela%'
                AND title NOT LIKE '%Coleira%'
            )";
        }

        // 10. Eletrônicos & Telefonia (ID 44, 509, 7)
        if (str_contains($clean, 'eletronico') || str_contains($clean, 'eletrônico') || str_contains($clean, 'telefone') || str_contains($clean, 'telefonia') || str_contains($clean, 'computador') || str_contains($clean, 'tecnologia') || str_contains($clean, 'gadget') || str_contains($clean, 'audio') || str_contains($clean, 'áudio') || str_contains($clean, 'fone')) {
            return "(
                category IN ('Eletrônicos & Telefonia', 'Eletrônicos', 'Telefones e telecomunicações', 'Computador e escritório', 'Tecnologia & Gadgets', 'Eletrônicos & Gadget', 'Áudio', 'Audio', 'Fones Bluetooth TWS')
                OR category LIKE '%Eletrônic%'
                OR category LIKE '%Eletronic%'
                OR category LIKE '%Telefone%'
                OR category LIKE '%Computador%'
                OR category LIKE '%Tecnologia%'
                OR category LIKE '%Áudio%'
                OR category LIKE '%Audio%'
                OR title LIKE '%Projetor%'
                OR title LIKE '%Console%'
                OR title LIKE '%Power Bank%'
                OR title LIKE '%Carregador%'
                OR title LIKE '%Fone%'
                OR title LIKE '%Tws%'
                OR title LIKE '%Headphone%'
                OR title LIKE '%Earphone%'
                OR title LIKE '%Microfone%'
            )";
        }

        // Fallback genérico seguro
        $params[] = $raw;
        $params[] = '%' . $raw . '%';
        return "(category = ? OR category LIKE ?)";
    }
}
