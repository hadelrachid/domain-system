<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\Services;

use DomainSystem\Plugins\academy\Contracts\CourseImageStorageInterface;
use DomainSystem\Plugins\academy\Contracts\CourseRepositoryInterface;
use InvalidArgumentException;
use RuntimeException;

class CourseService
{
    private CourseRepositoryInterface $repository;
    private CourseImageStorageInterface $storage;

    private static array $accentMap = [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
        'é'=>'e','è'=>'e','ê'=>'e','ë'=>'e',
        'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
        'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
        'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
        'ç'=>'c','ñ'=>'n',
        'Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a',
        'É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e',
        'Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i',
        'Ó'=>'o','Ò'=>'o','Õ'=>'o','Ô'=>'o','Ö'=>'o',
        'Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u',
        'Ç'=>'c','Ñ'=>'n'
    ];

    public function __construct(CourseRepositoryInterface $repository, CourseImageStorageInterface $storage)
    {
        $this->repository = $repository;
        $this->storage = $storage;
    }

    public function createCourse(array $data, ?array $uploadedFile = null): int
    {
        $this->validateData($data);

        $slug = $this->generateUniqueSlug((string)$data['title']);
        $thumbnail = null;

        if (!empty($uploadedFile['tmp_name'])) {
            $thumbnail = $this->storage->storeUpload($uploadedFile);
        }

        return $this->repository->create([
            'title'       => trim((string)$data['title']),
            'slug'        => $slug,
            'description' => trim((string)($data['description'] ?? '')),
            'thumbnail'   => $thumbnail,
            'status'      => $data['status'] ?? 'draft',
            'link'        => trim((string)($data['link'] ?? '')),
        ]);
    }

    public function updateCourse(int $id, array $data, ?array $uploadedFile = null): bool
    {
        $course = $this->repository->findById($id);
        if (!$course) {
            throw new RuntimeException("Curso #{$id} não encontrado.");
        }

        $this->validateData($data);

        $slug = $course['slug'];
        if (trim((string)$data['title']) !== (string)$course['title']) {
            $slug = $this->generateUniqueSlug((string)$data['title'], $id);
        }

        $thumbnail = $course['thumbnail'];
        if (!empty($uploadedFile['tmp_name'])) {
            $newThumbnail = $this->storage->storeUpload($uploadedFile);
            if (!empty($thumbnail)) {
                $this->storage->deleteFile((string)$thumbnail);
            }
            $thumbnail = $newThumbnail;
        }

        return $this->repository->update($id, [
            'title'       => trim((string)$data['title']),
            'slug'        => $slug,
            'description' => trim((string)($data['description'] ?? '')),
            'thumbnail'   => $thumbnail,
            'status'      => $data['status'] ?? 'draft',
            'link'        => trim((string)($data['link'] ?? '')),
        ]);
    }

    public function deleteCourse(int $id): bool
    {
        $course = $this->repository->findById($id);
        if ($course && !empty($course['thumbnail'])) {
            $this->storage->deleteFile((string)$course['thumbnail']);
        }
        return $this->repository->delete($id);
    }

    public function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $text = strtr($title, self::$accentMap);

        if (function_exists('transliterator_transliterate')) {
            $trans = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $text);
            if ($trans !== false) {
                $text = $trans;
            }
        }

        $slug = strtolower(trim((string)preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
        if (empty($slug)) {
            $slug = 'curso';
        }

        $base = $slug;
        $counter = 1;

        while ($existing = $this->repository->findBySlug($slug)) {
            if ($ignoreId !== null && (int)$existing['id'] === $ignoreId) {
                break;
            }
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function validateData(array $data): void
    {
        if (empty(trim((string)($data['title'] ?? '')))) {
            throw new InvalidArgumentException("O título do curso é obrigatório.");
        }

        $validStatuses = ['draft', 'soon', 'published'];
        if (!in_array($data['status'] ?? 'draft', $validStatuses, true)) {
            throw new InvalidArgumentException("Status inválido.");
        }

        if (!empty($data['link'])) {
            $link = (string)$data['link'];
            if (!filter_var($link, FILTER_VALIDATE_URL) && !str_starts_with($link, '/') && !str_starts_with($link, '#')) {
                throw new InvalidArgumentException("O link informado deve ser uma URL válida ou um caminho relativo.");
            }
        }
    }
}
