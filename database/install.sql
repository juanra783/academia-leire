DROP TABLE IF EXISTS scan_attempts;
DROP TABLE IF EXISTS scanned_materials;
DROP TABLE IF EXISTS user_rewards;
DROP TABLE IF EXISTS reward_items;
DROP TABLE IF EXISTS daily_tasks;
DROP TABLE IF EXISTS daily_rewards;
DROP TABLE IF EXISTS mascots;
DROP TABLE IF EXISTS adventure_worlds;
DROP TABLE IF EXISTS ai_usage;
DROP TABLE IF EXISTS ai_logs;
DROP TABLE IF EXISTS xp_history;
DROP TABLE IF EXISTS student_profiles;
DROP TABLE IF EXISTS achievements;
DROP TABLE IF EXISTS topic_files;
DROP TABLE IF EXISTS results;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS topics;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  username VARCHAR(80) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','student') NOT NULL DEFAULT 'student',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE student_profiles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  avatar VARCHAR(255) NOT NULL DEFAULT 'assets/img/avatar-leire.svg',
  level INT NOT NULL DEFAULT 1,
  xp INT NOT NULL DEFAULT 0,
  coins INT NOT NULL DEFAULT 0,
  daily_streak INT NOT NULL DEFAULT 0,
  weekly_goal INT NOT NULL DEFAULT 5,
  last_activity DATE NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_student_profiles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE xp_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  result_id INT NULL,
  reason VARCHAR(180) NOT NULL,
  xp INT NOT NULL DEFAULT 0,
  coins INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_xp_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE topics (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject VARCHAR(120) NOT NULL,
  course VARCHAR(80) NOT NULL DEFAULT '4º Primaria Andalucía',
  title VARCHAR(180) NOT NULL,
  description TEXT NULL,
  content MEDIUMTEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE topic_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  topic_id INT NOT NULL,
  kind ENUM('libro','apunte') NOT NULL DEFAULT 'libro',
  title VARCHAR(180) NULL,
  notes TEXT NULL,
  file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NULL,
  size_bytes INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_topic_files_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE questions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  topic_id INT NOT NULL,
  type ENUM('text','multiple') NOT NULL DEFAULT 'text',
  question TEXT NOT NULL,
  options_json JSON NULL,
  correct_answer VARCHAR(255) NOT NULL,
  explanation TEXT NOT NULL,
  difficulty TINYINT NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_questions_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  topic_id INT NOT NULL,
  mode ENUM('deberes','examen','repaso','batalla') NOT NULL DEFAULT 'deberes',
  total_questions INT NOT NULL,
  correct_questions INT NOT NULL,
  score DECIMAL(4,2) NOT NULL,
  details_json JSON NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_results_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_results_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE achievements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(160) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(16) NOT NULL DEFAULT '⭐',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_achievements_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users(name, username, password_hash, role) VALUES
('Juanra', 'admin', '$2y$12$DMB8ne75AY9cE.ykoWtLgePOzVZBU73TmgHh41CEZw6rOrItHXxzu', 'admin'),
('Leire', 'leire', '$2y$12$NbH4E/l8d0d70Sm3i6JljuJUDVV1z22CB4jtelUq/tDbwp5aGtRJS', 'student');

INSERT INTO student_profiles(user_id, avatar, level, xp, coins, daily_streak, weekly_goal)
SELECT id, 'assets/img/avatar-leire.svg', 1, 0, 50, 0, 5 FROM users WHERE username='leire';

INSERT INTO topics(subject, course, title, description, content) VALUES
('Matemáticas', '4º Primaria Andalucía', 'Multiplicaciones y divisiones', 'Repaso de operaciones básicas con explicación.', 'Multiplicar es sumar varias veces el mismo número. Dividir es repartir en partes iguales.'),
('Lengua', '4º Primaria Andalucía', 'Ortografía: b y v', 'Ejercicios para distinguir palabras con b y v.', 'Se escriben con b algunas formas de verbos terminados en -bir, excepto vivir, servir y hervir.'),
('Conocimiento del Medio', '4º Primaria Andalucía', 'Los seres vivos', 'Clasificación básica de seres vivos.', 'Los seres vivos nacen, crecen, se reproducen y mueren. Pueden ser animales, plantas, hongos y otros organismos.'),
('Inglés', '4º Primaria Andalucía', 'Basic vocabulary', 'Vocabulario básico en inglés.', 'Repaso de palabras sencillas: colours, numbers, family and school objects.');

INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES
(1,'text','Calcula: 7 × 8',NULL,'56','7 × 8 significa sumar 7 ocho veces, o recordar la tabla del 7. El resultado es 56.',1),
(1,'text','Calcula: 48 ÷ 6',NULL,'8','Dividir 48 entre 6 es buscar qué número multiplicado por 6 da 48. Como 6 × 8 = 48, la respuesta es 8.',1),
(1,'text','Si tienes 5 cajas con 9 lápices cada una, ¿cuántos lápices hay en total?',NULL,'45','Hay que multiplicar 5 × 9 porque son 5 grupos de 9 lápices. 5 × 9 = 45.',1),
(1,'multiple','¿Cuál es el resultado de 63 ÷ 7?','["8","9","7","10"]','9','Como 7 × 9 = 63, entonces 63 ÷ 7 = 9.',1),
(1,'text','Calcula: 12 × 4',NULL,'48','12 × 4 es sumar 12 cuatro veces: 12 + 12 + 12 + 12 = 48.',1),
(1,'multiple','¿Qué operación usarías para repartir 36 caramelos entre 4 niños?','["36 + 4","36 - 4","36 ÷ 4","36 × 4"]','36 ÷ 4','Cuando repartimos en partes iguales usamos la división.',1),
(2,'multiple','Elige la palabra bien escrita.','["baca","vaca","vaka","bakka"]','vaca','El animal se escribe “vaca” con v.',1),
(2,'multiple','Elige la palabra bien escrita.','["beber","veber","bebér","vevér"]','beber','“Beber” se escribe con b.',1),
(2,'text','Completa: Me gusta ___ agua después de correr.',NULL,'beber','La frase correcta es “beber agua”. Beber se escribe con b.',1),
(2,'multiple','¿Cuál está bien escrita?','["vivir","bivir","vibir","bibir"]','vivir','“Vivir” es una excepción y se escribe con v.',2),
(3,'multiple','¿Cuál de estas opciones es una característica de los seres vivos?','["No cambian nunca","Nacen, crecen, se reproducen y mueren","No necesitan nada","Son siempre animales"]','Nacen, crecen, se reproducen y mueren','Los seres vivos tienen un ciclo vital: nacen, crecen, se reproducen y mueren.',1),
(3,'multiple','¿Una planta es un ser vivo?','["Sí","No","Solo si tiene flores","Solo si está en una maceta"]','Sí','Las plantas son seres vivos porque nacen, crecen, se alimentan, se reproducen y mueren.',1),
(3,'text','¿Qué necesitan las plantas para vivir? Escribe una cosa.',NULL,'agua','Una respuesta válida es agua. También necesitan luz, aire y sales minerales.',1),
(4,'multiple','What colour is “red”?','["rojo","azul","verde","amarillo"]','rojo','“Red” significa rojo en español.',1),
(4,'text','Translate: cat',NULL,'gato','“Cat” en español significa gato.',1),
(4,'multiple','Choose the correct translation: school','["casa","colegio","perro","mesa"]','colegio','“School” significa colegio.',1);


-- Temario extra V3.1 Andalucía
INSERT INTO topics(subject, course, title, description, content) VALUES
('Matemáticas', '4º Primaria Andalucía', 'Fracciones básicas', 'Numerador, denominador y fracciones equivalentes.', 'Una fracción representa una parte de un total. El numerador indica las partes que tomamos y el denominador las partes iguales en que se divide el total.'),
('Matemáticas', '4º Primaria Andalucía', 'Problemas de dos operaciones', 'Leer, elegir datos y resolver paso a paso.', 'Para resolver problemas: 1) leo despacio, 2) subrayo datos, 3) pienso qué me preguntan, 4) hago operaciones, 5) escribo la respuesta.'),
('Lengua', '4º Primaria Andalucía', 'Sustantivos y adjetivos', 'Gramática básica para reconocer palabras.', 'El sustantivo nombra personas, animales, cosas o lugares. El adjetivo dice cómo es el sustantivo.'),
('Lengua', '4º Primaria Andalucía', 'Comprensión lectora', 'Leer textos y responder con frases completas.', 'Para comprender un texto conviene leer dos veces: una para entender la historia y otra para buscar detalles importantes.'),
('Conocimiento del Medio', '4º Primaria Andalucía', 'Ecosistemas', 'Seres vivos, medio físico y relaciones.', 'Un ecosistema está formado por los seres vivos, el medio físico y las relaciones entre todos ellos.'),
('Conocimiento del Medio', '4º Primaria Andalucía', 'Andalucía: provincias y paisaje', 'Repaso de provincias, ríos, costas y montañas.', 'Andalucía tiene ocho provincias: Almería, Cádiz, Córdoba, Granada, Huelva, Jaén, Málaga y Sevilla.'),
('Inglés', '4º Primaria Andalucía', 'Daily routines', 'Rutinas diarias en inglés.', 'Daily routines: wake up, have breakfast, go to school, do homework, have dinner and go to bed.');

INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES
(5,'multiple','En una fracción, ¿cómo se llama el número de abajo?','["Numerador","Denominador","Resultado","Divisor"]','Denominador','El denominador indica en cuántas partes iguales se divide el total.',1),
(5,'text','¿Qué fracción representa una de cuatro partes iguales?',NULL,'1/4|un cuarto','Una parte de cuatro se escribe 1/4 y se lee un cuarto.',1),
(5,'multiple','¿Cuál es equivalente a 1/2?','["2/4","1/3","3/5","2/3"]','2/4','2/4 es equivalente a 1/2 porque representa la mitad.',2),
(6,'text','Ana tiene 3 bolsas con 8 canicas y compra 5 más. ¿Cuántas tiene?',NULL,'29','Primero 3 × 8 = 24. Después 24 + 5 = 29.',2),
(6,'multiple','Para resolver un problema, ¿qué conviene hacer primero?','["Escribir cualquier operación","Leer despacio el enunciado","Mirar la respuesta","Saltarlo"]','Leer despacio el enunciado','Primero hay que entender qué datos hay y qué pregunta el problema.',1),
(7,'multiple','En “la casa grande”, ¿cuál es el adjetivo?','["la","casa","grande","casa grande"]','grande','Grande dice cómo es la casa, por eso es el adjetivo.',1),
(7,'text','Escribe un sustantivo de esta frase: “El perro pequeño corre”.',NULL,'perro','Perro es un sustantivo porque nombra un animal.',1),
(8,'multiple','¿Qué es una idea principal?','["Un detalle pequeño","Lo más importante del texto","Una palabra difícil","El título siempre"]','Lo más importante del texto','La idea principal resume de qué trata el texto.',1),
(9,'multiple','¿Qué elementos forman un ecosistema?','["Solo animales","Seres vivos, lugar y relaciones","Solo plantas","Solo agua"]','Seres vivos, lugar y relaciones','Un ecosistema incluye seres vivos, el medio físico y sus relaciones.',1),
(9,'text','Nombra un ser vivo de un ecosistema.',NULL,'animal|planta|hongo|árbol|arbol|pez|pájaro|pajaro','En un ecosistema puede haber plantas, animales, hongos y otros seres vivos.',1),
(10,'multiple','¿Cuántas provincias tiene Andalucía?','["6","7","8","9"]','8','Andalucía tiene ocho provincias.',1),
(10,'text','Escribe una provincia de Andalucía.',NULL,'almería|almeria|cádiz|cadiz|córdoba|cordoba|granada|huelva|jaén|jaen|málaga|malaga|sevilla','Cualquiera de las ocho provincias andaluzas es válida.',1),
(11,'multiple','What does “go to school” mean?','["ir al colegio","cenar","levantarse","jugar"]','ir al colegio','Go to school significa ir al colegio.',1),
(11,'text','Translate: breakfast',NULL,'desayuno','Breakfast significa desayuno.',1);


-- ===== V4.0 incluido para instalación limpia =====
-- Academia Leire V4.0 - Aventura + IA Luna + mascota + recompensas
CREATE TABLE IF NOT EXISTS ai_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  subject VARCHAR(120) NULL,
  question TEXT NOT NULL,
  answer MEDIUMTEXT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ai_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS ai_usage (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  request_type VARCHAR(40) NOT NULL DEFAULT 'texto',
  model VARCHAR(120) NULL,
  prompt_tokens INT NOT NULL DEFAULT 0,
  completion_tokens INT NOT NULL DEFAULT 0,
  total_tokens INT NOT NULL DEFAULT 0,
  estimated_cost_eur DECIMAL(12,6) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_ai_usage_user_date (user_id, created_at),
  INDEX idx_ai_usage_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mascots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL DEFAULT 'Draco',
  level INT NOT NULL DEFAULT 1,
  xp INT NOT NULL DEFAULT 0,
  mood VARCHAR(80) NOT NULL DEFAULT 'contento',
  skin VARCHAR(80) NOT NULL DEFAULT 'dragon_dorado',
  last_fed DATE NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_mascots_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS daily_rewards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  reward_date DATE NOT NULL,
  xp INT NOT NULL DEFAULT 0,
  coins INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_daily_reward(user_id, reward_date),
  CONSTRAINT fk_daily_rewards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS adventure_worlds (
  id INT AUTO_INCREMENT PRIMARY KEY,
  subject VARCHAR(120) NOT NULL,
  world_name VARCHAR(160) NOT NULL,
  icon VARCHAR(16) NOT NULL DEFAULT '⭐',
  description TEXT NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO mascots(user_id, name, level, xp, mood, skin)
SELECT id, 'Draco', 1, 0, 'contento', 'dragon_dorado' FROM users WHERE username='leire';

INSERT INTO adventure_worlds(subject, world_name, icon, description, sort_order)
SELECT 'Matemáticas','Bosque Matemático','➗','Operaciones, fracciones, problemas y retos de lógica.',1
WHERE NOT EXISTS (SELECT 1 FROM adventure_worlds WHERE world_name='Bosque Matemático');
INSERT INTO adventure_worlds(subject, world_name, icon, description, sort_order)
SELECT 'Lengua','Castillo de la Lengua','📖','Ortografía, gramática, lectura y escritura.',2
WHERE NOT EXISTS (SELECT 1 FROM adventure_worlds WHERE world_name='Castillo de la Lengua');
INSERT INTO adventure_worlds(subject, world_name, icon, description, sort_order)
SELECT 'Conocimiento del Medio','Laboratorio del Medio','🌍','Animales, plantas, Andalucía, mapas y ciencia.',3
WHERE NOT EXISTS (SELECT 1 FROM adventure_worlds WHERE world_name='Laboratorio del Medio');
INSERT INTO adventure_worlds(subject, world_name, icon, description, sort_order)
SELECT 'Inglés','Isla del Inglés','🇬🇧','Vocabulario, frases y pequeños diálogos.',4
WHERE NOT EXISTS (SELECT 1 FROM adventure_worlds WHERE world_name='Isla del Inglés');

UPDATE student_profiles SET avatar='assets/img/avatar-leire-main.jpg' WHERE avatar='assets/img/avatar-leire.svg';


-- ===== V4.1 incluido para instalación limpia =====
-- Academia Leire V4.1 - Entrenador inteligente + tienda ampliada + batalla Draco
CREATE TABLE IF NOT EXISTS reward_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  item_key VARCHAR(80) NOT NULL UNIQUE,
  category VARCHAR(60) NOT NULL,
  title VARCHAR(160) NOT NULL,
  description TEXT NULL,
  icon VARCHAR(16) NOT NULL DEFAULT '⭐',
  price INT NOT NULL DEFAULT 0,
  unlock_level INT NOT NULL DEFAULT 1,
  rarity VARCHAR(40) NOT NULL DEFAULT 'normal',
  active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_rewards (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  item_id INT NOT NULL,
  equipped TINYINT(1) NOT NULL DEFAULT 0,
  purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_user_item(user_id,item_id),
  CONSTRAINT fk_user_rewards_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_user_rewards_item FOREIGN KEY (item_id) REFERENCES reward_items(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS daily_tasks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  task_date DATE NOT NULL,
  task_key VARCHAR(80) NOT NULL,
  title VARCHAR(180) NOT NULL,
  subject VARCHAR(120) NULL,
  topic_id INT NULL,
  action_url VARCHAR(255) NOT NULL,
  xp_reward INT NOT NULL DEFAULT 0,
  coins_reward INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  completed_at DATETIME NULL,
  UNIQUE KEY uniq_daily_task(user_id,task_date,task_key),
  CONSTRAINT fk_daily_tasks_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_daily_tasks_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO reward_items(item_key,category,title,description,icon,price,unlock_level,rarity) VALUES
  ('marco_dorado','marco','Marco dorado','Un marco brillante para el perfil principal.','🟡',50,1,'normal'),
  ('marco_morado','marco','Marco morado mágico','Marco violeta suave para el avatar.','💜',70,2,'normal'),
  ('marco_arcoiris','marco','Marco arcoíris','Marco alegre para días especiales.','🌈',120,4,'raro'),
  ('marco_galaxia','marco','Marco galaxia','Fondo estrellado alrededor de Leire.','🌌',180,7,'epico'),
  ('fondo_castillo','fondo','Fondo castillo','Un castillo de cuento para la pantalla.','🏰',100,3,'normal'),
  ('fondo_bosque','fondo','Bosque matemático','Fondo del mundo de matemáticas.','🌳',80,2,'normal'),
  ('fondo_isla','fondo','Isla del Inglés','Fondo de isla para practicar inglés.','🏝️',90,2,'normal'),
  ('fondo_estrellas','fondo','Cielo de estrellas','Fondo nocturno tranquilo y bonito.','✨',130,5,'raro'),
  ('fondo_arcoiris','fondo','Parque arcoíris','Un fondo alegre con colores suaves.','🎡',160,6,'raro'),
  ('draco_corona','draco','Corona de Draco','Draco se convierte en rey de la academia.','👑',150,5,'raro'),
  ('draco_alas','draco','Alas brillantes','Alas mágicas para Draco.','🪽',180,7,'epico'),
  ('draco_bufanda','draco','Bufanda azul','Bufanda suave para cuidar a Draco.','🧣',65,1,'normal'),
  ('draco_gafas','draco','Gafas sabias','Draco parece un profesor de ciencias.','🤓',110,4,'raro'),
  ('draco_armadura','draco','Armadura mini','Armadura de aventura para las batallas.','🛡️',220,9,'epico'),
  ('draco_fuego','draco','Fuego mágico','Efecto de fuego dorado para Draco.','🔥',250,10,'epico'),
  ('ropa_vestido_azul','ropa','Vestido azul aventura','Ropa azul para el personaje Roblox.','👗',90,2,'normal'),
  ('ropa_zapatillas','ropa','Zapatillas doradas','Zapatillas para correr por los mundos.','👟',85,2,'normal'),
  ('ropa_mochila','ropa','Mochila estrella','Mochila de exploradora para Leire.','🎒',120,4,'raro'),
  ('ropa_varita','ropa','Varita de Luna','Accesorio mágico para estudiar con Luna.','🪄',160,6,'raro'),
  ('ropa_diadema','ropa','Diadema brillante','Diadema bonita para el avatar jugable.','🎀',75,1,'normal'),
  ('ropa_capa','ropa','Capa aventurera','Capa para misiones difíciles.','🦸‍♀️',210,8,'epico'),
  ('pegatina_estrella','pegatina','Pegatina estrella','Una estrella para el álbum.','⭐',30,1,'normal'),
  ('pegatina_corazon','pegatina','Pegatina corazón','Pegatina tierna para recompensas.','💖',30,1,'normal'),
  ('pegatina_trofeo','pegatina','Pegatina trofeo','Para recordar una victoria.','🏆',60,3,'normal'),
  ('pegatina_llama','pegatina','Pegatina llama','Para rachas y días potentes.','🔥',60,3,'normal'),
  ('pegatina_libro','pegatina','Pegatina libro','Para lecturas completadas.','📚',45,2,'normal'),
  ('pegatina_luna','pegatina','Pegatina Luna','La profesora Luna en el álbum.','🌙',75,4,'raro'),
  ('bonus_pista','bonus','Pista extra','Recompensa simbólica para pedir una pista.','💡',40,1,'normal'),
  ('bonus_doble_moneda','bonus','Doble moneda imaginaria','Premio especial de motivación.','🪙',140,5,'raro'),
  ('bonus_salvar_racha','bonus','Salva racha','Objeto especial para no perder motivación.','🛟',200,8,'epico');


-- ===== V4.2 incluido para instalación limpia =====
-- Academia Leire V4.2 - Escanear y practicar: foto/ficha a ejercicios, examen y explicación
CREATE TABLE IF NOT EXISTS scanned_materials (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  title VARCHAR(180) NOT NULL,
  subject VARCHAR(120) NULL,
  source_type VARCHAR(40) NOT NULL DEFAULT 'foto',
  file_path VARCHAR(255) NULL,
  original_name VARCHAR(255) NULL,
  mime_type VARCHAR(120) NULL,
  size_bytes INT NOT NULL DEFAULT 0,
  extracted_text MEDIUMTEXT NULL,
  corrected_text MEDIUMTEXT NULL,
  generated_json JSON NULL,
  status VARCHAR(40) NOT NULL DEFAULT 'generado',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_scanned_user(user_id),
  CONSTRAINT fk_scanned_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS scan_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  material_id INT NOT NULL,
  user_id INT NOT NULL,
  mode VARCHAR(40) NOT NULL DEFAULT 'practica',
  total_questions INT NOT NULL DEFAULT 0,
  correct_questions INT NOT NULL DEFAULT 0,
  score DECIMAL(4,2) NOT NULL DEFAULT 0,
  details_json JSON NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_scan_attempts_user(user_id),
  CONSTRAINT fk_scan_attempts_material FOREIGN KEY (material_id) REFERENCES scanned_materials(id) ON DELETE CASCADE,
  CONSTRAINT fk_scan_attempts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Academia Leire V4.5 - Emilia, tienda personalizable e inventario por capas
-- Ejecutar después de update_v4_3.sql si vienes de una versión anterior.

CREATE TABLE IF NOT EXISTS mascots (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  name VARCHAR(80) NOT NULL DEFAULT 'Emilia',
  level INT NOT NULL DEFAULT 1,
  xp INT NOT NULL DEFAULT 0,
  mood VARCHAR(120) NOT NULL DEFAULT 'lista para estudiar',
  skin VARCHAR(80) NOT NULL DEFAULT 'emilia_base',
  last_fed DATE NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_mascots_user_v44_sql FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO mascots(user_id, name, level, xp, mood, skin)
SELECT id, 'Emilia', 1, 0, 'lista para estudiar', 'emilia_base' FROM users WHERE username='leire';

UPDATE mascots
SET name='Emilia', skin='emilia_base', mood=IF(mood IN ('contento','feliz'), 'lista para estudiar', mood)
WHERE name='Draco' OR skin LIKE 'dragon%';

-- Se conservan objetos antiguos por historial, pero se ocultan de la tienda.
UPDATE reward_items SET active=0 WHERE category='draco' OR item_key LIKE 'draco_%';

INSERT IGNORE INTO reward_items(item_key,category,title,description,icon,price,unlock_level,rarity) VALUES
('emilia_gafas_redondas','emilia_gafas','Gafas redondas','Gafas redondas doradas para que Emilia parezca una profe divertida.','👓',40,1,'normal'),
('emilia_gafas_corazon','emilia_gafas','Gafas corazón','Gafas rosas con forma de corazón.','💗',70,2,'normal'),
('emilia_gafas_sol','emilia_gafas','Gafas de sol','Unas gafas modernas para recompensas especiales.','🕶️',95,4,'raro'),
('emilia_gafas_estrella','emilia_gafas','Gafas estrella','Gafas brillantes para días de nota alta.','⭐',140,7,'epico'),
('emilia_lazo_rosa','emilia_cabeza','Lazo rosa','Un lazo grande y alegre para Emilia.','🎀',35,1,'normal'),
('emilia_gorra_estrella','emilia_cabeza','Gorra estrella','Gorra rosa y azul con una estrella dorada.','🧢',60,2,'normal'),
('emilia_flor_amarilla','emilia_cabeza','Flor amarilla','Flor tropical para el pelo.','🌼',75,3,'normal'),
('emilia_sombrero_lila','emilia_cabeza','Sombrero lila','Sombrero elegante de color lila.','👒',115,5,'raro'),
('emilia_corona_suave','emilia_cabeza','Corona de logro','Corona dorada para los grandes objetivos.','👑',200,9,'epico'),
('emilia_look_colegio','emilia_ropa','Look colegio','Uniforme bonito para modo estudio.','🎒',90,2,'normal'),
('emilia_look_deportivo','emilia_ropa','Look deportivo','Chándal turquesa para retos rápidos.','🏃‍♀️',110,3,'normal'),
('emilia_sudadera_pastel','emilia_ropa','Sudadera pastel','Sudadera rosa y morada para el día a día.','🧥',125,4,'raro'),
('emilia_look_fiesta','emilia_ropa','Look fiesta brilli','Vestido morado brillante para celebraciones.','👗',180,7,'epico'),
('emilia_chaqueta_morada','emilia_ropa','Chaqueta morada','Chaqueta estilo app educativa con detalles dorados.','💜',150,6,'raro'),
('emilia_auriculares','emilia_extra','Auriculares pastel','Auriculares para escuchar lecturas y practicar inglés.','🎧',85,2,'normal'),
('emilia_mochila','emilia_extra','Mochila estrella','Mochila con estrellas para la aventura.','🎒',100,3,'normal'),
('emilia_collar_estrella','emilia_extra','Collar estrella','Collar dorado con una estrella.','📿',70,2,'normal'),
('emilia_pulsera','emilia_extra','Pulsera morada','Pulsera con cuentas moradas y doradas.','🟣',60,1,'normal'),
('emilia_microfono','emilia_extra','Micrófono pop','Micrófono para celebrar exámenes aprobados.','🎤',170,7,'epico');
-- ===== V4.6 CURRICULUM RESET =====
-- Academia Leire V4.6 - Banco curricular corregido 4º Primaria Andalucía
SET FOREIGN_KEY_CHECKS=0;
DELETE FROM results;
DELETE FROM topic_files;
DELETE FROM questions;
DELETE FROM topics;
ALTER TABLE topics AUTO_INCREMENT=1;
ALTER TABLE questions AUTO_INCREMENT=1;
SET FOREIGN_KEY_CHECKS=1;

INSERT INTO topics(subject, course, title, description, content) VALUES
('Matemáticas','4º Primaria Andalucía','01 · Números naturales y valor posicional','Lectura, escritura, descomposición, comparación y redondeo de números naturales hasta centenas de millar.','Un número se puede descomponer por unidades, decenas, centenas, millares, decenas de millar y centenas de millar. Comparar números ayuda a ordenarlos y el redondeo sirve para estimar.'),
('Matemáticas','4º Primaria Andalucía','02 · Sumas, restas y cálculo mental','Operaciones con números naturales, estimación y resolución de situaciones cotidianas.','Antes de operar conviene mirar si se puede calcular mentalmente o estimar. En problemas, lee la pregunta, localiza los datos y escribe una respuesta con unidades.'),
('Matemáticas','4º Primaria Andalucía','03 · Multiplicaciones','Multiplicación por una, dos y tres cifras, tablas y problemas de grupos iguales.','Multiplicar sirve para calcular grupos iguales. Para multiplicar por dos cifras se multiplican unidades y decenas y después se suman los productos parciales.'),
('Matemáticas','4º Primaria Andalucía','04 · Divisiones','División exacta y no exacta, reparto, agrupación y comprobación.','Dividir es repartir en partes iguales. Se comprueba con: divisor × cociente + resto = dividendo. El resto siempre debe ser menor que el divisor.'),
('Matemáticas','4º Primaria Andalucía','05 · Fracciones','Numerador, denominador, comparación de fracciones sencillas y fracciones equivalentes.','El denominador indica en cuántas partes iguales se divide la unidad y el numerador cuántas partes tomamos. Dos fracciones son equivalentes si representan la misma cantidad.'),
('Matemáticas','4º Primaria Andalucía','06 · Decimales y dinero','Décimas, centésimas, comparación de decimales y operaciones sencillas con euros y céntimos.','Los números decimales aparecen en medidas y dinero. En euros, 100 céntimos forman 1 euro. Para comparar decimales, mira primero la parte entera.'),
('Matemáticas','4º Primaria Andalucía','07 · Medidas de longitud','Metro, kilómetro, centímetro y milímetro. Cambios de unidad y problemas de longitud.','La unidad principal de longitud es el metro. 1 km = 1000 m, 1 m = 100 cm y 1 cm = 10 mm. Hay que elegir una unidad adecuada para cada objeto o distancia.'),
('Matemáticas','4º Primaria Andalucía','08 · Masa y capacidad','Kilogramo, gramo, litro, centilitro y mililitro. Comparación y problemas.','La masa mide cuánto pesa un objeto y la capacidad mide cuánto líquido cabe en un recipiente. 1 kg = 1000 g y 1 L = 1000 mL.'),
('Matemáticas','4º Primaria Andalucía','09 · Tiempo y dinero','Horas, minutos, días, calendarios, monedas y billetes.','1 hora tiene 60 minutos. Para calcular duraciones se puede avanzar en la línea del tiempo. Con dinero se suma lo pagado y se resta para calcular el cambio.'),
('Matemáticas','4º Primaria Andalucía','10 · Geometría: rectas, ángulos y polígonos','Rectas, segmentos, tipos de ángulos, triángulos, cuadriláteros y perímetros.','La geometría estudia formas y posiciones. El perímetro de una figura es la suma de las longitudes de todos sus lados.'),
('Matemáticas','4º Primaria Andalucía','11 · Simetría, coordenadas y orientación','Ejes de simetría, giros, coordenadas sencillas y orientación en planos.','Una figura es simétrica si puede dividirse en dos partes iguales que se reflejan. En un plano usamos filas, columnas o coordenadas para localizar puntos.'),
('Matemáticas','4º Primaria Andalucía','12 · Datos, tablas, gráficas y azar','Recogida de datos, tablas, diagramas de barras y probabilidad sencilla.','Las tablas y gráficas ayudan a ordenar información. En el azar, un suceso puede ser seguro, posible o imposible.'),
('Lengua','4º Primaria Andalucía','01 · Comprensión lectora','Lectura de textos, idea principal, detalles, inferencias y respuesta completa.','Para comprender un texto, lee con atención, localiza la idea principal, busca datos concretos y responde con frases completas.'),
('Lengua','4º Primaria Andalucía','02 · Sustantivos','Sustantivos comunes y propios, género y número.','Los sustantivos nombran personas, animales, objetos, lugares, ideas o sentimientos. Pueden ser comunes o propios, masculinos o femeninos, singulares o plurales.'),
('Lengua','4º Primaria Andalucía','03 · Adjetivos y concordancia','Adjetivos calificativos y concordancia con el sustantivo.','El adjetivo dice cómo es o cómo está el sustantivo. Debe concordar en género y número: niña simpática, niños simpáticos.'),
('Lengua','4º Primaria Andalucía','04 · Determinantes','Artículos, demostrativos, posesivos y numerales.','Los determinantes acompañan al sustantivo y concretan su significado: el libro, esta casa, mi mochila, tres lápices.'),
('Lengua','4º Primaria Andalucía','05 · Verbos','Infinitivo, persona, número y tiempos presente, pasado y futuro.','Los verbos expresan acciones o estados. Su infinitivo puede terminar en -ar, -er o -ir. Cambian según persona, número y tiempo.'),
('Lengua','4º Primaria Andalucía','06 · Ortografía: b/v, g/j, h, ll/y','Reglas y práctica de palabras frecuentes con ortografía dudosa.','La ortografía ayuda a escribir correctamente. Algunas palabras deben memorizarse y otras siguen reglas sencillas.'),
('Lengua','4º Primaria Andalucía','07 · Acentuación','Sílabas tónica, palabras agudas, llanas y esdrújulas.','La sílaba tónica se pronuncia con más fuerza. Las palabras agudas, llanas y esdrújulas siguen reglas de acentuación.'),
('Lengua','4º Primaria Andalucía','08 · Puntuación y diálogo','Punto, coma, dos puntos, signos de interrogación y exclamación, raya de diálogo.','Los signos de puntuación ordenan el texto y ayudan a leerlo con sentido. En español, preguntas y exclamaciones llevan signo de apertura y cierre.'),
('Lengua','4º Primaria Andalucía','09 · Vocabulario: sinónimos, antónimos y polisemia','Relaciones de significado y palabras con varios sentidos.','Los sinónimos tienen significado parecido, los antónimos significado contrario y las palabras polisémicas tienen varios significados.'),
('Lengua','4º Primaria Andalucía','10 · Familias de palabras, prefijos y sufijos','Palabras derivadas, primitivas, prefijos y sufijos.','Una familia de palabras comparte raíz. Los prefijos van delante y los sufijos detrás para formar palabras nuevas.'),
('Lengua','4º Primaria Andalucía','11 · Textos narrativos','Cuentos, personajes, narrador, inicio, nudo y desenlace.','Un texto narrativo cuenta una historia. Suele tener personajes, lugar, tiempo, problema y solución.'),
('Lengua','4º Primaria Andalucía','12 · Textos informativos y expresión escrita','Noticias, descripciones, cartas, resúmenes y textos ordenados.','Los textos informativos explican hechos o ideas. Para escribir bien conviene planificar, ordenar párrafos, revisar y corregir.'),
('Conocimiento del Medio','4º Primaria Andalucía','01 · Seres vivos y funciones vitales','Nutrición, relación, reproducción y clasificación básica de seres vivos.','Los seres vivos realizan funciones vitales: nutrición, relación y reproducción. Se pueden clasificar en animales, plantas, hongos y otros grupos.'),
('Conocimiento del Medio','4º Primaria Andalucía','02 · Animales vertebrados e invertebrados','Clasificación de animales y características principales.','Los vertebrados tienen columna vertebral y los invertebrados no. Los vertebrados se agrupan en mamíferos, aves, reptiles, anfibios y peces.'),
('Conocimiento del Medio','4º Primaria Andalucía','03 · Plantas','Partes de la planta, necesidades, reproducción y fotosíntesis básica.','Las plantas fabrican su alimento con luz, agua, sales minerales y dióxido de carbono. Sus partes principales son raíz, tallo, hojas, flores y frutos.'),
('Conocimiento del Medio','4º Primaria Andalucía','04 · Ecosistemas y cadenas alimentarias','Ecosistemas, hábitats, productores, consumidores y cuidado del medio.','Un ecosistema está formado por seres vivos, medio físico y relaciones. Las cadenas alimentarias muestran quién se alimenta de quién.'),
('Conocimiento del Medio','4º Primaria Andalucía','05 · El cuerpo humano y la salud','Aparatos del cuerpo, hábitos saludables, alimentación y prevención.','El cuerpo humano funciona gracias a aparatos y sistemas. Para cuidarlo necesitamos alimentación equilibrada, higiene, descanso y ejercicio.'),
('Conocimiento del Medio','4º Primaria Andalucía','06 · Materia y materiales','Propiedades, estados de la materia, cambios y uso responsable de materiales.','La materia tiene masa y ocupa espacio. Puede estar en estado sólido, líquido o gaseoso. Los materiales pueden ser naturales o artificiales.'),
('Conocimiento del Medio','4º Primaria Andalucía','07 · Energía, fuerzas y máquinas','Fuentes de energía, fuerzas, movimiento y máquinas simples.','La energía produce cambios. Las fuerzas pueden mover, detener o deformar objetos. Las máquinas simples facilitan trabajos.'),
('Conocimiento del Medio','4º Primaria Andalucía','08 · Tierra, agua y atmósfera','Planeta Tierra, ciclo del agua, tiempo atmosférico y protección del entorno.','La Tierra tiene agua, aire y suelo. El ciclo del agua incluye evaporación, condensación y precipitación. La atmósfera contiene el aire que respiramos.'),
('Conocimiento del Medio','4º Primaria Andalucía','09 · Mapas, relieve y paisajes','Planos, mapas, leyendas, relieve, ríos, costas y paisajes.','Los mapas representan territorios. El relieve incluye montañas, valles y llanuras. Los paisajes pueden ser de interior, costa, naturales o humanizados.'),
('Conocimiento del Medio','4º Primaria Andalucía','10 · Andalucía','Provincias, relieve, ríos, costas, espacios naturales y cultura andaluza.','Andalucía tiene ocho provincias. Cuenta con Sierra Morena, Sistemas Béticos, valle del Guadalquivir y costa mediterránea y atlántica.'),
('Conocimiento del Medio','4º Primaria Andalucía','11 · Población, municipios y sectores económicos','Localidad, municipio, servicios públicos, población y trabajos.','Vivimos en municipios con ayuntamiento y servicios. Los trabajos se agrupan en sector primario, secundario y terciario.'),
('Conocimiento del Medio','4º Primaria Andalucía','12 · Historia y patrimonio','Tiempo histórico, fuentes, etapas de la historia y patrimonio cultural.','La historia estudia el pasado mediante fuentes. El patrimonio cultural incluye monumentos, tradiciones, obras y restos que debemos cuidar.'),
('Inglés','4º Primaria Andalucía','01 · Greetings and introductions','Saludos, despedidas, nombre, edad y pequeñas conversaciones.','Practise: hello, goodbye, my name is, I am, how are you, please and thank you.'),
('Inglés','4º Primaria Andalucía','02 · Numbers, dates and time','Números, días, meses, fechas y horas sencillas.','Practise numbers, days of the week, months and simple time expressions.'),
('Inglés','4º Primaria Andalucía','03 · School and classroom objects','Objetos de clase, asignaturas e instrucciones de aula.','Practise school objects: pencil, book, ruler, rubber, classroom, teacher and subjects.'),
('Inglés','4º Primaria Andalucía','04 · Family and descriptions','Familia, aspecto físico y adjetivos sencillos.','Practise family words and descriptions with have got, has got and adjectives.'),
('Inglés','4º Primaria Andalucía','05 · Daily routines','Rutinas diarias y presente simple básico.','Practise daily routines: get up, have breakfast, go to school, do homework and go to bed.'),
('Inglés','4º Primaria Andalucía','06 · Food and healthy habits','Comida, gustos y hábitos saludables.','Practise food vocabulary, I like, I don’t like and healthy habits.'),
('Inglés','4º Primaria Andalucía','07 · Clothes and weather','Ropa, tiempo atmosférico y estaciones.','Practise clothes and weather: sunny, rainy, coat, T-shirt, shoes and seasons.'),
('Inglés','4º Primaria Andalucía','08 · House and prepositions','Partes de la casa, muebles y preposiciones de lugar.','Practise rooms, furniture and prepositions: in, on, under, next to, behind.'),
('Inglés','4º Primaria Andalucía','09 · Animals and nature','Animales, hábitats y descripciones sencillas.','Practise animal vocabulary and simple descriptions with can/can’t and has got.'),
('Inglés','4º Primaria Andalucía','10 · Places in town and directions','Lugares de la ciudad y direcciones básicas.','Practise places: park, supermarket, library, school, hospital and directions.'),
('Inglés','4º Primaria Andalucía','11 · Likes, hobbies and abilities','Gustos, aficiones y can/can’t.','Practise I like, I don’t like, can, can’t, sports and hobbies.'),
('Inglés','4º Primaria Andalucía','12 · Simple stories and past basics','Historias sencillas, conectores y pasado básico con was/were.','Practise short stories with first, then, finally and simple past expressions.');

INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES
(1,'multiple','¿Qué valor tiene la cifra 7 en el número 472.315?','["7 unidades", "7 decenas", "7 decenas de millar", "7 centenas"]','7 decenas de millar','En 472.315, el 7 está en la posición de decenas de millar, por eso vale 70.000.',1),
(1,'text','Escribe con cifras: trescientos cuarenta y dos mil ciento seis.',NULL,'342106|342.106','Trescientos cuarenta y dos mil son 342.000 y ciento seis es 106. Total: 342.106.',1),
(1,'multiple','¿Cuál es mayor?','["58.932", "58.293", "57.999", "58.329"]','58.932','Comparamos cifra a cifra: todos tienen 58 mil menos 57.999; 932 centenas/unidades supera a 329 y 293.',1),
(1,'text','Descompón 45.802 en forma de suma.',NULL,'40000+5000+800+2|40.000+5.000+800+2','45.802 se descompone en 40.000 + 5.000 + 800 + 2.',2),
(1,'multiple','Redondea 36.482 a la unidad de millar más cercana.','["36.000", "36.500", "37.000", "40.000"]','36.000','Miramos las centenas: 4 centenas es menor que 5, así que se queda en 36.000.',2),
(1,'text','¿Cuántas decenas tiene la cifra de las decenas en 23.845?',NULL,'4','La cifra de las decenas es la segunda empezando por la derecha en 23.845.',1),
(1,'text','¿Cuántas decenas tiene la cifra de las decenas en 67.019?',NULL,'1','La cifra de las decenas es la segunda empezando por la derecha en 67.019.',1),
(1,'text','¿Cuántas decenas tiene la cifra de las decenas en 104.506?',NULL,'0','La cifra de las decenas es la segunda empezando por la derecha en 104.506.',1),
(1,'text','¿Cuántas decenas tiene la cifra de las decenas en 90.580?',NULL,'8','La cifra de las decenas es la segunda empezando por la derecha en 90.580.',1),
(1,'text','¿Cuántas decenas tiene la cifra de las decenas en 389.201?',NULL,'0','La cifra de las decenas es la segunda empezando por la derecha en 389.201.',1),
(1,'text','¿Cuántas decenas tiene la cifra de las decenas en 720.036?',NULL,'3','La cifra de las decenas es la segunda empezando por la derecha en 720.036.',1),
(1,'text','¿Cuántas decenas tiene la cifra de las decenas en 500.214?',NULL,'1','La cifra de las decenas es la segunda empezando por la derecha en 500.214.',1),
(1,'text','¿Cuántas decenas tiene la cifra de las decenas en 99.108?',NULL,'0','La cifra de las decenas es la segunda empezando por la derecha en 99.108.',1),
(1,'multiple','Elige el signo correcto: 34.567 __ 35.467','[">", "<", "="]','<','Compara ambos números de izquierda a derecha hasta encontrar una cifra distinta.',1),
(1,'multiple','Elige el signo correcto: 78.210 __ 78.120','[">", "<", "="]','>','Compara ambos números de izquierda a derecha hasta encontrar una cifra distinta.',1),
(1,'multiple','Elige el signo correcto: 145.006 __ 145.060','[">", "<", "="]','<','Compara ambos números de izquierda a derecha hasta encontrar una cifra distinta.',1),
(1,'multiple','Elige el signo correcto: 90.876 __ 90.786','[">", "<", "="]','>','Compara ambos números de izquierda a derecha hasta encontrar una cifra distinta.',1),
(1,'multiple','Elige el signo correcto: 210.345 __ 201.345','[">", "<", "="]','>','Compara ambos números de izquierda a derecha hasta encontrar una cifra distinta.',1),
(1,'text','Redondea 148.760 a la decena de millar.',NULL,'150000|150.000','La cifra de los millares es 8, por eso 148.760 se redondea a 150.000.',2),
(1,'multiple','¿Qué número corresponde a 80.000 + 3.000 + 400 + 20 + 6?','["83.426", "80.342", "84.326", "83.462"]','83.426','Sumamos cada valor posicional: 80.000 + 3.000 + 400 + 20 + 6 = 83.426.',1),
(1,'multiple','Ordena de menor a mayor: 6.902, 6.290, 6.920. ¿Cuál va primero?','["6.902", "6.920", "6.290", "todos iguales"]','6.290','Todos tienen 6 millares; 290 es menor que 902 y 920.',1),
(2,'text','Calcula: 3.456 + 2.789',NULL,'6245','Suma unidades con unidades. decenas con decenas y recuerda llevar cuando corresponda. Resultado: 6.245.',1),
(2,'text','Calcula: 5.608 + 2.394',NULL,'8002','Suma unidades con unidades. decenas con decenas y recuerda llevar cuando corresponda. Resultado: 8.002.',1),
(2,'text','Calcula: 12.045 + 8.788',NULL,'20833','Suma unidades con unidades. decenas con decenas y recuerda llevar cuando corresponda. Resultado: 20.833.',1),
(2,'text','Calcula: 45.000 + 17.325',NULL,'62325','Suma unidades con unidades. decenas con decenas y recuerda llevar cuando corresponda. Resultado: 62.325.',1),
(2,'text','Calcula: 6.809 + 5.176',NULL,'11985','Suma unidades con unidades. decenas con decenas y recuerda llevar cuando corresponda. Resultado: 11.985.',1),
(2,'text','Calcula: 24.570 + 3.190',NULL,'27760','Suma unidades con unidades. decenas con decenas y recuerda llevar cuando corresponda. Resultado: 27.760.',1),
(2,'text','Calcula: 9.000 - 4.578',NULL,'4422','Resta de derecha a izquierda pidiendo prestado cuando sea necesario. Resultado: 4.422.',1),
(2,'text','Calcula: 15.320 - 6.865',NULL,'8455','Resta de derecha a izquierda pidiendo prestado cuando sea necesario. Resultado: 8.455.',1),
(2,'text','Calcula: 45.001 - 12.999',NULL,'32002','Resta de derecha a izquierda pidiendo prestado cuando sea necesario. Resultado: 32.002.',1),
(2,'text','Calcula: 7.200 - 3.856',NULL,'3344','Resta de derecha a izquierda pidiendo prestado cuando sea necesario. Resultado: 3.344.',1),
(2,'text','Calcula: 60.000 - 24.875',NULL,'35125','Resta de derecha a izquierda pidiendo prestado cuando sea necesario. Resultado: 35.125.',1),
(2,'text','Calcula: 100.000 - 56.432',NULL,'43568','Resta de derecha a izquierda pidiendo prestado cuando sea necesario. Resultado: 43.568.',1),
(2,'text','En una biblioteca había 2.345 libros y llegan 678 más. ¿Cuántos hay ahora?',NULL,'3023|3.023','Hay que sumar 2.345 + 678 = 3.023 libros.',1),
(2,'text','Un colegio tiene 1.250 alumnos. Hoy han faltado 87. ¿Cuántos han asistido?',NULL,'1163|1.163','Restamos los que faltan: 1.250 - 87 = 1.163.',1),
(2,'multiple','¿Cuál es una buena estimación de 398 + 602?','["900", "1.000", "1.100", "800"]','1.000','398 se aproxima a 400 y 602 a 600. 400 + 600 = 1.000.',1),
(2,'multiple','Para saber cuánto falta de 275 a 500, ¿qué operación haces?','["500 - 275", "500 + 275", "275 × 500", "275 ÷ 500"]','500 - 275','Cuando buscamos cuánto falta hasta una cantidad mayor, restamos.',1),
(2,'text','Calcula mentalmente: 4.800 + 200',NULL,'5000|5.000','Sumar 200 a 4.800 completa 5.000.',1),
(2,'text','Calcula mentalmente: 7.000 - 500',NULL,'6500|6.500','Quitar 500 a 7.000 deja 6.500.',1),
(2,'text','Marta tenía 35 pegatinas, compra 18 y regala 12. ¿Cuántas le quedan?',NULL,'41','Primero suma 35 + 18 = 53. Luego resta 53 - 12 = 41.',2),
(2,'multiple','¿Qué dato sobra? “Tengo 12 años, compro 3 cuadernos de 2 € y pago con 10 €.”','["12 años", "3 cuadernos", "2 €", "10 €"]','12 años','La edad no sirve para calcular el coste ni el cambio.',2),
(3,'text','Calcula: 34 × 6',NULL,'204','Multiplicamos por partes y sumamos los productos parciales. 34 × 6 = 204.',1),
(3,'text','Calcula: 128 × 4',NULL,'512','Multiplicamos por partes y sumamos los productos parciales. 128 × 4 = 512.',1),
(3,'text','Calcula: 207 × 5',NULL,'1035','Multiplicamos por partes y sumamos los productos parciales. 207 × 5 = 1035.',1),
(3,'text','Calcula: 46 × 23',NULL,'1058','Multiplicamos por partes y sumamos los productos parciales. 46 × 23 = 1058.',2),
(3,'text','Calcula: 73 × 28',NULL,'2044','Multiplicamos por partes y sumamos los productos parciales. 73 × 28 = 2044.',2),
(3,'text','Calcula: 215 × 32',NULL,'6880','Multiplicamos por partes y sumamos los productos parciales. 215 × 32 = 6880.',2),
(3,'text','Calcula: 346 × 7',NULL,'2422','Multiplicamos por partes y sumamos los productos parciales. 346 × 7 = 2422.',1),
(3,'text','Calcula: 509 × 8',NULL,'4072','Multiplicamos por partes y sumamos los productos parciales. 509 × 8 = 4072.',1),
(3,'text','Calcula: 123 × 45',NULL,'5535','Multiplicamos por partes y sumamos los productos parciales. 123 × 45 = 5535.',2),
(3,'text','Calcula: 302 × 16',NULL,'4832','Multiplicamos por partes y sumamos los productos parciales. 302 × 16 = 4832.',2),
(3,'text','Calcula: 48 × 25',NULL,'1200','Multiplicamos por partes y sumamos los productos parciales. 48 × 25 = 1200.',2),
(3,'text','Calcula: 601 × 13',NULL,'7813','Multiplicamos por partes y sumamos los productos parciales. 601 × 13 = 7813.',2),
(3,'text','Una caja tiene 24 rotuladores. ¿Cuántos rotuladores hay en 7 cajas?',NULL,'168','Son 7 grupos de 24: 24 × 7 = 168.',1),
(3,'text','En una excursión van 18 filas con 12 alumnos en cada fila. ¿Cuántos alumnos hay?',NULL,'216','Hay que multiplicar filas por alumnos de cada fila: 18 × 12 = 216.',2),
(3,'multiple','¿Qué significa 9 × 8?','["Sumar 9 ocho veces", "Restar 8 a 9", "Dividir 9 entre 8", "Sumar 9 + 8 una sola vez"]','Sumar 9 ocho veces','La multiplicación representa grupos iguales.',1),
(3,'multiple','¿Cuál es el doble de 346?','["592", "682", "692", "706"]','692','El doble es 346 × 2 = 692.',1),
(3,'multiple','¿Cuál es el triple de 125?','["250", "375", "300", "425"]','375','El triple es 125 × 3 = 375.',1),
(3,'text','Si un libro cuesta 13 € y compro 9 libros, ¿cuánto pago?',NULL,'117','13 × 9 = 117 euros.',1),
(3,'text','Calcula: 250 × 4',NULL,'1000|1.000','25 × 4 = 100; al tener un cero más, 250 × 4 = 1.000.',1),
(3,'multiple','En una multiplicación, ¿cómo se llaman los números que se multiplican?','["Sumandos", "Factores", "Restos", "Denominadores"]','Factores','Los números que se multiplican son factores; el resultado es el producto.',1),
(4,'text','Calcula: 56 ÷ 7',NULL,'8','Comprueba multiplicando: 8 × 7 = 56.',1),
(4,'text','Calcula: 96 ÷ 8',NULL,'12','Comprueba multiplicando: 12 × 8 = 96.',1),
(4,'text','Calcula: 144 ÷ 12',NULL,'12','Comprueba multiplicando: 12 × 12 = 144.',1),
(4,'text','Calcula: 324 ÷ 6',NULL,'54','Comprueba multiplicando: 54 × 6 = 324.',1),
(4,'text','Calcula: 728 ÷ 8',NULL,'91','Comprueba multiplicando: 91 × 8 = 728.',1),
(4,'text','Calcula: 945 ÷ 9',NULL,'105','Comprueba multiplicando: 105 × 9 = 945.',1),
(4,'text','Calcula: 1250 ÷ 5',NULL,'250','Comprueba multiplicando: 250 × 5 = 1250.',2),
(4,'text','Calcula: 840 ÷ 12',NULL,'70','Comprueba multiplicando: 70 × 12 = 840.',1),
(4,'text','Calcula: 1560 ÷ 13',NULL,'120','Comprueba multiplicando: 120 × 13 = 1560.',2),
(4,'text','Calcula: 2024 ÷ 4',NULL,'506','Comprueba multiplicando: 506 × 4 = 2024.',2),
(4,'text','Calcula: 369 ÷ 3',NULL,'123','Comprueba multiplicando: 123 × 3 = 369.',1),
(4,'text','Calcula: 735 ÷ 7',NULL,'105','Comprueba multiplicando: 105 × 7 = 735.',1),
(4,'text','Reparte 72 caramelos entre 9 niños. ¿Cuántos recibe cada uno?',NULL,'8','72 ÷ 9 = 8 caramelos cada uno.',1),
(4,'text','Hay 156 cromos y se guardan en sobres de 6. ¿Cuántos sobres se llenan?',NULL,'26','156 ÷ 6 = 26 sobres.',1),
(4,'multiple','En 47 ÷ 5, ¿cuál es el resto?','["0", "1", "2", "3"]','2','5 × 9 = 45 y 47 - 45 = 2. El resto es 2.',2),
(4,'multiple','¿Qué condición debe cumplir el resto de una división?','["Ser mayor que el divisor", "Ser igual al divisor", "Ser menor que el divisor", "Ser siempre cero"]','Ser menor que el divisor','Si el resto fuera igual o mayor que el divisor, podríamos seguir repartiendo.',2),
(4,'text','Comprueba la división: divisor 8, cociente 14 y resto 3. ¿Cuál es el dividendo?',NULL,'115','Dividendo = divisor × cociente + resto = 8 × 14 + 3 = 115.',2),
(4,'multiple','Para comprobar 84 ÷ 7 = 12, ¿qué multiplicación sirve?','["12 × 7 = 84", "84 × 7 = 12", "84 - 12 = 7", "7 + 12 = 84"]','12 × 7 = 84','La división se comprueba multiplicando divisor por cociente.',1),
(4,'text','En una carrera hay 128 corredores en grupos de 4. ¿Cuántos grupos hay?',NULL,'32','128 ÷ 4 = 32 grupos.',1),
(4,'text','Calcula: 1.008 ÷ 6',NULL,'168','6 × 168 = 1.008.',2),
(4,'multiple','¿Qué operación usamos para repartir en partes iguales?','["Suma", "Resta", "Multiplicación", "División"]','División','Repartir una cantidad en grupos iguales es dividir.',1),
(5,'multiple','En la fracción 3/8, ¿cuál es el numerador?','["3", "8", "11", "5"]','3','El numerador es el número de arriba.',1),
(5,'multiple','En la fracción 5/6, ¿qué indica el denominador?','["Las partes que tomamos", "Las partes iguales en que se divide la unidad", "El resultado de sumar", "Los lados de una figura"]','Las partes iguales en que se divide la unidad','El denominador indica el total de partes iguales.',1),
(5,'multiple','¿Cuál representa la mitad?','["1/2", "1/3", "2/5", "3/4"]','1/2','La mitad son una de dos partes iguales.',1),
(5,'multiple','¿Cuál es equivalente a 2/4?','["1/2", "1/3", "3/4", "2/5"]','1/2','2/4 y 1/2 representan la mitad.',1),
(5,'multiple','Si una pizza se divide en 8 partes y comes 3, ¿qué fracción comes?','["3/8", "8/3", "5/8", "3/5"]','3/8','Tomamos 3 partes de un total de 8.',1),
(5,'multiple','¿Qué fracción es mayor si tienen el mismo denominador: 5/9 o 2/9?','["5/9", "2/9", "son iguales", "no se puede saber"]','5/9','Con el mismo denominador, es mayor la que tiene numerador mayor.',1),
(5,'multiple','¿Cuál es menor: 1/6 o 1/3?','["1/6", "1/3", "son iguales", "ninguna"]','1/6','Si el numerador es 1, cuanto mayor es el denominador, más pequeña es la parte.',1),
(5,'text','Escribe la fracción “2 de 5 partes iguales”.',NULL,'2/5','Se escribe 2/5: 2 es el numerador y 5 el denominador.',1),
(5,'text','Escribe la fracción “4 de 10 partes iguales”.',NULL,'4/10','Se escribe 4/10: 4 es el numerador y 10 el denominador.',1),
(5,'text','Escribe la fracción “3 de 6 partes iguales”.',NULL,'3/6','Se escribe 3/6: 3 es el numerador y 6 el denominador.',1),
(5,'text','Escribe la fracción “6 de 8 partes iguales”.',NULL,'6/8','Se escribe 6/8: 6 es el numerador y 8 el denominador.',1),
(5,'text','Escribe la fracción “5 de 12 partes iguales”.',NULL,'5/12','Se escribe 5/12: 5 es el numerador y 12 el denominador.',1),
(5,'text','Escribe la fracción “7 de 10 partes iguales”.',NULL,'7/10','Se escribe 7/10: 7 es el numerador y 10 el denominador.',1),
(5,'text','Calcula 1/4 de 20.',NULL,'5','Para hallar 1/4 de 20 dividimos 20 entre 4: da 5.',2),
(5,'text','Calcula 1/3 de 18.',NULL,'6','18 ÷ 3 = 6.',2),
(5,'text','Calcula 2/5 de 30.',NULL,'12','Primero 30 ÷ 5 = 6; luego 6 × 2 = 12.',3),
(5,'multiple','Si coloreas 4 partes de 4, ¿qué fracción representa todo?','["4/4", "1/4", "0/4", "2/4"]','4/4','Cuatro partes de cuatro es la unidad completa.',1),
(5,'multiple','¿Cuál de estas fracciones es equivalente a 3/6?','["1/2", "2/3", "3/4", "1/3"]','1/2','3/6 se puede simplificar dividiendo numerador y denominador entre 3.',2),
(5,'multiple','¿Qué operación ayuda a calcular una fracción de una cantidad?','["Dividir y a veces multiplicar", "Solo sumar", "Solo restar", "Ordenar letras"]','Dividir y a veces multiplicar','Para a/b de una cantidad, se divide entre b y se multiplica por a.',2),
(6,'text','Escribe en euros: 3 euros y 45 céntimos.',NULL,'3,45|3.45','45 céntimos se escriben con dos cifras decimales: 3,45 €.',1),
(6,'text','Escribe en euros: 12 euros y 5 céntimos.',NULL,'12,05|12.05','5 céntimos se escriben con dos cifras decimales: 12,05 €.',1),
(6,'text','Escribe en euros: 0 euros y 75 céntimos.',NULL,'0,75|0.75','75 céntimos se escriben con dos cifras decimales: 0,75 €.',1),
(6,'text','Escribe en euros: 8 euros y 90 céntimos.',NULL,'8,90|8.90','90 céntimos se escriben con dos cifras decimales: 8,90 €.',1),
(6,'text','Escribe en euros: 24 euros y 99 céntimos.',NULL,'24,99|24.99','99 céntimos se escriben con dos cifras decimales: 24,99 €.',1),
(6,'text','Calcula: 2,5 € + 1,75 €',NULL,'4,25|4.25','Suma euros con euros y céntimos con céntimos, Resultado: 4,25 €,',1),
(6,'text','Calcula: 5,2 € + 3,4 €',NULL,'8,60|8.60','Suma euros con euros y céntimos con céntimos, Resultado: 8,60 €,',1),
(6,'text','Calcula: 10,0 € + 4,65 €',NULL,'14,65|14.65','Suma euros con euros y céntimos con céntimos, Resultado: 14,65 €,',1),
(6,'text','Calcula: 7,35 € + 2,15 €',NULL,'9,50|9.50','Suma euros con euros y céntimos con céntimos, Resultado: 9,50 €,',1),
(6,'text','Calcula: 12,8 € + 0,95 €',NULL,'13,75|13.75','Suma euros con euros y céntimos con céntimos, Resultado: 13,75 €,',1),
(6,'text','Pagas 5 € por algo que cuesta 3,25 €. ¿Cuánto te devuelven?',NULL,'1,75|1.75','Restamos 5 - 3,25 = 1,75 €,',1),
(6,'text','Pagas 10 € por algo que cuesta 6,7 €. ¿Cuánto te devuelven?',NULL,'3,30|3.30','Restamos 10 - 6,70 = 3,30 €,',1),
(6,'text','Pagas 20 € por algo que cuesta 12,45 €. ¿Cuánto te devuelven?',NULL,'7,55|7.55','Restamos 20 - 12,45 = 7,55 €,',1),
(6,'text','Pagas 10 € por algo que cuesta 4,8 €. ¿Cuánto te devuelven?',NULL,'5,20|5.20','Restamos 10 - 4,80 = 5,20 €,',1),
(6,'multiple','¿Qué número es mayor?','["3,45", "3,54", "3,05", "2,99"]','3,54','Todos tienen 3 euros menos 2,99. Comparamos los céntimos: 54 es mayor que 45 y 05.',1),
(6,'multiple','¿Cuántos céntimos hay en 1 euro?','["10", "50", "100", "1000"]','100','1 euro son 100 céntimos.',1),
(6,'text','Convierte 250 céntimos en euros.',NULL,'2,50|2.50','250 céntimos son 2 euros y 50 céntimos: 2,50 €.',1),
(6,'text','Calcula: 4,8 + 2,6',NULL,'7,4|7.4','4,8 + 2,6 = 7,4.',2),
(6,'multiple','¿Cuál es la parte decimal de 18,37?','["18", "37", "3", "7"]','37','La parte decimal está después de la coma.',1),
(6,'text','Ordena mentalmente: ¿cuál es menor, 0,8 o 0,75?',NULL,'0,75|0.75','0,8 es 0,80. Como 75 centésimas es menor que 80 centésimas, 0,75 es menor.',2),
(6,'multiple','¿Qué moneda equivale a 0,50 €?','["50 céntimos", "5 céntimos", "2 euros", "10 céntimos"]','50 céntimos','0,50 € son cincuenta céntimos.',1),
(7,'text','Convierte 1 m a centímetros.',NULL,'100','1 m = 100 cm, así que 1 m = 100 cm.',1),
(7,'text','Convierte 2 m a centímetros.',NULL,'200','1 m = 100 cm, así que 2 m = 200 cm.',1),
(7,'text','Convierte 5 m a centímetros.',NULL,'500','1 m = 100 cm, así que 5 m = 500 cm.',1),
(7,'text','Convierte 12 m a centímetros.',NULL,'1200','1 m = 100 cm, así que 12 m = 1200 cm.',1),
(7,'text','Convierte 25 m a centímetros.',NULL,'2500','1 m = 100 cm, así que 25 m = 2500 cm.',1),
(7,'text','Convierte 48 m a centímetros.',NULL,'4800','1 m = 100 cm, así que 48 m = 4800 cm.',1),
(7,'text','Convierte 100 m a centímetros.',NULL,'10000','1 m = 100 cm, así que 100 m = 10000 cm.',1),
(7,'text','Convierte 100 cm a metros.',NULL,'1','Como 100 cm = 1 m, dividimos 100 entre 100.',1),
(7,'text','Convierte 250 cm a metros.',NULL,'2,50','Como 100 cm = 1 m, dividimos 250 entre 100.',1),
(7,'text','Convierte 500 cm a metros.',NULL,'5','Como 100 cm = 1 m, dividimos 500 entre 100.',1),
(7,'text','Convierte 1200 cm a metros.',NULL,'12','Como 100 cm = 1 m, dividimos 1200 entre 100.',1),
(7,'text','Convierte 3500 cm a metros.',NULL,'35','Como 100 cm = 1 m, dividimos 3500 entre 100.',1),
(7,'text','Convierte 1 km a metros.',NULL,'1000','1 km = 1000 m, por tanto 1 km = 1000 m.',1),
(7,'text','Convierte 3 km a metros.',NULL,'3000','1 km = 1000 m, por tanto 3 km = 3000 m.',1),
(7,'text','Convierte 7 km a metros.',NULL,'7000','1 km = 1000 m, por tanto 7 km = 7000 m.',1),
(7,'text','Convierte 12 km a metros.',NULL,'12000','1 km = 1000 m, por tanto 12 km = 12000 m.',1),
(7,'multiple','¿Qué unidad usarías para medir la distancia entre dos ciudades?','["milímetros", "centímetros", "kilómetros", "gramos"]','kilómetros','Las distancias largas se miden normalmente en kilómetros.',1),
(7,'multiple','¿Qué unidad usarías para medir el largo de un lápiz?','["kilómetros", "centímetros", "litros", "kilogramos"]','centímetros','Un lápiz se mide bien en centímetros.',1),
(7,'text','Una cinta mide 2 m y 35 cm. ¿Cuántos centímetros son?',NULL,'235','2 m son 200 cm. 200 + 35 = 235 cm.',1),
(7,'text','Leire camina 1 km y 250 m. ¿Cuántos metros camina?',NULL,'1250|1.250','1 km son 1000 m. 1000 + 250 = 1.250 m.',1),
(7,'text','Un rectángulo mide 8 cm de largo y 5 cm de ancho. ¿Cuál es su perímetro?',NULL,'26','Perímetro = 8 + 5 + 8 + 5 = 26 cm.',2),
(7,'multiple','¿Cuál es mayor?','["3 m", "250 cm", "30 cm", "2 m y 90 cm"]','3 m','3 m son 300 cm, mayor que 250 cm, 30 cm y 290 cm.',2),
(7,'text','Convierte 45 mm a centímetros.',NULL,'4,5|4.5','10 mm = 1 cm. 45 mm = 4,5 cm.',2),
(7,'multiple','La unidad principal de longitud es…','["litro", "metro", "kilogramo", "hora"]','metro','La longitud se mide principalmente en metros.',1),
(7,'text','Un camino mide 950 m. ¿Cuántos metros le faltan para 1 km?',NULL,'50','1 km = 1000 m. 1000 - 950 = 50 m.',1),
(8,'text','Convierte 1 kg a gramos.',NULL,'1000','1 kg = 1000 g, así que 1 kg = 1000 g.',1),
(8,'text','Convierte 2 kg a gramos.',NULL,'2000','1 kg = 1000 g, así que 2 kg = 2000 g.',1),
(8,'text','Convierte 5 kg a gramos.',NULL,'5000','1 kg = 1000 g, así que 5 kg = 5000 g.',1),
(8,'text','Convierte 12 kg a gramos.',NULL,'12000','1 kg = 1000 g, así que 12 kg = 12000 g.',1),
(8,'text','Convierte 25 kg a gramos.',NULL,'25000','1 kg = 1000 g, así que 25 kg = 25000 g.',1),
(8,'text','Convierte 1 L a mililitros.',NULL,'1000','1 L = 1000 mL, así que 1 L = 1000 mL.',1),
(8,'text','Convierte 2 L a mililitros.',NULL,'2000','1 L = 1000 mL, así que 2 L = 2000 mL.',1),
(8,'text','Convierte 3 L a mililitros.',NULL,'3000','1 L = 1000 mL, así que 3 L = 3000 mL.',1),
(8,'text','Convierte 5 L a mililitros.',NULL,'5000','1 L = 1000 mL, así que 5 L = 5000 mL.',1),
(8,'text','Convierte 10 L a mililitros.',NULL,'10000','1 L = 1000 mL, así que 10 L = 10000 mL.',1),
(8,'multiple','¿Qué unidad usarías para medir la masa de una sandía?','["kilogramos", "milímetros", "litros", "minutos"]','kilogramos','La masa de objetos pesados se mide en kilogramos.',1),
(8,'multiple','¿Qué unidad usarías para medir el agua de una botella?','["litros", "gramos", "centímetros", "kilómetros"]','litros','La capacidad de una botella se mide en litros o mililitros.',1),
(8,'text','Una bolsa pesa 2 kg y 300 g. ¿Cuántos gramos son?',NULL,'2300|2.300','2 kg = 2000 g. 2000 + 300 = 2300 g.',1),
(8,'text','Una jarra tiene 1 L y 250 mL. ¿Cuántos mL son?',NULL,'1250|1.250','1 L = 1000 mL. 1000 + 250 = 1250 mL.',1),
(8,'text','Tienes 3 botellas de 500 mL. ¿Cuántos mL hay en total?',NULL,'1500|1.500','3 × 500 = 1500 mL.',1),
(8,'multiple','¿Cuál es mayor?','["750 g", "1 kg", "500 g", "900 g"]','1 kg','1 kg = 1000 g, que es mayor que 750 g, 500 g y 900 g.',1),
(8,'text','Si una receta necesita 800 g y tienes 1 kg, ¿cuántos gramos sobran?',NULL,'200','1 kg = 1000 g. 1000 - 800 = 200 g.',1),
(8,'text','Convierte 2500 mL a litros.',NULL,'2,5|2.5','Dividimos entre 1000: 2500 mL = 2,5 L.',2),
(8,'multiple','¿Cuál mide capacidad?','["litro", "metro", "gramo", "euro"]','litro','El litro mide capacidad.',1),
(8,'multiple','¿Cuál mide masa?','["kilogramo", "litro", "metro", "hora"]','kilogramo','El kilogramo mide masa.',1),
(9,'text','¿Cuántos minutos hay en 1 horas?',NULL,'60','1 hora = 60 minutos. 1 × 60 = 60.',1),
(9,'text','¿Cuántos minutos hay en 2 horas?',NULL,'120','1 hora = 60 minutos. 2 × 60 = 120.',1),
(9,'text','¿Cuántos minutos hay en 3 horas?',NULL,'180','1 hora = 60 minutos. 3 × 60 = 180.',1),
(9,'text','¿Cuántos minutos hay en 5 horas?',NULL,'300','1 hora = 60 minutos. 5 × 60 = 300.',1),
(9,'text','Una película empieza a las 17:30 y dura 90 minutos. ¿A qué hora termina?',NULL,'19:00|19.00|7:00','90 minutos son 1 hora y 30 minutos. 17:30 + 1:30 = 19:00.',2),
(9,'text','El recreo empieza a las 11:15 y termina a las 11:45. ¿Cuántos minutos dura?',NULL,'30','De 11:15 a 11:45 pasan 30 minutos.',1),
(9,'multiple','¿Cuántos meses tiene un año?','["10", "11", "12", "13"]','12','Un año tiene 12 meses.',1),
(9,'multiple','¿Cuántos días suele tener una semana?','["5", "6", "7", "8"]','7','Una semana tiene 7 días.',1),
(9,'text','Si hoy es lunes, ¿qué día será dentro de 3 días?',NULL,'jueves','Lunes + 1 martes, +2 miércoles, +3 jueves.',1),
(9,'text','Pagas 20 € y compras algo de 13 €. ¿Cuánto cambio recibes?',NULL,'7','20 - 13 = 7 euros.',1),
(9,'text','Compra 2 cuadernos de 3 € y un lápiz de 1 €. ¿Cuánto cuesta todo?',NULL,'7','2 × 3 = 6; 6 + 1 = 7 euros.',1),
(9,'multiple','¿Qué hora es media hora después de las 16:20?','["16:30", "16:50", "17:20", "15:50"]','16:50','Media hora son 30 minutos. 16:20 + 30 min = 16:50.',1),
(9,'text','¿Cuántos segundos hay en 2 minutos?',NULL,'120','1 minuto son 60 segundos. 2 × 60 = 120.',1),
(9,'text','Leire estudia de 18:00 a 18:45. ¿Cuántos minutos estudia?',NULL,'45','De 18:00 a 18:45 pasan 45 minutos.',1),
(9,'multiple','¿Qué instrumento usamos para medir el tiempo?','["balanza", "regla", "reloj", "termómetro"]','reloj','El reloj sirve para medir el tiempo.',1),
(9,'text','Si un libro cuesta 8,50 € y pagas con 10 €, ¿cuánto te devuelven?',NULL,'1,50|1.50','10,00 - 8,50 = 1,50 €.',1),
(9,'multiple','¿Qué es un trimestre?','["3 meses", "6 meses", "12 meses", "15 días"]','3 meses','Un trimestre son tres meses.',2),
(9,'text','¿Cuántos minutos hay entre las 09:10 y las 09:35?',NULL,'25','De 09:10 a 09:35 pasan 25 minutos.',1),
(9,'text','Si una clase dura 55 minutos y empieza a las 10:00, ¿a qué hora termina?',NULL,'10:55|10.55','Sumamos 55 minutos a las 10:00: termina a las 10:55.',1),
(10,'multiple','¿Cuántos lados tiene un triángulo?','["3", "4", "5", "6"]','3','Un triángulo tiene tres lados.',1),
(10,'multiple','¿Cuántos lados tiene un cuadrilátero?','["3", "4", "5", "8"]','4','Un cuadrilátero tiene cuatro lados.',1),
(10,'multiple','Un ángulo menor que un ángulo recto se llama…','["agudo", "obtuso", "llano", "completo"]','agudo','Los ángulos agudos miden menos de 90 grados.',1),
(10,'multiple','Un ángulo mayor que un recto y menor que un llano se llama…','["agudo", "recto", "obtuso", "nulo"]','obtuso','Los ángulos obtusos miden más de 90° y menos de 180°.',1),
(10,'multiple','¿Qué es el perímetro?','["La suma de los lados", "El color de la figura", "La mitad de un lado", "El número de vértices siempre"]','La suma de los lados','El perímetro es la longitud del contorno.',1),
(10,'multiple','¿Cuántos lados tiene un pentágono?','["4", "5", "6", "7"]','5','Penta- significa cinco.',1),
(10,'multiple','¿Cuántos lados tiene un hexágono?','["5", "6", "7", "8"]','6','Hexa- significa seis.',1),
(10,'multiple','Dos rectas que no se cortan nunca son…','["paralelas", "secantes", "curvas", "ángulos"]','paralelas','Las rectas paralelas mantienen siempre la misma distancia.',1),
(10,'multiple','Dos rectas que se cortan forman…','["un ángulo", "un litro", "una fracción", "un decimal"]','un ángulo','Cuando dos rectas se cortan, forman ángulos.',1),
(10,'text','Un cuadrado tiene lados de 3 cm. ¿Cuál es su perímetro?',NULL,'12','El cuadrado tiene 4 lados iguales: 3 × 4 = 12 cm.',1),
(10,'text','Un cuadrado tiene lados de 4 cm. ¿Cuál es su perímetro?',NULL,'16','El cuadrado tiene 4 lados iguales: 4 × 4 = 16 cm.',1),
(10,'text','Un cuadrado tiene lados de 5 cm. ¿Cuál es su perímetro?',NULL,'20','El cuadrado tiene 4 lados iguales: 5 × 4 = 20 cm.',1),
(10,'text','Un cuadrado tiene lados de 8 cm. ¿Cuál es su perímetro?',NULL,'32','El cuadrado tiene 4 lados iguales: 8 × 4 = 32 cm.',1),
(10,'text','Un cuadrado tiene lados de 12 cm. ¿Cuál es su perímetro?',NULL,'48','El cuadrado tiene 4 lados iguales: 12 × 4 = 48 cm.',1),
(10,'text','Un rectángulo mide 6 cm de largo y 4 cm de ancho. ¿Cuál es su perímetro?',NULL,'20','Perímetro = 6+4+6+4 = 20 cm.',2),
(10,'text','Un rectángulo mide 9 cm de largo y 5 cm de ancho. ¿Cuál es su perímetro?',NULL,'28','Perímetro = 9+5+9+5 = 28 cm.',2),
(10,'text','Un rectángulo mide 12 cm de largo y 7 cm de ancho. ¿Cuál es su perímetro?',NULL,'38','Perímetro = 12+7+12+7 = 38 cm.',2),
(10,'text','Un rectángulo mide 15 cm de largo y 8 cm de ancho. ¿Cuál es su perímetro?',NULL,'46','Perímetro = 15+8+15+8 = 46 cm.',2),
(10,'multiple','¿Qué polígono tiene todos sus lados iguales y cuatro ángulos rectos?','["cuadrado", "triángulo", "pentágono", "círculo"]','cuadrado','El cuadrado tiene cuatro lados iguales y cuatro ángulos rectos.',1),
(10,'multiple','¿Cuál no es un polígono?','["triángulo", "cuadrado", "círculo", "hexágono"]','círculo','Un polígono tiene lados rectos; el círculo tiene línea curva.',1),
(10,'text','¿Cuántos vértices tiene un rectángulo?',NULL,'4','Un rectángulo tiene 4 esquinas o vértices.',1),
(10,'multiple','Un segmento es…','["una parte de recta con dos extremos", "una línea sin principio ni fin", "una curva cerrada", "un número decimal"]','una parte de recta con dos extremos','El segmento está limitado por dos puntos extremos.',1),
(11,'multiple','Una figura tiene simetría si…','["se puede dividir en dos partes iguales reflejadas", "siempre es redonda", "tiene muchos colores", "no tiene lados"]','se puede dividir en dos partes iguales reflejadas','El eje de simetría divide la figura en dos partes iguales como un espejo.',1),
(11,'multiple','¿Qué objeto suele tener simetría?','["mariposa", "zapato solo", "nube irregular", "piedra rota"]','mariposa','Una mariposa suele tener dos alas parecidas a ambos lados.',1),
(11,'multiple','En un plano, para localizar un punto usamos…','["coordenadas", "litros", "gramos", "sílabas"]','coordenadas','Las coordenadas indican la posición en un plano.',1),
(11,'multiple','Si avanzas hacia el norte y luego giras a la derecha, miras hacia…','["este", "oeste", "sur", "norte"]','este','En una rosa de los vientos, a la derecha del norte está el este.',1),
(11,'multiple','¿Qué indica una flecha en un mapa?','["dirección", "peso", "precio", "sabor"]','dirección','Las flechas ayudan a orientarse y saber hacia dónde moverse.',1),
(11,'text','Si estás en la casilla B2 y subes una fila, ¿en qué fila estarás?',NULL,'1|B1','Al subir una fila desde la fila 2 se llega a la fila 1.',2),
(11,'multiple','La rosa de los vientos muestra…','["norte, sur, este y oeste", "kilogramos", "nombres de animales", "fracciones"]','norte, sur, este y oeste','Sirve para orientarse con los puntos cardinales.',1),
(11,'text','¿Cuál es el punto cardinal opuesto al norte?',NULL,'sur','El sur está en dirección contraria al norte.',1),
(11,'text','¿Cuál es el punto cardinal opuesto al este?',NULL,'oeste','Este y oeste son direcciones opuestas.',1),
(11,'multiple','Una línea que divide una figura en dos partes iguales se llama…','["eje de simetría", "perímetro", "diagonal siempre", "resto"]','eje de simetría','El eje de simetría funciona como un espejo.',1),
(11,'multiple','¿Qué movimiento es un giro?','["Dar la vuelta alrededor de un punto", "Aumentar el peso", "Cambiar de idioma", "Sumar dos lados"]','Dar la vuelta alrededor de un punto','Un giro rota una figura alrededor de un punto.',1),
(11,'multiple','Si una figura se traslada, significa que…','["se mueve sin cambiar de forma", "se rompe", "se hace siempre más grande", "se convierte en círculo"]','se mueve sin cambiar de forma','La traslación desplaza una figura manteniendo su tamaño y forma.',1),
(11,'text','En una cuadrícula, ¿qué se suele mirar primero: columna o fila?',NULL,'columna|la columna','Muchas cuadrículas se leen primero por columna/letra y después por fila/número, como B3.',2),
(11,'multiple','¿Cuál de estas letras mayúsculas suele tener simetría vertical?','["A", "F", "G", "R"]','A','La A puede dividirse en dos partes parecidas por un eje vertical.',2),
(11,'multiple','¿Cuál es una instrucción de orientación?','["gira a la izquierda", "multiplica por 8", "subraya el verbo", "pesa 2 kg"]','gira a la izquierda','Girar a la izquierda indica dirección en un recorrido.',1),
(11,'text','Si das media vuelta, ¿cuántos grados giras?',NULL,'180|180 grados','Media vuelta equivale a 180 grados.',2),
(11,'multiple','¿Qué palabra significa lo mismo que “arriba” en un plano?','["norte si el mapa está orientado", "litro", "suma", "resto"]','norte si el mapa está orientado','En muchos mapas orientados, arriba corresponde al norte.',2),
(11,'text','Si un robot avanza 3 casillas y luego 2 más, ¿cuántas casillas avanza en total?',NULL,'5','Sumamos 3 + 2 = 5 casillas.',1),
(11,'multiple','Para no perderse en un plano conviene mirar…','["la leyenda y la orientación", "solo los colores", "el peso del papel", "el número de páginas"]','la leyenda y la orientación','La leyenda y la orientación ayudan a interpretar el plano.',1),
(11,'multiple','Una coordenada como C4 indica…','["una posición", "una masa", "una hora", "una moneda"]','una posición','C4 localiza un punto o casilla en la cuadrícula.',1),
(12,'multiple','¿Para qué sirve una tabla de datos?','["Para ordenar información", "Para medir líquidos", "Para dibujar ángulos", "Para conjugar verbos"]','Para ordenar información','Una tabla organiza datos para leerlos mejor.',1),
(12,'multiple','En una gráfica de barras, la barra más alta indica…','["el dato mayor", "el dato menor", "un error", "el título"]','el dato mayor','La altura de la barra representa la cantidad.',1),
(12,'multiple','Un suceso seguro es…','["algo que ocurre siempre", "algo imposible", "algo que nunca se sabe", "una resta"]','algo que ocurre siempre','Seguro significa que ocurre siempre en esa situación.',1),
(12,'multiple','Sacar un 7 en un dado normal de 6 caras es…','["imposible", "seguro", "muy frecuente", "una longitud"]','imposible','Un dado normal solo tiene números del 1 al 6.',1),
(12,'multiple','Sacar un número par en un dado es…','["posible", "imposible", "seguro", "una unidad de masa"]','posible','Puede salir 2, 4 o 6, pero no siempre ocurre.',1),
(12,'text','En una encuesta, 8 niños eligen fútbol, 5 baile y 7 dibujo. ¿Cuántos niños participaron?',NULL,'20','Sumamos 8 + 5 + 7 = 20.',1),
(12,'multiple','Si una barra marca 12 votos y otra 9, ¿cuál es mayor?','["12 votos", "9 votos", "son iguales", "no se puede saber"]','12 votos','12 es mayor que 9.',1),
(12,'text','Calcula la diferencia entre 18 votos y 11 votos.',NULL,'7','18 - 11 = 7.',1),
(12,'multiple','¿Qué es la moda en un conjunto de datos sencillo?','["El dato que más se repite", "El dato más pequeño siempre", "La suma de todos", "Una figura geométrica"]','El dato que más se repite','La moda es el dato con mayor frecuencia.',2),
(12,'text','Datos: 2, 3, 3, 5, 6. ¿Cuál es la moda?',NULL,'3','El 3 aparece dos veces; los demás una vez.',2),
(12,'multiple','¿Cuál es una pregunta estadística?','["¿Cuál es tu deporte favorito?", "¿Cuánto es 3×4?", "¿Dónde está el norte?", "¿Qué es un sustantivo?"]','¿Cuál es tu deporte favorito?','Una pregunta estadística recoge respuestas para analizarlas.',1),
(12,'text','En una tabla hay 14 perros y 9 gatos. ¿Cuántos animales hay?',NULL,'23','14 + 9 = 23 animales.',1),
(12,'multiple','Si en una bolsa solo hay bolas rojas, sacar una bola roja es…','["seguro", "imposible", "raro", "una división"]','seguro','Si todas son rojas, siempre sale roja.',1),
(12,'multiple','Si en una bolsa hay bolas rojas y azules, sacar verde es…','["imposible", "seguro", "muy fácil", "una gráfica"]','imposible','No hay bolas verdes, así que no puede salir verde.',1),
(12,'text','La suma de cuatro datos es 40. Si todos son iguales, ¿cuánto vale cada uno?',NULL,'10','40 dividido entre 4 = 10.',2),
(12,'multiple','¿Qué elemento debe tener una gráfica para entenderla mejor?','["título", "olor", "peso", "precio siempre"]','título','El título explica qué datos representa la gráfica.',1),
(12,'text','Si una gráfica muestra 6 libros leídos en enero y 10 en febrero, ¿cuántos más se leyeron en febrero?',NULL,'4','10 - 6 = 4 libros más.',1),
(12,'multiple','Un pictograma usa…','["dibujos o símbolos", "solo letras sin datos", "kilómetros", "verbos"]','dibujos o símbolos','Los pictogramas representan cantidades con símbolos.',1),
(12,'multiple','Antes de hacer una gráfica conviene…','["recoger y ordenar los datos", "borrar los datos", "mezclar unidades", "escribir un cuento"]','recoger y ordenar los datos','La gráfica se construye a partir de datos ordenados.',1),
(13,'text','Clara plantó una semilla en una maceta. Cada mañana la regaba y la ponía junto a la ventana. Después de unos días apareció un tallo verde. ¿Qué plantó Clara?',NULL,'una semilla|semilla','Clara plantó una semilla en una maceta.',1),
(13,'text','El sábado, Dani fue al mercado con su abuela. Compraron naranjas, pan y queso. Al volver, prepararon una merienda para toda la familia. ¿Con quién fue Dani al mercado?',NULL,'su abuela|abuela','El texto dice que Dani fue con su abuela.',1),
(13,'text','Aina escuchó un ruido en el jardín. Al mirar, vio a un gato pequeño escondido detrás de una maceta. Le puso agua y avisó a su madre. ¿Dónde estaba escondido el gato?',NULL,'detrás de una maceta|detras de una maceta|maceta','El gato estaba detrás de una maceta.',1),
(13,'multiple','¿Qué es la idea principal de un texto?','["Lo más importante que cuenta", "Una palabra difícil", "El nombre del autor", "El último punto"]','Lo más importante que cuenta','La idea principal resume lo esencial del texto.',1),
(13,'multiple','Para responder bien una pregunta de lectura conviene…','["buscar la información en el texto", "responder sin leer", "copiar una palabra al azar", "mirar solo el título"]','buscar la información en el texto','Hay que volver al texto y localizar el dato.',1),
(13,'multiple','Una inferencia es…','["deducir algo que no se dice exactamente", "contar letras", "hacer una división", "poner una coma"]','deducir algo que no se dice exactamente','Inferir es comprender algo a partir de pistas del texto.',2),
(13,'text','Si un texto dice “María abrió el paraguas”, ¿qué tiempo puede hacer?',NULL,'lluvia|llueve|está lloviendo|esta lloviendo','Abrir un paraguas suele indicar que llueve o puede llover.',2),
(13,'multiple','¿Qué respuesta está mejor escrita?','["Fue al parque con su hermano.", "parque", "porque sí", "no sé"]','Fue al parque con su hermano.','Una respuesta completa forma una frase con sentido.',1),
(13,'text','Escribe una pregunta que podrías hacer después de leer un cuento.',NULL,'respuesta libre','Debe ser una pregunta relacionada con personajes, lugar, problema o final.',1),
(13,'text','Escribe una frase que explique qué es un resumen.',NULL,'respuesta libre','Un resumen cuenta lo más importante con pocas palabras.',1),
(13,'multiple','¿Qué palabra indica tiempo en una narración?','["después", "azul", "mesa", "rápido"]','después','Después indica orden temporal.',1),
(13,'multiple','Si no entiendes una palabra del texto, conviene…','["leer la frase completa y buscar pistas", "saltarse todo el texto", "borrar la palabra", "contar sus letras solamente"]','leer la frase completa y buscar pistas','El contexto ayuda a comprender palabras desconocidas.',1),
(13,'text','Lee: “El perro movía la cola al ver a Lucía”. ¿Cómo se sentía probablemente el perro?',NULL,'contento|alegre|feliz','Mover la cola al ver a alguien suele indicar alegría.',1),
(13,'multiple','¿Qué parte de un texto suele anticipar de qué trata?','["título", "número de página", "margen", "tipo de letra siempre"]','título','El título da una pista sobre el contenido.',1),
(13,'text','Escribe la idea principal de este texto: “Los árboles dan sombra, producen oxígeno y sirven de hogar a muchos animales.”',NULL,'los árboles son importantes|los arboles son importantes|los árboles ayudan a la naturaleza|los arboles ayudan a la naturaleza','La idea principal es que los árboles son importantes para la naturaleza.',2),
(13,'multiple','¿Qué es un detalle?','["Una información concreta del texto", "El tema general", "El título siempre", "Un signo de puntuación"]','Una información concreta del texto','Los detalles completan la idea principal.',1),
(13,'multiple','Para ordenar los hechos de un cuento usamos palabras como…','["primero, luego, finalmente", "rojo, azul, verde", "alto, bajo, ancho", "kg, litro, metro"]','primero, luego, finalmente','Son conectores temporales.',1),
(13,'text','¿Qué debes hacer antes de contestar un examen de lectura?',NULL,'leer el texto|leer despacio|leer dos veces','Primero hay que leer y entender el texto.',1),
(14,'text','Escribe un sustantivo de la frase: “La niña juega en el patio.”',NULL,'niña|patio','Un sustantivo nombra una persona, animal, cosa o lugar.',1),
(14,'text','Escribe un sustantivo de la frase: “El perro duerme bajo la mesa.”',NULL,'perro|mesa','Un sustantivo nombra una persona, animal, cosa o lugar.',1),
(14,'text','Escribe un sustantivo de la frase: “Sevilla es una ciudad bonita.”',NULL,'Sevilla|ciudad','Un sustantivo nombra una persona, animal, cosa o lugar.',1),
(14,'text','Escribe un sustantivo de la frase: “Mi mochila azul está llena.”',NULL,'mochila','Un sustantivo nombra una persona, animal, cosa o lugar.',1),
(14,'text','Escribe un sustantivo de la frase: “El río baja por la montaña.”',NULL,'río|rio|montaña|montana','Un sustantivo nombra una persona, animal, cosa o lugar.',1),
(14,'multiple','¿Cuál es un sustantivo propio?','["Cádiz", "ciudad", "perro", "mesa"]','Cádiz','Los nombres propios se escriben con mayúscula.',1),
(14,'multiple','¿Cuál es un sustantivo común?','["Leire", "España", "río", "Andalucía"]','río','Río es un nombre común; no nombra uno concreto.',1),
(14,'multiple','El plural de “lápiz” es…','["lápices", "lápizs", "lápizes", "lapiz"]','lápices','Las palabras terminadas en z cambian z por c en plural: lápices.',1),
(14,'multiple','El femenino de “actor” es…','["actora", "actriz", "actoras", "actores"]','actriz','Actor tiene como femenino actriz.',2),
(14,'multiple','¿Cuál está en singular?','["casas", "niños", "flor", "mesas"]','flor','Flor nombra una sola cosa.',1),
(14,'multiple','¿Cuál está en plural?','["árbol", "libros", "ciudad", "pez"]','libros','Libros nombra más de uno.',1),
(14,'multiple','¿Cuál nombra un lugar?','["playa", "correr", "bonito", "rápidamente"]','playa','Playa es un sustantivo de lugar.',1),
(14,'multiple','¿Cuál nombra un sentimiento?','["alegría", "saltó", "azul", "mis"]','alegría','Alegría es un sustantivo abstracto.',2),
(14,'text','Escribe el plural de “pez”.',NULL,'peces','Las palabras acabadas en z cambian z por c: pez → peces.',1),
(14,'text','Escribe el singular de “camiones”.',NULL,'camión|camion','Camiones es plural; su singular es camión.',1),
(14,'multiple','¿Qué sustantivo debe escribirse con mayúscula?','["lorena", "mesa", "gato", "flor"]','lorena','Los nombres propios de persona se escriben con mayúscula: Lorena.',1),
(14,'text','Escribe un sustantivo común de animal.',NULL,'perro|gato|caballo|pez|pájaro|pajaro|vaca|león|leon','Cualquier nombre común de animal es válido.',1),
(14,'multiple','En “el coche rojo”, ¿cuál es el sustantivo?','["el", "coche", "rojo", "el coche rojo"]','coche','Coche nombra una cosa.',1),
(15,'text','Escribe el adjetivo de la frase: “La casa grande tiene ventanas.”',NULL,'grande','El adjetivo dice cómo es el sustantivo.',1),
(15,'text','Escribe el adjetivo de la frase: “El gato negro duerme.”',NULL,'negro','El adjetivo dice cómo es el sustantivo.',1),
(15,'text','Escribe el adjetivo de la frase: “Las flores amarillas huelen bien.”',NULL,'amarillas','El adjetivo dice cómo es el sustantivo.',1),
(15,'text','Escribe el adjetivo de la frase: “Un niño simpático saluda.”',NULL,'simpático|simpatico','El adjetivo dice cómo es el sustantivo.',1),
(15,'text','Escribe el adjetivo de la frase: “La mochila pesada está en la silla.”',NULL,'pesada','El adjetivo dice cómo es el sustantivo.',1),
(15,'multiple','Completa: “las niñas ___”','["contenta", "contentos", "contentas", "contento"]','contentas','Niñas es femenino plural, por eso el adjetivo debe ser femenino plural.',1),
(15,'multiple','Completa: “el perro ___”','["pequeño", "pequeña", "pequeñas", "pequeños"]','pequeño','Perro es masculino singular.',1),
(15,'multiple','¿Cuál es un adjetivo?','["rápido", "correr", "mesa", "ayer"]','rápido','Rápido dice cómo es o cómo va algo.',1),
(15,'text','Escribe un adjetivo para “montaña”.',NULL,'alta|grande|nevada|bonita|rocosa','Un adjetivo válido debe decir cómo es la montaña.',1),
(15,'text','Cambia a plural: “flor roja”.',NULL,'flores rojas','Flor roja en plural es flores rojas.',1),
(15,'multiple','En “los coches nuevos”, el adjetivo está en…','["masculino plural", "femenino plural", "masculino singular", "femenino singular"]','masculino plural','Coches es masculino plural y nuevos concuerda.',2),
(15,'multiple','¿Qué frase tiene concordancia correcta?','["La niñas alto", "Los perro pequeño", "Las casas blancas", "El mesa grande"]','Las casas blancas','Sustantivo y adjetivo concuerdan en femenino plural.',1),
(15,'text','Escribe el femenino de “bonito”.',NULL,'bonita','Bonito cambia la o por a en femenino.',1),
(15,'text','Escribe el plural de “feliz”.',NULL,'felices','Feliz termina en z, por eso el plural es felices.',2),
(15,'multiple','¿Qué adjetivo describe tamaño?','["enorme", "ayer", "correr", "María"]','enorme','Enorme indica tamaño.',1),
(15,'multiple','¿Qué adjetivo describe color?','["verde", "mesa", "saltó", "con"]','verde','Verde puede describir el color de un objeto.',1),
(15,'text','Añade un adjetivo a la frase: “El lápiz ___ está en la mesa”.',NULL,'respuesta libre','Debe ser una palabra que describa al lápiz: rojo, largo, nuevo...',1),
(15,'multiple','El grado comparativo aparece en…','["más alto que", "altísimo", "alto", "el alto"]','más alto que','El comparativo compara dos elementos.',3),
(15,'multiple','“Preciosísima” es un adjetivo en grado…','["positivo", "comparativo", "superlativo", "verbal"]','superlativo','El superlativo expresa una cualidad en grado muy alto.',3),
(16,'multiple','En “la casa”, ¿qué palabra es determinante?','["la", "casa", "las", "casas"]','la','La acompaña al sustantivo casa.',1),
(16,'multiple','Completa: “___ libros son míos”.','["Estos", "Esta", "Este", "Ese"]','Estos','Libros es masculino plural.',1),
(16,'multiple','Completa: “___ mochila es de Leire”.','["Mi", "Mis", "Tus", "Sus de ellos"]','Mi','Mochila es singular.',1),
(16,'multiple','¿Cuál es un artículo determinado?','["el", "un", "algún", "mi"]','el','El, la, los, las son artículos determinados.',1),
(16,'multiple','¿Cuál es un artículo indeterminado?','["una", "la", "los", "mis"]','una','Un, una, unos, unas son artículos indeterminados.',1),
(16,'multiple','¿Cuál es un posesivo?','["mi", "este", "dos", "la"]','mi','Mi indica posesión.',1),
(16,'multiple','¿Cuál es un demostrativo?','["aquella", "nuestro", "tres", "el"]','aquella','Aquella indica distancia respecto a quien habla.',1),
(16,'multiple','¿Cuál es un numeral?','["cuatro", "mi", "este", "la"]','cuatro','Cuatro indica cantidad exacta.',1),
(16,'text','Escribe un determinante para “___ cuaderno”.',NULL,'el|un|mi|este|ese|aquel|tu','Debe acompañar al sustantivo cuaderno.',1),
(16,'text','Escribe el plural de “esta mesa”.',NULL,'estas mesas','El determinante y el sustantivo pasan a plural.',1),
(16,'multiple','¿Qué frase está bien?','["Aquellos niños juegan", "Aquella niños juegan", "Aquel niños juegan", "Aquellos niño juegan"]','Aquellos niños juegan','Aquellos concuerda con niños en masculino plural.',2),
(16,'multiple','En “tres lápices”, “tres” es…','["determinante numeral", "sustantivo", "verbo", "adjetivo de color"]','determinante numeral','Tres indica cantidad y acompaña a lápices.',1),
(16,'multiple','En “nuestro colegio”, “nuestro” es…','["posesivo", "demostrativo", "artículo", "verbo"]','posesivo','Nuestro indica pertenencia.',1),
(16,'text','Completa con artículo: “___ agua está fría”.',NULL,'el','Aunque agua es femenino, se usa el agua por empezar con a tónica.',3),
(16,'multiple','¿Cuál acompaña bien a “flores”?','["las", "el", "un", "este"]','las','Flores es femenino plural.',1),
(16,'multiple','¿Cuál acompaña bien a “árbol”?','["el", "las", "unos", "estas"]','el','Árbol es masculino singular.',1),
(16,'text','Escribe una frase con un determinante posesivo.',NULL,'respuesta libre','Debe incluir un posesivo como mi, tu, su, nuestro...',1),
(16,'multiple','¿Qué determinante indica cercanía?','["este", "aquel", "mi", "cinco"]','este','Este se usa para algo cercano.',1),
(16,'multiple','¿Qué determinante indica lejanía?','["aquel", "este", "mi", "un"]','aquel','Aquel se usa para algo lejano.',1),
(16,'text','Completa: “___ amigas vienen a casa” con un posesivo.',NULL,'mis|tus|sus|nuestras|vuestras','Debe concordar con amigas en plural.',1),
(17,'text','Escribe el verbo de la frase: “Ana canta una canción.”',NULL,'canta','El verbo expresa una acción o estado.',1),
(17,'text','Escribe el verbo de la frase: “Nosotros jugamos en el patio.”',NULL,'jugamos','El verbo expresa una acción o estado.',1),
(17,'text','Escribe el verbo de la frase: “Ellos escribieron una carta.”',NULL,'escribieron','El verbo expresa una acción o estado.',1),
(17,'text','Escribe el verbo de la frase: “Mañana visitaré a mi abuela.”',NULL,'visitaré|visitare','El verbo expresa una acción o estado.',1),
(17,'text','Escribe el verbo de la frase: “Leire lee un cuento.”',NULL,'lee','El verbo expresa una acción o estado.',1),
(17,'multiple','El infinitivo de “cantaba” es…','["cantar", "canto", "cantó", "cantando"]','cantar','El infinitivo termina en -ar.',1),
(17,'multiple','El infinitivo de “bebemos” es…','["beber", "bebí", "beben", "bebido"]','beber','Beber termina en -er.',1),
(17,'multiple','El infinitivo de “vivirán” es…','["vivir", "viven", "vivió", "viviendo"]','vivir','Vivir termina en -ir.',1),
(17,'multiple','¿Qué frase está en pasado?','["Ayer jugué al fútbol.", "Hoy juego.", "Mañana jugaré.", "Juego ahora."]','Ayer jugué al fútbol.','Ayer indica pasado.',1),
(17,'multiple','¿Qué frase está en futuro?','["Mañana estudiaré.", "Ayer estudié.", "Ahora estudio.", "Estudio todos los días."]','Mañana estudiaré.','Mañana indica futuro.',1),
(17,'multiple','En “nosotros corremos”, la persona es…','["1ª persona plural", "2ª persona singular", "3ª persona plural", "1ª persona singular"]','1ª persona plural','Nosotros corresponde a primera persona plural.',2),
(17,'text','Cambia a pasado: “Yo salto”.',NULL,'yo salté|salte|salté|salte','En pasado puede decirse “yo salté”.',2),
(17,'text','Cambia a futuro: “Ella canta”.',NULL,'ella cantará|cantara|cantará|cantara','En futuro: ella cantará.',2),
(17,'multiple','¿Cuál es un verbo?','["comer", "mesa", "azul", "rápido"]','comer','Comer expresa una acción.',1),
(17,'multiple','Los verbos terminados en -ar pertenecen a la…','["primera conjugación", "segunda conjugación", "tercera conjugación", "familia de palabras"]','primera conjugación','La primera conjugación termina en -ar.',2),
(17,'multiple','Los verbos terminados en -er pertenecen a la…','["segunda conjugación", "primera conjugación", "tercera conjugación", "acentuación"]','segunda conjugación','La segunda conjugación termina en -er.',2),
(17,'multiple','Los verbos terminados en -ir pertenecen a la…','["tercera conjugación", "primera conjugación", "segunda conjugación", "puntuación"]','tercera conjugación','La tercera conjugación termina en -ir.',2),
(17,'text','Escribe una forma verbal en presente del verbo “leer”.',NULL,'leo|lees|lee|leemos|leéis|leeis|leen','Son formas de presente del verbo leer.',1),
(17,'text','Escribe una frase con un verbo en futuro.',NULL,'respuesta libre','Debe expresar una acción que ocurrirá después.',1),
(18,'multiple','Elige la palabra correcta.','["vaca", "baca animal", "vaka", "baka"]','vaca','El animal se escribe vaca con v.',1),
(18,'multiple','Elige la palabra correcta.','["beber", "veber", "bebér", "vever"]','beber','Beber se escribe con b.',1),
(18,'multiple','Elige la palabra correcta.','["huevo", "uevo", "guevo", "juevo"]','huevo','Huevo se escribe con h inicial.',1),
(18,'multiple','Elige la palabra correcta.','["guitarra", "juitarra", "gitara", "gitarra"]','guitarra','Guitarra se escribe con gu para que suene suave.',1),
(18,'multiple','Elige la palabra correcta.','["jirafa", "girafa", "guirafa", "gerafa"]','jirafa','Jirafa se escribe con j.',1),
(18,'multiple','Elige la palabra correcta.','["llave", "yave", "llabe", "yabe"]','llave','Llave se escribe con ll y v.',1),
(18,'multiple','Elige la palabra correcta.','["ayer", "aller", "ayerh", "hayer"]','ayer','Ayer se escribe con y.',1),
(18,'multiple','Elige la palabra correcta.','["hormiga", "ormiga", "hormija", "ormija"]','hormiga','Hormiga se escribe con h.',1),
(18,'multiple','Elige la palabra correcta.','["viaje", "biage", "viajeh", "viage"]','viaje','Viaje se escribe con v y j.',1),
(18,'multiple','Elige la palabra correcta.','["burro", "vurro", "buro", "vurro"]','burro','Burro se escribe con b.',1),
(18,'text','Completa: Me gusta ___ agua después de correr.',NULL,'beber','Beber se escribe con b.',1),
(18,'text','Completa: Ayer fuimos de ___ al campo.',NULL,'viaje','Viaje se escribe con v y j.',1),
(18,'multiple','¿Cuál de estos verbos es excepción de -bir y se escribe con v?','["vivir", "escribir", "recibir", "subir"]','vivir','Vivir, servir y hervir son excepciones frecuentes.',2),
(18,'multiple','¿Cuál lleva h?','["hielo", "ielo", "yelo", "jielo"]','hielo','Hielo se escribe con h inicial.',1),
(18,'text','Escribe una palabra con “mb”.',NULL,'tambor|bomba|hombre|sombrero|cambio|campo','Antes de b se escribe m.',2),
(18,'multiple','Antes de b se escribe normalmente…','["m", "n", "r", "h"]','m','Se escribe m antes de b y p.',2),
(18,'multiple','Antes de p se escribe normalmente…','["m", "n", "b", "v"]','m','Se escribe m antes de p: campo, tiempo.',2),
(18,'text','Corrige la palabra: “ospital”.',NULL,'hospital','Hospital se escribe con h inicial.',1),
(18,'text','Corrige la palabra: “gerra” para referirse a un conflicto.',NULL,'guerra','Guerra se escribe con gu para sonido suave antes de e.',2),
(18,'multiple','¿Cuál está bien escrita?','["colegio", "colejio", "colegío", "kolegio"]','colegio','Colegio se escribe con g.',1),
(18,'multiple','¿Cuál está bien escrita?','["trabajo", "trabago", "travago", "travajo"]','trabajo','Trabajo se escribe con b y j.',1),
(19,'multiple','La sílaba que suena más fuerte se llama…','["tónica", "átona", "plural", "aguda"]','tónica','La sílaba tónica se pronuncia con más fuerza.',1),
(19,'multiple','Las palabras agudas llevan tilde cuando terminan en…','["vocal, n o s", "cualquier consonante", "r siempre", "z siempre"]','vocal, n o s','Es la regla general de las agudas.',1),
(19,'multiple','Las palabras llanas llevan tilde cuando…','["no terminan en vocal, n o s", "terminan en vocal siempre", "son muy largas", "terminan en n siempre"]','no terminan en vocal, n o s','Es la regla general de las llanas.',1),
(19,'multiple','Las palabras esdrújulas…','["siempre llevan tilde", "nunca llevan tilde", "solo llevan tilde si acaban en vocal", "solo si son nombres propios"]','siempre llevan tilde','Todas las esdrújulas llevan tilde.',1),
(19,'multiple','“Camión” es una palabra…','["aguda", "llana", "esdrújula", "monosílaba sin tilde"]','aguda','La fuerza recae en la última sílaba: camión.',1),
(19,'multiple','“Árbol” es una palabra…','["llana", "aguda", "esdrújula", "sobresdrújula"]','llana','La fuerza recae en la penúltima sílaba y lleva tilde por acabar en l.',1),
(19,'multiple','“Música” es una palabra…','["esdrújula", "aguda", "llana", "sin sílaba tónica"]','esdrújula','La fuerza recae en la antepenúltima sílaba.',1),
(19,'text','Separa en sílabas: “camino”.',NULL,'ca-mi-no|ca mi no','Camino se divide ca-mi-no.',1),
(19,'text','¿Cuál es la sílaba tónica de “zapato”?',NULL,'pa','Za-PA-to: la fuerza está en pa.',1),
(19,'text','Pon tilde si hace falta: “lapiz”.',NULL,'lápiz|lapiz','Lápiz es llana y termina en z, por eso lleva tilde.',1),
(19,'text','Pon tilde si hace falta: “cafe”.',NULL,'café|cafe','Café es aguda terminada en vocal, lleva tilde.',1),
(19,'text','Pon tilde si hace falta: “musica”.',NULL,'música|musica','Música es esdrújula, siempre lleva tilde.',1),
(19,'multiple','¿Cuál está bien acentuada?','["teléfono", "telefono", "teléfonó", "telefonó"]','teléfono','Teléfono es esdrújula y lleva tilde en lé.',1),
(19,'multiple','¿Cuál NO lleva tilde?','["mesa", "árbol", "café", "música"]','mesa','Mesa es llana terminada en vocal, no lleva tilde.',1),
(19,'text','Clasifica “pared”: aguda, llana o esdrújula.',NULL,'aguda','Pa-red tiene la fuerza en la última sílaba.',1),
(19,'text','Clasifica “casa”: aguda, llana o esdrújula.',NULL,'llana','Ca-sa tiene la fuerza en la penúltima sílaba.',1),
(19,'text','Clasifica “pájaro”: aguda, llana o esdrújula.',NULL,'esdrújula|esdrujula','Pá-ja-ro tiene la fuerza en la antepenúltima sílaba.',1),
(19,'multiple','“Ratón” lleva tilde porque…','["es aguda terminada en n", "es llana terminada en vocal", "es esdrújula", "es plural"]','es aguda terminada en n','Ratón es aguda y termina en n.',2),
(19,'multiple','“Azúcar” lleva tilde porque…','["es llana y no termina en vocal, n o s", "es aguda terminada en vocal", "es esdrújula", "todas las palabras con z llevan tilde"]','es llana y no termina en vocal, n o s','Azúcar es llana terminada en r.',2),
(19,'multiple','¿Qué palabra es esdrújula?','["brújula", "cantar", "mesa", "reloj"]','brújula','Brújula tiene la fuerza en la antepenúltima sílaba.',1),
(20,'multiple','¿Qué signo cierra una pregunta?','["?", "!", ".", ","]','?','Las preguntas terminan con signo de interrogación de cierre.',1),
(20,'multiple','En español, una pregunta debe llevar…','["¿ y ?", "solo ?", "solo ¿", "dos puntos siempre"]','¿ y ?','En español usamos signos de apertura y cierre.',1),
(20,'multiple','¿Qué signo usamos para separar elementos de una lista?','["coma", "punto final", "raya", "paréntesis siempre"]','coma','La coma separa elementos enumerados.',1),
(20,'multiple','¿Qué signo termina una oración enunciativa?','["punto", "coma", "dos puntos", "raya"]','punto','El punto marca el final de una oración.',1),
(20,'multiple','¿Qué signos usamos para expresar sorpresa?','["¡ y !", "¿ y ?", "; y :", "( y )"]','¡ y !','Los signos de exclamación expresan emoción o sorpresa.',1),
(20,'text','Escribe correctamente la pregunta: “como te llamas”',NULL,'¿Cómo te llamas?|como te llamas','Debe llevar signos de interrogación y tilde en cómo.',1),
(20,'text','Escribe correctamente la exclamación: “que alegria”',NULL,'¡Qué alegría!|que alegria','Debe llevar signos de exclamación y tildes.',1),
(20,'multiple','Elige la frase bien puntuada.','["Compré pan, leche y queso.", "Compré pan leche y queso", "Compré, pan leche, y queso", "Compré pan leche y, queso"]','Compré pan, leche y queso.','La coma separa elementos de una lista.',1),
(20,'multiple','Los dos puntos pueden usarse antes de…','["una enumeración", "una multiplicación siempre", "una sílaba tónica", "un eje de simetría"]','una enumeración','Ejemplo: Compré: pan, leche y fruta.',2),
(20,'multiple','La raya de diálogo se usa para…','["indicar que habla un personaje", "separar decimales", "marcar una suma", "hacer una tabla"]','indicar que habla un personaje','En narraciones, la raya introduce intervenciones de personajes.',2),
(20,'text','Añade coma: “Traje lápices gomas reglas y colores”.',NULL,'Traje lápices, gomas, reglas y colores.|lapices, gomas, reglas y colores','La coma separa los elementos de una enumeración.',1),
(20,'multiple','¿Qué falta en “Dónde está mi mochila?”?','["signo de apertura ¿", "un punto y coma", "una raya", "un guion al final"]','signo de apertura ¿','En español: ¿Dónde está mi mochila?',1),
(20,'multiple','¿Qué oración necesita signos de exclamación?','["Qué susto", "Me llamo Ana", "Vivo en Cádiz", "Tengo un lápiz"]','Qué susto','Expresa sorpresa o emoción.',1),
(20,'text','Corrige: “hola leire.”',NULL,'Hola, Leire.|Hola Leire.','Una oración empieza con mayúscula y puede llevar coma para llamar a alguien.',2),
(20,'multiple','Después de punto se escribe…','["mayúscula", "minúscula siempre", "número", "coma"]','mayúscula','Tras punto comienza una nueva oración con mayúscula.',1),
(20,'text','Escribe una oración con signos de interrogación.',NULL,'respuesta libre','Debe ser una pregunta con ¿ y ?.',1),
(20,'text','Escribe una oración con signos de exclamación.',NULL,'respuesta libre','Debe expresar emoción con ¡ y !.',1),
(20,'multiple','¿Cuál está correctamente escrita?','["¿Vienes al parque?", "Vienes al parque?", "¿Vienes al parque", "vienes al parque?"]','¿Vienes al parque?','Tiene apertura, cierre y mayúscula inicial.',1),
(20,'multiple','La coma en “Sí, quiero” cambia…','["la pausa y el sentido", "la masa", "la longitud", "el número de sílabas siempre"]','la pausa y el sentido','La puntuación ayuda a entender correctamente.',2),
(20,'multiple','Un párrafo termina normalmente con…','["punto y aparte", "coma", "guion", "dos puntos siempre"]','punto y aparte','El punto y aparte separa párrafos.',1),
(21,'multiple','Sinónimo de “alegre”.','["contento", "triste", "lento", "oscuro"]','contento','Alegre y contento tienen significado parecido.',1),
(21,'multiple','Antónimo de “alto”.','["bajo", "grande", "elevado", "gigante"]','bajo','Alto y bajo son contrarios.',1),
(21,'multiple','Sinónimo de “rápido”.','["veloz", "lento", "quieto", "pequeño"]','veloz','Rápido y veloz significan parecido.',1),
(21,'multiple','Antónimo de “frío”.','["caliente", "helado", "fresco", "nieve"]','caliente','Frío y caliente son contrarios.',1),
(21,'multiple','Una palabra polisémica es…','["una palabra con varios significados", "una palabra sin vocales", "una palabra inventada", "un verbo siempre"]','una palabra con varios significados','Por ejemplo, banco puede ser asiento o entidad.',1),
(21,'multiple','“Banco” puede significar…','["asiento y entidad de dinero", "solo animal", "solo color", "solo número"]','asiento y entidad de dinero','Banco tiene varios significados.',1),
(21,'multiple','Campo semántico de “colegio”:','["pizarra, lápiz, clase", "pez, mar, barco", "médico, vacuna, hospital", "tren, vía, estación"]','pizarra, lápiz, clase','Son palabras relacionadas con colegio.',1),
(21,'text','Escribe un antónimo de “grande”.',NULL,'pequeño|pequeno','Grande y pequeño son contrarios.',1),
(21,'text','Escribe un sinónimo de “bonito”.',NULL,'hermoso|precioso|lindo|bello','Son palabras de significado parecido.',1),
(21,'text','Escribe un antónimo de “entrar”.',NULL,'salir','Entrar y salir son acciones contrarias.',1),
(21,'multiple','¿Cuál pertenece al campo semántico de “frutas”?','["manzana", "silla", "lápiz", "coche"]','manzana','Manzana es una fruta.',1),
(21,'multiple','¿Cuál pertenece al campo semántico de “transportes”?','["autobús", "perro", "plato", "flor"]','autobús','El autobús es un medio de transporte.',1),
(21,'text','Escribe dos palabras del campo semántico de “mar”.',NULL,'respuesta libre','Pueden ser: ola, playa, barco, arena, pez...',1),
(21,'multiple','¿Cuál es antónimo de “abrir”?','["cerrar", "mirar", "saltar", "leer"]','cerrar','Abrir y cerrar son contrarios.',1),
(21,'multiple','¿Cuál es sinónimo de “terminar”?','["acabar", "empezar", "romper", "comprar"]','acabar','Terminar y acabar tienen significado parecido.',1),
(21,'multiple','“Ratón” puede ser animal y también…','["dispositivo de ordenador", "tipo de nube", "unidad de masa", "provincia"]','dispositivo de ordenador','Ratón es una palabra polisémica.',2),
(21,'text','Escribe una palabra polisémica.',NULL,'banco|ratón|raton|copa|carta|planta|sierra','Son palabras con más de un significado.',2),
(21,'multiple','¿Cuál es una pareja de sinónimos?','["feliz-contento", "alto-bajo", "entrar-salir", "frío-caliente"]','feliz-contento','Feliz y contento tienen significado parecido.',1),
(21,'multiple','¿Cuál es una pareja de antónimos?','["día-noche", "bonito-hermoso", "rápido-veloz", "casa-hogar"]','día-noche','Día y noche son contrarios.',1),
(21,'text','Cambia “La casa es grande” usando un sinónimo de grande.',NULL,'respuesta libre','Puede ser enorme, amplia, gigantesca...',1),
(21,'multiple','Las palabras “flor, árbol, hierba” pertenecen al campo semántico de…','["plantas", "coches", "ropa", "deportes"]','plantas','Todas son plantas o partes del mundo vegetal.',1),
(22,'multiple','La palabra primitiva de “panadero” es…','["pan", "panadería", "panecillo", "empanar"]','pan','Panadero deriva de pan.',1),
(22,'multiple','¿Cuál pertenece a la familia de “mar”?','["marino", "mesa", "lápiz", "correr"]','marino','Marino comparte raíz con mar.',1),
(22,'multiple','Un prefijo va…','["delante de la raíz", "detrás de la raíz", "solo al final de una frase", "encima de una tilde"]','delante de la raíz','Los prefijos se añaden al principio.',1),
(22,'multiple','Un sufijo va…','["detrás de la raíz", "delante de la raíz", "en medio de una coma", "antes del artículo"]','detrás de la raíz','Los sufijos se añaden al final.',1),
(22,'multiple','El prefijo “des-” suele indicar…','["negación o contrario", "tamaño pequeño siempre", "color", "lugar"]','negación o contrario','Deshacer significa hacer lo contrario de hacer.',1),
(22,'multiple','El sufijo “-ito/-ita” puede indicar…','["tamaño pequeño o cariño", "contrario", "lugar lejano", "número exacto"]','tamaño pequeño o cariño','Casita puede ser casa pequeña o expresiva.',1),
(22,'text','Escribe una palabra de la familia de “flor”.',NULL,'florero|florista|florería|floreria|florecer|florecilla','Comparten la raíz flor.',1),
(22,'text','Escribe una palabra de la familia de “zapato”.',NULL,'zapatero|zapatería|zapateria|zapatilla|zapatear','Comparten la raíz zapat-.',1),
(22,'multiple','¿Cuál tiene prefijo?','["desorden", "ordenado", "orden", "ordenar"]','desorden','Des- aparece delante de la raíz.',1),
(22,'multiple','¿Cuál tiene sufijo?','["casita", "casa", "mi casa", "la casa"]','casita','-ita es sufijo.',1),
(22,'text','Añade el prefijo “re-” a “leer”.',NULL,'releer','Releer significa leer otra vez.',1),
(22,'text','Añade el prefijo “des-” a “hacer”.',NULL,'deshacer','Deshacer significa hacer lo contrario.',1),
(22,'multiple','¿Qué palabra NO pertenece a la familia de “pan”?','["panadero", "panadería", "panecillo", "pantalón"]','pantalón','Pantalón no viene de pan.',1),
(22,'multiple','¿Qué palabra pertenece a la familia de “libro”?','["librería", "libre", "libertad", "liebre"]','librería','Librería comparte raíz con libro.',1),
(22,'text','Escribe la palabra primitiva de “jardinero”.',NULL,'jardín|jardin','Jardinero deriva de jardín.',1),
(22,'multiple','El prefijo “sub-” puede significar…','["debajo", "encima siempre", "muy grande", "dos veces"]','debajo','Subterráneo está debajo de la tierra.',3),
(22,'multiple','El prefijo “pre-” puede significar…','["antes", "después", "contra", "pequeño"]','antes','Prehistoria significa antes de la historia escrita.',2),
(22,'text','Forma una palabra con el sufijo “-ero” a partir de “pan”.',NULL,'panadero','Panadero se forma con pan + -adero/-ero.',1),
(22,'multiple','¿Cuál es derivada?','["florero", "flor", "mar", "sol"]','florero','Florero deriva de flor.',1),
(22,'text','Escribe una familia de palabras con “mar”: al menos dos palabras.',NULL,'respuesta libre','Ejemplos: mar, marinero, marino, marea.',1),
(22,'multiple','Las palabras con la misma raíz forman…','["una familia de palabras", "una gráfica", "un diálogo", "un ángulo"]','una familia de palabras','Comparten una parte común y significado relacionado.',1),
(23,'multiple','Un texto narrativo sirve para…','["contar una historia", "medir una distancia", "hacer una división", "clasificar animales"]','contar una historia','La narración cuenta hechos reales o imaginarios.',1),
(23,'multiple','Las partes básicas de un cuento son…','["inicio, nudo y desenlace", "suma, resta y producto", "norte, sur y este", "litro, metro y gramo"]','inicio, nudo y desenlace','Es la estructura habitual de una narración.',1),
(23,'multiple','El personaje principal es…','["el protagonista", "el título", "el punto final", "el lugar siempre"]','el protagonista','El protagonista es quien tiene el papel principal.',1),
(23,'multiple','El narrador es…','["quien cuenta la historia", "quien dibuja la portada", "un signo de puntuación", "un adjetivo"]','quien cuenta la historia','El narrador relata los hechos.',1),
(23,'multiple','El nudo de una historia contiene…','["el problema o conflicto", "solo el saludo", "la lista de precios", "la moraleja siempre"]','el problema o conflicto','En el nudo ocurre el conflicto principal.',1),
(23,'multiple','El desenlace es…','["la solución o final", "la primera palabra", "un diálogo sin personajes", "una descripción de objetos"]','la solución o final','El desenlace resuelve la historia.',1),
(23,'text','Escribe un posible protagonista para un cuento.',NULL,'respuesta libre','Puede ser una persona, animal o personaje imaginario.',1),
(23,'text','Escribe un lugar donde podría ocurrir una historia.',NULL,'respuesta libre','El lugar puede ser real o imaginario.',1),
(23,'text','Inventa un título para un cuento de aventuras.',NULL,'respuesta libre','Debe ser un título relacionado con una aventura.',1),
(23,'multiple','¿Cuál es un conector temporal?','["después", "azul", "mesa", "alto"]','después','Los conectores temporales ordenan acciones.',1),
(23,'multiple','¿Cuál sería un buen inicio de cuento?','["Érase una vez...", "El resultado es 24", "1 kg = 1000 g", "El verbo es cantar"]','Érase una vez...','Es una fórmula típica de inicio narrativo.',1),
(23,'multiple','¿Qué elemento ayuda a saber cómo son los personajes?','["descripción", "división", "gráfica", "capacidad"]','descripción','Describir un personaje ayuda a imaginarlo.',1),
(23,'text','Escribe una frase para empezar una historia.',NULL,'respuesta libre','Puede empezar presentando personaje, lugar o tiempo.',1),
(23,'text','Escribe una frase de diálogo entre dos personajes.',NULL,'respuesta libre','Debe parecer una intervención de un personaje.',1),
(23,'multiple','La moraleja es…','["una enseñanza", "una moneda", "una unidad de longitud", "un determinante"]','una enseñanza','Algunos cuentos terminan con una enseñanza.',1),
(23,'multiple','¿Cuál es un personaje secundario?','["un personaje que acompaña al protagonista", "el título", "el problema siempre", "la última palabra"]','un personaje que acompaña al protagonista','No es el principal, pero participa en la historia.',1),
(23,'text','Ordena: primero, luego y finalmente son palabras para…',NULL,'ordenar una historia|ordenar hechos|ordenar acciones','Sirven para contar hechos en orden.',1),
(23,'multiple','Una narración puede estar escrita en…','["primera o tercera persona", "kilómetros o metros", "singular solo", "mayúsculas siempre"]','primera o tercera persona','El narrador puede contar desde “yo” o desde fuera.',1),
(23,'text','Escribe un problema que podría aparecer en un cuento.',NULL,'respuesta libre','El problema debe poder resolverse en la historia.',1),
(23,'text','Escribe un final feliz para una historia.',NULL,'respuesta libre','Debe cerrar la historia de forma positiva.',1),
(24,'multiple','Un texto informativo sirve para…','["explicar datos o hechos", "contar siempre una fantasía", "hacer operaciones", "decorar una página"]','explicar datos o hechos','El texto informativo transmite información.',1),
(24,'multiple','Una noticia debe responder a preguntas como…','["qué, quién, cuándo y dónde", "suma, resta y división", "norte y sur", "singular y plural"]','qué, quién, cuándo y dónde','Son preguntas básicas para informar.',1),
(24,'multiple','Antes de escribir conviene…','["planificar las ideas", "empezar sin pensar", "no revisar", "borrar el título"]','planificar las ideas','Planificar ayuda a ordenar el texto.',1),
(24,'multiple','Después de escribir conviene…','["revisar y corregir", "tirar la hoja", "quitar los puntos", "mezclar párrafos"]','revisar y corregir','La revisión mejora ortografía, orden y claridad.',1),
(24,'multiple','Un párrafo contiene…','["ideas relacionadas", "solo números", "solo signos", "una sola letra"]','ideas relacionadas','Cada párrafo desarrolla una idea o parte del tema.',1),
(24,'multiple','Un resumen debe incluir…','["lo más importante", "todos los detalles pequeños", "solo el título", "palabras al azar"]','lo más importante','El resumen reduce el texto a ideas principales.',1),
(24,'text','Escribe un título para un texto sobre animales marinos.',NULL,'respuesta libre','Debe anticipar el tema del texto.',1),
(24,'text','Escribe una frase informativa sobre Andalucía.',NULL,'respuesta libre','Debe aportar un dato o idea sobre Andalucía.',1),
(24,'multiple','¿Cuál es una descripción?','["Mi mochila es azul y grande.", "¡Ay!", "¿Vienes?", "Corre rápido."]','Mi mochila es azul y grande.','Describe cómo es un objeto.',1),
(24,'multiple','¿Qué conectores ordenan ideas?','["primero, además, finalmente", "rojo, verde, azul", "kg, g, L", "yo, tú, él"]','primero, además, finalmente','Ayudan a organizar el texto.',1),
(24,'text','Escribe una oración para terminar una carta.',NULL,'respuesta libre','Puede ser una despedida adecuada.',1),
(24,'text','Escribe una frase con “además”.',NULL,'respuesta libre','Debe usar el conector además para añadir información.',1),
(24,'multiple','Una carta suele tener…','["saludo, cuerpo y despedida", "solo operaciones", "eje de simetría", "tabla de multiplicar"]','saludo, cuerpo y despedida','Son partes habituales de una carta.',1),
(24,'multiple','En una noticia, el titular debe ser…','["claro y breve", "muy secreto", "sin relación", "solo una coma"]','claro y breve','El titular resume la noticia y llama la atención.',1),
(24,'text','Corrige esta idea para que sea más completa: “Perros buenos”.',NULL,'respuesta libre','Debe convertirse en una frase con sentido, por ejemplo: Los perros son animales fieles.',1),
(24,'multiple','¿Qué texto usarías para explicar cómo cuidar una planta?','["texto instructivo o informativo", "cuento de dragones", "poema sin instrucciones", "lista de compras siempre"]','texto instructivo o informativo','Explica pasos o información útil.',1),
(24,'text','Escribe tres palabras clave para un texto sobre el agua.',NULL,'respuesta libre','Pueden ser río, lluvia, mar, potable, ahorrar...',1),
(24,'multiple','Para que un texto sea claro, las ideas deben estar…','["ordenadas", "mezcladas", "repetidas sin sentido", "sin puntos"]','ordenadas','El orden facilita la comprensión.',1),
(24,'text','Escribe una frase de introducción para un texto sobre el reciclaje.',NULL,'respuesta libre','Debe presentar el tema del reciclaje.',1),
(24,'multiple','¿Qué conviene evitar en un resumen?','["copiar todo el texto", "usar ideas principales", "escribir claro", "respetar el orden"]','copiar todo el texto','Resumir no es copiar todo, sino seleccionar lo esencial.',1),
(25,'multiple','¿Cuáles son las funciones vitales?','["nutrición, relación y reproducción", "suma, resta y división", "norte, sur y este", "sólido, líquido y gaseoso"]','nutrición, relación y reproducción','Son las tres funciones vitales principales.',1),
(25,'multiple','La nutrición sirve para…','["obtener materia y energía", "cambiar de idioma", "dibujar mapas", "formar sílabas"]','obtener materia y energía','Los seres vivos necesitan nutrientes y energía.',1),
(25,'multiple','La función de relación permite…','["captar cambios y responder", "hacer una gráfica", "medir longitud", "escribir cuentos"]','captar cambios y responder','Los seres vivos se relacionan con el medio.',1),
(25,'multiple','La reproducción sirve para…','["tener descendencia", "pesar objetos", "orientarse", "redondear números"]','tener descendencia','Permite que haya nuevos seres vivos.',1),
(25,'multiple','¿Cuál es un ser vivo?','["pino", "roca", "silla", "vaso"]','pino','Un pino nace, crece, se nutre y se reproduce.',1),
(25,'multiple','¿Cuál no es un ser vivo?','["piedra", "perro", "girasol", "hongo"]','piedra','Una piedra no realiza funciones vitales.',1),
(25,'text','Escribe una función vital.',NULL,'nutrición|nutricion|relación|relacion|reproducción|reproduccion','Son nutrición, relación y reproducción.',1),
(25,'multiple','Los hongos son…','["seres vivos", "máquinas", "minerales", "mapas"]','seres vivos','Los hongos realizan funciones vitales.',1),
(25,'multiple','Un organismo autótrofo…','["fabrica su propio alimento", "come siempre carne", "no necesita energía", "es un mineral"]','fabrica su propio alimento','Las plantas son organismos autótrofos.',2),
(25,'multiple','Un organismo heterótrofo…','["se alimenta de otros seres vivos o sus restos", "fabrica alimento solo con luz", "no se nutre", "es una roca"]','se alimenta de otros seres vivos o sus restos','Los animales son heterótrofos.',2),
(25,'text','Escribe un ejemplo de ser vivo.',NULL,'respuesta libre','Puede ser animal, planta, hongo u otro organismo.',1),
(25,'multiple','¿Qué necesitan muchos seres vivos para respirar?','["oxígeno", "oro", "plástico", "arena"]','oxígeno','Muchos seres vivos utilizan oxígeno para respirar.',1),
(25,'multiple','Las células son…','["unidades básicas de los seres vivos", "un tipo de mapa", "un instrumento musical", "monedas antiguas"]','unidades básicas de los seres vivos','Todos los seres vivos están formados por células.',2),
(25,'multiple','¿Qué grupo incluye animales como perro, gato y delfín?','["mamíferos", "insectos", "plantas", "hongos"]','mamíferos','Son vertebrados mamíferos.',1),
(25,'multiple','Para cuidar a los seres vivos debemos…','["respetar sus hábitats", "tirar basura al campo", "arrancar plantas sin motivo", "malgastar agua"]','respetar sus hábitats','Cuidar los hábitats protege la vida.',1),
(26,'multiple','Los vertebrados tienen…','["columna vertebral", "seis patas siempre", "alas siempre", "caparazón siempre"]','columna vertebral','La columna vertebral es característica de vertebrados.',1),
(26,'multiple','Los invertebrados…','["no tienen columna vertebral", "son todos mamíferos", "viven solo en el mar", "tienen pelo siempre"]','no tienen columna vertebral','Invertebrado significa sin columna vertebral.',1),
(26,'multiple','¿Cuál es vertebrado?','["pez", "mariposa", "caracol", "medusa"]','pez','Los peces tienen columna vertebral.',1),
(26,'multiple','¿Cuál es invertebrado?','["abeja", "perro", "águila", "rana"]','abeja','Los insectos son invertebrados.',1),
(26,'multiple','Las aves tienen…','["plumas", "escamas húmedas", "pelo y leche", "caparazón siempre"]','plumas','Las plumas son propias de las aves.',1),
(26,'multiple','Los mamíferos normalmente…','["nacen del vientre de su madre y maman", "tienen seis patas", "son todos ovíparos", "no respiran"]','nacen del vientre de su madre y maman','La mayoría son vivíparos y alimentan a sus crías con leche.',1),
(26,'multiple','Los reptiles suelen tener…','["escamas y respiración pulmonar", "plumas", "piel desnuda y húmeda siempre", "branquias toda la vida"]','escamas y respiración pulmonar','Lagartos y serpientes son reptiles.',1),
(26,'multiple','Los anfibios tienen la piel…','["húmeda", "con plumas", "de metal", "con pelo"]','húmeda','Ranas y sapos tienen piel húmeda.',1),
(26,'text','Escribe un animal invertebrado.',NULL,'mariposa|abeja|caracol|medusa|araña|arana|mosquito|hormiga','Son ejemplos de invertebrados.',1),
(26,'text','Escribe un animal vertebrado.',NULL,'perro|gato|pez|rana|águila|aguila|caballo|delfín|delfin','Son animales con columna vertebral.',1),
(26,'multiple','Los peces respiran principalmente por…','["branquias", "pulmones con pelo", "alas", "raíces"]','branquias','Las branquias les permiten obtener oxígeno del agua.',1),
(26,'multiple','Los animales ovíparos nacen de…','["huevos", "semillas", "rocas", "nubes"]','huevos','Ovíparo significa que nace de huevo.',1),
(26,'multiple','Los animales vivíparos nacen…','["del vientre de la madre", "de huevos siempre", "de flores", "de ramas"]','del vientre de la madre','Muchos mamíferos son vivíparos.',1),
(26,'multiple','¿Cuál es un insecto?','["hormiga", "sardina", "paloma", "lagarto"]','hormiga','Los insectos son invertebrados con seis patas.',1),
(26,'multiple','¿Qué grupo incluye tiburón y sardina?','["peces", "aves", "anfibios", "reptiles"]','peces','Ambos viven en el agua y son peces.',1),
(27,'multiple','La raíz sirve principalmente para…','["sujetar la planta y absorber agua", "hacer ruido", "volar", "fabricar semillas siempre"]','sujetar la planta y absorber agua','La raíz absorbe agua y sales minerales.',1),
(27,'multiple','Las hojas ayudan a la planta a…','["fabricar alimento", "caminar", "masticar", "beber leche"]','fabricar alimento','En las hojas se realiza gran parte de la fotosíntesis.',1),
(27,'multiple','El tallo sirve para…','["sostener la planta y transportar sustancias", "producir pelo", "dibujar mapas", "respirar como pez"]','sostener la planta y transportar sustancias','El tallo sostiene hojas, flores y frutos.',1),
(27,'multiple','La fotosíntesis necesita…','["luz, agua, dióxido de carbono y sales minerales", "solo arena", "solo oscuridad", "plástico y metal"]','luz, agua, dióxido de carbono y sales minerales','Con esos elementos la planta fabrica alimento.',2),
(27,'multiple','Las plantas producen…','["oxígeno", "plástico", "monedas", "fuego siempre"]','oxígeno','Durante la fotosíntesis liberan oxígeno.',1),
(27,'text','Escribe una parte de la planta.',NULL,'raíz|raiz|tallo|hoja|flor|fruto|semilla','Son partes de las plantas.',1),
(27,'multiple','Las semillas sirven para…','["formar nuevas plantas", "medir masa", "crear lluvia", "hacer mapas"]','formar nuevas plantas','De una semilla puede nacer una nueva planta.',1),
(27,'multiple','¿Qué parte suele atraer insectos polinizadores?','["flor", "raíz", "tallo bajo tierra", "piedra"]','flor','Muchas flores atraen polinizadores.',1),
(27,'multiple','El fruto protege muchas veces a…','["las semillas", "las raíces", "las nubes", "los huesos"]','las semillas','Muchos frutos contienen semillas.',1),
(27,'multiple','Una planta sin suficiente agua puede…','["marchitarse", "convertirse en roca", "volar", "hacer ruido"]','marchitarse','El agua es necesaria para vivir.',1),
(27,'multiple','¿Qué gas toman las plantas para la fotosíntesis?','["dióxido de carbono", "helio", "humo siempre", "oxígeno solamente"]','dióxido de carbono','Usan dióxido de carbono y liberan oxígeno.',2),
(27,'text','¿Qué necesitan las plantas para vivir?',NULL,'agua|luz|sales minerales|aire|sol','Necesitan agua, luz, aire y nutrientes minerales.',1),
(27,'multiple','Las plantas son seres vivos porque…','["realizan funciones vitales", "son verdes siempre", "no cambian", "son objetos"]','realizan funciones vitales','Nacen, crecen, se nutren, se relacionan y se reproducen.',1),
(27,'multiple','¿Qué parte absorbe agua del suelo?','["raíz", "flor", "fruto", "semilla seca"]','raíz','La raíz absorbe agua y sales minerales.',1),
(27,'multiple','Cuidar las plantas ayuda a…','["mejorar el entorno y producir oxígeno", "gastar más agua sin control", "tener menos sombra", "ensuciar el aire"]','mejorar el entorno y producir oxígeno','Las plantas son importantes para los ecosistemas.',1),
(28,'multiple','Un ecosistema está formado por…','["seres vivos, medio físico y relaciones", "solo animales", "solo agua", "solo rocas"]','seres vivos, medio físico y relaciones','Incluye biocenosis, biotopo y relaciones.',1),
(28,'multiple','Un productor en una cadena alimentaria suele ser…','["una planta", "un león", "un hongo siempre", "una roca"]','una planta','Las plantas producen su propio alimento.',1),
(28,'multiple','Un consumidor se alimenta de…','["otros seres vivos o sus partes", "luz solamente", "piedras", "aire sin más"]','otros seres vivos o sus partes','Los animales son consumidores.',1),
(28,'multiple','Los descomponedores ayudan a…','["reciclar restos de seres vivos", "crear plástico", "apagar el sol", "medir longitudes"]','reciclar restos de seres vivos','Hongos y bacterias descomponen restos.',2),
(28,'multiple','¿Cuál es un ecosistema acuático?','["laguna", "desierto", "bosque", "pradera"]','laguna','Una laguna es un ecosistema de agua.',1),
(28,'multiple','¿Cuál es un ecosistema terrestre?','["bosque", "océano", "río", "lago"]','bosque','Un bosque es terrestre.',1),
(28,'multiple','En una cadena “hierba → conejo → zorro”, ¿quién es productor?','["hierba", "conejo", "zorro", "todos"]','hierba','La hierba fabrica su alimento.',1),
(28,'multiple','En esa cadena, ¿quién se come al conejo?','["zorro", "hierba", "sol", "agua"]','zorro','La flecha indica que el zorro se alimenta del conejo.',1),
(28,'multiple','El hábitat es…','["el lugar donde vive un ser vivo", "una operación matemática", "una tilde", "un tipo de moneda"]','el lugar donde vive un ser vivo','Cada especie vive en un hábitat adecuado.',1),
(28,'text','Escribe un ecosistema.',NULL,'bosque|río|rio|mar|desierto|laguna|pradera|playa|montaña|montana','Son ejemplos de ecosistemas.',1),
(28,'multiple','Para proteger un ecosistema debemos…','["no contaminar y respetar especies", "tirar basura", "hacer fuego sin control", "arrancar plantas protegidas"]','no contaminar y respetar especies','La protección evita daños al medio.',1),
(28,'multiple','La contaminación puede…','["dañar seres vivos y hábitats", "mejorar siempre el agua", "crear más oxígeno siempre", "no afectar nunca"]','dañar seres vivos y hábitats','Contaminar altera los ecosistemas.',1),
(28,'multiple','Una relación de alimentación se representa con…','["cadena alimentaria", "eje de simetría", "tabla de verbos", "calendario"]','cadena alimentaria','Muestra relaciones tróficas.',1),
(28,'multiple','Si desaparecen muchas plantas, los herbívoros…','["pueden quedarse sin alimento", "tienen más comida", "se vuelven rocas", "no cambian nunca"]','pueden quedarse sin alimento','Los productores son base de muchas cadenas.',2),
(28,'multiple','Los seres vivos de un ecosistema dependen…','["unos de otros y del medio", "solo de los mapas", "solo del dinero", "de las tildes"]','unos de otros y del medio','Hay relaciones entre seres vivos y condiciones físicas.',1),
(29,'multiple','El aparato digestivo sirve para…','["obtener nutrientes de los alimentos", "respirar aire", "mover huesos", "ver colores"]','obtener nutrientes de los alimentos','La digestión transforma alimentos en nutrientes.',1),
(29,'multiple','El aparato respiratorio sirve para…','["tomar oxígeno y expulsar dióxido de carbono", "hacer la digestión", "pensar", "masticar"]','tomar oxígeno y expulsar dióxido de carbono','Respiramos para intercambiar gases.',1),
(29,'multiple','El corazón pertenece al aparato…','["circulatorio", "digestivo", "respiratorio", "locomotor"]','circulatorio','El corazón impulsa la sangre.',1),
(29,'multiple','Los huesos y músculos ayudan a…','["movernos y sostener el cuerpo", "digerir alimentos", "fabricar oxígeno", "leer mejor"]','movernos y sostener el cuerpo','Forman parte del aparato locomotor.',1),
(29,'multiple','Un hábito saludable es…','["hacer ejercicio y dormir bien", "comer solo dulces", "no lavarse las manos", "no beber agua"]','hacer ejercicio y dormir bien','El ejercicio y el descanso cuidan la salud.',1),
(29,'multiple','Una dieta equilibrada incluye…','["alimentos variados", "solo chucherías", "solo refrescos", "ninguna fruta"]','alimentos variados','Conviene comer de varios grupos de alimentos.',1),
(29,'text','Escribe un hábito saludable.',NULL,'hacer ejercicio|dormir|comer fruta|beber agua|lavarse las manos|higiene|descansar','Son hábitos que cuidan la salud.',1),
(29,'multiple','Lavarse las manos ayuda a…','["prevenir enfermedades", "aumentar longitud", "crear electricidad", "hacer mapas"]','prevenir enfermedades','Reduce la transmisión de gérmenes.',1),
(29,'multiple','Los sentidos nos permiten…','["recibir información del entorno", "hacer la digestión", "sumar más rápido", "formar montañas"]','recibir información del entorno','Vista, oído, olfato, gusto y tacto captan información.',1),
(29,'multiple','¿Qué sentido usamos para escuchar?','["oído", "vista", "olfato", "tacto"]','oído','El oído permite oír sonidos.',1),
(29,'multiple','¿Qué sentido usamos para oler?','["olfato", "gusto", "vista", "tacto"]','olfato','El olfato percibe olores.',1),
(29,'multiple','Los nutrientes dan al cuerpo…','["energía y materiales", "solo color", "monedas", "sombra"]','energía y materiales','Sirven para funcionar, crecer y reparar.',1),
(29,'multiple','Beber agua es importante porque…','["el cuerpo necesita hidratarse", "sustituye siempre a la comida", "hace innecesario dormir", "es un sólido"]','el cuerpo necesita hidratarse','La hidratación es básica para la salud.',1),
(29,'multiple','Dormir poco puede producir…','["cansancio y falta de atención", "más energía siempre", "más huesos", "mejor digestión siempre"]','cansancio y falta de atención','El descanso ayuda al cuerpo y al cerebro.',1),
(29,'multiple','La prevención consiste en…','["evitar problemas antes de que ocurran", "curar tarde siempre", "no hacer nada", "olvidar hábitos"]','evitar problemas antes de que ocurran','Prevenir es actuar para reducir riesgos.',2),
(30,'multiple','La materia es todo lo que…','["tiene masa y ocupa espacio", "solo está vivo", "no pesa", "es invisible siempre"]','tiene masa y ocupa espacio','Es una definición básica de materia.',1),
(30,'multiple','Los estados principales de la materia son…','["sólido, líquido y gaseoso", "norte, sur y este", "aguda, llana y esdrújula", "suma, resta y producto"]','sólido, líquido y gaseoso','Son estados frecuentes de la materia.',1),
(30,'multiple','El hielo está en estado…','["sólido", "líquido", "gaseoso", "energético"]','sólido','El hielo conserva forma propia.',1),
(30,'multiple','El agua de un vaso está en estado…','["líquido", "sólido", "gaseoso", "mineral siempre"]','líquido','Los líquidos toman la forma del recipiente.',1),
(30,'multiple','El vapor de agua es estado…','["gaseoso", "sólido", "líquido", "vegetal"]','gaseoso','El vapor es agua en forma de gas.',1),
(30,'multiple','La evaporación es el paso de…','["líquido a gas", "gas a sólido", "sólido a líquido", "luz a materia"]','líquido a gas','Cuando el agua se evapora pasa a gas.',1),
(30,'multiple','La fusión es el paso de…','["sólido a líquido", "líquido a sólido", "gas a líquido", "sólido a gas siempre"]','sólido a líquido','El hielo se funde y se convierte en agua líquida.',1),
(30,'multiple','Un material natural es…','["madera", "plástico fabricado", "vidrio industrial", "ladrillo"]','madera','Procede de la naturaleza con poca transformación.',1),
(30,'multiple','Un material artificial es…','["plástico", "lana", "piedra", "algodón"]','plástico','Se fabrica transformando materias primas.',1),
(30,'text','Escribe un estado de la materia.',NULL,'sólido|solido|líquido|liquido|gaseoso|gas','Son estados de la materia.',1),
(30,'multiple','Un material impermeable…','["no deja pasar fácilmente el agua", "se rompe siempre", "es siempre transparente", "atrae imanes siempre"]','no deja pasar fácilmente el agua','Impermeable significa que resiste el paso del agua.',2),
(30,'multiple','El reciclaje sirve para…','["aprovechar materiales y reducir residuos", "ensuciar más", "gastar más recursos", "tirar todo junto"]','aprovechar materiales y reducir residuos','Reciclar ayuda a cuidar el medio ambiente.',1),
(30,'multiple','El vidrio se tira normalmente en el contenedor…','["verde", "azul", "amarillo", "gris siempre"]','verde','El contenedor verde recoge vidrio.',1),
(30,'multiple','El papel y cartón se tiran en el contenedor…','["azul", "verde", "amarillo", "rojo"]','azul','El azul es para papel y cartón.',1),
(30,'multiple','Los envases ligeros suelen ir al contenedor…','["amarillo", "azul", "verde", "marrón siempre"]','amarillo','El amarillo recoge envases de plástico, latas y briks.',1),
(31,'multiple','La energía permite…','["producir cambios", "formar sílabas", "poner tildes", "hacer sustantivos"]','producir cambios','La energía causa movimiento, calor, luz u otros cambios.',1),
(31,'multiple','El Sol es una fuente de energía…','["natural y renovable", "artificial siempre", "no renovable", "muscular"]','natural y renovable','La energía solar viene del Sol y se renueva.',1),
(31,'multiple','Una fuerza puede…','["mover, detener o deformar un objeto", "hacer una tilde", "crear un verbo", "medir litros"]','mover, detener o deformar un objeto','Las fuerzas cambian el movimiento o forma.',1),
(31,'multiple','Empujar una puerta es aplicar una…','["fuerza", "capacidad", "sílaba", "fracción"]','fuerza','Empujar es ejercer fuerza.',1),
(31,'multiple','Una máquina simple es…','["palanca", "ordenador siempre", "televisión", "libro"]','palanca','La palanca es una máquina simple.',1),
(31,'multiple','La polea sirve para…','["levantar cargas con menos esfuerzo", "medir tiempo", "respirar", "clasificar palabras"]','levantar cargas con menos esfuerzo','La polea facilita levantar objetos.',2),
(31,'multiple','El plano inclinado ayuda a…','["subir objetos con menos esfuerzo", "escribir cuentos", "producir lluvia", "guardar datos"]','subir objetos con menos esfuerzo','Una rampa es un plano inclinado.',1),
(31,'multiple','La energía eléctrica se usa para…','["encender aparatos", "hacer crecer raíces directamente", "formar ríos", "crear montañas"]','encender aparatos','Muchos aparatos funcionan con electricidad.',1),
(31,'multiple','Una fuente no renovable es…','["petróleo", "sol", "viento", "agua en movimiento"]','petróleo','El petróleo tarda muchísimo en formarse y se agota.',2),
(31,'multiple','Una fuente renovable es…','["viento", "carbón", "gas natural", "petróleo"]','viento','La energía eólica aprovecha el viento.',1),
(31,'multiple','Ahorrar energía ayuda a…','["cuidar el planeta", "gastar más recursos", "contaminar más", "apagar siempre todo sin pensar"]','cuidar el planeta','Consumir menos reduce impacto ambiental.',1),
(31,'text','Escribe una fuente de energía renovable.',NULL,'sol|solar|viento|eólica|eolica|agua|hidráulica|hidraulica','Son fuentes que se renuevan.',1),
(31,'multiple','La gravedad es una fuerza que…','["atrae los objetos hacia la Tierra", "empuja al cielo", "produce luz", "mide capacidad"]','atrae los objetos hacia la Tierra','La gravedad atrae los cuerpos.',2),
(31,'multiple','El rozamiento suele…','["frenar el movimiento", "aumentar siempre la velocidad", "crear luz siempre", "no afectar nunca"]','frenar el movimiento','El rozamiento se opone al movimiento.',2),
(31,'multiple','Un circuito eléctrico necesita…','["fuente, cables y receptor", "agua y arena", "sustantivo y adjetivo", "norte y sur solamente"]','fuente, cables y receptor','Son partes básicas de un circuito sencillo.',2),
(32,'multiple','El planeta donde vivimos es…','["Tierra", "Marte", "Júpiter", "Venus"]','Tierra','Vivimos en el planeta Tierra.',1),
(32,'multiple','La atmósfera es…','["la capa de gases que rodea la Tierra", "una montaña", "un río", "un continente"]','la capa de gases que rodea la Tierra','Contiene el aire y protege el planeta.',1),
(32,'multiple','El ciclo del agua incluye…','["evaporación, condensación y precipitación", "suma, resta y división", "inicio, nudo y desenlace", "aguda, llana y esdrújula"]','evaporación, condensación y precipitación','Son fases principales del ciclo del agua.',1),
(32,'multiple','La evaporación ocurre cuando…','["el agua líquida pasa a vapor", "cae lluvia", "el hielo se forma", "la nube desaparece en roca"]','el agua líquida pasa a vapor','El calor favorece la evaporación.',1),
(32,'multiple','La condensación forma…','["nubes", "montañas", "semillas", "monedas"]','nubes','El vapor se enfría y forma gotitas.',1),
(32,'multiple','La precipitación puede ser…','["lluvia, nieve o granizo", "solo sol", "solo viento", "solo calor"]','lluvia, nieve o granizo','Es el agua que cae desde las nubes.',1),
(32,'multiple','El agua potable es…','["apta para beber", "siempre salada", "peligrosa siempre", "vapor solamente"]','apta para beber','Potable significa que se puede beber con seguridad.',1),
(32,'multiple','Para ahorrar agua podemos…','["cerrar el grifo al cepillarnos", "dejarlo abierto siempre", "tirar basura al río", "regar al mediodía siempre"]','cerrar el grifo al cepillarnos','Cerrar el grifo reduce consumo.',1),
(32,'multiple','El tiempo atmosférico describe…','["cómo está la atmósfera en un momento y lugar", "la historia antigua", "la masa de un objeto", "una familia de palabras"]','cómo está la atmósfera en un momento y lugar','Incluye temperatura, viento, lluvia...',1),
(32,'multiple','Un termómetro mide…','["temperatura", "viento", "lluvia", "presión solamente"]','temperatura','El termómetro indica temperatura.',1),
(32,'multiple','El viento es…','["aire en movimiento", "agua congelada", "tierra mojada", "luz sólida"]','aire en movimiento','Cuando el aire se mueve, sentimos viento.',1),
(32,'text','Escribe una forma de precipitación.',NULL,'lluvia|nieve|granizo','Son formas de precipitación.',1),
(32,'multiple','Los océanos contienen agua principalmente…','["salada", "dulce", "potable siempre", "sólida siempre"]','salada','El agua marina es salada.',1),
(32,'multiple','Los ríos contienen agua generalmente…','["dulce", "salada", "de plástico", "gaseosa siempre"]','dulce','Los ríos son aguas continentales dulces.',1),
(32,'multiple','Cuidar el agua es importante porque…','["es necesaria para la vida", "solo sirve para jugar", "no se agota nunca", "no la usan las plantas"]','es necesaria para la vida','Los seres vivos necesitan agua.',1),
(33,'multiple','Un mapa sirve para…','["representar un territorio", "medir masa", "conjugar verbos", "hacer digestión"]','representar un territorio','Los mapas muestran lugares de forma reducida.',1),
(33,'multiple','La leyenda de un mapa explica…','["símbolos y colores", "el precio del mapa", "las sílabas", "los verbos"]','símbolos y colores','Ayuda a interpretar el mapa.',1),
(33,'multiple','Los puntos cardinales son…','["norte, sur, este y oeste", "arriba, abajo y dentro", "suma, resta y producto", "ayer, hoy y mañana"]','norte, sur, este y oeste','Sirven para orientarse.',1),
(33,'multiple','Una montaña es una forma de…','["relieve", "clima", "sector económico", "texto narrativo"]','relieve','El relieve describe formas del terreno.',1),
(33,'multiple','Un valle suele estar…','["entre montañas", "encima de una nube", "dentro del mar siempre", "sobre un tejado"]','entre montañas','Muchos valles se sitúan entre zonas elevadas.',1),
(33,'multiple','Una llanura es…','["terreno amplio y plano", "montaña muy alta", "río corto", "playa con olas"]','terreno amplio y plano','Tiene poca pendiente.',1),
(33,'multiple','El curso de un río va desde…','["nacimiento hasta desembocadura", "playa hasta nube", "ciudad a montaña siempre", "mapa a leyenda"]','nacimiento hasta desembocadura','Los ríos nacen y desembocan en otro río, lago o mar.',1),
(33,'multiple','La costa es…','["zona donde la tierra toca el mar", "cima de montaña", "interior sin agua", "bosque alto"]','zona donde la tierra toca el mar','La costa limita con el mar.',1),
(33,'multiple','Un paisaje humanizado tiene…','["elementos construidos por personas", "solo naturaleza intacta", "solo animales salvajes", "ningún camino"]','elementos construidos por personas','Carreteras, casas o cultivos son humanos.',1),
(33,'multiple','Un paisaje natural tiene…','["pocos elementos humanos", "muchos edificios siempre", "solo fábricas", "solo mapas"]','pocos elementos humanos','Predominan elementos de la naturaleza.',1),
(33,'text','Escribe un elemento del relieve.',NULL,'montaña|montana|valle|llanura|meseta|sierra|depresión|depresion','Son formas del terreno.',1),
(33,'multiple','La escala de un mapa indica…','["la relación entre distancia real y distancia en el mapa", "la temperatura", "la edad de una ciudad", "el número de animales"]','la relación entre distancia real y distancia en el mapa','La escala permite calcular distancias reales.',2),
(33,'multiple','Un plano representa normalmente…','["un espacio pequeño con detalle", "todo el planeta siempre", "solo océanos", "solo montañas"]','un espacio pequeño con detalle','Planos de casa, colegio o barrio muestran detalle.',1),
(33,'multiple','Para orientarte en un mapa miras…','["la rosa de los vientos", "solo dibujos bonitos", "el peso del papel", "las faltas de ortografía"]','la rosa de los vientos','Indica dirección.',1),
(33,'multiple','Un río desemboca en…','["mar, lago u otro río", "una montaña siempre", "una carretera", "un árbol"]','mar, lago u otro río','La desembocadura es el final del río.',1),
(34,'multiple','¿Cuántas provincias tiene Andalucía?','["8", "6", "7", "9"]','8','Andalucía tiene ocho provincias.',1),
(34,'multiple','¿Cuál es una provincia andaluza?','["Cádiz", "Madrid", "Valencia", "Murcia"]','Cádiz','Cádiz pertenece a Andalucía.',1),
(34,'multiple','La capital de Andalucía es…','["Sevilla", "Cádiz", "Málaga", "Granada"]','Sevilla','Sevilla es la capital de la comunidad autónoma.',1),
(34,'multiple','El río más importante de Andalucía es…','["Guadalquivir", "Ebro", "Tajo", "Miño"]','Guadalquivir','El Guadalquivir atraviesa Andalucía.',1),
(34,'multiple','Sierra Nevada está en la provincia de…','["Granada", "Huelva", "Cádiz", "Sevilla"]','Granada','Sierra Nevada se encuentra principalmente en Granada.',1),
(34,'multiple','Andalucía tiene costa en…','["Atlántico y Mediterráneo", "solo Cantábrico", "solo océano Índico", "ningún mar"]','Atlántico y Mediterráneo','Andalucía tiene litoral atlántico y mediterráneo.',2),
(34,'text','Escribe una provincia de Andalucía.',NULL,'almería|almeria|cádiz|cadiz|córdoba|cordoba|granada|huelva|jaén|jaen|málaga|malaga|sevilla','Cualquiera de las ocho provincias es válida.',1),
(34,'multiple','Doñana es un espacio natural situado entre provincias como…','["Huelva, Sevilla y Cádiz", "Granada y Jaén solo", "Almería solo", "Málaga solo"]','Huelva, Sevilla y Cádiz','Doñana se extiende por esa zona.',2),
(34,'multiple','El valle del Guadalquivir es una zona de…','["llanuras y tierras fértiles", "alta montaña siempre", "desierto polar", "islas volcánicas"]','llanuras y tierras fértiles','Es una gran depresión recorrida por el río.',2),
(34,'multiple','Sierra Morena está al…','["norte de Andalucía", "sur en la costa", "centro del mar", "oeste de Portugal"]','norte de Andalucía','Sierra Morena limita al norte.',2),
(34,'multiple','El flamenco forma parte del patrimonio…','["cultural", "mineral", "atmosférico", "matemático"]','cultural','Es una manifestación cultural andaluza.',1),
(34,'multiple','Una ciudad andaluza con la Alhambra es…','["Granada", "Almería", "Córdoba", "Huelva"]','Granada','La Alhambra está en Granada.',1),
(34,'multiple','La Mezquita-Catedral está en…','["Córdoba", "Sevilla", "Jaén", "Málaga"]','Córdoba','Es un monumento emblemático de Córdoba.',1),
(34,'multiple','Andalucía está situada al…','["sur de España", "norte de Francia", "este de Italia", "centro de Europa"]','sur de España','Andalucía está al sur de la península ibérica.',1),
(34,'multiple','Cuidar el patrimonio andaluz significa…','["respetar monumentos, tradiciones y espacios", "pintar paredes históricas", "tirar basura", "romper restos antiguos"]','respetar monumentos, tradiciones y espacios','El patrimonio es de todos y debe conservarse.',1),
(35,'multiple','Un municipio está gobernado por…','["ayuntamiento", "hospital", "colegio", "estación"]','ayuntamiento','El ayuntamiento organiza servicios municipales.',1),
(35,'multiple','La población es…','["el conjunto de personas que vive en un lugar", "un tipo de montaña", "un aparato del cuerpo", "un alimento"]','el conjunto de personas que vive en un lugar','Población se refiere a habitantes.',1),
(35,'multiple','El sector primario obtiene…','["recursos de la naturaleza", "productos en fábricas", "servicios como educación", "solo información digital"]','recursos de la naturaleza','Agricultura, pesca y ganadería son sector primario.',1),
(35,'multiple','El sector secundario transforma…','["materias primas en productos", "personas en habitantes", "mapas en ríos", "verbos en sustantivos"]','materias primas en productos','La industria pertenece al sector secundario.',1),
(35,'multiple','El sector terciario ofrece…','["servicios", "materias primas", "minerales solamente", "cosechas"]','servicios','Sanidad, comercio, transporte y educación son servicios.',1),
(35,'multiple','Un agricultor trabaja en el sector…','["primario", "secundario", "terciario", "cuaternario escolar"]','primario','La agricultura obtiene productos del campo.',1),
(35,'multiple','Una fábrica pertenece al sector…','["secundario", "primario", "terciario", "doméstico"]','secundario','Transforma materias primas en productos.',1),
(35,'multiple','Una profesora trabaja en el sector…','["terciario", "primario", "secundario", "minero"]','terciario','La educación es un servicio.',1),
(35,'text','Escribe un servicio público.',NULL,'colegio|hospital|biblioteca|policía|policia|transporte|bomberos|centro de salud','Son servicios para la población.',1),
(35,'multiple','Una localidad puede ser…','["pueblo o ciudad", "solo montaña", "solo río", "solo bosque"]','pueblo o ciudad','Las localidades son núcleos de población.',1),
(35,'multiple','La población urbana vive principalmente en…','["ciudades", "granjas aisladas", "bosques sin casas", "barcos siempre"]','ciudades','Urbano se relaciona con ciudad.',1),
(35,'multiple','La población rural vive en…','["pueblos o zonas de campo", "grandes capitales siempre", "aeropuertos", "centros comerciales solamente"]','pueblos o zonas de campo','Rural se relaciona con campo y pueblos.',1),
(35,'multiple','El comercio consiste en…','["comprar y vender productos", "cultivar trigo solo", "fabricar acero solo", "respirar"]','comprar y vender productos','El comercio es un servicio del sector terciario.',1),
(35,'multiple','El turismo pertenece al sector…','["terciario", "primario", "secundario", "mineral"]','terciario','El turismo ofrece servicios.',1),
(35,'multiple','Las normas de convivencia sirven para…','["vivir mejor en comunidad", "hacer multiplicaciones", "crear ríos", "escribir tildes"]','vivir mejor en comunidad','Ayudan al respeto y organización social.',1),
(36,'multiple','La historia estudia…','["el pasado de las personas y sociedades", "solo el tiempo de mañana", "las tablas de multiplicar", "la digestión"]','el pasado de las personas y sociedades','La historia investiga hechos del pasado.',1),
(36,'multiple','Una fuente histórica puede ser…','["un documento antiguo", "una operación", "un litro", "una nube"]','un documento antiguo','Los documentos ayudan a conocer el pasado.',1),
(36,'multiple','Los restos arqueológicos son…','["fuentes materiales", "verbos", "gráficas de barras", "seres vivos siempre"]','fuentes materiales','Objetos o construcciones antiguas aportan información.',2),
(36,'multiple','Una línea del tiempo sirve para…','["ordenar hechos cronológicamente", "medir masa", "hacer una división", "localizar sílabas"]','ordenar hechos cronológicamente','Muestra el orden de acontecimientos.',1),
(36,'multiple','Antes de Cristo se abrevia…','["a. C.", "d. C.", "kg", "km"]','a. C.','a. C. significa antes de Cristo.',2),
(36,'multiple','Después de Cristo se abrevia…','["d. C.", "a. C.", "cm", "L"]','d. C.','d. C. significa después de Cristo.',2),
(36,'multiple','El patrimonio cultural incluye…','["monumentos, tradiciones y obras", "solo animales", "solo nubes", "solo números"]','monumentos, tradiciones y obras','Es herencia cultural de una comunidad.',1),
(36,'multiple','Cuidar el patrimonio significa…','["respetarlo y conservarlo", "romperlo", "ensuciarlo", "olvidarlo"]','respetarlo y conservarlo','El patrimonio debe protegerse.',1),
(36,'multiple','La Prehistoria es…','["el periodo anterior a la escritura", "la época actual", "el futuro", "un mapa"]','el periodo anterior a la escritura','Termina con la aparición de la escritura.',2),
(36,'multiple','La Edad Antigua empieza con…','["la escritura", "internet", "la electricidad", "el automóvil"]','la escritura','La escritura marca el inicio de la Edad Antigua.',2),
(36,'text','Escribe un ejemplo de fuente histórica.',NULL,'carta|fotografía|fotografia|moneda|documento|monumento|herramienta|resto arqueológico|resto arqueologico','Son fuentes que ayudan a conocer el pasado.',1),
(36,'multiple','Un museo sirve para…','["conservar y mostrar patrimonio", "vender solo comida", "fabricar plástico", "medir ángulos"]','conservar y mostrar patrimonio','Los museos conservan objetos valiosos.',1),
(36,'multiple','La Alhambra es patrimonio…','["histórico y cultural", "solo natural sin construcción", "matemático", "atmosférico"]','histórico y cultural','Es un monumento histórico de Andalucía.',1),
(36,'multiple','Una tradición es…','["una costumbre transmitida en el tiempo", "una división exacta", "un material artificial", "un ser vivo"]','una costumbre transmitida en el tiempo','Las tradiciones pasan de generación en generación.',1),
(36,'multiple','Ordenar hechos del más antiguo al más reciente es ordenar…','["cronológicamente", "alfabéticamente siempre", "por peso", "por color"]','cronológicamente','Cronológico se refiere al tiempo.',1),
(37,'multiple','Choose the correct greeting in the morning.','["Good morning", "Good night", "Goodbye", "See you"]','Good morning','Good morning se usa por la mañana.',1),
(37,'multiple','How do you say “Me llamo Leire” in English?','["My name is Leire", "I am nine years old", "Goodbye Leire", "Thank you Leire"]','My name is Leire','My name is... sirve para decir el nombre.',1),
(37,'multiple','Answer: How are you?','["I am fine, thank you", "I am Spain", "I have pencil", "I like blue"]','I am fine, thank you','Es una respuesta habitual a How are you?',1),
(37,'multiple','What does “thank you” mean?','["gracias", "hola", "adiós", "por favor"]','gracias','Thank you significa gracias.',1),
(37,'multiple','What does “please” mean?','["por favor", "gracias", "buenas noches", "mesa"]','por favor','Please significa por favor.',1),
(37,'text','Translate into Spanish: hello',NULL,'hola','“hello” significa “hola”.',1),
(37,'text','Translate into Spanish: goodbye',NULL,'adiós|adios','“goodbye” significa “adiós”.',1),
(37,'text','Translate into Spanish: good night',NULL,'buenas noches','“good night” significa “buenas noches”.',1),
(37,'text','Translate into Spanish: I am nine',NULL,'tengo nueve años|tengo 9 años','“I am nine” significa “tengo nueve años”.',1),
(37,'text','Translate into Spanish: see you later',NULL,'hasta luego','“see you later” significa “hasta luego”.',1),
(37,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(37,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(37,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(37,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(37,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(38,'multiple','What number is “twenty”?','["20", "12", "30", "2"]','20','Twenty es veinte.',1),
(38,'multiple','What day comes after Monday?','["Tuesday", "Sunday", "Friday", "January"]','Tuesday','After Monday comes Tuesday.',1),
(38,'multiple','What month comes after March?','["April", "May", "February", "Monday"]','April','After March comes April.',1),
(38,'multiple','Choose the correct translation: “lunes”.','["Monday", "Tuesday", "Sunday", "Friday"]','Monday','Monday significa lunes.',1),
(38,'multiple','What time is “three o’clock”?','["3:00", "2:30", "4:00", "12:00"]','3:00','Three o’clock es las tres en punto.',1),
(38,'text','Translate into Spanish: one',NULL,'uno','“one” significa “uno”.',1),
(38,'text','Translate into Spanish: ten',NULL,'diez','“ten” significa “diez”.',1),
(38,'text','Translate into Spanish: fifteen',NULL,'quince','“fifteen” significa “quince”.',1),
(38,'text','Translate into Spanish: thirty',NULL,'treinta','“thirty” significa “treinta”.',1),
(38,'text','Translate into Spanish: Sunday',NULL,'domingo','“Sunday” significa “domingo”.',1),
(38,'text','Translate into Spanish: January',NULL,'enero','“January” significa “enero”.',1),
(38,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(38,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(38,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(38,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(38,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(39,'multiple','What is “pencil” in Spanish?','["lápiz", "libro", "mesa", "puerta"]','lápiz','Pencil significa lápiz.',1),
(39,'multiple','Choose the classroom object.','["ruler", "apple", "dog", "sunny"]','ruler','Ruler es regla, un objeto de clase.',1),
(39,'multiple','What does “teacher” mean?','["profesor o profesora", "alumno", "libro", "patio"]','profesor o profesora','Teacher es la persona que enseña.',1),
(39,'multiple','Complete: Open your ___.','["book", "sandwich", "shoes", "rain"]','book','Open your book significa abre tu libro.',1),
(39,'multiple','What subject is “Maths”?','["Matemáticas", "Lengua", "Música", "Educación Física"]','Matemáticas','Maths significa Matemáticas.',1),
(39,'text','Translate into Spanish: book',NULL,'libro','“book” significa “libro”.',1),
(39,'text','Translate into Spanish: rubber',NULL,'goma','“rubber” significa “goma”.',1),
(39,'text','Translate into Spanish: school',NULL,'colegio','“school” significa “colegio”.',1),
(39,'text','Translate into Spanish: classroom',NULL,'clase|aula','“classroom” significa “clase”.',1),
(39,'text','Translate into Spanish: pen',NULL,'bolígrafo|boligrafo','“pen” significa “bolígrafo”.',1),
(39,'text','Translate into Spanish: bag',NULL,'mochila|bolsa','“bag” significa “mochila”.',1),
(39,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(39,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(39,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(39,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(39,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(40,'multiple','What does “mother” mean?','["madre", "padre", "hermano", "abuela"]','madre','Mother significa madre.',1),
(40,'multiple','What does “brother” mean?','["hermano", "hermana", "primo", "tío"]','hermano','Brother significa hermano.',1),
(40,'multiple','Choose the adjective: tall','["alto", "bajo", "pequeño", "viejo"]','alto','Tall significa alto.',1),
(40,'multiple','Complete: She ___ got brown hair.','["has", "have", "is", "are"]','has','Con she usamos has got.',2),
(40,'multiple','Complete: I ___ got two sisters.','["have", "has", "am", "is"]','have','Con I usamos have got.',2),
(40,'text','Translate into Spanish: father',NULL,'padre','“father” significa “padre”.',1),
(40,'text','Translate into Spanish: sister',NULL,'hermana','“sister” significa “hermana”.',1),
(40,'text','Translate into Spanish: grandmother',NULL,'abuela','“grandmother” significa “abuela”.',1),
(40,'text','Translate into Spanish: short',NULL,'bajo|corto','“short” significa “bajo”.',1),
(40,'text','Translate into Spanish: young',NULL,'joven','“young” significa “joven”.',1),
(40,'text','Translate into Spanish: old',NULL,'viejo|mayor','“old” significa “viejo”.',1),
(40,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(40,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(40,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(40,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(40,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(41,'multiple','What does “get up” mean?','["levantarse", "cenar", "dormir", "leer"]','levantarse','Get up significa levantarse.',1),
(41,'multiple','What does “have breakfast” mean?','["desayunar", "comer", "cenar", "jugar"]','desayunar','Have breakfast significa desayunar.',1),
(41,'multiple','Complete: I ___ to school.','["go", "goes", "going", "is"]','go','Con I usamos go.',2),
(41,'multiple','Complete: She ___ homework.','["does", "do", "doing", "are"]','does','Con she en presente simple usamos does.',2),
(41,'multiple','What routine is at night?','["go to bed", "get up", "have breakfast", "go to school"]','go to bed','Go to bed suele ser por la noche.',1),
(41,'text','Translate into Spanish: wake up',NULL,'despertarse','“wake up” significa “despertarse”.',1),
(41,'text','Translate into Spanish: go to school',NULL,'ir al colegio','“go to school” significa “ir al colegio”.',1),
(41,'text','Translate into Spanish: do homework',NULL,'hacer los deberes','“do homework” significa “hacer los deberes”.',1),
(41,'text','Translate into Spanish: have dinner',NULL,'cenar','“have dinner” significa “cenar”.',1),
(41,'text','Translate into Spanish: brush my teeth',NULL,'lavarme los dientes','“brush my teeth” significa “lavarme los dientes”.',1),
(41,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(41,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(41,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(41,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(41,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(42,'multiple','What does “apple” mean?','["manzana", "pera", "pan", "agua"]','manzana','Apple significa manzana.',1),
(42,'multiple','Choose a healthy food.','["fruit", "sweets", "chips", "cola"]','fruit','Fruit es fruta, una opción saludable.',1),
(42,'multiple','Complete: I like ___.','["water", "are", "goes", "pencil"]','water','Water es un alimento/bebida y encaja tras I like.',1),
(42,'multiple','What does “I don’t like fish” mean?','["No me gusta el pescado", "Me gusta el pescado", "Tengo pescado", "Soy pescado"]','No me gusta el pescado','Don’t like expresa que no gusta.',2),
(42,'multiple','What meal is in the morning?','["breakfast", "dinner", "lunch at night", "bed"]','breakfast','Breakfast es desayuno.',1),
(42,'text','Translate into Spanish: bread',NULL,'pan','“bread” significa “pan”.',1),
(42,'text','Translate into Spanish: milk',NULL,'leche','“milk” significa “leche”.',1),
(42,'text','Translate into Spanish: cheese',NULL,'queso','“cheese” significa “queso”.',1),
(42,'text','Translate into Spanish: chicken',NULL,'pollo','“chicken” significa “pollo”.',1),
(42,'text','Translate into Spanish: vegetables',NULL,'verduras','“vegetables” significa “verduras”.',1),
(42,'text','Translate into Spanish: water',NULL,'agua','“water” significa “agua”.',1),
(42,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(42,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(42,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(42,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(42,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(43,'multiple','What does “T-shirt” mean?','["camiseta", "zapato", "abrigo", "sombrero"]','camiseta','T-shirt significa camiseta.',1),
(43,'multiple','Choose the weather word.','["rainy", "trousers", "dress", "socks"]','rainy','Rainy describe tiempo lluvioso.',1),
(43,'multiple','What do you wear when it is cold?','["coat", "shorts", "sandals", "swimsuit"]','coat','Coat es abrigo.',1),
(43,'multiple','What does “sunny” mean?','["soleado", "lluvioso", "nublado", "nevado"]','soleado','Sunny significa soleado.',1),
(43,'multiple','Complete: It ___ raining.','["is", "are", "am", "be"]','is','It is raining significa está lloviendo.',2),
(43,'text','Translate into Spanish: shoes',NULL,'zapatos','“shoes” significa “zapatos”.',1),
(43,'text','Translate into Spanish: hat',NULL,'sombrero|gorro','“hat” significa “sombrero”.',1),
(43,'text','Translate into Spanish: dress',NULL,'vestido','“dress” significa “vestido”.',1),
(43,'text','Translate into Spanish: jumper',NULL,'jersey','“jumper” significa “jersey”.',1),
(43,'text','Translate into Spanish: cloudy',NULL,'nublado','“cloudy” significa “nublado”.',1),
(43,'text','Translate into Spanish: windy',NULL,'ventoso','“windy” significa “ventoso”.',1),
(43,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(43,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(43,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(43,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(43,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(44,'multiple','What does “kitchen” mean?','["cocina", "baño", "dormitorio", "jardín"]','cocina','Kitchen significa cocina.',1),
(44,'multiple','The book is on the table. “On” means…','["encima de", "debajo de", "detrás de", "al lado de"]','encima de','On significa encima de cuando hay contacto.',1),
(44,'multiple','The cat is under the chair. “Under” means…','["debajo de", "encima de", "entre", "delante de"]','debajo de','Under significa debajo de.',1),
(44,'multiple','Choose the room where you sleep.','["bedroom", "kitchen", "bathroom", "garage"]','bedroom','Bedroom es dormitorio.',1),
(44,'multiple','Complete: The sofa is ___ the living room.','["in", "under", "old", "has"]','in','In significa dentro/en.',1),
(44,'text','Translate into Spanish: bathroom',NULL,'baño|bano','“bathroom” significa “baño”.',1),
(44,'text','Translate into Spanish: bed',NULL,'cama','“bed” significa “cama”.',1),
(44,'text','Translate into Spanish: chair',NULL,'silla','“chair” significa “silla”.',1),
(44,'text','Translate into Spanish: table',NULL,'mesa','“table” significa “mesa”.',1),
(44,'text','Translate into Spanish: next to',NULL,'al lado de','“next to” significa “al lado de”.',1),
(44,'text','Translate into Spanish: behind',NULL,'detrás de|detras de','“behind” significa “detrás de”.',1),
(44,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(44,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(44,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(44,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(44,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(45,'multiple','What does “dog” mean?','["perro", "gato", "pájaro", "pez"]','perro','Dog significa perro.',1),
(45,'multiple','Choose an animal that can fly.','["bird", "fish", "horse", "snake"]','bird','Bird significa pájaro y puede volar.',1),
(45,'multiple','Complete: A fish can ___.','["swim", "fly in the sky", "read", "cook"]','swim','Fish can swim significa los peces pueden nadar.',1),
(45,'multiple','What does “forest” mean?','["bosque", "playa", "ciudad", "colegio"]','bosque','Forest significa bosque.',1),
(45,'multiple','Complete: It has got four ___.','["legs", "wings only", "tables", "books"]','legs','Muchos animales tienen cuatro patas.',2),
(45,'text','Translate into Spanish: cat',NULL,'gato','“cat” significa “gato”.',1),
(45,'text','Translate into Spanish: horse',NULL,'caballo','“horse” significa “caballo”.',1),
(45,'text','Translate into Spanish: rabbit',NULL,'conejo','“rabbit” significa “conejo”.',1),
(45,'text','Translate into Spanish: bird',NULL,'pájaro|pajaro','“bird” significa “pájaro”.',1),
(45,'text','Translate into Spanish: tree',NULL,'árbol|arbol','“tree” significa “árbol”.',1),
(45,'text','Translate into Spanish: river',NULL,'río|rio','“river” significa “río”.',1),
(45,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(45,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(45,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(45,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(45,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(46,'multiple','What does “park” mean?','["parque", "hospital", "tienda", "calle"]','parque','Park significa parque.',1),
(46,'multiple','Where can you read and borrow books?','["library", "hospital", "supermarket", "cinema"]','library','Library es biblioteca.',1),
(46,'multiple','What does “turn left” mean?','["gira a la izquierda", "gira a la derecha", "sigue recto", "para"]','gira a la izquierda','Turn left significa gira a la izquierda.',1),
(46,'multiple','What does “go straight on” mean?','["sigue recto", "gira", "compra pan", "duerme"]','sigue recto','Go straight on significa sigue recto.',1),
(46,'multiple','Where do doctors work?','["hospital", "park", "library", "school only"]','hospital','Doctors work in a hospital.',1),
(46,'text','Translate into Spanish: shop',NULL,'tienda','“shop” significa “tienda”.',1),
(46,'text','Translate into Spanish: street',NULL,'calle','“street” significa “calle”.',1),
(46,'text','Translate into Spanish: school',NULL,'colegio','“school” significa “colegio”.',1),
(46,'text','Translate into Spanish: supermarket',NULL,'supermercado','“supermarket” significa “supermercado”.',1),
(46,'text','Translate into Spanish: right',NULL,'derecha','“right” significa “derecha”.',1),
(46,'text','Translate into Spanish: left',NULL,'izquierda','“left” significa “izquierda”.',1),
(46,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(46,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(46,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(46,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(46,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(47,'multiple','What does “I like dancing” mean?','["Me gusta bailar", "No me gusta bailar", "Puedo bailar", "Tengo baile"]','Me gusta bailar','I like expresa gusto.',1),
(47,'multiple','What does “I don’t like football” mean?','["No me gusta el fútbol", "Me gusta el fútbol", "Juego al fútbol siempre", "Soy fútbol"]','No me gusta el fútbol','Don’t like expresa que algo no gusta.',2),
(47,'multiple','Complete: I can ___.','["swim", "swims", "swimming", "to swim"]','swim','Después de can usamos verbo base.',2),
(47,'multiple','Complete: She can’t ___.','["fly", "flies", "flying", "to fly"]','fly','Después de can’t usamos verbo base.',2),
(47,'multiple','Choose a hobby.','["drawing", "hospital", "rainy", "under"]','drawing','Drawing es dibujar, una afición.',1),
(47,'text','Translate into Spanish: dance',NULL,'bailar','“dance” significa “bailar”.',1),
(47,'text','Translate into Spanish: sing',NULL,'cantar','“sing” significa “cantar”.',1),
(47,'text','Translate into Spanish: play football',NULL,'jugar al fútbol|jugar al futbol','“play football” significa “jugar al fútbol”.',1),
(47,'text','Translate into Spanish: read comics',NULL,'leer cómics|leer comics','“read comics” significa “leer cómics”.',1),
(47,'text','Translate into Spanish: ride a bike',NULL,'montar en bici','“ride a bike” significa “montar en bici”.',1),
(47,'text','Translate into Spanish: swim',NULL,'nadar','“swim” significa “nadar”.',1),
(47,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(47,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(47,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(47,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(47,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(48,'multiple','What does “first” mean in a story?','["primero", "después", "finalmente", "ayer"]','primero','First ordena el inicio.',1),
(48,'multiple','What does “then” mean?','["luego", "ayer", "siempre", "nunca"]','luego','Then conecta acciones posteriores.',1),
(48,'multiple','What does “finally” mean?','["finalmente", "primero", "nunca", "hoy"]','finalmente','Finally introduce el final.',1),
(48,'multiple','Complete: Yesterday, I ___ happy.','["was", "were", "am", "is"]','was','Con I en pasado usamos was.',2),
(48,'multiple','Complete: They ___ at school yesterday.','["were", "was", "are", "is"]','were','Con they en pasado usamos were.',2),
(48,'text','Translate into Spanish: yesterday',NULL,'ayer','“yesterday” significa “ayer”.',1),
(48,'text','Translate into Spanish: story',NULL,'historia|cuento','“story” significa “historia”.',1),
(48,'text','Translate into Spanish: went',NULL,'fue|fui|fueron','“went” significa “fue”.',1),
(48,'text','Translate into Spanish: played',NULL,'jugó|jugo|jugué|jugue|jugaron','“played” significa “jugó”.',1),
(48,'text','Translate into Spanish: happy',NULL,'feliz|contento','“happy” significa “feliz”.',1),
(48,'text','Write a short sentence using one word from this topic.',NULL,'respuesta libre','La frase debe usar vocabulario del tema.',1),
(48,'text','Escribe una palabra en inglés que hayas aprendido en este tema.',NULL,'respuesta libre','Debe ser una palabra relacionada con el tema.',1),
(48,'multiple','What language are we practising in this subject?','["English", "French", "Italian", "German"]','English','La asignatura es Inglés.',1),
(48,'text','Translate into English: hola',NULL,'hello','Hola en inglés es hello.',1),
(48,'multiple','Choose the correct option to be polite.','["please", "banana", "chair", "under"]','please','Please se usa para pedir algo con educación.',1),
(25,'text','Escribe una idea importante sobre “Seres vivos y funciones vitales”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(25,'text','Escribe una idea importante sobre “Seres vivos y funciones vitales”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(25,'text','Escribe una idea importante sobre “Seres vivos y funciones vitales”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(26,'text','Escribe una idea importante sobre “Animales vertebrados e invertebrados”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(26,'text','Escribe una idea importante sobre “Animales vertebrados e invertebrados”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(26,'text','Escribe una idea importante sobre “Animales vertebrados e invertebrados”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(27,'text','Escribe una idea importante sobre “Plantas”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(27,'text','Escribe una idea importante sobre “Plantas”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(27,'text','Escribe una idea importante sobre “Plantas”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(28,'text','Escribe una idea importante sobre “Ecosistemas y cadenas alimentarias”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(28,'text','Escribe una idea importante sobre “Ecosistemas y cadenas alimentarias”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(28,'text','Escribe una idea importante sobre “Ecosistemas y cadenas alimentarias”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(29,'text','Escribe una idea importante sobre “El cuerpo humano y la salud”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(29,'text','Escribe una idea importante sobre “El cuerpo humano y la salud”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(29,'text','Escribe una idea importante sobre “El cuerpo humano y la salud”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(30,'text','Escribe una idea importante sobre “Materia y materiales”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(30,'text','Escribe una idea importante sobre “Materia y materiales”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(30,'text','Escribe una idea importante sobre “Materia y materiales”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(31,'text','Escribe una idea importante sobre “Energía, fuerzas y máquinas”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(31,'text','Escribe una idea importante sobre “Energía, fuerzas y máquinas”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(31,'text','Escribe una idea importante sobre “Energía, fuerzas y máquinas”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(32,'text','Escribe una idea importante sobre “Tierra, agua y atmósfera”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(32,'text','Escribe una idea importante sobre “Tierra, agua y atmósfera”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(32,'text','Escribe una idea importante sobre “Tierra, agua y atmósfera”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(33,'text','Escribe una idea importante sobre “Mapas, relieve y paisajes”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(33,'text','Escribe una idea importante sobre “Mapas, relieve y paisajes”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(33,'text','Escribe una idea importante sobre “Mapas, relieve y paisajes”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(34,'text','Escribe una idea importante sobre “Andalucía”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(34,'text','Escribe una idea importante sobre “Andalucía”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(34,'text','Escribe una idea importante sobre “Andalucía”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(35,'text','Escribe una idea importante sobre “Población, municipios y sectores económicos”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(35,'text','Escribe una idea importante sobre “Población, municipios y sectores económicos”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(35,'text','Escribe una idea importante sobre “Población, municipios y sectores económicos”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(36,'text','Escribe una idea importante sobre “Historia y patrimonio”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(36,'text','Escribe una idea importante sobre “Historia y patrimonio”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(36,'text','Escribe una idea importante sobre “Historia y patrimonio”.',NULL,'respuesta libre','Debe ser una idea verdadera del tema.',1),
(37,'text','Write or translate one word related to “Greetings and introductions”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(37,'text','Write or translate one word related to “Greetings and introductions”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(37,'text','Write or translate one word related to “Greetings and introductions”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(38,'text','Write or translate one word related to “Numbers, dates and time”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(38,'text','Write or translate one word related to “Numbers, dates and time”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(39,'text','Write or translate one word related to “School and classroom objects”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(39,'text','Write or translate one word related to “School and classroom objects”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(40,'text','Write or translate one word related to “Family and descriptions”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(40,'text','Write or translate one word related to “Family and descriptions”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(41,'text','Write or translate one word related to “Daily routines”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(41,'text','Write or translate one word related to “Daily routines”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(41,'text','Write or translate one word related to “Daily routines”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(42,'text','Write or translate one word related to “Food and healthy habits”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(42,'text','Write or translate one word related to “Food and healthy habits”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(43,'text','Write or translate one word related to “Clothes and weather”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(43,'text','Write or translate one word related to “Clothes and weather”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(44,'text','Write or translate one word related to “House and prepositions”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(44,'text','Write or translate one word related to “House and prepositions”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(45,'text','Write or translate one word related to “Animals and nature”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(45,'text','Write or translate one word related to “Animals and nature”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(46,'text','Write or translate one word related to “Places in town and directions”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(46,'text','Write or translate one word related to “Places in town and directions”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(47,'text','Write or translate one word related to “Likes, hobbies and abilities”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(47,'text','Write or translate one word related to “Likes, hobbies and abilities”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(48,'text','Write or translate one word related to “Simple stories and past basics”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(48,'text','Write or translate one word related to “Simple stories and past basics”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1),
(48,'text','Write or translate one word related to “Simple stories and past basics”.',NULL,'respuesta libre','Debe ser vocabulario relacionado con el tema.',1);

-- Refresca mundos de aventura para asegurar que apuntan a asignaturas con contenido real
DELETE FROM adventure_worlds;
INSERT INTO adventure_worlds(subject, world_name, icon, description, sort_order) VALUES
('Matemáticas','Bosque Matemático','➗','Números, medidas, geometría y problemas por temas.',1),
('Lengua','Castillo de la Lengua','📖','Lectura, gramática, ortografía y escritura.',2),
('Conocimiento del Medio','Laboratorio del Medio','🌍','Naturaleza, cuerpo humano, Andalucía, sociedad e historia.',3),
('Inglés','Isla del Inglés','🇬🇧','Vocabulario, frases y comunicación sencilla.',4);

-- Resumen: 48 temas y 916 preguntas curriculares.


-- Academia Leire V4.7 - Tema 8 Conocimiento del Medio: Cómo han cambiado nuestros hábitos
START TRANSACTION;
DELETE FROM topics WHERE subject='Conocimiento del Medio' AND title='Tema 8 · Cómo han cambiado nuestros hábitos';
INSERT INTO topics(subject, course, title, description, content) VALUES (
  'Conocimiento del Medio',
  '4º Primaria Andalucía',
  'Tema 8 · Cómo han cambiado nuestros hábitos',
  'Prehistoria: tiempo e historia, Paleolítico, Neolítico, Edad de los Metales, Península Ibérica y Andalucía.',
  '# Cómo han cambiado nuestros hábitos
Este tema explica cómo vivían las personas antes de que existiera la escritura y cómo fueron cambiando sus formas de vida. Es una explicación original para estudiar el Tema 8 de Conocimiento del Medio de 4º de Primaria: el tiempo y la historia, el Paleolítico, el Neolítico, la Edad de los Metales, la Península Ibérica en la Prehistoria y Andalucía en la Prehistoria.

# 1. El tiempo y la historia
La historia estudia el pasado de las personas y de las sociedades. Para ordenarlo usamos medidas de tiempo: años, décadas, siglos y milenios.
- Una década son 10 años.
- Un siglo son 100 años.
- Un milenio son 1.000 años.
Para saber cómo vivían las personas del pasado, los historiadores estudian fuentes históricas: restos materiales, pinturas, herramientas, edificios, documentos escritos, fotografías y testimonios.
La Prehistoria es la etapa anterior a la invención de la escritura. Como no había textos escritos, conocemos esta etapa gracias a objetos, huesos, herramientas, pinturas rupestres y restos de viviendas.

# 2. La Prehistoria
La Prehistoria es un periodo muy largo. Empieza con los primeros seres humanos y termina cuando aparece la escritura. Se divide en tres grandes etapas:
- Paleolítico: los seres humanos vivían de la caza, la pesca y la recolección.
- Neolítico: aparecen la agricultura y la ganadería, y muchos grupos se hacen sedentarios.
- Edad de los Metales: las personas aprenden a fabricar objetos con metales y las aldeas crecen.

# 3. El Paleolítico
Durante el Paleolítico, las personas eran nómadas: no vivían siempre en el mismo lugar, sino que se desplazaban buscando comida. Se alimentaban de animales que cazaban, peces que pescaban y frutos, raíces o semillas que recogían.
Vivían en cuevas, refugios naturales o cabañas sencillas. Fabricaban herramientas con piedra tallada, madera, hueso y asta. El fuego fue muy importante porque servía para calentarse, cocinar, iluminar y protegerse de algunos animales.
También realizaron pinturas rupestres en las paredes de algunas cuevas. Muchas representaban animales, escenas de caza o signos.

# 4. El Neolítico
En el Neolítico cambiaron muchos hábitos. Las personas aprendieron a cultivar plantas y a domesticar animales. Así aparecieron la agricultura y la ganadería.
Gracias a estos cambios, muchas comunidades se hicieron sedentarias: empezaron a vivir en poblados estables. Construyeron viviendas más permanentes, cuidaron cultivos, guardaron alimentos y fabricaron cerámica para cocinar o almacenar.
Las herramientas también mejoraron: usaban piedra pulida, hoces, molinos de mano y objetos para trabajar la tierra. La vida diaria empezó a organizarse con trabajos diferentes.

# 5. La Edad de los Metales
En la Edad de los Metales, las personas aprendieron a usar metales para fabricar herramientas, armas, adornos y otros objetos. Primero utilizaron cobre, después bronce y más tarde hierro.
Los poblados crecieron y algunos se protegieron con murallas. Aumentó el comercio, porque unas comunidades intercambiaban metales, alimentos, cerámica o adornos con otras. También aparecieron más diferencias entre grupos, porque algunas personas tenían más riqueza o poder.
En esta etapa se construyeron monumentos megalíticos, como dólmenes y menhires. Algunos se relacionaban con enterramientos o ceremonias.

# 6. La Península Ibérica en la Prehistoria
En la Península Ibérica hubo presencia humana desde tiempos muy antiguos. Se han encontrado herramientas, huesos, pinturas rupestres y restos de poblados que nos ayudan a conocer cómo vivían.
En el Paleolítico destacaron cuevas con arte rupestre. En el Neolítico se extendieron la agricultura, la ganadería y los poblados. En la Edad de los Metales crecieron las aldeas, el comercio y los monumentos megalíticos.
La Península Ibérica era un territorio con distintos paisajes: costas, montañas, ríos y llanuras. Por eso los grupos humanos se adaptaron de formas diferentes según el lugar donde vivían.

# 7. Andalucía en la Prehistoria
Andalucía conserva muchos restos prehistóricos. En cuevas y yacimientos se han encontrado pinturas, herramientas, huesos y restos de poblados.
Algunos ejemplos importantes son las cuevas con arte rupestre, los dólmenes de Antequera y poblados de la Edad de los Metales como Los Millares, en Almería. Estos lugares muestran que en nuestra comunidad hubo cazadores, recolectores, agricultores, ganaderos, artesanos y comunidades que comerciaban.
Estudiar estos restos nos ayuda a comprender cómo fueron cambiando los hábitos de vida en Andalucía: de grupos nómadas a poblados estables, de herramientas de piedra a objetos de metal, y de vivir solo de la naturaleza a producir alimentos.

# 8. Resumen para aprenderlo bien
Al principio, en el Paleolítico, las personas eran nómadas y obtenían alimentos cazando, pescando y recolectando. Después, en el Neolítico, aprendieron a cultivar y criar animales, por eso pudieron vivir en poblados. Más tarde, en la Edad de los Metales, usaron metales, aumentó el comercio y crecieron las aldeas.
La idea más importante del tema es entender cómo cambiaron los hábitos: alimentación, vivienda, herramientas, trabajo, organización y forma de vivir.

# 9. Vocabulario clave
- Nómada: persona o grupo que se desplaza de un lugar a otro y no vive siempre en el mismo sitio.
- Sedentario: persona o grupo que vive de forma estable en un lugar.
- Agricultura: cultivo de la tierra para obtener alimentos.
- Ganadería: cría de animales domésticos.
- Piedra tallada: piedra golpeada para darle forma y usarla como herramienta.
- Piedra pulida: piedra trabajada para que quede más lisa y eficaz.
- Pintura rupestre: pintura realizada sobre rocas o paredes de cuevas.
- Megalito: monumento construido con grandes piedras.
- Dolmen: monumento megalítico formado por grandes piedras, muchas veces relacionado con enterramientos.

# 10. Preguntas que debes saber responder
- ¿Qué diferencia hay entre historia y prehistoria?
- ¿Qué fuentes usamos para conocer la Prehistoria?
- ¿Cómo vivían las personas del Paleolítico?
- ¿Qué cambios aparecieron en el Neolítico?
- ¿Por qué fue importante la Edad de los Metales?
- ¿Qué restos prehistóricos se han encontrado en Andalucía?
- ¿Cómo cambiaron los hábitos de vida desde el Paleolítico hasta la Edad de los Metales?'
);
SET @topic_habitos := LAST_INSERT_ID();
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES
(@topic_habitos,'multiple','¿Qué estudia la historia?','["El pasado de las personas y sociedades", "Solo los animales", "Solo los planetas", "Las tablas de multiplicar"]','El pasado de las personas y sociedades','La historia estudia cómo vivían las personas y las sociedades en el pasado.',1),
(@topic_habitos,'multiple','¿Cuántos años tiene un siglo?','["10", "50", "100", "1.000"]','100','Un siglo son 100 años.',1),
(@topic_habitos,'multiple','¿Cuántos años tiene una década?','["5", "10", "100", "1.000"]','10','Una década son 10 años.',1),
(@topic_habitos,'multiple','¿Cuántos años tiene un milenio?','["10", "100", "500", "1.000"]','1.000','Un milenio son 1.000 años.',1),
(@topic_habitos,'multiple','¿Qué etapa es anterior a la escritura?','["Edad Media", "Prehistoria", "Edad Moderna", "Edad Contemporánea"]','Prehistoria','La Prehistoria es la etapa anterior a la invención de la escritura.',1),
(@topic_habitos,'multiple','¿Qué tipo de fuente puede servir para conocer la Prehistoria?','["Una herramienta de piedra", "Un mensaje de móvil", "Un periódico actual", "Un semáforo"]','Una herramienta de piedra','En la Prehistoria no había escritura, por eso son importantes los restos materiales.',1),
(@topic_habitos,'text','Completa: La etapa anterior a la invención de la escritura se llama...',NULL,'prehistoria','La Prehistoria termina cuando aparece la escritura.',1),
(@topic_habitos,'text','Escribe una fuente histórica que sirva para conocer el pasado.',NULL,'herramienta|hueso|pintura rupestre|documento|fotografía|fotografia|resto material|monumento','Una fuente histórica puede ser un objeto, un documento, una pintura, un hueso, un edificio o una fotografía.',1),
(@topic_habitos,'multiple','¿Cuál de estas opciones ordena bien el tiempo de menor a mayor?','["década, siglo, milenio", "milenio, siglo, década", "siglo, década, milenio", "década, milenio, siglo"]','década, siglo, milenio','Una década son 10 años, un siglo 100 y un milenio 1.000.',2),
(@topic_habitos,'multiple','¿Por qué no hay documentos escritos del Paleolítico?','["Porque todavía no se había inventado la escritura", "Porque no existían animales", "Porque no había piedras", "Porque todo era de metal"]','Porque todavía no se había inventado la escritura','La escritura apareció más tarde; por eso el Paleolítico se conoce por restos materiales.',2),
(@topic_habitos,'multiple','¿Cuáles son las tres grandes etapas de la Prehistoria?','["Paleolítico, Neolítico y Edad de los Metales", "Edad Antigua, Media y Moderna", "Primavera, verano e invierno", "Romanos, visigodos y árabes"]','Paleolítico, Neolítico y Edad de los Metales','La Prehistoria se divide en Paleolítico, Neolítico y Edad de los Metales.',1),
(@topic_habitos,'multiple','¿Qué etapa va primero?','["Neolítico", "Edad de los Metales", "Paleolítico", "Edad Media"]','Paleolítico','El Paleolítico es la primera etapa de la Prehistoria.',1),
(@topic_habitos,'multiple','¿Qué etapa va después del Neolítico?','["Paleolítico", "Edad de los Metales", "Edad Moderna", "Edad Media"]','Edad de los Metales','Después del Neolítico llegó la Edad de los Metales.',1),
(@topic_habitos,'text','Ordena estas etapas: Neolítico, Edad de los Metales, Paleolítico.',NULL,'paleolítico, neolítico, edad de los metales|paleolitico, neolitico, edad de los metales','El orden correcto es Paleolítico, Neolítico y Edad de los Metales.',2),
(@topic_habitos,'multiple','¿Qué cambio marca el paso hacia el Neolítico?','["La agricultura y la ganadería", "La invención del teléfono", "La construcción de carreteras", "La electricidad"]','La agricultura y la ganadería','En el Neolítico aparecen la agricultura y la ganadería.',1),
(@topic_habitos,'multiple','En el Paleolítico, muchas personas eran...','["nómadas", "sedentarias", "industriales", "marineros modernos"]','nómadas','Eran nómadas porque se desplazaban buscando alimento.',1),
(@topic_habitos,'multiple','¿De qué vivían principalmente en el Paleolítico?','["Caza, pesca y recolección", "Tiendas y supermercados", "Agricultura intensiva", "Fábricas"]','Caza, pesca y recolección','En el Paleolítico obtenían alimentos cazando, pescando y recolectando.',1),
(@topic_habitos,'multiple','¿Qué significa ser nómada?','["Moverse de un lugar a otro para vivir", "Vivir siempre en el mismo pueblo", "Tener muchas monedas", "Usar metales"]','Moverse de un lugar a otro para vivir','Los nómadas no se quedaban siempre en el mismo lugar.',1),
(@topic_habitos,'multiple','¿Qué material usaban mucho para fabricar herramientas en el Paleolítico?','["Piedra tallada", "Plástico", "Acero inoxidable", "Cristal moderno"]','Piedra tallada','En el Paleolítico se fabricaban herramientas de piedra tallada, hueso y madera.',1),
(@topic_habitos,'multiple','¿Para qué fue importante el fuego?','["Para calentarse, cocinar e iluminar", "Para escribir libros", "Para fabricar teléfonos", "Para cultivar cereales"]','Para calentarse, cocinar e iluminar','El fuego ayudó a cocinar, calentarse, iluminar y protegerse.',1),
(@topic_habitos,'multiple','¿Dónde podían vivir los grupos del Paleolítico?','["Cuevas o refugios naturales", "Rascacielos", "Castillos medievales", "Pisos con ascensor"]','Cuevas o refugios naturales','Usaban cuevas, refugios naturales y cabañas sencillas.',1),
(@topic_habitos,'text','Escribe una actividad para conseguir comida en el Paleolítico.',NULL,'caza|pesca|recolección|recoleccion|cazar|pescar|recolectar','Cazaban, pescaban y recolectaban frutos, raíces o semillas.',1),
(@topic_habitos,'text','Completa: Las pinturas hechas en cuevas o rocas se llaman pinturas...',NULL,'rupestres|rupestre','Las pinturas rupestres se realizaban sobre rocas o paredes de cuevas.',1),
(@topic_habitos,'multiple','¿Qué representaban muchas pinturas rupestres?','["Animales y escenas de caza", "Ordenadores", "Coches", "Mapas de metro"]','Animales y escenas de caza','Muchas pinturas rupestres mostraban animales, escenas de caza o signos.',1),
(@topic_habitos,'multiple','Si un grupo sigue a los animales para cazar y cambia de lugar, es un grupo...','["nómada", "sedentario", "metalúrgico", "romano"]','nómada','Cambiar de lugar para buscar alimento es propio de grupos nómadas.',2),
(@topic_habitos,'multiple','¿Qué aparece en el Neolítico?','["Agricultura y ganadería", "Internet", "Trenes de alta velocidad", "Máquinas de vapor"]','Agricultura y ganadería','El Neolítico trajo el cultivo de plantas y la cría de animales.',1),
(@topic_habitos,'multiple','¿Qué significa sedentario?','["Vivir de forma estable en un lugar", "Cambiar siempre de lugar", "Cazar sin parar", "Vivir solo en cuevas"]','Vivir de forma estable en un lugar','Los grupos sedentarios viven en un lugar fijo, normalmente en poblados.',1),
(@topic_habitos,'multiple','¿Por qué pudieron vivir en poblados en el Neolítico?','["Porque producían alimentos con agricultura y ganadería", "Porque inventaron el avión", "Porque no necesitaban agua", "Porque ya tenían coches"]','Porque producían alimentos con agricultura y ganadería','Al cultivar y criar animales no necesitaban desplazarse tanto para buscar comida.',2),
(@topic_habitos,'multiple','¿Para qué servía la cerámica en el Neolítico?','["Cocinar y guardar alimentos", "Enviar correos electrónicos", "Construir coches", "Fabricar ventanas"]','Cocinar y guardar alimentos','La cerámica ayudaba a cocinar, transportar o almacenar alimentos.',1),
(@topic_habitos,'multiple','¿Qué herramienta se relaciona con el Neolítico?','["Piedra pulida", "Teléfono móvil", "Lata de refresco", "Rueda de coche"]','Piedra pulida','En el Neolítico se usaron herramientas de piedra pulida.',1),
(@topic_habitos,'text','Completa: En el Neolítico las personas aprendieron a cultivar plantas; eso se llama...',NULL,'agricultura','La agricultura es el cultivo de la tierra para obtener alimentos.',1),
(@topic_habitos,'text','Completa: La cría de animales domésticos se llama...',NULL,'ganadería|ganaderia','La ganadería consiste en criar animales domésticos.',1),
(@topic_habitos,'multiple','¿Cuál es un cambio importante del Neolítico?','["De nómadas a sedentarios", "De escritura a prehistoria", "De metal a plástico", "De ciudades a cuevas"]','De nómadas a sedentarios','Con la agricultura y la ganadería muchos grupos empezaron a vivir en poblados.',2),
(@topic_habitos,'multiple','¿Cuál de estos alimentos pudo empezar a obtenerse cultivando?','["Cereal", "Pescado del mar", "Carne de animal salvaje", "Piedra"]','Cereal','En el Neolítico se cultivaron plantas como cereales y legumbres.',1),
(@topic_habitos,'multiple','¿Qué permitió guardar mejor los alimentos?','["La cerámica", "La televisión", "El papel de periódico", "El cemento moderno"]','La cerámica','Los recipientes de cerámica servían para guardar y cocinar alimentos.',1),
(@topic_habitos,'multiple','¿Qué aprendieron a utilizar en la Edad de los Metales?','["Metales", "Plástico", "Electricidad", "Gasolina"]','Metales','En esta etapa se fabricaron objetos con metales.',1),
(@topic_habitos,'multiple','¿Cuál de estos metales se usó en la Prehistoria?','["Cobre", "Aluminio de latas modernas", "Uranio", "Titanio espacial"]','Cobre','Primero se usó cobre, luego bronce y más tarde hierro.',1),
(@topic_habitos,'multiple','¿Qué tres metales se suelen estudiar en esta etapa?','["Cobre, bronce e hierro", "Oro, papel y plástico", "Hierro, cristal y goma", "Cobre, agua y madera"]','Cobre, bronce e hierro','La Edad de los Metales se asocia al cobre, bronce e hierro.',1),
(@topic_habitos,'multiple','¿Qué aumentó en la Edad de los Metales?','["El comercio entre comunidades", "El uso de móviles", "Los supermercados", "Los trenes"]','El comercio entre comunidades','Las comunidades intercambiaban metales, alimentos, cerámica o adornos.',1),
(@topic_habitos,'multiple','¿Por qué algunos poblados tenían murallas?','["Para protegerse", "Para jugar", "Para cultivar mejor", "Para guardar agua únicamente"]','Para protegerse','Al crecer los poblados, algunos se fortificaron con murallas.',1),
(@topic_habitos,'multiple','¿Qué es un megalito?','["Monumento hecho con grandes piedras", "Una pintura pequeña", "Una herramienta de plástico", "Un tipo de moneda"]','Monumento hecho con grandes piedras','Los megalitos son monumentos construidos con grandes piedras.',1),
(@topic_habitos,'text','Completa: Un monumento megalítico relacionado muchas veces con enterramientos es el...',NULL,'dolmen','Un dolmen es un monumento hecho con grandes piedras, a menudo relacionado con enterramientos.',1),
(@topic_habitos,'multiple','¿Qué objeto se podía fabricar con metal?','["Herramientas y armas", "Libros impresos", "Bicicletas modernas", "Bombillas"]','Herramientas y armas','Los metales permitieron fabricar herramientas, armas y adornos más resistentes.',1),
(@topic_habitos,'multiple','¿Cuál de estas opciones muestra un cambio de la Edad de los Metales?','["Aldeas más grandes y comercio", "Desaparece la vida en grupo", "Se deja de usar cualquier herramienta", "Vuelven todos a ser nómadas"]','Aldeas más grandes y comercio','La Edad de los Metales trajo poblados más organizados y más intercambios.',2),
(@topic_habitos,'multiple','Orden correcto de uso de metales en muchas zonas:','["cobre, bronce, hierro", "hierro, cobre, bronce", "bronce, hierro, cobre", "hierro, bronce, cobre"]','cobre, bronce, hierro','Primero se utilizó cobre, después bronce y más tarde hierro.',2),
(@topic_habitos,'multiple','¿Qué cambio de hábito se produjo del Paleolítico al Neolítico?','["Pasaron de recolectar y cazar a cultivar y criar animales", "Pasaron de escribir libros a dibujar cuevas", "Pasaron de usar electricidad a usar fuego", "Pasaron de ciudades a cuevas"]','Pasaron de recolectar y cazar a cultivar y criar animales','La agricultura y la ganadería cambiaron la forma de conseguir alimentos.',2),
(@topic_habitos,'multiple','¿Qué cambio hubo en la vivienda?','["De refugios temporales a poblados estables", "De pisos a cuevas siempre", "De castillos a rascacielos", "No hubo ningún cambio"]','De refugios temporales a poblados estables','El sedentarismo permitió construir poblados más estables.',2),
(@topic_habitos,'multiple','¿Qué cambió en las herramientas?','["De piedra tallada a piedra pulida y metales", "De móviles a piedras", "De hierro a plástico", "De herramientas a juguetes"]','De piedra tallada a piedra pulida y metales','La tecnología pasó de piedra tallada a piedra pulida y después a metales.',2),
(@topic_habitos,'multiple','¿Qué cambió en el trabajo de las personas?','["Aparecieron agricultores, ganaderos, artesanos y comerciantes", "Todos hicieron exactamente lo mismo siempre", "Desapareció la alimentación", "Solo había cazadores modernos"]','Aparecieron agricultores, ganaderos, artesanos y comerciantes','Al producir alimentos y objetos, se fueron diferenciando trabajos.',2),
(@topic_habitos,'text','Explica en una frase qué significa que cambiaron los hábitos de vida.',NULL,'respuesta libre','Una buena respuesta debe decir que cambió la forma de conseguir alimento, vivir, trabajar, fabricar herramientas u organizarse.',2),
(@topic_habitos,'multiple','¿Qué permitió que hubiera más alimentos guardados?','["Agricultura, ganadería y cerámica", "Solo pintar cuevas", "Usar móviles", "No vivir en grupo"]','Agricultura, ganadería y cerámica','Cultivar, criar animales y guardar alimentos cambió la vida diaria.',2),
(@topic_habitos,'multiple','¿Qué hábito se relaciona mejor con el Paleolítico?','["Desplazarse para buscar comida", "Vivir siempre en poblados grandes", "Comprar en tiendas", "Escribir documentos"]','Desplazarse para buscar comida','En el Paleolítico los grupos eran nómadas.',1),
(@topic_habitos,'multiple','¿Qué hábito se relaciona mejor con el Neolítico?','["Cuidar cultivos y animales", "Usar hierro para trenes", "Escribir periódicos", "Vivir en ciudades romanas"]','Cuidar cultivos y animales','El Neolítico se relaciona con agricultura y ganadería.',1),
(@topic_habitos,'multiple','¿Qué hábito se relaciona mejor con la Edad de los Metales?','["Intercambiar objetos y fabricar con metal", "Solo recolectar frutos", "No usar herramientas", "Vivir siempre en cuevas"]','Intercambiar objetos y fabricar con metal','En la Edad de los Metales aumentaron la metalurgia y el comercio.',1),
(@topic_habitos,'multiple','¿Dónde está Andalucía?','["En la Península Ibérica", "En América", "En Oceanía", "En el Polo Norte"]','En la Península Ibérica','Andalucía forma parte de la Península Ibérica.',1),
(@topic_habitos,'multiple','¿Qué restos ayudan a conocer la Prehistoria en la Península Ibérica?','["Herramientas, huesos, pinturas y poblados", "Solo billetes actuales", "Solo semáforos", "Solo vídeos modernos"]','Herramientas, huesos, pinturas y poblados','Los restos materiales permiten estudiar cómo vivían las personas prehistóricas.',1),
(@topic_habitos,'multiple','¿Por qué los grupos humanos se adaptaban de formas distintas?','["Porque vivían en paisajes diferentes", "Porque todos tenían el mismo trabajo", "Porque no necesitaban alimentos", "Porque no existían ríos"]','Porque vivían en paisajes diferentes','Costas, montañas, ríos y llanuras ofrecen recursos diferentes.',2),
(@topic_habitos,'multiple','En la Península Ibérica, el arte rupestre aparece sobre todo en...','["cuevas y rocas", "edificios modernos", "pantallas de ordenador", "carreteras"]','cuevas y rocas','El arte rupestre se conserva en paredes de cuevas y superficies rocosas.',1),
(@topic_habitos,'multiple','¿Qué se extendió durante el Neolítico en la Península Ibérica?','["Agricultura, ganadería y poblados", "La imprenta", "Las fábricas", "Los trenes"]','Agricultura, ganadería y poblados','El Neolítico trajo producción de alimentos y asentamientos más estables.',1),
(@topic_habitos,'multiple','¿Qué creció durante la Edad de los Metales en la Península Ibérica?','["Aldeas, comercio y monumentos megalíticos", "Los aeropuertos", "Las carreteras modernas", "Los periódicos"]','Aldeas, comercio y monumentos megalíticos','La metalurgia favoreció poblados más complejos y más intercambio.',2),
(@topic_habitos,'multiple','¿Qué comunidad estamos estudiando en este tema?','["Andalucía", "Galicia", "Aragón", "Canarias"]','Andalucía','El tema se centra también en nuestra comunidad: Andalucía.',1),
(@topic_habitos,'multiple','¿Qué monumento prehistórico andaluz es muy conocido?','["Dólmenes de Antequera", "Torre Eiffel", "Coliseo romano", "Muralla China"]','Dólmenes de Antequera','Los dólmenes de Antequera son monumentos megalíticos importantes en Andalucía.',1),
(@topic_habitos,'multiple','¿Los Millares se relaciona con qué etapa?','["Edad de los Metales", "Edad Contemporánea", "Edad Media", "Roma actual"]','Edad de los Metales','Los Millares es un poblado importante de la Edad de los Metales en Almería.',2),
(@topic_habitos,'multiple','¿En qué provincia andaluza está Los Millares?','["Almería", "Cádiz", "Sevilla", "Jaén"]','Almería','Los Millares se encuentra en la provincia de Almería.',2),
(@topic_habitos,'multiple','¿Qué podemos encontrar en yacimientos prehistóricos andaluces?','["Herramientas, huesos, pinturas y restos de poblados", "Solo ordenadores", "Solo coches antiguos", "Solo monedas modernas"]','Herramientas, huesos, pinturas y restos de poblados','Los yacimientos conservan restos que ayudan a estudiar el pasado.',1),
(@topic_habitos,'multiple','¿Qué muestran los restos prehistóricos de Andalucía?','["Cómo cambiaron la vida, las herramientas y los poblados", "Que no vivió nadie", "Que todo empezó en la Edad Media", "Que solo había ciudades modernas"]','Cómo cambiaron la vida, las herramientas y los poblados','Los restos muestran el paso de grupos nómadas a comunidades más estables y organizadas.',2),
(@topic_habitos,'text','Nombra un ejemplo de resto prehistórico que pueda encontrarse en Andalucía.',NULL,'dolmen|dólmen|pintura rupestre|cueva|herramienta|hueso|poblado|los millares|antequera','En Andalucía hay dólmenes, cuevas con arte rupestre, herramientas, huesos y restos de poblados.',1),
(@topic_habitos,'text','Escribe un lugar o ejemplo prehistórico andaluz estudiado en el tema.',NULL,'dólmenes de antequera|dolmenes de antequera|los millares|cueva de nerja|cueva de la pileta|antequera|almería|almeria','Son ejemplos válidos los dólmenes de Antequera, Los Millares o cuevas con arte rupestre.',2),
(@topic_habitos,'multiple','¿Para qué sirven los restos arqueológicos?','["Para conocer cómo vivían las personas del pasado", "Para adivinar el futuro", "Para estudiar solo matemáticas", "Para fabricar juguetes"]','Para conocer cómo vivían las personas del pasado','La arqueología estudia restos materiales para comprender la vida antigua.',1),
(@topic_habitos,'multiple','¿Qué es agricultura?','["Cultivar la tierra", "Cazar animales salvajes", "Pintar cuevas", "Fabricar metales"]','Cultivar la tierra','La agricultura es el cultivo de plantas para obtener alimentos.',1),
(@topic_habitos,'multiple','¿Qué es ganadería?','["Criar animales domésticos", "Recoger frutos silvestres", "Talllar solo piedra", "Hacer pinturas rupestres"]','Criar animales domésticos','La ganadería es la cría de animales para obtener alimento, pieles u otros productos.',1),
(@topic_habitos,'multiple','¿Qué es una pintura rupestre?','["Pintura realizada en rocas o cuevas", "Pintura de una libreta", "Fotografía actual", "Un mapa moderno"]','Pintura realizada en rocas o cuevas','Rupestre significa relacionado con rocas o cuevas.',1),
(@topic_habitos,'multiple','¿Qué es un dolmen?','["Monumento hecho con grandes piedras", "Herramienta pequeña de madera", "Un animal doméstico", "Una vasija de cerámica"]','Monumento hecho con grandes piedras','Un dolmen es un monumento megalítico construido con grandes piedras.',1),
(@topic_habitos,'multiple','¿Qué es la cerámica?','["Objetos hechos con barro cocido", "Una herramienta de metal", "Una pintura en una cueva", "Una fruta"]','Objetos hechos con barro cocido','La cerámica se fabrica modelando barro y cociéndolo.',1),
(@topic_habitos,'multiple','¿Qué es un yacimiento?','["Lugar donde se encuentran restos antiguos", "Un tipo de ropa", "Un alimento prehistórico", "Un río moderno"]','Lugar donde se encuentran restos antiguos','Un yacimiento arqueológico conserva restos del pasado.',1),
(@topic_habitos,'multiple','¿Cuál es la mejor definición de fuente histórica?','["Objeto, documento o resto que informa sobre el pasado", "Solo una fuente de agua", "Una pregunta de examen", "Una calculadora"]','Objeto, documento o resto que informa sobre el pasado','Las fuentes históricas ayudan a conocer hechos y formas de vida del pasado.',1),
(@topic_habitos,'text','Escribe una diferencia entre nómada y sedentario.',NULL,'respuesta libre','Debe aparecer la idea de que el nómada se desplaza y el sedentario vive en un lugar fijo.',2),
(@topic_habitos,'text','¿Por qué el fuego fue importante en el Paleolítico?',NULL,'respuesta libre','Una respuesta válida debe mencionar que servía para calentarse, cocinar, iluminar o protegerse.',2),
(@topic_habitos,'text','¿Por qué la agricultura cambió la forma de vida?',NULL,'respuesta libre','La agricultura permitió producir alimentos y vivir de forma más estable en poblados.',2),
(@topic_habitos,'text','¿Por qué los metales mejoraron las herramientas?',NULL,'respuesta libre','Los metales permitieron fabricar herramientas más resistentes y útiles que muchas de piedra.',2),
(@topic_habitos,'text','Resume el cambio principal desde el Paleolítico hasta la Edad de los Metales.',NULL,'respuesta libre','La respuesta debe explicar el paso de grupos nómadas cazadores-recolectores a poblados agrícolas, ganaderos y con metales.',3),
(@topic_habitos,'multiple','¿Cuál es la respuesta más completa sobre cómo cambiaron los hábitos?','["Cambió la alimentación, la vivienda, las herramientas, el trabajo y la organización", "Solo cambiaron los nombres", "Solo cambiaron los animales", "No cambió nada"]','Cambió la alimentación, la vivienda, las herramientas, el trabajo y la organización','El tema trata varios cambios de la vida diaria, no solo uno.',3),
(@topic_habitos,'multiple','Si una pregunta dice “explica cómo cambió la alimentación”, ¿qué debes comparar?','["Caza y recolección con agricultura y ganadería", "Dólmenes con cuevas únicamente", "Siglos con décadas", "Cobre con bronce únicamente"]','Caza y recolección con agricultura y ganadería','La alimentación cambió al pasar de obtener alimentos de la naturaleza a producirlos.',2),
(@topic_habitos,'multiple','Si una pregunta dice “compara Paleolítico y Neolítico”, ¿qué opción es mejor?','["Paleolítico nómada; Neolítico sedentario", "Paleolítico con móviles; Neolítico con coches", "Son exactamente iguales", "Neolítico antes que Paleolítico"]','Paleolítico nómada; Neolítico sedentario','El contraste principal es caza-recolección y nomadismo frente a agricultura, ganadería y sedentarismo.',2),
(@topic_habitos,'multiple','¿Qué respuesta sería correcta para “¿qué fue antes?”','["Paleolítico antes que Neolítico", "Edad de los Metales antes que Paleolítico", "Neolítico antes que Paleolítico", "La escritura antes que la Prehistoria"]','Paleolítico antes que Neolítico','El Paleolítico es la primera etapa de la Prehistoria.',1),
(@topic_habitos,'text','Escribe dos palabras clave del tema.',NULL,'respuesta libre','Pueden ser: Prehistoria, Paleolítico, Neolítico, nómada, sedentario, agricultura, ganadería, metal, dolmen, pintura rupestre.',1),
(@topic_habitos,'multiple','En un examen, si ves “piedra tallada”, lo relacionas sobre todo con...','["Paleolítico", "Neolítico", "Edad de los Metales", "Edad Moderna"]','Paleolítico','La piedra tallada es típica del Paleolítico.',2),
(@topic_habitos,'multiple','En un examen, si ves “piedra pulida”, lo relacionas sobre todo con...','["Neolítico", "Paleolítico", "Edad Contemporánea", "Imperio romano"]','Neolítico','La piedra pulida se relaciona con el Neolítico.',2),
(@topic_habitos,'multiple','En un examen, si ves “cobre, bronce e hierro”, lo relacionas con...','["Edad de los Metales", "Paleolítico", "Neolítico", "Edad Media"]','Edad de los Metales','El uso de metales da nombre a esta etapa.',2),
(@topic_habitos,'multiple','¿Qué opción es una consecuencia de hacerse sedentarios?','["Construir poblados estables", "Cambiar de cueva cada semana", "Abandonar todos los alimentos", "No usar herramientas"]','Construir poblados estables','Si viven en el mismo lugar, pueden construir viviendas y poblados más estables.',2),
(@topic_habitos,'multiple','¿Qué opción es falsa?','["En el Paleolítico había supermercados", "En el Neolítico hubo agricultura", "En la Edad de los Metales se usaron metales", "La Prehistoria es anterior a la escritura"]','En el Paleolítico había supermercados','Los supermercados son actuales, no del Paleolítico.',1),
(@topic_habitos,'multiple','¿Qué opción es verdadera?','["La agricultura favoreció el sedentarismo", "La escritura inició el Paleolítico", "El hierro apareció antes que la piedra tallada", "Los dólmenes son ordenadores antiguos"]','La agricultura favoreció el sedentarismo','Al producir alimentos, muchos grupos pudieron vivir en un lugar fijo.',2),
(@topic_habitos,'text','Explica qué es una fuente material.',NULL,'respuesta libre','Una fuente material es un objeto o resto físico del pasado, como una herramienta, un hueso, una vasija o un monumento.',2),
(@topic_habitos,'text','Escribe un ejemplo de cambio tecnológico en la Prehistoria.',NULL,'respuesta libre','Puede mencionar la piedra tallada, la piedra pulida, la cerámica o el uso de metales.',2);
COMMIT;


-- ============================================================
-- V4.8 - Preguntas adicionales Tema 8 Conocimiento del Medio
-- ============================================================
-- Academia Leire V4.8 - Refuerzo Tema 8 Conocimiento del Medio
-- Añade 50 preguntas nuevas y variadas sobre el tema "Cómo han cambiado nuestros hábitos".
START TRANSACTION;
SET @topic_habitos := (SELECT id FROM topics WHERE subject='Conocimiento del Medio' AND title='Tema 8 · Cómo han cambiado nuestros hábitos' LIMIT 1);
INSERT INTO topics(subject, course, title, description, content) SELECT 'Conocimiento del Medio','4º Primaria Andalucía','Tema 8 · Cómo han cambiado nuestros hábitos','Prehistoria: tiempo e historia, Paleolítico, Neolítico, Edad de los Metales, Península Ibérica y Andalucía.','Tema de estudio sobre cómo cambiaron los hábitos en la Prehistoria.' WHERE @topic_habitos IS NULL;
SET @topic_habitos := (SELECT id FROM topics WHERE subject='Conocimiento del Medio' AND title='Tema 8 · Cómo han cambiado nuestros hábitos' LIMIT 1);
DELETE FROM questions WHERE topic_id=@topic_habitos AND question IN (
'¿Qué diferencia principal hay entre historia y Prehistoria?',
'¿Cuál es una fuente material para estudiar la Prehistoria?',
'¿Qué significa ordenar los hechos en una línea del tiempo?',
'Si algo ocurrió hace 2 milenios, ocurrió hace aproximadamente...',
'Explica con tus palabras qué es una línea del tiempo.',
'¿Qué etapa de la Prehistoria se relaciona más con la caza, la pesca y la recolección?',
'¿Por qué los grupos del Paleolítico eran nómadas?',
'¿Qué herramienta sería más propia del Paleolítico?',
'¿Para qué servía el fuego en el Paleolítico?',
'Nombra dos alimentos que podían conseguir en el Paleolítico.',
'¿Qué cambio fue más importante en el Neolítico?',
'¿Qué significa que una comunidad sea sedentaria?',
'¿Por qué la cerámica fue útil en el Neolítico?',
'¿Qué herramienta se asocia mejor con la agricultura del Neolítico?',
'Explica por qué la agricultura ayudó a que las personas vivieran en poblados.',
'¿Qué metales se usaron en la Edad de los Metales?',
'¿Qué mejoró con el uso de los metales?',
'¿Por qué aumentó el comercio en la Edad de los Metales?',
'¿Qué es un megalito?',
'Escribe una diferencia entre un menhir y un dolmen.',
'¿Qué etapa relacionarías con poblados más grandes, murallas y más comercio?',
'¿Qué resto de la Península Ibérica puede ayudar a conocer el Paleolítico?',
'¿Por qué la Península Ibérica tuvo formas de vida variadas en la Prehistoria?',
'¿Qué actividad se extendió por la Península Ibérica durante el Neolítico?',
'¿Qué tipo de restos prehistóricos se han encontrado en la Península Ibérica?',
'Escribe una razón por la que los ríos eran importantes para los grupos prehistóricos.',
'¿Qué lugar andaluz es famoso por sus dólmenes?',
'¿Con qué se relaciona Los Millares?',
'¿Qué nos enseñan los yacimientos prehistóricos andaluces?',
'¿Qué ejemplo andaluz se relaciona con monumentos megalíticos?',
'Nombra un ejemplo de resto prehistórico de Andalucía.',
'¿Qué pareja está bien relacionada?',
'¿Qué pareja está bien relacionada?',
'¿Qué pareja está bien relacionada?',
'¿Cuál es el orden correcto?',
'¿Qué frase resume mejor el paso del Paleolítico al Neolítico?',
'¿Qué frase resume mejor el paso del Neolítico a la Edad de los Metales?',
'Compara Paleolítico y Neolítico usando las palabras nómada y sedentario.',
'Compara las herramientas del Paleolítico, Neolítico y Edad de los Metales.',
'Si en un examen aparece “domesticación de animales”, debes relacionarlo con...',
'Si en un examen aparece “arte rupestre”, debes pensar en...',
'Si en un examen aparece “almacenar grano”, ¿con qué cambio lo relacionas?',
'¿Cuál de estas opciones es una causa del sedentarismo?',
'¿Cuál de estas opciones es una consecuencia de la ganadería?',
'¿Cuál es una consecuencia del comercio en la Edad de los Metales?',
'¿Cuál sería una buena respuesta a “cómo cambiaron nuestros hábitos en la Prehistoria”?',
'Haz un resumen de 4 líneas del tema: cómo cambiaron nuestros hábitos.',
'Explica por qué los restos de Andalucía son importantes para estudiar la Prehistoria.',
'Di tres palabras clave del tema y explica una.',
'¿Qué respuesta sería más completa sobre las fuentes históricas de la Prehistoria?',
'¿Qué opción demuestra que una alumna entiende el tema?'
);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES
(@topic_habitos,'multiple','¿Qué diferencia principal hay entre historia y Prehistoria?','["La historia tiene fuentes escritas y la Prehistoria es anterior a la escritura", "La Prehistoria ocurrió ayer y la historia hoy", "La historia solo estudia animales", "No hay ninguna diferencia"]','La historia tiene fuentes escritas y la Prehistoria es anterior a la escritura','La Prehistoria termina cuando aparece la escritura. Antes de eso usamos sobre todo restos materiales para conocer el pasado.',2),
(@topic_habitos,'multiple','¿Cuál es una fuente material para estudiar la Prehistoria?','["Una vasija de cerámica", "Una noticia de internet", "Un billete actual", "Un correo electrónico"]','Una vasija de cerámica','Las fuentes materiales son restos físicos: herramientas, huesos, vasijas, monumentos o pinturas.',1),
(@topic_habitos,'multiple','¿Qué significa ordenar los hechos en una línea del tiempo?','["Colocarlos de más antiguos a más recientes", "Ponerlos por tamaño", "Ordenarlos alfabéticamente", "Colorearlos todos igual"]','Colocarlos de más antiguos a más recientes','La línea del tiempo ayuda a ver cuándo ocurrió cada hecho y qué ocurrió antes o después.',1),
(@topic_habitos,'multiple','Si algo ocurrió hace 2 milenios, ocurrió hace aproximadamente...','["2.000 años", "200 años", "20 años", "2 años"]','2.000 años','Un milenio son 1.000 años, por eso dos milenios son 2.000 años.',1),
(@topic_habitos,'text','Explica con tus palabras qué es una línea del tiempo.',NULL,'respuesta libre','Debe decir que sirve para ordenar hechos o etapas según cuándo ocurrieron.',2),
(@topic_habitos,'multiple','¿Qué etapa de la Prehistoria se relaciona más con la caza, la pesca y la recolección?','["Paleolítico", "Neolítico", "Edad de los Metales", "Edad Moderna"]','Paleolítico','En el Paleolítico los grupos humanos obtenían alimento de la naturaleza cazando, pescando y recolectando.',1),
(@topic_habitos,'multiple','¿Por qué los grupos del Paleolítico eran nómadas?','["Porque seguían a los animales y buscaban alimentos", "Porque tenían colegios modernos", "Porque cultivaban grandes campos", "Porque viajaban en tren"]','Porque seguían a los animales y buscaban alimentos','Al no producir alimentos, se desplazaban para encontrar recursos.',2),
(@topic_habitos,'multiple','¿Qué herramienta sería más propia del Paleolítico?','["Un bifaz o piedra tallada", "Una llave inglesa", "Una rueda de coche", "Un libro impreso"]','Un bifaz o piedra tallada','La piedra tallada era una técnica típica del Paleolítico.',1),
(@topic_habitos,'multiple','¿Para qué servía el fuego en el Paleolítico?','["Para calentarse, cocinar, iluminar y protegerse", "Solo para decorar cuevas", "Para escribir libros", "Para construir ordenadores"]','Para calentarse, cocinar, iluminar y protegerse','El fuego cambió mucho la vida diaria porque dio calor, luz, protección y permitió cocinar.',1),
(@topic_habitos,'text','Nombra dos alimentos que podían conseguir en el Paleolítico.',NULL,'carne|pescado|frutos|raíces|raices|semillas|bayas|animales','Podían obtener carne de la caza, pescado, frutos, raíces, semillas o bayas.',1),
(@topic_habitos,'multiple','¿Qué cambio fue más importante en el Neolítico?','["Aparecieron la agricultura y la ganadería", "Aparecieron los teléfonos móviles", "Desaparecieron todas las aldeas", "Se inventaron los coches"]','Aparecieron la agricultura y la ganadería','La agricultura y la ganadería permitieron producir alimentos y vivir en poblados.',1),
(@topic_habitos,'multiple','¿Qué significa que una comunidad sea sedentaria?','["Que vive de forma estable en un lugar", "Que siempre cambia de lugar", "Que no usa herramientas", "Que vive solo en barcos"]','Que vive de forma estable en un lugar','Sedentario significa que vive en un lugar fijo o estable.',1),
(@topic_habitos,'multiple','¿Por qué la cerámica fue útil en el Neolítico?','["Para guardar y cocinar alimentos", "Para fabricar teléfonos", "Para hacer armas de fuego", "Para construir coches"]','Para guardar y cocinar alimentos','La cerámica permitió almacenar granos, líquidos y alimentos, además de cocinar.',2),
(@topic_habitos,'multiple','¿Qué herramienta se asocia mejor con la agricultura del Neolítico?','["Hoz", "Telescopio", "Martillo neumático", "Ordenador"]','Hoz','La hoz se usaba para cortar cereales y se relaciona con la agricultura.',2),
(@topic_habitos,'text','Explica por qué la agricultura ayudó a que las personas vivieran en poblados.',NULL,'respuesta libre','La idea clave es que al cultivar alimentos podían quedarse cerca de los campos y almacenar comida.',2),
(@topic_habitos,'multiple','¿Qué metales se usaron en la Edad de los Metales?','["Cobre, bronce e hierro", "Plástico, goma y cristal", "Oro, papel y madera", "Agua, tierra y aire"]','Cobre, bronce e hierro','Primero se usó cobre, después bronce y más tarde hierro.',1),
(@topic_habitos,'multiple','¿Qué mejoró con el uso de los metales?','["Herramientas, armas y adornos", "Solo los cuentos", "Solo los dibujos", "Nada cambió"]','Herramientas, armas y adornos','Los metales permitieron fabricar objetos más resistentes y variados.',1),
(@topic_habitos,'multiple','¿Por qué aumentó el comercio en la Edad de los Metales?','["Porque intercambiaban metales, alimentos y objetos", "Porque todos tenían supermercados", "Porque ya existía internet", "Porque dejaron de fabricar objetos"]','Porque intercambiaban metales, alimentos y objetos','El comercio creció porque no todos los lugares tenían los mismos recursos.',2),
(@topic_habitos,'multiple','¿Qué es un megalito?','["Una construcción hecha con grandes piedras", "Una herramienta pequeña de hueso", "Una pintura en papel", "Una moneda moderna"]','Una construcción hecha con grandes piedras','Los megalitos son monumentos construidos con piedras de gran tamaño.',1),
(@topic_habitos,'text','Escribe una diferencia entre un menhir y un dolmen.',NULL,'respuesta libre','Una respuesta válida: el menhir es una gran piedra colocada verticalmente; el dolmen está formado por varias piedras y suele relacionarse con enterramientos.',3),
(@topic_habitos,'multiple','¿Qué etapa relacionarías con poblados más grandes, murallas y más comercio?','["Edad de los Metales", "Paleolítico", "Edad Contemporánea", "Inicio del curso escolar"]','Edad de los Metales','En la Edad de los Metales crecieron algunos poblados, aparecieron defensas y aumentaron los intercambios.',2),
(@topic_habitos,'multiple','¿Qué resto de la Península Ibérica puede ayudar a conocer el Paleolítico?','["Pinturas rupestres en cuevas", "Un periódico actual", "Un semáforo", "Un billete de autobús"]','Pinturas rupestres en cuevas','El arte rupestre es una fuente material muy importante para estudiar el Paleolítico.',1),
(@topic_habitos,'multiple','¿Por qué la Península Ibérica tuvo formas de vida variadas en la Prehistoria?','["Porque tenía costas, montañas, ríos y llanuras", "Porque solo tenía desiertos", "Porque todos vivían igual", "Porque no había recursos naturales"]','Porque tenía costas, montañas, ríos y llanuras','Los paisajes distintos ofrecen recursos diferentes y condicionan la forma de vivir.',2),
(@topic_habitos,'multiple','¿Qué actividad se extendió por la Península Ibérica durante el Neolítico?','["La agricultura y la ganadería", "La fabricación de trenes", "La escritura de periódicos", "El uso de ordenadores"]','La agricultura y la ganadería','En el Neolítico se difundieron la agricultura, la ganadería y los poblados.',1),
(@topic_habitos,'multiple','¿Qué tipo de restos prehistóricos se han encontrado en la Península Ibérica?','["Herramientas, huesos, pinturas y poblados", "Solo fotografías modernas", "Solo billetes", "Solo antenas"]','Herramientas, huesos, pinturas y poblados','Estos restos permiten conocer cómo vivían las personas antes de la escritura.',1),
(@topic_habitos,'text','Escribe una razón por la que los ríos eran importantes para los grupos prehistóricos.',NULL,'respuesta libre','Podían servir para beber, pescar, regar cultivos, desplazarse o vivir cerca de recursos.',2),
(@topic_habitos,'multiple','¿Qué lugar andaluz es famoso por sus dólmenes?','["Antequera", "Madrid", "París", "Londres"]','Antequera','Los dólmenes de Antequera son un conjunto megalítico muy importante de Andalucía.',1),
(@topic_habitos,'multiple','¿Con qué se relaciona Los Millares?','["Un poblado de la Edad de los Metales en Almería", "Una ciudad romana de Francia", "Un castillo medieval de Cádiz", "Una playa actual"]','Un poblado de la Edad de los Metales en Almería','Los Millares fue un importante poblado prehistórico de la Edad de los Metales.',2),
(@topic_habitos,'multiple','¿Qué nos enseñan los yacimientos prehistóricos andaluces?','["Cómo cambiaron la vida y los hábitos de las personas", "Cómo funciona un móvil", "Cómo se hacen coches", "Cómo era internet antiguo"]','Cómo cambiaron la vida y los hábitos de las personas','Los yacimientos conservan restos que muestran vivienda, herramientas, alimentación y organización.',1),
(@topic_habitos,'multiple','¿Qué ejemplo andaluz se relaciona con monumentos megalíticos?','["Dólmenes de Antequera", "Giralda actual", "Estadio de fútbol", "Estación de tren"]','Dólmenes de Antequera','Los dólmenes de Antequera son monumentos construidos con grandes piedras.',1),
(@topic_habitos,'text','Nombra un ejemplo de resto prehistórico de Andalucía.',NULL,'dólmenes de antequera|dolmenes de antequera|los millares|cueva|pintura rupestre|herramienta|hueso|poblado|antequera|almería|almeria','Son válidos ejemplos como dólmenes, cuevas con pinturas, herramientas, huesos o poblados como Los Millares.',1),
(@topic_habitos,'multiple','¿Qué pareja está bien relacionada?','["Paleolítico - nómadas", "Neolítico - fábricas modernas", "Edad de los Metales - ordenadores", "Prehistoria - escritura abundante"]','Paleolítico - nómadas','En el Paleolítico los grupos eran principalmente nómadas.',1),
(@topic_habitos,'multiple','¿Qué pareja está bien relacionada?','["Neolítico - agricultura", "Paleolítico - coches", "Edad de los Metales - imprenta", "Prehistoria - periódicos"]','Neolítico - agricultura','El Neolítico se caracteriza por el inicio de la agricultura y la ganadería.',1),
(@topic_habitos,'multiple','¿Qué pareja está bien relacionada?','["Edad de los Metales - cobre, bronce e hierro", "Paleolítico - cerámica industrial", "Neolítico - internet", "Historia - sin fuentes escritas siempre"]','Edad de los Metales - cobre, bronce e hierro','La Edad de los Metales recibe ese nombre por el uso de metales.',1),
(@topic_habitos,'multiple','¿Cuál es el orden correcto?','["Paleolítico, Neolítico, Edad de los Metales", "Neolítico, Paleolítico, Edad de los Metales", "Edad de los Metales, Paleolítico, Neolítico", "Paleolítico, Edad Media, Neolítico"]','Paleolítico, Neolítico, Edad de los Metales','Ese es el orden básico de las etapas de la Prehistoria.',1),
(@topic_habitos,'multiple','¿Qué frase resume mejor el paso del Paleolítico al Neolítico?','["De vivir de la caza y recolección a producir alimentos", "De usar móviles a usar ordenadores", "De vivir en ciudades modernas a vivir en cuevas", "De escribir libros a no saber hablar"]','De vivir de la caza y recolección a producir alimentos','El gran cambio fue empezar a cultivar y criar animales.',2),
(@topic_habitos,'multiple','¿Qué frase resume mejor el paso del Neolítico a la Edad de los Metales?','["De la piedra pulida y poblados a objetos de metal y más comercio", "De los coches a los aviones", "De las fábricas a las cuevas", "De la escritura al silencio"]','De la piedra pulida y poblados a objetos de metal y más comercio','Los metales y el comercio son rasgos importantes de esta etapa.',2),
(@topic_habitos,'text','Compara Paleolítico y Neolítico usando las palabras nómada y sedentario.',NULL,'respuesta libre','Debe indicar que en el Paleolítico eran nómadas y en el Neolítico muchos grupos se hicieron sedentarios.',2),
(@topic_habitos,'text','Compara las herramientas del Paleolítico, Neolítico y Edad de los Metales.',NULL,'respuesta libre','Debe mencionar piedra tallada en Paleolítico, piedra pulida en Neolítico y metales en la Edad de los Metales.',3),
(@topic_habitos,'multiple','Si en un examen aparece “domesticación de animales”, debes relacionarlo con...','["Neolítico", "Paleolítico solamente", "Edad Contemporánea", "Edad Media"]','Neolítico','La domesticación de animales y la ganadería se relacionan con el Neolítico.',2),
(@topic_habitos,'multiple','Si en un examen aparece “arte rupestre”, debes pensar en...','["Pinturas en rocas o cuevas", "Pinturas en una libreta", "Un libro de texto moderno", "Una pantalla de ordenador"]','Pinturas en rocas o cuevas','Rupestre significa relacionado con rocas; muchas pinturas se hicieron en cuevas.',1),
(@topic_habitos,'multiple','Si en un examen aparece “almacenar grano”, ¿con qué cambio lo relacionas?','["Agricultura y cerámica del Neolítico", "Caza del Paleolítico únicamente", "Trenes de la Edad Moderna", "Ordenadores actuales"]','Agricultura y cerámica del Neolítico','Al cultivar, necesitaban guardar alimentos; la cerámica ayudó a almacenarlos.',2),
(@topic_habitos,'multiple','¿Cuál de estas opciones es una causa del sedentarismo?','["Cultivar alimentos cerca del poblado", "Tener que seguir siempre a los animales", "No conocer el fuego", "Vivir sin herramientas"]','Cultivar alimentos cerca del poblado','La agricultura favoreció quedarse en un lugar estable.',2),
(@topic_habitos,'multiple','¿Cuál de estas opciones es una consecuencia de la ganadería?','["Obtener alimento y productos de animales domésticos", "Abandonar todos los poblados", "Dejar de tener alimentos", "No usar animales nunca"]','Obtener alimento y productos de animales domésticos','La ganadería aportaba carne, leche, pieles y otros recursos.',1),
(@topic_habitos,'multiple','¿Cuál es una consecuencia del comercio en la Edad de los Metales?','["Intercambio de objetos y contacto entre comunidades", "Desaparición de todos los poblados", "Menos relación entre grupos", "No usar herramientas"]','Intercambio de objetos y contacto entre comunidades','El comercio permitió intercambiar productos y conectar comunidades.',2),
(@topic_habitos,'multiple','¿Cuál sería una buena respuesta a “cómo cambiaron nuestros hábitos en la Prehistoria”?','["Cambió la forma de alimentarse, vivir, trabajar y fabricar herramientas", "Solo cambió el color de la ropa", "No cambió nada importante", "Solo cambiaron los animales"]','Cambió la forma de alimentarse, vivir, trabajar y fabricar herramientas','El tema trata cambios amplios en la vida diaria a lo largo de la Prehistoria.',3),
(@topic_habitos,'text','Haz un resumen de 4 líneas del tema: cómo cambiaron nuestros hábitos.',NULL,'respuesta libre','Debe incluir Paleolítico, Neolítico, Edad de los Metales y la idea de cambio en alimentación, vivienda, herramientas y organización.',3),
(@topic_habitos,'text','Explica por qué los restos de Andalucía son importantes para estudiar la Prehistoria.',NULL,'respuesta libre','Debe decir que los restos permiten conocer cómo vivían las personas en nuestra comunidad antes de la escritura.',2),
(@topic_habitos,'text','Di tres palabras clave del tema y explica una.',NULL,'respuesta libre','Puede usar palabras como Prehistoria, Paleolítico, Neolítico, nómada, sedentario, agricultura, ganadería, dolmen o metal.',2),
(@topic_habitos,'multiple','¿Qué respuesta sería más completa sobre las fuentes históricas de la Prehistoria?','["Herramientas, huesos, pinturas, cerámica, monumentos y restos de poblados", "Solo libros escritos por prehistóricos", "Solo vídeos y fotografías", "Solo cuentos inventados"]','Herramientas, huesos, pinturas, cerámica, monumentos y restos de poblados','Como no había escritura, se estudian sobre todo restos materiales.',2),
(@topic_habitos,'multiple','¿Qué opción demuestra que una alumna entiende el tema?','["Sabe comparar etapas y explicar cambios de hábitos", "Solo memoriza una fecha sin entenderla", "Dice que todas las etapas son iguales", "Confunde Paleolítico con Edad Moderna"]','Sabe comparar etapas y explicar cambios de hábitos','Lo importante no es solo memorizar nombres, sino comprender cómo cambió la vida.',3);
COMMIT;

-- Academia Leire V4.9 - Lengua Tema 12 Anaya Andalucía
-- Añade/actualiza el tema 12 de Lengua: ¡Que llueva, que llueva! El azar.
-- Contenido original de estudio, ejercicios, deberes y exámenes sobre azar, sucesos, probabilidad, lluvia, comprensión, vocabulario, gramática, ortografía y expresión escrita.
START TRANSACTION;

SET @topic_lengua12 := (SELECT id FROM topics WHERE subject='Lengua' AND (title LIKE '12 %' OR title LIKE '12 ·%' OR title LIKE 'Tema 12%') ORDER BY id LIMIT 1);
INSERT INTO topics(subject, course, title, description, content)
SELECT 'Lengua','4º Primaria Andalucía','12 · ¡Que llueva, que llueva! El azar','Azar, lluvia, sucesos seguros, posibles e imposibles, probabilidad, comprensión lectora, vocabulario, ortografía y expresión escrita.', '# Tema 12 · ¡Que llueva, que llueva! El azar
Este tema trabaja el azar a partir de situaciones de la vida diaria, como mirar si lloverá, hacer planes, tirar un dado o sacar una carta. También sirve para practicar comprensión lectora, vocabulario, expresión escrita y razonamiento.

## 1. ¿Qué es el azar?
El azar aparece cuando no podemos saber con total seguridad qué va a ocurrir. Podemos imaginar lo que es más probable, pero no podemos asegurarlo al cien por cien.

Ejemplos:
- No sabemos con seguridad si mañana lloverá.
- No sabemos qué número saldrá al tirar un dado.
- No sabemos qué carta saldrá si sacamos una al azar.

## 2. Suceso seguro, posible e imposible
Un suceso es algo que puede ocurrir.

- Suceso seguro: ocurre siempre. Ejemplo: si hoy es lunes, mañana será martes.
- Suceso posible: puede ocurrir, pero no es seguro. Ejemplo: puede llover esta tarde.
- Suceso imposible: no puede ocurrir. Ejemplo: que al tirar un dado normal salga un 8.

## 3. Probabilidad de un suceso
La probabilidad indica si algo tiene muchas o pocas posibilidades de ocurrir.

- Muy probable: tiene muchas posibilidades. Ejemplo: que salga un número del 1 al 6 al tirar un dado.
- Poco probable: tiene pocas posibilidades. Ejemplo: que llueva en un día completamente despejado.
- Equiprobable: dos sucesos tienen las mismas posibilidades. Ejemplo: al lanzar una moneda puede salir cara o cruz.

## 4. El tiempo y la lluvia en la vida diaria
Muchas personas miran el tiempo para organizarse: elegir ropa, hacer planes, trabajar, viajar o cuidar cultivos y animales.

La lluvia puede molestar si tenemos planes al aire libre, pero es necesaria para la vida. El agua sirve para beber, lavarnos, limpiar, cultivar alimentos, llenar embalses y cuidar la naturaleza.

## 5. Vocabulario del tema
Palabras importantes:
- Azar: algo que ocurre sin que podamos controlarlo por completo.
- Suceso: hecho que puede ocurrir.
- Seguro: ocurre siempre.
- Posible: puede ocurrir.
- Imposible: no puede ocurrir.
- Probabilidad: posibilidad de que ocurra algo.
- Previsión: información que intenta anticipar lo que puede pasar.
- Embalse: lugar donde se almacena agua.
- Sequía: periodo largo con poca lluvia.

## 6. Comprensión lectora
Cuando leas un texto sobre la lluvia o el azar, fíjate en:
- El tema principal.
- La idea más importante de cada párrafo.
- Los datos concretos que aparecen.
- La opinión del autor o autora.
- Las preguntas que te hacen después.

## 7. Expresión escrita: cartel o póster
Un póster debe tener:
- Título claro y grande.
- Mensaje breve.
- Información ordenada.
- Dibujos o símbolos.
- Una conclusión o consejo.

Ejemplo de tema: ¿Qué pasaría si lloviera mucho menos?
Puedes explicar consecuencias como menos agua en los embalses, problemas en los cultivos, más sequía y necesidad de ahorrar agua.

## 8. Resumen para examen
El azar ocurre cuando no sabemos con seguridad qué pasará. Los sucesos pueden ser seguros, posibles o imposibles. La probabilidad nos ayuda a decir si algo tiene muchas o pocas posibilidades de ocurrir. La lluvia es necesaria para la vida, aunque a veces cambie nuestros planes. En este tema también practicamos leer textos, entender datos y escribir mensajes claros en forma de póster o explicación.
'
WHERE @topic_lengua12 IS NULL;
SET @topic_lengua12 := (SELECT id FROM topics WHERE subject='Lengua' AND (title LIKE '12 %' OR title LIKE '12 ·%' OR title LIKE 'Tema 12%') ORDER BY id LIMIT 1);
UPDATE topics SET
  title='12 · ¡Que llueva, que llueva! El azar',
  description='Azar, lluvia, sucesos seguros, posibles e imposibles, probabilidad, comprensión lectora, vocabulario, ortografía y expresión escrita.',
  content='# Tema 12 · ¡Que llueva, que llueva! El azar
Este tema trabaja el azar a partir de situaciones de la vida diaria, como mirar si lloverá, hacer planes, tirar un dado o sacar una carta. También sirve para practicar comprensión lectora, vocabulario, expresión escrita y razonamiento.

## 1. ¿Qué es el azar?
El azar aparece cuando no podemos saber con total seguridad qué va a ocurrir. Podemos imaginar lo que es más probable, pero no podemos asegurarlo al cien por cien.

Ejemplos:
- No sabemos con seguridad si mañana lloverá.
- No sabemos qué número saldrá al tirar un dado.
- No sabemos qué carta saldrá si sacamos una al azar.

## 2. Suceso seguro, posible e imposible
Un suceso es algo que puede ocurrir.

- Suceso seguro: ocurre siempre. Ejemplo: si hoy es lunes, mañana será martes.
- Suceso posible: puede ocurrir, pero no es seguro. Ejemplo: puede llover esta tarde.
- Suceso imposible: no puede ocurrir. Ejemplo: que al tirar un dado normal salga un 8.

## 3. Probabilidad de un suceso
La probabilidad indica si algo tiene muchas o pocas posibilidades de ocurrir.

- Muy probable: tiene muchas posibilidades. Ejemplo: que salga un número del 1 al 6 al tirar un dado.
- Poco probable: tiene pocas posibilidades. Ejemplo: que llueva en un día completamente despejado.
- Equiprobable: dos sucesos tienen las mismas posibilidades. Ejemplo: al lanzar una moneda puede salir cara o cruz.

## 4. El tiempo y la lluvia en la vida diaria
Muchas personas miran el tiempo para organizarse: elegir ropa, hacer planes, trabajar, viajar o cuidar cultivos y animales.

La lluvia puede molestar si tenemos planes al aire libre, pero es necesaria para la vida. El agua sirve para beber, lavarnos, limpiar, cultivar alimentos, llenar embalses y cuidar la naturaleza.

## 5. Vocabulario del tema
Palabras importantes:
- Azar: algo que ocurre sin que podamos controlarlo por completo.
- Suceso: hecho que puede ocurrir.
- Seguro: ocurre siempre.
- Posible: puede ocurrir.
- Imposible: no puede ocurrir.
- Probabilidad: posibilidad de que ocurra algo.
- Previsión: información que intenta anticipar lo que puede pasar.
- Embalse: lugar donde se almacena agua.
- Sequía: periodo largo con poca lluvia.

## 6. Comprensión lectora
Cuando leas un texto sobre la lluvia o el azar, fíjate en:
- El tema principal.
- La idea más importante de cada párrafo.
- Los datos concretos que aparecen.
- La opinión del autor o autora.
- Las preguntas que te hacen después.

## 7. Expresión escrita: cartel o póster
Un póster debe tener:
- Título claro y grande.
- Mensaje breve.
- Información ordenada.
- Dibujos o símbolos.
- Una conclusión o consejo.

Ejemplo de tema: ¿Qué pasaría si lloviera mucho menos?
Puedes explicar consecuencias como menos agua en los embalses, problemas en los cultivos, más sequía y necesidad de ahorrar agua.

## 8. Resumen para examen
El azar ocurre cuando no sabemos con seguridad qué pasará. Los sucesos pueden ser seguros, posibles o imposibles. La probabilidad nos ayuda a decir si algo tiene muchas o pocas posibilidades de ocurrir. La lluvia es necesaria para la vida, aunque a veces cambie nuestros planes. En este tema también practicamos leer textos, entender datos y escribir mensajes claros en forma de póster o explicación.
'
WHERE id=@topic_lengua12;

DELETE FROM questions WHERE topic_id=@topic_lengua12;

INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES
(@topic_lengua12,'multiple','¿Qué es el azar?','["Algo que no podemos predecir con total seguridad", "Una norma de ortografía", "Un tipo de sustantivo", "Una operación matemática exacta"]','Algo que no podemos predecir con total seguridad','El azar aparece cuando no sabemos con seguridad qué ocurrirá.',1),
(@topic_lengua12,'multiple','¿Cuál de estos ejemplos depende del azar?','["Sacar una bola de una bolsa sin mirar", "Leer el título de un libro", "Escribir tu nombre", "Contar de 1 a 10"]','Sacar una bola de una bolsa sin mirar','Si sacas una bola sin mirar no puedes saber con seguridad cuál saldrá.',1),
(@topic_lengua12,'multiple','Un suceso seguro es...','["Algo que ocurre siempre", "Algo que no puede ocurrir", "Algo que ocurre solo a veces", "Algo que no tiene sentido"]','Algo que ocurre siempre','Seguro significa que ocurre siempre en esa situación.',1),
(@topic_lengua12,'multiple','Un suceso posible es...','["Algo que puede ocurrir, pero no siempre", "Algo que nunca ocurre", "Algo que ocurre siempre", "Una palabra aguda"]','Algo que puede ocurrir, pero no siempre','Posible significa que puede pasar, aunque no sea seguro.',1),
(@topic_lengua12,'multiple','Un suceso imposible es...','["Algo que no puede ocurrir", "Algo que ocurre todos los días", "Algo muy probable", "Una noticia del tiempo"]','Algo que no puede ocurrir','Imposible significa que no puede pasar.',1),
(@topic_lengua12,'multiple','Al tirar un dado normal, que salga un número del 1 al 6 es un suceso...','["Seguro", "Imposible", "Poco probable", "No relacionado"]','Seguro','Un dado normal solo tiene los números del 1 al 6.',2),
(@topic_lengua12,'multiple','Al tirar un dado normal, que salga un 8 es un suceso...','["Imposible", "Seguro", "Muy probable", "Posible"]','Imposible','Un dado normal no tiene el número 8.',2),
(@topic_lengua12,'multiple','Que mañana llueva es un suceso...','["Posible", "Seguro siempre", "Imposible siempre", "Una oración interrogativa"]','Posible','Puede llover o no llover; no lo sabemos con total seguridad.',1),
(@topic_lengua12,'multiple','Si una bolsa tiene solo caramelos rojos, sacar un caramelo rojo es...','["Seguro", "Imposible", "Poco probable", "Inseguro"]','Seguro','Como todos son rojos, siempre sacarás uno rojo.',2),
(@topic_lengua12,'multiple','Si una bolsa tiene solo caramelos rojos, sacar un caramelo azul es...','["Imposible", "Seguro", "Muy probable", "Equiprobable"]','Imposible','No hay caramelos azules en la bolsa.',2),
(@topic_lengua12,'multiple','Si en una caja hay lápices rojos y azules, sacar uno rojo sin mirar es...','["Posible", "Imposible", "Seguro si hay azules", "Una falta de ortografía"]','Posible','Puede salir rojo, pero también podría salir azul.',2),
(@topic_lengua12,'text','Escribe un ejemplo de suceso seguro.',NULL,'respuesta libre','Debe ser algo que ocurra siempre en esa situación. Ejemplo: al tirar un dado normal saldrá un número del 1 al 6.',2),
(@topic_lengua12,'text','Escribe un ejemplo de suceso posible.',NULL,'respuesta libre','Debe ser algo que puede pasar, pero no es seguro. Ejemplo: que mañana llueva.',2),
(@topic_lengua12,'text','Escribe un ejemplo de suceso imposible.',NULL,'respuesta libre','Debe ser algo que no puede ocurrir. Ejemplo: que al tirar un dado normal salga un 9.',2),
(@topic_lengua12,'multiple','La probabilidad sirve para...','["Saber si algo tiene muchas o pocas posibilidades de ocurrir", "Ordenar palabras alfabéticamente", "Separar sílabas", "Buscar verbos en pasado"]','Saber si algo tiene muchas o pocas posibilidades de ocurrir','La probabilidad habla de las posibilidades de que ocurra un suceso.',1),
(@topic_lengua12,'multiple','Si algo es muy probable, significa que...','["Tiene muchas posibilidades de ocurrir", "No puede ocurrir", "Ocurre siempre sin excepción", "Es una palabra compuesta"]','Tiene muchas posibilidades de ocurrir','Muy probable no significa seguro, sino que tiene muchas posibilidades.',1),
(@topic_lengua12,'multiple','Si algo es poco probable, significa que...','["Tiene pocas posibilidades de ocurrir", "Ocurre siempre", "No se puede escribir", "Es obligatorio"]','Tiene pocas posibilidades de ocurrir','Poco probable significa que puede pasar, pero es difícil.',1),
(@topic_lengua12,'multiple','Al lanzar una moneda, cara y cruz son sucesos...','["Con las mismas posibilidades", "Imposibles", "Seguros los dos a la vez", "Sin relación con el azar"]','Con las mismas posibilidades','Una moneda tiene dos resultados principales con posibilidades parecidas.',2),
(@topic_lengua12,'multiple','Si en una bolsa hay 9 bolas rojas y 1 azul, es más probable sacar...','["Una roja", "Una azul", "Ninguna", "Una verde"]','Una roja','Hay más bolas rojas que azules.',2),
(@topic_lengua12,'multiple','Si en una bolsa hay 5 bolas rojas y 5 azules, sacar roja o azul es...','["Igual de probable", "Imposible", "Seguro solo para la roja", "Seguro solo para la azul"]','Igual de probable','Hay el mismo número de bolas de cada color.',2),
(@topic_lengua12,'multiple','Si el cielo está muy nublado y el parte anuncia lluvia, llover es...','["Más probable", "Imposible", "Seguro matemáticamente", "Una palabra llana"]','Más probable','Las nubes y la previsión aumentan la posibilidad, aunque no lo hacen totalmente seguro.',2),
(@topic_lengua12,'multiple','¿Cuál es un ejemplo de algo poco probable?','["Que nieve en agosto en una playa andaluza", "Que el sol salga mañana", "Que un dado normal saque un 3", "Que llueva algún día del año"]','Que nieve en agosto en una playa andaluza','Puede imaginarse, pero en una playa andaluza en agosto sería muy poco probable.',2),
(@topic_lengua12,'text','Explica con tus palabras qué significa probabilidad.',NULL,'respuesta libre','Debe decir que es la posibilidad de que ocurra algo.',2),
(@topic_lengua12,'text','Inventa una situación en la que un suceso sea muy probable.',NULL,'respuesta libre','Debe incluir una situación con muchas posibilidades de ocurrir.',2),
(@topic_lengua12,'text','Inventa una situación en la que un suceso sea poco probable.',NULL,'respuesta libre','Debe incluir una situación posible pero difícil.',2),
(@topic_lengua12,'multiple','¿Por qué muchas personas miran la previsión del tiempo?','["Para organizar sus planes, ropa o viajes", "Para aprender las tablas", "Para saber su nombre", "Para escribir sin faltas"]','Para organizar sus planes, ropa o viajes','La previsión ayuda a decidir qué hacer y cómo prepararse.',1),
(@topic_lengua12,'multiple','¿Por qué la lluvia es necesaria?','["Porque aporta agua para vivir, cultivar y cuidar la naturaleza", "Porque siempre estropea los planes", "Porque solo sirve para mojar la ropa", "Porque no tiene ninguna utilidad"]','Porque aporta agua para vivir, cultivar y cuidar la naturaleza','El agua es necesaria para personas, animales, plantas y cultivos.',1),
(@topic_lengua12,'multiple','¿Qué puede ocurrir si llueve mucho menos durante mucho tiempo?','["Sequía y menos agua disponible", "Más agua en todos los embalses", "Que desaparezcan las nubes", "Que siempre haga frío"]','Sequía y menos agua disponible','Si llueve poco durante mucho tiempo, puede haber sequía y problemas de agua.',2),
(@topic_lengua12,'multiple','Un embalse sirve para...','["Almacenar agua", "Guardar libros", "Medir palabras", "Lanzar dados"]','Almacenar agua','Los embalses acumulan agua para poder usarla cuando hace falta.',1),
(@topic_lengua12,'multiple','La palabra sequía significa...','["Periodo largo con poca lluvia", "Lluvia muy fuerte de un minuto", "Día con nieve", "Río muy caudaloso"]','Periodo largo con poca lluvia','La sequía aparece cuando falta lluvia durante bastante tiempo.',1),
(@topic_lengua12,'multiple','¿Cuál es una forma de ahorrar agua?','["Cerrar el grifo mientras nos cepillamos los dientes", "Dejar el grifo abierto siempre", "Llenar la bañera cada día", "Regar al mediodía con mucho sol"]','Cerrar el grifo mientras nos cepillamos los dientes','Cerrar el grifo evita gastar agua innecesariamente.',1),
(@topic_lengua12,'multiple','Si una familia va a hacer una excursión y mira si lloverá, está usando...','["La previsión del tiempo", "Una receta", "Un diccionario de antónimos", "Un cuento fantástico"]','La previsión del tiempo','La previsión informa sobre el tiempo que puede hacer.',1),
(@topic_lengua12,'text','Explica dos razones por las que la lluvia es importante.',NULL,'respuesta libre','Puede mencionar beber, cultivos, animales, embalses, plantas, limpieza o naturaleza.',2),
(@topic_lengua12,'text','Escribe dos consejos para ahorrar agua en casa.',NULL,'respuesta libre','Ejemplos: cerrar el grifo, ducharse en vez de bañarse, arreglar fugas o usar bien la lavadora.',2),
(@topic_lengua12,'text','¿Por qué puede molestar la lluvia aunque sea necesaria?',NULL,'respuesta libre','Debe explicar que puede cambiar planes al aire libre, pero sigue siendo importante para la vida.',2),
(@topic_lengua12,'multiple','¿Qué palabra significa “información que intenta anticipar lo que puede pasar”?','["Previsión", "Azar", "Sequía", "Cartel"]','Previsión','La previsión intenta decir lo que puede ocurrir, por ejemplo con el tiempo.',2),
(@topic_lengua12,'multiple','¿Cuál es un sinónimo de “probable”?','["Posible", "Imposible", "Antiguo", "Ruidoso"]','Posible','Probable se relaciona con posible, aunque probable suele indicar que hay bastantes posibilidades.',1),
(@topic_lengua12,'multiple','¿Cuál es un antónimo de “posible”?','["Imposible", "Seguro", "Probable", "Lluvioso"]','Imposible','Posible e imposible expresan ideas contrarias.',1),
(@topic_lengua12,'multiple','¿Cuál de estas palabras pertenece al campo semántico del tiempo atmosférico?','["Lluvia", "Cuaderno", "Silla", "Zapatilla"]','Lluvia','Lluvia se relaciona con el tiempo atmosférico.',1),
(@topic_lengua12,'multiple','¿Cuál de estas palabras pertenece al campo semántico del agua?','["Embalse", "Lápiz", "Zapato", "Teléfono"]','Embalse','Embalse se relaciona con el agua porque la almacena.',1),
(@topic_lengua12,'multiple','La palabra “lluvioso” significa...','["Que tiene lluvia o se relaciona con la lluvia", "Que tiene sol intenso", "Que no puede ocurrir", "Que no tiene agua"]','Que tiene lluvia o se relaciona con la lluvia','Lluvioso describe un día, lugar o tiempo con lluvia.',1),
(@topic_lengua12,'multiple','La expresión “estar pendiente del tiempo” significa...','["Estar atento a la previsión o al clima", "Mirar un reloj roto", "Esperar a que pase una hora exacta", "No hacer caso a nada"]','Estar atento a la previsión o al clima','En este contexto, el tiempo se refiere al clima.',2),
(@topic_lengua12,'text','Escribe tres palabras relacionadas con la lluvia.',NULL,'respuesta libre','Ejemplos: nube, paraguas, gota, tormenta, chubasco, agua, charco.',1),
(@topic_lengua12,'text','Escribe una frase con la palabra “probabilidad”.',NULL,'respuesta libre','Debe usar la palabra probabilidad de forma correcta.',2),
(@topic_lengua12,'text','Explica la diferencia entre “seguro” y “probable”.',NULL,'respuesta libre','Seguro ocurre siempre; probable tiene muchas posibilidades, pero no es totalmente seguro.',3),
(@topic_lengua12,'multiple','Lee la idea: “La lluvia puede cambiar nuestros planes, pero es necesaria para vivir”. ¿Cuál es la idea principal?','["La lluvia es necesaria aunque a veces moleste", "La lluvia nunca sirve", "Los planes no importan", "Siempre llueve igual"]','La lluvia es necesaria aunque a veces moleste','La frase contrasta una molestia con la importancia de la lluvia.',2),
(@topic_lengua12,'multiple','En un texto, el dato “se cortó el agua por falta de reservas” sirve para...','["Mostrar una consecuencia de que haya poca agua", "Decorar la página", "Explicar una multiplicación", "Contar un chiste"]','Mostrar una consecuencia de que haya poca agua','Un dato ayuda a entender mejor el problema del agua.',2),
(@topic_lengua12,'multiple','¿Qué pregunta sería adecuada después de leer un texto sobre lluvia y sequía?','["¿Qué pasaría si lloviera mucho menos?", "¿Cuántas patas tiene una silla?", "¿Cuál es el plural de lápiz?", "¿Qué color tiene una resta?"]','¿Qué pasaría si lloviera mucho menos?','Esa pregunta está relacionada con las consecuencias de la falta de lluvia.',1),
(@topic_lengua12,'multiple','Si un texto dice que el agua es esencial, significa que...','["Es muy necesaria", "Es decorativa", "No sirve para nada", "Solo se usa en vacaciones"]','Es muy necesaria','Esencial quiere decir fundamental o muy importante.',1),
(@topic_lengua12,'multiple','¿Qué detalle ayuda a comprender por qué miramos el tiempo?','["Queremos saber cómo vestirnos o planear el día", "Queremos inventar un número", "Queremos borrar un cuaderno", "Queremos construir una mesa"]','Queremos saber cómo vestirnos o planear el día','La previsión del tiempo ayuda a prepararnos.',1),
(@topic_lengua12,'text','Resume en dos líneas por qué la lluvia es importante.',NULL,'respuesta libre','Debe mencionar que aporta agua para vivir, cultivos, animales, plantas o embalses.',2),
(@topic_lengua12,'text','Escribe una pregunta que harías sobre un texto titulado “¿Lloverá mañana?”.',NULL,'respuesta libre','Debe ser una pregunta relacionada con la previsión, la lluvia o el tiempo.',2),
(@topic_lengua12,'text','Escribe una opinión razonada sobre un día de lluvia.',NULL,'respuesta libre','Debe expresar una opinión y dar una razón.',2),
(@topic_lengua12,'multiple','¿Qué tipo de oración es “¿Lloverá mañana?”?','["Interrogativa", "Exclamativa", "Enunciativa afirmativa", "Imperativa"]','Interrogativa','Pregunta algo y lleva signos de interrogación.',1),
(@topic_lengua12,'multiple','¿Qué tipo de oración es “¡Que llueva, que llueva!”?','["Exclamativa", "Interrogativa", "Enunciativa negativa", "Dubitativa sin emoción"]','Exclamativa','Expresa emoción o intensidad y lleva signos de exclamación.',1),
(@topic_lengua12,'multiple','¿Qué tipo de oración es “La lluvia es necesaria”?','["Enunciativa afirmativa", "Interrogativa", "Exclamativa", "Imperativa"]','Enunciativa afirmativa','Afirma una información.',1),
(@topic_lengua12,'multiple','¿Qué tipo de oración es “No lloverá esta tarde”?','["Enunciativa negativa", "Interrogativa", "Exclamativa", "Imperativa"]','Enunciativa negativa','Niega una información.',1),
(@topic_lengua12,'multiple','En la oración “Muchas personas consultan el tiempo”, el verbo es...','["consultan", "personas", "tiempo", "muchas"]','consultan','El verbo indica la acción que realiza el sujeto.',1),
(@topic_lengua12,'multiple','En “La lluvia llena los embalses”, el sujeto es...','["La lluvia", "llena", "los embalses", "embalses"]','La lluvia','El sujeto indica quién realiza la acción o de qué se habla.',2),
(@topic_lengua12,'multiple','En “El agua es esencial”, el adjetivo es...','["esencial", "agua", "el", "es"]','esencial','Esencial describe cómo es el agua.',2),
(@topic_lengua12,'multiple','¿Cuál es el plural de “probabilidad”?','["probabilidades", "probabilidads", "probabilidados", "probableces"]','probabilidades','Las palabras acabadas en -dad suelen formar el plural con -des: probabilidades.',2),
(@topic_lengua12,'multiple','¿Cuál es el plural de “suceso”?','["sucesos", "sucesoes", "sucesas", "susesos"]','sucesos','Suceso forma el plural añadiendo -s.',1),
(@topic_lengua12,'text','Escribe una oración interrogativa sobre la lluvia.',NULL,'respuesta libre','Debe ser una pregunta y usar signos de interrogación.',2),
(@topic_lengua12,'text','Escribe una oración exclamativa sobre el tiempo.',NULL,'respuesta libre','Debe expresar emoción y usar signos de exclamación.',2),
(@topic_lengua12,'text','Escribe una oración afirmativa usando la palabra “azar”.',NULL,'respuesta libre','Debe afirmar algo e incluir azar.',2),
(@topic_lengua12,'multiple','¿Qué frase está bien puntuada?','["¿Lloverá mañana?", "Lloverá mañana?", "¿Lloverá mañana", "Lloverá mañana¿"]','¿Lloverá mañana?','Las preguntas llevan signo de apertura y de cierre.',1),
(@topic_lengua12,'multiple','¿Cuál está bien escrito?','["lluvia", "yuvia", "llubia", "yubia"]','lluvia','Lluvia se escribe con ll y v.',1),
(@topic_lengua12,'multiple','¿Cuál de estas palabras lleva tilde?','["probabilidad", "lluvia", "previsión", "posible"]','previsión','Previsión es aguda terminada en n y lleva tilde.',2),
(@topic_lengua12,'multiple','¿Cuál de estas palabras no lleva tilde?','["lluvia", "también", "previsión", "qué"]','lluvia','Lluvia no lleva tilde.',1),
(@topic_lengua12,'text','Copia correctamente esta pregunta: llovera mañana',NULL,'¿lloverá mañana?|¿Lloverá mañana?','Debe escribirse con signos de interrogación y tilde: ¿Lloverá mañana?',2),
(@topic_lengua12,'text','Escribe correctamente: que tormenta tan fuerte',NULL,'¡qué tormenta tan fuerte!|¡Qué tormenta tan fuerte!','Debe llevar signos de exclamación y tilde en qué.',2),
(@topic_lengua12,'multiple','Un póster debe tener...','["Título claro, mensaje breve e información ordenada", "Solo letras pequeñas", "Muchas frases sin relación", "Ningún dibujo ni título"]','Título claro, mensaje breve e información ordenada','Un póster comunica mejor si está claro y ordenado.',1),
(@topic_lengua12,'multiple','Para un póster sobre ahorrar agua, el mejor título sería...','["Cuidemos cada gota", "Mi videojuego favorito", "Las tablas de multiplicar", "El recreo de ayer"]','Cuidemos cada gota','Ese título se relaciona con el tema del agua.',1),
(@topic_lengua12,'multiple','¿Qué consejo encaja en un póster sobre la sequía?','["Cierra el grifo cuando no lo uses", "Abre todos los grifos", "Tira agua al suelo", "Riega a mediodía todos los días"]','Cierra el grifo cuando no lo uses','Es un consejo útil para ahorrar agua.',1),
(@topic_lengua12,'multiple','¿Qué frase es más adecuada para convencer a alguien de ahorrar agua?','["Cada gota cuenta: usa solo la que necesites", "El agua no importa nada", "Gasta toda el agua que puedas", "No cierres nunca el grifo"]','Cada gota cuenta: usa solo la que necesites','Es clara, breve y transmite un mensaje responsable.',2),
(@topic_lengua12,'text','Escribe un título para un póster sobre qué pasaría si lloviera menos.',NULL,'respuesta libre','Debe ser un título breve y relacionado con lluvia, sequía o ahorro de agua.',2),
(@topic_lengua12,'text','Escribe dos consecuencias de que llueva menos de lo normal.',NULL,'respuesta libre','Puede mencionar sequía, menos agua en embalses, problemas en cultivos, más calor o necesidad de ahorrar.',2),
(@topic_lengua12,'text','Escribe un mensaje breve para animar a ahorrar agua.',NULL,'respuesta libre','Debe ser claro, breve y relacionado con el ahorro de agua.',2),
(@topic_lengua12,'text','Diseña con palabras un mini póster: título, dibujo que pondrías y consejo.',NULL,'respuesta libre','Debe incluir título, idea visual y consejo.',3),
(@topic_lengua12,'multiple','¿Cuál de estas parejas está bien relacionada?','["Seguro → ocurre siempre", "Imposible → ocurre siempre", "Posible → nunca ocurre", "Probabilidad → no tiene posibilidades"]','Seguro → ocurre siempre','Seguro significa que ocurre siempre.',2),
(@topic_lengua12,'multiple','Si un dado normal tiene 6 caras, sacar un número par es...','["Posible", "Imposible", "Seguro", "Una oración exclamativa"]','Posible','Puede salir 2, 4 o 6, pero también puede salir impar.',2),
(@topic_lengua12,'multiple','Si un dado normal tiene 6 caras, sacar un número mayor que 0 es...','["Seguro", "Imposible", "Poco probable", "Una opinión"]','Seguro','Todos los números de un dado normal son mayores que 0.',2),
(@topic_lengua12,'multiple','Si un dado normal tiene 6 caras, sacar un número menor que 1 es...','["Imposible", "Seguro", "Muy probable", "Igual de probable que sacar 3"]','Imposible','El número más pequeño de un dado normal es 1.',2),
(@topic_lengua12,'multiple','Si una ruleta tiene 3 partes verdes y 1 parte roja, es más probable que salga...','["Verde", "Roja", "Azul", "Ningún color"]','Verde','Hay más partes verdes que rojas.',2),
(@topic_lengua12,'multiple','Si una ruleta tiene 2 partes verdes y 2 rojas, verde y rojo son...','["Igual de probables", "Imposibles", "Seguros a la vez", "Poco probables siempre"]','Igual de probables','Hay el mismo número de partes de cada color.',2),
(@topic_lengua12,'multiple','La mejor explicación de “azar” es...','["No se puede saber con seguridad el resultado antes de que ocurra", "Siempre sabemos exactamente qué pasará", "Es una regla para poner tildes", "Es una clase de determinante"]','No se puede saber con seguridad el resultado antes de que ocurra','El azar tiene incertidumbre: no conocemos el resultado con seguridad.',2),
(@topic_lengua12,'multiple','¿Qué respuesta demuestra que entiendes el tema?','["Un suceso posible puede ocurrir, pero no es seguro", "Un suceso imposible ocurre siempre", "La lluvia no sirve para nada", "La probabilidad es una falta de ortografía"]','Un suceso posible puede ocurrir, pero no es seguro','Esa frase define correctamente un suceso posible.',2),
(@topic_lengua12,'text','Clasifica: “que salga un 7 en un dado normal”. ¿Seguro, posible o imposible? Explica por qué.',NULL,'respuesta libre','Debe decir imposible porque un dado normal no tiene 7.',3),
(@topic_lengua12,'text','Clasifica: “que llueva algún día este mes”. ¿Seguro, posible o imposible? Explica.',NULL,'respuesta libre','Debe explicar que es posible, aunque depende del lugar y del tiempo.',3),
(@topic_lengua12,'text','Clasifica: “que al lanzar una moneda salga cara”. ¿Seguro, posible o imposible? Explica.',NULL,'respuesta libre','Debe decir posible porque puede salir cara o cruz.',3),
(@topic_lengua12,'text','Haz un resumen de 4 líneas del tema del azar y la lluvia.',NULL,'respuesta libre','Debe mencionar azar, sucesos seguros/posibles/imposibles, probabilidad e importancia de la lluvia.',3),
(@topic_lengua12,'text','Explica qué relación puede haber entre mirar el tiempo y el azar.',NULL,'respuesta libre','Debe explicar que la previsión ayuda, pero no siempre sabemos con total seguridad qué pasará.',3),
(@topic_lengua12,'text','Escribe una pregunta tipo examen sobre este tema y respóndela.',NULL,'respuesta libre','Debe crear una pregunta relacionada con azar, probabilidad, lluvia o sucesos, y dar respuesta coherente.',3),
(@topic_lengua12,'multiple','¿Cuál es el significado de “esencial”?','["Muy necesario", "Muy pequeño", "Muy antiguo", "Muy divertido"]','Muy necesario','Esencial significa fundamental o necesario.',2),
(@topic_lengua12,'multiple','¿Qué palabra es una acción?','["llover", "lluvia", "nube", "azar"]','llover','Llover es un verbo; indica una acción o fenómeno.',2),
(@topic_lengua12,'multiple','¿Qué palabra es un sustantivo?','["lluvia", "llover", "lluvioso", "lloviendo"]','lluvia','Lluvia es un sustantivo porque nombra un fenómeno.',2),
(@topic_lengua12,'multiple','¿Qué palabra es un adjetivo?','["lluvioso", "lluvia", "llover", "lloviznar"]','lluvioso','Lluvioso describe cómo es un día o un tiempo.',2),
(@topic_lengua12,'multiple','¿Cuál de estas opciones es una familia de palabras?','["lluvia, llover, lluvioso", "agua, mesa, zapato", "dado, libro, lluvia", "nube, silla, azar"]','lluvia, llover, lluvioso','Pertenecen a la misma familia de palabras.',2),
(@topic_lengua12,'multiple','¿Qué oración tiene sentido?','["La lluvia llena los embalses.", "La lluvia come zapatos.", "El azar escribe paraguas.", "La probabilidad duerme en la mesa."]','La lluvia llena los embalses.','Es una oración coherente y relacionada con el tema.',2),
(@topic_lengua12,'multiple','¿Qué conector expresa causa?','["porque", "pero", "aunque", "también"]','porque','Porque introduce una razón o causa.',2),
(@topic_lengua12,'multiple','¿Qué conector expresa contraste?','["pero", "porque", "cuando", "donde"]','pero','Pero sirve para oponer o contrastar ideas.',2),
(@topic_lengua12,'multiple','¿Qué frase usa bien el conector “porque”?','["Miramos el tiempo porque queremos saber si lloverá.", "Miramos el tiempo porque pero lloverá.", "Porque miramos el tiempo aunque.", "Tiempo porque lluvia sin frase."]','Miramos el tiempo porque queremos saber si lloverá.','La frase explica la causa de mirar el tiempo.',2),
(@topic_lengua12,'multiple','¿Qué frase usa bien “aunque”?','["Aunque llueva, la lluvia es necesaria.", "Aunque porque lloverá.", "La lluvia aunque porque agua.", "Aunque imposible seguro azar."]','Aunque llueva, la lluvia es necesaria.','Aunque introduce una dificultad o contraste.',2);

COMMIT;


-- V5.9 · Temas nuevos, archivado de temas y refuerzo del plan diario
CREATE TABLE IF NOT EXISTS user_topic_status (
  user_id INT NOT NULL,
  topic_id INT NOT NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,
  archived_at DATETIME NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, topic_id),
  INDEX idx_topic_status_archived (user_id, is_archived),
  CONSTRAINT fk_topic_status_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_topic_status_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SET @topic_mates12 := (SELECT id FROM topics WHERE title IN ('Tema 12 · El azar','Tema 12 · ¿Qué llueva, que llueva?') ORDER BY FIELD(subject,'Matemáticas','Lengua') LIMIT 1);
UPDATE topics SET subject='Matemáticas', course='4º Primaria Andalucía', title='Tema 12 · El azar', description='Experiencias de azar, sucesos seguros, posibles e imposibles y probabilidad básica.', content='# Tema 12 · El azar
En este tema aprendemos a reconocer situaciones de azar y a distinguir sucesos seguros, posibles e imposibles.

# Ideas clave
- En una experiencia de azar conocemos los resultados que pueden salir, pero no sabemos cuál ocurrirá.
- Ejemplos: lanzar una moneda, tirar un dado, girar una ruleta o jugar a piedra, papel o tijera.
- Un suceso seguro ocurre siempre.
- Un suceso posible ocurre a veces.
- Un suceso imposible no ocurre nunca.

# Experiencias de azar
- Lanzar una moneda: resultados posibles, cara o cruz.
- Lanzar un dado: resultados posibles, 1, 2, 3, 4, 5 o 6.
- Jugar al bingo: pueden salir números del 1 al 90.

# Suceso seguro, posible e imposible
- Al lanzar un dado, sacar un número menor que 7 es seguro.
- Sacar un 8 con un solo dado es imposible.
- Sacar un 5 con un dado es posible.
- Si llueve y no llevas paraguas, es seguro que te mojes.
- Si no llueve, es imposible mojarse con la lluvia.

# Probabilidad de un suceso
- Si en una urna hay muchas bolas de un color y pocas de otro, es más probable sacar el color que más se repite.
- Con dos dados, la suma nunca puede ser mayor que 12.
- En pares o nones pueden salir resultados pares o impares.

# Cómo pensar las respuestas
- Fíjate en todos los resultados posibles.
- Decide si el suceso puede ocurrir siempre, a veces o nunca.
- Da ejemplos cuando te los pidan.

# Para practicar
- Haz deberes para repasar ideas clave.
- Haz un reto con Emilia para practicar rápido.
- Termina con el examen para comprobar si distingues bien seguro, posible e imposible.' WHERE id=@topic_mates12;
INSERT INTO topics(subject, course, title, description, content) SELECT 'Matemáticas','4º Primaria Andalucía','Tema 12 · El azar','Experiencias de azar, sucesos seguros, posibles e imposibles y probabilidad básica.','# Tema 12 · El azar
En este tema aprendemos a reconocer situaciones de azar y a distinguir sucesos seguros, posibles e imposibles.

# Ideas clave
- En una experiencia de azar conocemos los resultados que pueden salir, pero no sabemos cuál ocurrirá.
- Ejemplos: lanzar una moneda, tirar un dado, girar una ruleta o jugar a piedra, papel o tijera.
- Un suceso seguro ocurre siempre.
- Un suceso posible ocurre a veces.
- Un suceso imposible no ocurre nunca.

# Experiencias de azar
- Lanzar una moneda: resultados posibles, cara o cruz.
- Lanzar un dado: resultados posibles, 1, 2, 3, 4, 5 o 6.
- Jugar al bingo: pueden salir números del 1 al 90.

# Suceso seguro, posible e imposible
- Al lanzar un dado, sacar un número menor que 7 es seguro.
- Sacar un 8 con un solo dado es imposible.
- Sacar un 5 con un dado es posible.
- Si llueve y no llevas paraguas, es seguro que te mojes.
- Si no llueve, es imposible mojarse con la lluvia.

# Probabilidad de un suceso
- Si en una urna hay muchas bolas de un color y pocas de otro, es más probable sacar el color que más se repite.
- Con dos dados, la suma nunca puede ser mayor que 12.
- En pares o nones pueden salir resultados pares o impares.

# Cómo pensar las respuestas
- Fíjate en todos los resultados posibles.
- Decide si el suceso puede ocurrir siempre, a veces o nunca.
- Da ejemplos cuando te los pidan.

# Para practicar
- Haz deberes para repasar ideas clave.
- Haz un reto con Emilia para practicar rápido.
- Termina con el examen para comprobar si distingues bien seguro, posible e imposible.' FROM DUAL WHERE @topic_mates12 IS NULL;
SET @topic_mates12 := COALESCE(@topic_mates12, (SELECT id FROM topics WHERE subject='Matemáticas' AND title='Tema 12 · El azar' LIMIT 1));
DELETE FROM questions WHERE topic_id=@topic_mates12;
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','¿Qué es una experiencia de azar?','["Una situación en la que conocemos los resultados posibles pero no sabemos cuál saldrá", "Una cuenta exacta con una sola respuesta", "Un dibujo artístico", "Una historia inventada"]','Una situación en la que conocemos los resultados posibles pero no sabemos cuál saldrá','En el azar conocemos qué puede pasar, pero no sabemos el resultado exacto antes de hacerlo.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','¿Cuál de estas situaciones es de azar?','["Tirar un dado y adivinar el número", "Escribir tu nombre", "Contar hasta diez", "Abrir un libro ya abierto por la misma página"]','Tirar un dado y adivinar el número','Tirar un dado es una experiencia de azar porque puede salir cualquiera de sus seis caras.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Al lanzar una moneda, ¿qué resultados pueden salir?','["Cara o cruz", "1 o 2", "Rojo o azul", "Par o impar"]','Cara o cruz','En una moneda los resultados posibles son cara o cruz.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Al lanzar un dado normal, ¿qué resultados pueden salir?','["1, 2, 3, 4, 5 y 6", "0, 1 y 2", "Solo números pares", "Del 1 al 10"]','1, 2, 3, 4, 5 y 6','Un dado normal tiene seis caras numeradas del 1 al 6.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Sacar un 8 al tirar un solo dado es un suceso…','["imposible", "seguro", "posible", "probable seguro"]','imposible','Con un solo dado normal solo pueden salir números del 1 al 6, nunca un 8.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Sacar un número menor que 7 al tirar un dado es un suceso…','["seguro", "imposible", "posible", "raro"]','seguro','Todos los números de un dado normal son menores que 7.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Sacar un 5 al lanzar un dado es un suceso…','["posible", "seguro", "imposible", "fijo"]','posible','Puede salir 5, pero no siempre sale.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Si llueve y no llevas paraguas, mojarte es un suceso…','["seguro", "imposible", "posible", "ninguno"]','seguro','Si está lloviendo y no te proteges, lo normal es mojarse.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Si no llueve, mojarte con la lluvia es…','["imposible", "seguro", "posible", "azaroso"]','imposible','Si no hay lluvia, no puedes mojarte con la lluvia.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','En una urna hay muchas bolas amarillas y una sola azul. ¿Qué color conviene elegir si quieres acertar?','["Amarillo", "Azul", "Da igual", "Rojo"]','Amarillo','Es más probable sacar el color que aparece más veces.',2);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Con dos dados, que la suma sea mayor que 12 es…','["imposible", "seguro", "posible", "fácil"]','imposible','La suma mayor con dos dados es 12, así que más de 12 no puede salir.',2);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Con dos dados, que salgan dos números pares es un suceso…','["posible", "imposible", "seguro", "único"]','posible','Puede pasar si salen, por ejemplo, 2 y 4, pero no siempre ocurre.',2);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','En “pares o nones”, ¿qué tipos de resultados pueden salir?','["Pares o impares", "Solo pares", "Solo impares", "Cara o cruz"]','Pares o impares','La suma de dedos puede ser par o impar.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'text','Escribe una palabra: ¿cómo se llama el suceso que ocurre siempre?',NULL,'seguro','El suceso que ocurre siempre se llama seguro.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'text','Escribe una palabra: ¿cómo se llama el suceso que no ocurre nunca?',NULL,'imposible','El suceso que no ocurre nunca se llama imposible.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'text','Escribe una palabra: ¿cómo se llama el suceso que ocurre a veces?',NULL,'posible','El suceso que ocurre algunas veces se llama posible.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','¿Cuál de estas afirmaciones es correcta?','["En el bingo pueden salir números del 1 al 90", "En el bingo solo sale el número 10", "En el bingo no hay números", "En el bingo salen letras y colores"]','En el bingo pueden salir números del 1 al 90','En el bingo los números posibles van del 1 al 90.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_mates12,'multiple','Si una bolsa tiene 4 bolas verdes y 4 amarillas, sacar una verde es…','["posible", "seguro", "imposible", "solo si miras"]','posible','Hay bolas verdes, así que puede salir una, pero no está garantizado.',2);
SET @topic_lengua12 := (SELECT id FROM topics WHERE subject='Lengua' AND title='Tema 12 · Valoramos las tradiciones' LIMIT 1);
INSERT INTO topics(subject, course, title, description, content) SELECT 'Lengua','4º Primaria Andalucía','Tema 12 · Valoramos las tradiciones','Tradiciones, textos periodísticos, flamenco, lenguas de España y ortografía con ll/y.','# Tema 12 · Valoramos las tradiciones
Aprender este tema ayuda a conocer mejor nuestras tradiciones y a usar distintos textos periodísticos.

# Lo más importante
- Los textos periodísticos informan u opinan sobre temas de actualidad.
- La entrevista, la noticia y el reportaje son textos periodísticos.
- El flamenco es una manifestación cultural andaluza y fue declarado Patrimonio Inmaterial de la Humanidad por la UNESCO en 2010.
- Las tres manifestaciones del flamenco son el cante, el toque y el baile.
- En España, además del castellano, hay lenguas cooficiales: gallego, catalán, valenciano y euskara.

# La entrevista
## Partes de una entrevista
- Saludo y presentación de la persona entrevistada.
- Conjunto de preguntas y respuestas.
- Despedida del personaje.

# El reportaje y la noticia
## La noticia
- Tiene titular, entradilla y cuerpo.
- Responde a qué ha ocurrido, cuándo, dónde, cómo, por qué y a quién.
## El reportaje
- Desarrolla un tema con más detalle.
- Suele incluir información, investigación, imágenes o ejemplos.

# El flamenco
- Es un arte universal y una expresión cultural de Andalucía.
- Sus manifestaciones son: cante, toque y baile.
- Barrios de origen destacados: Cádiz (La Viña y La Santa María), Jerez (Santiago y San Miguel), Granada (Sacromonte), Sevilla (Triana y Macarena) y Málaga (La Caleta y El Perchel).

# Vocabulario sobre la danza
- Danza folclórica: danza tradicional.
- Danza de salón: baile de pareja.
- Danza clásica: ballet.
- Ritmos rápidos: endemoniado, acelerado, desenfrenado, galopante, infernal, veloz.

# Siglas y abreviaturas
- BNE: Ballet Nacional de España.
- CNE: Compañía Nacional de España.
- IAF: Instituto Andaluz del Flamenco.
- Abreviaturas útiles: avda., atte., D., DNI, núm., Dir.ª, ONG.

# Las lenguas de España y el andaluz
- En Galicia se habla gallego.
- En Cataluña e Illes Balears se habla catalán.
- En la Comunidad Valenciana el catalán recibe el nombre de valenciano.
- En el País Vasco y algunas zonas de Navarra se habla euskara.
- Rasgos del andaluz: yeísmo, seseo, ceceo, aspiración de la s final, pérdida de algunas consonantes finales y pérdida de la d entre vocales.

# Ortografía con ll e y
- Se escriben con ll muchas palabras terminadas en -illo, -illa, -ello, -ella, -alle y -elle.
- Ejemplos: anillo, aquella, chiquilla, valle, muelle.
- Los verbos terminados en -illar, -ullar y -ullir suelen escribirse con ll.

# Para estudiar mejor
- Primero lee el resumen.
- Después haz deberes para practicar.
- Termina con el examen para comprobar si lo has entendido.' FROM DUAL WHERE @topic_lengua12 IS NULL;
UPDATE topics SET subject='Lengua', course='4º Primaria Andalucía', title='Tema 12 · Valoramos las tradiciones', description='Tradiciones, textos periodísticos, flamenco, lenguas de España y ortografía con ll/y.', content='# Tema 12 · Valoramos las tradiciones
Aprender este tema ayuda a conocer mejor nuestras tradiciones y a usar distintos textos periodísticos.

# Lo más importante
- Los textos periodísticos informan u opinan sobre temas de actualidad.
- La entrevista, la noticia y el reportaje son textos periodísticos.
- El flamenco es una manifestación cultural andaluza y fue declarado Patrimonio Inmaterial de la Humanidad por la UNESCO en 2010.
- Las tres manifestaciones del flamenco son el cante, el toque y el baile.
- En España, además del castellano, hay lenguas cooficiales: gallego, catalán, valenciano y euskara.

# La entrevista
## Partes de una entrevista
- Saludo y presentación de la persona entrevistada.
- Conjunto de preguntas y respuestas.
- Despedida del personaje.

# El reportaje y la noticia
## La noticia
- Tiene titular, entradilla y cuerpo.
- Responde a qué ha ocurrido, cuándo, dónde, cómo, por qué y a quién.
## El reportaje
- Desarrolla un tema con más detalle.
- Suele incluir información, investigación, imágenes o ejemplos.

# El flamenco
- Es un arte universal y una expresión cultural de Andalucía.
- Sus manifestaciones son: cante, toque y baile.
- Barrios de origen destacados: Cádiz (La Viña y La Santa María), Jerez (Santiago y San Miguel), Granada (Sacromonte), Sevilla (Triana y Macarena) y Málaga (La Caleta y El Perchel).

# Vocabulario sobre la danza
- Danza folclórica: danza tradicional.
- Danza de salón: baile de pareja.
- Danza clásica: ballet.
- Ritmos rápidos: endemoniado, acelerado, desenfrenado, galopante, infernal, veloz.

# Siglas y abreviaturas
- BNE: Ballet Nacional de España.
- CNE: Compañía Nacional de España.
- IAF: Instituto Andaluz del Flamenco.
- Abreviaturas útiles: avda., atte., D., DNI, núm., Dir.ª, ONG.

# Las lenguas de España y el andaluz
- En Galicia se habla gallego.
- En Cataluña e Illes Balears se habla catalán.
- En la Comunidad Valenciana el catalán recibe el nombre de valenciano.
- En el País Vasco y algunas zonas de Navarra se habla euskara.
- Rasgos del andaluz: yeísmo, seseo, ceceo, aspiración de la s final, pérdida de algunas consonantes finales y pérdida de la d entre vocales.

# Ortografía con ll e y
- Se escriben con ll muchas palabras terminadas en -illo, -illa, -ello, -ella, -alle y -elle.
- Ejemplos: anillo, aquella, chiquilla, valle, muelle.
- Los verbos terminados en -illar, -ullar y -ullir suelen escribirse con ll.

# Para estudiar mejor
- Primero lee el resumen.
- Después haz deberes para practicar.
- Termina con el examen para comprobar si lo has entendido.' WHERE id=(SELECT id FROM topics WHERE subject='Lengua' AND title='Tema 12 · Valoramos las tradiciones' LIMIT 1);
SET @topic_lengua12 := (SELECT id FROM topics WHERE subject='Lengua' AND title='Tema 12 · Valoramos las tradiciones' LIMIT 1);
DELETE FROM questions WHERE topic_id=@topic_lengua12;
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Qué tipo de texto es una entrevista?','["Un texto periodístico", "Un problema matemático", "Un mapa", "Una receta"]','Un texto periodístico','La entrevista pertenece a los textos periodísticos porque sirve para informar mediante preguntas y respuestas.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Cuáles son las tres partes básicas de una entrevista?','["Presentación, preguntas y despedida", "Título, suma y resta", "Inicio, nudo y desenlace", "Regla, ejemplo y final"]','Presentación, preguntas y despedida','Una entrevista suele empezar con saludo y presentación, sigue con preguntas y respuestas y termina con la despedida.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'text','¿En qué año fue declarado el flamenco Patrimonio Inmaterial de la Humanidad?',NULL,'2010','La UNESCO declaró el flamenco Patrimonio Inmaterial de la Humanidad en 2010.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Cuáles son las tres manifestaciones del flamenco?','["Cante, toque y baile", "Pintura, música y teatro", "Sol, luna y mar", "Cuento, poema y novela"]','Cante, toque y baile','El flamenco se expresa principalmente a través del cante, el toque y el baile.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','Relaciona bien: ¿qué barrios pertenecen a Cádiz?','["La Viña y La Santa María", "Triana y Macarena", "Sacromonte", "La Caleta y El Perchel"]','La Viña y La Santa María','En el tema se explica que en Cádiz destacan los barrios de La Viña y La Santa María.',2);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Qué barrios pertenecen a Sevilla?','["Triana y Macarena", "Santiago y San Miguel", "Sacromonte", "La Viña y La Santa María"]','Triana y Macarena','Sevilla se relaciona con los barrios de Triana y Macarena.',2);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Qué significa la sigla BNE?','["Ballet Nacional de España", "Biblioteca Nacional Escolar", "Baile Nacional Europeo", "Base Nacional Española"]','Ballet Nacional de España','BNE significa Ballet Nacional de España.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Qué significa la sigla IAF?','["Instituto Andaluz del Flamenco", "Instituto Andaluz de Física", "Información Andaluza del Folclore", "Instituto Artístico Flamenco"]','Instituto Andaluz del Flamenco','IAF corresponde a Instituto Andaluz del Flamenco.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Qué abreviatura corresponde a “Avenida”?','["avda.", "atte.", "DNI", "Dir.ª"]','avda.','La abreviatura habitual de avenida es avda.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Qué lengua cooficial se habla en Galicia?','["Gallego", "Euskara", "Catalán", "Valenciano"]','Gallego','En Galicia la lengua cooficial es el gallego.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Cómo se llama el catalán en la Comunidad Valenciana?','["Valenciano", "Gallego", "Euskara", "Castellano"]','Valenciano','En la Comunidad Valenciana el catalán recibe el nombre de valenciano.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Qué rasgo del andaluz consiste en pronunciar la z como s?','["Seseo", "Ceceo", "Yeísmo", "Aspiración"]','Seseo','El seseo consiste en pronunciar el sonido de la z como s.',2);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Qué rasgo del andaluz consiste en pronunciar ll y y de la misma forma?','["Yeísmo", "Ceceo", "Seseo", "Entonación"]','Yeísmo','El yeísmo consiste en pronunciar ll y y igual.',2);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Cuál de estas palabras se escribe con ll?','["anillo", "rayo", "ley", "ayuda"]','anillo','Anillo lleva ll, igual que muchas palabras terminadas en -illo.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Cuál de estas palabras se escribe con y?','["ayuda", "muelle", "silla", "lluvia"]','ayuda','Ayuda se escribe con y.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','¿Qué es una noticia?','["Un texto periodístico que informa sobre un hecho reciente", "Una poesía con rima", "Un problema de cálculo", "Una carta informal"]','Un texto periodístico que informa sobre un hecho reciente','La noticia informa de un acontecimiento reciente que interesa a muchas personas.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'text','Escribe una de las tres manifestaciones del flamenco.',NULL,'cante','Una respuesta correcta puede ser cante, toque o baile.',1);
INSERT INTO questions(topic_id,type,question,options_json,correct_answer,explanation,difficulty) VALUES(@topic_lengua12,'multiple','Danza clásica se relaciona con…','["ballet", "baile de pareja", "danza tradicional", "hip-hop"]','ballet','La danza clásica se asocia al ballet.',1);
