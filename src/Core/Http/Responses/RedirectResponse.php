<?php
namespace DomainSystem\Core\Http\Responses;

use DomainSystem\Core\Http\Response;

class RedirectResponse extends Response
{
    public function __construct(string $url, int $statusCode = 302, array $headers = [])
    {
        $headers['Location'] = $url;
        parent::__construct('', $statusCode, $headers);
    }
    
    public function shouldWrapLayout(): bool
    {
        return false;
    }
}
