<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\flextheme\manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

/**
 * Manual Oficial e Guia de Testes do Novo Flex Builder (Motor em Blocos FSE & Design Tokens).
 * Descoberto automaticamente pelo sistema de Manuais do Domain-System OS (/admin/manual).
 */
class FlexBuilderManualModule implements ManualModuleInterface
{
    public function getId(): string
    {
        return 'flextheme';
    }

    public function getTitle(): string
    {
        return 'Flex Builder (Motor Visual em Blocos, Design Tokens & FSE)';
    }

    public function getSubtitle(): string
    {
        return 'Guia completo de composição de Headers, Footers e Seções em grade responsiva, catálogo de 12 blocos e tokens de design.';
    }

    public function getBadge(): string
    {
        return 'Flex Builder v3.0 • Full Site Editing';
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
                'label' => '1. Acessar /admin/builder e conferir as seções de Cabeçalhos (Headers), Rodapés (Footers) e Seções Centrais com os status de associação do tema ativo.',
            ],
            [
                'id'    => 'flexbuilder-create-header',
                'label' => '2. Clicar em "+ Criar Nova Parte > Novo Header", selecionar o preset de 3 colunas (3_cols_header) e adicionar Logo, Menu de Navegação e Botão CTA.',
            ],
            [
                'id'    => 'flexbuilder-mobile-toggle',
                'label' => '3. Na aba "Config" do Header, testar a alternância entre os 4 estilos de botão hamburguer mobile (classic ☰, pill MENU ☰, minimal ✕ e dots ⋮).',
            ],
            [
                'id'    => 'flexbuilder-create-footer',
                'label' => '4. Criar um Footer profissional com 4 colunas (4_cols_footer) contendo Logo com texto descritivo, Links, Redes Sociais e Copyright dinâmico com {year}.',
            ],
            [
                'id'    => 'flexbuilder-device-simulator',
                'label' => '5. No topo do Studio, alternar a simulação entre Desktop (100%), Laptop (1024px), Tablet (768px) e Smartphone (375px) observando a auto-adaptação da grade.',
            ],
            [
                'id'    => 'flexbuilder-dimension-units',
                'label' => '6. No Inspetor de Propriedades, testar a troca de unidades dimensionais flexíveis (px, %, em, rem, vw, vh) em larguras, alturas e paddings.',
            ],
            [
                'id'    => 'flexbuilder-design-tokens',
                'label' => '7. Acessar /admin/builder/tokens, personalizar as cores mestres e fontes globais, e verificar o reflexo imediato no simulador de tokens.',
            ],
            [
                'id'    => 'flexbuilder-assign-theme',
                'label' => '8. No painel de partes, vincular o Header e Footer ao tema ativo (ou marcar como "Padrão Global") e abrir a vitrine pública para validar o layout.',
            ],
            [
                'id'    => 'flexbuilder-fallback-test',
                'label' => '9. Desvincular a parte personalizada e comprovar que o sistema aciona com 100% de segurança o fallback nativo do tema (partials/header.php).',
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
                O <strong>Novo Flex Builder (v3.0)</strong> é o ecossistema oficial de composição visual <em>Full Site Editing (FSE)</em> do Domain-System OS. Ele substitui a arquitetura antiga por um motor veloz inspirado no Gutenberg e no Elementor, construído rigorosamente sobre <strong>Contratos SOLID (Ring 0 e Ring 1)</strong>, <strong>Design Tokens Globais</strong>, <strong>Divisórias em CSS Grid</strong> e <strong>Simulador Multi-Dispositivo</strong>.
            </p>

            <!-- PILARES DO MOTOR -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin: 20px 0 25px;">
                <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #38bdf8; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📐 1. A Base é a Grade</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Divisórias estruturais de 1 a 4 colunas com controle de largura, gap e padding com auto-colapso responsivo no tablet e mobile.
                    </div>
                </div>

                <div style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #c084fc; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🎨 2. Design Tokens</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Paleta de cores mestre e famílias tipográficas que geram variáveis CSS automáticas aplicadas universalmente.
                    </div>
                </div>

                <div style="background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #00d284; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📱 3. Simulador Responsivo</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Visualização em tempo real alternando entre Desktop (100%), Laptop (1024px), Tablet (768px) e Mobile (375px).
                    </div>
                </div>

                <div style="background: rgba(234, 179, 8, 0.08); border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #facc15; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🛡️ 4. Fallback 100% Graceful</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Se uma parte não estiver atribuída, o sistema carrega os arquivos PHP originais do tema, garantindo zero páginas quebradas.
                    </div>
                </div>
            </div>

            <!-- SEÇÃO 1: ROTEIRO PASSO A PASSO PARA TESTAR -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🚀 Tutorial Passo a Passo: Como Testar o Flex Builder
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <ol style="margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 12px;">
                    <li>
                        <strong>Passo 1 — Acessar o Flex Builder:</strong><br>
                        No menu lateral esquerdo do painel, clique em <code>Temas > Flex Builder</code> (ou acesse diretamente <code><?= $baseUrl ?>/admin/builder</code>). Você verá os painéis de <em>Cabeçalhos</em>, <em>Rodapés</em> e <em>Seções</em>.
                    </li>
                    <li>
                        <strong>Passo 2 — Criar um Header Pro:</strong><br>
                        No topo direito, clique em <code>+ Criar Nova Parte > Novo Header</code>. O <strong>Studio Full Site Editing</strong> será aberto. Selecione a grade <code>3 Colunas Header Pro (25% / 50% / 25%)</code>. Na Coluna 1, adicione o bloco <strong>Logotipo</strong>; na Coluna 2, o <strong>Menu de Navegação</strong>; e na Coluna 3, o <strong>Botão CTA</strong>.
                    </li>
                    <li>
                        <strong>Passo 3 — Testar o Hamburguer Mobile Padronizado:</strong><br>
                        Na aba lateral <code>Config</code>, selecione um dos 4 estilos de botão toggle mobile:
                        <ul style="margin: 6px 0 0 16px; font-size: 13px;">
                            <li><code>classic</code>: ☰ Tradicional com 3 barras horizontais.</li>
                            <li><code>pill</code>: Pílula moderna com texto e ícone (ex: <code>MENU ☰</code>).</li>
                            <li><code>minimal</code>: ✕ 2 barras minimalistas ultra-limpas.</li>
                            <li><code>dots</code>: ⋮ 3 pontinhos verticais modernos.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Passo 4 — Simular em Diferentes Telas:</strong><br>
                        No topo do Studio, clique nos botões de simulação: <code>💻 Desktop</code>, <code>💻 1024px</code>, <code>📱 768px</code> e <code>📱 375px</code>. Observe como as colunas se reorganizam e colapsam suavemente.
                    </li>
                    <li>
                        <strong>Passo 5 — Salvar com 1 Clique (AJAX):</strong><br>
                        Clique no botão verde <code>💾 Salvar</code> no topo direito. O salvamento ocorre via requisição assíncrona com feedback imediato e sem recarregar a tela.
                    </li>
                    <li>
                        <strong>Passo 6 — Personalizar os Design Tokens:</strong><br>
                        Acesse <code><?= $baseUrl ?>/admin/builder/tokens</code>. Altere a Cor Primária (ex: <code>#ff0055</code>) e a Fonte dos Títulos (ex: <code>Montserrat</code>). Observe o card de simulação atualizar imediatamente. Ao clicar em <strong>Salvar Tokens Globais</strong>, todo o site adota a nova paleta.
                    </li>
                </ol>
            </div>

            <!-- SEÇÃO 2: CATÁLOGO DOS 12 BLOCOS SEMÂNTICOS -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🧩 Catálogo Completo dos 12 Blocos Semânticos Nativos
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; margin-bottom: 25px;">
                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🔤 Título (Heading)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Níveis H1 a H6, tamanho flexível (rem, px, vw), seleção de fonte (Primária/Secundária) e alinhamento.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">📄 Parágrafo (Paragraph)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Texto corrido rico, controle de line-height, alinhamento justificado e cores de alto contraste.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🖼️ Imagem (Image)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">URL ou caminho local, larguras dinâmicas (%, px, vw), bordas arredondadas e links de clique.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🎥 Vídeo (Video)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Embed automático de YouTube, Vimeo ou player nativo MP4/WebM com aspect ratio 16:9 limpo.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🧭 Menu de Navegação (Nav Menu)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Conecta dinamicamente ao repositório de menus (nav_menus) e renderiza gaveta mobile acessível.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🛡️ Logotipo (Logo)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Suporte a imagem gráfica ou texto estilizado com destaque na cor primária e link automático para home.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🚀 Botão CTA (Call-to-Action)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Variantes de destaque (Primária sólida, Secundária ou Outline), paddings configuráveis e URLs.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">⚡ Shortcode Pronto (Shortcode)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Dropdown com todos os shortcodes ativos no sistema (ex: [hub_de_cursos], [login_form], [base_url]).</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">➖ Divisória / Espaçador (Divider)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Linha sólida, tracejada ou espaçador transparente para respiro entre seções com altura em px/rem/vh.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🌐 Redes Sociais (Social Icons)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Ícones SVG de GitHub, LinkedIn, Instagram, X, WhatsApp e YouTube com links diretos.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">© Copyright & LGPD (Copyright)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Substituição automática de {year} e {site_name} com links integrados para Termos e Privacidade.</div>
                </div>

                <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                    <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">💻 Código Customizado (Custom Code)</div>
                    <div style="font-size: 12px; color: var(--text-muted);">Inserção livre de snippets HTML, CSS e JavaScript para personalizações de alta precisão.</div>
                </div>
            </div>

            <!-- SEÇÃO 3: CONTROLE DE DIMENSÕES & DESIGN TOKENS -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                📏 Unidades de Medida Flexíveis
            </h3>

            <p>
                Qualquer campo dimensional no Flex Builder suporta o <strong>Seletor Rápido de Unidades</strong>:
            </p>

            <table style="width: 100%; border-collapse: collapse; font-size: 13px; margin-bottom: 25px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border, #30363d); text-align: left;">
                        <th style="padding: 8px 12px; color: #fff;">Unidade</th>
                        <th style="padding: 8px 12px; color: #fff;">Símbolo</th>
                        <th style="padding: 8px 12px; color: #fff;">Aplicação Típica</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><strong>Pixels</strong></td>
                        <td style="padding: 8px 12px;"><code>px</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Medidas fixas, alturas de logos, bordas arredondadas (ex: <code>36px</code>, <code>1280px</code>).</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><strong>Porcentagem</strong></td>
                        <td style="padding: 8px 12px;"><code>%</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Larguras fluidas relativas ao container pai (ex: <code>100%</code>, <code>50%</code>).</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><strong>Rem</strong></td>
                        <td style="padding: 8px 12px;"><code>rem</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Tamanho de fontes e espaçamentos acessíveis escaláveis com o navegador (ex: <code>1.5rem</code>, <code>2rem</code>).</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><strong>Em</strong></td>
                        <td style="padding: 8px 12px;"><code>em</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Espaçamento contextual baseado no tamanho da fonte do próprio bloco.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px;"><strong>Viewport Width</strong></td>
                        <td style="padding: 8px 12px;"><code>vw</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Proporcional à largura total do monitor do usuário (ex: <code>90vw</code>).</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px;"><strong>Viewport Height</strong></td>
                        <td style="padding: 8px 12px;"><code>vh</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Proporcional à altura vertical da tela (ótimo para banners e heros de <code>80vh</code> ou <code>100vh</code>).</td>
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
