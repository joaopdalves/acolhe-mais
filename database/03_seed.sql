-- =============================================================
-- Acolhe+ | Dados de demonstração (ONG fictícia "ONG Esperança")
-- Senha de todos os usuários: acolhe123
-- As datas são relativas ao dia em que o script roda, para que sempre
-- exista uma tarefa atrasada e outra perto do vencimento.
-- =============================================================
USE acolhe_mais;
SET NAMES utf8mb4;

SET @senha = '$2y$10$knGFICXas9Ka3nNoPoy.Ke/FGLyMeUpoALF8U3yd3AePkUxxDneD6';

INSERT INTO organizacao (id_organizacao, nome, email, area_atuacao)
VALUES (1, 'ONG Esperança', 'contato@ongesperanca.org', 'Educação e reforço escolar');

INSERT INTO usuario (id_usuario, id_organizacao, nome, email, senha_hash, perfil, tipo_vinculo, funcao, data_inicio, ultimo_acesso) VALUES
 (1, 1, 'Gustavo Mota',   'gustavo@ongesperanca.org', @senha, 'COORDENADOR', 'VOLUNTARIO',     'Coordenação de voluntários', '2024-02-01', NOW()),
 (2, 1, 'Matheus Luna',   'matheus@ongesperanca.org', @senha, 'PADRINHO',    'VOLUNTARIO',     'Reforço escolar',            '2024-03-10', NOW()),
 (3, 1, 'João Domingues', 'joao@ongesperanca.org',    @senha, 'INTEGRANTE',  'VOLUNTARIO',     'Reforço escolar',            DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY)),
 (4, 1, 'Jailson Mendes', 'jailson@ongesperanca.org', @senha, 'INTEGRANTE',  'JOVEM_APRENDIZ', 'Reforço escolar',            DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(NOW(), INTERVAL 8 DAY)),
 (5, 1, 'Paulo Guina',    'paulo@ongesperanca.org',   @senha, 'INTEGRANTE',  'VOLUNTARIO',     'Reforço escolar',            DATE_SUB(CURDATE(), INTERVAL 2 DAY), NOW());

-- Central de conteúdos (versão oficial vigente)
INSERT INTO conteudo (id_conteudo, id_organizacao, id_categoria, id_responsavel, titulo, tipo, corpo, versao, publicado) VALUES
 (1, 1, 1, 1, 'Nossa história e missão', 'TEXTO',
  'A ONG Esperança nasceu em 2009 e hoje atende 120 crianças no contraturno escolar, com reforço em português e matemática.', 2, 1),
 (2, 1, 3, 1, 'Código de convivência da ONG', 'TEXTO',
  'Pontualidade: respeite os horários da escala. Respeito: trate alunos e equipe com gentileza. Uso de imagem: fotos só com autorização da coordenação.', 1, 1),
 (3, 1, 4, 1, 'Quem procurar para cada assunto', 'TEXTO',
  'Escala e faltas: Gustavo (coordenação). Materiais e chaves: recepção. Dúvidas do dia a dia: seu padrinho.', 3, 1),
 (4, 1, 2, 2, 'Rotina de uma aula de reforço', 'TEXTO',
  'Chegada dos alunos às 14h, lanche às 14h15, atividade em duplas até 15h45 e registro de presença no fim da aula.', 1, 1);

-- Trilha "Reforço Escolar" com 3 etapas e liberação sequencial
INSERT INTO trilha (id_trilha, id_organizacao, nome, descricao, liberacao_sequencial)
VALUES (1, 1, 'Reforço Escolar', 'Integração de voluntários do reforço escolar', 1);

INSERT INTO etapa (id_etapa, id_trilha, titulo, ordem, tempo_estimado_min) VALUES
 (1, 1, 'Conhecendo a ONG', 1, 5),
 (2, 1, 'Regras e conduta', 2, 5),
 (3, 1, 'Tarefas de chegada', 3, 10);

INSERT INTO etapa_conteudo (id_etapa, id_conteudo, ordem) VALUES (1, 1, 1), (2, 2, 1), (2, 3, 2), (3, 4, 1);

INSERT INTO tarefa (id_tarefa, id_etapa, titulo, descricao, responsavel_tipo, prazo_dias, exige_validacao) VALUES
 (1, 1, 'Entrar no grupo da equipe',             'Peça o link ao seu padrinho.',                 'INTEGRANTE',  1,  0),
 (2, 3, 'Enviar cópia do RG para a secretaria',  'Pode ser foto pelo e-mail da secretaria.',     'INTEGRANTE',  8,  0),
 (3, 3, 'Retirar o crachá na secretaria',        'Segunda a sexta, das 8h às 17h.',              'INTEGRANTE',  11, 0),
 (4, 3, 'Treinamento presencial com o padrinho', 'Acompanhe uma aula inteira com o padrinho.',   'INTEGRANTE',  14, 1),
 (5, 3, 'Liberar acesso à planilha de escala',   'Feito pela coordenação.',                      'COORDENADOR', 10, 0);

-- Termos obrigatórios (RF08)
INSERT INTO termo (id_termo, id_organizacao, tipo, titulo, texto, versao) VALUES
 (1, 1, 'ADESAO_VOLUNTARIADO', 'Termo de adesão ao serviço voluntário',
  'Entidade: ONG Esperança. Objeto: apoio no reforço escolar, sem vínculo empregatício e sem remuneração (Lei nº 9.608/1998). Condições: 4 horas por semana.', 1),
 (2, 1, 'CODIGO_CONDUTA', 'Código de conduta',
  'Pontualidade com a escala, respeito com alunos e equipe e uso de imagem somente com autorização da coordenação.', 1),
 (3, 1, 'PROTECAO_CRIANCA', 'Compromisso de proteção à criança e ao adolescente',
  'Nunca ficar a sós com um aluno em local fechado, não compartilhar contatos pessoais com menores e comunicar à coordenação qualquer suspeita de violência (ECA – Lei nº 8.069/1990).', 1),
 (4, 1, 'PRIVACIDADE', 'Consentimento para uso dos dados (LGPD)',
  'Usamos seu nome, e-mail e telefone apenas para organizar sua integração. Você pode pedir a exclusão dos dados a qualquer momento.', 1);

-- Integrações: João (padrinho Matheus), Jailson (sem padrinho, sem acesso há 8 dias), Paulo (padrinho Matheus)
INSERT INTO integracao (id_integracao, id_integrante, id_trilha, id_padrinho, data_inicio)
SELECT 1, 3, 1, 2, data_inicio FROM usuario WHERE id_usuario = 3;
INSERT INTO integracao (id_integracao, id_integrante, id_trilha, id_padrinho, data_inicio)
SELECT 2, 4, 1, NULL, data_inicio FROM usuario WHERE id_usuario = 4;
INSERT INTO integracao (id_integracao, id_integrante, id_trilha, id_padrinho, data_inicio)
SELECT 3, 5, 1, 2, data_inicio FROM usuario WHERE id_usuario = 5;

-- Tarefas geradas para cada integração, com o prazo calculado a partir da data de início
INSERT INTO tarefa_integracao (id_integracao, id_tarefa, data_prazo)
SELECT i.id_integracao, t.id_tarefa, DATE_ADD(i.data_inicio, INTERVAL t.prazo_dias DAY)
  FROM integracao i CROSS JOIN tarefa t;

-- Progresso inicial do João: etapa 1 concluída (leu o conteúdo e entrou no grupo) e termos já aceitos
INSERT INTO leitura_conteudo (id_integracao, id_conteudo, data_leitura) VALUES (1, 1, DATE_SUB(NOW(), INTERVAL 8 DAY));
UPDATE tarefa_integracao SET status = 'CONCLUIDA', data_conclusao = DATE_SUB(NOW(), INTERVAL 8 DAY), id_concluido_por = 3
 WHERE id_integracao = 1 AND id_tarefa = 1;
INSERT INTO aceite_termo (id_termo, id_usuario, data_hora, endereco_ip)
SELECT id_termo, 3, DATE_SUB(NOW(), INTERVAL 9 DAY), '189.45.67.12' FROM termo;
INSERT INTO aceite_termo (id_termo, id_usuario, data_hora, endereco_ip)
SELECT id_termo, 4, DATE_SUB(NOW(), INTERVAL 12 DAY), '189.45.120.3' FROM termo;
-- Paulo ainda não aceitou os termos: no primeiro acesso, cai na tela de termos.
