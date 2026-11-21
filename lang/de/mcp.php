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
$string['promptcolours'] = 'Verwenden Sie für die Farben des Themes {$a->theme} die CSS-Variablen {$a->variables} mit Ersatzwerten in var(), da dieses HTML in einem isolierten iframe läuft.';
$string['promptdarkmode'] = 'Das CSS MUSS helle und dunkle Darstellung unterstützen: Definieren Sie helle Stile als Standard, berücksichtigen Sie @media (prefers-color-scheme: dark) und die expliziten Einstellungen [data-bs-theme="dark"] und [data-bs-theme="light"], sofern vorhanden. Da das HTML in einem isolierten iframe ausgeführt wird, muss es eigene Stile definieren. Achten Sie auf ausreichenden Kontrast für Hintergründe, Texte, Rahmen und Steuerelemente.';
$string['promptselecthelp'] = 'Wählen Sie die Aktivitätsart aus, um die passenden Anweisungen für ChatGPT oder Claude zu erstellen.';
$string['promptselectinteractive'] = 'Interaktiv (Quiz, Übungen ...)';
$string['promptselectpage'] = 'Inhalt (Seite)';
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
