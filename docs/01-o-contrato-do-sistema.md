# O Contrato do Sistema Operacional (Domain-System OS)

Bem-vindo ao coração do **Domain-System OS**. Se você está acostumado a criar temas e plugins para sistemas como WordPress, prepare-se para uma quebra de paradigma. 

Aqui, nós não escrevemos código "solto" torcendo para não dar conflito. Nós assinamos um **Contrato de Execução**.

Este manual explicará passo a passo como o motor do sistema funciona e introduzirá um dos conceitos mais poderosos (e muitas vezes mal compreendidos) da engenharia de software: a **Injeção de Dependências**.

---

## 1. Por que um Contrato? (A Morte do `functions.php`)

Em sistemas antigos, os plugins usam funções globais para modificar o sistema. Isso é como ter um quadro elétrico com os fios soltos: se dois plugins tentarem usar a mesma "tomada", o sistema entra em curto (Fatal Error) e sai do ar.

No **Domain-System OS**, toda Extensão (seja um Tema visual ou um Plugin de retaguarda) é obrigada a assinar a `OsExtensionInterface`. 

Esta interface funciona como um disjuntor inteligente. Ela divide a vida do seu plugin em duas fases isoladas: **Negociação** e **Execução**.

```php
interface OsExtensionInterface {
    public function osRegister(OsConnector $os): void;
    public function osBoot(OsRuntime $runtime): void;
}
```

---

## 2. Fase 1: A Negociação (`osRegister`)

Antes de deixar o seu código rodar, o Sistema Operacional pergunta: *"O que você precisa para trabalhar e o que você oferece em troca?"*

Você usa o objeto `$os` (A Ponte de Negociação) para declarar formalmente as suas intenções. Nenhuma regra de banco de dados ou lógica complexa deve ser colocada aqui.

```php
public function osRegister(OsConnector $os): void
{
    // Eu preciso ler os usuários do sistema
    $os->requireLink('auth.user_reader');
    
    // Eu quero desenhar um widget no dashboard administrativo
    $os->requestSlot('admin.dashboard.alerts');
    
    // Eu ofereço ao sistema o serviço de envio de SMS
    $os->provideLink('sms.sender', TwilioSmsSender::class);
}
```

> [!IMPORTANT]
> **Segurança em Primeiro Lugar**
> Se você tentar usar um recurso na Fase 2 que não foi pedido aqui na Fase 1, o **Sistema Operacional bloqueará o seu plugin por violação de segurança.**

---

## 3. A Magia da Injeção de Dependências (O que é isso?)

Antes de irmos para a Fase 2, precisamos desmistificar um termo que assusta muita gente no Brasil: **Injeção de Dependências (Dependency Injection)**.

**O Jeito Antigo (Errado): A Caça ao Tesouro**
Nos sistemas legados, se você precisasse do Banco de Dados, você fazia assim:
```php
$db = Application::getInstance()->getDatabase(); // O plugin caçando a ferramenta
```
O problema é que o plugin se torna "xereta". Ele entra onde não deve, procura as coisas sozinho, e se o nome do Banco de Dados mudar, o plugin quebra.

**O Jeito Moderno (Certo): Injeção de Dependências**
Injeção de Dependências é, de forma simples, o conceito do **"Garçom"**. 
Você não vai até a cozinha preparar o prato. Você senta na mesa e o garçom *traz (injeta)* a comida pronta para você.

Em vez de você ir caçar a ferramenta, o Sistema Operacional "Injeta" a ferramenta diretamente na sua mão. E é exatamente isso que acontece na Fase 2!

---

## 4. Fase 2: A Execução (`osBoot`)

Aqui o seu plugin acorda e começa a trabalhar. E como o Sistema Operacional sabe que o seu plugin é "confiável" (pois negociou tudo na Fase 1), ele envia o garçom: o objeto `$runtime`.

O `$runtime` carrega uma bandeja **contendo estritamente as coisas que você pediu**, através de **Injeção de Dependências**.

```php
public function osBoot(OsRuntime $runtime): void
{
    // O OS INJETA a ferramenta na sua mão! Sem classes globais.
    $userReader = $runtime->getLink('auth.user_reader');
    
    // Agora você pode usar a ferramenta com segurança
    $medicos = $userReader->getAllDoctors();
    
    // O OS permite que você injete HTML no slot que você requisitou
    $runtime->contributeTo('admin.dashboard.alerts', [
        'type' => 'success',
        'message' => 'Carregamos ' . count($medicos) . ' médicos com sucesso!'
    ]);
}
```

### Resumo dos Benefícios
1. **Sem Erros Fatais:** Se faltar o `auth.user_reader`, o sistema avisa o usuário para instalar o plugin faltante antes de rodar, em vez de quebrar a tela inteira.
2. **Auto-Complete Perfeito:** As IDEs modernas conseguem ler o `$runtime` e sugerir as opções de código, eliminando a necessidade de decorar nomes de funções.
3. **Auditoria Pronta para IA:** Um robô ou inteligência artificial pode ler o seu `osRegister` e entender instantaneamente tudo o que o seu plugin faz, criando um ciclo de verificação e segurança perfeito.

Bem-vindo à nova era do PHP modular!
