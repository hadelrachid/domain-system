<?php
namespace DomainSystem\Core\Http\Responses;

use DomainSystem\Core\Http\Response;

class JsonResponse extends Response
{
    public function __construct(mixed $data, int $statusCode = 200, array $headers = [])
    {
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);
        $headers['Content-Type'] = 'application/json';
        parent::__construct($content, $statusCode, $headers);
    }
    
    public function shouldWrapLayout(): bool
    {
        return false;
    }
}
