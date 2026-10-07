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
                                'Orientación para rescates, denuncias ante la autoridad correcta y atención veterinaria.',
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
                                ['title' => 'Asistencia y orientación', 'text' => 'Qué hacer si encontraste un gato herido, una camada o un caso de maltrato, y qué autoridad de Tizayuca atiende cada denuncia.', 'label' => 'Pedir orientación', 'url' => '/asistencia/'],
                                ['title' => 'Directorio', 'text' => 'Veterinarias, urgencias 24 h, tiendas de mascotas y estéticas de Tizayuca y, para casos específicos, de Zumpango.', 'label' => 'Ver clínicas', 'url' => '/directorio/'],
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
                        'label' => 'Denuncias: qué es y cómo denunciar',
                        'help' => 'En los textos largos deja una línea en blanco para separar párrafos.',
                        'fields' => self::heading('Denuncias', 'Denunciar para proteger.', 'Cuando un animal está en riesgo, saber a quién avisar es tan importante como avisar. Aquí explicamos qué es una denuncia y qué autoridad de Tizayuca atiende cada tipo de situación.') + [
                            'whatTitle' => self::text('Título "qué es"', '¿Qué es una denuncia?'),
                            'whatText' => self::textarea('Qué es una denuncia', "Una denuncia es el reporte que hace una persona ante una autoridad para informar de un hecho que puede afectar el bienestar de un animal, la salud pública, el ambiente o la seguridad de la comunidad. Por ejemplo, cuando un animal está abandonado, sin agua, lesionado, expuesto al clima, atrapado o en una posible situación de maltrato.\n\nEn Hidalgo, cualquier persona puede denunciar hechos que vayan en contra de la protección y el trato digno de los animales, ante el área municipal competente o ante la Procuraduría correspondiente. La ley también permite hacerlo de forma anónima."),
                            'howTitle' => self::text('Título "cómo denunciar"', 'Cómo denunciar en Tizayuca'),
                            'howIntro' => self::textarea('Introducción de "cómo denunciar"', 'El reporte se presenta ante la autoridad que corresponda según la situación observada. Para que sea útil, conviene incluir:'),
                            'howList' => self::lines('Qué incluir en el reporte (uno por línea)', [
                                'Qué ocurrió y dónde: calle, número, colonia y alguna referencia.',
                                'Cuándo ocurrió, o desde cuándo está pasando.',
                                'Cuántos animales están involucrados y sus características.',
                                'Fotografías, videos, ubicación o testimonios, si se tienen.',
                                'Si existe un riesgo inmediato para el animal o para las personas.',
                            ]),
                            'howNote' => self::textarea('Dónde se entrega', 'El reporte puede entregarse en el Ayuntamiento, el CECOBAM o el área competente. Si la situación es una emergencia en curso, se pide apoyo directo a Seguridad Ciudadana.'),
                        ],
                    ],
                    'where' => [
                        'label' => 'Denuncias: autoridades que intervienen',
                        'help' => 'Una tarjeta por autoridad. Verifica con cada dependencia sus datos vigentes antes de agregar teléfonos.',
                        'fields' => self::heading('A dónde acudir', 'Autoridades que intervienen.', 'Tizayuca no tiene una sola oficina para todo: cada autoridad atiende una parte distinta del problema.') + [
                            'places' => self::items('Autoridades', ['title' => 'Autoridad', 'text' => 'Qué hace', 'when' => 'Cuándo interviene'], [
                                ['title' => 'CECOBAM', 'text' => 'Centro de Control y Bienestar Animal Municipal. Atiende salud, protección y bienestar de los animales.', 'when' => 'Animales abandonados, lesionados, enfermos o en posible maltrato.'],
                                ['title' => 'Ecología y Medio Ambiente', 'text' => 'Atiende lo que afecta el entorno o las condiciones sanitarias del lugar.', 'when' => 'Basura, heces, malos olores, insalubridad o fauna silvestre.'],
                                ['title' => 'Juzgado Cívico', 'text' => 'Media conflictos vecinales y conoce faltas administrativas.', 'when' => 'Problemas entre vecinos: ladridos, olores, animales sueltos.'],
                                ['title' => 'Seguridad Ciudadana', 'text' => 'Primer respondiente: atiende el reporte inicial y canaliza a quien corresponda.', 'when' => 'Riesgo inmediato, flagrancia o peligro para personas o animales.'],
                                ['title' => 'Bomberos y Protección Civil', 'text' => 'Auxilio técnico en emergencias y rescates.', 'when' => 'Animal atrapado en coladera, pozo, azotea o sitio peligroso.'],
                                ['title' => 'Ministerio Público', 'text' => 'Investiga posibles delitos y puede abrir una carpeta de investigación.', 'when' => 'Envenenamiento, crueldad, lesiones intencionales o muerte del animal.'],
                            ]),
                            'note' => self::textarea('Nota', 'La Casa de los Gatos no es autoridad y no puede retirar animales de un domicilio. Lo que sí hacemos es orientarte para que tu denuncia esté bien presentada y acompañarte en el seguimiento.'),
                        ],
                    ],
                    'cecobam' => [
                        'label' => 'Denuncias: el CECOBAM',
                        'fields' => [
                            'title' => self::text('Título', 'El CECOBAM, más de cerca'),
                            'text' => self::textarea('Texto', 'CECOBAM significa Centro de Control y Bienestar Animal Municipal. Es el área encargada de perros y gatos en Tizayuca, y su meta es prevenir el abandono y apoyar en situaciones de riesgo, no solo recoger animales.'),
                            'listTitle' => self::text('Título de la lista', 'Entre sus tareas están'),
                            'list' => self::lines('Tareas (una por línea)', [
                                'Atender reportes de posible maltrato animal.',
                                'Valorar las condiciones de un animal en riesgo.',
                                'Realizar o apoyar campañas de vacunación y esterilización.',
                                'Promover la adopción responsable.',
                                'Canalizar casos a otras autoridades cuando hace falta.',
                            ]),
                        ],
                    ],
                    'responder' => [
                        'label' => 'Denuncias: Seguridad Ciudadana',
                        'fields' => [
                            'title' => self::text('Título', 'Seguridad Ciudadana: primer respondiente'),
                            'text' => self::textarea('Texto', 'Cuando la situación es urgente, Seguridad Ciudadana suele ser la primera autoridad en llegar. Protege a las personas, evita que el riesgo crezca y después canaliza el caso.'),
                            'listTitle' => self::text('Título de la lista', 'Atiende la urgencia y canaliza a'),
                            'routes' => self::items('Canalización', ['title' => 'Autoridad', 'text' => 'En qué caso'], [
                                ['title' => 'CECOBAM', 'text' => 'si es un tema de bienestar animal.'],
                                ['title' => 'Ecología', 'text' => 'si hay contaminación o insalubridad.'],
                                ['title' => 'Bomberos', 'text' => 'si el animal necesita rescate.'],
                                ['title' => 'Juzgado Cívico', 'text' => 'si hay un conflicto vecinal.'],
                                ['title' => 'Ministerio Público', 'text' => 'si hay indicios de un delito.'],
                            ]),
                        ],
                    ],
                    'civic' => [
                        'label' => 'Denuncias: Justicia Cívica',
                        'help' => 'Deja una línea en blanco para separar párrafos.',
                        'fields' => [
                            'title' => self::text('Título', 'Justicia Cívica y mediación'),
                            'text' => self::textarea('Texto', "El Juzgado Cívico no investiga delitos ni sustituye al Ministerio Público: ayuda a que vecinos dialoguen y lleguen a acuerdos. Por ejemplo, si unos perros ladran toda la noche y eso genera fricción entre vecinos, ahí interviene.\n\nPero si un animal está gravemente lesionado, fue envenenado o hay violencia de por medio, el caso ya no es solo mediación: ahí entran Seguridad Ciudadana, el CECOBAM y, de ser necesario, el Ministerio Público."),
                        ],
                    ],
                    'evidence' => [
                        'label' => 'Denuncias: qué reunir',
                        'fields' => self::heading('Antes de denunciar', 'Qué necesitas reunir para denunciar.', 'Entre mejor documentado esté tu reporte, más fácil es que la autoridad actúe. Antes de presentarlo, procura tener:') + [
                            'list' => self::lines('Qué reunir (uno por línea)', [
                                'Fotografías o videos claros de la situación.',
                                'Domicilio completo: calle, número, colonia y alguna referencia del lugar.',
                                'Fecha, hora y desde cuándo ocurre la situación.',
                                'Descripción del animal: especie, color, tamaño y señas particulares.',
                                'Nombre o datos de la persona responsable, si se conocen.',
                                'Testigos dispuestos a dar su nombre y contacto, si los hay.',
                                'Reporte o certificado veterinario, si el animal fue atendido.',
                                'Relación breve de los hechos: qué observaste primero y qué pasó después.',
                            ]),
                            'note' => self::textarea('Nota', 'No es necesario tenerlo todo: entrega lo que tengas, sin ponerte en riesgo, y describe solo lo que viste.'),
                        ],
                    ],
                    'summary' => [
                        'label' => 'Denuncias: en resumen',
                        'fields' => [
                            'eyebrow' => self::text('Etiqueta', 'En resumen'),
                            'text' => self::textarea('Texto', 'Denunciar es una forma de cuidar a los animales y a la comunidad. El CECOBAM atiende el bienestar animal, Ecología lo ambiental y sanitario, Justicia Cívica los conflictos vecinales, Seguridad Ciudadana responde primero en emergencias, Bomberos hace los rescates técnicos, y el Ministerio Público investiga los posibles delitos.'),
                            'source' => self::text('Fuente', 'Contenido informativo elaborado por La Casa de los Gatos, con base en el marco municipal y estatal de Tizayuca, Hidalgo.'),
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
                        'help' => 'Los lugares se administran en Control · Directorio · Clínicas.',
                        'fields' => self::hero('Directorio', 'Veterinarias y servicios', 'de la zona.', 'Clínicas veterinarias, tiendas de mascotas y estéticas de Tizayuca y, para casos específicos, de Zumpango, con dirección, teléfono y horario de cada lugar.'),
                    ],
                    'tizayuca' => [
                        'label' => 'Clínicas de Tizayuca',
                        'fields' => self::heading('Tizayuca, Hgo.', 'Veterinarias y servicios en Tizayuca.', 'Clínicas y veterinarias, tiendas de mascotas y estéticas dentro del municipio, incluido el CECOBAM y una clínica de urgencias abierta las 24 horas.'),
                    ],
                    'zumpango' => [
                        'label' => 'Clínicas de Zumpango',
                        'fields' => self::heading('Zumpango, Edo. Méx.', 'Para casos específicos, en Zumpango.', 'Clínicas y hospitales veterinarios de Zumpango de Ocampo, para cuando el caso requiere un servicio que no encuentras en Tizayuca.'),
                    ],
                    'note' => [
                        'label' => 'Aviso y llamado final',
                        'fields' => [
                            'disclaimer' => self::textarea('Aviso', 'Este directorio es informativo. La Casa de los Gatos no cobra por aparecer en él ni recibe comisión. Confirma siempre horarios, costos y disponibilidad directamente con la clínica.'),
                            'title' => self::text('Título del llamado final', '¿Conoces un lugar que debería estar aquí?'),
                            'text' => self::textarea('Texto del llamado final', 'Escríbenos con el nombre, la dirección y el teléfono. Lo revisamos y lo agregamos.'),
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
