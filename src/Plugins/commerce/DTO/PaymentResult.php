<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\commerce\DTO;

/**
 * DTO que encapsula o resultado de qualquer operação de liquidação financeira (SRP / DIP).
 */
class PaymentResult
{
    public bool $success;
    public string $orderRef;
    public string $status; // 'approved', 'pending', 'rejected', 'failed'
    public string $paymentMethod; // 'pix', 'credit_card', 'boleto', 'paypal'
    public ?string $pixQrCode;
    public ?string $pixCopiaECola;
    public ?string $boletoUrl;
    public ?string $redirectUrl;
    public ?string $transactionId;
    public string $message;
    public array $rawPayload;

    public function __construct(
        bool $success,
        string $orderRef,
        string $status,
        string $paymentMethod = 'pix',
        ?string $pixQrCode = null,
        ?string $pixCopiaECola = null,
        ?string $boletoUrl = null,
        ?string $redirectUrl = null,
        ?string $transactionId = null,
        string $message = '',
        array $rawPayload = []
    ) {
        $this->success = $success;
        $this->orderRef = $orderRef;
        $this->status = $status;
        $this->paymentMethod = $paymentMethod;
        $this->pixQrCode = $pixQrCode;
        $this->pixCopiaECola = $pixCopiaECola;
        $this->boletoUrl = $boletoUrl;
        $this->redirectUrl = $redirectUrl;
        $this->transactionId = $transactionId;
        $this->message = $message;
        $this->rawPayload = $rawPayload;
    }

    public static function approved(string $orderRef, string $paymentMethod, ?string $transactionId = null, string $message = 'Pagamento aprovado com sucesso!'): self
    {
        return new self(true, $orderRef, 'approved', $paymentMethod, null, null, null, null, $transactionId, $message);
    }

    public static function pendingPix(string $orderRef, string $pixCopiaECola, ?string $pixQrCode = null, ?string $transactionId = null, string $message = 'Aguardando pagamento via Pix.'): self
    {
        return new self(true, $orderRef, 'pending', 'pix', $pixQrCode, $pixCopiaECola, null, null, $transactionId, $message);
    }

    public static function pendingBoleto(string $orderRef, string $boletoUrl, ?string $transactionId = null, string $message = 'Boleto bancário gerado com sucesso.'): self
    {
        return new self(true, $orderRef, 'pending', 'boleto', null, null, $boletoUrl, null, $transactionId, $message);
    }

    public static function redirect(string $orderRef, string $redirectUrl, string $paymentMethod = 'paypal', string $message = 'Redirecionando para conclusão segura do pagamento.'): self
    {
        return new self(true, $orderRef, 'pending', $paymentMethod, null, null, null, $redirectUrl, null, $message);
    }

    public static function failure(string $orderRef, string $message, string $paymentMethod = 'unknown'): self
    {
        return new self(false, $orderRef, 'failed', $paymentMethod, null, null, null, null, null, $message);
    }

    public function isApproved(): bool
    {
        return $this->success && $this->status === 'approved';
    }
}
