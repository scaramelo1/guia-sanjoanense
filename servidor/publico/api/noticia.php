<?php
require __DIR__ . '/../../inc/api.php';
require __DIR__ . '/../../inc/noticias.php';
metodo('GET');
responder(['noticia' => noticia_completa((int)($_GET['id'] ?? 0)) ?? erro('Notícia não encontrada.', 404)]);
