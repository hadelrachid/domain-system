<?php

namespace DomainSystem\Plugins\clinic_pack\Controllers;

use DomainSystem\Core\Theme\ThemeManager;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;
use DomainSystem\Core\Http\SessionManager;

class NursingDashboardController
{
    public function __construct(
        private ThemeManager $theme,
        private SessionManager $session
    ) {}

    public function index(Request $request): Response
    {
        $this->theme->setActiveThemePath(__DIR__ . '/../themes/cockpit_nursing');
        $html = $this->theme->render('index', ['user_name' => $this->session->get('user_name', 'Enfermeiro')]);
        return new Response($html);
    }
}
