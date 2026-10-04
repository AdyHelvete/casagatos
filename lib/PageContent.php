<?php
declare(strict_types=1);

require_once __DIR__ . '/SiteStorage.php';

/**
 * Textos editables de las páginas públicas. Cada página declara sus secciones
 * y campos con un valor por defecto; lo que se guarda desde el panel
 * (Control · Textos de las páginas) vive en data/content.json y sustituye al
 * valor por defecto campo por campo.
 *
 * Tipos de campo:
 *  - text / textarea / image: cadena.
 *  - lines: lista de cadenas (una por línea en el panel).
 *  - items: lista de bloques con subcampos (título, texto, enlace…).
 */
class PageContent
{
    private const FILE = 'content.json';

    /**
     * Motivo con el que se registran los cuestionarios de adopción. La API lo
     * acepta siempre, aunque no esté en la lista editable de motivos.
     */
    public const ADOPTION_FORM_SERVICE = 'Cuestionario de adopción';

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $stored = null;

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function schema(): array
    {
        return [
            'home' => [
                'label' => 'Inicio',
                'url' => '/',
                'sections' => [
                    'hero' => [
                        'label' => 'Portada',
                        'fields' => [
                            'eyebrow' => self::text('Etiqueta superior', 'TNR · Adopción · Orientación · Tizayuca y zona'),
                            'title' => self::text('Título', 'Adopta. Esteriliza. Protege.'),
                            'accent' => self::text('Título (parte destacada)', 'La Casa de los Gatos'),
                            'lead' => self::textarea('Texto de presentación', 'Somos un proyecto vecinal de Tizayuca que trabaja por los gatos de calle: control ético de la población con TNR, adopción responsable y orientación para quien quiere ayudar y no sabe por dónde empezar.'),
                            'bullets' => self::lines('Puntos clave', [
                                'TNR y jornadas de esterilización para frenar el abandono de raíz.',
                                'Adopción con cuestionario, visita al hogar y seguimiento.',
                                'Orientación para rescates, denuncias y atención veterinaria.',
                            ]),
                            'image' => self::image('Imagen', '/assets/images/hero-adopcion.jpg'),
                            'imageAlt' => self::text('Descripción de la imagen', 'Gato negro de La Casa de los Gatos dentro de una caja de cartón'),
                            'primaryLabel' => self::text('Botón principal', 'Quiero adoptar'),
                            'primaryUrl' => self::text('Enlace del botón principal', '/adopcion/'),
                            'secondaryLabel' => self::text('Botón secundario', 'Necesito orientación'),
                            'secondaryUrl' => self::text('Enlace del botón secundario', '/asistencia/'),
                        ],
                    ],
                    'marquee' => [
                        'label' => 'Cinta de palabras',
                        'fields' => [
                            'words' => self::lines('Palabras (una por línea)', ['#AdoptaNoCompres', 'Esteriliza', 'TNR', 'Denuncia', 'Comparte']),
                        ],
                    ],
                    'access' => [
                        'label' => 'Acceso rápido a las secciones',
                        'fields' => self::heading('¿Qué necesitas?', 'Todo el sitio en cuatro puertas.', 'Elige la que se parece a tu caso. Cada sección explica qué hacer, paso a paso.') + [
                            'cards' => self::items('Tarjetas', ['title' => 'Título', 'text' => 'Texto', 'label' => 'Texto del enlace', 'url' => 'Enlace'], [
                                ['title' => 'TNR', 'text' => 'Qué es el Trampeo, Esterilización y Retorno, cómo preparar a un gato para su cirugía y cuándo son las próximas jornadas.', 'label' => 'Conocer el TNR', 'url' => '/tnr/'],
                                ['title' => 'Adopción', 'text' => 'Requisitos, cuestionario, visita al hogar, seguimiento y las historias de quienes ya encontraron familia.', 'label' => 'Quiero adoptar', 'url' => '/adopcion/'],
                                ['title' => 'Asistencia y orientación', 'text' => 'Qué hacer si encontraste un gato herido, una camada o un caso de maltrato. Incluye cómo y dónde denunciar.', 'label' => 'Pedir orientación', 'url' => '/asistencia/'],
                                ['title' => 'Directorio', 'text' => 'Clínicas veterinarias de Tizayuca y, para casos específicos, de Zumpango, con el servicio que ofrece cada una.', 'label' => 'Ver clínicas', 'url' => '/directorio/'],
                            ]),
                        ],
                    ],
                    'jornadas' => [
                        'label' => 'Bloque de jornadas',
                        'fields' => self::heading('Jornadas', 'Próximas jornadas de esterilización.', 'Los cupos son limitados y se asignan por registro previo.'),
                    ],
                    'cats' => [
                        'label' => 'Bloque de gatos en adopción',
                        'fields' => self::heading('Buscan hogar', 'Ellos están esperando.', 'Cada ficha incluye edad, carácter y estado de salud. Si alguno te late, llena el cuestionario de adopción.'),
                    ],
                    'social' => [
                        'label' => 'Bloque de redes sociales',
                        'fields' => [
                            'eyebrow' => self::text('Etiqueta', 'Comunidad'),
                            'title' => self::text('Título', 'El día a día está en redes.'),
                            'text' => self::textarea('Texto', 'Publicamos rescates, avances de los gatos en tratamiento y avisos de jornadas. Seguirnos y compartir es la ayuda que más multiplica.'),
                        ],
                    ],
                    'cta' => [
                        'label' => 'Llamado final',
                        'fields' => [
                            'title' => self::text('Título', '¿No sabes por dónde empezar?'),
                            'text' => self::textarea('Texto', 'Escríbenos por WhatsApp y cuéntanos tu caso. Te orientamos sin costo.'),
                        ],
                    ],
                ],
            ],

            'tnr' => [
                'label' => 'TNR',
                'url' => '/tnr/',
                'sections' => [
                    'hero' => [
                        'label' => 'Encabezado',
                        'fields' => self::hero('TNR', 'Trampeo, esterilización', 'y retorno.', 'El TNR es el método más efectivo y humano para controlar la población de gatos de calle: se captura al gato sin lastimarlo, se esteriliza y se devuelve a su colonia, donde ya no se reproducirá.'),
                    ],
                    'what' => [
                        'label' => 'Qué es el TNR',
                        'fields' => self::heading('Qué es', 'Tres pasos que cambian una colonia.', 'TNR son las siglas en inglés de Trap, Neuter, Return. En español: Trampeo, Esterilización y Retorno.') + [
                            'steps' => self::items('Pasos', ['title' => 'Título', 'text' => 'Texto'], [
                                ['title' => 'Trampeo', 'text' => 'Se captura al gato con una trampa-jaula que no lo lastima. Se hace con calma, con comida como señuelo y siempre con un plan para trasladarlo el mismo día.'],
                                ['title' => 'Esterilización', 'text' => 'Una médica o médico veterinario lo opera, lo desparasita y le hace una pequeña marca en la punta de la oreja. Esa marca indica que ya está esterilizado y evita que lo vuelvan a capturar.'],
                                ['title' => 'Retorno', 'text' => 'Tras recuperarse (24 a 72 horas), regresa al mismo lugar donde vivía. Ahí conoce su territorio, sus fuentes de alimento y a las personas que lo cuidan.'],
                            ]),
                        ],
                    ],
                    'why' => [
                        'label' => 'Control ético de la población',
                        'fields' => self::heading('Por qué funciona', 'Control ético, no exterminio.', 'Retirar o matar gatos no resuelve nada: el territorio que queda libre lo ocupan otros gatos sin esterilizar y el ciclo empieza de nuevo. El TNR estabiliza la colonia y la reduce con el tiempo.') + [
                            'points' => self::items('Argumentos', ['title' => 'Título', 'text' => 'Texto'], [
                                ['title' => 'Menos camadas', 'text' => 'Una gata puede tener de dos a tres camadas al año. Cada esterilización evita decenas de nacimientos en la calle.'],
                                ['title' => 'Menos molestias', 'text' => 'Sin celo disminuyen las peleas, los maullidos nocturnos y el marcaje con orina: la convivencia con los vecinos mejora.'],
                                ['title' => 'Gatos más sanos', 'text' => 'Se reducen las enfermedades de transmisión por peleas y apareamiento, y los tumores asociados a las hormonas.'],
                                ['title' => 'Responsabilidad compartida', 'text' => 'Los gatos de calle existen porque alguien abandonó o no esterilizó. Cuidarlos y controlarlos sin crueldad es tarea de la comunidad.'],
                            ]),
                        ],
                    ],
                    'process' => [
                        'label' => 'Orientación sobre la esterilización',
                        'fields' => self::heading('Antes y después', 'Cómo preparar a un gato para su esterilización.', 'Estas indicaciones son generales. El día de la jornada, la médica o médico veterinario te dará las instrucciones definitivas.') + [
                            'beforeTitle' => self::text('Título de la lista "antes"', 'Antes de la cirugía'),
                            'before' => self::lines('Antes de la cirugía', [
                                'Regístrate con anticipación: los cupos de cada jornada son limitados.',
                                'Ayuno de alimento la noche anterior, según la indicación que recibas al registrarte.',
                                'Llévalo en transportadora o trampa-jaula, nunca en brazos ni en cajas abiertas.',
                                'Avisa si está enfermo, si es una hembra lactando o si sospechas que está gestante.',
                                'Lleva una cobija o toalla limpia para su recuperación.',
                            ]),
                            'afterTitle' => self::text('Título de la lista "después"', 'Después de la cirugía'),
                            'after' => self::lines('Después de la cirugía', [
                                'Mantenlo en un lugar cerrado, tranquilo y templado mientras pasa la anestesia.',
                                'Ofrécele agua y poca comida cuando esté completamente despierto.',
                                'Revisa la herida una vez al día: no debe sangrar, abrirse ni supurar.',
                                'Los gatos de colonia regresan a su territorio cuando el veterinario lo indique, normalmente entre 24 y 72 horas.',
                                'Ante cualquier signo de alarma, acude a una clínica del directorio.',
                            ]),
                        ],
                    ],
                    'jornadas' => [
                        'label' => 'Jornadas disponibles',
                        'fields' => self::heading('Jornadas', 'Jornadas disponibles.', 'Las jornadas se publican aquí y en nuestras redes. El registro es obligatorio.') + [
                            'emptyTitle' => self::text('Título cuando no hay jornadas', 'No hay jornadas abiertas en este momento'),
                            'emptyText' => self::textarea('Texto cuando no hay jornadas', 'Publicamos cada convocatoria con anticipación. Escríbenos y te avisamos de la próxima, o consulta el directorio de clínicas para esterilizar por tu cuenta.'),
                            'pastTitle' => self::text('Título del historial', 'Jornadas anteriores'),
                        ],
                    ],
                    'faq' => [
                        'label' => 'Preguntas frecuentes',
                        'fields' => self::heading('Dudas comunes', 'Lo que más nos preguntan.', '') + [
                            'items' => self::items('Preguntas', ['title' => 'Pregunta', 'text' => 'Respuesta'], [
                                ['title' => '¿A partir de qué edad se puede esterilizar?', 'text' => 'La edad y el peso mínimos los define la médica o médico veterinario de cada jornada. Como referencia, suele hacerse a partir de los cuatro o cinco meses. Pregunta al registrarte.'],
                                ['title' => '¿Qué significa la oreja cortada?', 'text' => 'Es la marca universal de un gato de colonia esterilizado. Se hace bajo anestesia, durante la misma cirugía, y evita volver a capturar y anestesiar a un gato que ya fue operado.'],
                                ['title' => '¿Por qué lo devuelven a la calle?', 'text' => 'Porque un gato feral adulto no se adapta a vivir en una casa. Regresarlo a su colonia, ya esterilizado, es lo mejor para él. Los cachorros y los gatos sociables sí se canalizan a adopción.'],
                                ['title' => 'Cuido una colonia, ¿me pueden ayudar?', 'text' => 'Sí. Escríbenos con la ubicación, el número aproximado de gatos y si alguien los alimenta. Te orientamos para organizar el trampeo y te avisamos de la siguiente jornada.'],
                            ]),
                        ],
                    ],
                    'cta' => [
                        'label' => 'Llamado final',
                        'fields' => [
                            'title' => self::text('Título', '¿Hay una colonia cerca de tu casa?'),
                            'text' => self::textarea('Texto', 'Cuéntanos dónde está y cuántos gatos son. Te ayudamos a planear el TNR.'),
                        ],
                    ],
                ],
            ],

            'adopcion' => [
                'label' => 'Adopción',
                'url' => '/adopcion/',
                'sections' => [
                    'hero' => [
                        'label' => 'Encabezado',
                        'fields' => self::hero('Adopción', 'Adoptar toma unos días.', 'Vivir juntos, 15 años.', 'Nuestro proceso no busca complicarte la vida: busca que la adopción sea definitiva y que el gato no regrese a la calle en tres meses.'),
                    ],
                    'requirements' => [
                        'label' => 'Requisitos',
                        'fields' => self::heading('Requisitos', 'Lo que pedimos.', 'Cada requisito tiene detrás una experiencia previa. Ninguno es un trámite por cumplir.') + [
                            'list' => self::lines('Requisitos (uno por línea)', [
                                'Ser mayor de 18 años y presentar identificación oficial.',
                                'Que todas las personas de la casa estén de acuerdo con la adopción.',
                                'Responder el cuestionario de adopción.',
                                'Aceptar una visita y entrevista en tu hogar.',
                                'Ventanas, balcones y azoteas protegidos con malla o red.',
                                'Compromiso de esterilizar al gato cuando tenga la edad adecuada.',
                                'No practicarle la onicectomía (amputación de las falanges).',
                                'Mantenerlo dentro de casa, sin "salidas libres" a la calle.',
                                'Aceptar el seguimiento posterior a la adopción.',
                            ]),
                        ],
                    ],
                    'process' => [
                        'label' => 'Proceso de adopción',
                        'fields' => self::heading('Paso a paso', 'Así es el proceso.', 'Desde que envías el cuestionario hasta la entrega suelen pasar entre 5 y 10 días.') + [
                            'steps' => self::items('Pasos', ['title' => 'Título', 'text' => 'Texto'], [
                                ['title' => '1. Cuestionario', 'text' => 'Llena el cuestionario de adopción de esta página. Nos ayuda a conocer tu hogar y a recomendarte al gato adecuado.'],
                                ['title' => '2. Visita y entrevista', 'text' => 'Acordamos una visita a tu hogar para platicar con la familia y revisar juntos que el espacio sea seguro.'],
                                ['title' => '3. Carta compromiso', 'text' => 'Firmas un acuerdo sencillo: esterilización, no onicectomía, no abandono y avisarnos si algo cambia.'],
                                ['title' => '4. Entrega', 'text' => 'Te entregamos al gato con su historial médico y las indicaciones para los primeros días de adaptación.'],
                                ['title' => '5. Seguimiento', 'text' => 'Te contactamos después de la entrega para saber cómo va la adaptación y resolver dudas.'],
                            ]),
                        ],
                    ],
                    'cats' => [
                        'label' => 'Gatos en adopción',
                        'fields' => self::heading('Buscan hogar', 'Gatos en adopción.', 'Todos salen desparasitados y con revisión veterinaria; los adultos, además, esterilizados.') + [
                            'emptyTitle' => self::text('Título cuando no hay fichas', 'Por ahora no hay fichas publicadas'),
                            'emptyText' => self::textarea('Texto cuando no hay fichas', 'Publicamos cada rescate nuevo en redes. Puedes dejar tu cuestionario y te avisamos cuando haya un gato compatible con tu hogar.'),
                        ],
                    ],
                    'questionnaire' => [
                        'label' => 'Cuestionario de adopción',
                        'help' => 'Las respuestas llegan a Control · Contactos con el motivo "Cuestionario de adopción".',
                        'fields' => self::heading('Cuestionario', 'Cuestionario de adopción.', 'Responde con calma y con sinceridad: no hay respuestas "correctas", solo queremos conocer tu hogar.') + [
                            'questions' => self::lines('Preguntas (una por línea)', [
                                '¿En qué colonia y municipio vives?',
                                '¿Vives en casa o departamento? ¿Es propio o rentado?',
                                '¿Quiénes viven contigo? Indica edades. ¿Están todos de acuerdo con la adopción?',
                                '¿Tienes o has tenido otros animales? Cuéntanos de ellos.',
                                '¿Tus ventanas, balcones o azotea tienen malla o protección?',
                                '¿Dónde dormirá el gato y cuántas horas al día estará solo?',
                                '¿Qué harías si te mudas, cambias de trabajo o llega un bebé a la familia?',
                                '¿Cuentas con presupuesto para alimento, arena y veterinario?',
                                '¿Estás de acuerdo en esterilizarlo y en no practicarle la onicectomía?',
                                '¿Aceptas una visita a tu hogar y el seguimiento posterior a la adopción?',
                            ]),
                            'catLabel' => self::text('Etiqueta del campo "gato de interés"', 'Gato que te interesa (opcional)'),
                            'ageConfirm' => self::text('Texto de la casilla de edad', 'Confirmo que soy mayor de 18 años.'),
                            'button' => self::text('Texto del botón', 'Enviar cuestionario'),
                        ],
                    ],
                    'visit' => [
                        'label' => 'Visita y entrevista en el hogar',
                        'fields' => self::heading('Visita y entrevista', 'Conocemos tu hogar antes de la entrega.', 'No es una inspección: es una plática en tu casa para resolver dudas y prevenir accidentes.') + [
                            'list' => self::lines('Qué revisamos (uno por línea)', [
                                'Que no haya riesgo de caída o escape por ventanas, balcones o azotea.',
                                'Dónde estarán el arenero, el agua, la comida y su lugar de descanso.',
                                'Cómo será la convivencia con niñas, niños y otros animales.',
                                'Que toda la familia conozca los compromisos de la adopción.',
                            ]),
                        ],
                    ],
                    'followup' => [
                        'label' => 'Seguimiento posterior',
                        'fields' => self::heading('Seguimiento', 'No te dejamos solo después de la entrega.', 'El seguimiento es parte de la adopción. Sirve para acompañarte en la adaptación y actuar a tiempo si algo no va bien.') + [
                            'steps' => self::items('Momentos del seguimiento', ['title' => 'Título', 'text' => 'Texto'], [
                                ['title' => 'Primera semana', 'text' => 'Te escribimos para saber cómo comió, si ya usa el arenero y cómo se lleva con la familia.'],
                                ['title' => 'Al primer mes', 'text' => 'Pedimos fotos o video y revisamos el avance de vacunas y desparasitación.'],
                                ['title' => 'A los tres meses', 'text' => 'Confirmamos la esterilización si era cachorro y cerramos el seguimiento formal.'],
                                ['title' => 'Siempre', 'text' => 'Si algún día no puedes seguir cuidándolo, avísanos antes que cualquier otra cosa: lo recibimos de vuelta.'],
                            ]),
                        ],
                    ],
                    'onychectomy' => [
                        'label' => 'Información sobre onicectomía',
                        'help' => 'Aviso destacado. Debe dejar claro que es una amputación, no un corte de uñas.',
                        'fields' => [
                            'eyebrow' => self::text('Etiqueta', 'Información importante'),
                            'title' => self::text('Título', 'La onicectomía es una amputación, no un corte de uñas.'),
                            'text' => self::textarea('Explicación', 'La onicectomía, conocida como "desungulación" o "quitar las garras", no consiste en cortar ni retirar las uñas. Es una cirugía en la que se amputa la última falange de cada dedo: el hueso del que nace la uña. En una mano humana equivaldría a cortar cada dedo a la altura del último nudillo. No es un procedimiento estético ni inofensivo.'),
                            'consequencesTitle' => self::text('Título de consecuencias', 'Lo que provoca'),
                            'consequences' => self::lines('Consecuencias (una por línea)', [
                                'Dolor agudo tras la cirugía y, en muchos casos, dolor crónico de por vida.',
                                'Cambios en la forma de caminar, problemas de articulaciones y de espalda.',
                                'Mordidas y agresividad: el gato pierde su primera forma de defensa.',
                                'Rechazo al arenero, porque escarbar le duele.',
                            ]),
                            'alternativesTitle' => self::text('Título de alternativas', 'Lo que sí puedes hacer'),
                            'alternatives' => self::lines('Alternativas (una por línea)', [
                                'Rascadores verticales y horizontales en los lugares donde pasa más tiempo.',
                                'Corte de la punta de las uñas cada dos o tres semanas.',
                                'Juego diario para que descargue energía.',
                                'Protectores para muebles mientras aprende a usar el rascador.',
                            ]),
                            'note' => self::textarea('Nota final', 'Por eso es requisito de adopción comprometerse a no practicarla. Si un gato rasguña los muebles, tiene solución; una amputación no tiene marcha atrás.'),
                        ],
                    ],
                    'stories' => [
                        'label' => 'Historias felices',
                        'fields' => self::heading('Historias felices', 'Ya encontraron familia.', 'Cada adopción bien hecha es un gato menos en la calle y una razón para seguir.'),
                    ],
                    'cta' => [
                        'label' => 'Llamado final',
                        'fields' => [
                            'title' => self::text('Título', '¿Todo claro?'),
                            'text' => self::textarea('Texto', 'Llena el cuestionario y empecemos el proceso. Si tienes dudas, escríbenos antes.'),
                        ],
                    ],
                ],
            ],

            'asistencia' => [
                'label' => 'Asistencia y orientación',
                'url' => '/asistencia/',
                'sections' => [
                    'hero' => [
                        'label' => 'Encabezado',
                        'fields' => self::hero('Asistencia y orientación', 'Qué hacer', 'en cada caso.', 'No somos un albergue ni un servicio de emergencias, pero sí podemos decirte qué hacer, a dónde acudir y cómo denunciar cuando un animal está en riesgo.'),
                    ],
                    'cases' => [
                        'label' => 'Casos de orientación',
                        'help' => 'Los casos se administran en Control · Orientación.',
                        'fields' => self::heading('Orientación', 'Elige la situación que se parece a la tuya.', 'Abre cada caso para ver los pasos. Si el tuyo no aparece, escríbenos.'),
                    ],
                    'report' => [
                        'label' => 'Denuncias: cómo denunciar',
                        'fields' => self::heading('Denuncias', 'Cómo denunciar el maltrato animal.', 'El maltrato y la crueldad contra los animales están sancionados por la ley. Denunciar es un derecho de cualquier persona; no necesitas ser dueña o dueño del animal.') + [
                            'steps' => self::items('Pasos para denunciar', ['title' => 'Título', 'text' => 'Texto'], [
                                ['title' => '1. Pon a salvo lo urgente', 'text' => 'Si el animal está siendo agredido en ese momento o su vida corre peligro, llama al 911. No te expongas ni confrontes a la persona agresora.'],
                                ['title' => '2. Reúne evidencia', 'text' => 'Fotos y videos con fecha, ubicación exacta, descripción de los hechos y, si los hay, datos de testigos. Entre más concreta, mejor.'],
                                ['title' => '3. Presenta la denuncia', 'text' => 'Acude a la instancia que corresponde según el caso y el municipio (ver "A dónde acudir"). Pide que te entreguen un número de folio o acuse.'],
                                ['title' => '4. Da seguimiento', 'text' => 'Guarda el folio y pregunta por el avance. Si no hay respuesta, escríbenos con el número de folio y te orientamos sobre el siguiente paso.'],
                            ]),
                            'evidenceTitle' => self::text('Título de la lista de evidencia', 'Qué debe llevar tu denuncia'),
                            'evidence' => self::lines('Evidencia (una por línea)', [
                                'Dirección exacta o referencias claras del lugar.',
                                'Fecha y hora de los hechos.',
                                'Descripción del animal y de lo que ocurrió.',
                                'Fotografías o videos, sin editar.',
                                'Nombre o señas de la persona responsable, si los conoces.',
                            ]),
                        ],
                    ],
                    'where' => [
                        'label' => 'Denuncias: a dónde acudir',
                        'help' => 'Verifica con cada dependencia sus datos y horarios vigentes antes de publicar teléfonos.',
                        'fields' => self::heading('A dónde acudir', 'Depende del caso y del municipio.', 'Tizayuca pertenece a Hidalgo y Zumpango al Estado de México: las instancias cambian según dónde ocurran los hechos.') + [
                            'places' => self::items('Instancias', ['title' => 'Instancia', 'text' => 'Cuándo acudir y cómo contactarla'], [
                                ['title' => 'Emergencias 911', 'text' => 'Cuando la agresión está ocurriendo en ese momento o hay riesgo inmediato para el animal o para las personas.'],
                                ['title' => 'Denuncia anónima 089', 'text' => 'Para reportar hechos sin dar tu nombre. Sirve cuando temes represalias de la persona agresora.'],
                                ['title' => 'Ministerio Público', 'text' => 'Para presentar una denuncia formal por maltrato o crueldad animal. Lleva tu evidencia y una identificación; pide tu número de carpeta.'],
                                ['title' => 'Autoridad municipal de Tizayuca', 'text' => 'Para reportes de animales en situación de riesgo, abandono o condiciones insalubres dentro del municipio. Pregunta en Presidencia Municipal por el área de ecología o protección animal.'],
                                ['title' => 'Procuraduría ambiental estatal', 'text' => 'En Hidalgo y en el Estado de México las procuradurías de protección al ambiente reciben denuncias por maltrato animal. Para hechos en Zumpango corresponde la del Estado de México.'],
                            ]),
                            'note' => self::textarea('Nota', 'La Casa de los Gatos no es autoridad y no puede retirar animales de un domicilio. Lo que sí hacemos es orientarte para que tu denuncia esté bien presentada y acompañarte en el seguimiento.'),
                        ],
                    ],
                    'cta' => [
                        'label' => 'Llamado final',
                        'fields' => [
                            'title' => self::text('Título', '¿Tu caso no aparece aquí?'),
                            'text' => self::textarea('Texto', 'Escríbenos con la ubicación, una foto y lo que está pasando. Te decimos qué hacer.'),
                        ],
                    ],
                ],
            ],

            'directorio' => [
                'label' => 'Directorio',
                'url' => '/directorio/',
                'sections' => [
                    'hero' => [
                        'label' => 'Encabezado',
                        'help' => 'Las clínicas se administran en Control · Directorio de clínicas.',
                        'fields' => self::hero('Directorio', 'Clínicas veterinarias', 'de la zona.', 'Clínicas de Tizayuca y, para casos específicos, de Zumpango. En cada una indicamos qué tipo de servicio ofrece para que sepas a cuál acudir.'),
                    ],
                    'tizayuca' => [
                        'label' => 'Clínicas de Tizayuca',
                        'fields' => self::heading('Tizayuca', 'Clínicas veterinarias en Tizayuca.', 'Para consulta general, vacunación, esterilización y urgencias dentro del municipio.'),
                    ],
                    'zumpango' => [
                        'label' => 'Clínicas de Zumpango',
                        'fields' => self::heading('Zumpango', 'Para casos específicos, en Zumpango.', 'Cuando el caso requiere un servicio que no está disponible en Tizayuca: estudios, cirugías especializadas u hospitalización.'),
                    ],
                    'note' => [
                        'label' => 'Aviso y llamado final',
                        'fields' => [
                            'disclaimer' => self::textarea('Aviso', 'Este directorio es informativo. La Casa de los Gatos no cobra por aparecer en él ni recibe comisión. Confirma siempre horarios, costos y disponibilidad directamente con la clínica.'),
                            'title' => self::text('Título del llamado final', '¿Conoces una clínica que debería estar aquí?'),
                            'text' => self::textarea('Texto del llamado final', 'Escríbenos con el nombre, la dirección y los servicios que ofrece. La revisamos y la agregamos.'),
                        ],
                    ],
                ],
            ],

            'contacto' => [
                'label' => 'Contacto',
                'url' => '/contacto/',
                'sections' => [
                    'hero' => [
                        'label' => 'Encabezado',
                        'fields' => self::hero('Contacto', 'Escríbenos y', 'te acompañamos.', 'Adopción, jornadas de esterilización, orientación sobre un caso o una clínica para el directorio: cuéntanos qué necesitas y te respondemos.'),
                    ],
                    'intro' => [
                        'label' => 'Bloque informativo',
                        'fields' => [
                            'eyebrow' => self::text('Etiqueta', 'Antes de escribir'),
                            'title' => self::text('Título', 'Somos un equipo pequeño de voluntarios.'),
                            'text' => self::textarea('Texto', 'Respondemos en cuanto podemos, normalmente en menos de 48 horas. Si tu caso es urgente (un gato herido o una camada recién nacida), escríbenos directo por WhatsApp.'),
                            'list' => self::lines('Lista (una por línea)', [
                                'Adopciones con cuestionario, visita y seguimiento',
                                'Registro a jornadas de esterilización y TNR',
                                'Orientación para rescates y denuncias',
                                'Altas y correcciones del directorio de clínicas',
                            ]),
                            'formTitle' => self::text('Título del formulario', 'Cuéntanos tu caso'),
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Contenido de una página con los valores guardados sobre los de fábrica.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function get(string $page): array
    {
        $schema = self::schema()[$page] ?? null;
        if ($schema === null) {
            return [];
        }

        $stored = self::stored()[$page] ?? [];
        $content = [];

        foreach ($schema['sections'] as $sectionKey => $section) {
            foreach ($section['fields'] as $fieldKey => $field) {
                $saved = is_array($stored[$sectionKey] ?? null) && array_key_exists($fieldKey, $stored[$sectionKey])
                    ? $stored[$sectionKey][$fieldKey]
                    : null;

                $content[$sectionKey][$fieldKey] = $saved === null
                    ? $field['default']
                    : self::clean($field, $saved);
            }
        }

        return $content;
    }

    /**
     * Guarda una sección a partir de los datos del formulario del panel.
     */
    public static function saveSection(string $page, string $sectionKey, array $input): bool
    {
        $section = self::schema()[$page]['sections'][$sectionKey] ?? null;
        if ($section === null) {
            return false;
        }

        $all = self::stored();

        foreach ($section['fields'] as $fieldKey => $field) {
            $all[$page][$sectionKey][$fieldKey] = self::clean($field, $input[$fieldKey] ?? null);
        }

        self::$stored = $all;

        return SiteStorage::write(self::FILE, $all);
    }

    /**
     * Devuelve una sección a sus textos de fábrica.
     */
    public static function resetSection(string $page, string $sectionKey): bool
    {
        $all = self::stored();
        unset($all[$page][$sectionKey]);
        if (isset($all[$page]) && $all[$page] === []) {
            unset($all[$page]);
        }

        self::$stored = $all;

        return SiteStorage::write(self::FILE, $all);
    }

    /**
     * @return array<string, mixed>
     */
    private static function stored(): array
    {
        self::$stored ??= SiteStorage::read(self::FILE, []);

        return self::$stored;
    }

    /**
     * @param array<string, mixed> $field
     * @param mixed $value
     * @return mixed
     */
    private static function clean(array $field, $value)
    {
        switch ($field['type']) {
            case 'lines':
                if (is_string($value)) {
                    $value = preg_split('/\R+/', $value) ?: [];
                }
                if (!is_array($value)) {
                    return [];
                }

                return array_values(array_filter(
                    array_map(static fn($line): string => self::plain((string) $line), $value),
                    static fn(string $line): bool => $line !== ''
                ));

            case 'items':
                if (!is_array($value)) {
                    return [];
                }
                $items = [];
                foreach ($value as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    $item = [];
                    foreach (array_keys($field['fields']) as $sub) {
                        $item[$sub] = self::plain((string) ($row[$sub] ?? ''));
                    }
                    if (implode('', $item) !== '') {
                        $items[] = $item;
                    }
                }

                return $items;

            default:
                return self::plain((string) ($value ?? ''));
        }
    }

    /**
     * Los textos se guardan sin HTML: las plantillas escapan al imprimir.
     */
    private static function plain(string $value): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value) ?? '';

        return trim($value);
    }

    private static function text(string $label, string $default, string $help = ''): array
    {
        return ['type' => 'text', 'label' => $label, 'default' => $default, 'help' => $help];
    }

    private static function textarea(string $label, string $default, string $help = ''): array
    {
        return ['type' => 'textarea', 'label' => $label, 'default' => $default, 'help' => $help];
    }

    private static function image(string $label, string $default): array
    {
        return ['type' => 'image', 'label' => $label, 'default' => $default, 'help' => 'Ruta pública de la imagen. Cópiala desde Biblioteca de imágenes.'];
    }

    private static function lines(string $label, array $default): array
    {
        return ['type' => 'lines', 'label' => $label, 'default' => $default, 'help' => 'Un elemento por línea. Borra la línea para quitarlo.'];
    }

    /**
     * @param array<string, string> $fields Subcampo => etiqueta
     */
    private static function items(string $label, array $fields, array $default): array
    {
        return ['type' => 'items', 'label' => $label, 'fields' => $fields, 'default' => $default, 'help' => 'Deja un bloque vacío para eliminarlo. Al guardar aparecen bloques nuevos en blanco.'];
    }

    private static function heading(string $eyebrow, string $title, string $intro): array
    {
        return [
            'eyebrow' => self::text('Etiqueta', $eyebrow),
            'title' => self::text('Título', $title),
            'intro' => self::textarea('Texto introductorio', $intro),
        ];
    }

    private static function hero(string $eyebrow, string $title, string $accent, string $lead): array
    {
        return [
            'eyebrow' => self::text('Etiqueta', $eyebrow),
            'title' => self::text('Título', $title),
            'accent' => self::text('Título (parte destacada)', $accent),
            'lead' => self::textarea('Texto de presentación', $lead),
        ];
    }
}
