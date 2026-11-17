<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\CategoryTaxonomyInterface;

/**
 * Serviço de Taxonomia Canônica, Classificação Semântica e Filtros SQL (SOLID / SRP).
 * 
 * Centraliza de forma desacoplada toda a inteligência de categorização de produtos,
 * normalização de sinônimos e consultas SQL tolerantes a variações lexicais.
 */
class CategoryTaxonomyService implements CategoryTaxonomyInterface
{
    public const CAT_TECH         = 'Eletrônicos & Gadget';
    public const CAT_SMARTWATCH   = 'Smartwatches & Áudio';
    public const CAT_HOME         = 'Casa Inteligente & Organização';
    public const CAT_TOOLS        = 'Ferramentas & Utilidades';
    public const CAT_BEAUTY       = 'Beleza, Cuidados & Estilo';
    public const CAT_PETS         = 'Pet Shop & Pets Virais';
    public const CAT_AUTO         = 'Acessórios para Veículos';
    public const CAT_FASHION      = 'Moda, Roupas & Vestuário';
    public const CAT_LINGERIE     = 'Moda Íntima, Lingeries & Pijamas';
    public const CAT_ADULT        = 'Sex Shop & Produtos Adultos (+18)';

    public function getCanonicalCategories(): array
    {
        return [
            self::CAT_TECH,
            self::CAT_HOME,
            self::CAT_BEAUTY,
            self::CAT_TOOLS,
            self::CAT_PETS,
            self::CAT_AUTO,
            self::CAT_FASHION,
            self::CAT_LINGERIE,
            self::CAT_ADULT,
        ];
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

        if (str_contains($clean, 'lingerie') || str_contains($clean, 'sutia') || str_contains($clean, 'sutiã') || str_contains($clean, 'calcinha') || str_contains($clean, 'intima') || str_contains($clean, 'íntima') || str_contains($clean, 'pijama')) {
            return self::CAT_LINGERIE;
        }

        if (str_contains($clean, 'sex shop') || str_contains($clean, 'sexshop') || str_contains($clean, 'adulto') || str_contains($clean, '18') || str_contains($clean, 'vibrat') || str_contains($clean, 'massageador')) {
            return self::CAT_ADULT;
        }

        if (str_contains($clean, 'roupa') || str_contains($clean, 'vestuario') || str_contains($clean, 'vestuário') || str_contains($clean, 'casaco') || str_contains($clean, 'jaqueta') || (str_contains($clean, 'moda') && !str_contains($clean, 'intima') && !str_contains($clean, 'íntima'))) {
            return self::CAT_FASHION;
        }

        if (str_contains($clean, 'pet') || str_contains($clean, 'cachorro') || str_contains($clean, 'gato') || str_contains($clean, 'cao') || str_contains($clean, 'cão')) {
            return self::CAT_PETS;
        }

        if (str_contains($clean, 'veiculo') || str_contains($clean, 'veículo') || str_contains($clean, 'carro') || str_contains($clean, 'automotivo') || str_contains($clean, 'auto')) {
            return self::CAT_AUTO;
        }

        if (str_contains($clean, 'alicate') || str_contains($clean, 'ferramenta') || str_contains($clean, 'crimpador') || str_contains($clean, 'decapador') || str_contains($clean, 'chave de fenda') || str_contains($clean, 'utilidades')) {
            return self::CAT_TOOLS;
        }

        if (str_contains($clean, 'smartwatch') || str_contains($clean, 'relogio') || str_contains($clean, 'relógio') || str_contains($clean, 'audio') || str_contains($clean, 'áudio') || str_contains($clean, 'fone') || str_contains($clean, 'headphone') || str_contains($clean, 'earphone')) {
            return self::CAT_SMARTWATCH;
        }

        if (str_contains($clean, 'beleza') || str_contains($clean, 'cabelo') || str_contains($clean, 'pele') || str_contains($clean, 'secador') || str_contains($clean, 'escova') || str_contains($clean, 'saude') || str_contains($clean, 'saúde')) {
            return self::CAT_BEAUTY;
        }

        if (str_contains($clean, 'casa') || str_contains($clean, 'led') || str_contains($clean, 'luminaria') || str_contains($clean, 'luminária') || str_contains($clean, 'lampada') || str_contains($clean, 'lâmpada') || str_contains($clean, 'tuya')) {
            return self::CAT_HOME;
        }

        return self::CAT_TECH;
    }

    public function classifyFromText(string $title, ?string $rawCategory = null): string
    {
        $t = mb_strtolower(trim($title), 'UTF-8');

        // 1. Moda Íntima e Lingeries (Precedência alta sobre Moda geral para evitar falso-positivo)
        if (preg_match('/\b(lingerie|sutiã|sutia|calcinha|calcinhas|bojo|push up|push-up|guipir|bralette|camisola|baby doll|pijama|pijamas|renda sexy|natalina sexy)\b/iu', $t)) {
            return self::CAT_LINGERIE;
        }

        // 2. Sex Shop & Adulto (+18)
        if (preg_match('/\b(sex shop|sexshop|adulto|\+18|vibratório|vibratorio|vibratória|vibratoria|vibração|vibracao|massageador|amendoim|estimulador|plug|lubrificante)\b/iu', $t)) {
            return self::CAT_ADULT;
        }

        // 3. Moda, Roupas & Vestuário (Geral)
        if (preg_match('/\b(casaco|casacos|jaqueta|jaquetas|corta-vento|corta vento|ciclismo|corrida secagem|impermeável masculino|capuz capa|moletom|calça|calca|camisa|camiseta|vestido|saia|bermuda|roupa|roupas|vestuário|vestuario)\b/iu', $t)) {
            return self::CAT_FASHION;
        }

        // 4. Pet Shop & Pets Virais
        if (preg_match('/\b(pet|pets|cachorro|cachorros|cão|cães|gato|gatos|coleira|trela|fita led noite segurança|fibra ótica recarregável cães)\b/iu', $t)) {
            return self::CAT_PETS;
        }

        // 5. Acessórios para Veículos
        if (preg_match('/\b(veicular|veículo|veiculo|carro|automotivo|ventilação de ar|suporte celular carro|suporte automotivo|telemóvel com ventosa|suporte magnético 360)\b/iu', $t)) {
            return self::CAT_AUTO;
        }

        // 6. Ferramentas & Utilidades
        if (preg_match('/\b(alicate|alicates|crimpador|decapador|desencapar fios|chaves de fenda|chave de fenda|catraca|ferramentas|ferramenta|chaves de precisão|bits)\b/iu', $t)) {
            return self::CAT_TOOLS;
        }

        // 7. Smartwatches & Áudio
        if (preg_match('/\b(smartwatch|smartwatches|relógio inteligente|relogio inteligente|apple watch|amazfit|aurafit|pulseira homem de ferro|fone|fones|tws|headphone|earphone|microfone lapela|bluetooth anc)\b/iu', $t)) {
            return self::CAT_SMARTWATCH;
        }

        // 8. Beleza, Cuidados & Estilo
        if (preg_match('/\b(secador|escova secadora|escova alisadora|alisador|modelador de cabelo|cabelo|curling|barbeador|barba|depilador|maquiagem|facial|pele)\b/iu', $t)) {
            return self::CAT_BEAUTY;
        }

        // 9. Casa Inteligente & Organização
        if (preg_match('/\b(fita de led|fita led|rgbic|tuya|luminária|luminaria|lâmpada|lampada|barra de luz|monitor luzes|iluminação|teto|armário|quarto|cozinha|aspirador)\b/iu', $t)) {
            return self::CAT_HOME;
        }

        // 10. Se houver categoria bruta informada, tenta normalizar
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

        // 1. Moda Íntima, Lingeries & Pijamas
        if (str_contains($clean, 'lingerie') || str_contains($clean, 'intima') || str_contains($clean, 'íntima') || str_contains($clean, 'pijama') || str_contains($clean, 'sutia') || str_contains($clean, 'sutiã')) {
            return "(
                category IN ('Moda Íntima, Lingeries & Pijamas', 'Moda Íntima', 'Lingeries', 'Lingerie')
                OR category LIKE '%Lingerie%'
                OR category LIKE '%Íntima%'
                OR category LIKE '%Intima%'
                OR title LIKE '%Lingerie%'
                OR title LIKE '%Sutiã%'
                OR title LIKE '%Sutia%'
                OR title LIKE '%Calcinha%'
                OR title LIKE '%Push Up%'
                OR title LIKE '%Push-Up%'
                OR title LIKE '%Pijama%'
                OR title LIKE '%Baby Doll%'
                OR title LIKE '%Renda Natalina%'
            )";
        }

        // 2. Sex Shop & Produtos Adultos (+18)
        // No universo do e-commerce adulto, o filtro contempla tanto os dispositivos/massageadores íntimos
        // quanto lingeries e peças sensuais/eróticas.
        if (str_contains($clean, 'sex shop') || str_contains($clean, 'sexshop') || str_contains($clean, 'adulto') || str_contains($clean, '18') || str_contains($clean, 'vibrat') || str_contains($clean, 'massageador')) {
            return "(
                category IN ('Sex Shop & Produtos Adultos (+18)', 'Sex Shop', 'Moda Íntima, Lingeries & Pijamas')
                OR category LIKE '%Sex Shop%'
                OR category LIKE '%Adulto%'
                OR category LIKE '%+18%'
                OR category LIKE '%Lingerie%'
                OR title LIKE '%Vibrat%'
                OR title LIKE '%Massageador%'
                OR title LIKE '%Amendoim%'
                OR title LIKE '%Relaxamento Muscular%'
                OR title LIKE '%Rolo De Massagem%'
                OR title LIKE '%Estimulador%'
                OR title LIKE '%Lingerie%'
                OR title LIKE '%Sensual%'
                OR title LIKE '%Erótica%'
                OR title LIKE '%Erotica%'
                OR title LIKE '%Renda Natalina%'
                OR title LIKE '%Baby Doll%'
                OR title LIKE '%Calcinha%'
                OR title LIKE '%Sutiã%'
                OR title LIKE '%Sutia%'
            )";
        }

        // 3. Moda, Roupas & Vestuário (exclui Lingerie para não canibalizar)
        if (str_contains($clean, 'roupa') || str_contains($clean, 'vestuario') || str_contains($clean, 'vestuário') || str_contains($clean, 'moda') || str_contains($clean, 'casaco') || str_contains($clean, 'jaqueta')) {
            return "(
                (
                    category IN ('Moda, Roupas & Vestuário', 'Roupas', 'Vestuário', 'Moda')
                    OR (category LIKE '%Moda%' AND category NOT LIKE '%Íntima%' AND category NOT LIKE '%Intima%')
                    OR category LIKE '%Roupa%'
                    OR category LIKE '%Vestuário%'
                    OR category LIKE '%Vestuario%'
                    OR title LIKE '%Casaco%'
                    OR title LIKE '%Jaqueta%'
                    OR title LIKE '%Corta-Vento%'
                    OR title LIKE '%Ciclismo%'
                    OR title LIKE '%Moletom%'
                    OR title LIKE '%Camisa%'
                    OR title LIKE '%Calça%'
                )
                AND title NOT LIKE '%Lingerie%'
                AND title NOT LIKE '%Sutiã%'
                AND title NOT LIKE '%Calcinha%'
            )";
        }

        // 4. Pet Shop & Pets Virais
        if (str_contains($clean, 'pet') || str_contains($clean, 'cachorro') || str_contains($clean, 'gato') || str_contains($clean, 'cao') || str_contains($clean, 'cão')) {
            return "(
                category IN ('Pet Shop & Pets Virais', 'Pet Shop')
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

        // 5. Acessórios para Veículos
        if (str_contains($clean, 'veiculo') || str_contains($clean, 'veículo') || str_contains($clean, 'carro') || str_contains($clean, 'automotivo') || str_contains($clean, 'auto')) {
            return "(
                category IN ('Acessórios para Veículos', 'Acessórios Auto')
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

        // 6. Ferramentas & Utilidades
        if (str_contains($clean, 'alicate') || str_contains($clean, 'ferramenta') || str_contains($clean, 'crimpador') || str_contains($clean, 'decapador') || str_contains($clean, 'chave') || str_contains($clean, 'utilidade')) {
            return "(
                category IN ('Ferramentas & Utilidades', 'Ferramentas', 'Acessórios Virais & Ferramentas', 'Acessórios Virais')
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

        // 7a. Smartwatches específicos
        if (in_array($lower, ['smartwatches', 'smartwatch', 'smartwatches & pulseiras', 'pulseiras'], true) || ($clean === 'smartwatches' || $clean === 'smartwatch' || $clean === 'pulseiras')) {
            return "(
                category IN ('Smartwatches', 'Smartwatches & Pulseiras', 'Smartwatches & Áudio')
                OR category LIKE '%Smartwatch%'
                OR title LIKE '%Smartwatch%'
                OR title LIKE '%Relógio%'
                OR title LIKE '%Relogio%'
                OR title LIKE '%Apple Watch%'
                OR title LIKE '%Pulseira%'
            )";
        }

        // 7b. Áudio específico
        if (in_array($lower, ['áudio', 'audio', 'fones', 'fones bluetooth tws', 'fones tws'], true) || ($clean === 'audio' || $clean === 'áudio' || $clean === 'fones' || $clean === 'fones tws')) {
            return "(
                category IN ('Áudio', 'Audio', 'Fones Bluetooth TWS', 'Smartwatches & Áudio')
                OR category LIKE '%Áudio%'
                OR category LIKE '%Audio%'
                OR title LIKE 'Fone%'
                OR title LIKE '% Fone%'
                OR title LIKE '%Tws%'
                OR title LIKE '%Earphone%'
                OR title LIKE '%Headphone%'
                OR title LIKE '%Microfone%'
            )";
        }

        // 7c. Celular específico
        if (in_array($lower, ['celular', 'acessórios para celular', 'acessorios para celular', 'smartphone', 'smartphones'], true) || ($clean === 'celular' || $clean === 'smartphone')) {
            return "(
                category IN ('Acessórios para Celular')
                OR title LIKE '%Celular%'
                OR title LIKE '%Smartphone%'
                OR title LIKE '%Reparo Do Telefone%'
                OR title LIKE '%iPhone%'
                OR title LIKE '%Usb-C%'
                OR title LIKE '%Lightning%'
                OR title LIKE '%Carregador%'
                OR title LIKE '%Capinha%'
                OR title LIKE '%MagSafe%'
            )";
        }

        // 7d. Smartwatches & Áudio Combinados
        if (str_contains($clean, 'smartwatch') || str_contains($clean, 'relogio') || str_contains($clean, 'relógio') || str_contains($clean, 'audio') || str_contains($clean, 'áudio') || str_contains($clean, 'fone')) {
            return "(
                category IN ('Smartwatches & Áudio', 'Smartwatches', 'Áudio', 'Audio')
                OR category LIKE '%Smartwatch%'
                OR category LIKE '%Áudio%'
                OR category LIKE '%Audio%'
                OR title LIKE '%Smartwatch%'
                OR title LIKE '%Relógio%'
                OR title LIKE '%Relogio%'
                OR title LIKE '%Watch%'
                OR title LIKE '%Pulseira%'
                OR title LIKE '%Fone%'
                OR title LIKE '%Tws%'
                OR title LIKE '%Microfone%'
            )";
        }

        // 8. Beleza, Cuidados & Estilo
        if (str_contains($clean, 'beleza') || str_contains($clean, 'cabelo') || str_contains($clean, 'secador') || str_contains($clean, 'escova') || str_contains($clean, 'saude') || str_contains($clean, 'saúde') || str_contains($clean, 'cuidado')) {
            return "(
                category IN ('Beleza, Cuidados & Estilo', 'Beleza & Estilo', 'Saúde & Cuidados')
                OR category LIKE '%Beleza%'
                OR category LIKE '%Saúde%'
                OR category LIKE '%Saude%'
                OR category LIKE '%Cuidado%'
                OR title LIKE '%Secador%'
                OR title LIKE '%Escova%'
                OR title LIKE '%Cabelo%'
                OR title LIKE '%Modelador%'
                OR title LIKE '%Barbeador%'
            )";
        }

        // 9. Casa Inteligente & Organização
        if (str_contains($clean, 'casa') || str_contains($clean, 'led') || str_contains($clean, 'luminaria') || str_contains($clean, 'luminária') || str_contains($clean, 'lampada') || str_contains($clean, 'lâmpada')) {
            return "(
                (
                    category IN ('Casa Inteligente & Organização', 'Casa Inteligente')
                    OR category LIKE '%Casa%'
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

        // 10. Tecnologia & Gadgets
        if (str_contains($clean, 'tecnologia') || str_contains($clean, 'eletronico') || str_contains($clean, 'eletrônico') || str_contains($clean, 'gadget')) {
            return "(
                category IN ('Tecnologia & Gadgets', 'Eletrônicos & Gadget', 'Eletrônicos')
                OR category LIKE '%Tecnologia%'
                OR category LIKE '%Eletrônicos%'
                OR category LIKE '%Eletronicos%'
                OR category LIKE '%Gadget%'
                OR title LIKE '%Projetor%'
                OR title LIKE '%Console%'
                OR title LIKE '%Power Bank%'
                OR title LIKE '%Carregador%'
            )";
        }

        // Fallback genérico seguro com prepared statement
        $params[] = $raw;
        $params[] = '%' . $raw . '%';
        return "(category = ? OR category LIKE ?)";
    }
}
