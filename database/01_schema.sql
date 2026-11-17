-- =============================================================
-- Acolhe+  |  Script do banco de dados (MySQL 8.0 / InnoDB)
-- Plataforma web de integração de novos integrantes em ONGs
-- =============================================================
CREATE DATABASE IF NOT EXISTS acolhe_mais
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE acolhe_mais;
SET NAMES utf8mb4;

-- Organização (ONG) que utiliza a plataforma
CREATE TABLE organizacao (
  id_organizacao INT AUTO_INCREMENT PRIMARY KEY,
  nome           VARCHAR(150) NOT NULL,
  cnpj           CHAR(14) NULL UNIQUE,
  email          VARCHAR(150) NOT NULL,
  telefone       VARCHAR(20) NULL,
  area_atuacao   VARCHAR(100) NULL,
  data_cadastro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Usuários e perfis de acesso (RF-01)
CREATE TABLE usuario (
  id_usuario         INT AUTO_INCREMENT PRIMARY KEY,
  id_organizacao     INT NOT NULL,
  nome               VARCHAR(150) NOT NULL,
  email              VARCHAR(150) NOT NULL UNIQUE,
  telefone           VARCHAR(20) NULL,
  exibir_telefone    BOOLEAN NOT NULL DEFAULT FALSE,
  senha_hash         VARCHAR(255) NULL,
  perfil             ENUM('ADMINISTRADOR','COORDENADOR','PADRINHO','INTEGRANTE') NOT NULL,
  tipo_vinculo       ENUM('VOLUNTARIO','CONTRATADO','ESTAGIARIO','JOVEM_APRENDIZ') NOT NULL,
  funcao             VARCHAR(100) NULL,
  data_inicio        DATE NULL,
  recebe_lembretes   BOOLEAN NOT NULL DEFAULT TRUE,
  ativo              BOOLEAN NOT NULL DEFAULT TRUE,
  ultimo_acesso      DATETIME NULL,
  data_cadastro      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuario_org FOREIGN KEY (id_organizacao) REFERENCES organizacao(id_organizacao)
);

-- Categorias da central de conteúdos (RF-02)
CREATE TABLE categoria (
  id_categoria INT AUTO_INCREMENT PRIMARY KEY,
  nome         VARCHAR(80) NOT NULL UNIQUE,
  descricao    VARCHAR(255) NULL
);

-- Conteúdos (versão oficial vigente) (RF-02)
CREATE TABLE conteudo (
  id_conteudo      INT AUTO_INCREMENT PRIMARY KEY,
  id_organizacao   INT NOT NULL,
  id_categoria     INT NOT NULL,
  id_responsavel   INT NOT NULL,
  titulo           VARCHAR(150) NOT NULL,
  descricao        VARCHAR(255) NULL,
  tipo             ENUM('TEXTO','ARQUIVO','LINK') NOT NULL,
  corpo            TEXT NULL,
  caminho_arquivo  VARCHAR(255) NULL,
  url_externa      VARCHAR(255) NULL,
  versao           INT NOT NULL DEFAULT 1,
  publicado        BOOLEAN NOT NULL DEFAULT FALSE,
  data_atualizacao DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_conteudo_org  FOREIGN KEY (id_organizacao) REFERENCES organizacao(id_organizacao),
  CONSTRAINT fk_conteudo_cat  FOREIGN KEY (id_categoria)   REFERENCES categoria(id_categoria),
  CONSTRAINT fk_conteudo_resp FOREIGN KEY (id_responsavel) REFERENCES usuario(id_usuario),
  FULLTEXT KEY ft_conteudo (titulo, descricao, corpo)
);

-- Histórico de versões anteriores de cada conteúdo (RF-02)
CREATE TABLE conteudo_versao (
  id_conteudo_versao INT AUTO_INCREMENT PRIMARY KEY,
  id_conteudo        INT NOT NULL,
  id_editor          INT NOT NULL,
  versao             INT NOT NULL,
  titulo             VARCHAR(150) NOT NULL,
  corpo              TEXT NULL,
  data_versao        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_versao_conteudo FOREIGN KEY (id_conteudo) REFERENCES conteudo(id_conteudo),
  CONSTRAINT fk_versao_editor   FOREIGN KEY (id_editor)   REFERENCES usuario(id_usuario),
  UNIQUE KEY uk_conteudo_versao (id_conteudo, versao)
);

-- Trilhas de integração (RF-03)
CREATE TABLE trilha (
  id_trilha            INT AUTO_INCREMENT PRIMARY KEY,
  id_organizacao       INT NOT NULL,
  nome                 VARCHAR(120) NOT NULL,
  descricao            VARCHAR(255) NULL,
  liberacao_sequencial BOOLEAN NOT NULL DEFAULT TRUE,
  ativa                BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT fk_trilha_org FOREIGN KEY (id_organizacao) REFERENCES organizacao(id_organizacao)
);

-- Etapas da trilha (RF-03)
CREATE TABLE etapa (
  id_etapa           INT AUTO_INCREMENT PRIMARY KEY,
  id_trilha          INT NOT NULL,
  titulo             VARCHAR(120) NOT NULL,
  descricao          VARCHAR(255) NULL,
  ordem              TINYINT NOT NULL,
  tempo_estimado_min SMALLINT NULL,
  CONSTRAINT fk_etapa_trilha FOREIGN KEY (id_trilha) REFERENCES trilha(id_trilha),
  UNIQUE KEY uk_etapa_ordem (id_trilha, ordem)
);

-- Conteúdos que compõem cada etapa (N:N) (RF-03)
CREATE TABLE etapa_conteudo (
  id_etapa    INT NOT NULL,
  id_conteudo INT NOT NULL,
  ordem       TINYINT NOT NULL DEFAULT 1,
  PRIMARY KEY (id_etapa, id_conteudo),
  CONSTRAINT fk_ec_etapa    FOREIGN KEY (id_etapa)    REFERENCES etapa(id_etapa),
  CONSTRAINT fk_ec_conteudo FOREIGN KEY (id_conteudo) REFERENCES conteudo(id_conteudo)
);

-- Modelo de tarefa do checklist (RF-04)
CREATE TABLE tarefa (
  id_tarefa          INT AUTO_INCREMENT PRIMARY KEY,
  id_etapa           INT NOT NULL,
  titulo             VARCHAR(150) NOT NULL,
  descricao          VARCHAR(255) NULL,
  responsavel_tipo   ENUM('INTEGRANTE','PADRINHO','COORDENADOR') NOT NULL DEFAULT 'INTEGRANTE',
  prazo_dias         SMALLINT NOT NULL,
  exige_validacao    BOOLEAN NOT NULL DEFAULT FALSE,
  CONSTRAINT fk_tarefa_etapa FOREIGN KEY (id_etapa) REFERENCES etapa(id_etapa)
);

-- Integração: vínculo do novo integrante com a trilha e o padrinho (RF-03, RF-05, RF-06)
CREATE TABLE integracao (
  id_integracao        INT AUTO_INCREMENT PRIMARY KEY,
  id_integrante        INT NOT NULL,
  id_trilha            INT NOT NULL,
  id_padrinho          INT NULL,
  data_inicio          DATE NOT NULL,
  data_conclusao       DATE NULL,
  situacao             ENUM('EM_ANDAMENTO','CONCLUIDA','CANCELADA') NOT NULL DEFAULT 'EM_ANDAMENTO',
  nota_avaliacao       TINYINT NULL CHECK (nota_avaliacao BETWEEN 1 AND 5),
  comentario_avaliacao VARCHAR(500) NULL,
  CONSTRAINT fk_integ_integrante FOREIGN KEY (id_integrante) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_integ_trilha     FOREIGN KEY (id_trilha)     REFERENCES trilha(id_trilha),
  CONSTRAINT fk_integ_padrinho   FOREIGN KEY (id_padrinho)   REFERENCES usuario(id_usuario)
);

-- Tarefas geradas para cada integração, com prazo e status (RF-04, RF-05)
CREATE TABLE tarefa_integracao (
  id_tarefa_integracao INT AUTO_INCREMENT PRIMARY KEY,
  id_integracao        INT NOT NULL,
  id_tarefa            INT NOT NULL,
  data_prazo           DATE NOT NULL,
  status               ENUM('PENDENTE','CONCLUIDA','ATRASADA') NOT NULL DEFAULT 'PENDENTE',
  data_conclusao       DATETIME NULL,
  id_concluido_por     INT NULL,
  id_validado_por      INT NULL,
  CONSTRAINT fk_ti_integracao FOREIGN KEY (id_integracao)    REFERENCES integracao(id_integracao),
  CONSTRAINT fk_ti_tarefa     FOREIGN KEY (id_tarefa)        REFERENCES tarefa(id_tarefa),
  CONSTRAINT fk_ti_concluido  FOREIGN KEY (id_concluido_por) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_ti_validado   FOREIGN KEY (id_validado_por)  REFERENCES usuario(id_usuario),
  UNIQUE KEY uk_ti (id_integracao, id_tarefa)
);

-- Registro de leitura dos conteúdos da trilha (RF-03, RF-05)
CREATE TABLE leitura_conteudo (
  id_integracao INT NOT NULL,
  id_conteudo   INT NOT NULL,
  data_leitura  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_integracao, id_conteudo),
  CONSTRAINT fk_lc_integracao FOREIGN KEY (id_integracao) REFERENCES integracao(id_integracao),
  CONSTRAINT fk_lc_conteudo   FOREIGN KEY (id_conteudo)   REFERENCES conteudo(id_conteudo)
);

-- Observações de acompanhamento do padrinho/coordenador (RF-06)
CREATE TABLE observacao (
  id_observacao INT AUTO_INCREMENT PRIMARY KEY,
  id_integracao INT NOT NULL,
  id_autor      INT NOT NULL,
  texto         VARCHAR(1000) NOT NULL,
  data_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_obs_integracao FOREIGN KEY (id_integracao) REFERENCES integracao(id_integracao),
  CONSTRAINT fk_obs_autor      FOREIGN KEY (id_autor)      REFERENCES usuario(id_usuario)
);

-- Perguntas frequentes (RF-07)
CREATE TABLE faq (
  id_faq         INT AUTO_INCREMENT PRIMARY KEY,
  id_organizacao INT NOT NULL,
  id_categoria   INT NOT NULL,
  pergunta       VARCHAR(255) NOT NULL,
  resposta       TEXT NOT NULL,
  ativo          BOOLEAN NOT NULL DEFAULT TRUE,
  CONSTRAINT fk_faq_org FOREIGN KEY (id_organizacao) REFERENCES organizacao(id_organizacao),
  CONSTRAINT fk_faq_cat FOREIGN KEY (id_categoria)   REFERENCES categoria(id_categoria),
  FULLTEXT KEY ft_faq (pergunta, resposta)
);

-- Dúvidas enviadas pelos integrantes (RF-07)
CREATE TABLE duvida (
  id_duvida      INT AUTO_INCREMENT PRIMARY KEY,
  id_autor       INT NOT NULL,
  id_respondente INT NULL,
  id_faq_gerada  INT NULL,
  pergunta       VARCHAR(500) NOT NULL,
  resposta       TEXT NULL,
  status         ENUM('ABERTA','RESPONDIDA') NOT NULL DEFAULT 'ABERTA',
  data_envio     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  data_resposta  DATETIME NULL,
  CONSTRAINT fk_duv_autor FOREIGN KEY (id_autor)       REFERENCES usuario(id_usuario),
  CONSTRAINT fk_duv_resp  FOREIGN KEY (id_respondente) REFERENCES usuario(id_usuario),
  CONSTRAINT fk_duv_faq   FOREIGN KEY (id_faq_gerada)  REFERENCES faq(id_faq)
);

-- Termos (adesão ao voluntariado, conduta, privacidade, proteção à criança) (RF-08)
CREATE TABLE termo (
  id_termo         INT AUTO_INCREMENT PRIMARY KEY,
  id_organizacao   INT NOT NULL,
  tipo             ENUM('ADESAO_VOLUNTARIADO','CODIGO_CONDUTA','PRIVACIDADE','PROTECAO_CRIANCA') NOT NULL,
  titulo           VARCHAR(150) NOT NULL,
  texto            TEXT NOT NULL,
  versao           INT NOT NULL DEFAULT 1,
  obrigatorio      BOOLEAN NOT NULL DEFAULT TRUE,
  vigente          BOOLEAN NOT NULL DEFAULT TRUE,
  data_publicacao  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_termo_org FOREIGN KEY (id_organizacao) REFERENCES organizacao(id_organizacao)
);

-- Aceites dos termos (registro imutável) (RF-08)
CREATE TABLE aceite_termo (
  id_aceite   INT AUTO_INCREMENT PRIMARY KEY,
  id_termo    INT NOT NULL,
  id_usuario  INT NOT NULL,
  data_hora   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  endereco_ip VARCHAR(45) NOT NULL,
  CONSTRAINT fk_aceite_termo   FOREIGN KEY (id_termo)   REFERENCES termo(id_termo),
  CONSTRAINT fk_aceite_usuario FOREIGN KEY (id_usuario) REFERENCES usuario(id_usuario),
  UNIQUE KEY uk_aceite (id_termo, id_usuario)
);

-- Carga inicial das categorias da central de conteúdos
INSERT INTO categoria (nome, descricao) VALUES
 ('Sobre a ONG',              'Missão, causa, público atendido e história'),
 ('Processos e rotinas',      'Procedimentos oficiais e rotinas de cada área'),
 ('Regras, valores e conduta','Valores, combinados e código de conduta'),
 ('Quem é quem',              'Contatos e organograma'),
 ('Ferramentas e acessos',    'Sistemas, planilhas, grupos e como obter acesso'),
 ('O que se espera de você',  'Funções, escalas, turnos e expectativas');
