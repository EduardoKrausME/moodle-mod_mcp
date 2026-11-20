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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activitynotpublished'] = 'Esta atividade ainda não foi publicada.';
$string['addinstance'] = 'Adicionar uma atividade MCP';
$string['attempt'] = 'Tentativa';
$string['attemptfinished'] = 'Sua tentativa foi enviada.';
$string['attemptinprogress'] = 'Sua tentativa está em andamento.';
$string['beginattempt'] = 'Iniciar avaliação';
$string['connectionanother'] = 'Precisa conectar outro ChatGPT ou Claude, ou autorizar sua conta novamente? Consulte as instruções para configurar uma nova conexão:';
$string['connectionconfigured'] = 'Sua conta Moodle já possui uma conexão MCP ativa. Abra o ChatGPT ou Claude em que a conexão foi autorizada e cole o prompt abaixo para criar o rascunho.';
$string['connectionnew'] = 'Configurar conexão MCP';
$string['connectionrequired'] = 'Sua conta Moodle ainda não possui uma conexão MCP ativa. Primeiro conecte o ChatGPT ou Claude a este Moodle e autorize o acesso com a sua conta; depois volte a esta atividade e use o prompt abaixo.';
$string['content'] = 'Documento HTML (incluindo CSS e JavaScript)';
$string['content_help'] = 'Utilize um documento HTML completo com estilos e scripts internos. Os scripts são executados em um iframe isolado.';
$string['copyinstructions'] = 'Copie este prompt para construir a atividade no ChatGPT ou Claude:';
$string['draftsaved'] = 'Rascunho salvo; os estudantes continuam vendo a versão publicada.';
$string['editcontent'] = 'Editar conteúdo da atividade';
$string['finishattempt'] = 'Enviar respostas e concluir';
$string['grade'] = 'Nota máxima';
$string['manageactivity'] = 'Gerenciar atividade MCP';
$string['mcp:addinstance'] = 'Adicionar atividades MCP';
$string['mcp:manage'] = 'Editar e publicar atividades MCP';
$string['mcp:submit'] = 'Enviar respostas da avaliação MCP';
$string['mcp:view'] = 'Visualizar atividades MCP';
$string['missingcontent'] = 'Construa a atividade';
$string['missingcontenthelp'] = 'Crie o conteúdo desta atividade com o ChatGPT ou Claude, usando as ferramentas do Moodle MCP. Até que uma versão HTML válida seja publicada, os estudantes não poderão acessá-la.';
$string['modulename'] = 'Atividade MCP';
$string['modulename_help'] = 'Crie atividades interativas com um único documento HTML, gerenciado pelo ChatGPT ou Claude por meio do Moodle MCP.';
$string['modulenameplural'] = 'Atividades MCP';
$string['pluginadministration'] = 'Administração da atividade MCP';
$string['pluginname'] = 'Atividade MCP';
$string['preview'] = 'Visualizar rascunho atual';
$string['privacy:metadata:mcp_attempts'] = 'Armazena o usuário e o estado de cada tentativa de avaliação e sua utilização nativa de questões.';
$string['privacy:metadata:mcp_attempts:userid'] = 'O usuário que realizou a tentativa.';
$string['privacy:metadata:mcp_revisions'] = 'Armazena o autor de cada revisão HTML.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'O usuário que criou a revisão.';
$string['prompt'] = 'No curso Moodle {$a->courseid}, edite a atividade mod_mcp EXISTENTE "{$a->name}" (ID do módulo {$a->cmid}, URL {$a->url}). Crie uma ATIVIDADE INTERATIVA, como quiz, exercício, desafio ou simulação, em um documento HTML completo, acessível e responsivo, com CSS em <style> e JavaScript em <script>. Use as ferramentas mod_mcp expostas pelo local_mcp para salvar um rascunho. Para questões com nota, crie ou selecione PRIMEIRO questões nativas do banco de questões Moodle, obtenha os IDs reais e associe essas questões à atividade; nunca coloque gabaritos ou notas no HTML. Mostre uma prévia e NÃO publique até minha aprovação.';
$string['promptpage'] = 'No curso Moodle {$a->courseid}, edite a atividade mod_mcp EXISTENTE "{$a->name}" (ID do módulo {$a->cmid}, URL {$a->url}). Construa uma PÁGINA DE CONTEÚDO didático, não um quiz ou avaliação. Crie um documento HTML completo, acessível e responsivo, organizado em seções com títulos, explicações, exemplos práticos e elementos visuais úteis quando apropriado, incluindo CSS em <style> e JavaScript em <script> quando necessário. Não crie questões avaliativas, pontuação, gabaritos ou associações ao banco de questões. Use as ferramentas mod_mcp expostas pelo local_mcp para salvar um rascunho, mostre uma prévia e NÃO publique até minha aprovação.';
$string['promptselecthelp'] = 'Escolha o tipo de atividade para gerar o prompt adequado ao ChatGPT ou Claude.';
$string['promptselectinteractive'] = 'Interativo (quiz, exercícios...)';
$string['promptselectpage'] = 'Conteúdo (página)';
$string['promptselecttitle'] = 'O que você quer construir?';
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
