<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\academy\manual;

use DomainSystem\Core\Contracts\ManualModuleInterface;

/**
 * Manual Oficial, Tutorial Passo a Passo e Guia de Homologação do Plugin Academy (LMS Híbrido, Editor em Blocos & Fábrica PHP).
 * Descoberto automaticamente pelo Cockpit via contrato ManualModuleInterface (/admin/manual e /manual).
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
        return 'Tutorial passo a passo e manual completo de criação de cursos, fábrica de arquivos PHP, PDFs híbridos multi-cloud, integração com Flex Builder, cockpit do aluno (LGPD) e vendas com DomainCommerce.';
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
                'label' => '1. Cadastrar um curso com status "Publicado" em /admin/academy, preenchendo título, imagem de capa 16:9 (mín. 640x360) e conferindo a geração de slug amigável sem acentos.',
            ],
            [
                'id'    => 'academy-create-folder-module',
                'label' => '2. Na edição do curso, clicar em "📁 Criar Pasta (ou Módulo)", digitar o nome do módulo e conferir a criação automática da pasta física em pages/ do tema ativo.',
            ],
            [
                'id'    => 'academy-scaffold-folder-php',
                'label' => '3. Criar uma aula mantendo marcada a "🏭 Fábrica Automática de Arquivos PHP" e salvar: verificar a criação do arquivo .php e a liberação da rota direta /cursos/{slug-da-aula}.',
            ],
            [
                'id'    => 'academy-hybrid-modes',
                'label' => '4. Testar os 4 modos de construção de conteúdo: Editor em Blocos (content), Fábrica PHP Automática, Arquivo Existente do Tema (theme_file) e "📂 Importar Pasta Inteira".',
            ],
            [
                'id'    => 'academy-block-editor-live',
                'label' => '5. Abrir o "⚡ Editor de Blocos" da aula, adicionar Título, Parágrafo, Vídeo 16:9, Alerta e Código, salvando com Ctrl+S enquanto visualiza o preview ao vivo.',
            ],
            [
                'id'    => 'academy-pdf-hybrid',
                'label' => '6. Inserir o bloco "📄 Visualizador de PDF": testar incorporação segura via nuvem corporativa (Google Drive / OneDrive / Dropbox) ou upload local até 128MB com validação mágica %PDF-.',
            ],
            [
                'id'    => 'academy-storage-best-practices',
                'label' => '7. Boas Práticas de Servidor: confirmar que PDFs pesados (>50MB) usam links de nuvem para economizar espaço em disco e tráfego na hospedagem.',
            ],
            [
                'id'    => 'academy-device-simulator',
                'label' => '8. No Simulador do Player, alternar entre os 5 dispositivos mobile (320px a 1024px) e testar os 3 Perfis Mobile: 📱 Gaveta (drawer), 📲 Barra Fixa (bottom_bar) e 🎬 Modo Cinema (16:9).',
            ],
            [
                'id'    => 'academy-flex-header-integration',
                'label' => '9. No Flex Builder (/admin/builder), adicionar o bloco "👤 Autenticação do Usuário" no Header para exibir links dinâmicos de Login / Cadastro e acesso direto ao Cockpit do Aluno.',
            ],
            [
                'id'    => 'academy-student-cockpit',
                'label' => '10. Fazer login como aluno e acessar o Cockpit (/aluno/painel): testar barra de progresso em tempo real, botão "Concluir Aula" e anotações isoladas por aluno (LGPD).',
            ],
            [
                'id'    => 'academy-access-guard-commerce',
                'label' => '11. Definir preço no curso (ex: R$ 197,00) e verificar que visitantes não matriculados veem o card de matrícula com checkout integrado ao DomainCommerce (Mercado Pago Pix/Cartão).',
            ],
            [
                'id'    => 'academy-course-zip-export-import',
                'label' => '12. Clicar em "📦 Exportar (.zip)" para baixar o pacote do curso com manifesto course.json e aulas PHP, e testar a importação instantânea em outra instalação.',
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
                O <strong>Plugin Academy (v2.6.0)</strong> é o motor de aprendizagem e ensino (<strong>LMS Híbrido</strong>) oficial do Domain-System OS. Desenvolvido sobre os princípios <strong>SOLID</strong> e <strong>Inversão de Dependência (DIP)</strong>, ele permite estruturar e monetizar cursos de quatro maneiras complementares: com o <strong>Editor Visual em Blocos</strong>, pela <strong>Fábrica de Arquivos PHP responsivos</strong> gerados no tema, por <strong>Páginas personalizadas do Tema</strong> ou via <strong>Importação de Pastas em 1 clique</strong>.
            </p>

            <!-- PILARES DO ACADEMY -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 14px; margin: 20px 0 25px;">
                <div style="background: rgba(168, 85, 247, 0.08); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #c084fc; font-weight: 800; font-size: 13px; margin-bottom: 6px;">⚡ 1. Quatro Modos Híbridos</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Crie aulas no Editor de Blocos visual, gere arquivos PHP com a Fábrica, aponte para templates customizados ou importe pastas inteiras com 1 clique.
                    </div>
                </div>

                <div style="background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #00d284; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🛡️ 2. Cockpit do Aluno & LGPD</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Painel exclusivo (<code>/aluno/painel</code>) com acompanhamento de progresso percentual, conclusão de aulas e anotações criptografadas e isoladas por usuário.
                    </div>
                </div>

                <div style="background: rgba(69, 243, 255, 0.08); border: 1px solid rgba(69, 243, 255, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #45f3ff; font-weight: 800; font-size: 13px; margin-bottom: 6px;">📄 3. Visualizador de PDF Multi-Cloud & Otimização</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        O bloco <strong>Visualizador de PDF</strong> integra com Google Drive (/preview), OneDrive e Dropbox, sem sobrecarregar o espaço em disco da sua hospedagem.
                    </div>
                </div>

                <div style="background: rgba(245, 166, 35, 0.08); border: 1px solid rgba(245, 166, 35, 0.3); border-radius: 8px; padding: 14px 16px;">
                    <div style="color: #f5a623; font-weight: 800; font-size: 13px; margin-bottom: 6px;">🛒 4. Vendas Automáticas com Pix</div>
                    <div style="color: var(--text-muted, #8c8f94); font-size: 12px; line-height: 1.5;">
                        Integração nativa com DomainCommerce e Mercado Pago. Pagou via Pix, o webhook libera o acesso do aluno imediatamente.
                    </div>
                </div>
            </div>

            <!-- ROTEIRO COMPLETO PASSO A PASSO -->
            <h3 style="color: #c084fc; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🚀 Roteiro Passo a Passo Definitivo: Da Criação do Curso ao Cockpit do Aluno
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <ol style="margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 14px; font-size: 13.5px;">
                    <li>
                        <strong>Passo 1 — Cadastrar o Curso no Painel Administrativo:</strong><br>
                        Acesse <code>Academy &gt; Cursos</code> (<code><?= $baseUrl ?>/admin/academy</code>) e clique em <strong>"+ Novo Curso"</strong>.
                        <ul style="margin: 6px 0; padding-left: 20px; font-size: 13px;">
                            <li><strong>Título e Slug:</strong> Digite o nome do curso (ex: <em>"Arquitetura SOLID na Prática"</em>); o slug limpo sem acentos (<code>arquitetura-solid-na-pratica</code>) é gerado instantaneamente.</li>
                            <li><strong>Preço e Acesso:</strong> Se for gratuito, mantenha <code>0,00</code>. Se for pago, digite o valor (ex: <code>197,00</code>) para acionar a guarda de matrícula.</li>
                            <li><strong>Capa Proporção 16:9:</strong> Envie uma imagem com no mínimo <code>640&times;360 px</code> (ideal <code>1280&times;720 px</code>) para exibição correta no catálogo e no player.</li>
                            <li><strong>Status:</strong> Defina como <strong>Publicado</strong> e clique em <strong>Salvar Curso</strong>.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Passo 2 — Estruturar Módulos e Pastas Físicas:</strong><br>
                        Na tela de edição do curso recém-criado, clique no botão <strong>"📁 Criar Pasta (ou Módulo)"</strong>. Digite o título do módulo (ex: <em>"Módulo 01 - Introdução e Princípios"</em>). O sistema cria o módulo visualmente e, opcionalmente, cria a subpasta física correspondente dentro de <code>themes/{tema-ativo}/pages/</code>.
                    </li>
                    <li>
                        <strong>Passo 3 — Criar Aulas (Fábrica Automática vs Editor de Blocos):</strong><br>
                        Dentro do módulo criado, clique em <strong>"+ Nova Aula"</strong> e escolha seu fluxo favorito:
                        <ul style="margin: 6px 0; padding-left: 20px; font-size: 13px;">
                            <li><strong>🏭 Fluxo com Fábrica PHP:</strong> Mantenha a caixa <em>"Fábrica Automática de Arquivos PHP"</em> marcada. Ao salvar, o arquivo <code>.php</code> responsivo é gerado no tema e a rota direta amigável <code>/cursos/{slug-da-aula}</code> é liberada no mesmo segundo!</li>
                            <li><strong>⚡ Fluxo com Editor de Blocos:</strong> Marque <em>"Editor em Blocos"</em> e clique em <strong>Editar Conteúdo</strong> para montar a aula com blocos visuais sem tocar em código.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Passo 4 — Integrar o LMS com o Header do Site no Flex Builder:</strong><br>
                        Para que seus visitantes e alunos acessem facilmente a área de membros:
                        <ul style="margin: 6px 0; padding-left: 20px; font-size: 13px;">
                            <li>Abra o <strong>Flex Builder</strong> em <code><?= $baseUrl ?>/admin/builder</code> e edite o Header principal.</li>
                            <li>Na 3ª coluna, adicione o bloco <code>👤 Autenticação do Usuário (user_auth)</code>.</li>
                            <li>Para visitantes, ele exibe automaticamente botões estilizados de <em>"Entrar"</em> e <em>"Cadastrar-se"</em> apontando para <code>/aluno/login</code>.</li>
                            <li>Para alunos conectados, o bloco se transforma automaticamente em um menu com a foto/nome do aluno e link direto para o <strong>Cockpit do Aluno (<code>/aluno/painel</code>)</strong>.</li>
                            <li>No mobile, o botão é absorvido para dentro do menu hambúrguer, mantendo o layout enxuto.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Passo 5 — Publicar o Catálogo na Home ou Páginas do Site:</strong><br>
                        Você pode disponibilizar os cursos de duas maneiras:
                        <ul style="margin: 6px 0; padding-left: 20px; font-size: 13px;">
                            <li>Pela rota nativa do catálogo público: <code><a href="<?= $baseUrl ?>/cursos" target="_blank" style="color:#45f3ff;"><?= $baseUrl ?>/cursos</a></code>.</li>
                            <li>Inserindo o shortcode <code>[hub_de_cursos]</code> dentro de qualquer página de conteúdo ou bloco de shortcode do Flex Builder.</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Passo 6 — Vivenciar a Experiência do Aluno no Cockpit (LGPD):</strong><br>
                        Acesse <code><a href="<?= $baseUrl ?>/aluno/painel" target="_blank" style="color:#00d284;"><?= $baseUrl ?>/aluno/painel</a></code>:
                        <ul style="margin: 6px 0; padding-left: 20px; font-size: 13px;">
                            <li>O aluno visualiza sua lista de cursos matriculados e o percentual concluído de cada um.</li>
                            <li>Ao entrar na aula, ele assiste ao vídeo, lê a apostila e pode clicar em <strong>"Concluir Aula"</strong> para atualizar seu progresso em tempo real.</li>
                            <li>Na gaveta lateral <strong>Minhas Anotações</strong>, ele digita anotações pessoais de estudo. Esses dados são salvos em pasta isolada por usuário (<code>storage/students/{id}/</code>) garantindo estrita privacidade conforme a LGPD.</li>
                        </ul>
                    </li>
                </ol>
            </div>

            <!-- REGRA DE OURO DE ARMAZENAMENTO E PDFS HÍBRIDOS -->
            <h3 style="color: #45f3ff; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                📄 Apostilas e PDFs Híbridos: Regra de Ouro contra o Inchaço do Servidor
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <p style="margin-top: 0;">
                    O bloco <strong>📄 Visualizador de PDF</strong> conta com um leitor inteligente capaz de processar apostilas e documentos armazenados tanto localmente quanto nas principais nuvens corporativas do mundo. Para manter sua instalação <strong>leve, ágil e econômica</strong>, siga estas recomendações:
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px; margin: 15px 0;">
                    <div style="background: rgba(0, 210, 132, 0.08); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 6px; padding: 12px 14px;">
                        <div style="color: #00d284; font-weight: 700; margin-bottom: 4px;">☁️ Nuvem Corporativa (Recomendado para Arquivos Grandes)</div>
                        <div style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                            Para apostilas pesadas, livros ou manuais volumosos (ex: 20MB a 100MB+), hospede o PDF no <strong>Google Drive</strong>, <strong>Microsoft OneDrive</strong> ou <strong>Dropbox</strong> e cole o link no bloco. O Academy converte o link automaticamente para visualização segura embutida (<code>/preview</code> ou <code>embedview</code>), sem gastar 1 byte de espaço nem largura de banda do seu servidor PHP!
                        </div>
                    </div>

                    <div style="background: rgba(56, 189, 248, 0.08); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 6px; padding: 12px 14px;">
                        <div style="color: #38bdf8; font-weight: 700; margin-bottom: 4px;">💾 Upload Local no Servidor (Para Arquivos Leves)</div>
                        <div style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                            Ideal para resumos, fichas de exercícios e apostilas compactadas (até 5MB a 10MB). Os arquivos são armazenados de forma protegida em <code>storage/academy/lessons_pdf/</code> com validação rigorosa de cabeçalho binário (<em>Magic Bytes <code>%PDF-</code></em>) e proteção anti-download direto.
                        </div>
                    </div>
                </div>
            </div>

            <!-- OS 3 PERFIS MOBILE DO PLAYER -->
            <h3 style="color: #f5a623; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                📱 Os 3 Perfis de Layout Mobile do Player de Aulas
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <p style="margin-top: 0;">
                    Ao editar qualquer aula, você pode definir como o conteúdo será apresentado em smartphones e tablets, garantindo a melhor ergonomia de acordo com o tipo de aula:
                </p>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px; margin-top: 14px;">
                    <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                        <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">📱 Gaveta Lateral (<code>drawer</code>)</div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            Padrão para cursos com muitas aulas e módulos. O índice fica guardado em uma gaveta lateral deslizante que abre ao tocar no botão de menu, liberando 100% da tela para a leitura do conteúdo.
                        </div>
                    </div>

                    <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                        <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">📲 Barra Inferior Fixa (<code>bottom_bar</code>)</div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            Fixa uma barra de navegação no rodapé da tela do celular com botões de <em>Aula Anterior</em>, <em>Próxima Aula</em> e <em>Índice Rápido</em>. Perfeito para aulas sequenciais práticas.
                        </div>
                    </div>

                    <div style="background: var(--bg-card, #161b22); border: 1px solid var(--border, #30363d); border-radius: 6px; padding: 12px 14px;">
                        <div style="font-weight: 700; color: #fff; margin-bottom: 4px;">🎬 Modo Cinema (<code>cinema</code>)</div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            Dedicado para vídeo-aulas onde o foco principal é o vídeo. O player ocupa o topo em proporção 16:9 contínua e a lista de aulas desliza suavemente em um painel inferior recolhível.
                        </div>
                    </div>
                </div>
            </div>

            <!-- GUIA DO DESENVOLVEDOR: AULAS EM PHP PURO -->
            <h3 style="color: #00d284; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                💻 Guia do Desenvolvedor: Criação em PHP Puro & Importação de Pastas
            </h3>

            <div style="background: rgba(0, 0, 0, 0.28); border: 1px solid rgba(0, 210, 132, 0.3); border-radius: 8px; padding: 18px 20px; margin-bottom: 24px;">
                <p style="margin: 0 0 12px 0;">
                    Desenvolvedores podem estruturar módulos e aulas em <strong>arquivos PHP puros</strong> diretamente na pasta do tema ativo para importação automática:
                </p>
                <div style="font-size: 13px; line-height: 1.8;">
                    <strong>1. Crie a pasta do curso/módulo em <code>pages/</code> do seu tema:</strong><br>
                    <code style="background:rgba(255,255,255,0.06); padding:2px 8px; border-radius:4px;">themes/rachidd/pages/modulo-01-fundamentos/</code>
                </div>
                <div style="font-size: 13px; line-height: 1.8; margin-top: 10px;">
                    <strong>2. Estruture o arquivo da aula (ex: <code>01-introducao.php</code>) com o docblock de metadados:</strong>
                </div>
                <pre style="background: #0b0f19; border: 1px solid rgba(255,255,255,0.1); border-radius: 6px; padding: 14px; font-size: 12px; color: #38bdf8; overflow-x: auto; margin: 10px 0;">&lt;?php
/**
 * Título: Introdução à Programação Orientada a Objetos
 * Perfil Mobile: drawer
 */
?&gt;
&lt;div class="minha-aula"&gt;
    &lt;h1&gt;Introdução à Programação Orientada a Objetos&lt;/h1&gt;
    &lt;p&gt;Bem-vindo à primeira aula do módulo!&lt;/p&gt;
    &lt;p&gt;Curso: &lt;strong&gt;&lt;?= htmlspecialchars($course['title']) ?&gt;&lt;/strong&gt;&lt;/p&gt;
&lt;/div&gt;</pre>
                <div style="font-size: 13px; line-height: 1.8; margin-top: 10px;">
                    <strong>3. Variáveis injetadas automaticamente no escopo da aula:</strong>
                    <ul style="margin: 4px 0 0 18px; padding: 0;">
                        <li><code>$course</code>: Dados completos do curso (<code>id</code>, <code>title</code>, <code>slug</code>, <code>price</code>, etc.).</li>
                        <li><code>$lesson</code>: Metadados da aula ativa (<code>id</code>, <code>title</code>, <code>slug</code>, <code>duration</code>, etc.).</li>
                        <li><code>$adjacent</code>: Array de navegação com aula anterior (<code>$adjacent['prev']</code>) e próxima (<code>$adjacent['next']</code>).</li>
                        <li><code>$navHtml</code>: Menu lateral completo com os módulos e aulas do curso.</li>
                    </ul>
                </div>
                <div style="font-size: 13px; line-height: 1.8; margin-top: 10px;">
                    <strong>4. Como importar no painel em 1 clique:</strong><br>
                    Na edição do curso, clique no botão <strong>"📂 Importar Pasta Inteira"</strong> do módulo, aponte para o diretório e clique em Confirmar. O importador inteligente lê os metadados do cabeçalho e cadastra todas as aulas em ordem automaticamente!
                </div>
            </div>

            <!-- EXPORTAÇÃO E MIGRAÇÃO EM ZIP -->
            <h3 style="color: #c084fc; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                📦 Exportação, Backup &amp; Migração de Cursos Portáveis (.zip)
            </h3>

            <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border, #30363d); border-radius: 8px; padding: 18px 22px; margin-bottom: 24px;">
                <p style="font-size: 13px; margin-top: 0; line-height: 1.6;">
                    O Academy conta com um motor completo de empacotamento e descompactação de cursos portáveis:
                </p>
                <ul style="margin: 0; padding-left: 20px; display: flex; flex-direction: column; gap: 8px; font-size: 13px;">
                    <li><strong>📦 Exportar Curso:</strong> Na listagem ou edição do curso, clique em <strong>"📦 Exportar (.zip)"</strong>. O sistema gera um arquivo ZIP padronizado contendo o manifesto <code>course.json</code> (com todos os módulos, metadados, preços e ordenação) e todos os arquivos <code>.php</code> vinculados às aulas.</li>
                    <li><strong>📥 Importar Curso:</strong> Em qualquer instalação do Domain-System, acesse <code>/admin/academy</code> e clique em <strong>"📥 Importar Curso (.zip)"</strong>. Envie o arquivo ZIP: o sistema extrai com segurança os arquivos <code>.php</code> no tema ativo e restaura integralmente a estrutura do curso!</li>
                </ul>
            </div>

            <!-- TABELA DE ROTAS E LINKS ÚTEIS -->
            <h3 style="color: #38bdf8; font-size: 16px; margin: 30px 0 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 8px;">
                🔗 Mapa de Rotas e Endpoints Oficiais do Academy
            </h3>

            <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; margin-bottom: 25px;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border, #30363d); text-align: left;">
                        <th style="padding: 8px 12px; color: #fff;">Finalidade</th>
                        <th style="padding: 8px 12px; color: #fff;">Rota / URL</th>
                        <th style="padding: 8px 12px; color: #fff;">Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px; font-weight: 700;">Catálogo de Cursos</td>
                        <td style="padding: 8px 12px;"><code><a href="<?= $baseUrl ?>/cursos" target="_blank" style="color:#45f3ff;"><?= $baseUrl ?>/cursos</a></code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Grade pública de cursos publicados com filtro e cards de matrícula.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px; font-weight: 700;">Cockpit do Aluno</td>
                        <td style="padding: 8px 12px;"><code><a href="<?= $baseUrl ?>/aluno/painel" target="_blank" style="color:#00d284;"><?= $baseUrl ?>/aluno/painel</a></code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Área de membros com métricas de progresso e atalhos para continuar assistindo.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px; font-weight: 700;">Login do Aluno</td>
                        <td style="padding: 8px 12px;"><code><a href="<?= $baseUrl ?>/aluno/login" target="_blank" style="color:#45f3ff;"><?= $baseUrl ?>/aluno/login</a></code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Tela de autenticação com segurança contra força bruta e redirecionamento dinâmico.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px; font-weight: 700;">Cadastro do Aluno</td>
                        <td style="padding: 8px 12px;"><code><a href="<?= $baseUrl ?>/aluno/cadastro" target="_blank" style="color:#00d284;"><?= $baseUrl ?>/aluno/cadastro</a></code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Formulário de cadastro simplificado com criação imediata de conta.</td>
                    </tr>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.06);">
                        <td style="padding: 8px 12px; font-weight: 700;">Rota Direta de Aula</td>
                        <td style="padding: 8px 12px;"><code><?= $baseUrl ?>/cursos/{slug-da-aula}</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">URL amigável direta liberada automaticamente para aulas fabricadas em PHP.</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 12px; font-weight: 700;">Shortcode de Cursos</td>
                        <td style="padding: 8px 12px;"><code>[hub_de_cursos]</code></td>
                        <td style="padding: 8px 12px; color: var(--text-muted);">Inserível em qualquer página de conteúdo ou bloco do Flex Builder.</td>
                    </tr>
                </tbody>
            </table>

            <!-- DESTAQUE ARQUITETURAL SOLID -->
            <div style="background: rgba(168, 85, 247, 0.08); border-left: 4px solid #a855f7; padding: 12px 16px; margin: 16px 0 0; border-radius: 0 6px 6px 0; font-size: 13px;">
                <strong>Blindagem SOLID &amp; Contratos (Ring 3):</strong><br>
                O Academy opera 100% desacoplado através dos contratos: <code>CourseRepositoryInterface</code>, <code>CourseStructureRepositoryInterface</code>, <code>CoursePackageServiceInterface</code>, <code>CourseEnrollmentInterface</code>, <code>StudentProgressInterface</code>, <code>StudentStorageInterface</code>, <code>LessonBlockRendererInterface</code>, <code>ThemePageResolverInterface</code>, <code>LessonFileScaffolderInterface</code> e <code>ManualModuleInterface</code>.
            </div>

            <!-- BOTÃO DIRETO PARA O ACADEMY -->
            <div style="margin-top: 30px; text-align: center;">
                <a href="<?= $baseUrl ?>/admin/academy" class="btn button-primary" style="background: #9333ea; color: #fff; padding: 12px 28px; border-radius: 6px; font-weight: 700; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                    🎓 Gerenciar Cursos no Academy Agora
                </a>
            </div>

        </div>
        <?php
        return (string)ob_get_clean();
    }
}
