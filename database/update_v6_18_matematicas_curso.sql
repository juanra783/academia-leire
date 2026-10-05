-- Academia Star 6.15 · Matemáticas reales del libro
-- Contenido basado en las páginas fotografiadas: multiplicación, expresiones y potencias.

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

CREATE TABLE IF NOT EXISTS study_task_content (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 task_id INT UNSIGNED NOT NULL,
 sort_order INT NOT NULL DEFAULT 1,
 prompt TEXT NOT NULL,
 options_json TEXT NULL,
 answer TEXT NOT NULL,
 explanation TEXT NULL,
 UNIQUE KEY uq_task_order(task_id,sort_order),
 INDEX idx_task_content_task(task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS study_task_attempts (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 task_id INT UNSIGNED NOT NULL,
 user_id INT NOT NULL,
 total_questions INT NOT NULL DEFAULT 0,
 correct_answers INT NOT NULL DEFAULT 0,
 xp_earned INT NOT NULL DEFAULT 0,
 completed_at DATETIME NOT NULL,
 status VARCHAR(30) NOT NULL DEFAULT 'completed',
 INDEX idx_attempt_user_task(user_id,task_id),
 INDEX idx_attempt_date(user_id,completed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1. Multiplicar por 10, 100 y 1000
INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Multiplicar por 10, 100 y 1000','matematicas','multiplicar-por-10-100-1000','Practica el truco de multiplicar por decenas, centenas y millares.',10,15,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Multiplicar por 10, 100 y 1000');
SET @t1=(SELECT id FROM study_tasks WHERE title='Multiplicar por 10, 100 y 1000' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@t1,1,'4 × 20 = ?', '["80","800","24","60"]','80','Multiplicar por 20 es multiplicar por 2 y añadir un cero.'),
(@t1,2,'4 × 200 = ?', '["800","80","8000","600"]','800','Multiplicar por 200 da 800.'),
(@t1,3,'4 × 2000 = ?', '["8000","800","80000","6000"]','8000','Multiplicar por 2000 da 8000.'),
(@t1,4,'23 × 10 = ?', '["230","203","2300","33"]','230','Al multiplicar por 10 añadimos un cero.'),
(@t1,5,'3855 × 61, aproximando a 4000 × 60, es aproximadamente…','["240000","24000","244000","230000"]','240000','4000 × 60 = 240000.'),
(@t1,6,'En 24 cajas hay 236 piezas en cada una. ¿Cuántas piezas hay?','["5664","5646","5264","5660"]','5664','236 × 24 = 236 × 20 + 236 × 4 = 4720 + 944 = 5664.');

-- 2. Multiplicaciones de varias cifras
INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Multiplicaciones de varias cifras','matematicas','multiplicaciones-varias-cifras','Coloca y calcula multiplicaciones de dos, tres y más cifras.',15,25,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Multiplicaciones de varias cifras');
SET @t2=(SELECT id FROM study_tasks WHERE title='Multiplicaciones de varias cifras' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@t2,1,'237 × 92 = ?', '["21804","21704","21904","20804"]','21804','237 × 90 = 21330 y 237 × 2 = 474; total 21804.'),
(@t2,2,'1568 × 56 = ?', '["87808","87880","86808","87908"]','87808','1568 × 50 = 78400 y × 6 = 9408; total 87808.'),
(@t2,3,'7021 × 87 = ?', '["610827","6108270","607821","620827"]','610827','7021 × 80 = 561680 y × 7 = 49147; total 610827.'),
(@t2,4,'457 × 219 = ?', '["100083","100803","99083","101083"]','100083','457 × 200 + 457 × 19 = 91400 + 8683 = 100083.'),
(@t2,5,'529 × 842 = ?', '["445418","445148","454418","445018"]','445418','529 × 800 + 529 × 42 = 423200 + 22218 = 445418.'),
(@t2,6,'2671 × 345 = ?', '["921495","921945","912495","922495"]','921495','2671 × 300 + ×40 + ×5 = 801300 + 106840 + 13355 = 921495.');

-- 3. Descomposición y propiedad distributiva
INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Multiplicamos descomponiendo','matematicas','propiedad-distributiva','Descompón los factores y comprueba que obtienes el mismo resultado.',15,25,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Multiplicamos descomponiendo');
SET @t3=(SELECT id FROM study_tasks WHERE title='Multiplicamos descomponiendo' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@t3,1,'513 × 42 = ?', '["21546","21564","21456","21646"]','21546','500×40 + 10×40 + 3×40 + 500×2 + 10×2 + 3×2 = 21546.'),
(@t3,2,'28 × 53 = ?', '["1484","1844","1458","1584"]','1484','28×50 + 28×3 = 1400 + 84 = 1484.'),
(@t3,3,'832 × 74 = ?', '["61568","61586","61658","60568"]','61568','832×70 + 832×4 = 58240 + 3328 = 61568.'),
(@t3,4,'964 × 123 = ?', '["118572","118752","117572","119572"]','118572','964×100 + ×20 + ×3 = 96400 + 19280 + 2892 = 118572.'),
(@t3,5,'¿Qué propiedad usamos cuando 236 × (20 + 4) = 236×20 + 236×4?','["Distributiva","Conmutativa","Asociativa","Identidad"]','Distributiva','La multiplicación se reparte sobre la suma.'),
(@t3,6,'Calcula 703 × 500.','["351500","350500","351050","350000"]','351500','703 × 5 = 3515 y añadimos dos ceros.');

-- 4. Expresiones con varias operaciones
INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Expresiones y orden de operaciones','matematicas','expresiones','Resuelve expresiones respetando paréntesis y el orden de las operaciones.',15,25,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Expresiones y orden de operaciones');
SET @t4=(SELECT id FROM study_tasks WHERE title='Expresiones y orden de operaciones' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@t4,1,'5 × (10 − 7) = ?', '["15","35","65","8"]','15','Primero 10−7=3 y después 5×3=15.'),
(@t4,2,'(4 + 3) × 8 = ?', '["56","35","32","60"]','56','Primero 4+3=7 y después 7×8=56.'),
(@t4,3,'12 + 6 × 2 = ?', '["24","36","30","20"]','24','Primero la multiplicación: 6×2=12; después 12+12=24.'),
(@t4,4,'40 − 3 × 7 = ?', '["19","259","37","61"]','19','Primero 3×7=21; después 40−21=19.'),
(@t4,5,'(25 + 6) × 3 = ?', '["93","75","108","31"]','93','25+6=31 y 31×3=93.'),
(@t4,6,'30 × 40 + 7 = ?', '["1207","12007","1280","120"]','1207','Primero 30×40=1200 y después sumamos 7.');

-- 5. Potencias: base y exponente
INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Potencias: base y exponente','matematicas','potencias-base-exponente','Aprende a escribir multiplicaciones repetidas como potencias y calcula su valor.',12,20,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Potencias: base y exponente');
SET @t5=(SELECT id FROM study_tasks WHERE title='Potencias: base y exponente' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@t5,1,'2 × 2 × 2 = ?', '["2³","3²","2²","6²"]','2³','La base es 2 y se repite 3 veces.'),
(@t5,2,'6 × 6 × 6 × 6 = ?', '["6⁴","4⁶","6³","24²"]','6⁴','La base es 6 y el exponente indica cuatro factores.'),
(@t5,3,'¿Cuál es la base de 5⁴?','["5","4","20","9"]','5','La base es el número que se multiplica repetidamente.'),
(@t5,4,'¿Cuál es el exponente de 7³?','["3","7","21","10"]','3','El exponente indica cuántas veces se repite la base.'),
(@t5,5,'2⁴ = ?', '["16","8","12","24"]','16','2×2×2×2=16.'),
(@t5,6,'9¹ = ?', '["9","1","0","81"]','9','Toda potencia con exponente 1 es la propia base.');

-- 6. Cuadrados y cubos
INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Cuadrados y cubos','matematicas','cuadrados-y-cubos','Relaciona las potencias de exponente 2 y 3 con cuadrados y cubos.',12,20,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Cuadrados y cubos');
SET @t6=(SELECT id FROM study_tasks WHERE title='Cuadrados y cubos' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@t6,1,'5² = ?', '["25","10","15","20"]','25','5×5=25.'),
(@t6,2,'7² = ?', '["49","14","21","42"]','49','7×7=49.'),
(@t6,3,'3³ = ?', '["27","9","18","12"]','27','3×3×3=27.'),
(@t6,4,'4³ = ?', '["64","16","12","48"]','64','4×4×4=64.'),
(@t6,5,'Un cuadrado de lado 5 tiene… cuadraditos.','["25","10","20","15"]','25','5 filas de 5: 5²=25.'),
(@t6,6,'Un cubo de arista 2 tiene… cubitos.','["8","6","4","12"]','8','2×2×2=2³=8.');

-- 7. Problemas de multiplicación y potencias
INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Problemas de multiplicación','matematicas','problemas-multiplicacion','Resuelve problemas parecidos a los trabajados en las páginas del libro.',15,25,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Problemas de multiplicación');
SET @t7=(SELECT id FROM study_tasks WHERE title='Problemas de multiplicación' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@t7,1,'En un edificio viven 210 personas. Cada una recicla 67 envases al año. ¿Cuántos envases son aproximadamente?','["14070","1407","21000","13400"]','14070','210×67 = 14070.'),
(@t7,2,'Una escuela recicla 38 kg de papel al mes. ¿Cuántos kg recicla aproximadamente en 9 meses?','["342","304","380","350"]','342','38×9=342.'),
(@t7,3,'Marta gasta 95 L de agua cada día. ¿Cuántos litros gastará aproximadamente en 30 días?','["2850","950","3000","1950"]','2850','95×30=2850.'),
(@t7,4,'Un museo recibe 1176 visitantes cada semana. ¿Cuántos recibe en 5 semanas?','["5880","5870","5800","6176"]','5880','1176×5=5880.'),
(@t7,5,'Una protectora compra 32 sacos de 25 kg y 14 sacos de 12 kg. ¿Cuántos kg compra en una semana?','["968","944","800","1120"]','968','32×25=800 y 14×12=168; total 968 kg.'),
(@t7,6,'Para fabricar una camiseta hacen falta 22 botellas. ¿Cuántas botellas hacen falta para 100 camisetas?','["2200","2220","2000","220"]','2200','22×100=2200.');

-- 8. Repaso final del bloque
INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Repaso final: multiplicación y potencias','matematicas','repaso-bloque','Mini prueba mezclando multiplicaciones, expresiones y potencias del bloque trabajado.',18,30,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Repaso final: multiplicación y potencias');
SET @t8=(SELECT id FROM study_tasks WHERE title='Repaso final: multiplicación y potencias' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@t8,1,'774 × 104 = ?', '["80496","80469","79496","81496"]','80496','774×100 + 774×4 = 77400 + 3096 = 80496.'),
(@t8,2,'820 × 431 = ?', '["353420","353240","352420","354420"]','353420','820×431 = 431×82×10 = 35342×10 = 353420.'),
(@t8,3,'596 − 8 = ?', '["588","582","598","586"]','588','596−8=588.'),
(@t8,4,'73 − 9 × 7 = ?', '["10","448","64","70"]','10','Primero 9×7=63; después 73−63=10.'),
(@t8,5,'10 × 10 × 10 = ?', '["10³","3¹⁰","100","10²"]','10³','El 10 se repite tres veces.'),
(@t8,6,'12² = ?', '["144","24","36","121"]','144','12×12=144.'),
(@t8,7,'6³ = ?', '["216","36","18","108"]','216','6×6×6=216.'),
(@t8,8,'¿Cómo se llama una potencia de exponente 2?','["Cuadrado","Cubo","Raíz","Factor"]','Cuadrado','Las potencias de exponente 2 reciben el nombre de cuadrados.');
