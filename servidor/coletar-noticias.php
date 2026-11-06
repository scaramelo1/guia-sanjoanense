<?php
// Coleta agendada das notícias (cron em /etc/cron.d/guia-sanjoanense). Só roda pela linha de comando.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/inc/base.php';
require __DIR__ . '/inc/noticias.php';
noticias_atualizar(true);
echo date('Y-m-d H:i') . ' notícias: ' . valor('SELECT COUNT(*) FROM noticias') . ' no total, ' . valor('SELECT COUNT(*) FROM noticias WHERE completa = 1') . " com texto\n";
