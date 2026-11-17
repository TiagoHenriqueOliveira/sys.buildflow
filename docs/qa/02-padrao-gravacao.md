# Padrão de gravação de tela

[← Índice](00-indice.md)

Toda execução de roteiro é gravada. O vídeo tem duas funções ao mesmo tempo:

1. **Evidência de teste:** prova o que foi feito, em qual versão e com qual perfil, e qual foi o resultado.
2. **Wiki interna:** ensina a equipe (suporte, implantação, novos devs) a usar cada tela.

Por isso a gravação é **narrada e didática**, não só um registro de cliques.

---

## 1. Ferramentas

| Ferramenta | Uso | Observação |
|---|---|---|
| **OBS Studio** (padrão) | Gravar a tela | Gratuito. Permite legenda fixa e pausa por atalho. |
| Ferramenta de Captura do Windows 11 (`Win + Shift + R`) | Alternativa rápida para evidência curta de bug | Sem legenda fixa; use só para clipes de bug |
| **PowerToys › Realce do Mouse** (`Win + Shift + H`) | Destacar cliques no vídeo | Ative antes de gravar |
| **Clipchamp** (já vem no Windows 11) | Cortar início e fim | Não fazer edição pesada |
| Google Chrome, perfil "QA" | Navegador da gravação | Sem extensões, sem favoritos, sem senhas salvas |

---

## 2. Configuração do OBS (perfil "QA-Buildflow")

| Configuração | Valor |
|---|---|
| Resolução de base e de saída | 1920 × 1080 |
| FPS | 30 |
| Formato | MP4 (em Configurações › Saída › Gravação; nas versões recentes, use "MP4 fragmentado" para não perder o arquivo se o OBS fechar) |
| Codificador | Hardware (NVENC/QSV/AMF) se disponível; senão x264 |
| Taxa de bits | 6.000 kbps |
| Áudio | Só o microfone (narração). **Desative o áudio da área de trabalho.** |
| Atalhos | Iniciar/parar: `Ctrl + F9` · Pausar/retomar: `Ctrl + F10` |

**Cenas:**
1. **Fonte "Captura de janela"** apontando para o Chrome do perfil QA. Não capture a tela inteira: evita mostrar a barra de tarefas, notificações e outras janelas.
2. **Fonte "Texto (GDI+)"** no canto superior direito, fundo semitransparente. Atualize o texto antes de cada vídeo:

```
CT-CLI-001 a 005 · Perfil: Comercial · v1.14.0 · 17/11/2026
```

---

## 3. Antes de cada gravação (checklist)

- [ ] Windows em **Não perturbe** (sem pop-up de e-mail, Teams ou WhatsApp).
- [ ] Só o Chrome do perfil QA aberto na janela gravada, com **zoom em 100%** (`Ctrl + 0`).
- [ ] Logado com o **perfil certo** para o roteiro. Faça o login **antes** de gravar ou com a gravação pausada.
- [ ] Legenda do OBS atualizada (IDs, perfil, versão, data).
- [ ] Massa de dados do roteiro existe.
- [ ] Arquivos de teste (`C:\QA-arquivos\`) à mão.
- [ ] DevTools (`F12`) aberto **somente** nos casos de segurança (D10), na aba **Network**, com o filtro em "Fetch/XHR" ou "Doc".

---

## 4. Estrutura de cada vídeo

Um vídeo por **bloco de casos** do roteiro, ou seja, 3 a 8 casos relacionados. A meta é de 2 a 8 minutos por vídeo.

| Momento | O que fazer | Duração |
|---|---|---|
| Abertura | Falar: "Roteiro D04, casos CT-CLI-001 a 005, cadastro de clientes, perfil Comercial, versão 1.14.0" | 10 s |
| Para cada caso | 1. Dizer o ID e **o objetivo** ("vou validar que o CNPJ duplicado é recusado")<br>2. Executar os passos **devagar**, sem pular telas<br>3. **Antes** de clicar em Salvar, dizer o resultado esperado<br>4. Mostrar o resultado e **parar 2 s** na mensagem ou tela final<br>5. Dizer "passou" ou "falhou" | 30 s a 2 min |
| Encerramento | "Fim do bloco: 4 passaram, 1 falhou, bug BUG-012" | 5 s |

**Boas práticas:**
- Narre o que importa, não cada clique ("abro a aba Contatos e adiciono um contato sem nome").
- Se errar um passo, **não corte**: diga "vou refazer" e refaça. Vídeo honesto vale mais que vídeo perfeito.
- Use `Ctrl + F10` (pausar) para digitar senha, esperar processamento longo ou trocar de usuário.
- Para mostrar um detalhe pequeno (mensagem de validação, rodapé), use o zoom do sistema (`Win` + `+`) por alguns segundos.

---

## 5. Nome dos arquivos

```
D<dia>_<IDs>_<assunto>_v<versão>_<RESULTADO>.mp4
```

| Exemplo | Quando |
|---|---|
| `D04_CT-CLI-001-005_clientes-cadastro_v1.14.0_PASS.mp4` | Todos os casos do bloco passaram |
| `D04_CT-CLI-006-010_clientes-validacoes_v1.14.0_FAIL.mp4` | Pelo menos um caso falhou |
| `BUG-012_cnpj-duplicado-500_v1.14.0.mp4` | Clipe curto de evidência de bug (até 1 min) |
| `RETESTE_BUG-012_cnpj-duplicado_v1.14.1_PASS.mp4` | Reteste após a correção |

Use letras minúsculas, sem acento, palavras separadas por hífen.

---

## 6. Onde guardar

Os vídeos ficam na **pasta compartilhada da equipe** (Google Drive ou OneDrive). **Nunca no repositório git:** vídeos incham o histórico para sempre.

```
Buildflow FAÉ/
└── QA/
    └── v1.14.0/
        ├── D00 - Preparação/
        ├── D01 - Acesso e segurança/
        ├── D02 - Cadastros de apoio/
        ├── …
        ├── D11 - Regressão e fechamento/
        ├── Bugs/
        └── Wiki - vídeos de referência/   ← cópia dos melhores vídeos "PASS", para a equipe consultar
```

- **Compartilhamento:** "Qualquer pessoa da organização com o link pode ver". Não use link público.
- **Ao subir**, copie o link do arquivo para a coluna **Vídeo + tempo** do Registro de execução do roteiro, indicando o minuto de cada caso:

```
[vídeo](https://link-do-drive) 01:42
```

- Nova rodada de testes (nova versão) = **nova pasta** (`v1.14.1/`). Não sobrescreva vídeos de versões anteriores.

---

## 7. Privacidade e LGPD

- Grave **somente dados DEMO**: CNPJ e CPF gerados para teste, nomes com prefixo `DEMO`.
- **Nunca** mostre no vídeo:
  - senhas, o arquivo `.env`, tokens;
  - o dump de produção (`Documents\dumps`);
  - dados de clientes reais.
- Assinaturas desenhadas nos testes: rabisco ou "DEMO", nunca a assinatura real de alguém.
- Se algo sensível aparecer por engano, **não suba o vídeo**. Regrave ou corte o trecho no Clipchamp antes de publicar.

---

## 8. Depois de gravar (checklist)

- [ ] Cortar só o início e o fim (Clipchamp › Exportar em 1080p).
- [ ] Assistir em velocidade 2× para conferir que o resultado de cada caso está visível.
- [ ] Renomear seguindo a seção 5.
- [ ] Subir na pasta do dia.
- [ ] Colar o link e os tempos no Registro de execução do roteiro.
- [ ] Se houve falha, gravar ou recortar o clipe do bug e anexar ao bug ([03-padrao-bugs.md](03-padrao-bugs.md)).
