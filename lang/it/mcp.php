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
 * Italian strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = 'Questa attività non è ancora stata pubblicata.';
$string['addinstance'] = 'Aggiungi un\'attività MCP';
$string['attempt'] = 'Tentativo';
$string['attemptfinished'] = 'Il tuo tentativo è stato inviato.';
$string['attemptinprogress'] = 'Il tuo tentativo è in corso.';
$string['beginattempt'] = 'Inizia la valutazione';
$string['completioninteractive'] = 'Quando il documento interattivo richiede il completamento';
$string['completionnotallowed'] = 'Le regole di completamento attuali non consentono di completare l\'attività tramite il documento.';
$string['connectionanother'] = 'Devi collegare un\'altra applicazione ChatGPT o Claude oppure autorizzare nuovamente il tuo account? Segui queste istruzioni:';
$string['connectionnew'] = 'Configura connessione MCP';
$string['connectionrequired'] = 'Il tuo account Moodle non ha ancora una connessione MCP attiva. Collega ChatGPT o Claude a questo sito Moodle, autorizza l\'accesso con il tuo account e poi torna qui per utilizzare le istruzioni seguenti.';
$string['content'] = 'Documento HTML (inclusi CSS e JavaScript)';
$string['content_help'] = 'Utilizza un documento HTML completo con stili e script incorporati. Gli script vengono eseguiti in un iframe isolato.';
$string['copyinstructions'] = 'Copia queste istruzioni in ChatGPT o Claude per creare l\'attività:';
$string['draftsaved'] = 'Bozza salvata; gli studenti continuano a vedere la versione pubblicata.';
$string['editcontent'] = 'Modifica il contenuto dell\'attività';
$string['finishattempt'] = 'Invia le risposte e termina';
$string['grade'] = 'Voto massimo';
$string['gradescalenotsupported'] = 'Le attività MCP supportano punteggi o nessun voto, ma non scale di valutazione.';
$string['manageactivity'] = 'Gestisci attività MCP';
$string['mcp:addinstance'] = 'Aggiungi attività MCP';
$string['mcp:manage'] = 'Modifica e pubblica attività MCP';
$string['mcp:submit'] = 'Invia le risposte della valutazione MCP';
$string['mcp:view'] = 'Visualizza attività MCP';
$string['missingcontent'] = 'Crea l\'attività';
$string['missingcontenthelp'] = 'Crea il contenuto di questa attività con ChatGPT o Claude tramite gli strumenti MCP di Moodle. Gli studenti non potranno accedervi finché non verrà pubblicata una revisione HTML valida.';
$string['modulename'] = 'Attività MCP';
$string['modulename_help'] = 'Crea attività interattive con un unico documento HTML gestito tramite ChatGPT o Claude usando Moodle MCP.';
$string['modulenameplural'] = 'Attività MCP';
$string['pluginadministration'] = 'Amministrazione dell\'attività MCP';
$string['pluginname'] = 'Attività MCP';
$string['preview'] = 'Anteprima della bozza corrente';
$string['privacy:metadata:mcp_attempts'] = 'Memorizza l\'utente e lo stato di ogni tentativo di valutazione e l\'utilizzo delle domande native.';
$string['privacy:metadata:mcp_attempts:userid'] = 'Utente che ha inviato il tentativo.';
$string['privacy:metadata:mcp_revisions'] = 'Memorizza l\'autore di ogni revisione HTML.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'Utente che ha creato questa revisione.';
$string['privacy:metadata:mcp_state'] = 'Memorizza i dati di ripresa per utente e l\'indicatore di completamento personalizzato.';
$string['privacy:metadata:mcp_state:statejson'] = 'Dati di avanzamento inseriti dallo studente nel documento interattivo.';
$string['privacy:metadata:mcp_state:userid'] = 'Utente proprietario dei progressi salvati.';
$string['privacy:metadata:mcp_tracking'] = 'Memorizza eventi di interazione limitati inviati dal documento.';
$string['privacy:metadata:mcp_tracking:datajson'] = 'Dati di risultato degli eventi generati dall\'interazione dello studente.';
$string['privacy:metadata:mcp_tracking:userid'] = 'Utente che ha interagito con l\'attività.';
$string['progressconflict'] = 'I progressi sono stati aggiornati in un\'altra finestra. Ricaricali prima di salvare.';
$string['promptcolours'] = 'Per i colori del tema {$a->theme}, utilizza le variabili CSS {$a->variables} con valori di riserva in var(), perché questo HTML viene visualizzato in un iframe isolato.';
$string['promptdarkmode'] = 'Il CSS DEVE supportare le modalità chiara e scura: definisci lo stile chiaro come predefinito, usa @media (prefers-color-scheme: dark) e supporta [data-bs-theme="dark"] e [data-bs-theme="light"] ove disponibili. L\'HTML viene eseguito in un iframe isolato, quindi deve includere stili propri senza dipendere dalla pagina Moodle. Garantisci un contrasto adeguato per sfondi, testo, bordi e controlli.';
$string['promptselecthelp'] = 'Scegli il tipo di attività per generare le istruzioni adatte a ChatGPT o Claude.';
$string['promptselectinteractive'] = 'Interattiva (quiz, esercizi...)';
$string['promptselectpage'] = 'Contenuto (pagina)';
$string['promptselecttitle'] = 'Che cosa vuoi creare?';
$string['publish'] = 'Pubblica per gli studenti';
$string['published'] = 'L\'attività è stata pubblicata.';
$string['questionnotconfigured'] = 'Non sono state associate domande valutate a questa attività.';
$string['questions'] = 'Valutazione';
$string['response'] = 'Risposta';
$string['revisionconflict'] = 'Un altro autore ha modificato questa attività. Ricaricala prima di salvare.';
$string['saveanswers'] = 'Salva risposte';
$string['savedraft'] = 'Salva bozza';
$string['securecontent'] = 'Il codice personalizzato viene eseguito senza accesso alla sessione Moodle.';
$string['studentview'] = 'Vista studente';
$string['submissionsclosed'] = 'Questo tentativo è già terminato.';
$string['trackinglimit'] = 'È stato raggiunto il limite massimo di eventi di interazione per questa attività.';
$string['yourgrade'] = 'Il tuo voto: {$a}';
