<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use DomainSystem\Plugins\domain_drop\DTO\DropshipProductDto;
use Throwable;

/**
 * Serviço de Proteção, Mascaramento e Cache Local de Mídia (SRP / DIP).
 *
 * Responsável por:
 * 1. Ocultar totalmente o domínio da Alibaba/AliExpress (alicdn.com) gerando URLs locais (/dropship/media/{id}/{hash}.jpg)
 * 2. Armazenar em cache local no servidor para performance e proteção anti-rastreamento
 * 3. Proteger contra inspeção e extração de links diretos de fornecedores
 */
class ProductMediaProxyService
{
    private string $storageDir;

    public function __construct(
        private readonly ?DropshipCatalogRepositoryInterface $catalogRepo = null,
        ?string $storageDir = null
    ) {
        $root = defined('DOMAIN_SYSTEM_ROOT') ? DOMAIN_SYSTEM_ROOT : dirname(__DIR__, 4);
        $this->storageDir = $storageDir ?? ($root . '/public/uploads/dropship');
    }

    /**
     * Gera o hash único determinístico de 16 caracteres a partir da URL da imagem.
     */
    public function generateToken(string $rawUrl): string
    {
        return substr(md5(trim($rawUrl)), 0, 16);
    }

    /**
     * Converte qualquer URL de imagem externa em uma URL mascarada sob o domínio local da loja.
     */
    public function getMaskedUrl(int $productId, string $rawUrl, ?string $baseUrl = null): string
    {
        $rawUrl = trim($rawUrl);
        if ($rawUrl === '') {
            return '';
        }

        // Se já for uma URL local relativa ou SVG inline, retorna inalterada
        if (str_starts_with($rawUrl, '/') || str_starts_with($rawUrl, 'data:')) {
            return $rawUrl;
        }

        $token = $this->generateToken($rawUrl);
        $base = ($baseUrl !== null) ? rtrim($baseUrl, '/') : (defined('BASE_URL') ? rtrim(BASE_URL, '/') : '');

        return ($base !== '' ? $base : '') . "/dropship/media/{$productId}/{$token}.jpg";
    }

    /**
     * Localiza o arquivo em cache no disco ou faz o download seguro da origem sob demanda.
     */
    public function resolveAndServe(int $productId, string $token): ResponseInterface
    {
        $cleanToken = pathinfo(trim($token), PATHINFO_FILENAME);
        if ($cleanToken === '') {
            return $this->servePlaceholder();
        }

        $productDir = $this->storageDir . DIRECTORY_SEPARATOR . $productId;
        $cachedFilePath = $productDir . DIRECTORY_SEPARATOR . $cleanToken . '.jpg';

        // 1. Se já está em cache local no disco, serve imediatamente
        if (is_file($cachedFilePath) && filesize($cachedFilePath) > 100) {
            $bytes = (string)file_get_contents($cachedFilePath);
            return new Response($bytes, 200, [
                'Content-Type'              => 'image/jpeg',
                'Cache-Control'             => 'public, max-age=31536000, immutable',
                'X-Content-Type-Options'    => 'nosniff',
                'X-Protected-By'            => 'RachidD-DomainDrop',
            ]);
        }

        // 2. Se o repositório estiver disponível, busca a URL original correspondente
        $rawSourceUrl = null;
        if ($this->catalogRepo !== null && $productId > 0) {
            try {
                $product = $this->catalogRepo->findById($productId);
                if ($product !== null) {
                    $rawSourceUrl = $this->findUrlByToken($product, $cleanToken);
                }
            } catch (Throwable $e) {
                $rawSourceUrl = null;
            }
        }

        // 3. Se encontrou a URL de origem, realiza o download local seguro
        if ($rawSourceUrl !== null) {
            $downloaded = $this->downloadImage($rawSourceUrl);
            if ($downloaded !== null) {
                if (!is_dir($productDir)) {
                    @mkdir($productDir, 0755, true);
                }
                @file_put_contents($cachedFilePath, $downloaded['bytes']);

                return new Response($downloaded['bytes'], 200, [
                    'Content-Type'              => $downloaded['content_type'] ?: 'image/jpeg',
                    'Cache-Control'             => 'public, max-age=31536000, immutable',
                    'X-Content-Type-Options'    => 'nosniff',
                    'X-Protected-By'            => 'RachidD-DomainDrop',
                ]);
            }
        }

        // 4. Fallback elegante em SVG caso a imagem original esteja inacessível
        return $this->servePlaceholder();
    }

    /**
     * Encontra a URL original de um produto correspondente ao token fornecido.
     */
    public function findUrlByToken(DropshipProductDto $product, string $token): ?string
    {
        // Verifica imagem principal
        if (!empty($product->mainImageUrl) && $this->generateToken($product->mainImageUrl) === $token) {
            return $product->mainImageUrl;
        }

        // Verifica imagens da galeria
        foreach ($product->images as $img) {
            if (!empty($img) && $this->generateToken((string)$img) === $token) {
                return (string)$img;
            }
        }

        // Verifica fotos das variantes
        if ($product->hasVariants()) {
            foreach ($product->getVariants() as $variant) {
                if (!empty($variant->imageUrl) && $this->generateToken($variant->imageUrl) === $token) {
                    return $variant->imageUrl;
                }
            }
        }

        return null;
    }

    /**
     * Pré-carrega todas as imagens de um produto para o cache local do servidor.
     */
    public function cacheProductMedia(DropshipProductDto $product): int
    {
        if ($product->id === null || $product->id <= 0) {
            return 0;
        }

        $allUrls = [];
        if (!empty($product->mainImageUrl)) {
            $allUrls[] = $product->mainImageUrl;
        }
        foreach ($product->images as $img) {
            if (!empty($img) && !in_array($img, $allUrls, true)) {
                $allUrls[] = (string)$img;
            }
        }
        if ($product->hasVariants()) {
            foreach ($product->getVariants() as $v) {
                if (!empty($v->imageUrl) && !in_array($v->imageUrl, $allUrls, true)) {
                    $allUrls[] = $v->imageUrl;
                }
            }
        }

        $cachedCount = 0;
        $productDir = $this->storageDir . DIRECTORY_SEPARATOR . $product->id;
        if (!is_dir($productDir)) {
            @mkdir($productDir, 0755, true);
        }

        foreach ($allUrls as $url) {
            $token = $this->generateToken($url);
            $filePath = $productDir . DIRECTORY_SEPARATOR . $token . '.jpg';

            if (is_file($filePath) && filesize($filePath) > 100) {
                $cachedCount++;
                continue;
            }

            $downloaded = $this->downloadImage($url);
            if ($downloaded !== null) {
                @file_put_contents($filePath, $downloaded['bytes']);
                $cachedCount++;
            }
        }

        return $cachedCount;
    }

    /**
     * Download com timeout curto e cabeçalhos de navegador para evitar bloqueio da CDN.
     *
     * @return array{bytes: string, content_type: string}|null
     */
    private function downloadImage(string $url): ?array
    {
        // Sanitiza URL removendo protocolos relativos
        if (str_starts_with($url, '//')) {
            $url = 'https:' . $url;
        }

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);

            $rawBytes = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = (string)(curl_getinfo($ch, CURLINFO_CONTENT_TYPE) ?: 'image/jpeg');
            curl_close($ch);

            if ($rawBytes !== false && $httpCode === 200 && strlen($rawBytes) > 100) {
                return [
                    'bytes'        => $rawBytes,
                    'content_type' => $contentType,
                ];
            }
        } else {
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 8,
                    'header'  => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ]);
            $rawBytes = @file_get_contents($url, false, $ctx);
            if ($rawBytes !== false && strlen($rawBytes) > 100) {
                return [
                    'bytes'        => $rawBytes,
                    'content_type' => 'image/jpeg',
                ];
            }
        }

        return null;
    }

    /**
     * Renderiza placeholder vetorial de alta definição sem expor links externos.
     */
    private function servePlaceholder(): ResponseInterface
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="600" viewBox="0 0 600 600">'
            . '<defs>'
            . '<linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0%" stop-color="#0e1420"/>'
            . '<stop offset="100%" stop-color="#06090e"/>'
            . '</linearGradient>'
            . '</defs>'
            . '<rect width="100%" height="100%" fill="url(#bg)"/>'
            . '<rect x="2" y="2" width="596" height="596" rx="16" fill="none" stroke="rgba(212,175,55,0.3)" stroke-width="2"/>'
            . '<circle cx="300" cy="260" r="70" fill="rgba(212,175,55,0.12)" stroke="rgba(212,175,55,0.4)" stroke-width="2"/>'
            . '<text x="300" y="275" font-size="50" text-anchor="middle" fill="#F0D060">📦</text>'
            . '<text x="300" y="380" font-family="-apple-system, sans-serif" font-size="20" font-weight="700" fill="#ffffff" text-anchor="middle">RachidD Curadoria Verificada</text>'
            . '<text x="300" y="410" font-family="-apple-system, sans-serif" font-size="13" fill="#94a3b8" text-anchor="middle">✦ Produto Selecionado com Envio Nacional</text>'
            . '</svg>';

        return new Response($svg, 200, [
            'Content-Type'              => 'image/svg+xml; charset=utf-8',
            'Cache-Control'             => 'public, max-age=86400',
            'X-Content-Type-Options'    => 'nosniff',
        ]);
    }
}
