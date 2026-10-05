-- Academia Star V6.1 · Tareas y contenido inicial real
CREATE TABLE IF NOT EXISTS study_tasks (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 title VARCHAR(180) NOT NULL,
 subject_key VARCHAR(80) NOT NULL,
 topic_key VARCHAR(120) NOT NULL,
 description TEXT NOT NULL,
 duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 10,
 xp SMALLINT UNSIGNED NOT NULL DEFAULT 10,
 task_type ENUM('auto','manual') NOT NULL DEFAULT 'auto',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO study_tasks (title,subject_key,topic_key,description,duration_minutes,xp,task_type) VALUES
('Sumas y restas con llevadas','matematicas','sumas-restas','Resuelve 12 operaciones de dos y tres cifras. Comprueba cada resultado con la operación inversa.',15,20,'auto'),
('Tablas del 2 al 5','matematicas','multiplicaciones','Practica las tablas del 2, 3, 4 y 5 y completa una mini prueba de velocidad.',10,15,'auto'),
('Problemas de dos pasos','matematicas','problemas','Lee cuatro problemas, identifica los datos y escribe las dos operaciones necesarias.',20,25,'auto'),
('Fracciones básicas','matematicas','fracciones','Representa mitades, tercios y cuartos y compara fracciones con el mismo denominador.',15,20,'auto'),
('Comprensión lectora','lengua','comprension','Lee un texto breve y responde preguntas sobre personajes, lugar, orden de hechos e idea principal.',20,25,'auto'),
('Sustantivos y adjetivos','lengua','gramatica','Localiza sustantivos y adjetivos en diez frases y crea cinco frases propias.',15,20,'auto'),
('Ortografía: b y v','lengua','ortografia','Completa palabras con b o v y redacta un pequeño dictado de diez palabras.',15,20,'auto'),
('Escribe una descripción','lengua','expresion','Describe un animal o lugar usando al menos ocho adjetivos y tres conectores.',20,25,'auto'),
('El cuerpo humano','medio','cuerpo-humano','Relaciona órganos con sus funciones y explica con tus palabras el recorrido de los alimentos.',20,25,'auto'),
('Seres vivos y ecosistemas','medio','seres-vivos','Clasifica animales por alimentación y reproducción y completa una cadena alimentaria.',20,25,'auto'),
('Materia y sus estados','medio','materia','Identifica sólido, líquido y gas en ejemplos cotidianos y explica dos cambios de estado.',15,20,'auto'),
('La Tierra y sus movimientos','medio','tierra','Diferencia rotación y traslación y relaciona cada movimiento con un fenómeno.',15,20,'auto'),
('Daily routines','ingles','routines','Practica diez acciones diarias y escribe cinco frases con I get up, I have y I go.',15,20,'auto'),
('Present simple','ingles','present-simple','Completa frases afirmativas y negativas y formula cinco preguntas cortas.',20,25,'auto'),
('School objects','ingles','vocabulary','Aprende diez objetos de clase y utiliza there is / there are en seis frases.',15,20,'auto'),
('Reading: My day','ingles','reading','Lee un texto sencillo sobre una rutina y responde cinco preguntas en inglés.',20,25,'auto');

CREATE TABLE IF NOT EXISTS study_task_assignments (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 task_id INT UNSIGNED NOT NULL,
 user_id INT NOT NULL,
 status ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
 assigned_date DATE NOT NULL,
 completed_at DATETIME NULL,
 UNIQUE KEY uq_task_user_day(task_id,user_id,assigned_date),
 INDEX idx_assignment_user_date(user_id,assigned_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
