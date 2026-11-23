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
 * German strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = 'Diese Aktivität wurde noch nicht veröffentlicht.';
$string['addinstance'] = 'Interaktives Lernerlebnis hinzufügen';
$string['attempt'] = 'Versuch';
$string['attemptfinished'] = 'Ihr Versuch wurde abgegeben.';
$string['attemptinprogress'] = 'Ihr Versuch läuft.';
$string['beginattempt'] = 'Bewertung beginnen';
$string['completioninteractive'] = 'Wenn das interaktive Dokument den Abschluss anfordert';
$string['completionnotallowed'] = 'Die aktuellen Abschlussbedingungen erlauben keinen Abschluss über das Dokument.';
$string['connectionanother'] = 'Möchten Sie eine weitere ChatGPT- oder Claude-Anwendung verbinden oder Ihr Konto erneut autorisieren? Folgen Sie diesen Schritten:';
$string['connectionnew'] = 'MCP-Verbindung einrichten';
$string['connectionrequired'] = 'Ihr Moodle-Konto hat noch keine aktive MCP-Verbindung. Verbinden Sie ChatGPT oder Claude mit dieser Moodle-Website, autorisieren Sie den Zugriff mit Ihrem Konto und verwenden Sie anschließend die folgenden Anweisungen.';
$string['content'] = 'HTML-Dokument (einschließlich CSS und JavaScript)';
$string['content_help'] = 'Verwenden Sie ein vollständiges HTML-Dokument mit eingebetteten Stilen und Skripten. Skripte werden in einem isolierten iframe ausgeführt.';
$string['copyinstructions'] = 'Kopieren Sie diese Anweisungen in ChatGPT oder Claude, um die Aktivität zu erstellen:';
$string['draftsaved'] = 'Entwurf gespeichert; Lernende sehen weiterhin die veröffentlichte Version.';
$string['editcontent'] = 'Aktivitätsinhalt bearbeiten';
$string['finishattempt'] = 'Antworten abgeben und abschließen';
$string['grade'] = 'Maximalpunktzahl';
$string['gradescalenotsupported'] = 'Interaktive Lernerlebnisse unterstützen Punkte oder keine Bewertung, jedoch keine Bewertungsskalen.';
$string['manageactivity'] = 'Interaktives Lernerlebnis verwalten';
$string['mcp:addinstance'] = 'Interaktive Lernerlebnisse hinzufügen';
$string['mcp:manage'] = 'Interaktive Lernerlebnisse bearbeiten und veröffentlichen';
$string['mcp:submit'] = 'Antworten für interaktive Lernerlebnisse abgeben';
$string['mcp:view'] = 'Interaktive Lernerlebnisse anzeigen';
$string['missingcontent'] = 'Aktivität erstellen';
$string['missingcontenthelp'] = 'Erstellen Sie den Inhalt dieser Aktivität mit ChatGPT oder Claude über die Moodle-MCP-Werkzeuge. Lernende erhalten erst nach Veröffentlichung einer gültigen HTML-Version Zugriff.';
$string['modulename'] = 'Interaktives Lernerlebnis';
$string['modulename_help'] = 'Erstellen Sie interaktive Lernerlebnisse mit HTML, CSS und JavaScript, die über ChatGPT oder Claude mit Moodle MCP verwaltet werden.';
$string['modulenameplural'] = 'Interaktive Lernerlebnisse';
$string['pluginadministration'] = 'Verwaltung der interaktiven Lernerlebnisse';
$string['pluginname'] = 'Interaktives Lernerlebnis';
$string['preview'] = 'Aktuellen Entwurf in Vorschau ansehen';
$string['privacy:metadata:mcp_attempts'] = 'Speichert Nutzer und Status jedes Bewertungsversuchs sowie die Nutzung nativer Fragen.';
$string['privacy:metadata:mcp_attempts:userid'] = 'Nutzer, der den Versuch abgegeben hat.';
$string['privacy:metadata:mcp_revisions'] = 'Speichert den Autor jeder HTML-Version.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'Nutzer, der diese Version erstellt hat.';
$string['privacy:metadata:mcp_state'] = 'Speichert nutzerbezogene Fortsetzungsdaten und den benutzerdefinierten Abschlussstatus.';
$string['privacy:metadata:mcp_state:statejson'] = 'Von Lernenden im interaktiven Dokument eingegebene Fortschrittsdaten.';
$string['privacy:metadata:mcp_state:userid'] = 'Nutzer, dem der gespeicherte Fortschritt gehört.';
$string['privacy:metadata:mcp_tracking'] = 'Speichert begrenzte, aus dem Dokument übermittelte Interaktionsereignisse.';
$string['privacy:metadata:mcp_tracking:datajson'] = 'Ergebnisdaten der Interaktionsereignisse von Lernenden.';
$string['privacy:metadata:mcp_tracking:userid'] = 'Nutzer, der mit der Aktivität interagiert hat.';
$string['progressconflict'] = 'Der Lernfortschritt wurde in einem anderen Fenster geändert. Laden Sie ihn vor dem Speichern erneut.';
$string['promptcolours'] = 'VERBINDLICHE MARKENFARBENREGEL für das Theme {$a->theme}: Ausschließlich die Variablen {$a->variables} dürfen Markenfarben liefern. Erfinde keine Farbpalette und verwende keine fest codierten Hex-, rgb()/rgba()-, hsl()/hsla()- oder benannten Markenfarben in Hero-Bereichen, Verläufen samt Farbstopps, Schaltflächen, Links, Rahmen, Symbolen, Karten, Hover-/Fokuszuständen oder Pseudoelementen. Sämtliche Markenfarben MÜSSEN var({$a->primary}, #0d6efd) und var({$a->complementary}, var({$a->primary}, #0d6efd)), o tokens locales derivados de estas variables. Ejemplo válido: .hero { background: linear-gradient(123deg, var({$a->primary}, #0d6efd), var({$a->complementary}, var({$a->primary}, #0d6efd))); }. Nutze color-mix() mit diesen Variablen für Zwischenfarben und Transparenz. Literale Farben sind nur als Fallback in var() sowie für neutrale und semantische Barrierefreiheitszustände erlaubt, niemals als Ersatz für Markenfarben. Prüfe vor der HTML-Ausgabe das gesamte CSS einschließlich Media Queries und Pseudoelementen auf erfundene Markenfarben. mod_mcp übermittelt die aktuellen Variablenwerte des Themes an den isolierten Moodle-iframe; für eigenständige Vorschauen und die App bleiben Fallbacks notwendig.';
$string['promptdarkmode'] = 'Das CSS MUSS helle und dunkle Darstellung unterstützen: Definieren Sie helle Stile als Standard, berücksichtigen Sie @media (prefers-color-scheme: dark) und die expliziten Einstellungen [data-bs-theme="dark"] und [data-bs-theme="light"], sofern vorhanden. Da das HTML in einem isolierten iframe ausgeführt wird, muss es eigene Stile definieren. Achten Sie auf ausreichenden Kontrast für Hintergründe, Texte, Rahmen und Steuerelemente.';
$string['promptdescriptioncase'] = 'Erstelle eine Fallstudie, in der Lernende Entscheidungen treffen, ihre Folgen erkunden und Rückmeldungen zu ihren Optionen erhalten. ChatGPT oder Claude fragt: „Welches Szenario und welcher sachliche Kontext?“, „Wer ist die Zielgruppe und was das Lernziel?“, „Wie viele Entscheidungen sind geplant?“, „Dürfen Lernende zurückgehen und andere Wege ausprobieren?“ und „Gibt es Dokumente oder Beispiele als Grundlage?“. Alle Verzweigungen müssen stimmig und die Folgen nachvollziehbar sein.';
$string['promptdescriptioncustom'] = 'Entwickle eine Lernaktivität nach deinen Vorstellungen, ohne auf die übrigen Registerkarten beschränkt zu sein. ChatGPT oder Claude fragt zunächst: „Welche Art von Aktivität möchtest du erstellen?“. Anschließend folgen Fragen wie „Wie soll sie funktionieren?“, „Welche Inhalte, Zielgruppe und welches Lernziel?“, „Welche Interaktionen möchtest du?“ und „Hast du Dokumente, Bilder oder andere Quellen?“. Erst nach deinen Antworten und Materialien wird der Inhalt erzeugt.';
$string['promptdescriptioninteractive'] = 'Erstelle ein interaktives Quiz mit Fragen in fester oder zufälliger Reihenfolge, die Moodle bewertet. ChatGPT oder Claude fragt vorab: „Welches Thema, welche Zielgruppe und welches Lernziel?“, „Wie viele Fragen?“, „Sollen sie gemischt werden?“, „Wie viele werden pro Versuch zufällig ausgewählt?“ und „Gibt es Dateien oder Referenzmaterial?“. Die richtigen Antworten bleiben im serverseitigen JSON und dürfen nicht im HTML des Lernenden stehen.';
$string['promptdescriptionpage'] = 'Erstelle eine Lernseite mit Erklärungen, Beispielen und Bildern, jedoch ohne Noten oder Bewertungsfragen. ChatGPT oder Claude fragt: „Möchtest du eine erklärende Lektion, eine Schritt-für-Schritt-Anleitung, eine Zusammenfassung, eine Fallstudie oder ein anderes Format?“, „Welches Thema, welche Zielgruppe und welches Lernziel?“, „Gibt es besondere Vorgaben?“ und „Hast du PDFs, Bilder oder andere Materialien?“. Der Seiteninhalt richtet sich nach deinen Antworten.';
$string['promptdescriptionsimulation'] = 'Erstelle eine interaktive Simulation, bei der Lernende Parameter ändern und Ergebnisse, Grafiken oder Diagramme beobachten. ChatGPT oder Claude fragt: „Welches Phänomen oder welcher Prozess soll simuliert werden?“, „Wer ist die Zielgruppe?“, „Welche Werte dürfen verändert werden?“, „Welche Einheiten, Formeln und sicheren Grenzen gelten?“ und „Welche Ergebnisse sollen erscheinen?“. Wenn Regeln fehlen, werden Quellen und Annahmen erfragt, bevor das Modell entsteht.';
$string['promptdescriptionsurvey'] = 'Erstelle eine Zufriedenheitsumfrage, die jeweils eine Frage anzeigt und Antworten ohne Benotung speichert. ChatGPT oder Claude fragt: „Was ist das Ziel?“, „Wer antwortet?“, „Wie viele Fragen?“, „Welche Skalen und Antwortformate?“, „Welche Felder sind Pflicht?“ und „Werden Berichte oder Anonymität benötigt?“. Aggregierte Berichte und Anonymität sind ohne zusätzliche serverseitige Unterstützung nicht garantiert und dürfen nicht versprochen werden.';
$string['promptdescriptiontimeline'] = 'Erstelle eine erkundbare Zeitleiste, in der Ereignisse nach Datum sortiert sind und Lernende Details öffnen können. ChatGPT oder Claude fragt: „Welches Thema und welcher Zeitraum?“, „Welche Schlüsselereignisse sollen erscheinen?“, „Wer ist die Zielgruppe?“, „Sind Ereigniskarten, aufklappbare Details oder Filter gewünscht?“ und „Gibt es Quellen oder Referenzmaterial?“. Alle Daten und Fakten müssen überprüfbar sein und dürfen nicht erfunden werden.';
$string['promptfreedom'] = '<strong>Das sind nur Vorschläge:</strong> Du musst dich nicht darauf beschränken. Bitte die künstliche Intelligenz, zusätzliche Funktionen, Interaktionen und eigene Ideen umzusetzen. Deiner Fantasie sind keine Grenzen gesetzt.';
$string['promptimagealt'] = 'Visuelles Beispiel für {$a}.';
$string['promptpublishinstructions'] = 'Zeige nach dem Speichern des Entwurfs eine Vorschau und biete im Gespräch die Aktionen Genehmigen und Ablehnen an, nicht im HTML. Veröffentliche erst nach ausdrücklicher Zustimmung der Lehrkraft. Bei Ablehnung bleibt der Entwurf unveröffentlicht; frage nach den gewünschten Änderungen.';
$string['promptselectcase'] = 'Fallstudie mit Entscheidungen';
$string['promptselectcustom'] = 'Ich entscheide';
$string['promptselecthelp'] = 'Wählen Sie die Aktivitätsart aus, um die passenden Anweisungen für ChatGPT oder Claude zu erstellen.';
$string['promptselectinteractive'] = 'Interaktiv (Quiz, Übungen ...)';
$string['promptselectpage'] = 'Inhalt (Seite)';
$string['promptselectsimulation'] = 'Interaktive Simulation';
$string['promptselectsurvey'] = 'Zufriedenheitsumfrage';
$string['promptselecttimeline'] = 'Zeitleiste';
$string['promptselecttitle'] = 'Was möchten Sie erstellen?';
$string['publish'] = 'Für Lernende veröffentlichen';
$string['published'] = 'Die Aktivität wurde veröffentlicht.';
$string['questionnotconfigured'] = 'Dieser Aktivität wurden keine bewerteten Fragen zugewiesen.';
$string['questions'] = 'Bewertung';
$string['response'] = 'Antwort';
$string['revisionconflict'] = 'Ein anderer Autor hat diese Aktivität geändert. Laden Sie sie vor dem Speichern erneut.';
$string['saveanswers'] = 'Antworten speichern';
$string['savedraft'] = 'Entwurf speichern';
$string['securecontent'] = 'Benutzerdefinierter Code wird ohne Zugriff auf die Moodle-Sitzung ausgeführt.';
$string['studentview'] = 'Lernendenansicht';
$string['submissionsclosed'] = 'Dieser Versuch ist bereits abgeschlossen.';
$string['trackinglimit'] = 'Die maximale Anzahl von Interaktionsereignissen für diese Aktivität wurde erreicht.';
$string['yourgrade'] = 'Ihre Bewertung: {$a}';
