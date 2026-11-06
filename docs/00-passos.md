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
- Os 10 profissionais atuais são **fictícios** (`exemplo = 1`); apagar antes de lançar.

## Passos

1. ✅ Banco, instalador e publicação (`servidor/sql/schema.sql`, `servidor/instalar.sh`, 24 categorias)
2. ✅ API do app: início (destaques + categorias), categoria, busca, perfil, contagem de contatos
3. ✅ Contas do app: criar, entrar, sair, excluir; avaliações com estrelas e comentário; salvos
4. ✅ App: tela inicial (busca, carrossel, botões de categoria), todas as categorias, lista, busca,
   perfil (WhatsApp, ligar, mapa, Instagram, indicar), avaliar, salvos, conta
5. ⬜ Testar o app no PC (`npm install`, `npx tsc --noEmit`, `npx expo lint`, emulador)
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
- Logo original em alta resolução (o recebido tem 191 px).
