-- Aba Notícias: manchetes coletadas automaticamente (só título, imagem e link para a matéria original)
CREATE TABLE IF NOT EXISTS noticias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fonte VARCHAR(60) NOT NULL,
  titulo VARCHAR(300) NOT NULL,
  link VARCHAR(600) NOT NULL,
  link_hash CHAR(40) NOT NULL UNIQUE,
  imagem VARCHAR(600) NULL,
  publicada_em DATETIME NOT NULL,
  coletada_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  oculta TINYINT(1) NOT NULL DEFAULT 0,       -- o admin pode esconder uma manchete
  KEY (oculta, publicada_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
