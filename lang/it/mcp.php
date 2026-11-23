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
$string['addinstance'] = 'Aggiungi un\'esperienza interattiva';
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
$string['gradescalenotsupported'] = 'Le esperienze interattive supportano punteggi o nessun voto, ma non scale di valutazione.';
$string['manageactivity'] = 'Gestisci esperienza interattiva';
$string['mcp:addinstance'] = 'Aggiungi esperienze interattive';
$string['mcp:manage'] = 'Modifica e pubblica esperienze interattive';
$string['mcp:submit'] = 'Invia le risposte della valutazione dell\'esperienza interattiva';
$string['mcp:view'] = 'Visualizza esperienze interattive';
$string['missingcontent'] = 'Crea l\'attività';
$string['missingcontenthelp'] = 'Crea il contenuto di questa attività con ChatGPT o Claude tramite gli strumenti MCP di Moodle. Gli studenti non potranno accedervi finché non verrà pubblicata una revisione HTML valida.';
$string['modulename'] = 'Esperienza interattiva';
$string['modulename_help'] = 'Crea esperienze interattive con HTML, CSS e JavaScript, gestite tramite ChatGPT o Claude usando Moodle MCP.';
$string['modulenameplural'] = 'Esperienze interattive';
$string['pluginadministration'] = 'Amministrazione dell\'esperienza interattiva';
$string['pluginname'] = 'Esperienza interattiva';
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
$string['promptcolours'] = 'REGOLA OBBLIGATORIA PER I COLORI DEL MARCHIO del tema {$a->theme}: solo le variabili {$a->variables} possono fornire i colori del marchio. Non inventare palette alternative né usare colori fissi in hex, rgb()/rgba(), hsl()/hsla() o nomi di colori per sezioni in evidenza, sfumature e tutte le interruzioni, pulsanti, link, bordi, icone, schede, stati hover/focus o pseudoelementi. Tutti i colori del marchio DEVONO usare var({$a->primary}, #0d6efd) and var({$a->complementary}, var({$a->primary}, #0d6efd)), or local tokens derived from these variables. Valid example: .hero { background: linear-gradient(123deg, var({$a->primary}, #0d6efd), var({$a->complementary}, var({$a->primary}, #0d6efd))); }. Usa color-mix() con queste variabili per tonalità intermedie e trasparenze. I colori letterali sono ammessi solo come fallback all\'interno di var() oppure per neutri e stati semantici di accessibilità, mai per sostituire quelli del tema. Prima di consegnare l\'HTML controlla tutto il CSS, comprese media query e pseudoelementi, e sostituisci i colori del marchio inventati. mod_mcp trasferisce i valori correnti delle variabili al proprio iframe isolato in Moodle; i fallback servono ancora per anteprime indipendenti e app.';
$string['promptdarkmode'] = 'Il CSS DEVE supportare le modalità chiara e scura: definisci lo stile chiaro come predefinito, usa @media (prefers-color-scheme: dark) e supporta [data-bs-theme="dark"] e [data-bs-theme="light"] ove disponibili. L\'HTML viene eseguito in un iframe isolato, quindi deve includere stili propri senza dipendere dalla pagina Moodle. Garantisci un contrasto adeguato per sfondi, testo, bordi e controlli.';
$string['promptdescriptioncase'] = 'Crea un caso di studio in cui gli studenti prendono decisioni, esplorano le conseguenze e ricevono riscontri sulle proprie scelte. ChatGPT o Claude chiederà: «Qual è lo scenario e il contesto fattuale?», «Chi è il pubblico e qual è l\'obiettivo didattico?», «Quante decisioni servono?», «Si può tornare indietro e cambiare scelta?» e «Hai documenti o esempi di riferimento?». I percorsi decisionali devono essere coerenti e accompagnati da feedback esplicativi, anche in caso di errori.';
$string['promptdescriptioncustom'] = 'Crea l\'attività didattica che immagini senza limitarti alle altre schede. ChatGPT o Claude chiederà prima: «Quale tipo di attività vuoi creare?». In base alla risposta seguiranno altre domande: «Come dovrebbe funzionare?», «Quali sono il contenuto, il pubblico e l\'obiettivo didattico?», «Quali interazioni deve includere?» e «Hai documenti, immagini o altre fonti?». Aspetterà le tue risposte e i materiali prima di generare contenuti.';
$string['promptdescriptioninteractive'] = 'Crea un quiz interattivo con domande in ordine fisso o casuale, valutate da Moodle. ChatGPT o Claude chiederà: «Qual è l\'argomento, il pubblico e l\'obiettivo didattico?», «Quante domande desideri?», «Vanno mescolate?», «Quante devono essere estratte a caso per tentativo?» e «Hai file o materiali di riferimento?». Le risposte corrette restano nel JSON sul server e non vengono inserite nell\'HTML visibile allo studente.';
$string['promptdescriptionpage'] = 'Crea una pagina didattica con spiegazioni, esempi e immagini, senza voti né domande di valutazione. ChatGPT o Claude chiederà: «Preferisci una lezione esplicativa, un tutorial passo passo, un riepilogo, un caso di studio o un altro formato?», «Qual è l\'argomento, il pubblico e l\'obiettivo didattico?», «Hai istruzioni specifiche?» e «Disponi di PDF, immagini o altri materiali di riferimento?». La pagina seguirà le tue risposte senza inventare fonti mancanti.';
$string['promptdescriptionsimulation'] = 'Crea una simulazione interattiva in cui gli studenti modificano parametri e osservano risultati, grafici o diagrammi. ChatGPT o Claude chiederà: «Quale fenomeno o processo simulare?», «Per quale pubblico?», «Quali valori possono modificare gli studenti?», «Quali unità, formule e limiti di sicurezza usare?» e «Quali risultati mostrare?». Se mancano regole attendibili, chiederà fonti e ipotesi prima di creare il modello, evitando calcoli inventati.';
$string['promptdescriptionsurvey'] = 'Crea un sondaggio di soddisfazione che mostra una domanda alla volta e salva le risposte senza voti. ChatGPT o Claude chiederà: «Qual è lo scopo del sondaggio?», «Chi risponderà?», «Quante domande vuoi?», «Quali scale o formati di risposta usare?», «Quali risposte sono obbligatorie?» e «Servono report o anonimato?». I report aggregati e l\'anonimato non sono garantiti senza ulteriore supporto lato server e non vanno promessi.';
$string['promptdescriptiontimeline'] = 'Crea una linea temporale esplorabile di eventi ordinati per data, con dettagli consultabili dagli studenti. ChatGPT o Claude chiederà: «Quale argomento e periodo?», «Quali eventi principali inserire?», «A chi è rivolta l\'attività?», «Preferisci schede evento, dettagli espandibili o filtri?» e «Hai fonti o materiali di riferimento?». Le date e gli altri fatti devono essere verificabili e non possono essere inventati.';
$string['promptfreedom'] = '<strong>Questi sono solo suggerimenti:</strong> non devi limitarti a essi. Chiedi all\'intelligenza artificiale di aggiungere altre funzioni, interazioni o qualsiasi idea tu abbia. L\'unico limite è la tua immaginazione.';
$string['promptimagealt'] = 'Esempio visivo di {$a}.';
$string['promptpublishinstructions'] = 'Dopo aver salvato la bozza, mostra un\'anteprima e proponi due azioni nella conversazione: Approva e Rifiuta, non nell\'HTML. Pubblica solo dopo l\'approvazione esplicita del docente. Se rifiutata, mantieni la bozza non pubblicata e chiedi le modifiche necessarie.';
$string['promptselectcase'] = 'Caso di studio con decisioni';
$string['promptselectcustom'] = 'Scelgo io';
$string['promptselecthelp'] = 'Scegli il tipo di attività per generare le istruzioni adatte a ChatGPT o Claude.';
$string['promptselectinteractive'] = 'Interattiva (quiz, esercizi...)';
$string['promptselectpage'] = 'Contenuto (pagina)';
$string['promptselectsimulation'] = 'Simulazione interattiva';
$string['promptselectsurvey'] = 'Sondaggio di soddisfazione';
$string['promptselecttimeline'] = 'Linea temporale';
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
