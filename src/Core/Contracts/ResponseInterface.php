<?php

namespace DomainSystem\Core\Contracts;

interface ResponseInterface
{
    public function setContent(string $content): self;
    public function getContent(): string;
    public function setStatusCode(int $code): self;
    public function setHeader(string $name, string $value): self;
    public function send(): void;
}
