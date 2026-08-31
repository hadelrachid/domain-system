# Como Criar Temas no Domain-System

O Domain-System permite a criação de temas isolados para interfaces web. Ao contrário de sistemas engessados, um tema aqui pode ser empacotado dentro de um Plugin ou viver de forma independente na pasta `/themes`.

## Estrutura de Pastas

Para iniciar, um tema requer no mínimo 2 arquivos na sua pasta raiz:

```
/meu-tema-incrivel
├── theme.json       <-- Metadados de automação
├── index.php        <-- O ponto de entrada da view
└── screenshot.png   <-- (Opcional) A imagem de capa para o painel (800x600 recomendado)
```

## O Arquivo theme.json

Este arquivo JSON é lido pelo **ThemeManager** para construir a interface visual na área de administração.

```json
{
  "name": "Meu Portal do Paciente",
  "version": "1.0.0",
  "description": "Um tema voltado para agendamentos online e resultados de exames públicos.",
  "author": "Dr. Fulano",
  "screenshot": "screenshot.png"
}
```

## Como Instalar (Upload)
Na área de "Temas" do Painel de Controle, você pode clicar em **Instalar Tema (.zip)**. Basta compactar a sua pasta `meu-tema-incrivel` e subir pelo administrador!

