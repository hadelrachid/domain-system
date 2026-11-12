<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\domain_drop\Services;

use DomainSystem\Plugins\domain_drop\Contracts\DropshipDisputeRepositoryInterface;
use DomainSystem\SystemApps\auth\Contracts\EmailSenderInterface;
use Throwable;

/**
 * Serviço de Intermediação de Reclamações com Fornecedor (1 clique no admin).
 * Mantém português para fornecedores brasileiros e traduz para chinês simplificado (zh-CN)
 * quando o fornecedor for da China/Internacional.
 */
class SellerMediationService
{
    /** @var callable|null */
    private $translatorCallback;

    public function __construct(
        private readonly DropshipDisputeRepositoryInterface $disputeRepo,
        private readonly ?EmailSenderInterface $mailer = null,
        ?callable $translatorCallback = null
    ) {
        $this->translatorCallback = $translatorCallback;
    }

    /**
     * Monta, traduz (se chinês) e registra/envia a intermediação ao vendedor em 1 chamada.
     *
     * @param array<string, mixed> $order
     * @param array<string, mixed> $dispute
     * @param string $baseUrl
     * @return array<string, mixed>
     */
    public function sendMediation(array $order, array $dispute, string $baseUrl = ''): array
    {
        $sellerOrigin = strtoupper(trim((string)($dispute['seller_origin'] ?: ($order['seller_origin'] ?? 'CN'))));
        if ($sellerOrigin !== 'BR') {
            $sellerOrigin = 'CN';
        }

        $language = $sellerOrigin === 'BR' ? 'pt-BR' : 'zh-CN';
        $message = $this->composeMessage($order, $dispute, $language, $baseUrl);

        $sellerEmail = trim((string)($dispute['seller_email'] ?: ($order['seller_email'] ?? '')));
        $channel = 'aliexpress_mediation_log';
        $emailDispatched = false;

        if ($sellerEmail !== '' && filter_var($sellerEmail, FILTER_VALIDATE_EMAIL) !== false && $this->mailer !== null) {
            $subject = $language === 'zh-CN'
                ? '[售后订单协助] 订单 ' . (string)($order['order_ref'] ?? '') . ' 客户反馈处理'
                : '[Mediação de Pedido] ' . (string)($order['order_ref'] ?? '') . ' - Reclamação de Cliente';
            try {
                $this->mailer->send($sellerEmail, $subject, $message);
                $channel = 'email';
                $emailDispatched = true;
            } catch (Throwable $e) {
                // Registra como disponível no painel sem interromper a operação do administrador.
                $channel = 'email_fallback_log';
            }
        }

        $disputeId = (int)($dispute['id'] ?? 0);
        if ($disputeId > 0) {
            $this->disputeRepo->markMediationSent($disputeId, $language, $message, $channel);
        }

        return [
            'success'           => true,
            'dispute_id'        => $disputeId,
            'seller_origin'     => $sellerOrigin,
            'seller_language'   => $language,
            'channel'           => $channel,
            'email_dispatched'  => $emailDispatched,
            'translated_message'=> $message,
        ];
    }

    /**
     * Gera a mensagem completa de intermediação no idioma do fornecedor.
     *
     * @param array<string, mixed> $order
     * @param array<string, mixed> $dispute
     */
    public function composeMessage(array $order, array $dispute, string $language, string $baseUrl = ''): string
    {
        $orderRef = (string)($order['order_ref'] ?? '');
        $aeOrderId = (string)($order['aliexpress_order_id'] ?? 'N/A');
        $trackingCode = (string)($order['tracking_code'] ?? 'N/A');
        $reasonCode = (string)($dispute['reason'] ?? 'produto_danificado');
        $descriptionPt = trim((string)($dispute['description'] ?? ''));
        $attachments = is_array($dispute['attachments'] ?? null) ? $dispute['attachments'] : [];
        $disputeId = (int)($dispute['id'] ?? 0);

        $attachmentLines = [];
        foreach ($attachments as $att) {
            if (!is_array($att)) {
                continue;
            }
            $file = (string)($att['filename'] ?? '');
            $orig = (string)($att['original_name'] ?? $file);
            if ($file === '') {
                continue;
            }
            $url = rtrim($baseUrl, '/') . '/dropship/disputes/attachment/' . $disputeId . '/' . rawurlencode($file);
            $attachmentLines[] = "- {$orig}: {$url}";
        }
        $evidenceText = $attachmentLines !== []
            ? implode("\n", $attachmentLines)
            : ($language === 'zh-CN' ? '暂无附件' : 'Nenhum arquivo anexado.');

        if ($language === 'pt-BR') {
            $reasonPt = $this->reasonLabelPt($reasonCode);

            return implode("\n", [
                "Prezado Fornecedor (Brasil),",
                "Encaminhamos uma solicitação de intermediação de pós-venda aberta pelo cliente comprador.",
                "",
                "Pedido Loja: {$orderRef}",
                "Pedido AliExpress: {$aeOrderId}",
                "Código de Rastreio: {$trackingCode}",
                "Motivo: {$reasonPt}",
                "",
                "Descrição do Problema (Cliente):",
                $descriptionPt,
                "",
                "Evidências / Fotos / PDF:",
                $evidenceText,
                "",
                "Solicitamos retorno com orientação de reembolso, troca ou solução amigável dentro do prazo de garantia.",
            ]);
        }

        $translatedProblem = $this->translatePortugueseToChinese($descriptionPt);
        $reasonZh = $this->reasonLabelZh($reasonCode);

        return implode("\n", [
            "尊敬的供应商您好：",
            "我们收到买家的售后服务/退款沟通请求，请协助核实并处理以下订单问题：",
            "",
            "店铺订单号 (Loja): {$orderRef}",
            "速卖通订单号 (AliExpress Order ID): {$aeOrderId}",
            "物流追踪号 (Tracking): {$trackingCode}",
            "问题类型: {$reasonZh}",
            "",
            "问题详情（中文翻译）:",
            $translatedProblem,
            "",
            "客户原始反馈 (Original PT-BR):",
            $descriptionPt,
            "",
            "买家提供的凭证（图片 / PDF 文件）:",
            $evidenceText,
            "",
            "请告知补发、部分退款或全额退款方案，谢谢您的配合！",
        ]);
    }

    /**
     * Traduz frases e termos comuns de reclamação PT-BR para Chinês Simplificado (zh-CN).
     */
    public function translatePortugueseToChinese(string $text): string
    {
        if ($this->translatorCallback !== null) {
            $custom = (string)call_user_func($this->translatorCallback, $text);
            if (trim($custom) !== '') {
                return $custom;
            }
        }

        $normalized = trim($text);
        if ($normalized === '') {
            return '买家反馈商品存在问题，请协助核实。';
        }

        $dictionary = [
            'produto danificado'       => '商品损坏',
            'chegou com dano'          => '到货时已损坏',
            'chegou quebrado'          => '到货时已破损',
            'veio quebrado'            => '收到时已破损',
            'embalagem violada'        => '外包装破损',
            'embalagem amassada'       => '包装严重变形',
            'entrega atrasada'         => '物流严重延迟',
            'atraso na entrega'        => '配送超时延迟',
            'nao chegou'               => '尚未收到包裹',
            'não chegou'               => '尚未收到包裹',
            'pacote perdido'           => '包裹疑似丢失',
            'produto errado'           => '发错商品',
            'item errado'              => '商品与下单不符',
            'cor errada'               => '颜色错误',
            'tamanho errado'           => '尺寸/尺码错误',
            'nao funciona'             => '无法正常使用',
            'não funciona'             => '无法正常使用',
            'com defeito'              => '存在功能缺陷',
            'faltando peças'           => '缺少零件或配件',
            'faltando acessorios'      => '缺少配件',
            'faltando acessórios'      => '缺少配件',
            'solicito reembolso'       => '申请退款',
            'quero reembolso'          => '要求全额退款',
            'reembolso total'          => '全额退款',
            'troca do produto'         => '申请换货/补发',
            'em anexo'                 => '详见附件照片/PDF',
            'segue foto'               => '已附实拍照片',
            'segue pdf'                => '已附PDF证明文件',
            'vidro quebrado'           => '玻璃已碎裂',
            'tela trincada'            => '屏幕破裂',
            'arranhado'                => '表面有划痕',
            'molhado'                  => '商品受潮进水',
        ];

        $lower = mb_strtolower($normalized, 'UTF-8');
        $matchedSegments = [];
        foreach ($dictionary as $pt => $zh) {
            if (str_contains($lower, $pt)) {
                $matchedSegments[] = $zh;
            }
        }

        $unique = array_values(array_unique($matchedSegments));
        if ($unique !== []) {
            return '核心问题：' . implode('；', $unique) . '。';
        }

        return '买家反馈订单存在售后问题，请结合附件图片/PDF与原始葡语说明核实处理。';
    }

    private function reasonLabelPt(string $reason): string
    {
        return match ($reason) {
            'produto_danificado' => 'Produto Danificado / Avaria',
            'entrega_atrasada'   => 'Entrega em Atraso / Extravio',
            'item_errado'        => 'Produto Diferente / Incompleto',
            'arrependimento'     => 'Devolução / Garantia 7 a 15 Dias',
            default              => 'Solicitação de Suporte / Reembolso',
        };
    }

    private function reasonLabelZh(string $reason): string
    {
        return match ($reason) {
            'produto_danificado' => '商品损坏 / 运输受损',
            'entrega_atrasada'   => '物流延迟 / 包裹疑丢失',
            'item_errado'        => '错发 / 漏发商品',
            'arrependimento'     => '退货退款申请 (7-15天无理由/售后)',
            default              => '订单售后协商 / 退款请求',
        };
    }
}
