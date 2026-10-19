<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

/**
 * Manual Oficial e Tutorial Interativo do Plugin Academy (LMS Híbrido & Editor em Blocos).
 * Descoberto automaticamente pelo Cockpit via contrato ManualModuleInterface.
 */
class AcademyManualModule implements ManualModuleInterface
{
    public function getId(): string
    {
        return 'academy';
    }

    public function getTitle(): string
    {
        return 'Plugin Academy (LMS Híbrido & Editor em Blocos)';
    }

    public function getSubtitle(): string
    {
        return 'Editor em blocos estilo Gutenberg/Notion, preview multi-dispositivos ao vivo e importação de pastas.';
    }

    public function getBadge(): string
    {
        return 'Módulo Auto-Descoberto • Academy v2.3.0';
    }

    public function getAccentColor(): string
    {
        return '#a855f7';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/academy';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'academy-create-published',
                'label' => 'Criar um curso com status "Publicado" e validar que o slug sem acentos é gerado automaticamente.',
            ],
            [
                'id'    => 'academy-hybrid-modes',
                'label' => 'Testar os 3 modos híbridos na estrutura do curso: Editor em Blocos (content), Arquivo PHP do Tema (theme_file) e Importar Pasta do Tema.',
            ],
            [
                'id'    => 'academy-block-editor-live',
                'label' => 'Abrir o Editor de Blocos (/admin/academy/lessons/{id}/editor), adicionar Título, Parágrafo, Vídeo 16:9 e Código, e ver o Preview Dinâmico Lado a Lado atualizar em tempo real.',
            ],
            [
                'id'    => 'academy-device-simulator',
                'label' => 'Alternar no Preview entre Desktop e Mobile/Tablet (320px, 375px, 430px, 768px, 1024px) e testar o menu ☰ e a gaveta de aulas interativa.',
            ],
            [
                'id'    => 'academy-public-classroom',
                'label' => 'Acessar a aula pública (/cursos/{curso}/{aula}) e validar o vídeo 16:9, navegação entre aulas e gaveta mobile responsiva.',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        ob_start();
        ?>
        <div style="color: var(--text-main, #c5c6c7); font-size: 14px; line-height: 1.7;">
            <p style="margin-top: 0;">
                O <strong>Plugin Academy v2.3.0</strong> transforma o Domain-System OS em uma plataforma completa de ensino (<strong>LMS Híbrido</strong>), combinando a velocidade de arquivos nativos do tema com um <strong>Editor em Blocos Modular</strong> estilo <em>WordPress Gutenberg / Notion / Editor.js</em> e simulador responsivo em tempo real.
            </p>

            <!-- ARQUITETURA DOS 3 PILARES -->
            <h3 style="color: #45f3ff; font-size: 15px; margin: 20px 0 12px;">1. Os 3 Modos Híbridos de Criação de Aulas</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 22px;">
                <div style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #c084fc; font-weight: 800; font-size: 13px; margin-bottom: 6px;">⚡ Modo 1: Editor em Blocos (Nativo)</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Ideal para criar aulas ricas direto pelo painel, sem tocar em código: <strong>Títulos (H1-H3)</strong>, <strong>Parágrafos</strong>, <strong>Vídeos 16:9 (YouTube/Vimeo/MP4)</strong>, <strong>Blocos de Código</strong>, <strong>Dicas/Alertas (Callouts)</strong>, <strong>Listas</strong>, <strong>Imagens</strong> e <strong>Divisores</strong>.
                    </div>
                </div>

                <div style="background: rgba(6, 182, 212, 0.08); border: 1px solid rgba(6, 182, 212, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #45f3ff; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📄 Modo 2: Arquivo PHP do Tema</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Ideal para aulas interativas customizadas em código. Vincula qualquer arquivo <code>pages/*.php</code> do tema ativo com validação estrita anti-Path Traversal (<code>realpath</code>).
                    </div>
                </div>

                <div style="background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #00d284; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📂 Modo 3: Auto-Importação de Pasta</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Com 1 clique no botão <strong>"📂 Importar Pasta do Tema"</strong> dentro de qualquer módulo, o sistema escaneia uma pasta inteira do tema e gera todas as aulas ordenadas automaticamente.
                    </div>
                </div>
            </div>

            <!-- TUTORIAL PASSO A PASSO -->
            <h3 style="color: #fff; font-size: 15px; margin: 22px 0 10px;">2. Tutorial Passo a Passo: Como Criar e Editar um Curso</h3>
            <ol style="margin: 0 0 20px 20px; padding: 0; line-height: 1.85; font-size: 13.5px;">
                <li>
                    <strong>Criar o Curso (<code>/admin/academy</code>):</strong> Clique em <em>"+ Novo Curso"</em>, informe o título (ex: <em>Curso de Python</em>), escolha o status <strong>Publicado</strong> (cursos em status <em>Em Breve</em> ou <em>Rascunho</em> ocultam as aulas para visitantes públicos) e envie uma capa horizontal na proporção <strong>16:9</strong> (mín. 640&times;360 px).
                </li>
                <li>
                    <strong>Estruturar Módulos e Aulas (<code>/admin/academy/edit/{id}</code>):</strong> Adicione módulos e aulas. Em cada aula, selecione a origem:
                    <code>⚡ Editor em Blocos (Nativo)</code> ou <code>📄 Arquivo PHP do Tema</code>, e clique em <strong>Salvar Curso e Estrutura</strong>.
                </li>
                <li>
                    <strong>Abrir o Editor de Blocos (<code>/admin/academy/lessons/{id}/editor</code>):</strong> Clique no botão <strong>"⚡ Editor de Blocos"</strong> ao lado da aula. Adicione, reordene (▲/▼), duplique (📋) ou remova blocos e salve instantaneamente via AJAX (ou com o atalho <code>Ctrl + S</code>).
                </li>
                <li>
                    <strong>Testar no Preview Ao Vivo & Simulador Mobile/Tablet:</strong>
                    Use a barra superior para alternar entre <code>✏️ Blocos</code>, <code>⚡ Dinâmico (Lado a Lado)</code> e <code>👁️ Aula Final</code>. No painel da direita, alterne entre <strong>🖥️ Desktop</strong> e <strong>📱 Mobile</strong> para testar em 5 resoluções padrões:
                    <em>Celular Compacto (320px)</em>, <em>Smartphone Padrão (375px)</em>, <em>Smartphone Max (430px)</em>, <em>Tablet Retrato (768px)</em> e <em>Tablet Paisagem (1024px)</em>.
                </li>
                <li>
                    <strong>Exibir no Site:</strong> Use o shortcode <code>[hub_de_cursos]</code> em qualquer página (como <code>/tutoriais</code>) ou acesse o catálogo direto em <a href="<?= htmlspecialchars($baseUrl) ?>/cursos" target="_blank" style="color:#45f3ff;"><code><?= htmlspecialchars($baseUrl) ?>/cursos</code></a>.
                </li>
            </ol>

            <!-- DESTAQUE ARQUITETURAL SOLID -->
            <div style="background: rgba(168, 85, 247, 0.08); border-left: 4px solid #a855f7; padding: 12px 16px; margin: 16px 0; border-radius: 0 6px 6px 0; font-size: 13px;">
                <strong>Blindagem SOLID &amp; Segurança (Zero XSS / Zero Superglobais):</strong>
                O serviço <code>LessonBlockRenderer</code> sanitiza todo o JSON dos blocos no servidor (bloqueando vetores <code>javascript:</code> e <code>data:</code>), extrai texto limpo para meta-descrições de SEO automaticamente e este próprio manual é carregado via contrato <code>ManualModuleInterface</code> direto de <code>src/Plugins/academy/manual/</code>!
            </div>
        </div>
        <?php
        return (string)ob_get_clean();
    }
}
