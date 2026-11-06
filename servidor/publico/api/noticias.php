<?php
require __DIR__ . '/../../inc/api.php';
require __DIR__ . '/../../inc/noticias.php';
metodo('GET');
noticias_atualizar();
responder(['noticias' => noticias_lista()]);
