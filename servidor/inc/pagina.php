<?php
// Visual comum das páginas públicas (divulgação, termos, privacidade): minimalista, letra grande, preto e branco
// com detalhes ciano/magenta, como o app.
declare(strict_types=1);

/** Contatos e links das lojas, definidos em /etc/guia-sanjoanense/config.php (vazio = ainda não existe). */
function site(string $chave): string {
    return trim((string)(config()[$chave] ?? ''));
}

/** Como falar com o Guia: e-mail, WhatsApp e Instagram que estiverem no config. */
function contatos_html(): string {
    $itens = [];
    if ($e = site('contato_email')) $itens[] = '<a href="mailto:' . h($e) . '">' . h($e) . '</a>';
    if ($w = site('contato_whatsapp')) $itens[] = '<a href="' . h(link_whatsapp($w, 'Olá! Vim pelo site do Guia Sanjoanense.')) . '">WhatsApp ' . h(telefone_formatado($w)) . '</a>';
    if ($i = site('instagram')) $itens[] = '<a href="https://instagram.com/' . h(ltrim($i, '@')) . '">Instagram @' . h(ltrim($i, '@')) . '</a>';
    return $itens ? implode(' · ', $itens) : 'pelo Instagram da página Sanjoanenses';
}

function pagina_inicio(string $titulo, string $descricao, string $classe = ''): void {
    $base = config()['url'];
    $canonica = url_publica(basename($_SERVER['SCRIPT_NAME']) === 'index.php' ? '' : basename($_SERVER['SCRIPT_NAME']));
    ?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($titulo) ?></title>
<meta name="description" content="<?= h($descricao) ?>">
<link rel="canonical" href="<?= h($canonica) ?>">
<meta property="og:title" content="<?= h($titulo) ?>">
<meta property="og:description" content="<?= h($descricao) ?>">
<meta property="og:image" content="<?= h(url_publica('assets/logo.png')) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="pt_BR">
<link rel="icon" href="<?= $base ?>/assets/favicon.ico" sizes="any">
<link rel="icon" type="image/png" href="<?= $base ?>/assets/favicon.png">
<link rel="apple-touch-icon" href="<?= $base ?>/assets/apple-touch-icon.png">
<meta name="theme-color" content="#000000">
<link rel="stylesheet" href="<?= $base ?>/assets/site.css?v=1">
</head>
<body class="<?= h($classe) ?>">
<header class="topo">
  <a class="marca" href="<?= $base ?>/"><img src="<?= $base ?>/assets/logo.png" alt="" width="40" height="40"><span>Guia Sanjoanense</span></a>
  <nav><a href="<?= $base ?>/#como-funciona">Como funciona</a><a href="<?= $base ?>/#profissionais">Para profissionais</a><a href="<?= $base ?>/#baixar" class="nav-botao">Baixar o app</a></nav>
</header>
<?php
}

function pagina_fim(): void {
    $base = config()['url'];
    ?>
<footer class="rodape">
  <div class="faixa"><span></span><span></span></div>
  <p class="rodape-marca"><img src="<?= $base ?>/assets/logo.png" alt="" width="32" height="32"> <strong>Guia Sanjoanense</strong> · chancelado pela página Sanjoanenses</p>
  <p><a href="<?= $base ?>/termos.php">Termos de uso</a> · <a href="<?= $base ?>/privacidade.php">Privacidade</a> · <a href="<?= $base ?>/privacidade.php#excluir-conta">Excluir minha conta</a> · <a href="<?= $base ?>/painel/">Quero aparecer no guia</a></p>
  <p>Fale conosco: <?= contatos_html() ?></p>
  <p class="discreto">São João da Boa Vista – SP · <?= date('Y') ?></p>
</footer>
</body>
</html>
<?php
}

/** Botões das lojas: link quando existe, "em breve" quando ainda não. */
function botoes_lojas(): string {
    $lojas = [
        ['link_play', 'Google Play', 'Android', '<path d="M4 3.3v17.4c0 .5.5.8 1 .6l10-8.7a.8.8 0 0 0 0-1.2L5 2.7c-.5-.2-1 .1-1 .6Zm12.4 6.2-2.6 2.5 2.6 2.5 3.5-2a.6.6 0 0 0 0-1Z"/>'],
        ['link_app_store', 'App Store', 'iPhone', '<path d="M16.4 12.6c0-2.4 2-3.5 2-3.6a4.3 4.3 0 0 0-3.4-1.8c-1.4-.2-2.8.8-3.5.8s-1.9-.8-3-.8a4.6 4.6 0 0 0-3.9 2.4c-1.7 2.9-.4 7.2 1.2 9.6.8 1.2 1.8 2.5 3 2.4 1.2 0 1.7-.8 3.1-.8s1.9.8 3.1.8 2.1-1.2 2.9-2.4a10 10 0 0 0 1.3-2.7 4.1 4.1 0 0 1-2.8-3.9ZM14 5.5A4.1 4.1 0 0 0 15 2.5a4.2 4.2 0 0 0-2.8 1.4 4 4 0 0 0-1 2.9A3.5 3.5 0 0 0 14 5.5Z"/>'],
    ];
    $html = '';
    foreach ($lojas as [$chave, $nome, $sistema, $svg]) {
        $icone = '<svg viewBox="0 0 24 24" width="28" height="28" fill="currentColor" aria-hidden="true">' . $svg . '</svg>';
        $link = site($chave);
        $html .= $link
            ? '<a class="loja" href="' . h($link) . '">' . $icone . '<span><small>Baixar no</small>' . $nome . '</span></a>'
            : '<span class="loja breve" aria-disabled="true">' . $icone . '<span><small>' . $sistema . ' · em breve</small>' . $nome . '</span></span>';
    }
    return $html;
}
