<?php
namespace DomainSystem\Plugins\clinic_pack\Views;

interface CockpitViewInterface
{
    public function renderHeader(): string;
    public function renderMainContent(): string;
    public function renderProfileModal(): string;
    public function renderScripts(): string;
    public function render(): void;
}