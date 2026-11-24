# 🧩 Como Criar um Plugin no Domain System

> **Aprenda a criar seu primeiro módulo em 5 minutos.**  
> Este guia é direto ao ponto e não exige frameworks pesados.

---

## O que é um Plugin?
No **Domain System**, a lógica de negócio (como a loja de dropshipping, a plataforma de cursos e os pagamentos) fica totalmente separada do núcleo. Cada funcionalidade é um **Plugin** que vive dentro de sua própria pasta em `src/Plugins/{nome_do_plugin}/`.

---

## 🚀 Método Rápido (Via Linha de Comando)

Você pode gerar a estrutura do plugin instantaneamente rodando no terminal:

```bash
php os-cli.php make:plugin meu_plugin
```

Esse comando cria a pasta `src/Plugins/meu_plugin` com o `plugin.json` e a classe `Plugin.php` prontos para você programar.

---

## 🛠️ Criando Passo a Passo Manualmente

Se preferir criar os arquivos na mão, você só precisa de dois arquivos:

```text
src/Plugins/meu_plugin/
├── plugin.json
└── Plugin.php
```

### 1. O Manifesto (`plugin.json`)
Crie `src/Plugins/meu_plugin/plugin.json`:

```json
{
    "name": "meu_plugin",
    "version": "1.0.0",
    "description": "Meu primeiro plugin incrível",
    "author": "Seu Nome",
    "core": false,
    "os_version": "2.7"
}
```

### 2. A Classe Principal (`Plugin.php`)
Crie `src/Plugins/meu_plugin/Plugin.php`:

```php
<?php

declare(strict_types=1);

namespace DomainSystem\Plugins\meu_plugin;

use DomainSystem\Core\Plugin\AbstractPlugin;
use DomainSystem\Core\Contracts\OsExtensionInterface;
use DomainSystem\Core\Contracts\OsConnectorInterface;
use DomainSystem\Core\Contracts\OsRuntimeInterface;
use DomainSystem\Core\Routing\Router;
use DomainSystem\Core\Http\Request;
use DomainSystem\Core\Http\Response;

class Plugin extends AbstractPlugin implements OsExtensionInterface
{
    public function register(): void {}

    // FASE 1: Declara o que o plugin precisa usar do núcleo
    public function osRegister(OsConnectorInterface $os): void
    {
        $os->requireLink('core.db.schema'); // Permite criar tabelas no banco
        $os->listenHook('router.register'); // Permite registrar rotas web
        $os->listenHook('admin.menu');       // Permite adicionar botão no painel admin
    }

    // FASE 2: Registra suas rotas e eventos durante o carregamento da página
    public function osBoot(OsRuntimeInterface $runtime): void
    {
        // 1. Criando uma rota pública: GET /meu-app
        $runtime->onHook('router.register', function (Router $router) {
            $router->addRoute('GET', '/meu-app', function (Request $request) {
                return new Response("<h1>Olá Mundo! Meu plugin está funcionando perfeitamente.</h1>");
            });
        });

        // 2. Criando um item no menu do painel administrativo
        $runtime->onHook('admin.menu', function ($menus, $role = 'guest') {
            if ($role === 'admin') {
                $menus[] = [
                    'title' => 'Meu App',
                    'url'   => '/meu-app',
                    'icon'  => '🚀',
                ];
            }
            return $menus;
        });
    }

    // FASE 3: Executado apenas na primeira vez que o admin clica em "Ativar"
    public function activate(OsRuntimeInterface $runtime): void
    {
        $schema = $runtime->getLink('core.db.schema');
        $schema->create('minha_tabela_exemplo', function ($table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });
    }
}
```

---

## 🎯 Testando seu Plugin
1. Salve os arquivos.
2. Acesse seu painel em `http://localhost:8000/admin/plugins`.
3. Localize **"meu_plugin"** e clique em **Ativar**.
4. Acesse no navegador: `http://localhost:8000/meu-app`.
5. Pronto! Sua rota e sua tabela já estão funcionando.

---

## 📚 Veja Também
- [Como Criar um Tema Visual](Criando-Temas)
- [Voltar para a Página Principal da Wiki](Home)
