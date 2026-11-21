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

$string['activitynotpublished'] = 'Esta actividad todavía no se ha publicado.';
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
$string['draftsaved'] = 'Borrador guardado; el alumnado sigue viendo la revisión publicada.';
$string['editcontent'] = 'Editar contenido de la actividad';
$string['finishattempt'] = 'Enviar respuestas y finalizar';
$string['grade'] = 'Calificación máxima';
$string['gradescalenotsupported'] = 'Las experiencias interactivas admiten calificaciones por puntos o ninguna calificación, pero no escalas de evaluación.';
$string['manageactivity'] = 'Gestionar experiencia interactiva';
$string['mcp:addinstance'] = 'Añadir experiencias interactivas';
$string['mcp:manage'] = 'Editar y publicar experiencias interactivas';
$string['mcp:submit'] = 'Enviar respuestas de evaluación de la experiencia interactiva';
$string['mcp:view'] = 'Ver experiencias interactivas';
$string['missingcontent'] = 'Crear la actividad';
$string['missingcontenthelp'] = 'Crea el contenido de esta actividad con ChatGPT o Claude mediante las herramientas MCP de Moodle. El alumnado no podrá acceder hasta que se publique una revisión HTML válida.';
$string['modulename'] = 'Experiencia interactiva';
$string['modulename_help'] = 'Crea experiencias interactivas con HTML, CSS y JavaScript, gestionadas mediante ChatGPT o Claude a través de Moodle MCP.';
$string['modulenameplural'] = 'Experiencias interactivas';
$string['pluginadministration'] = 'Administración de la experiencia interactiva';
$string['pluginname'] = 'Experiencia interactiva';
$string['preview'] = 'Vista previa del borrador actual';
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
$string['promptcolours'] = 'Para los colores del tema {$a->theme}, utiliza las variables CSS {$a->variables} con valores alternativos en var(), porque el HTML se muestra en un iframe aislado.';
$string['promptdarkmode'] = 'El CSS DEBE admitir modos claro y oscuro: define los estilos claros como predeterminados, utiliza @media (prefers-color-scheme: dark) y contempla [data-bs-theme="dark"] y [data-bs-theme="light"] cuando estén disponibles. El HTML se muestra en un iframe aislado; define sus propios estilos sin depender de los atributos o estilos de la página Moodle. Garantiza un contraste suficiente en fondos, texto, bordes y controles.';
$string['promptselecthelp'] = 'Elige el tipo de actividad para generar las instrucciones adecuadas para ChatGPT o Claude.';
$string['promptselectinteractive'] = 'Interactiva (cuestionario, ejercicios...)';
$string['promptselectpage'] = 'Contenido (página)';
$string['promptselecttitle'] = '¿Qué deseas crear?';
$string['publish'] = 'Publicar para el alumnado';
$string['published'] = 'La actividad se ha publicado.';
$string['questionnotconfigured'] = 'No se han asociado preguntas calificables a esta actividad.';
$string['questions'] = 'Evaluación';
$string['response'] = 'Respuesta';
$string['revisionconflict'] = 'Otro autor ha modificado esta actividad. Vuelve a consultarla antes de guardar.';
$string['saveanswers'] = 'Guardar respuestas';
$string['savedraft'] = 'Guardar borrador';
$string['securecontent'] = 'El código personalizado se ejecuta sin acceso a la sesión de Moodle.';
$string['studentview'] = 'Vista del estudiante';
$string['submissionsclosed'] = 'Este intento ya ha finalizado.';
$string['trackinglimit'] = 'Se ha alcanzado el límite de eventos de interacción para esta actividad.';
$string['yourgrade'] = 'Tu calificación: {$a}';
