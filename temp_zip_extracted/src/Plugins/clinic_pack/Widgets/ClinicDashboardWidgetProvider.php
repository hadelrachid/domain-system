<?php

namespace DomainSystem\Plugins\clinic_pack\Widgets;

use DomainSystem\Core\Contracts\DashboardWidgetProviderInterface;
use DomainSystem\Core\Application;
use DomainSystem\Plugins\SystemAdmin\Contracts\DashboardRepositoryInterface;

class ClinicDashboardWidgetProvider implements DashboardWidgetProviderInterface
{
    private function getRepo(): ?DashboardRepositoryInterface
    {
        try {
            return Application::getInstance()->getContainer()->make(DashboardRepositoryInterface::class);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getProviderName(): string
    {
        return "Pack Clínico (Agenda e Médicos)";
    }

    public function getAvailableWidgets(): array
    {
        return [
            'clinic_daily_stats' => [
                'title' => 'Estatísticas de Hoje',
                'description' => 'Mostra o total de pacientes, total de consultas e faturamento do dia.'
            ],
            'clinic_queue' => [
                'title' => 'Fila de Espera / Fila de Atendimento',
                'description' => 'Mostra os pacientes aguardando ou em atendimento no momento.'
            ],
            'clinic_chart' => [
                'title' => 'Gráfico de Consultas (7 Dias)',
                'description' => 'Gráfico dinâmico visualizando o volume de consultas da última semana.'
            ]
        ];
    }

    public function renderWidget(string $widgetId): string
    {
        $repo = $this->getRepo();
        $html = '';
        
        switch ($widgetId) {
            case 'clinic_daily_stats':
                $stats = $repo ? $repo->getGlobalStats(date('Y-m-d')) : ['appointmentsToday' => 0, 'totalPatients' => 0];
                
                $html = '
                <div style="background: #1d2327; color: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #f56e28; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                    <h3 style="margin-top:0; color: #f56e28; display:flex; align-items:center; gap:10px;">📊 Estatísticas da Clínica</h3>
                    <div style="display: flex; gap: 40px; margin-top: 15px;">
                        <div>
                            <p style="margin: 0; font-size: 13px; color: #a7aaad; text-transform:uppercase; letter-spacing:1px;">Consultas Hoje</p>
                            <h4 style="margin: 5px 0 0; font-size: 32px; font-weight: 300;">' . $stats['appointmentsToday'] . '</h4>
                        </div>
                        <div>
                            <p style="margin: 0; font-size: 13px; color: #a7aaad; text-transform:uppercase; letter-spacing:1px;">Pacientes Totais</p>
                            <h4 style="margin: 5px 0 0; font-size: 32px; font-weight: 300;">' . $stats['totalPatients'] . '</h4>
                        </div>
                    </div>
                </div>';
                break;
                
            case 'clinic_queue':
                $queue = $repo ? $repo->getWaitingRoom() : [];
                $listHtml = '';
                
                if (empty($queue)) {
                    $listHtml = '<li style="padding: 15px 0; color: #8c8f94;">Ninguém na fila de espera no momento.</li>';
                } else {
                    foreach ($queue as $q) {
                        $color = $q['status'] === 'Em Atendimento' ? '#00d284' : '#f56e28';
                        $listHtml .= '<li style="padding: 12px 0; border-bottom: 1px solid #2c3338; display:flex; justify-content:space-between;">
                            <span><strong>' . htmlspecialchars($q['patient_name']) . '</strong> - ' . htmlspecialchars($q['doctor_name']) . '</span>
                            <span style="color: '.$color.'; font-size:12px; font-weight:bold; padding:3px 8px; border:1px solid '.$color.'; border-radius:12px;">' . htmlspecialchars($q['status']) . '</span>
                        </li>';
                    }
                }
                
                $html = '
                <div style="background: #1d2327; color: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #2271b1; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                    <h3 style="margin-top:0; color: #2271b1; display:flex; align-items:center; gap:10px;">👥 Fila de Atendimento</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        ' . $listHtml . '
                    </ul>
                </div>';
                break;
                
            case 'clinic_chart':
                $chartData = $repo ? $repo->getAppointmentsChartData(7) : ['labels' => [], 'data' => []];
                $labelsJson = json_encode($chartData['labels']);
                $dataJson = json_encode($chartData['data']);
                $canvasId = 'chart_' . uniqid();
                
                $html = '
                <div style="background: #1d2327; color: #fff; padding: 20px; border-radius: 8px; border-left: 4px solid #00d284; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.3);">
                    <h3 style="margin-top:0; color: #00d284; display:flex; align-items:center; gap:10px;">📈 Volume de Consultas (7 Dias)</h3>
                    <div style="position: relative; height:250px; width:100%; margin-top:20px;">
                        <canvas id="' . $canvasId . '"></canvas>
                    </div>
                </div>
                <!-- Carrega o Chart.js se ainda não existir na página -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const ctx = document.getElementById("' . $canvasId . '").getContext("2d");
                    
                    // Gradiente Dark Futurista
                    let gradient = ctx.createLinearGradient(0, 0, 0, 400);
                    gradient.addColorStop(0, "rgba(0, 210, 132, 0.5)");
                    gradient.addColorStop(1, "rgba(0, 210, 132, 0.0)");
                    
                    new Chart(ctx, {
                        type: "line",
                        data: {
                            labels: ' . $labelsJson . ',
                            datasets: [{
                                label: "Consultas",
                                data: ' . $dataJson . ',
                                borderColor: "#00d284",
                                backgroundColor: gradient,
                                borderWidth: 3,
                                pointBackgroundColor: "#1d2327",
                                pointBorderColor: "#00d284",
                                pointBorderWidth: 2,
                                pointRadius: 4,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false }
                            },
                            scales: {
                                x: {
                                    grid: { color: "#2c3338", drawBorder: false },
                                    ticks: { color: "#8c8f94" }
                                },
                                y: {
                                    grid: { color: "#2c3338", drawBorder: false },
                                    ticks: { color: "#8c8f94", stepSize: 1 }
                                }
                            }
                        }
                    });
                });
                </script>';
                break;
                
            default:
                $html = '<div style="color: red;">Widget não encontrado.</div>';
                break;
        }

        return $html;
    }
}
