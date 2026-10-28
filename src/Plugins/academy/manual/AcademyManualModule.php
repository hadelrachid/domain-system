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
        return 'Guia completo de criação de cursos, fábrica automática de pastas e arquivos PHP, editor em blocos e simulador mobile/tablet.';
    }

    public function getBadge(): string
    {
        return 'Módulo Auto-Descoberto • Academy v2.5.0';
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
                'id'    => 'academy-device-simulator',
                'label' => '6. Alternar no Simulador entre Desktop e os 5 tamanhos Mobile/Tablet (320px, 375px, 430px, 768px, 1024px) e testar os 3 Perfis de Layout Mobile (Gaveta, Barra Fixa e Modo Cinema 16:9).',
            ],
            [
                'id'    => 'academy-public-classroom',
                'label' => '7. Acessar a sala de aula pública (/cursos/{curso}/{aula} ou rota direta /cursos/estrutura-de-dados) e o catálogo [hub_de_cursos].',
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
                O <strong>Plugin Academy (v2.5.0)</strong> é o motor de ensino (<strong>LMS Híbrido</strong>) do Domain-System OS. Ele foi projetado sob os princípios <strong>SOLID</strong> e <strong>Inversão de Dependência (DIP)</strong> para permitir que você crie cursos completos de quatro maneiras integradas: digitando diretamente no <strong>Editor Visual em Blocos</strong>, fabricando automaticamente <strong>arquivos <code>.php</code> responsivos</strong> no tema ativo, vinculando páginas existentes ou importando pastas inteiras com 1 clique.
            </p>

            <!-- SEÇÃO 1: OS 4 MODOS DE CRIAÇÃO -->
            <h3 style="color: #45f3ff; font-size: 15px; margin: 22px 0 12px;">1. Os 4 Modos de Construção de Aulas</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 24px;">
                <div style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #c084fc; font-weight: 800; font-size: 13px; margin-bottom: 6px;">⚡ 1. Editor Visual em Blocos</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Construa a aula visualmente com <strong>8 tipos de blocos nativos</strong>: <em>Título (H2/H3/H4)</em>, <em>Parágrafo rico</em>, <em>Vídeo 16:9 (YouTube/Vimeo/MP4)</em>, <em>Código com destaque</em>, <em>Callout (Info/Sucesso/Aviso/Erro)</em>, <em>Lista</em>, <em>Imagem</em> e <em>Divisor</em>.
                    </div>
                </div>

                <div style="background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #00d284; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🏭 2. Fábrica Automática de Arquivos PHP</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12.5px; line-height: 1.55;">
                        Não quer criar arquivos na mão? Basta digitar o título da aula (ex: <strong>"Estrutura de Dados"</strong>) e salvar! O contrato <code>LessonFileScaffolderInterface</code> cria automaticamente <code>pages/estrutura-de-dados.php</code> já com CSS/JS Desktop &amp; Mobile e libera a URL <code>/cursos/estrutura-de-dados</code>.
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
            <h3 style="color: #fff; font-size: 15px; margin: 22px 0 10px;">2. Guia Passo a Passo: Do Zero até a Aula Publicada</h3>
            <div style="background: rgba(0, 0, 0, 0.25); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 8px; padding: 16px 20px; margin-bottom: 22px;">
                <ol style="margin: 0 0 0 18px; padding: 0; line-height: 1.9; font-size: 13.5px;">
                    <li style="margin-bottom: 8px;">
                        <strong>Passo 1 — Cadastrar o Curso (<code>/admin/academy</code>):</strong>
                        No painel esquerdo lateral (ou modal), preencha o <em>Título do Curso</em>, escolha o status <strong>Publicado</strong>, adicione uma descrição resumida e envie uma imagem de capa em proporção <strong>16:9</strong> (mínimo <code>640&times;360 px</code>). Clique em <strong>Salvar Curso</strong> e depois em <strong>Editar</strong>.
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>Passo 2 — Criar uma Pasta/Módulo Físico (<code>📁 Criar Pasta (ou Módulo)</code>):</strong>
                        Dentro da tela de edição do curso, clique no botão verde <strong>"📁 Criar Pasta (ou Módulo)"</strong>. Se você digitar <code>Módulo 01 - Fundamentos</code>, o sistema criará automaticamente a pasta física <code>pages/modulo-01-fundamentos/</code> no tema ativo e adicionará o módulo na árvore do curso. (Se preferir que os arquivos fiquem direto na raiz <code>pages/</code>, basta deixar o campo de subpasta do módulo em branco).
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>Passo 3 — Criar a Aula e Fabricar o Arquivo <code>.php</code> Automaticamente:</strong>
                        Clique em <strong>"+ Adicionar Aula"</strong> dentro do módulo e digite o título, por exemplo: <strong><code>Estrutura de Dados</code></strong>.
                        <ul style="margin: 4px 0 6px 18px; color: #cbd5e1; font-size: 13px;">
                            <li>O slug será preenchido automaticamente como <code>estrutura-de-dados</code>.</li>
                            <li>Com a caixa <strong>"🏭 Fábrica Automática de Arquivos PHP"</strong> marcada no rodapé, ao clicar em <strong>"💾 Salvar Estrutura Completa"</strong>, o sistema fabrica o arquivo físico <code>pages/estrutura-de-dados.php</code> (ou <code>pages/{pasta}/estrutura-de-dados.php</code>) pré-configurado para Desktop e Mobile!</li>
                            <li>A aula passa a responder tanto em <code><?= htmlspecialchars($baseUrl) ?>/cursos/{slug-do-curso}/estrutura-de-dados</code> quanto na rota direta amigável <code><?= htmlspecialchars($baseUrl) ?>/cursos/estrutura-de-dados</code>.</li>
                        </ul>
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>Passo 4 — Editar os Blocos da Aula (<code>⚡ Editor de Blocos</code>):</strong>
                        Clique no botão <strong>"⚡ Editor de Blocos"</strong> na linha da aula. Você entrará no estúdio visual:
                        <ul style="margin: 4px 0 6px 18px; color: #cbd5e1; font-size: 13px;">
                            <li>Use a barra de ferramentas inferior para inserir blocos de <strong>Título</strong>, <strong>Parágrafo</strong>, <strong>Vídeo 16:9</strong>, <strong>Código</strong>, <strong>Alerta (Callout)</strong>, <strong>Lista</strong>, <strong>Imagem</strong> ou <strong>Divisor</strong>.</li>
                            <li>Marque a opção <strong>"🏭 Fabricar .php"</strong> na barra superior se quiser que qualquer alteração feita nos blocos também atualize instantaneamente o arquivo <code>.php</code> físico do tema.</li>
                            <li>Pressione <code>Ctrl + S</code> (ou clique em <strong>Salvar Aula</strong>) para gravar via AJAX sem recarregar a página.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Passo 5 — Testar no Simulador Mobile/Tablet e Escolher Perfis de Layout:</strong>
                        No painel de Preview ao vivo à direita, alterne entre <strong>🖥️ Desktop</strong> e os <strong>5 dispositivos Mobile/Tablet</strong> (<code>320&times;568</code>, <code>375&times;667</code>, <code>430&times;932</code>, <code>768&times;1024</code> e <code>1024&times;768</code>) e escolha entre os <strong>3 Perfis Mobile</strong>:
                        <ul style="margin: 4px 0 0 18px; color: #cbd5e1; font-size: 13px;">
                            <li><strong>📱 Gaveta Deslizante (<code>drawer</code>):</strong> Menu lateral recolhido em um botão flutuante que abre uma gaveta suave (Off-Canvas).</li>
                            <li><strong>📲 Barra Inferior Fixa (<code>bottom_bar</code>):</strong> Barra estilo aplicativo nativo fixa no rodapé do celular com botões <em>Anterior</em>, <em>Módulos</em> e <em>Próxima</em>.</li>
                            <li><strong>🎬 Modo Cinema 16:9 (<code>cinema</code>):</strong> O player de vídeo 16:9 permanece fixo (<em>sticky</em>) no topo da tela do celular enquanto o aluno rola o texto e os códigos da aula!</li>
                        </ul>
                    </li>
                </ol>
            </div>

            <!-- SEÇÃO 3: SHORTCODE E ROTAS -->
            <h3 style="color: #00d284; font-size: 15px; margin: 20px 0 10px;">3. Shortcode e Rotas Públicas</h3>
            <div style="background: rgba(0, 210, 132, 0.06); border: 1px solid rgba(0, 210, 132, 0.25); border-radius: 8px; padding: 14px 18px; margin-bottom: 18px; font-size: 13px;">
                <div><strong>• Catálogo Completo:</strong> <a href="<?= htmlspecialchars($baseUrl) ?>/cursos" target="_blank" style="color:#45f3ff;"><code><?= htmlspecialchars($baseUrl) ?>/cursos</code></a></div>
                <div style="margin-top: 4px;"><strong>• Rota Direta da Aula Fabricada:</strong> <code><?= htmlspecialchars($baseUrl) ?>/cursos/{slug-da-aula}</code> (ex: <code>/cursos/estrutura-de-dados</code>)</div>
                <div style="margin-top: 4px;"><strong>• Rota Hierárquica do Curso:</strong> <code><?= htmlspecialchars($baseUrl) ?>/cursos/{slug-do-curso}/{slug-da-aula}</code></div>
                <div style="margin-top: 4px;"><strong>• Shortcode para Páginas do Tema:</strong> Insira <code>[hub_de_cursos]</code> (ou <code>[hub_de_cursos status="published" limit="6"]</code>) em qualquer página gerenciada pelo módulo Pages.</div>
            </div>

            <!-- DESTAQUE ARQUITETURAL SOLID -->
            <div style="background: rgba(168, 85, 247, 0.08); border-left: 4px solid #a855f7; padding: 12px 16px; margin: 16px 0 0; border-radius: 0 6px 6px 0; font-size: 13px;">
                <strong>Blindagem SOLID &amp; Contratos (Ring 3):</strong>
                O Academy opera 100% desacoplado através dos contratos <code>CourseRepositoryInterface</code>, <code>CourseStructureRepositoryInterface</code>, <code>LessonBlockRendererInterface</code>, <code>ThemePageResolverInterface</code>, <code>LessonFileScaffolderInterface</code> e <code>ManualModuleInterface</code>.
            </div>
        </div>
        <?php
        return (string)ob_get_clean();
    }
}
