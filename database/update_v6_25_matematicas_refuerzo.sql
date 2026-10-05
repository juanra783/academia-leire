-- Academia Star 6.25 · Refuerzo intensivo Matemáticas
-- Refuerza el bloque trabajado: multiplicación, distributiva, expresiones, potencias, cuadrados/cubos y problemas.
-- No crea tablas nuevas: usa study_tasks y study_task_content existentes.

INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Entrenamiento extra · Multiplicar por 10, 100 y 1000','matematicas','multiplicar-por-10-100-1000','Repetición intensiva para automatizar multiplicaciones por 10, 100 y 1000.',12,20,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Entrenamiento extra · Multiplicar por 10, 100 y 1000');
SET @r1=(SELECT id FROM study_tasks WHERE title='Entrenamiento extra · Multiplicar por 10, 100 y 1000' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@r1,1,'15 × 10 = ?','["150","1500","25","105"]','150','Al multiplicar por 10 añadimos un cero.'),
(@r1,2,'32 × 100 = ?','["320","3200","32000","132"]','3200','Al multiplicar por 100 añadimos dos ceros.'),
(@r1,3,'47 × 1000 = ?','["4700","47000","470000","1047"]','47000','Al multiplicar por 1000 añadimos tres ceros.'),
(@r1,4,'205 × 10 = ?','["2050","20500","215","2005"]','2050','205 × 10 = 2050.'),
(@r1,5,'304 × 100 = ?','["3040","30400","304000","40004"]','30400','304 × 100 = 30400.'),
(@r1,6,'12 × 1000 = ?','["12000","1200","120000","1012"]','12000','12 × 1000 = 12000.'),
(@r1,7,'6 × 300 = ?','["1800","180","18000","306"]','1800','6 × 3 = 18 y se añaden dos ceros.'),
(@r1,8,'25 × 40 = ?','["1000","100","10000","650"]','1000','25 × 4 = 100 y se añade un cero.'),
(@r1,9,'73 × 20 = ?','["1460","146","14600","930"]','1460','73 × 2 = 146 y se añade un cero.'),
(@r1,10,'9 × 500 = ?','["4500","450","45000","509"]','4500','9 × 5 = 45 y se añaden dos ceros.');

INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Entrenamiento extra · Multiplicaciones','matematicas','multiplicaciones-varias-cifras','Muchas multiplicaciones para ganar rapidez y seguridad.',15,25,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Entrenamiento extra · Multiplicaciones');
SET @r2=(SELECT id FROM study_tasks WHERE title='Entrenamiento extra · Multiplicaciones' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@r2,1,'124 × 23 = ?','["2852","2582","2842","2952"]','2852','124×20=2480 y 124×3=372; 2480+372=2852.'),
(@r2,2,'315 × 24 = ?','["7560","7650","7506","7460"]','7560','315×20=6300 y 315×4=1260; total 7560.'),
(@r2,3,'208 × 35 = ?','["7280","728","7380","7080"]','7280','208×30=6240 y 208×5=1040; total 7280.'),
(@r2,4,'426 × 12 = ?','["5112","5122","5012","5212"]','5112','426×10=4260 y ×2=852; total 5112.'),
(@r2,5,'512 × 31 = ?','["15872","15782","16872","15827"]','15872','512×30=15360 y +512 = 15872.'),
(@r2,6,'342 × 25 = ?','["8550","8505","8650","8450"]','8550','342×100÷4 = 8550.'),
(@r2,7,'605 × 14 = ?','["8470","8460","8570","8407"]','8470','605×10=6050 y ×4=2420; total 8470.'),
(@r2,8,'721 × 16 = ?','["11536","11563","11636","10536"]','11536','721×10=7210 y ×6=4326; total 11536.'),
(@r2,9,'403 × 27 = ?','["10881","10818","10981","10781"]','10881','403×20=8060 y ×7=2821; total 10881.'),
(@r2,10,'218 × 42 = ?','["9156","9165","9056","9256"]','9156','218×40=8720 y ×2=436; total 9156.');

INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Entrenamiento extra · Propiedad distributiva','matematicas','propiedad-distributiva','Practica descomponiendo factores y comprobando el resultado.',15,25,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Entrenamiento extra · Propiedad distributiva');
SET @r3=(SELECT id FROM study_tasks WHERE title='Entrenamiento extra · Propiedad distributiva' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@r3,1,'125 × 24 = ?','["3000","3100","2900","2500"]','3000','125×20=2500 y 125×4=500; total 3000.'),
(@r3,2,'34 × 21 = ?','["714","741","704","724"]','714','34×20=680 y 34×1=34; total 714.'),
(@r3,3,'¿Qué propiedad usamos en 48×(10+2)=48×10+48×2?','["Distributiva","Conmutativa","Asociativa","Identidad"]','Distributiva','La multiplicación se reparte sobre la suma.'),
(@r3,4,'205 × 13 = ?','["2665","2655","2765","2565"]','2665','205×10=2050 y ×3=615; total 2665.'),
(@r3,5,'72 × 15 = ?','["1080","1090","1070","980"]','1080','72×10=720 y ×5=360; total 1080.'),
(@r3,6,'63 × 22 = ?','["1386","1368","1486","1286"]','1386','63×20=1260 y ×2=126; total 1386.'),
(@r3,7,'301 × 12 = ?','["3612","3621","3512","3712"]','3612','301×10=3010 y ×2=602; total 3612.'),
(@r3,8,'42 × 25 = ?','["1050","1005","950","1150"]','1050','42×100÷4 = 1050.');

INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Entrenamiento extra · Expresiones','matematicas','expresiones','Repite el orden correcto de las operaciones hasta hacerlo sin pensar.',15,25,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Entrenamiento extra · Expresiones');
SET @r4=(SELECT id FROM study_tasks WHERE title='Entrenamiento extra · Expresiones' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@r4,1,'8 + 4 × 3 = ?','["20","36","24","12"]','20','Primero 4×3=12 y después 8+12=20.'),
(@r4,2,'(8 + 4) × 3 = ?','["36","20","32","28"]','36','Primero el paréntesis: 8+4=12; después 12×3=36.'),
(@r4,3,'50 − 6 × 5 = ?','["20","220","44","30"]','20','Primero 6×5=30; después 50−30=20.'),
(@r4,4,'(50 − 6) × 5 = ?','["220","20","250","44"]','220','Primero 50−6=44; después 44×5=220.'),
(@r4,5,'7 × 9 + 8 = ?','["71","63","56","79"]','71','7×9=63; 63+8=71.'),
(@r4,6,'40 − 4 × 7 = ?','["12","252","36","8"]','12','4×7=28; 40−28=12.'),
(@r4,7,'(12 + 3) × 4 = ?','["60","48","15","51"]','60','12+3=15; 15×4=60.'),
(@r4,8,'60 ÷ 5 + 7 = ?','["19","24","12","67"]','19','60÷5=12; 12+7=19.');

INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Entrenamiento extra · Potencias','matematicas','potencias-base-exponente','Repite base, exponente y valor de potencias.',15,25,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Entrenamiento extra · Potencias');
SET @r5=(SELECT id FROM study_tasks WHERE title='Entrenamiento extra · Potencias' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@r5,1,'3² = ?','["9","6","12","8"]','9','3×3=9.'),
(@r5,2,'4³ = ?','["64","16","12","48"]','64','4×4×4=64.'),
(@r5,3,'¿Cuál es la base de 8²?','["8","2","16","10"]','8','La base es el número que se repite.'),
(@r5,4,'¿Cuál es el exponente de 6³?','["3","6","18","9"]','3','El exponente indica cuántas veces se repite la base.'),
(@r5,5,'5×5×5 = ?','["5³","3⁵","15²","25³"]','5³','El 5 se repite tres veces.'),
(@r5,6,'10×10×10×10 = ?','["10⁴","4¹⁰","10³","40²"]','10⁴','El 10 se repite cuatro veces.'),
(@r5,7,'7² = ?','["49","14","21","42"]','49','7×7=49.'),
(@r5,8,'2⁵ = ?','["32","10","16","64"]','32','2×2×2×2×2=32.');

INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Entrenamiento extra · Cuadrados y cubos','matematicas','cuadrados-y-cubos','Memoriza cuadrados y cubos sencillos y reconoce qué significa cada potencia.',12,20,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Entrenamiento extra · Cuadrados y cubos');
SET @r6=(SELECT id FROM study_tasks WHERE title='Entrenamiento extra · Cuadrados y cubos' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@r6,1,'6² = ?','["36","12","18","42"]','36','6×6=36.'),
(@r6,2,'8² = ?','["64","16","24","56"]','64','8×8=64.'),
(@r6,3,'9² = ?','["81","18","27","72"]','81','9×9=81.'),
(@r6,4,'3³ = ?','["27","9","18","12"]','27','3×3×3=27.'),
(@r6,5,'5³ = ?','["125","25","15","100"]','125','5×5×5=125.'),
(@r6,6,'6³ = ?','["216","36","18","108"]','216','6×6×6=216.'),
(@r6,7,'Una potencia de exponente 2 se llama…','["cuadrado","cubo","doble","factor"]','cuadrado','El exponente 2 indica un cuadrado.'),
(@r6,8,'Una potencia de exponente 3 se llama…','["cubo","cuadrado","mitad","factor"]','cubo','El exponente 3 indica un cubo.');

INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Entrenamiento extra · Problemas','matematicas','problemas-multiplicacion','Problemas variados para aprender a elegir la operación y comprobar el resultado.',15,25,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Entrenamiento extra · Problemas');
SET @r7=(SELECT id FROM study_tasks WHERE title='Entrenamiento extra · Problemas' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@r7,1,'Hay 36 cajas con 24 lápices cada una. ¿Cuántos lápices hay?','["864","846","824","964"]','864','36×24=864.'),
(@r7,2,'Una tienda recibe 125 cajas con 8 productos. ¿Cuántos productos?','["1000","1080","900","1250"]','1000','125×8=1000.'),
(@r7,3,'Una biblioteca tiene 48 estantes con 25 libros cada uno. ¿Cuántos libros?','["1200","1180","1250","1000"]','1200','48×25=1200.'),
(@r7,4,'Un autobús lleva 54 personas. Si hay 7 autobuses, ¿cuántas personas?','["378","368","388","354"]','378','54×7=378.'),
(@r7,5,'Una fábrica produce 320 piezas al día durante 6 días. ¿Cuántas piezas?','["1920","1820","2020","1600"]','1920','320×6=1920.'),
(@r7,6,'Hay 15 cajas con 40 botellas cada una. ¿Cuántas botellas?','["600","550","650","450"]','600','15×40=600.'),
(@r7,7,'Una clase tiene 28 alumnos y cada uno recibe 3 cuadernos. ¿Cuántos cuadernos?','["84","82","86","96"]','84','28×3=84.'),
(@r7,8,'Una excursión cuesta 18 € por alumno. Van 25 alumnos. ¿Cuánto cuesta?','["450","425","480","350"]','450','18×25=450.');

INSERT INTO study_tasks(title,subject_key,topic_key,description,duration_minutes,xp,task_type)
SELECT 'Repaso intensivo · Todo el bloque','matematicas','repaso-bloque','Repaso mezclado antes del examen. Hazlo más de una vez y busca mejorar.',20,35,'auto'
WHERE NOT EXISTS (SELECT 1 FROM study_tasks WHERE title='Repaso intensivo · Todo el bloque');
SET @r8=(SELECT id FROM study_tasks WHERE title='Repaso intensivo · Todo el bloque' LIMIT 1);
INSERT IGNORE INTO study_task_content(task_id,sort_order,prompt,options_json,answer,explanation) VALUES
(@r8,1,'45×20 = ?','["900","90","9000","650"]','900','45×2=90 y se añade un cero.'),
(@r8,2,'125×16 = ?','["2000","2100","1900","2500"]','2000','125×16=2000.'),
(@r8,3,'(9+6)×4 = ?','["60","54","15","42"]','60','9+6=15; 15×4=60.'),
(@r8,4,'70−5×8 = ?','["30","520","65","10"]','30','5×8=40; 70−40=30.'),
(@r8,5,'4³ = ?','["64","16","12","48"]','64','4×4×4=64.'),
(@r8,6,'11² = ?','["121","22","33","111"]','121','11×11=121.'),
(@r8,7,'¿Qué indica el exponente?','["Cuántas veces se repite la base","El resultado","El signo de la operación","El número mayor"]','Cuántas veces se repite la base','El exponente indica cuántas veces se multiplica la base por sí misma.'),
(@r8,8,'236×24 = ?','["5664","5646","5564","5764"]','5664','236×20=4720 y ×4=944; total 5664.'),
(@r8,9,'48×25 = ?','["1200","1100","1250","1000"]','1200','48×100÷4=1200.'),
(@r8,10,'10×10×10 = ?','["10³","10²","3¹⁰","30"]','10³','El 10 se repite tres veces.');
