<?php

namespace DomainSystem\Core\Contracts;

interface DashboardWidgetProviderInterface
{
    /**
     * Retorna o nome amigável do pacote/módulo (Ex: "Agenda Médica")
     */
    public function getProviderName(): string;

    /**
     * Retorna os widgets disponíveis neste módulo.
     * Deve retornar um array onde a chave é o ID único do widget e o valor é um array com ['title' => '...', 'description' => '...']
     */
    public function getAvailableWidgets(): array;

    /**
     * Retorna a string HTML renderizada para um widget específico.
     * @param string $widgetId
     * @return string
     */
    public function renderWidget(string $widgetId): string;
}
