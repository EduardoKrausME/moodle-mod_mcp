<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Spanish strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = 'Esta actividad todavía no tiene contenido.';
$string['addinstance'] = 'Añadir una experiencia interactiva';
$string['attempt'] = 'Intento';
$string['attemptfinished'] = 'Se ha enviado tu intento.';
$string['attemptinprogress'] = 'Tu intento está en curso.';
$string['beginattempt'] = 'Iniciar evaluación';
$string['completioninteractive'] = 'Cuando el documento interactivo solicite la finalización';
$string['completionnotallowed'] = 'Las reglas actuales de finalización no permiten completar esta actividad desde el documento.';
$string['connectionanother'] = '¿Necesitas conectar otra aplicación de ChatGPT o Claude o autorizar de nuevo tu cuenta? Sigue estas instrucciones:';
$string['connectionnew'] = 'Configurar conexión MCP';
$string['connectionrequired'] = 'Tu cuenta de Moodle aún no tiene una conexión MCP activa. Conecta ChatGPT o Claude a este sitio Moodle, autoriza el acceso con tu cuenta y vuelve para utilizar las instrucciones siguientes.';
$string['content'] = 'Documento HTML (incluidos CSS y JavaScript)';
$string['content_help'] = 'Utiliza un documento HTML completo con estilos y scripts integrados. Los scripts se ejecutan en un iframe aislado.';
$string['copyinstructions'] = 'Copia estas instrucciones en ChatGPT o Claude para crear la actividad:';
$string['editcontent'] = 'Editar contenido de la actividad';
$string['finishattempt'] = 'Enviar respuestas y finalizar';
$string['grade'] = 'Calificación máxima';
$string['gradescalenotsupported'] = 'Las experiencias interactivas admiten calificaciones por puntos o ninguna calificación, pero no escalas de evaluación.';
$string['manageactivity'] = 'Gestionar experiencia interactiva';
$string['mcp:addinstance'] = 'Añadir experiencias interactivas';
$string['mcp:manage'] = 'Editar experiencias interactivas';
$string['mcp:submit'] = 'Enviar respuestas de evaluación de la experiencia interactiva';
$string['mcp:view'] = 'Ver experiencias interactivas';
$string['missingcontent'] = 'Crear la actividad';
$string['missingcontenthelp'] = 'Crea el contenido de esta actividad con ChatGPT o Claude mediante las herramientas MCP de Moodle. Al guardar, se aplica la visibilidad nativa de Moodle.';
$string['modulename'] = 'Experiencia interactiva';
$string['modulename_help'] = 'Crea experiencias interactivas con HTML, CSS y JavaScript, gestionadas mediante ChatGPT o Claude a través de Moodle MCP.';
$string['modulenameplural'] = 'Experiencias interactivas';
$string['pluginadministration'] = 'Administración de la experiencia interactiva';
$string['pluginname'] = 'Experiencia interactiva';
$string['preview'] = 'Vista previa del contenido';
$string['privacy:metadata:mcp_attempts'] = 'Almacena el usuario, el estado de cada intento de evaluación y su uso de preguntas nativas.';
$string['privacy:metadata:mcp_attempts:userid'] = 'Usuario que envió el intento.';
$string['privacy:metadata:mcp_revisions'] = 'Almacena el autor de cada revisión HTML.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'Usuario que creó esta revisión.';
$string['privacy:metadata:mcp_state'] = 'Almacena los datos de reanudación por usuario y la marca de finalización personalizada.';
$string['privacy:metadata:mcp_state:statejson'] = 'Datos de progreso introducidos por el estudiante en el documento interactivo.';
$string['privacy:metadata:mcp_state:userid'] = 'Usuario al que pertenece el progreso guardado.';
$string['privacy:metadata:mcp_tracking'] = 'Almacena eventos limitados de interacción enviados desde el documento.';
$string['privacy:metadata:mcp_tracking:datajson'] = 'Datos de los resultados de eventos generados por la interacción del estudiante.';
$string['privacy:metadata:mcp_tracking:userid'] = 'Usuario que interactuó con la actividad.';
$string['progressconflict'] = 'El progreso se ha actualizado en otra ventana. Vuelve a cargarlo antes de guardar.';
$string['promptcolours'] = 'REGLA OBLIGATORIA DE COLORES DE MARCA del tema {$a->theme}: las variables {$a->variables} son las únicas fuentes de colores de marca. No inventes otra paleta ni fijes colores hexadecimales, rgb()/rgba(), hsl()/hsla() ni nombres de colores para secciones destacadas, degradados y todas sus paradas, botones, enlaces, bordes, iconos, tarjetas, estados hover/focus o pseudoelementos. Todos los colores de marca DEBEN utilizar var({$a->primary}, #0d6efd) y var({$a->complementary}, var({$a->primary}, #0d6efd)), o tokens locales derivados de estas variables. Ejemplo válido: .hero { background: linear-gradient(123deg, var({$a->primary}, #0d6efd), var({$a->complementary}, var({$a->primary}, #0d6efd))); }. Para tonos intermedios y transparencias utiliza color-mix() con esas variables. Los colores literales solo se permiten como fallback dentro de var() o en tonos neutros y estados semánticos de accesibilidad, nunca para sustituir colores de marca. Revisa todo el CSS, incluidas media queries y pseudoelementos, y elimina colores de marca inventados antes de entregar el HTML. mod_mcp transmite al iframe aislado de Moodle los valores actuales de las variables del tema; los fallbacks siguen siendo necesarios en vistas previas independientes y en la app.';
$string['promptdarkmode'] = 'El CSS DEBE admitir modos claro y oscuro: define los estilos claros como predeterminados, utiliza @media (prefers-color-scheme: dark) y contempla [data-bs-theme="dark"] y [data-bs-theme="light"] cuando estén disponibles. El HTML se muestra en un iframe aislado; define sus propios estilos sin depender de los atributos o estilos de la página Moodle. Garantiza un contraste suficiente en fondos, texto, bordes y controles.';
$string['promptdescriptioncase'] = 'Crea un estudio de caso en el que los estudiantes toman decisiones, exploran consecuencias y reciben comentarios sobre sus elecciones. ChatGPT o Claude preguntará: «¿Qué escenario y contexto real?», «¿Quién es el público y cuál es el objetivo de aprendizaje?», «¿Cuántas decisiones incluir?», «¿Se puede volver atrás y reconsiderar elecciones?» y «¿Hay documentos o ejemplos de referencia?». Los caminos deben tener ramificaciones consistentes y comentarios explicativos.';
$string['promptdescriptioncustom'] = 'Crea la actividad que imaginas, sin limitarte a las demás pestañas. ChatGPT o Claude preguntará primero: «¿Qué tipo de actividad quieres crear?». Según tu respuesta, preguntará: «¿Cómo debería funcionar?», «¿Cuáles son el contenido, el público y el objetivo de aprendizaje?», «¿Qué interacciones debe incluir?» y «¿Tienes documentos, imágenes u otras referencias?». Esperará tus respuestas y materiales antes de crear el contenido, y puedes proponer más ideas.';
$string['promptdescriptioninteractive'] = 'Crea un cuestionario interactivo con preguntas en orden fijo o aleatorio, calificadas por Moodle. ChatGPT o Claude preguntará: «¿Qué tema, público y objetivo de aprendizaje?», «¿Cuántas preguntas quieres?», «¿Deben mezclarse?», «¿Cuántas se eligen al azar por intento?» y «¿Tienes archivos o material de referencia?». Las respuestas correctas se guardan en JSON del servidor, nunca en el HTML que ve el estudiante, para mantener la evaluación segura.';
$string['promptdescriptionpage'] = 'Crea una página educativa con explicaciones, ejemplos e imágenes, sin calificaciones ni preguntas de evaluación. ChatGPT o Claude preguntará: «¿Prefieres una lección explicativa, tutorial paso a paso, resumen, estudio de caso u otro formato?», «¿Cuál es el tema, el público y el objetivo de aprendizaje?», «¿Tienes instrucciones específicas?» y «¿Dispones de PDF, imágenes u otro material de referencia?». El contenido seguirá tus respuestas sin inventar fuentes.';
$string['promptdescriptionsimulation'] = 'Crea una simulación interactiva en la que los estudiantes cambian parámetros y observan resultados, gráficos o diagramas. ChatGPT o Claude preguntará: «¿Qué fenómeno o proceso simular?», «¿Para qué público?», «¿Qué valores pueden cambiar los estudiantes?», «¿Qué unidades, fórmulas y límites seguros se aplican?» y «¿Qué resultados mostrar?». Si faltan reglas fiables, solicitará fuentes y supuestos antes de construir el modelo, sin inventar cálculos.';
$string['promptdescriptionsurvey'] = 'Crea una encuesta de satisfacción que presenta una pregunta por vez y guarda respuestas sin calificaciones. ChatGPT o Claude preguntará: «¿Cuál es el objetivo de la encuesta?», «¿Quién responderá?», «¿Cuántas preguntas?», «¿Qué escalas o formatos de respuesta utilizar?», «¿Qué respuestas son obligatorias?» y «¿Necesitas informes o anonimato?». Los informes consolidados y el anonimato no están garantizados sin soporte adicional del servidor.';
$string['promptdescriptiontimeline'] = 'Crea una línea de tiempo explorable con acontecimientos ordenados por fecha y detalles consultables. ChatGPT o Claude preguntará: «¿Qué tema y período?», «¿Cuáles son los acontecimientos clave?», «¿Para qué público?», «¿Prefieres tarjetas de eventos, detalles desplegables o filtros?» y «¿Tienes fuentes o materiales de referencia?». Las fechas y demás datos deben poder verificarse, sin inventar acontecimientos ni referencias.';
$string['promptfreedom'] = '<strong>Estas son solo sugerencias:</strong> no tienes que limitarte a ellas. Pide a la inteligencia artificial que añada otras funciones, interacciones o cualquier idea que desees. El límite es tu imaginación.';
$string['promptimagealt'] = 'Ejemplo visual de {$a}.';
$string['promptselectcase'] = 'Estudio de caso con decisiones';
$string['promptselectcustom'] = 'Yo elijo';
$string['promptselecthelp'] = 'Elige el tipo de actividad para generar las instrucciones adecuadas para ChatGPT o Claude.';
$string['promptselectinteractive'] = 'Interactiva (cuestionario, ejercicios...)';
$string['promptselectpage'] = 'Contenido (página)';
$string['promptselectsimulation'] = 'Simulación interactiva';
$string['promptselectsurvey'] = 'Encuesta de satisfacción';
$string['promptselecttimeline'] = 'Línea de tiempo';
$string['promptselecttitle'] = '¿Qué deseas crear?';
$string['questionnotconfigured'] = 'No se han asociado preguntas calificables a esta actividad.';
$string['questions'] = 'Evaluación';
$string['response'] = 'Respuesta';
$string['revisionconflict'] = 'Otro autor ha modificado esta actividad. Vuelve a consultarla antes de guardar.';
$string['saveanswers'] = 'Guardar respuestas';
$string['securecontent'] = 'El código personalizado se ejecuta sin acceso a la sesión de Moodle.';
$string['studentview'] = 'Vista del estudiante';
$string['submissionsclosed'] = 'Este intento ya ha finalizado.';
$string['trackinglimit'] = 'Se ha alcanzado el límite de eventos de interacción para esta actividad.';
$string['yourgrade'] = 'Tu calificación: {$a}';
