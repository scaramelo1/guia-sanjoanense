<?php
// Aba Notícias: coleta automática de notícias sobre São João da Boa Vista, abertas dentro do app.
// - Prefeitura: notícias institucionais públicas, feitas para divulgação → texto completo, sempre com
//   "Fonte: Prefeitura de São João da Boa Vista" e link para o original.
// - g1 (RSS): o g1 não permite reproduzir a matéria → só título, foto, o resumo que o próprio RSS traz e o botão
//   "Ler a matéria completa no g1".
// O feed do Google Notícias NÃO é usado: os termos dele proíbem uso comercial.
// Agenda: cron em 6h, 9h, 12h, 15h, 18h e 21h (coletar-noticias.php, instalado pelo instalar.sh). Se o cron parar,
// a própria API coleta quando alguém abre a aba e a última coleta tem mais de 3 horas.
declare(strict_types=1);

const NOTICIAS_INTERVALO = 10800;   // reserva caso o cron pare: 3 horas
const NOTICIAS_TEXTOS_POR_COLETA = 15;
const NOTICIAS_TERMO = '/s[ãa]o jo[ãa]o da boa vista|sanjoanense/iu';
const NOTICIAS_UA = 'Mozilla/5.0 (compatible; GuiaSanjoanense/1.0; +https://fsadesigners.com.br/guia-sanjoanense/)';

function noticias_fontes(): array {
    return [
        ['nome' => 'Prefeitura de São João da Boa Vista', 'tipo' => 'prefeitura', 'url' => 'https://www.saojoao.sp.gov.br/noticias', 'completa' => true],
        ['nome' => 'g1 Campinas e Região', 'tipo' => 'rss', 'url' => 'https://g1.globo.com/rss/g1/sp/campinas-regiao/', 'filtrar' => true, 'completa' => false],
    ];
}

function noticias_baixar(string $url): ?string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 3,
        CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 4, CURLOPT_ENCODING => '', CURLOPT_USERAGENT => NOTICIAS_UA]);
    $r = curl_exec($ch);
    $ok = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
    curl_close($ch);
    return $ok && is_string($r) ? $r : null;
}

function noticias_texto(string $s): string {
    return trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
}

// Página https://www.saojoao.sp.gov.br/noticias: <li class="list-item lista-noticias-item"> com img, meta "dd/mm/aaaa às HHhMM" e título
function noticias_ler_prefeitura(string $html, string $base): array {
    $itens = [];
    foreach (preg_split('/<li class="list-item lista-noticias-item/', $html) as $i => $bloco) {
        if ($i === 0) continue;
        if (!preg_match('/<a class="list-item__link" href="([^"]+)">(.*?)<\/a>/s', $bloco, $t)) continue;
        $data = preg_match('/(\d{2})\/(\d{2})\/(\d{4})\s*&#224;s\s*(\d{2})h(\d{2})|(\d{2})\/(\d{2})\/(\d{4})\s*às\s*(\d{2})h(\d{2})/u', $bloco, $d)
            ? sprintf('%s-%s-%s %s:%s:00', ...($d[1] !== '' ? [$d[3], $d[2], $d[1], $d[4], $d[5]] : [$d[8], $d[7], $d[6], $d[9], $d[10]])) : date('Y-m-d H:i:s');
        $itens[] = [
            'titulo' => noticias_texto($t[2]),
            'link' => str_starts_with($t[1], 'http') ? $t[1] : $base . $t[1],
            'imagem' => preg_match('/<img src="(https:[^"]+)"/', $bloco, $im) ? $im[1] : null,
            'publicada_em' => $data,
        ];
    }
    return $itens;
}

function noticias_ler_rss(string $xml, bool $filtrar): array {
    $prev = libxml_use_internal_errors(true);
    $rss = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
    libxml_use_internal_errors($prev);
    if (!$rss || !isset($rss->channel->item)) return [];
    $itens = [];
    foreach ($rss->channel->item as $it) {
        $titulo = noticias_texto((string)$it->title);
        $desc = noticias_texto((string)$it->description);
        if ($filtrar && !preg_match(NOTICIAS_TERMO, $titulo . ' ' . $desc)) continue;
        $img = null;
        $media = $it->children('http://search.yahoo.com/mrss/');
        if (isset($media->content)) $img = (string)$media->content->attributes()->url;
        if (!$img && preg_match('/<img[^>]+src="(https:[^"]+)"/', (string)$it->description, $m)) $img = $m[1];
        $t = strtotime((string)$it->pubDate);
        $itens[] = ['titulo' => $titulo, 'link' => (string)$it->link, 'imagem' => $img ?: null, 'resumo' => $desc !== '' ? mb_substr($desc, 0, 600) : null,
            'publicada_em' => date('Y-m-d H:i:s', $t ?: time())];
    }
    return $itens;
}

function noticias_salvar(string $fonte, array $itens): int {
    $novas = 0;
    foreach ($itens as $n) {
        if ($n['titulo'] === '' || !preg_match('#^https://#', $n['link'])) continue;
        if (strtotime($n['publicada_em']) > time() + 3600) $n['publicada_em'] = date('Y-m-d H:i:s');
        $novas += q('INSERT IGNORE INTO noticias (fonte, titulo, link, link_hash, imagem, resumo, publicada_em) VALUES (?,?,?,?,?,?,?)',
            [$fonte, mb_substr($n['titulo'], 0, 300), mb_substr($n['link'], 0, 600), sha1($n['link']),
             $n['imagem'] && preg_match('#^https://#', $n['imagem']) ? mb_substr($n['imagem'], 0, 600) : null,
             $n['resumo'] ?? null, $n['publicada_em']])->rowCount();
    }
    return $novas;
}

// Página de uma notícia da Prefeitura → blocos de texto/imagem. O texto fica entre a foto de capa e a galeria.
function noticias_ler_materia_prefeitura(string $html): array {
    $ini = strpos($html, 'class="box-foto-capa-post"');
    if ($ini === false) $ini = strpos($html, 'class="page-heading"');
    if ($ini === false) return [];
    $ini = strpos($html, '</div>', $ini) ?: $ini;
    $fim = strlen($html);
    foreach (['<script', 'gallery__container', 'conteudo-relacionado', '<footer'] as $marca) {
        $p = strpos($html, $marca, $ini);
        if ($p !== false && $p < $fim) $fim = $p;
    }
    $miolo = substr($html, $ini + 6, $fim - $ini - 6);
    $blocos = [];
    // separa imagens do texto, mantendo a ordem
    foreach (preg_split('/(<img[^>]+>)/i', $miolo, -1, PREG_SPLIT_DELIM_CAPTURE) as $parte) {
        if (preg_match('/^<img[^>]+src="(https:[^"]+)"/i', $parte, $m)) { $blocos[] = ['t' => 'img', 'src' => html_entity_decode($m[1])]; continue; }
        $parte = preg_replace('#<(br|/p|/div|/h[1-6]|/li|/tr)[^>]*>#i', "\n", $parte);
        foreach (explode("\n", html_entity_decode(strip_tags($parte), ENT_QUOTES | ENT_HTML5, 'UTF-8')) as $l) {
            $l = trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $l));
            if ($l === '') continue;
            // linha curta toda em maiúsculas = intertítulo
            $sub = mb_strlen($l) <= 80 && $l === mb_strtoupper($l) && preg_match('/\p{L}/u', $l);
            $blocos[] = ['t' => $sub ? 'sub' : 'p', 'x' => $sub ? mb_strtoupper(mb_substr($l, 0, 1)) . mb_strtolower(mb_substr($l, 1)) : $l];
        }
    }
    // fotos da galeria (até 6), depois do texto
    $galeria = substr($html, $fim, 40000);
    if (preg_match_all('/class="gallery__item" href="(https:[^"]+)"/', $galeria, $g))
        foreach (array_slice($g[1], 0, 6) as $src) $blocos[] = ['t' => 'img', 'src' => html_entity_decode($src)];
    return $blocos;
}

// Baixa o texto das notícias novas (até 15 por coleta; desiste depois de 3 falhas)
function noticias_buscar_textos(string $fonte): void {
    foreach (linhas('SELECT id, link FROM noticias WHERE fonte = ? AND corpo IS NULL AND tentativas < 3 ORDER BY publicada_em DESC LIMIT ' . NOTICIAS_TEXTOS_POR_COLETA, [$fonte]) as $n) {
        q('UPDATE noticias SET tentativas = tentativas + 1 WHERE id = ?', [$n['id']]);
        $html = noticias_baixar($n['link']);
        $blocos = $html ? noticias_ler_materia_prefeitura($html) : [];
        if (!array_filter($blocos, fn($b) => $b['t'] === 'p')) continue;
        $resumo = '';
        foreach ($blocos as $b) if ($b['t'] === 'p') { $resumo = mb_substr($b['x'], 0, 300); break; }
        q('UPDATE noticias SET corpo = ?, completa = 1, resumo = COALESCE(resumo, ?) WHERE id = ?',
            [json_encode($blocos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $resumo, $n['id']]);
        usleep(300000);   // gentileza com o site da Prefeitura
    }
}

// Coleta se a última passou do intervalo. Uma requisição por vez (trava de arquivo); se outra já está coletando, segue sem esperar.
function noticias_atualizar(bool $forcar = false): void {
    $marca = config()['dados_dir'] . '/noticias-coleta';
    if (!$forcar && is_file($marca) && time() - filemtime($marca) < NOTICIAS_INTERVALO) return;
    $trava = @fopen($marca . '.lock', 'c');
    if (!$trava || !flock($trava, LOCK_EX | LOCK_NB)) return;
    @touch($marca);
    foreach (noticias_fontes() as $f) {
        try {
            $conteudo = noticias_baixar($f['url']);
            if ($conteudo === null) { error_log("Guia Sanjoanense notícias: {$f['nome']} não respondeu"); continue; }
            $itens = $f['tipo'] === 'prefeitura'
                ? noticias_ler_prefeitura($conteudo, 'https://www.saojoao.sp.gov.br')
                : noticias_ler_rss($conteudo, !empty($f['filtrar']));
            noticias_salvar($f['nome'], $itens);
            if (!empty($f['completa'])) noticias_buscar_textos($f['nome']);
        } catch (Throwable $e) {
            error_log("Guia Sanjoanense notícias: {$f['nome']}: " . $e->getMessage());
        }
    }
    q('DELETE FROM noticias WHERE publicada_em < NOW() - INTERVAL 120 DAY');
    flock($trava, LOCK_UN);
    fclose($trava);
}

function noticias_lista(int $limite = 40): array {
    return array_map(fn($n) => [
        'id' => (int)$n['id'], 'titulo' => $n['titulo'], 'link' => $n['link'], 'imagem' => $n['imagem'],
        'fonte' => $n['fonte'], 'quando' => noticias_quando($n['publicada_em']),
    ], linhas("SELECT id, titulo, link, imagem, fonte, publicada_em FROM noticias WHERE oculta = 0 ORDER BY publicada_em DESC LIMIT $limite"));
}

function noticia_completa(int $id): ?array {
    $n = $id ? linha('SELECT * FROM noticias WHERE id = ? AND oculta = 0', [$id]) : null;
    if (!$n) return null;
    $blocos = $n['corpo'] ? (json_decode($n['corpo'], true) ?: []) : [];
    if (!$blocos && $n['resumo']) $blocos = [['t' => 'p', 'x' => $n['resumo']]];
    // a capa já aparece no topo: não repetir a mesma foto no corpo
    $blocos = array_values(array_filter($blocos, fn($b) => $b['t'] !== 'img' || !$n['imagem'] || substr(basename($b['src']), 2) !== substr(basename($n['imagem']), 2)));
    return [
        // capa: na lista a Prefeitura usa a foto pequena (p_); aqui a média (m_)
        'id' => (int)$n['id'], 'titulo' => $n['titulo'], 'imagem' => $n['imagem'] ? preg_replace('#/p_(\d)#', '/m_$1', $n['imagem']) : null, 'fonte' => $n['fonte'], 'link' => $n['link'],
        'data' => date('d/m/Y', strtotime($n['publicada_em'])) . ' às ' . date('H\\hi', strtotime($n['publicada_em'])), 'completa' => (bool)$n['completa'], 'blocos' => $blocos,
    ];
}

function noticias_quando(string $d): string {
    $s = time() - strtotime($d);
    if ($s < 3600) return 'agora há pouco';
    if ($s < 86400) return 'há ' . intdiv($s, 3600) . ' h';
    if ($s < 172800) return 'ontem';
    return date('d/m/Y', strtotime($d));
}
