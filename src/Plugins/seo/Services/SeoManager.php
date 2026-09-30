<?php

namespace DomainSystem\Plugins\seo\Services;

use DomainSystem\Plugins\seo\Contracts\SeoManagerInterface;

class SeoManager implements SeoManagerInterface
{
    private string $title = 'Domain System OS';
    private string $description = '';
    private string $canonical = '';
    private string $favicon = '';
    private array $metaTags = [];

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setCanonical(string $url): self
    {
        $this->canonical = $url;
        return $this;
    }

    public function addMeta(string $name, string $content, string $type = 'name'): self
    {
        $this->metaTags[] = [
            'type' => $type,
            'name' => $name,
            'content' => $content
        ];
        return $this;
    }

    public function setFavicon(string $url): self
    {
        $this->favicon = $url;
        return $this;
    }

    public function generateTags(): string
    {
        $html = "<title>" . htmlspecialchars($this->title) . "</title>\n";
        
        if (!empty($this->favicon)) {
            // Se for svg, adiciona type image/svg+xml
            $type = str_ends_with(strtolower($this->favicon), '.svg') ? 'image/svg+xml' : 'image/x-icon';
            $html .= "<link rel=\"icon\" type=\"{$type}\" href=\"" . htmlspecialchars($this->favicon) . "\">\n";
        }
        
        if (!empty($this->description)) {
            $html .= "<meta name=\"description\" content=\"" . htmlspecialchars($this->description) . "\">\n";
        }
        
        if (!empty($this->canonical)) {
            $html .= "<link rel=\"canonical\" href=\"" . htmlspecialchars($this->canonical) . "\">\n";
        }
        
        foreach ($this->metaTags as $meta) {
            $type = $meta['type']; // name or property
            $html .= "<meta {$type}=\"" . htmlspecialchars($meta['name']) . "\" content=\"" . htmlspecialchars($meta['content']) . "\">\n";
        }
        
        return $html;
    }
}
