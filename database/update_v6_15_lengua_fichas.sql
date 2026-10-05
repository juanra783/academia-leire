-- Academia Star 6.15 — Unidad de Lengua basada en las fichas fotografiadas.
-- Crea 4 actividades nuevas y todo su contenido autocorregible.
-- No borra ni modifica las actividades anteriores.

CREATE TABLE IF NOT EXISTS study_task_content (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT NOT NULL,
    content_type VARCHAR(30) NOT NULL DEFAULT 'question',
    prompt TEXT NOT NULL,
    answer TEXT NULL,
    options_json TEXT NULL,
    explanation TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    INDEX(task_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO study_tasks (title,subject_key,topic_key,description,duration_minutes,xp,task_type,active)
SELECT * FROM (
    SELECT 'Lenguaje, lengua y dialecto' AS title,'lengua' AS subject_key,'lenguaje-lengua-dialecto' AS topic_key,
           'Repasa qué son el lenguaje, la lengua y el dialecto, las lenguas de España y las comunidades bilingües.',20,30,'auto',1
    UNION ALL
    SELECT 'El andaluz','lengua','andaluz',
           'Reconoce las principales características del andaluz y aprende a identificar sus ejemplos.',15,25,'auto',1
    UNION ALL
    SELECT 'Reglas generales de acentuación','lengua','acentuacion',
           'Practica palabras agudas, llanas y esdrújulas, sus tildes y algunos cambios de plural.',20,30,'auto',1
    UNION ALL
    SELECT 'Comprensión lectora: Alejandría','lengua','comprension-alejandria',
           'Lee y comprende el texto sobre la ciudad de Alejandría, sus párrafos y los mecanismos de cohesión.',20,30,'auto',1
) x
WHERE NOT EXISTS (SELECT 1 FROM study_tasks t WHERE t.title=x.title);

-- ---------- TAREA 1 ----------
INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué es el lenguaje?','La capacidad humana para comunicarse, transmitir y recibir información.',NULL,
'El lenguaje es la capacidad humana para comunicarse, transmitir y recibir información.',1
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=1);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué es una lengua?','El conjunto de palabras, reglas y sonidos que utiliza una comunidad concreta.',NULL,
'Una lengua reúne palabras, reglas y sonidos compartidos por una comunidad.',2
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=2);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué es un dialecto?','Una variedad de una lengua que se habla en una zona geográfica determinada.',NULL,
'Un dialecto es una variedad geográfica de una lengua.',3
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=3);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué lengua europea se utiliza en «Muito obrigada»?','portugués','["portugués","inglés","francés","alemán"]',
'Muito obrigada es una expresión portuguesa que significa «muchas gracias».',4
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=4);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué significa «I like apples»?','Me gustan las manzanas.','["Me gustan las manzanas.","Quiero comprar manzanas.","Tengo una manzana.","No me gustan las manzanas."]',
'I like apples significa «Me gustan las manzanas».',5
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=5);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cuál es la lengua oficial de España?','castellano','["castellano","gallego","euskara","catalán"]',
'La Constitución española establece el castellano como lengua oficial del Estado.',6
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=6);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué lenguas españolas aparecen como cooficiales en sus respectivas comunidades autónomas?','gallego, euskara, catalán y valenciano',NULL,
'Son las lenguas citadas en la ficha junto al castellano.',7
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=7);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿En qué comunidad son cooficiales el catalán y el castellano?','Cataluña','["Cataluña","Galicia","País Vasco","Andalucía"]',
'En Cataluña, el catalán y el castellano son lenguas cooficiales.',8
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=8);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿En qué comunidad es cooficial el euskara junto al castellano?','País Vasco','["País Vasco","Galicia","Comunidad Valenciana","Canarias"]',
'El euskara es cooficial junto al castellano en el País Vasco.',9
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=9);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué lengua es propia de Galicia?','gallego','["gallego","euskara","valenciano","inglés"]',
'El gallego es la lengua cooficial de Galicia junto al castellano.',10
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=10);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué lengua se habla en la Comunidad Valenciana junto al castellano?','valenciano','["valenciano","gallego","euskara","romanche"]',
'El valenciano es cooficial junto al castellano en la Comunidad Valenciana.',11
FROM study_tasks t WHERE t.title='Lenguaje, lengua y dialecto'
AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=11);

-- ---------- TAREA 2 ----------
INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','En «sanahoria» por «zanahoria», ¿qué rasgo andaluz aparece?','seseo','["seseo","ceceo","yeísmo","acortamiento"]',
'El seseo consiste en pronunciar el sonido de la z como s.',1
FROM study_tasks t WHERE t.title='El andaluz' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=1);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','En «coza» por «cosa», ¿qué rasgo andaluz aparece?','ceceo','["ceceo","seseo","yeísmo","pérdida de d"]',
'El ceceo consiste en pronunciar el sonido de la s como z.',2
FROM study_tasks t WHERE t.title='El andaluz' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=2);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','En «altillo» pronunciado como «altivo», ¿qué rasgo aparece?','yeísmo','["yeísmo","seseo","ceceo","acortamiento"]',
'El yeísmo consiste en pronunciar y como ll o, según la variedad, igualar ambos sonidos.',3
FROM study_tasks t WHERE t.title='El andaluz' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=3);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','«Empuhón» por «empujón» es un ejemplo de…','aspiración del sonido de j','["aspiración del sonido de j","seseo","pérdida de d","acortamiento"]',
'La j puede pronunciarse de forma aspirada en algunas variedades andaluzas.',4
FROM study_tasks t WHERE t.title='El andaluz' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=4);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','«Mih amigoh» por «mis amigos» muestra…','aspiración de la s final','["aspiración de la s final","ceceo","yeísmo","pérdida de d"]',
'La s final puede aspirarse y sonar como una h.',5
FROM study_tasks t WHERE t.title='El andaluz' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=5);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','«Verdá» por «verdad» es un ejemplo de…','pérdida de consonante final','["pérdida de consonante final","seseo","yeísmo","acortamiento"]',
'Se pierde la consonante final d.',6
FROM study_tasks t WHERE t.title='El andaluz' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=6);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','«Perdío» por «perdido» muestra…','pérdida de la d entre vocales','["pérdida de la d entre vocales","ceceo","seseo","yeísmo"]',
'En algunas hablas se pierde la d situada entre vocales.',7
FROM study_tasks t WHERE t.title='El andaluz' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=7);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','«Mi arma» por «mi alma» muestra…','sustitución de l final de sílaba por r','["sustitución de l final de sílaba por r","seseo","ceceo","acortamiento"]',
'La l al final de sílaba puede realizarse como r.',8
FROM study_tasks t WHERE t.title='El andaluz' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=8);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué rasgo aparece en «mu» por «muy»?','acortamiento de palabras','["acortamiento de palabras","ceceo","seseo","yeísmo"]',
'Mu es una forma acortada de muy.',9
FROM study_tasks t WHERE t.title='El andaluz' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=9);

-- ---------- TAREA 3 ----------
INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cómo se clasifica «volcán»?','aguda','["aguda","llana","esdrújula"]',
'Vol-cán tiene la fuerza de voz en la última sílaba.',1
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=1);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cómo se clasifica «lámpara»?','esdrújula','["aguda","llana","esdrújula"]',
'Lám-pa-ra tiene la fuerza de voz en la antepenúltima sílaba.',2
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=2);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cómo se clasifica «árbol»?','llana','["aguda","llana","esdrújula"]',
'Ár-bol tiene la fuerza de voz en la penúltima sílaba.',3
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=3);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cuándo llevan tilde las palabras agudas?','Cuando terminan en vocal, n o s.',NULL,
'Las agudas llevan tilde cuando terminan en vocal, n o s.',4
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=4);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cuándo llevan tilde las palabras llanas?','Cuando terminan en consonante distinta de n o s.',NULL,
'Las llanas llevan tilde cuando terminan en consonante distinta de n o s.',5
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=5);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cómo son las reglas de las palabras esdrújulas?','Siempre llevan tilde.','["Siempre llevan tilde.","Nunca llevan tilde.","Solo llevan tilde si terminan en n o s."]',
'Todas las palabras esdrújulas llevan tilde.',6
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=6);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','Escribe correctamente: «dificil».','difícil',NULL,
'Difícil es llana y termina en consonante distinta de n o s.',7
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=7);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','Escribe correctamente: «comic».','cómic',NULL,
'Cómic es llana y termina en c, por eso lleva tilde.',8
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=8);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cuál es el plural correcto de «canción»?','canciones','["canciones","canciónes","cancióneses"]',
'Al formar el plural, la tilde de canción desaparece: canciones.',9
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=9);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cuál es el plural correcto de «examen»?','exámenes','["exámenes","examens","examenes"]',
'Exámenes es esdrújula y por eso lleva tilde.',10
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=10);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','En «En el mar gran nadador, juguetón y saltarín, el ____», ¿qué animal con tilde encaja?','delfín','["delfín","tiburon","mono","raton"]',
'Delfín es una palabra aguda terminada en n y lleva tilde.',11
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=11);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué animal de los ejemplos de la ficha se escribe con tilde?','águila','["águila","tigre","mono","pantera"]',
'Águila es esdrújula y siempre lleva tilde.',12
FROM study_tasks t WHERE t.title='Reglas generales de acentuación' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=12);

-- ---------- TAREA 4 ----------
INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Sobre qué trata principalmente el texto de Alejandría?','Sobre la ciudad de Alejandría, su historia y su importancia cultural.',NULL,
'El texto presenta dónde está Alejandría, quién la fundó y su importancia como centro cultural.',1
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=1);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿En qué país está situada Alejandría?','Egipto','["Egipto","Grecia","Italia","España"]',
'Alejandría está situada en Egipto.',2
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=2);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Quién fundó Alejandría?','Alejandro Magno','["Alejandro Magno","Julio César","Aristóteles","Cleopatra"]',
'La ciudad fue fundada por Alejandro Magno en el año 331 a. C.',3
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=3);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Por qué tuvo fama de ser una ciudad cosmopolita?','Porque convivieron en ella personas de distintas culturas.',NULL,
'El texto explica que allí convivieron griegos, egipcios, sirios, hebreos y otras culturas.',4
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=4);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué era el Museion?','Un centro cultural consagrado a las nueve musas.',NULL,
'El Museion era un centro cultural dedicado a las nueve musas, diosas de las artes y del conocimiento.',5
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=5);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿A qué se refiere «ella» en «pues en ella convivieron…»?','A Alejandría.','["A Alejandría.","A Egipto.","Al río Nilo.","A Grecia."]',
'El pronombre ella sustituye a Alejandría.',6
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=6);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','«Este imponente lugar» se refiere a…','el Museion','["el Museion","el río Nilo","Egipto","Alejandría"]',
'La expresión aparece después de explicar el Museion y se refiere a ese centro cultural.',7
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=7);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Cuántos párrafos tiene el texto sobre Alejandría?','1','["1","2","3","4"]',
'En la ficha, el texto aparece como un único bloque de texto y, por tanto, tiene un párrafo.',8
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=8);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué significa que Alejandría fue considerada la capital del conocimiento?','Que fue un gran centro de estudio, ciencia y cultura.',NULL,
'El texto destaca sus espacios para artistas y científicos y su gran Biblioteca.',9
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=9);

INSERT INTO study_task_content(task_id,content_type,prompt,answer,options_json,explanation,sort_order)
SELECT t.id,'question','¿Qué lugar de estudio y conservación aparece al final del material?','Las grandes bibliotecas.','["Las grandes bibliotecas.","Los estadios.","Los mercados.","Los puertos."]',
'La actividad final propone investigar grandes bibliotecas y redactar un texto expositivo.',10)
FROM study_tasks t WHERE t.title='Comprensión lectora: Alejandría' AND NOT EXISTS (SELECT 1 FROM study_task_content c WHERE c.task_id=t.id AND c.sort_order=10);
