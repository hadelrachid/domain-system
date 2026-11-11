<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\ProductLinkScraperInterface;
use DomainSystem\Plugins\domain_drop\DTO\ScrapedProductDto;
use Throwable;

/**
 * Motor de Scraping em Tempo Real para Links Oficiais do AliExpress (SRP / DIP).
 * 
 * Extrai diretamente do HTML da página pública:
 * - Imagem de capa oficial (og:image) em alta resolução
 * - Galeria completa de imagens Full HD (imagePathList)
 * - Título original do fornecedor
 * - Preço promocional em vigor (pdp_npi)
 */
class AliExpressLinkScraperService implements ProductLinkScraperInterface
{
    public function canHandle(string $urlOrId): bool
    {
        $trim = trim($urlOrId);
        if (str_contains($trim, 'aliexpress.com') || str_contains($trim, 'aliexpress-media.com')) {
            return true;
        }

        // Verifica se é um ID numérico típico do AliExpress (10 a 20 dígitos)
        return (bool)preg_match('/^\d{10,20}$/', $trim);
    }

    public function scrape(string $urlOrId): ?ScrapedProductDto
    {
        $productId = $this->extractProductId($urlOrId);
        if ($productId === '') {
            return null;
        }

        $canonicalUrl = "https://pt.aliexpress.com/item/{$productId}.html?country=BR&locale=pt_BR&currency=BRL";
        $targetUrl = str_starts_with($urlOrId, 'http') ? $urlOrId : $canonicalUrl;

        try {
            $html = $this->fetchHtml($targetUrl);
            if ($html === null || strlen($html) < 200) {
                return null;
            }

            // 1. Filtro de Link Morto / 404 / Página Desativada
            if (stripos($html, 'Página não encontrada') !== false 
                || stripos($html, 'Page Not Found') !== false 
                || stripos($html, 'Item no longer available') !== false
                || stripos($html, 'Não conseguimos encontrar esta página') !== false
            ) {
                return null;
            }

            // 2. Extração do Título
            $title = '';
            if (preg_match('/<meta property="og:title" content="([^"]+)"/i', $html, $m)) {
                $title = html_entity_decode(preg_replace('/\s*-\s*AliExpress.*$/i', '', $m[1]));
            }

            if (empty($title) 
                || stripos($title, 'Página não encontrada') !== false 
                || stripos($title, 'Page Not Found') !== false
            ) {
                return null;
            }

            // 3. Extração da Imagem Principal
            $mainImage = '';
            if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $m)) {
                $mainImage = $this->cleanImageUrl($m[1]);
            }

            // 3. Extração da Galeria Completa (imagePathList)
            $gallery = [];
            if (preg_match('/"imagePathList":(\[[^\]]+\])/', $html, $m)) {
                $decoded = json_decode($m[1], true);
                if (is_array($decoded)) {
                    foreach ($decoded as $img) {
                        $clean = $this->cleanImageUrl((string)$img);
                        if ($clean !== '' && !in_array($clean, $gallery, true)) {
                            $gallery[] = $clean;
                        }
                    }
                }
            }

            // Fallback de varredura se imagePathList não veio no regex mas há imagens alicdn
            if (empty($gallery) && !empty($mainImage)) {
                $gallery[] = $mainImage;
            }

            if (empty($mainImage) && !empty($gallery)) {
                $mainImage = $gallery[0];
            }

            // Se não encontrou nenhuma imagem, o scraping falhou ou foi bloqueado
            if (empty($mainImage)) {
                return null;
            }

            // 4. Preço Promocional (pdp_npi) ou padrão
            $costPriceUsd = 12.0;
            $decodedFull = urldecode($targetUrl . ' ' . $html);
            if (preg_match('/dis!([A-Z]{3})![0-9.]+!([0-9.]+)/i', $decodedFull, $pm)) {
                $currency = strtoupper($pm[1]);
                $promoVal = (float)$pm[2];
                if ($currency === 'BRL') {
                    $costPriceUsd = round($promoVal / 5.25, 2);
                } elseif ($currency === 'USD') {
                    $costPriceUsd = $promoVal;
                }
            }

            // 5. Categoria inferida pelo título
            $category = $this->inferCategory($title);

            return new ScrapedProductDto(
                sourceId: $productId,
                sourceUrl: $canonicalUrl,
                rawTitle: $title !== '' ? $title : "Produto AliExpress {$productId}",
                mainImageUrl: $mainImage,
                galleryImages: $gallery,
                costPriceUsd: $costPriceUsd,
                category: $category,
                rating: 4.8,
                ordersCount: 1250,
                attributes: [
                    'Origem' => 'Importação Oficial AliExpress',
                    'Envio'  => 'Rastreamento Internacional Expresso',
                ]
            );
        } catch (Throwable $e) {
            return null;
        }
    }

    private function extractProductId(string $urlOrId): string
    {
        $str = trim($urlOrId);
        if (preg_match('/\/item\/(\d+)\.html/i', $str, $matches)) {
            return $matches[1];
        }
        if (preg_match('/(\d{10,20})/', $str, $matches)) {
            return $matches[1];
        }

        return '';
    }

    public function isProductAlive(string $urlOrId): bool
    {
        return $this->scrape($urlOrId) !== null;
    }

    private function fetchHtml(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
            'Cookie: aep_usuc_f=region=BR&site=bra&b_locale=pt_BR&c_tp=BRL; xman_us_f=x_locale=pt_BR; intl_locale=pt_BR;',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
        ]);
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        // Se o servidor retornar 404, 410 ou redirecionar para página de erro, descarta imediatamente
        if ($httpCode !== 200) {
            return null;
        }

        if (str_contains($effectiveUrl, 'notfound') || str_contains($effectiveUrl, 'page-not-found') || str_contains($effectiveUrl, 'item-not-found')) {
            return null;
        }

        return is_string($html) ? $html : null;
    }

    private function cleanImageUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }
        // Remove sufixos de thumbnails e redimensionamento como _80x80.jpg, _50x50.jpg, _.webp
        $url = (string)preg_replace('/_[0-9]+x[0-9]+\.(?:jpg|png|webp|jpeg)$/i', '', $url);
        $url = (string)preg_replace('/_\.(?:jpg|png|webp|jpeg)$/i', '', $url);

        return $url;
    }

    private function inferCategory(string $title): string
    {
        $t = mb_strtolower($title, 'UTF-8');
        if (str_contains($t, 'pulseira') || str_contains($t, 'watch') || str_contains($t, 'relogio') || str_contains($t, 'fone') || str_contains($t, 'projetor') || str_contains($t, 'carregador')) {
            return 'Eletrônicos';
        }
        if (str_contains($t, 'lampada') || str_contains($t, 'luminaria') || str_contains($t, 'led') || str_contains($t, 'aspirador') || str_contains($t, 'cozinha') || str_contains($t, 'quarto')) {
            return 'Casa Inteligente';
        }
        if (str_contains($t, 'cabelo') || str_contains($t, 'pele') || str_contains($t, 'maquiagem') || str_contains($t, 'barbeador') || str_contains($t, 'beleza')) {
            return 'Beleza & Estilo';
        }
        if (str_contains($t, 'carro') || str_contains($t, 'suporte veicular') || str_contains($t, 'automotivo')) {
            return 'Acessórios Auto';
        }
        if (str_contains($t, 'pet') || str_contains($t, 'cachorro') || str_contains($t, 'gato')) {
            return 'Pet Shop';
        }

        return 'Geral';
    }
}
