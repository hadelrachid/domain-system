<?php

namespace DomainSystem\Plugins\legal_cases\Controllers;

use DomainSystem\Core\Contracts\ThemeManagerInterface;
use DomainSystem\Core\Http\Response;
use DomainSystem\Plugins\legal_cases\Contracts\LegalCaseRepositoryInterface;

class LegalController
{
    private ThemeManagerInterface $theme;
    private LegalCaseRepositoryInterface $repository;

    public function __construct(
        ThemeManagerInterface $theme,
        LegalCaseRepositoryInterface $repository
    ) {
        $this->theme = $theme;
        $this->repository = $repository;
    }

    public function index(): Response
    {
        $cases = $this->repository->all();

        $html = $this->theme->render(
            'admin_legal',
            ['cases' => $cases],
            __DIR__ . '/../views'
        );

        return new Response($html);
    }
}
