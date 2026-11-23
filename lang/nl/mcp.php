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
$string['addinstance'] = 'Interactieve ervaring toevoegen';
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
$string['gradescalenotsupported'] = 'Interactieve ervaringen ondersteunen punten of geen beoordeling, maar geen beoordelingsschalen.';
$string['manageactivity'] = 'Interactieve ervaring beheren';
$string['mcp:addinstance'] = 'Interactieve ervaringen toevoegen';
$string['mcp:manage'] = 'Interactieve ervaringen bewerken en publiceren';
$string['mcp:submit'] = 'Antwoorden voor beoordeling van een interactieve ervaring indienen';
$string['mcp:view'] = 'Interactieve ervaringen bekijken';
$string['missingcontent'] = 'Activiteit maken';
$string['missingcontenthelp'] = 'Maak de inhoud van deze activiteit met ChatGPT of Claude via de Moodle MCP-hulpmiddelen. Studenten hebben pas toegang nadat een geldige HTML-versie is gepubliceerd.';
$string['modulename'] = 'Interactieve ervaring';
$string['modulename_help'] = 'Maak interactieve ervaringen met HTML, CSS en JavaScript die via ChatGPT of Claude met Moodle MCP worden beheerd.';
$string['modulenameplural'] = 'Interactieve ervaringen';
$string['pluginadministration'] = 'Beheer van interactieve ervaringen';
$string['pluginname'] = 'Interactieve ervaring';
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
$string['promptcolours'] = 'VERPLICHTE REGEL VOOR MERKKLEUREN voor het thema {$a->theme}: alleen de variabelen {$a->variables} mogen de merkkleuren leveren. Verzin geen ander kleurenpalet en gebruik geen vaste hex-, rgb()/rgba()-, hsl()/hsla()- of benoemde merkkleuren in hero-secties, verlopen en alle stops, knoppen, links, randen, pictogrammen, kaarten, hover-/focusstatussen of pseudo-elementen. Alle merkkleuren MOETEN gebruikmaken van var({$a->primary}, #0d6efd) and var({$a->complementary}, var({$a->primary}, #0d6efd)), or local tokens derived from these variables. Valid example: .hero { background: linear-gradient(123deg, var({$a->primary}, #0d6efd), var({$a->complementary}, var({$a->primary}, #0d6efd))); }. Gebruik color-mix() met deze variabelen voor tussentinten en transparantie. Letterlijke kleurwaarden zijn alleen toegestaan als fallback binnen var() of voor neutrale kleuren en semantische toegankelijkheidsstatussen, nooit ter vervanging van merkkleuren. Controleer vóór oplevering alle CSS, inclusief mediaqueries en pseudo-elementen, en vervang verzonnen merkkleuren. mod_mcp geeft de actuele themavariabelen door aan het geïsoleerde iframe in Moodle; fallbacks blijven nodig voor zelfstandige voorbeelden en de app.';
$string['promptdarkmode'] = 'De CSS MOET zowel de lichte als de donkere modus ondersteunen: gebruik lichte stijlen als standaard, voeg @media (prefers-color-scheme: dark) toe en ondersteun [data-bs-theme="dark"] en [data-bs-theme="light"] waar beschikbaar. HTML draait in een geïsoleerd iframe en moet daarom eigen stijlen hebben, zonder afhankelijkheid van de bovenliggende Moodle-pagina. Zorg voor voldoende contrast tussen achtergronden, tekst, randen en bedieningselementen.';
$string['promptdescriptioncase'] = 'Maak een casestudy waarin studenten beslissingen nemen, de gevolgen daarvan verkennen en feedback op hun keuzes krijgen. ChatGPT of Claude vraagt: \'Welk scenario en welke feitelijke context?\', \'Wie is de doelgroep en wat is het leerdoel?\', \'Hoeveel beslissingen zijn er?\', \'Kunnen studenten teruggaan en hun keuzes herzien?\' en \'Zijn er documenten of voorbeelden als bron?\'. De verschillende routes moeten logisch samenhangen en duidelijke feedback bieden over positieve en negatieve gevolgen.';
$string['promptdescriptioncustom'] = 'Maak de leeractiviteit die je voor ogen hebt, zonder beperkt te zijn tot de andere tabbladen. ChatGPT of Claude vraagt eerst: \'Wat voor activiteit wil je maken?\'. Daarna volgen, afhankelijk van je antwoord: \'Hoe moet ze werken?\', \'Wat zijn de inhoud, doelgroep en het leerdoel?\', \'Welke interacties wil je toevoegen?\' en \'Heb je documenten, afbeeldingen of andere bronnen?\'. Pas na je antwoorden en bronmateriaal wordt de inhoud gemaakt en kun je extra ideeën toevoegen.';
$string['promptdescriptioninteractive'] = 'Maak een interactieve quiz met vragen in vaste of willekeurige volgorde, beoordeeld door Moodle. ChatGPT of Claude vraagt vooraf: \'Wat zijn het onderwerp, de doelgroep en het leerdoel?\', \'Hoeveel vragen wil je?\', \'Moeten vragen worden geschud?\', \'Hoeveel worden per poging willekeurig gekozen?\' en \'Heb je bestanden of ander referentiemateriaal?\'. De goede antwoorden blijven in JSON op de server en worden nooit zichtbaar in de HTML van de student.';
$string['promptdescriptionpage'] = 'Maak een lespagina met uitleg, voorbeelden en afbeeldingen, zonder cijfers of beoordelingsvragen. ChatGPT of Claude vraagt: \'Wil je een verklarende les, een stapsgewijze handleiding, een samenvatting, een casestudy of iets anders?\', \'Wat zijn het onderwerp, de doelgroep en het leerdoel?\', \'Heb je specifieke instructies?\' en \'Zijn er pdf\'s, afbeeldingen of andere bronnen?\'. De pagina wordt gebouwd op basis van je antwoorden en verzint geen ontbrekende bronnen.';
$string['promptdescriptionsimulation'] = 'Maak een interactieve simulatie waarbij studenten parameters aanpassen en de resultaten, grafieken of diagrammen bekijken. ChatGPT of Claude vraagt: \'Welk verschijnsel of proces moet worden gesimuleerd?\', \'Voor welke doelgroep?\', \'Welke waarden mogen studenten wijzigen?\', \'Welke eenheden, formules en veilige grenzen gelden?\' en \'Welke resultaten moeten verschijnen?\'. Als betrouwbare regels ontbreken, worden eerst bronnen en aannames gevraagd, zodat het model geen ongefundeerde resultaten toont.';
$string['promptdescriptionsurvey'] = 'Maak een tevredenheidsenquête die steeds één vraag toont en antwoorden zonder cijfers opslaat. ChatGPT of Claude vraagt: \'Wat is het doel van de enquête?\', \'Wie gaat antwoorden?\', \'Hoeveel vragen zijn nodig?\', \'Welke schalen of antwoordvormen?\', \'Welke antwoorden zijn verplicht?\' en \'Heb je rapporten of anonimiteit nodig?\'. Gecombineerde rapporten en anonimiteit zijn zonder extra serverondersteuning niet gegarandeerd; de beperkingen moeten vooraf duidelijk zijn.';
$string['promptdescriptiontimeline'] = 'Maak een interactieve tijdlijn met gebeurtenissen op datum en details die studenten kunnen openen. ChatGPT of Claude vraagt: \'Welk onderwerp en welke periode?\', \'Welke belangrijke gebeurtenissen moeten erin?\', \'Wie is de doelgroep?\', \'Wil je gebeurteniskaarten, uitklapbare details of filters?\' en \'Heb je bronnen of referentiemateriaal?\'. Datums en andere feiten moeten controleerbaar zijn; ontbrekende gebeurtenissen en bronnen mogen niet worden verzonnen.';
$string['promptfreedom'] = '<strong>Dit zijn slechts suggesties:</strong> je hoeft je er niet toe te beperken. Vraag de kunstmatige intelligentie om extra functies, interacties of andere ideeën toe te voegen. Je verbeelding is de enige grens.';
$string['promptimagealt'] = 'Visueel voorbeeld van {$a}.';
$string['promptpublishinstructions'] = 'Toon na het opslaan van het concept een voorbeeld en bied twee acties aan in het gesprek: Goedkeuren en Afwijzen, niet in de HTML. Publiceer pas na uitdrukkelijke toestemming van de docent. Bewaar bij afwijzing het ongepubliceerde concept en vraag naar de gewenste wijzigingen.';
$string['promptselectcase'] = 'Casestudy met beslissingen';
$string['promptselectcustom'] = 'Ik kies';
$string['promptselecthelp'] = 'Kies het activiteitstype om de juiste instructies voor ChatGPT of Claude te maken.';
$string['promptselectinteractive'] = 'Interactief (quiz, oefeningen...)';
$string['promptselectpage'] = 'Inhoud (pagina)';
$string['promptselectsimulation'] = 'Interactieve simulatie';
$string['promptselectsurvey'] = 'Tevredenheidsenquête';
$string['promptselecttimeline'] = 'Tijdlijn';
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
