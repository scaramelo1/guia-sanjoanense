<?php
// Aba Notícias: coleta automática de manchetes sobre São João da Boa Vista.
// Só usamos fontes que permitem isso: a página pública de notícias da Prefeitura e o RSS do G1 (feito para leitores
// de notícias), sempre mostrando apenas título, imagem e fonte, com link para a matéria original.
// O feed do Google Notícias NÃO é usado: os termos dele proíbem uso comercial.
// A coleta acontece quando alguém abre a aba e a última tem mais de 30 minutos (não precisa de cron).
declare(strict_types=1);

const NOTICIAS_INTERVALO = 1800;
const NOTICIAS_TERMO = '/s[ãa]o jo[ãa]o da boa vista|sanjoanense/iu';
const NOTICIAS_UA = 'Mozilla/5.0 (compatible; GuiaSanjoanense/1.0; +https://fsadesigners.com.br/guia-sanjoanense/)';

function noticias_fontes(): array {
    return [
        ['nome' => 'Prefeitura de São João', 'tipo' => 'prefeitura', 'url' => 'https://www.saojoao.sp.gov.br/noticias'],
        ['nome' => 'g1 Campinas e Região', 'tipo' => 'rss', 'url' => 'https://g1.globo.com/rss/g1/sp/campinas-regiao/', 'filtrar' => true],
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
        $itens[] = ['titulo' => $titulo, 'link' => (string)$it->link, 'imagem' => $img ?: null,
            'publicada_em' => date('Y-m-d H:i:s', $t ?: time())];
    }
    return $itens;
}

function noticias_salvar(string $fonte, array $itens): int {
    $novas = 0;
    foreach ($itens as $n) {
        if ($n['titulo'] === '' || !preg_match('#^https://#', $n['link'])) continue;
        if (strtotime($n['publicada_em']) > time() + 3600) $n['publicada_em'] = date('Y-m-d H:i:s');
        $novas += q('INSERT IGNORE INTO noticias (fonte, titulo, link, link_hash, imagem, publicada_em) VALUES (?,?,?,?,?,?)',
            [$fonte, mb_substr($n['titulo'], 0, 300), mb_substr($n['link'], 0, 600), sha1($n['link']),
             $n['imagem'] && preg_match('#^https://#', $n['imagem']) ? mb_substr($n['imagem'], 0, 600) : null, $n['publicada_em']])->rowCount();
    }
    return $novas;
}

// Coleta se a última passou de 30 min. Uma requisição por vez (trava de arquivo); se outra já está coletando, segue sem esperar.
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
    ], linhas("SELECT * FROM noticias WHERE oculta = 0 ORDER BY publicada_em DESC LIMIT $limite"));
}

function noticias_quando(string $d): string {
    $s = time() - strtotime($d);
    if ($s < 3600) return 'agora há pouco';
    if ($s < 86400) return 'há ' . intdiv($s, 3600) . ' h';
    if ($s < 172800) return 'ontem';
    return date('d/m/Y', strtotime($d));
}
