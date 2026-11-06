# Plano de Ação - Resolução da Auditoria V3 (AI Hub)

Este documento centraliza as diretrizes arquiteturais levantadas pelas inteligências (ChatGPT, Claude e DeepSeek) sobre a versão atual do **Domain System**. O objetivo é transformar o sistema de "funcional" para "intocável" e puramente fiel à sua promessa de Ambiente Operacional Web.

## 🔴 Prioridade 1: A Falsa Sandbox (ChatGPT)
**O Problema:** A classe AbstractPlugin exige permissões via OsRuntime, mas o construtor injeta o Container inteiro nas mãos do Plugin. Com o Container, qualquer plugin malicioso pode invocar $this->container->make(...) e obter privilégios de Root (Ring 0), burlando completamente as travas de segurança.
**Ação:**
- Remover a injeção do ContainerInterface da classe AbstractPlugin.
- Plugins Ring 3 deverão interagir com o Core **exclusivamente** através da interface do OsRuntime.
- Corrigir a documentação sobre "Ring 0 / Ring 3" para esclarecer que é um isolamento de capacidade, e não de processos do SO real.

## 🔴 Prioridade 2: Limpeza de Superglobais (Claude)
**O Problema:** Embora a assinatura dos controladores tenha evoluído para receber Request , o interior dos métodos (como EmergencyController e AdminController) continua acessando e manipulando $_SESSION e $_POST diretamente, quebrando o encapsulamento e o princípio de Responsabilidade Única (SOLID).
**Ação:**
- Refatorar todos os controladores que ainda usam $_SESSION para injetar e utilizar o SessionManagerInterface.
- Remover as chamadas estáticas (ex: PasswordAnalyzer::isAcceptable()) e substituí-las por Injeção de Dependência (PasswordPolicyInterface).
- (Opcional) Criar um teste de arquitetura automatizado (NoSuperglobalsInControllersTest) que falha a pipeline caso $_SESSION ou $_POST sejam detectados dentro da pasta de controladores.

## 🔴 Prioridade 3: View & Response Engine / Fim do "Inception Bug" (DeepSeek & Épico 2.4)
**O Problema:** Controladores retornam tipos de dados inconsistentes (strings cruas de HTML, ou chamam xit diretamente após um header()). Isso causa bugs visuais gravíssimos como o "Inception" (um painel inteiro renderizado dentro de outro painel) e impede o Kernel de intervir.
**Ação:**
- Criar a interface ResponseInterface e suas implementações concretas: ViewResponse, JsonResponse e RedirectResponse.
- Atualizar o Router e o Dispatcher para esperar que **todos** os controladores devolvam um objeto ResponseInterface.
- O Kernel (public/index.php ou ThemeManager) será o único responsável por processar o ResponseInterface final. Se for um ViewResponse na área administrativa, ele aplica o layout automaticamente sem duplicar o HTML (envelopamento limpo).

## 🟡 Prioridade 4: Unificação Global de Exceções (ChatGPT)
**O Problema:** Atualmente, tanto o ErrorHandler base quanto o NoBreakShield registram manipuladores de exceções usando set_exception_handler(). Isso cria uma concorrência desleal pela fronteira global de erros do PHP.
**Ação:**
- Criar uma única Fronteira Global de Exceções (GlobalExceptionBoundary) que orquestra o Log, a Quarentena e a Renderização.
- Remover os múltiplos set_exception_handler().

## 🟡 Prioridade 5: O Risco de Extração ZIP (ChatGPT)
**O Problema:** A validação do extrator de ZIP detecta arquivos perigosos e usa continue, o que pula a checagem, mas o sistema executa $zip->extractTo() logo em seguida, extraindo o ZIP **inteiro**, incluindo os arquivos perigosos que haviam sido detectados.
**Ação:**
- O ZipArchiveExtractor deve extrair arquivo por arquivo verificando os paths e bloqueando ../ e ..\, em vez de chamar o extrator nativo global na pasta inteira.
