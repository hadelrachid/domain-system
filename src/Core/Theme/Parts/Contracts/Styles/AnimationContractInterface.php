<?php

declare(strict_types=1);

namespace DomainSystem\Core\Theme\Parts\Contracts\Styles;

/**
 * Contrato para micro-interações, hover e animações com aceleração por GPU (SOLID - ISP / Ring 0).
 */
interface AnimationContractInterface
{
    /** Efeito ao passar o cursor (grow, lift, glow, shimmer, bounce, etc.) */
    public function getHoverEffect(): ?string;

    /** Animação de entrada ao rolar a página (fadeIn, slideUp, zoomIn, etc.) */
    public function getEntranceAnimation(): ?string;

    /** Duração da transição CSS (ex: "0.25s", "300ms") */
    public function getTransitionDuration(): ?string;

    /** Curva de aceleração / timing function (ex: "ease", "cubic-bezier(...)") */
    public function getTimingFunction(): ?string;
}
