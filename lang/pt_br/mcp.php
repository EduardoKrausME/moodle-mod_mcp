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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die();

$string['activitynotpublished'] = 'Esta atividade ainda não foi publicada.';
$string['addinstance'] = 'Adicionar uma atividade MCP';
$string['attempt'] = 'Tentativa';
$string['attemptfinished'] = 'Sua tentativa foi enviada.';
$string['attemptinprogress'] = 'Sua tentativa está em andamento.';
$string['beginattempt'] = 'Iniciar avaliação';
$string['content'] = 'Documento HTML (incluindo CSS e JavaScript)';
$string['content_help'] = 'Utilize um documento HTML completo com estilos e scripts internos. Os scripts são executados em um iframe isolado.';
$string['copyinstructions'] = 'Copie este prompt para o ChatGPT ou Claude, conectado a este Moodle:';
$string['draftsaved'] = 'Rascunho salvo; os estudantes continuam vendo a versão publicada.';
$string['editcontent'] = 'Editar conteúdo da atividade';
$string['finishattempt'] = 'Enviar respostas e concluir';
$string['grade'] = 'Nota máxima';
$string['manageactivity'] = 'Gerenciar atividade MCP';
$string['missingcontent'] = 'Esta atividade está sendo construída';
$string['missingcontenthelp'] = 'A atividade permanece oculta aos estudantes até a publicação de um HTML válido. Abra o ChatGPT ou Claude, conecte ao Moodle usando o local_mcp e cole o prompt abaixo.';
$string['modulename'] = 'Atividade MCP';
$string['modulename_help'] = 'Crie atividades interativas com um único documento HTML, gerenciado pelo ChatGPT ou Claude por meio do Moodle MCP.';
$string['modulenameplural'] = 'Atividades MCP';
$string['mcp:addinstance'] = 'Adicionar atividades MCP';
$string['mcp:manage'] = 'Editar e publicar atividades MCP';
$string['mcp:submit'] = 'Enviar respostas da avaliação MCP';
$string['mcp:view'] = 'Visualizar atividades MCP';
$string['pluginadministration'] = 'Administração da atividade MCP';
$string['pluginname'] = 'Atividade MCP';
$string['preview'] = 'Visualizar rascunho atual';
$string['privacy:metadata:mcp_attempts'] = 'Armazena o usuário e o estado de cada tentativa de avaliação e sua utilização nativa de questões.';
$string['privacy:metadata:mcp_attempts:userid'] = 'O usuário que realizou a tentativa.';
$string['privacy:metadata:mcp_revisions'] = 'Armazena o autor de cada revisão HTML.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'O usuário que criou a revisão.';
$string['publish'] = 'Publicar para estudantes';
$string['published'] = 'A atividade foi publicada.';
$string['questionnotconfigured'] = 'Nenhuma questão avaliativa foi associada à atividade.';
$string['questions'] = 'Avaliação';
$string['response'] = 'Resposta';
$string['revisionconflict'] = 'Outro autor modificou esta atividade. Consulte a versão atual antes de salvar.';
$string['saveanswers'] = 'Salvar respostas';
$string['securecontent'] = 'O código personalizado é executado sem acesso à sessão do Moodle.';
$string['studentview'] = 'Visualização do estudante';
$string['submissionsclosed'] = 'Esta tentativa já foi finalizada.';
$string['yourgrade'] = 'Sua nota: {$a}';
$string['prompt'] = 'No curso Moodle {$a->courseid}, edite a atividade mod_mcp EXISTENTE "{$a->name}" (ID do módulo {$a->cmid}, URL {$a->url}). Crie um documento HTML completo, acessível e responsivo, com CSS em <style> e JavaScript em <script>. Use as ferramentas mod_mcp expostas pelo local_mcp para salvar um rascunho. Para questões com nota, crie ou selecione PRIMEIRO questões nativas do banco de questões Moodle, obtenha os IDs reais e associe essas questões à atividade; nunca coloque gabaritos ou notas no HTML. Mostre uma prévia e NÃO publique até minha aprovação.';

