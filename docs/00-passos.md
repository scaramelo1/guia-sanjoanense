# Guia Sanjoanense — passos

App de guia comercial de São João da Boa Vista, chancelado pela página **Sanjoanenses**.
Pedido do cliente (06/11/2026): minimalista, simples para a terceira idade, botões de categorias na
tela inicial, carrossel de profissionais em destaque no topo (vitrine paga).

- **App:** Expo (TypeScript), na raiz do repositório (`src/`).
- **Servidor:** PHP 8.3 + MariaDB em `servidor/`, publicado em https://fsadesigners.com.br/guia-sanjoanense/
  (`bash servidor/instalar.sh` como root; config em `/etc/guia-sanjoanense/config.php`).
- **Pagamento:** Mercado Pago, assinatura recorrente no cartão (o cartão é digitado no Mercado Pago).

Legenda: ✅ feito · 🟡 parcial · ⬜ a fazer

## Regras combinadas

- Navegar, buscar e chamar no WhatsApp **não pede conta**. A conta (e-mail e senha) serve para avaliar,
  comentar e salvar profissionais. A pessoa pode excluir a conta pelo app (exigência das lojas).
- Uma avaliação por pessoa por profissional (pode mudar depois). O comentário mostra só "Maria S.".
- O profissional aparece se estiver **aprovado** e com a **assinatura em dia** (ou cortesia dada pelo admin).
- Ordem justa: destaques primeiro, depois quem tem avaliação; dentro de cada grupo a ordem muda uma vez por dia.
- Selo "Chancelado pela página Sanjoanenses" é dado pelo admin, não vem com o pagamento.
- Notícias: não usar o feed do Google Notícias (os termos proíbem uso comercial). Texto completo só de fonte que permite (Prefeitura: conteúdo institucional público; confirmar por e-mail com a Comunicação da Prefeitura antes de lançar). g1 e jornais: só título, foto, resumo do RSS e link. Novas fontes só com RSS público ou página pública, sempre com link para o original. O admin poderá ocultar manchetes.
- Os 10 profissionais atuais são **fictícios** (`exemplo = 1`); apagar antes de lançar.

## Passos

1. ✅ Banco, instalador e publicação (`servidor/sql/schema.sql`, `servidor/instalar.sh`, 24 categorias)
2. ✅ API do app: início (destaques + categorias), categoria, busca, perfil, contagem de contatos
3. ✅ Contas do app: criar, entrar, sair, excluir; avaliações com estrelas e comentário; salvos
4. ✅ App: tela inicial (busca, carrossel, botões de categoria), todas as categorias, lista, busca,
   perfil (WhatsApp, ligar, mapa, Instagram, indicar), avaliar, salvos, conta
5. ✅ Testar o app no PC (`npm install`, `npx tsc --noEmit`, `npx expo lint`, emulador) — feito pelo usuário em 06/11
5b. 🟡 Contas no app: usuário relatou que não funcionam; o servidor nunca recebeu tentativa de entrar/criar conta → investigar no emulador
5c. ✅ Aba Notícias (pedido do cliente, 06/11): abas embaixo (Início, Notícias, Salvos, Conta); manchetes coletadas a cada 30 min da página da Prefeitura e do RSS do g1 Campinas (filtrado por "São João da Boa Vista"); notícia aberta dentro do app (passo 5d)
5d. ✅ Notícia montada dentro do app (pedido do cliente, 06/11): Prefeitura com texto completo, intertítulos e galeria; g1 só com resumo do RSS; sempre com quadro "Fonte" + link do original. Coleta por cron 6h, 9h, 12h, 15h, 18h, 21h (`/etc/cron.d/guia-sanjoanense`, log em /var/lib/guia-sanjoanense/noticias.log); reserva: a API coleta se passar de 3 h
5e. ✅ Splash Screen (pedido do cliente, 06/11): splash nativa com o "S" da Sanjoanenses no fundo branco e, em seguida, abertura animada (`src/componentes/Abertura.tsx`): o S sobe, aparecem "Guia Sanjoanense", faixa ciano/magenta e "São João da Boa Vista"; some em ~2,5 s (tocar pula). Trocar pela logo em alta quando chegar (passo 10)
6. ⬜ Administração web (página Sanjoanenses): aprovar/recusar cadastros, editar, selo, cortesia,
   categorias (ordem e quais vão para a tela inicial), ocultar comentários, apagar exemplos
7. ⬜ Painel do profissional (web): autocadastro, foto, dados, serviços, responder avaliações,
   ver quantos tocaram em WhatsApp/ligar
8. ⬜ Assinatura recorrente no Mercado Pago (planos Básico e Destaque; webhook; atraso tira do ar)
9. ⬜ Página de divulgação em /guia-sanjoanense/ e termos de uso / privacidade
10. ⬜ Ícone original em alta (1024 px) com o cliente; gerar APK de teste (EAS)
11. ⬜ Publicar nas lojas (Play Store e App Store)

## Decisões pendentes com o cliente

- Preço dos planos Básico e Destaque (mensal).
- Quantos lugares no carrossel de destaque (hoje mostra até 10, ordem muda por dia).
- Outras fontes de notícias da cidade (jornais locais com RSS ou parceria; o Jornal do Parabrisa bloqueia robôs).
- Logo original em alta resolução (o recebido tem 191 px).
