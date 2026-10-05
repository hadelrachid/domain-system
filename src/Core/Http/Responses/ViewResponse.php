<?php
namespace DomainSystem\Core\Http\Responses;

use DomainSystem\Core\Http\Response;

class ViewResponse extends Response
{
    private bool $wrapLayout;

    public function __construct(string $content = '', bool $wrapLayout = true, int $statusCode = 200, array $headers = [])
    {
        parent::__construct($content, $statusCode, $headers);
        $this->wrapLayout = $wrapLayout;
        if (!isset($this->headers['Content-Type'])) {
            $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        }
    }

    public function shouldWrapLayout(): bool
    {
        return $this->wrapLayout;
    }
}
