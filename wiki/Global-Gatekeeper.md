> 🇧🇷 [Ler em Português](#-o-porteiro-global-centralized-acl) | 🇺🇸 [Read in English](#-the-global-gatekeeper-centralized-acl)

---

# 🇧🇷 👮 O Porteiro Global (Centralized ACL)

O pior "código sujo" (Code Smell) que você pode encontrar na camada Controller de um sistema é a repetição manual de validações de acesso, como:
```php
if ($_SESSION['user_role'] !== 'admin') { die('Acesso Negado'); }
```

Além de violar o princípio de **Responsabilidade Única (SRP)** (o controlador não deveria ser o segurança da porta), isso cria falhas críticas de escalabilidade. Se a clínica criar um novo cargo, o programador precisará alterar dezenas de controladores manualmente.

Para resolver isso, o **Domain-System** move a responsabilidade de autorização (ACL - Access Control List) para a engrenagem mais alta possível: **O Roteador (Router)**.

## O Roteador como Gatekeeper

Em vez de verificar sessões dentro dos controladores de negócio (Finanças, Pacientes, Prontuários), a autorização passa a ser puramente **Declarativa**.

Quando um módulo registra suas rotas no arquivo central `Plugin.php`, ele declara uma matriz (array) de Papéis Permitidos (`Roles`) diretamente na rota:

```php
$router->addRoute('GET', '/admin/finance', [FinanceController::class, 'index'], 'finance', ['admin']);
$router->addRoute('GET', '/admin/triage', [TriageController::class, 'index'], 'triage', ['admin', 'receptionist', 'doctor']);
```

### Bloqueio Antecipado

Antes do Roteador instanciar o Controlador na memória e processar injeções de dependência, ele age como um "Porteiro":
1. Lê a matriz de exigências da Rota.
2. Compara com a sessão ativa no Kernel.
3. Se o papel não bater, o Roteador exibe um Escudo de Acesso Negado (Status 403 Forbidden).

O controlador sequer chega a ser acionado. O código flui maravilhosamente limpo e focado 100% no negócio, deixando toda a lógica de segurança para o núcleo do sistema tratar de forma invisível.

<br><br><br><hr>

# 🇺🇸 👮 The Global Gatekeeper (Centralized ACL)

The worst "Code Smell" you can find in the Controller layer of a system is the manual repetition of access validations, such as:
```php
if ($_SESSION['user_role'] !== 'admin') { die('Access Denied'); }
```

Besides violating the **Single Responsibility Principle (SRP)** (the controller shouldn't be the door bouncer), this creates critical scalability flaws. If the clinic creates a new job role, the programmer will have to alter dozens of controllers manually.

To solve this, the **Domain-System** moves the authorization responsibility (ACL - Access Control List) to the highest possible gear: **The Router**.

## The Router as a Gatekeeper

Instead of verifying sessions inside business controllers (Finances, Patients, Records), authorization becomes purely **Declarative**.

When a module registers its routes in the central `Plugin.php` file, it declares an array of Allowed Roles directly on the route:

```php
$router->addRoute('GET', '/admin/finance', [FinanceController::class, 'index'], 'finance', ['admin']);
$router->addRoute('GET', '/admin/triage', [TriageController::class, 'index'], 'triage', ['admin', 'receptionist', 'doctor']);
```

### Preemptive Blocking

Before the Router instantiates the Controller in memory and processes dependency injections, it acts as a "Gatekeeper":
1. Reads the route's requirement array.
2. Compares it with the active session in the Kernel.
3. If the role doesn't match, the Router displays a Denied Access Shield (Status 403 Forbidden).

The controller never even gets triggered. The code flows beautifully clean and focused 100% on business logic, leaving all security handling to be processed invisibly by the system's core.
