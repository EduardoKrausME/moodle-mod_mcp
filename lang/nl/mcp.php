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
 * Dutch strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = 'Deze activiteit is nog niet gepubliceerd.';
$string['addinstance'] = 'MCP-activiteit toevoegen';
$string['attempt'] = 'Poging';
$string['attemptfinished'] = 'Je poging is ingediend.';
$string['attemptinprogress'] = 'Je poging is bezig.';
$string['beginattempt'] = 'Beoordeling starten';
$string['completioninteractive'] = 'Wanneer het interactieve document de voltooiing aanvraagt';
$string['completionnotallowed'] = 'De huidige voltooiingsregels staan niet toe deze activiteit via het document te voltooien.';
$string['connectionanother'] = 'Wil je nog een ChatGPT- of Claude-app verbinden of je account opnieuw autoriseren? Volg dan deze instructies:';
$string['connectionnew'] = 'MCP-verbinding instellen';
$string['connectionrequired'] = 'Je Moodle-account heeft nog geen actieve MCP-verbinding. Verbind ChatGPT of Claude met deze Moodle-site, geef toegang via je account en kom terug om de onderstaande instructies te gebruiken.';
$string['content'] = 'HTML-document (inclusief CSS en JavaScript)';
$string['content_help'] = 'Gebruik één volledig HTML-document met ingebedde stijlen en scripts. Scripts worden in een geïsoleerd iframe uitgevoerd.';
$string['copyinstructions'] = 'Kopieer deze instructies naar ChatGPT of Claude om de activiteit te maken:';
$string['draftsaved'] = 'Concept opgeslagen; studenten zien nog steeds de gepubliceerde versie.';
$string['editcontent'] = 'Activiteitsinhoud bewerken';
$string['finishattempt'] = 'Antwoorden indienen en afronden';
$string['grade'] = 'Maximumcijfer';
$string['gradescalenotsupported'] = 'MCP-activiteiten ondersteunen punten of geen beoordeling, maar geen beoordelingsschalen.';
$string['manageactivity'] = 'MCP-activiteit beheren';
$string['mcp:addinstance'] = 'MCP-activiteiten toevoegen';
$string['mcp:manage'] = 'MCP-activiteiten bewerken en publiceren';
$string['mcp:submit'] = 'Antwoorden voor MCP-beoordeling indienen';
$string['mcp:view'] = 'MCP-activiteiten bekijken';
$string['missingcontent'] = 'Activiteit maken';
$string['missingcontenthelp'] = 'Maak de inhoud van deze activiteit met ChatGPT of Claude via de Moodle MCP-hulpmiddelen. Studenten hebben pas toegang nadat een geldige HTML-versie is gepubliceerd.';
$string['modulename'] = 'MCP-activiteit';
$string['modulename_help'] = 'Maak interactieve activiteiten met één HTML-document dat via ChatGPT of Claude met Moodle MCP wordt beheerd.';
$string['modulenameplural'] = 'MCP-activiteiten';
$string['pluginadministration'] = 'MCP-activiteitbeheer';
$string['pluginname'] = 'MCP-activiteit';
$string['preview'] = 'Voorbeeld van huidig concept';
$string['privacy:metadata:mcp_attempts'] = 'Slaat de gebruiker en de status van elke beoordelingspoging en het gebruik van Moodle-vragen op.';
$string['privacy:metadata:mcp_attempts:userid'] = 'Gebruiker die de poging heeft ingediend.';
$string['privacy:metadata:mcp_revisions'] = 'Slaat de auteur van elke HTML-versie op.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'Gebruiker die deze versie heeft gemaakt.';
$string['privacy:metadata:mcp_state'] = 'Slaat hervattingsgegevens per gebruiker en de aangepaste voltooiingsstatus op.';
$string['privacy:metadata:mcp_state:statejson'] = 'Voortgangsgegevens die door de student in het interactieve document zijn ingevoerd.';
$string['privacy:metadata:mcp_state:userid'] = 'Gebruiker bij wie de opgeslagen voortgang hoort.';
$string['privacy:metadata:mcp_tracking'] = 'Slaat een beperkt aantal interactiegebeurtenissen vanuit het document op.';
$string['privacy:metadata:mcp_tracking:datajson'] = 'Resultaatgegevens van gebeurtenissen tijdens interacties van de student.';
$string['privacy:metadata:mcp_tracking:userid'] = 'Gebruiker die met de activiteit heeft gewerkt.';
$string['progressconflict'] = 'De voortgang is in een ander venster bijgewerkt. Laad opnieuw voordat je opslaat.';
$string['promptcolours'] = 'Gebruik voor de kleuren van thema {$a->theme} de CSS-variabelen {$a->variables}, met terugvalwaarden in var(), omdat deze HTML in een geïsoleerd iframe wordt weergegeven.';
$string['promptdarkmode'] = 'De CSS MOET zowel de lichte als de donkere modus ondersteunen: gebruik lichte stijlen als standaard, voeg @media (prefers-color-scheme: dark) toe en ondersteun [data-bs-theme="dark"] en [data-bs-theme="light"] waar beschikbaar. HTML draait in een geïsoleerd iframe en moet daarom eigen stijlen hebben, zonder afhankelijkheid van de bovenliggende Moodle-pagina. Zorg voor voldoende contrast tussen achtergronden, tekst, randen en bedieningselementen.';
$string['promptselecthelp'] = 'Kies het activiteitstype om de juiste instructies voor ChatGPT of Claude te maken.';
$string['promptselectinteractive'] = 'Interactief (quiz, oefeningen...)';
$string['promptselectpage'] = 'Inhoud (pagina)';
$string['promptselecttitle'] = 'Wat wil je maken?';
$string['publish'] = 'Publiceren voor studenten';
$string['published'] = 'De activiteit is gepubliceerd.';
$string['questionnotconfigured'] = 'Er zijn geen beoordeelde vragen aan deze activiteit gekoppeld.';
$string['questions'] = 'Beoordeling';
$string['response'] = 'Antwoord';
$string['revisionconflict'] = 'Een andere auteur heeft deze activiteit gewijzigd. Laad opnieuw voordat je opslaat.';
$string['saveanswers'] = 'Antwoorden opslaan';
$string['savedraft'] = 'Concept opslaan';
$string['securecontent'] = 'Aangepaste code wordt uitgevoerd zonder toegang tot de Moodle-sessie.';
$string['studentview'] = 'Studentweergave';
$string['submissionsclosed'] = 'Deze poging is al afgerond.';
$string['trackinglimit'] = 'Het maximumaantal interactiegebeurtenissen voor deze activiteit is bereikt.';
$string['yourgrade'] = 'Je cijfer: {$a}';
