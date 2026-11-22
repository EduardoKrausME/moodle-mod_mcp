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
$string['addinstance'] = 'Adicionar uma experiência interativa';
$string['attempt'] = 'Tentativa';
$string['attemptfinished'] = 'Sua tentativa foi enviada.';
$string['attemptinprogress'] = 'Sua tentativa está em andamento.';
$string['beginattempt'] = 'Iniciar avaliação';
$string['completioninteractive'] = 'Quando o documento interativo solicitar a conclusão';
$string['completionnotallowed'] = 'As regras de conclusão desta atividade não permitem concluí-la por meio do documento.';
$string['connectionanother'] = 'Precisa conectar outro ChatGPT ou Claude, ou autorizar sua conta novamente? Consulte as instruções para configurar uma nova conexão:';
$string['connectionnew'] = 'Configurar conexão MCP';
$string['connectionrequired'] = 'Sua conta Moodle ainda não possui uma conexão MCP ativa. Primeiro conecte o ChatGPT ou Claude a este Moodle e autorize o acesso com a sua conta; depois volte a esta atividade e use o prompt abaixo.';
$string['content'] = 'Documento HTML (incluindo CSS e JavaScript)';
$string['content_help'] = 'Utilize um documento HTML completo com estilos e scripts internos. Os scripts são executados em um iframe isolado.';
$string['copyinstructions'] = 'Copie este prompt para construir a atividade no ChatGPT ou Claude:';
$string['draftsaved'] = 'Rascunho salvo; os estudantes continuam vendo a versão publicada.';
$string['duplicatequestion'] = 'A mesma questão do banco não pode ser vinculada duas vezes à mesma avaliação.';
$string['editcontent'] = 'Editar conteúdo da atividade';
$string['finishattempt'] = 'Enviar respostas e concluir';
$string['grade'] = 'Nota máxima';
$string['gradescalenotsupported'] = 'Experiências interativas aceitam notas em pontos ou ausência de nota, mas não escalas de avaliação.';
$string['invalidquestionconfiguration'] = 'A configuração do sorteio é inválida ou uma versão de questão vinculada está indisponível.';
$string['invalidquestioncount'] = 'Defina uma quantidade válida, que não ultrapasse as questões distintas disponíveis.';
$string['manageactivity'] = 'Gerenciar experiência interativa';
$string['mcp:addinstance'] = 'Adicionar experiências interativas';
$string['mcp:manage'] = 'Editar e publicar experiências interativas';
$string['mcp:submit'] = 'Enviar respostas de avaliações da experiência interativa';
$string['mcp:view'] = 'Visualizar experiências interativas';
$string['missingcontent'] = 'Construa a atividade';
$string['missingcontenthelp'] = 'Crie o conteúdo desta atividade com o ChatGPT ou Claude, usando as ferramentas do Moodle MCP. Até que uma versão HTML válida seja publicada, os estudantes não poderão acessá-la.';
$string['modulename'] = 'Experiência Interativa';
$string['modulename_help'] = 'Crie experiências interativas com HTML, CSS e JavaScript, gerenciadas pelo ChatGPT ou Claude por meio do Moodle MCP.';
$string['modulenameplural'] = 'Experiências Interativas';
$string['pluginadministration'] = 'Administração da experiência interativa';
$string['pluginname'] = 'Experiência Interativa';
$string['preview'] = 'Visualizar rascunho atual';
$string['privacy:metadata:mcp_attempts'] = 'Armazena o usuário e o estado de cada tentativa de avaliação e sua utilização nativa de questões.';
$string['privacy:metadata:mcp_attempts:userid'] = 'O usuário que realizou a tentativa.';
$string['privacy:metadata:mcp_revisions'] = 'Armazena o autor de cada revisão HTML.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'O usuário que criou a revisão.';
$string['privacy:metadata:mcp_state'] = 'Armazena dados de retomada por usuário e o indicador de conclusão personalizada.';
$string['privacy:metadata:mcp_state:statejson'] = 'Dados de progresso informados pelo aluno no documento interativo.';
$string['privacy:metadata:mcp_state:userid'] = 'Usuário proprietário do progresso.';
$string['privacy:metadata:mcp_tracking'] = 'Armazena eventos de interação limitados enviados pelo documento.';
$string['privacy:metadata:mcp_tracking:datajson'] = 'Dados dos eventos gerados durante as interações do aluno.';
$string['privacy:metadata:mcp_tracking:userid'] = 'Usuário que interagiu com a atividade.';
$string['progressconflict'] = 'O progresso foi atualizado em outra janela. Recarregue antes de salvar.';
$string['promptcolours'] = 'Para as cores do tema {$a->theme}, use as variáveis CSS {$a->variables}, com valores de fallback em var(), pois o HTML será exibido em um iframe isolado.';
$string['promptdarkmode'] = 'O CSS DEVE oferecer suporte aos modos claro e escuro (dark mode): defina estilos claros como padrão, utilize @media (prefers-color-scheme: dark) para a preferência do sistema e considere as seleções explícitas [data-bs-theme="dark"] e [data-bs-theme="light"] quando disponíveis. O HTML é exibido em um iframe isolado e deve definir seus próprios estilos, sem depender dos atributos ou estilos herdados da página Moodle. Garanta fundos, textos, bordas e controles legíveis, com contraste adequado.';
$string['promptinstructions'] = 'Antes de gerar qualquer conteúdo, questões, HTML, CSS, JavaScript ou salvar um rascunho, PERGUNTE AO PROFESSOR o que ele deseja criar nesta atividade, quais assuntos devem ser abordados e qual é o objetivo do material. Não deduza o conteúdo apenas pelo título da atividade. Pergunte também se ele possui algum documento, PDF, imagens ou outros materiais de referência para usar como ponto de partida; se possuir, solicite que envie ou compartilhe os arquivos e analise o conteúdo antes de prosseguir, sem inventar informações de materiais indisponíveis. Aguarde a resposta do professor e os materiais que ele decidir fornecer antes de planejar ou criar qualquer coisa. Depois disso, faça as demais perguntas necessárias para configurar a atividade, se houver, e só então inicie a criação. Esta atividade será incorporada na página EXISTENTE do Moodle, que já exibe o nome do curso, o título da atividade, a navegação e os elementos do tema. Use o endereço do Moodle, os IDs de curso e módulo, o nome e a URL da atividade SOMENTE como contexto técnico para encontrar e editar a atividade com as ferramentas MCP. NÃO reproduza esses dados no HTML visível ao aluno, nem crie faixa superior de identificação, cabeçalho institucional, capa, breadcrumb, menu ou barra de navegação que repita a interface do Moodle. NÃO crie NENHUM RODAPÉ no HTML, mesmo que seja diferente do rodapé do Moodle: não use a tag <footer> nem seções, divs ou faixas visuais que funcionem como rodapé. Não acrescente créditos, assinatura, copyright, mensagens finais institucionais, nome do autor, links de suporte ou navegação inferior. Se houver rodapé no documento de referência, não o copie. Antes de salvar o rascunho, revise e remova qualquer rodapé; termine a página diretamente na última seção didática ou no último controle interativo necessário. Não inclua cartões, etiquetas, badges ou textos como "Moodle", "Curso", "ID do curso", "ID do módulo" ou o URL da atividade como metadados. Não repita o título da atividade no início do HTML apenas porque ele aparece no prompt. O HTML deve começar diretamente pelo conteúdo pedagógico solicitado, com títulos internos apenas quando úteis à organização didática. Preserve o documento HTML completo e seus estilos próprios para o iframe isolado, sem duplicar o layout externo da plataforma. O documento interativo possui o SDK assíncrono window.MoodleMCP dentro do iframe isolado. Use MoodleMCP.course.getActivities() e getNavigation() para navegação autorizada no curso; MoodleMCP.completion.get() e complete() para respeitar as regras nativas de conclusão configuradas; MoodleMCP.progress.load() e save({version, location, data}) para retomar o progresso; MoodleMCP.attempt.get()/start()/finish() e MoodleMCP.questions.get()/submit(responses, finish) para interações compatíveis com questões nativas; MoodleMCP.tracking.record({type, action, object, result}) para registrar eventos limitados. Essas operações são assíncronas e dependem das permissões do Moodle. Nunca inclua tokens de sessão, gabaritos ou correção de notas no HTML. Não presuma que a conclusão foi habilitada nem que os tipos de questão funcionam sem o JavaScript nativo do Moodle. A avaliação nativa permanece disponível como alternativa. As instruções deste prompt são destinadas exclusivamente à geração da página. NÃO devem ser transformadas em textos, avisos, etiquetas, legendas ou elementos visíveis no HTML.';
$string['promptpage'] = 'Na conexão MCP com {$a->wwwroot}, no curso Moodle {$a->courseid}, edite a atividade EXISTENTE "{$a->name}" (ID do módulo {$a->cmid}, URL {$a->url}). Na PRIMEIRA resposta, antes de qualquer HTML, planejamento ou ferramenta de escrita, pergunte ao professor que formato de página prefere: aula explicativa, tutorial passo a passo, resumo, estudo de caso ou outro. Ofereça opções selecionáveis E um campo de digitação em texto livre para o professor informar o tema, público, objetivo pedagógico e orientações específicas. Pergunte também se possui documentos, PDFs, imagens ou outros materiais de referência. Se a interface de conversa permitir escolhas e campo de texto, apresente ambos na mesma interação; caso contrário, mostre opções numeradas e convide a escrever uma resposta livre. Nunca limite a resposta a alternativas fechadas. Aguarde as respostas e os materiais disponibilizados antes de criar o conteúdo ou salvar um rascunho. Construa uma PÁGINA DE CONTEÚDO didático, não um quiz ou avaliação. Crie um documento HTML completo, acessível e responsivo, organizado em seções com títulos, explicações, exemplos práticos e elementos visuais úteis quando apropriado, incluindo CSS em <style> e JavaScript em <script> quando necessário. Depois de salvar o rascunho, mostre uma prévia e apresente na conversa dois botões de ação: Aprovar e Rejeitar (não inclua esses botões no HTML da atividade). Publique a atividade somente após minha aprovação explícita pelo botão Aprovar. Se eu escolher Rejeitar, mantenha o rascunho sem publicar e pergunte novas orientações para ajustes.';
$string['promptquiz'] = 'Na conexão MCP com {$a->wwwroot}, no curso Moodle {$a->courseid}, edite a atividade mod_mcp EXISTENTE "{$a->name}" (ID do módulo {$a->cmid}, URL {$a->url}). Antes de criar questões ou conteúdo, pergunte ao professor quantas questões deseja gerar, se a ordem das questões deve ser embaralhada e se cada tentativa deve apresentar todas as questões ou apenas uma quantidade sorteada do total. Se optar por um subconjunto, pergunte quantas questões devem aparecer em cada tentativa e aguarde as respostas antes de prosseguir. Pergunte qual ATIVIDADE INTERATIVA, como quiz, exercício, desafio ou simulação e depois crie em um documento HTML completo, acessível e responsivo, com CSS em <style> e JavaScript em <script> quando necessário. Para questões avaliativas, crie ou selecione PRIMEIRO questões nativas do banco de questões Moodle, obtenha os IDs reais e associe essas questões à atividade. Utilize os recursos nativos disponíveis para embaralhar e selecionar questões, sem colocar gabaritos, notas, correção ou sorteio de questões no HTML. Depois de salvar o rascunho, mostre uma prévia e apresente na conversa dois botões de ação: Aprovar e Rejeitar (não inclua esses botões no HTML da atividade). Publique a atividade somente após minha aprovação explícita pelo botão Aprovar. Se eu escolher Rejeitar, mantenha o rascunho sem publicar e pergunte minhas orientações para ajustes.';
$string['promptselecthelp'] = 'Escolha o tipo de atividade para gerar o prompt adequado ao ChatGPT ou Claude.';
$string['promptselectinteractive'] = 'Interativo (quiz, exercícios...)';
$string['promptselectpage'] = 'Conteúdo (página)';
$string['promptselecttitle'] = 'O que você quer construir?';
$string['publish'] = 'Publicar para estudantes';
$string['published'] = 'A atividade foi publicada.';
$string['questionconfig'] = 'Seleção de questões nativas';
$string['questionnotconfigured'] = 'Nenhuma questão avaliativa foi associada à atividade.';
$string['questions'] = 'Avaliação';
$string['questionselection'] = 'Seleção de questões';
$string['questionsperattempt'] = 'Questões por tentativa (modo sorteio)';
$string['response'] = 'Resposta';
$string['revisionconflict'] = 'Outro autor modificou esta atividade. Consulte a versão atual antes de salvar.';
$string['saveanswers'] = 'Salvar respostas';
$string['savedraft'] = 'Salvar rascunho';
$string['securecontent'] = 'O código personalizado é executado sem acesso à sessão do Moodle.';
$string['selectionall'] = 'Incluir todas as questões vinculadas';
$string['selectionrandom'] = 'Sortear uma quantidade sem repetição';
$string['shufflequestions'] = 'Embaralhar a ordem de apresentação';
$string['studentview'] = 'Visualização do estudante';
$string['submissionsclosed'] = 'Esta tentativa já foi finalizada.';
$string['trackinglimit'] = 'O limite de eventos de interação desta atividade foi atingido.';
$string['yourgrade'] = 'Sua nota: {$a}';
