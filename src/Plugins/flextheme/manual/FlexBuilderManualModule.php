<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

/**
 * Manual Oficial e Guia Interativo de Homologação (QA) do Flex Builder (v2.5.0).
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
        return 'Guia completo de composição de Headers, Footers e Seções em grade responsiva de 4 resoluções com reajuste automático de dimensões, 14 blocos semânticos e contratos SOLID.';
    }

    public function getBadge(): string
    {
        return 'Flex Builder v2.5.0 • Full Site Editing';
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
                'label' => '1. Acessar /admin/builder e conferir os painéis de Cabeçalhos (Headers), Rodapés (Footers) e Seções & Layouts Centrais com badges de alto contraste.',
            ],
            [
                'id'    => 'flexbuilder-create-header',
                'label' => '2. Clicar em "+ Criar Nova Parte > Novo Header", selecionar o preset de 3 colunas (3_cols_header) e adicionar Logotipo, Menu de Navegação e Botão CTA.',
            ],
            [
                'id'    => 'flexbuilder-mobile-toggle',
                'label' => '3. Na aba "CONFIG" do Header, testar os 4 estilos de botão hamburguer mobile (classic ☰, pill MENU ☰, minimal ✕ e dots ⋮) e os 3 alinhamentos (Esquerda, Centro, Direita).',
            ],
            [
                'id'    => 'flexbuilder-context-isolation',
                'label' => '4. Abrir uma Seção Central e confirmar que a aba "CONFIG" exibe apenas "Vincular ao Template do Tema" (sem controles de hamburguer mobile do Header).',
            ],
            [
                'id'    => 'flexbuilder-create-footer',
                'label' => '5. Criar um Footer profissional com 4 colunas (4_cols_footer) contendo Logotipo, Links, Redes Sociais (SVG Registry) e Copyright dinâmico com {year}.',
            ],
            [
                'id'    => 'flexbuilder-device-simulator',
                'label' => '6. No topo do Studio, alternar entre as 4 resoluções Ao Vivo: Desktop (100%), Laptop (1024px), Tablet (768px) e Mobile (375px).',
            ],
            [
                'id'    => 'flexbuilder-element-visibility-reflow',
                'label' => '7. Selecionar um elemento (ex: Logotipo na Coluna 1) e marcar "Ocultar em Laptop (1024px)" ou "Ocultar no Tablet (768px)": comprovar que apenas o elemento some (nunca a seção inteira) e que as colunas restantes reajustam suas dimensões automaticamente.',
            ],
            [
                'id'    => 'flexbuilder-14-blocks-restriction',
                'label' => '8. Testar os 14 blocos nativos (incluindo Botão com animações Hover e Grade de Cards/Posts restrita exclusivamente a Seções Centrais).',
            ],
            [
                'id'    => 'flexbuilder-dimension-units',
                'label' => '9. No Inspetor de Propriedades (aba ESTILO), testar unidades flexíveis (px, %, em, rem, vw, vh), espaçamentos, cores e bordas por elemento.',
            ],
            [
                'id'    => 'flexbuilder-design-tokens',
                'label' => '10. Acessar /admin/builder/tokens, personalizar a paleta de cores e tipografia global, e validar as variáveis CSS (--color-*, --font-*).',
            ],
            [
                'id'    => 'flexbuilder-template-shortcode',
                'label' => '11. Vincular uma Seção Central diretamente ao template home.php (ou inserir via shortcode [section id="..."]) e validar na vitrine pública.',
            ],
            [
                'id'    => 'flexbuilder-fallback-test',
                'label' => '12. Desvincular o Header/Footer customizado e comprovar o acionamento automático do fallback nativo do tema (partials/header.php e partials/footer.php).',
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
                O <strong>Flex Builder (v2.5.0)</strong> é o motor oficial de composição visual <em>Full Site Editing (FSE)</em> do Domain-System OS. Construído rigorosamente sobre <strong>Contratos SOLID (Ring 0 e Ring 3)</strong>, ele une um <strong>Compilador Único (`ThemePartCompilerInterface`)</strong> entre Preview e Publicação, <strong>Validação Estrutural Completa (`ThemePartValidatorInterface`)</strong>, <strong>Visibilidade Responsiva Isolada por Elemento nas 4 Resoluções</strong> e <strong>Reajuste Automático de Dimensões do CSS Grid (Auto-Reflow)</strong>.
            </p>

            <!-- PILARES DO MOTOR -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; margin: 20px 0 25px;">
                <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #38bdf8; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📐 1. CSS Grid com Auto-Reflow</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Quando todos os elementos de uma coluna são ocultados em uma resolução, a coluna colapsa e o <code>grid-template-columns</code> redistribui o espaço automaticamente para as colunas visíveis restantes.
                    </div>
                </div>

                <div style="background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #00d284; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📱 2. Visibilidade por Elemento (4 Telas)</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Oculte elementos individualmente em <strong>Desktop (100%)</strong>, <strong>Laptop (1024px)</strong>, <strong>Tablet (768px)</strong> e <strong>Mobile (375px)</strong> sem jamais sumir com o container da seção inteira.
                    </div>
                </div>

                <div style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #c084fc; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🎨 3. Design Tokens & Inspetor</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Paleta mestre, tipografia global e inspetor de estilos por elemento (<code>ElementStyleRendererInterface</code>) com proteção Anti-CSS Injection.
                    </div>
                </div>

                <div style="background: rgba(234, 179, 8, 0.08); border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #facc15; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🛡️ 4. Compilador Único & Validador</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Preview e produção usam o mesmo compilador (<code>ThemePartCompilerInterface</code>), validado por <code>ThemePartValidatorInterface</code> com compensação de associações.
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 1: VISIBILIDADE RESPONSIVA E AUTO-REFLOW -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                📱 Visibilidade Responsiva por Elemento & Reajuste Automático de Dimensões (4 Resoluções)
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <p style="margin-top: 0;">
                    No Studio do Flex Builder, você controla a visibilidade de <strong>cada elemento individualmente</strong> (ex: ocultar apenas o Logotipo ou apenas o Botão CTA em uma resolução específica, mantendo o título e o botão toggle visíveis):
                </p>
                <ul style="margin: 10px 0; padding-left: 20px; display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                    <li><strong>💻 Desktop (100% / &ge; 1025px):</strong> Classe <code>.ds-hide-desktop</code> aplicada exclusivamente ao wrapper do elemento (<code>.ds-element-wrapper</code>).</li>
                    <li><strong>💻 Laptop (1024px / 769px a 1024px):</strong> Classe <code>.ds-hide-laptop</code> sincronizada com o botão <code>1024px</code> do palco Ao Vivo.</li>
                    <li><strong>📱 Tablet (768px / 481px a 768px):</strong> Classe <code>.ds-hide-tablet</code> sincronizada com o botão <code>768px</code> do palco Ao Vivo.</li>
                    <li><strong>📱 Mobile (375px / &le; 480px):</strong> Classe <code>.ds-hide-mobile</code> sincronizada com o botão <code>375px</code> do palco Ao Vivo.</li>
                </ul>
                <div style="background: rgba(56, 189, 248, 0.08); border-left: 3px solid #38bdf8; padding: 10px 14px; border-radius: 4px; font-size: 12.5px; margin-top: 12px;">
                    <strong>🔄 Reajuste Automático do Grid (Auto-Reflow):</strong> Quando todos os elementos de uma coluna estão ocultos em determinada resolução, o <code>LayoutGridRenderer</code> aplica <code>ds-hide-{resolução}</code> na coluna vazia e recalcula automaticamente o <code>grid-template-columns</code> para as colunas visíveis restantes (ex.: um Header <code>1fr 2fr 1fr</code> onde o Logo na Coluna 1 é ocultado passa automaticamente para <code>2fr 1fr</code>; se restar apenas 1 coluna visível, ela assume <code>1fr</code> ocupando 100% da largura).
                </div>
            </div>

            <!-- SEÇÃO 2: ROTEIRO PASSO A PASSO PARA TESTAR -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🚀 Tutorial Passo a Passo: Como Operar e Homologar o Flex Builder
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <ol style="margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 12px;">
                    <li>
                        <strong>Passo 1 — Acessar o Painel de Partes Visuais:</strong><br>
                        No menu lateral esquerdo, clique em <code>Temas &gt; Flex Builder</code> (<code><?= $baseUrl ?>/admin/builder</code>). Você verá os cartões organizados em <em>Cabeçalhos (Headers)</em>, <em>Rodapés (Footers)</em> e <em>Seções &amp; Layouts Centrais</em>.
                    </li>
                    <li>
                        <strong>Passo 2 — Criar um Header Pro com 3 Colunas:</strong><br>
                        Clique em <code>+ Criar Nova Parte &gt; Novo Header</code>. Selecione o preset <code>3 Colunas Header Pro (25% / 50% / 25%)</code>. Adicione <strong>Logotipo</strong> na Coluna 1, <strong>Menu de Navegação</strong> na Coluna 2 e <strong>Botão CTA</strong> na Coluna 3.
                    </li>
                    <li>
                        <strong>Passo 3 — Configurar o Toggle Mobile (Exclusivo de Headers):</strong><br>
                        Na aba lateral <code>CONFIG</code> do Header, escolha o estilo do botão hamburguer (<code>classic ☰</code>, <code>pill MENU ☰</code>, <code>minimal ✕</code> ou <code>dots ⋮</code>) e seu alinhamento (<code>Esquerda</code>, <code>Centro</code> ou <code>Direita</code>).
                    </li>
                    <li>
                        <strong>Passo 4 — Testar Ocultação por Elemento nas 4 Resoluções:</strong><br>
                        Clique sobre o bloco <strong>Logotipo</strong> no palco ou na árvore de camadas, abra a seção <strong>Visibilidade Responsiva (Ocultar Elemento)</strong> no Inspetor e marque <em>Ocultar no Tablet (768px)</em>. Clique no botão <code>📱 768px</code> no topo do Studio e veja apenas o Logotipo desaparecer enquanto o Menu e o Botão CTA reajustam suas larguras automaticamente!
                    </li>
                    <li>
                        <strong>Passo 5 — Criar e Vincular uma Seção Central a um Template:</strong><br>
                        Crie uma <code>Nova Seção</code>, adicione o bloco <strong>Grade de Cards / Posts</strong> (exclusivo para seções de conteúdo) e, na aba <code>CONFIG</code>, vincule diretamente ao template <code>home.php</code> (ou incorpore em qualquer página usando o shortcode <code>[section id="ID_DA_PARTE"]</code>).
                    </li>
                    <li>
                        <strong>Passo 6 — Personalizar os Design Tokens Globais:</strong><br>
                        Acesse <code><?= $baseUrl ?>/admin/builder/tokens</code> para editar a paleta de cores e tipografia global injetada automaticamente em todas as partes visuais.
                    </li>
                </ol>
            </div>

            <!-- SEÇÃO 3: CATÁLOGO DOS 14 BLOCOS SEMÂNTICOS -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🧩 Catálogo Completo dos 14 Blocos Semânticos Nativos
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 12px; margin-bottom: 25px;">
                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🔤 1. Título (<code>heading</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Níveis H1 a H6, tipografia via Design Tokens, peso, espaçamento entre letras e alinhamento.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">📄 2. Parágrafo (<code>paragraph</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Texto corrido com HTML inline sanitizado (<code>SafeHtmlSanitizer</code> com whitelist positiva de URLs).</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🖼️ 3. Imagem (<code>image</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Upload seguro (PNG, JPG, WEBP, GIF e SVG validado via <code>DOMDocument</code>), alt text, bordas e link.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🎥 4. Vídeo (<code>video</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Embed responsivo 16:9 automático para YouTube, Vimeo ou arquivo MP4/WebM direto.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🧭 5. Menu de Navegação (<code>nav_menu</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Conecta ao <code>MenuProviderInterface</code> e renderiza gaveta mobile com os 4 estilos de toggle.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🛡️ 6. Logotipo (<code>logo</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Imagem gráfica e/ou texto estilizado com controle de altura e link automático para a Home.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🔘 7. Botão Estilizado (<code>button</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Botão versátil com ícone SVG opcional e 5 animações de hover (<code>grow</code>, <code>lift</code>, <code>glow</code>, <code>bounce</code>, <code>shimmer</code>).</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🚀 8. Botão CTA (<code>cta_button</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Call-to-Action de destaque rápido para Headers e Hero Sections (Primário, Secundário ou Outline).</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🗂️ 9. Grade de Cards / Posts (<code>post_cards_grid</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Grade responsiva de cards com imagem, badge, título, resumo e botão. <em>Exclusivo para Seções Centrais</em>.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">⚡ 10. Shortcode Dinâmico (<code>shortcode</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Executa qualquer shortcode registrado no sistema (ex: <code>[hub_de_cursos]</code>, <code>[section id="..."]</code>).</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">➖ 11. Divisória / Espaçador (<code>divider</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Linha divisória sólida, tracejada, pontilhada ou espaçador invisível com altura customizável.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🌐 12. Redes Sociais (<code>social_icons</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Ícones vetoriais via <code>SvgIconRegistryInterface</code> (GitHub, LinkedIn, Instagram, X, WhatsApp, YouTube).</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">© 13. Copyright &amp; Links Legais (<code>copyright</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Substituição dinâmica de <code>{year}</code> e <code>{site_name}</code> com links para Privacidade e Termos.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">💻 14. Código Customizado (<code>custom_code</code>)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Bloco avançado para marcação HTML/CSS/SVG customizada em seções específicas.</div>
                </div>
            </div>

            <!-- SEÇÃO 4: ARQUITETURA SOLID & CONTRATOS -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🏛️ Contratos SOLID do Flex Builder (`src/Core/Theme/Parts`)
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
                        <td style="padding: 8px 12px; color: var(--text-muted);">Caminho único de compilação compartilhado entre o Preview do Studio e o Frontend publicado.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><code>ThemePartValidatorInterface</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Valida tipo da parte, presets, existência/compatibilidade de blocos e limites estruturais antes de salvar.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><code>LayoutGridInterface</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Renderiza seções CSS Grid e recalcula <code>grid-template-columns</code> automaticamente quando colunas têm elementos ocultos.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><code>ElementStyleRendererInterface</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Higieniza propriedades CSS inline e emite as classes <code>.ds-hide-desktop</code>, <code>.ds-hide-laptop</code>, <code>.ds-hide-tablet</code> e <code>.ds-hide-mobile</code>.</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px;"><code>BlockProviderInterface</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Permite que qualquer plugin externo registre novos blocos no <code>BlockRegistryInterface</code> sem modificar o Core (OCP).</td>
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
