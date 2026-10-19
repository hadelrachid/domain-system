<?php

declare(strict_types=1);

namespace DomainSystem\Core\Update\Value;

use InvalidArgumentException;

final readonly class ReleaseInfo
{
    public function __construct(
        public string $version,
        public string $tag,
        public string $publishedAt,
        public string $changelog,
        public string $assetUrl,
        public string $assetName,
        public int $sizeBytes,
        public string $sha256,
        public ?string $etag = null,
    ) {
        if (trim($this->version) === '') {
            throw new InvalidArgumentException('A versão de ReleaseInfo não pode ser vazia.');
        }
        if (trim($this->tag) === '') {
            throw new InvalidArgumentException('A tag de ReleaseInfo não pode ser vazia.');
        }
        if (trim($this->publishedAt) === '') {
            throw new InvalidArgumentException('A data de publicação de ReleaseInfo não pode ser vazia.');
        }
        if (trim($this->assetUrl) === '') {
            throw new InvalidArgumentException('A URL do asset de ReleaseInfo não pode ser vazia.');
        }
        if (trim($this->assetName) === '') {
            throw new InvalidArgumentException('O nome do asset de ReleaseInfo não pode ser vazio.');
        }
        if ($this->sizeBytes < 0) {
            throw new InvalidArgumentException('O tamanho em bytes de ReleaseInfo não pode ser negativo.');
        }
        if (preg_match('/^[a-fA-F0-9]{64}$/', $this->sha256) !== 1) {
            throw new InvalidArgumentException('O hash sha256 de ReleaseInfo deve ter exatamente 64 caracteres hexadecimais.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version'     => $this->version,
            'tag'         => $this->tag,
            'publishedAt' => $this->publishedAt,
            'changelog'   => $this->changelog,
            'assetUrl'    => $this->assetUrl,
            'assetName'   => $this->assetName,
            'sizeBytes'   => $this->sizeBytes,
            'sha256'      => strtolower($this->sha256),
            'etag'        => $this->etag,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            version: (string)($data['version'] ?? ''),
            tag: (string)($data['tag'] ?? ''),
            publishedAt: (string)($data['publishedAt'] ?? ''),
            changelog: (string)($data['changelog'] ?? ''),
            assetUrl: (string)($data['assetUrl'] ?? ''),
            assetName: (string)($data['assetName'] ?? ''),
            sizeBytes: (int)($data['sizeBytes'] ?? 0),
            sha256: (string)($data['sha256'] ?? ''),
            etag: isset($data['etag']) && $data['etag'] !== '' ? (string)$data['etag'] : null,
        );
    }
}
