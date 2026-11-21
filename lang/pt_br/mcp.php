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
 * Brazilian Portuguese strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = 'Esta atividade ainda não foi publicada.';
$string['addinstance'] = 'Adicionar uma atividade MCP';
$string['attempt'] = 'Tentativa';
$string['attemptfinished'] = 'Sua tentativa foi enviada.';
$string['attemptinprogress'] = 'Sua tentativa está em andamento.';
$string['beginattempt'] = 'Iniciar avaliação';
$string['connectionanother'] = 'Precisa conectar outro ChatGPT ou Claude, ou autorizar sua conta novamente? Consulte as instruções para configurar uma nova conexão:';
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
$string['promptcoloursbootstrap'] = 'Para as cores do tema, use as variáveis CSS --bs-primary e --bs-secondary, com valores de fallback em var(), pois o HTML será exibido em um iframe isolado.';
$string['promptcolourslearnlingo'] = 'Para as cores do tema LearnLingo, use as variáveis CSS --bs-primary e --bs-accent, com valores de fallback em var(), pois o HTML será exibido em um iframe isolado.';
$string['promptdarkmode'] = 'O CSS DEVE oferecer suporte aos modos claro e escuro (dark mode): defina estilos claros como padrão, utilize @media (prefers-color-scheme: dark) para a preferência do sistema e considere as seleções explícitas [data-bs-theme="dark"] e [data-bs-theme="light"] quando disponíveis. O HTML é exibido em um iframe isolado e deve definir seus próprios estilos, sem depender dos atributos ou estilos herdados da página Moodle. Garanta fundos, textos, bordas e controles legíveis, com contraste adequado.';
$string['promptpage'] = 'Na conexão MCP com {$a->wwwroot}, no curso Moodle {$a->courseid}, edite a atividade EXISTENTE "{$a->name}" (ID do módulo {$a->cmid}, URL {$a->url}). Construa uma PÁGINA DE CONTEÚDO didático, não um quiz ou avaliação. Crie um documento HTML completo, acessível e responsivo, organizado em seções com títulos, explicações, exemplos práticos e elementos visuais úteis quando apropriado, incluindo CSS em <style> e JavaScript em <script> quando necessário. Não crie questões avaliativas, pontuação, gabaritos ou associações ao banco de questões. Use as ferramentas mod_mcp expostas pelo local_mcp para salvar um rascunho, mostre uma prévia e NÃO publique até minha aprovação.';
$string['promptquiz'] = 'Na conexão MCP com {$a->wwwroot}, no curso Moodle {$a->courseid}, edite a atividade mod_mcp EXISTENTE "{$a->name}" (ID do módulo {$a->cmid}, URL {$a->url}). Antes de criar questões ou conteúdo, pergunte ao professor quantas questões deseja gerar, se a ordem das questões deve ser embaralhada e se cada tentativa deve apresentar todas as questões ou apenas uma quantidade sorteada do total. Se optar por um subconjunto, pergunte quantas questões devem aparecer em cada tentativa e aguarde as respostas antes de prosseguir. Crie uma ATIVIDADE INTERATIVA, como quiz, exercício, desafio ou simulação, em um documento HTML completo, acessível e responsivo, com CSS em <style> e JavaScript em <script> quando necessário. Use as ferramentas mod_mcp expostas pelo local_mcp para salvar um rascunho. Para questões avaliativas, crie ou selecione PRIMEIRO questões nativas do banco de questões Moodle, obtenha os IDs reais e associe essas questões à atividade. Utilize os recursos nativos disponíveis para embaralhar e selecionar questões, sem colocar gabaritos, notas, correção ou sorteio de questões no HTML. Se alguma configuração solicitada não for suportada pelas ferramentas disponíveis, informe a limitação, sem inventar funcionalidades. Mostre uma prévia e NÃO publique até minha aprovação.';
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
