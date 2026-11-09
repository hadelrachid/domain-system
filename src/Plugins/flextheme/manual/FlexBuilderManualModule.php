<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

/**
 * Manual Oficial, Tutorial Passo a Passo e Guia Interativo de Homologação (QA) do Flex Builder (v2.6.0).
 * Descoberto automaticamente pelo sistema de Manuais do Domain-System OS (/admin/manual e /manual).
 */
class FlexBuilderManualModule implements ManualModuleInterface
{
    public function getId(): string
    {
        return 'flextheme';
    }

    public function getTitle(): string
    {
        return 'Flex Builder (Motor Visual FSE, 14 Blocos, Grid Auto-Reflow & Design Tokens)';
    }

    public function getSubtitle(): string
    {
        return 'Tutorial passo a passo e manual completo de criação de Headers, Footers e Seções em grade responsiva de 4 resoluções com reajuste automático (Auto-Reflow), Micro-UI 375px, 8 estilos mobile e contratos SOLID.';
    }

    public function getBadge(): string
    {
        return 'Flex Builder v2.6.0 • Full Site Editing';
    }

    public function getAccentColor(): string
    {
        return '#38bdf8';
    }

    public function getAdminUrl(): ?string
    {
        return '/admin/builder';
    }

    public function getChecklistItems(): array
    {
        return [
            [
                'id'    => 'flexbuilder-access',
                'label' => '1. Acessar /admin/builder e conferir os painéis de Cabeçalhos (Headers), Rodapés (Footers) e Seções Centrais com badges de alto contraste.',
            ],
            [
                'id'    => 'flexbuilder-create-header',
                'label' => '2. Criar um Header Pro de 3 Células (25% / 50% / 25%) adicionando Logotipo na Coluna 1, Menu de Navegação na Coluna 2 e Autenticação do Usuário/CTA na Coluna 3.',
            ],
            [
                'id'    => 'flexbuilder-mobile-toggle',
                'label' => '3. Na aba "CONFIG" do Header, testar os 8 estilos de botão hamburguer mobile (classic ☰, pill MENU ☰, minimal ✕, dots ⋮, circle ○☰, badge 🏷☰, square □☰, text MENU) e o botão "👁️ Pré-visualizar Gaveta".',
            ],
            [
                'id'    => 'flexbuilder-mobile-drawer-absorption',
                'label' => '4. No Mobile, comprovar que os blocos de Botão CTA e Autenticação do Usuário (user_auth) são absorvidos de forma inteligente dentro da gaveta lateral deslizante (📱), mantendo o cabeçalho limpo.',
            ],
            [
                'id'    => 'flexbuilder-block-guardrails',
                'label' => '5. Testar o Guardrail Arquitetural: tentar adicionar um bloco de Vídeo no Header ou Footer e verificar o Toast vermelho de bloqueio ("🔒 O bloco Vídeo não é permitido no Header/Footer").',
            ],
            [
                'id'    => 'flexbuilder-video-multi-embed',
                'label' => '6. Criar uma Seção Central / Hero com o bloco Vídeo: testar URLs do YouTube convencional, YouTube Shorts, YouTube Live, Vimeo e upload direto de arquivo MP4 (até 50MB) com preview instantâneo.',
            ],
            [
                'id'    => 'flexbuilder-create-footer',
                'label' => '7. Criar um Footer profissional com 4 colunas contendo Logotipo, Links de Navegação, Redes Sociais (SVG Registry) e Copyright dinâmico com tag {year}.',
            ],
            [
                'id'    => 'flexbuilder-device-simulator',
                'label' => '8. No topo do Studio, alternar Ao Vivo entre as 4 resoluções: Desktop (100%), Laptop (1024px), Tablet (768px) e Mobile (375px).',
            ],
            [
                'id'    => 'flexbuilder-micro-ui-375',
                'label' => '9. No modo 375px, conferir a Micro-UI: seções compactadas com badge "3 col" e botão "👁️" com toast explicativo, ações abreviadas (⚙️) e cards de blocos em formato de 1 linha enxuta sem redundâncias.',
            ],
            [
                'id'    => 'flexbuilder-element-visibility-reflow',
                'label' => '10. Selecionar um elemento e marcar "Ocultar em Laptop (1024px)" ou "Ocultar no Tablet (768px)": comprovar que apenas o elemento some e as colunas restantes recalculam o CSS Grid automaticamente (Auto-Reflow).',
            ],
            [
                'id'    => 'flexbuilder-dimension-units',
                'label' => '11. No Inspetor de Propriedades (aba ESTILO), personalizar tipografia, unidades flexíveis (px, %, em, rem, vw, vh), espaçamentos, cores e bordas por elemento.',
            ],
            [
                'id'    => 'flexbuilder-design-tokens',
                'label' => '12. Acessar /admin/builder/tokens, personalizar a paleta mestre e tipografia global, validando a sincronização com as variáveis CSS (--color-*, --font-*).',
            ],
            [
                'id'    => 'flexbuilder-template-shortcode',
                'label' => '13. Vincular uma Seção Central ao template home.php (ou inserir via shortcode [section id="..."]) e validar na vitrine pública.',
            ],
            [
                'id'    => 'flexbuilder-fallback-test',
                'label' => '14. Desvincular o Header/Footer customizado e comprovar o acionamento automático do fallback nativo do tema (partials/header.php e partials/footer.php).',
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
                O <strong>Flex Builder (v2.6.0)</strong> é o motor oficial de composição visual <em>Full Site Editing (FSE)</em> do Domain-System OS. Projetado estritamente sobre <strong>Princípios SOLID (Ring 0 e Ring 3)</strong>, ele fornece um <strong>Compilador Único (`ThemePartCompilerInterface`)</strong> compartilhado entre o Studio e a produção, <strong>Validação Estrutural Completa (`ThemePartValidatorInterface`)</strong>, <strong>Guardrails de Performance</strong>, <strong>Visibilidade Responsiva Isolada por Elemento nas 4 Resoluções</strong>, <strong>Micro-UI Otimizada a 375px</strong> e <strong>Reajuste Automático do CSS Grid (Auto-Reflow)</strong>.
            </p>

            <!-- PILARES DO MOTOR -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; margin: 20px 0 25px;">
                <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #38bdf8; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📐 1. CSS Grid com Auto-Reflow</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Quando elementos de uma coluna são ocultados, a coluna colapsa e o <code>grid-template-columns</code> recalcula e redistribui o espaço automaticamente para as colunas visíveis.
                    </div>
                </div>

                <div style="background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #00d284; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📱 2. Micro-UI a 375px & 8 Toggles</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Visualização ultra-compacta para telas móveis, 8 designs de botão hamburguer, preview da gaveta em tempo real e absorção do CTA/Login no drawer mobile.
                    </div>
                </div>

                <div style="background: rgba(234, 179, 8, 0.08); border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #facc15; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🛡️ 3. Guardrails & Performance</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Blocos pesados como Vídeo são restritos a Seções Centrais/Hero. Tentativas de inserção no Header ou Footer disparam aviso sonoro e toast vermelho de bloqueio.
                    </div>
                </div>

                <div style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #c084fc; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🎨 4. Design Tokens & Compilador Único</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Tipografia e cores mestres sincronizadas com variáveis CSS nativas (<code>--color-*</code>), higienização anti-CSS Injection e renderização idêntica no Studio e Frontend.
                    </div>
                </div>
            </div>

            <!-- ROTEIRO 1: COMO CRIAR UM HEADER PROFISSIONAL PASSO A PASSO -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🚀 Roteiro Passo a Passo 1: Como Construir um Header Profissional do Zero
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <ol style="margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 14px; font-size: 13.5px;">
                    <li>
                        <strong>Passo 1 — Acessar o Gerenciador de Partes Visuais:</strong><br>
                        Acesse no menu lateral <code>Temas &gt; Flex Builder</code> (<code><?= $baseUrl ?>/admin/builder</code>). O painel organiza suas partes em três grupos: <em>Cabeçalhos (Headers)</em>, <em>Rodapés (Footers)</em> e <em>Seções &amp; Layouts Centrais</em>.
                    </li>
                    <li>
                        <strong>Passo 2 — Iniciar um Novo Header com Preset Pro de 3 Colunas:</strong><br>
                        Clique em <code>+ Criar Nova Parte &gt; Novo Header</code>. No diálogo inicial, defina um nome (ex: <em>Header Principal do Site</em>) e selecione o preset recomendado: <strong>3 Células (3 Colunas Header Pro: 25% / 50% / 25%)</strong>.
                    </li>
                    <li>
                        <strong>Passo 3 — Montar a Estrutura Semântica das Células:</strong><br>
                        No palco ou na árvore lateral, adicione os blocos essenciais:
                        <ul style="margin: 6px 0; padding-left: 20px; font-size: 13px;">
                            <li><strong>Coluna 1 (Esquerda - 25%):</strong> Insira o bloco <code>🏷️ Logotipo / Marca</code>. Você pode escolher exibir imagem gráfica, texto estilizado ou ambos.</li>
                            <li><strong>Coluna 2 (Centro - 50%):</strong> Insira o bloco <code>🧭 Menu de Navegação</code>. Ele puxa automaticamente a lista de páginas do menu cadastrado no sistema.</li>
                            <li><strong>Coluna 3 (Direita - 25%):</strong> Insira o bloco <code>👤 Autenticação do Usuário</code> (exibe "Entrar / Cadastrar-se" para visitantes e "Minha Conta / Painel do Aluno" para logados) ou um <code>🚀 Botão CTA</code>.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Passo 4 — Personalizar o Toggle Mobile e a Gaveta Lateral:</strong><br>
                        Abra a aba <code>⚙️ CONFIG</code> do Header no painel lateral direito:
                        <ul style="margin: 6px 0; padding-left: 20px; font-size: 13px;">
                            <li><strong>Estilo do Botão Toggle:</strong> Escolha entre os 8 estilos disponíveis: <code>classic (☰)</code>, <code>pill (MENU ☰)</code>, <code>minimal (✕)</code>, <code>dots (⋮)</code>, <code>circle (○☰)</code>, <code>badge (🏷 ☰)</code>, <code>square (□☰)</code> ou <code>text (MENU)</code>.</li>
                            <li><strong>Alinhamento:</strong> Escolha entre Esquerda, Centro ou Direita.</li>
                            <li><strong>Pré-visualização Instantânea:</strong> Clique no botão <code>👁️ Pré-visualizar Gaveta</code> para abrir a sobreposição mobile sem sair da tela de edição.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Passo 5 — Testar no Simulador de 375px (Micro-UI &amp; Absorção):</strong><br>
                        No topo do Studio, clique no botão <code>📱 375px</code>. Veja como a barra superior da seção colapsa elegantemente para um badge compacto <code>3 col</code> com botão <code>👁️</code> (clique para ver detalhes da proporção das colunas via toast), as divisórias se abreviam para <code>⚙️</code>, os blocos viram cartões de 1 linha enxuta, e os botões de CTA e Login são absorvidos de forma inteligente dentro da gaveta lateral deslizante.
                    </li>
                    <li>
                        <strong>Passo 6 — Salvar e Ativar como Header Ativo:</strong><br>
                        Clique em <strong>Salvar Alterações</strong> (ou pressione <code>Ctrl+S</code>). Para torná-lo o cabeçalho padrão de todo o site, basta marcar o checkbox <em>"Tornar Header Ativo"</em> na aba CONFIG.
                    </li>
                </ol>
            </div>

            <!-- ROTEIRO 2: SEÇÕES CENTRAIS, VÍDEO HÍBRIDO E GUARDRAILS -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🎬 Roteiro Passo a Passo 2: Seções Centrais, Vídeo Híbrido (50MB) e Guardrails
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <ol style="margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 14px; font-size: 13.5px;">
                    <li>
                        <strong>Passo 1 — Criar uma Nova Seção de Conteúdo / Hero:</strong><br>
                        Em <code>/admin/builder</code>, clique em <code>+ Criar Nova Parte &gt; Nova Seção</code>. Escolha um layout de 1 Célula (Hero) ou 2 Células (Texto + Mídia).
                    </li>
                    <li>
                        <strong>Passo 2 — O Bloco de Vídeo Multi-Formato:</strong><br>
                        Adicione o bloco <code>🎥 Vídeo</code> na célula desejada. O Inspetor lateral oferece suporte completo e inteligente a múltiplos formatos:
                        <ul style="margin: 6px 0; padding-left: 20px; font-size: 13px;">
                            <li><strong>YouTube Padrão:</strong> <code>https://www.youtube.com/watch?v=VIDEO_ID</code> ou <code>https://youtu.be/VIDEO_ID</code>.</li>
                            <li><strong>YouTube Shorts:</strong> <code>https://www.youtube.com/shorts/VIDEO_ID</code> (detectado e convertido para player 16:9 automaticamente).</li>
                            <li><strong>YouTube Live / Transmissão:</strong> <code>https://www.youtube.com/live/VIDEO_ID</code>.</li>
                            <li><strong>Vimeo:</strong> <code>https://vimeo.com/VIDEO_ID</code>.</li>
                            <li><strong>Upload Direto de MP4 (Até 50 MB):</strong> Use o botão de envio local para carregar seu arquivo <code>.mp4</code> diretamente para o servidor com validação de tipo de mídia e barra de progresso em tempo real.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Passo 3 — Compreender o Guardrail de Performance (Bloqueio em Header/Footer):</strong><br>
                        O bloco de vídeo implementa estritamente o contrato <code>getAllowedPartTypes() = ['section']</code>.
                        <div style="background: rgba(239, 68, 68, 0.08); border-left: 3px solid #ef4444; padding: 10px 14px; border-radius: 4px; font-size: 12.5px; margin-top: 8px;">
                            <strong>🔒 Por que o Vídeo é proibido no Header/Footer?</strong><br>
                            Cabeçalhos e rodapés são renderizados criticamente em 100% das páginas do site. Embutir players pesados de vídeo ou iframes no cabeçalho atrasa a métrica <em>First Contentful Paint (FCP)</em>, prejudica a pontuação do Google PageSpeed e quebra a experiência do usuário móvel. Se alguém tentar arrastar ou adicionar um Vídeo em um Header ou Footer, o Studio bloqueia a operação imediatamente emitindo um Toast de erro: <em>"🔒 O bloco 'Vídeo' não é permitido no Header/Footer..."</em>.
                        </div>
                    </li>
                    <li>
                        <strong>Passo 4 — Vincular ao Tema ou Usar como Shortcode:</strong><br>
                        Na aba <code>CONFIG</code> da Seção Central, você pode associá-la diretamente a um arquivo de template do tema (como <code>home.php</code>) ou copiar o shortcode <code>[section id="..."]</code> e colá-lo em qualquer página editável do CMS.
                    </li>
                </ol>
            </div>

            <!-- ROTEIRO 3: VISIBILIDADE RESPONSIVA E AUTO-REFLOW -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                📱 Visibilidade Responsiva por Elemento & Reajuste Automático do Grid (Auto-Reflow)
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <p style="margin-top: 0;">
                    No Studio do Flex Builder, você tem o poder de controlar a visibilidade de <strong>cada elemento individualmente</strong> sem comprometer a estrutura do restante da página:
                </p>
                <ul style="margin: 10px 0; padding-left: 20px; display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                    <li><strong>💻 Desktop (100% / &ge; 1025px):</strong> Classe <code>.ds-hide-desktop</code> aplicada exclusivamente ao wrapper do elemento (<code>.ds-element-wrapper</code>).</li>
                    <li><strong>💻 Laptop (1024px / 769px a 1024px):</strong> Classe <code>.ds-hide-laptop</code> sincronizada com a prévia do botão <code>1024px</code>.</li>
                    <li><strong>📱 Tablet (768px / 481px a 768px):</strong> Classe <code>.ds-hide-tablet</code> sincronizada com a prévia do botão <code>768px</code>.</li>
                    <li><strong>📱 Mobile (375px / &le; 480px):</strong> Classe <code>.ds-hide-mobile</code> sincronizada com a prévia do botão <code>375px</code>.</li>
                </ul>
                <div style="background: rgba(56, 189, 248, 0.08); border-left: 3px solid #38bdf8; padding: 10px 14px; border-radius: 4px; font-size: 12.5px; margin-top: 12px;">
                    <strong>🔄 Como funciona o Auto-Reflow do Grid:</strong><br>
                    Quando todos os elementos dentro de uma coluna são configurados como ocultos para determinada resolução, o motor <code>LayoutGridRenderer</code> marca a coluna vazia e reconfigura instantaneamente o <code>grid-template-columns</code>. Exemplo: um layout de 3 colunas <code>1fr 2fr 1fr</code> onde o elemento da primeira coluna foi ocultado se reorganiza automaticamente para <code>2fr 1fr</code>; caso reste apenas 1 coluna visível, ela assume <code>1fr</code> ocupando 100% do container sem deixar vazios indesejados.
                </div>
            </div>

            <!-- SEÇÃO: CATÁLOGO DOS 14 BLOCOS SEMÂNTICOS -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🧩 Catálogo Completo dos 14 Blocos Semânticos Nativos
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-bottom: 25px;">
                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🔤 1. Título (<code>heading</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Níveis H1 a H6, tipografia sincronizada com Design Tokens, peso, alinhamento e espaçamento de letras.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">📄 2. Parágrafo (<code>paragraph</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Texto corrido com formatação e sanitização rígida via <code>SafeHtmlSanitizer</code> com proteção XSS.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🖼️ 3. Imagem (<code>image</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Upload seguro de PNG, JPG, WEBP, GIF e SVG validado via DOMDocument, alt text, link e bordas.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #38bdf8; margin-bottom: 4px;">🎥 4. Vídeo Híbrido (<code>video</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Player 16:9 para YouTube (padrão, Shorts e Live), Vimeo e upload local de MP4 até 50MB. <em>Exclusivo de Seções Centrais</em>.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🧭 5. Menu de Navegação (<code>nav_menu</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Conecta ao <code>MenuProviderInterface</code> com gaveta lateral mobile e 8 estilos visuais de botão toggle.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🛡️ 6. Logotipo / Marca (<code>logo</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Imagem gráfica da marca e/ou texto estilizado com controle de dimensão e link automático para a Home.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🔘 7. Botão Estilizado (<code>button</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Botão versátil com ícone SVG opcional e 5 efeitos de hover: <code>grow</code>, <code>lift</code>, <code>glow</code>, <code>bounce</code> e <code>shimmer</code>.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🚀 8. Botão CTA (<code>cta_button</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Call-to-Action com alto destaque para Headers e Seções Hero. No mobile, é absorvido dentro da gaveta.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #00d284; margin-bottom: 4px;">👤 9. Autenticação do Usuário (<code>user_auth</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Alterna dinamicamente entre "Entrar / Cadastro" e "Minha Conta / Cockpit do Aluno". Integração nativa com o LMS.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🗂️ 10. Grade de Cards / Posts (<code>post_cards_grid</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Grade de cartões responsivos com imagem, badge, título, resumo e botão. <em>Exclusivo para Seções Centrais</em>.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">⚡ 11. Shortcode Dinâmico (<code>shortcode</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Executa qualquer shortcode registrado no sistema (ex: <code>[hub_de_cursos]</code>, <code>[grade_de_cursos]</code>).</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">➖ 12. Divisória / Espaçador (<code>divider</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Linha divisória estilizada (sólida, tracejada, pontilhada) ou espaçamento invisível com controle de altura.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🌐 13. Redes Sociais (<code>social_icons</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Ícones vetoriais via <code>SvgIconRegistryInterface</code> (GitHub, LinkedIn, Instagram, X, WhatsApp, YouTube).</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">© 14. Copyright &amp; Links Legais (<code>copyright</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Substituição dinâmica de <code>{year}</code> e <code>{site_name}</code> com links para Termos e Privacidade.</div>
                </div>
            </div>

            <!-- SEÇÃO: CONTRATOS SOLID -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🏛️ Contratos SOLID do Motor Visual (`src/Core/Theme/Parts`)
            </h3>

            <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; margin-bottom: 25px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border, #30363d); text-align: left;">
                        <th style="padding: 8px 12px; color: #fff;">Contrato (Interface)</th>
                        <th style="padding: 8px 12px; color: #fff;">Responsabilidade no Ecossistema</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><code>ThemePartCompilerInterface</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Caminho único de compilação compartilhado estritamente entre o Studio e o Frontend publicado.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><code>ThemePartValidatorInterface</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Validação de estrutura JSON, tipagem de partes, presets e verificação de compatibilidade de blocos.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><code>LayoutGridInterface</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Renderiza a malha CSS Grid e aciona o Auto-Reflow das colunas visíveis em cada uma das 4 resoluções.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><code>ElementStyleRendererInterface</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Higieniza propriedades CSS inline, valida unidades e emite classes responsivas <code>.ds-hide-*</code>.</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px;"><code>BlockProviderInterface</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Permite que qualquer plugin externo adicione novos blocos no <code>BlockRegistryInterface</code> sem tocar no Core (OCP).</td>
                    </tr>
                </tbody>
            </table>

            <!-- BOTÃO DIRETO PARA O BUILDER -->
            <div style="margin-top: 30px; text-align: center;">
                <a href="<?= $baseUrl ?>/admin/builder" class="btn button-primary" style="background: #2563eb; color: #fff; padding: 12px 28px; border-radius: 6px; font-weight: 700; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                    🏗️ Abrir o Flex Builder Agora
                </a>
            </div>

        </div>
        <?php
        return (string)ob_get_clean();
    }
}
