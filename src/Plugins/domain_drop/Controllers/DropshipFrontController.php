<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Controllers;

use DomainSystem\Core\Contracts\ResponseInterface;
use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\domain_drop\Contracts\DropshipCatalogRepositoryInterface;
use Throwable;

/**
 * Controlador Público da Loja Virtual de Dropshipping (SRP / DIP).
 */
class DropshipFrontController
{
    public function __construct(
        private readonly DropshipCatalogRepositoryInterface $catalogRepo,
        private readonly ?ThemeManagerInterface $themeManager = null
    ) {
    }

    /**
     * Vitrine Completa da Loja: GET /loja
     */
    public function storefront(Request $request): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
        $selectedCategory = trim((string)$request->input('cat', ''));
        $search = trim((string)$request->input('q', ''));

        $criteria = ['status' => 'published'];
        if ($selectedCategory !== '') {
            $criteria['category'] = $selectedCategory;
        }
        if ($search !== '') {
            $criteria['search'] = $search;
        }

        $products = $this->catalogRepo->list($criteria, 48, 0);
        $totalCount = $this->catalogRepo->count($criteria);
        $categories = $this->catalogRepo->getCategories();

        ob_start();
        include dirname(__DIR__) . '/views/front/storefront.php';
        $content = ob_get_clean();

        // Tenta renderizar envelopado pelo Layout do Tema ativo, com fallback gracioso
        if ($this->themeManager !== null) {
            try {
                $wrapped = $this->themeManager->render('page', [
                    'title'   => 'Loja Oficial - Produtos Selecionados',
                    'content' => $content,
                ]);
                return new Response($wrapped);
            } catch (Throwable $e) {
                // Renderização direta caso o tema não possua layout 'page'
            }
        }

        return new Response($content ?: '');
    }

    /**
     * Página Dedicada do Produto: GET /produto/{slug}
     */
    public function singleProduct(Request $request, string $slug): ResponseInterface
    {
        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        // Tenta buscar por ID numérico ou pelo slug amigável do produto
        $product = null;
        if (is_numeric($slug)) {
            $product = $this->catalogRepo->findById((int)$slug);
        } else {
            $product = $this->catalogRepo->findBySlug($slug);
        }

        if (!$product) {
            return new Response("<h1>404 - Produto não encontrado na loja</h1>", 404);
        }

        $checkoutUrl = $baseUrl . '/checkout/produto/' . (int)$product->id;

        ob_start();
        include dirname(__DIR__) . '/views/front/product.php';
        $content = ob_get_clean();

        if ($this->themeManager !== null) {
            try {
                $wrapped = $this->themeManager->render('page', [
                    'title'   => $product->title . ' - Loja Oficial',
                    'content' => $content,
                ]);
                return new Response($wrapped);
            } catch (Throwable $e) {
                // Fallback
            }
        }

        return new Response($content ?: '');
    }
}
