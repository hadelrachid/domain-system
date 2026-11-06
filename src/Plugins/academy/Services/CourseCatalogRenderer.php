<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Core\Contracts\PublicStorageInterface;
use DomainSystem\Plugins\academy\Contracts\CourseCatalogRendererInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;

class CourseCatalogRenderer implements CourseCatalogRendererInterface
{
    private CourseRepositoryInterface $repository;
    private ?PublicStorageInterface $storage;

    public function __construct(CourseRepositoryInterface $repository, ?PublicStorageInterface $storage = null)
    {
        $this->repository = $repository;
        $this->storage = $storage;
    }

    /**
     * @param array<string, mixed> $attrs Atributos do shortcode [hub_de_cursos limit="6" status="published"]
     */
    public function render(array $attrs = []): string
    {
        $limit = isset($attrs['limit']) ? (int)$attrs['limit'] : 12;
        if ($limit <= 0) {
            $limit = 12;
        }

        $status = $attrs['status'] ?? null;
        $filters = ['limit' => $limit];
        if (!empty($status) && in_array($status, ['published', 'soon', 'draft'], true)) {
            $filters['status'] = $status;
        }

        $courses = $this->repository->findPublished($filters);

        if (empty($courses)) {
            return "<div class='ds-academy-empty' style='text-align:center; padding:40px 20px; background:rgba(255,255,255,0.03); border-radius:12px; border:1px dashed rgba(69,243,255,0.2); color:var(--text-muted, #8c8f94);'>
                <p style='margin:0; font-size:1.05rem;'>Nenhum curso disponível no momento.</p>
            </div>";
        }

        $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

        $html = "<div class='academy-grid' style='display:grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; margin: 30px 0;'>";

        foreach ($courses as $course) {
            $title = htmlspecialchars((string)($course['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars((string)($course['description'] ?? ''), ENT_QUOTES, 'UTF-8');
            
            // Resolução agnóstica de Link (Evita 404 em subpastas e suporta checkouts externos)
            $rawLink = trim((string)($course['link'] ?? ''));
            if ($rawLink !== '') {
                if (str_starts_with($rawLink, 'http://') || str_starts_with($rawLink, 'https://')) {
                    $finalLink = $rawLink;
                } else {
                    if ($baseUrl !== '' && !str_starts_with($rawLink, $baseUrl)) {
                        $finalLink = $baseUrl . '/' . ltrim($rawLink, '/');
                    } else {
                        $finalLink = '/' . ltrim($rawLink, '/');
                    }
                }
            } else {
                $finalLink = $baseUrl . '/cursos/' . urlencode((string)($course['slug'] ?? ''));
            }
            $link = htmlspecialchars($finalLink, ENT_QUOTES, 'UTF-8');

            // Precificação e Parcelamento ("Quantas vezes")
            $price = (float)($course['price'] ?? 0.0);
            $isFree = ($price > 0.0) ? false : (isset($course['is_free']) ? (bool)$course['is_free'] : true);
            $maxInstallments = isset($course['max_installments']) && (int)$course['max_installments'] > 0
                ? (int)$course['max_installments']
                : 12;

            $pricingHtml = '';
            if ($isFree || $price <= 0.0) {
                $pricingHtml = "<div class='course-pricing' style='margin-bottom:14px;'>
                    <span style='background:rgba(40,167,69,0.15); color:#2ecc71; font-weight:700; font-size:0.82rem; padding:3px 9px; border-radius:4px; border:1px solid rgba(46,204,113,0.3);'>GRATUITO</span>
                </div>";
                $btnText = "Acessar Conteúdo &rarr;";
                $btnBg = "var(--primary, #45f3ff)";
            } else {
                $formattedTotal = 'R$ ' . number_format($price, 2, ',', '.');
                if ($maxInstallments > 1) {
                    $installmentValue = $price / $maxInstallments;
                    $formattedInstallment = 'R$ ' . number_format($installmentValue, 2, ',', '.');
                    $pricingHtml = "<div class='course-pricing' style='margin-bottom:14px;'>
                        <div style='font-size:1.15rem; font-weight:700; color:#00d284;'>{$formattedTotal}</div>
                        <div style='font-size:0.85rem; color:var(--text-muted, #8c8f94); margin-top:2px;'>ou em até <strong style='color:#45f3ff;'>{$maxInstallments}x de {$formattedInstallment}</strong></div>
                    </div>";
                } else {
                    $pricingHtml = "<div class='course-pricing' style='margin-bottom:14px;'>
                        <div style='font-size:1.15rem; font-weight:700; color:#00d284;'>{$formattedTotal} à vista</div>
                    </div>";
                }
                $btnText = "Comprar";
                $btnBg = "linear-gradient(135deg, #00d284, #00b370)";
            }

            if (!empty($course['thumbnail'])) {
                $rawThumb = (string)$course['thumbnail'];
                if ($this->storage !== null) {
                    $thumbUrl = htmlspecialchars($this->storage->getPublicUrl($rawThumb), ENT_QUOTES, 'UTF-8');
                } elseif (str_starts_with($rawThumb, 'http://') || str_starts_with($rawThumb, 'https://') || str_starts_with($rawThumb, 'data:')) {
                    $thumbUrl = htmlspecialchars($rawThumb, ENT_QUOTES, 'UTF-8');
                } else {
                    $thumbUrl = htmlspecialchars($baseUrl . '/' . ltrim($rawThumb, '/'), ENT_QUOTES, 'UTF-8');
                }
            } else {
                $thumbUrl = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="640" height="360" viewBox="0 0 640 360"><rect width="100%" height="100%" fill="%231e2327"/><text x="50%" y="50%" fill="%2345f3ff" font-family="sans-serif" font-size="28" font-weight="bold" text-anchor="middle" dominant-baseline="middle">🎓 Academy</text></svg>';
            }

            $badge = '';
            if (($course['status'] ?? '') === 'soon') {
                $badge = "<span style='position:absolute; top:12px; right:12px; background:#f0ad4e; color:#0b0c10; font-size:11px; font-weight:bold; padding:4px 10px; border-radius:20px; box-shadow:0 2px 8px rgba(0,0,0,0.4); text-transform:uppercase;'>EM BREVE</span>";
            }

            $html .= "
            <div class=\"course-card glass-panel\" style=\"background:var(--bg-panel, #1d2327); border:1px solid rgba(69, 243, 255, 0.15); border-radius:12px; overflow:hidden; display:flex; flex-direction:column; position:relative; transition: transform 0.25s ease, box-shadow 0.25s ease;\">
                <div style=\"position:relative; width:100%; aspect-ratio:16/9; background:#0b0c10; overflow:hidden;\">
                    <img src=\"{$thumbUrl}\" alt=\"{$title}\" style=\"width:100%; height:100%; object-fit:cover; display:block;\" loading=\"lazy\">
                    {$badge}
                </div>
                <div style=\"padding:20px; display:flex; flex-direction:column; flex-grow:1;\">
                    <h3 style=\"margin:0 0 10px 0; font-size:1.2rem; color:var(--text-main, #ffffff); line-height:1.4;\">{$title}</h3>
                    <p style=\"margin:0 0 18px 0; color:var(--text-muted, #8c8f94); font-size:0.95rem; line-height:1.5; flex-grow:1;\">{$desc}</p>
                    {$pricingHtml}
                    <a href=\"{$link}\" style=\"display:inline-block; text-align:center; padding:10px 18px; border-radius:6px; background:{$btnBg}; color:#0b0c10; font-weight:700; text-decoration:none; transition:opacity 0.2s;\" onmouseover=\"this.style.opacity='0.85'\" onmouseout=\"this.style.opacity='1'\">
                        {$btnText}
                    </a>
                </div>
            </div>";
        }

        $html .= "</div>";

        $html .= "
        <style>
            .course-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 24px rgba(0,0,0,0.5), 0 0 12px rgba(69, 243, 255, 0.2);
            }
        </style>";

        return $html;
    }
}
