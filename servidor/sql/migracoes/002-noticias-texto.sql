-- Notícia aberta dentro do app: resumo (RSS) e texto completo em blocos (quando a fonte permite reproduzir)
ALTER TABLE noticias
  ADD COLUMN resumo TEXT NULL AFTER imagem,
  ADD COLUMN corpo MEDIUMTEXT NULL AFTER resumo,              -- JSON: [{"t":"p","x":"..."},{"t":"sub","x":"..."},{"t":"img","src":"..."}]
  ADD COLUMN completa TINYINT(1) NOT NULL DEFAULT 0 AFTER corpo, -- 1 = texto completo reproduzido (com crédito e link)
  ADD COLUMN tentativas TINYINT NOT NULL DEFAULT 0 AFTER completa;
UPDATE noticias SET fonte = 'Prefeitura de São João da Boa Vista' WHERE fonte = 'Prefeitura de São João';
