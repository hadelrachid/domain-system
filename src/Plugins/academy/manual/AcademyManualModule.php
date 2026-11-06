<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

/**
 * Manual Oficial e Tutorial Interativo Completo do Plugin Academy (LMS Híbrido, Editor em Blocos & Fábrica PHP).
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
        return 'Plugin Academy (LMS Híbrido, Editor em Blocos & Fábrica PHP)';
    }

    public function getSubtitle(): string
    {
        return 'Guia completo de criação de cursos, fábrica de arquivos PHP, PDFs híbridos multi-cloud, cockpit do aluno (LGPD) e integração com DomainCommerce.';
    }

    public function getBadge(): string
    {
        return 'Módulo Auto-Descoberto • Academy v2.6.0';
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
                'label' => '1. Criar um curso com status "Publicado" em /admin/academy e validar que o slug sem acentos é gerado automaticamente.',
            ],
            [
                'id'    => 'academy-create-folder-module',
                'label' => '2. Na tela "Editar Curso", clicar em "📁 Criar Pasta (ou Módulo)", digitar o nome do módulo e verificar a criação da subpasta em pages/.',
            ],
            [
                'id'    => 'academy-scaffold-folder-php',
                'label' => '3. Criar uma aula com o Título "Estrutura de Dados", manter a "🏭 Fábrica Automática de Arquivos PHP" marcada e salvar para fabricar pages/estrutura-de-dados.php e a rota /cursos/estrutura-de-dados.',
            ],
            [
                'id'    => 'academy-hybrid-modes',
                'label' => '4. Testar os 4 fluxos de conteúdo na estrutura do curso: Editor em Blocos (content), Arquivo PHP Fabricado, Arquivo Existente do Tema (theme_file) e "📂 Importar Pasta do Tema".',
            ],
            [
                'id'    => 'academy-block-editor-live',
                'label' => '5. Abrir o "⚡ Editor de Blocos" da aula, adicionar Título, Parágrafo, Vídeo 16:9, Código e Alerta (Callout), e salvar com Ctrl+S (ou botão Salvar) vendo o Preview Dinâmico atualizar em tempo real.',
            ],
            [
                'id'    => 'academy-pdf-hybrid',
                'label' => '6. No Editor de Blocos, adicionar o bloco "📄 Visualizador de PDF": colar link do Google Drive, OneDrive ou fazer upload local de arquivo (até 128MB) e verificar a renderização protegida.',
            ],
            [
                'id'    => 'academy-device-simulator',
                'label' => '7. Alternar no Simulador entre Desktop e os 5 tamanhos Mobile/Tablet (320px, 375px, 430px, 768px, 1024px) e testar os 3 Perfis de Layout Mobile (Gaveta, Barra Fixa e Modo Cinema 16:9).',
            ],
            [
                'id'    => 'academy-student-cockpit',
                'label' => '8. Fazer login como aluno e acessar o Cockpit (/aluno/painel): verificar cálculo de progresso percentual, marcação de conclusão e anotações isoladas (LGPD).',
            ],
            [
                'id'    => 'academy-access-guard',
                'label' => '9. Definir um preço para o curso (ex: R$ 197,00) e verificar que visitantes não-matriculados veem o card de matrícula com checkout integrado ao DomainCommerce.',
            ],
            [
                'id'    => 'academy-public-classroom',
                'label' => '10. Acessar a sala de aula pública (/cursos/{curso}/{aula} ou rota direta /cursos/estrutura-de-dados) e o catálogo [hub_de_cursos].',
            ],
        ];
    }

    public function renderContent(string $baseUrl = ''): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        ob_start();
        ?>
        <div style="color: var(--text-main, #c5c6c7); font-size: 14px; line-height: 1.75;">
            <p style="margin-top: 0;">
                O <strong>Plugin Academy (v2.6.0)</strong> é o motor de ensino (<strong>LMS Híbrido</strong>) do Domain-System OS. Ele foi projetado sob os princípios <strong>SOLID</strong> e <strong>Inversão de Dependência (DIP)</strong> para permitir que você crie cursos completos de quatro maneiras integradas: digitando diretamente no <strong>Editor Visual em Blocos</strong>, fabricando automaticamente <strong>arquivos <code>.php</code> responsivos</strong> no tema ativo, vinculando páginas existentes ou importando pastas inteiras com 1 clique.
            </p>

            <!-- SEÇÃO 1: OS 4 MODOS DE CRIAÇÃO -->
            <h3 style="color: #45f3ff; font-size: 15px; margin: 22px 0 12px;">1. Os 4 Modos de Construção de Aulas</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 24px;">
                <div style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #c084fc; font-weight: 800; font-size: 13px; margin-bottom: 6px;">⚡ 1. Editor Visual em Blocos</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Construa a aula visualmente com <strong>blocos nativos</strong>: <em>Título (H2/H3/H4)</em>, <em>Parágrafo rico</em>, <em>Vídeo 16:9</em>, <em>Código com destaque</em>, <em>Callout</em>, <em>Lista</em>, <em>Imagem</em>, <em>Divisor</em> e <strong>Visualizador de PDF Híbrido</strong>.
                    </div>
                </div>

                <div style="background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #00d284; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🏭 2. Fábrica Automática de Arquivos PHP</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Basta digitar o título da aula (ex: <strong>"Estrutura de Dados"</strong>) e salvar! O contrato <code>LessonFileScaffolderInterface</code> cria automaticamente <code>pages/estrutura-de-dados.php</code> já com CSS/JS Desktop &amp; Mobile e libera a rota direta <code>/cursos/estrutura-de-dados</code>.
                    </div>
                </div>

                <div style="background: rgba(6, 182, 212, 0.08); border: 1px solid rgba(6, 182, 212, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #45f3ff; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📄 3. Arquivo PHP Personalizado do Tema</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Vincule qualquer arquivo <code>pages/*.php</code> do tema ativo via <code>ThemePageResolverInterface</code>, com validação estrita anti-Path Traversal (<code>realpath</code>).
                    </div>
                </div>

                <div style="background: rgba(245, 166, 35, 0.08); border: 1px solid rgba(245, 166, 35, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #f5a623; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📂 4. Importação de Pasta em 1 Clique</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Já possui uma pasta com várias aulas em <code>pages/meu-curso/</code>? Clique em <strong>"📂 Importar Pasta do Tema"</strong> dentro do módulo e todas as aulas serão cadastradas e ordenadas automaticamente.
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 2: ROTEIRO PRÁTICO PASSO A PASSO -->
            <h3 style="color: #fff; font-size: 15px; margin: 22px 0 10px;">2. Guia Passo a Passo: Do Cadastro à Publicação</h3>
            <div style="background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 16px 20px; margin-bottom: 22px;">
                <ol style="margin: 0 0 0 18px; padding: 0; line-height: 1.9; font-size: 13.5px;">
                    <li style="margin-bottom: 8px;">
                        <strong>Passo 1 — Cadastrar o Curso (<code>/admin/academy</code>):</strong>
                        Preencha o <em>Título do Curso</em>, escolha o status <strong>Publicado</strong>, defina o preço (ou deixe zerado para gratuito), envie uma imagem de capa <strong>16:9</strong> (mínimo <code>640&times;360 px</code>) e clique em <strong>Salvar Curso</strong>.
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>Passo 2 — Criar Módulos ou Pastas Físicas (<code>📁 Criar Pasta (ou Módulo)</code>):</strong>
                        Ao digitar o nome do módulo (ex: <code>Módulo 01 - Fundamentos</code>), o sistema pode criar opcionalmente a pasta física <code>pages/modulo-01-fundamentos/</code> no tema ativo.
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>Passo 3 — Criar Aulas com Fábrica PHP:</strong>
                        Adicione aulas com a caixa <strong>"🏭 Fábrica Automática de Arquivos PHP"</strong> marcada. O arquivo <code>.php</code> responsivo é gerado no tema e a URL direta amigável <code>/cursos/{slug-da-aula}</code> é liberada no mesmo instante.
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>Passo 4 — Editor em Blocos &amp; Preview com Simulador Mobile/Tablet:</strong>
                        No estúdio de blocos da aula, teste entre Desktop e os 5 dispositivos móveis no <strong>Simulador Mobile/Tablet</strong> (320px até 1024px) e escolha entre os 3 perfis mobile: <strong>📱 Gaveta (drawer)</strong>, <strong>📲 Barra Inferior Fixa (bottom_bar)</strong> ou <strong>🎬 Modo Cinema 16:9 (cinema)</strong>.
                    </li>
                </ol>
            </div>

            <!-- SEÇÃO 3: VISUALIZADOR DE PDF HÍBRIDO -->
            <h3 style="color: #45f3ff; font-size: 15px; margin: 22px 0 10px;">3. Apostilas e PDFs Híbridos Multi-Cloud</h3>
            <div style="background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(69, 243, 255, 0.2); border-radius: 8px; padding: 16px 20px; margin-bottom: 22px;">
                <p style="margin: 0 0 10px 0;">
                    O bloco <strong>📄 Visualizador de PDF</strong> suporta tanto armazenamento local quanto as principais nuvens corporativas do mercado:
                </p>
                <ul style="margin: 0 0 0 18px; padding: 0; line-height: 1.8; font-size: 13px;">
                    <li><strong>Google Drive:</strong> Cole o link de compartilhamento. O sistema converte automaticamente para o modo de preview seguro <code>/preview</code>.</li>
                    <li><strong>Microsoft OneDrive &amp; SharePoint:</strong> Suporte nativo com conversão automática para o player embutido corporativo (<code>action=embedview</code>).</li>
                    <li><strong>Dropbox:</strong> Suporte com streaming direto (<code>?raw=1</code>).</li>
                    <li><strong>Upload Local no Servidor (Até 128 MB):</strong> Validação estrita do cabeçalho binário (<em>Magic Bytes <code>%PDF-</code></em>) armazenado de forma segura em <code>storage/academy/lessons_pdf/</code> com bloqueio anti-download e modo Canvas inteligente.</li>
                </ul>
            </div>

            <!-- SEÇÃO 4: COCKPIT DO ALUNO & LGPD -->
            <h3 style="color: #00d284; font-size: 15px; margin: 22px 0 10px;">4. Cockpit do Aluno &amp; Armazenamento Isolado (LGPD)</h3>
            <div style="background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(0, 210, 132, 0.25); border-radius: 8px; padding: 16px 20px; margin-bottom: 22px;">
                <p style="margin: 0 0 10px 0;">
                    Cada aluno matriculado possui um <strong>Cockpit Pessoal</strong> em <a href="<?= htmlspecialchars($baseUrl) ?>/aluno/painel" target="_blank" style="color:#00d284;"><code>/aluno/painel</code></a>:
                </p>
                <ul style="margin: 0 0 0 18px; padding: 0; line-height: 1.8; font-size: 13px;">
                    <li><strong>Barra de Progresso em Tempo Real:</strong> Acompanhamento percentual de conclusão por curso.</li>
                    <li><strong>Botão "Concluir Aula" no Player:</strong> O aluno marca a aula assistida com 1 clique e a plataforma sincroniza automaticamente.</li>
                    <li><strong>Gaveta Lateral "Minhas Anotações":</strong> Durante a aula, o aluno abre a gaveta de anotações privadas. Seus textos são armazenados no diretório isolado <code>storage/students/{id}/</code>, garantindo privacidade e conformidade rigorosa com a <strong>LGPD</strong>.</li>
                </ul>
            </div>

            <!-- SEÇÃO 5: INTEGRAÇÃO COM DOMAINCOMMERCE -->
            <h3 style="color: #f5a623; font-size: 15px; margin: 22px 0 10px;">5. Vendas de Cursos com DomainCommerce</h3>
            <div style="background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(245, 166, 35, 0.25); border-radius: 8px; padding: 16px 20px; margin-bottom: 22px;">
                <p style="margin: 0 0 10px 0;">
                    Ao definir um valor monetário para o curso (ex: <code>R$ 197,00</code>), o sistema ativa a <strong>Guarda de Matrícula</strong>:
                </p>
                <ul style="margin: 0 0 0 18px; padding: 0; line-height: 1.8; font-size: 13px;">
                    <li>Aulas não marcadas como "Prévia Gratuita" exibem o card de bloqueio com o botão <strong>🛒 Matricular-se</strong>.</li>
                    <li>O checkout é processado pelo <strong>DomainCommerce</strong> via <strong>Pix (QR Code instantâneo)</strong>, <strong>Cartão até 12x</strong> e <strong>Boleto Bancário</strong> diretamente pelo <strong>Mercado Pago Oficial</strong>.</li>
                    <li>Assim que o webhook confirma o pagamento, o evento <code>payment.approved</code> é disparado e a matrícula do aluno é ativada no mesmo segundo!</li>
                </ul>
            </div>

            <!-- SEÇÃO 6: SHORTCODE E ROTAS -->
            <h3 style="color: #fff; font-size: 15px; margin: 20px 0 10px;">6. Shortcode e Rotas Públicas</h3>
            <div style="background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; padding: 14px 18px; margin-bottom: 18px; font-size: 13px;">
                <div><strong>• Catálogo Geral de Cursos:</strong> <a href="<?= htmlspecialchars($baseUrl) ?>/cursos" target="_blank" style="color:#45f3ff;"><code><?= htmlspecialchars($baseUrl) ?>/cursos</code></a></div>
                <div style="margin-top: 4px;"><strong>• Login do Aluno:</strong> <a href="<?= htmlspecialchars($baseUrl) ?>/aluno/login" target="_blank" style="color:#45f3ff;"><code><?= htmlspecialchars($baseUrl) ?>/aluno/login</code></a></div>
                <div style="margin-top: 4px;"><strong>• Cadastro do Aluno:</strong> <a href="<?= htmlspecialchars($baseUrl) ?>/aluno/cadastro" target="_blank" style="color:#00d284;"><code><?= htmlspecialchars($baseUrl) ?>/aluno/cadastro</code></a></div>
                <div style="margin-top: 4px;"><strong>• Cockpit do Aluno:</strong> <a href="<?= htmlspecialchars($baseUrl) ?>/aluno/painel" target="_blank" style="color:#00d284;"><code><?= htmlspecialchars($baseUrl) ?>/aluno/painel</code></a></div>
                <div style="margin-top: 4px;"><strong>• Rota Direta da Aula Fabricada:</strong> <code><?= htmlspecialchars($baseUrl) ?>/cursos/{slug-da-aula}</code> (ex: <code>/cursos/estrutura-de-dados</code>)</div>
                <div style="margin-top: 4px;"><strong>• Rota Hierárquica do Curso:</strong> <code><?= htmlspecialchars($baseUrl) ?>/cursos/{slug-do-curso}/{slug-da-aula}</code></div>
                <div style="margin-top: 4px;"><strong>• Shortcode para Páginas do Tema:</strong> Insira <code>[hub_de_cursos]</code> em qualquer página gerenciada pelo módulo Pages.</div>
            </div>

            <!-- DESTAQUE ARQUITETURAL SOLID -->
            <div style="background: rgba(168, 85, 247, 0.08); border-left: 4px solid #a855f7; padding: 12px 16px; margin: 16px 0 0; border-radius: 0 6px 6px 0; font-size: 13px;">
                <strong>Blindagem SOLID &amp; Contratos (Ring 3):</strong>
                O Academy opera 100% desacoplado através dos contratos <code>CourseRepositoryInterface</code>, <code>CourseStructureRepositoryInterface</code>, <code>CourseEnrollmentInterface</code>, <code>StudentProgressInterface</code>, <code>StudentStorageInterface</code>, <code>LessonBlockRendererInterface</code>, <code>ThemePageResolverInterface</code>, <code>LessonFileScaffolderInterface</code> e <code>ManualModuleInterface</code>.
            </div>
        </div>
        <?php
        return (string)ob_get_clean();
    }
}
