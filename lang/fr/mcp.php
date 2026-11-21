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
 * French strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = 'Cette activité n\'a pas encore été publiée.';
$string['addinstance'] = 'Ajouter une activité MCP';
$string['attempt'] = 'Tentative';
$string['attemptfinished'] = 'Votre tentative a été envoyée.';
$string['attemptinprogress'] = 'Votre tentative est en cours.';
$string['beginattempt'] = 'Commencer l\'évaluation';
$string['completioninteractive'] = 'Lorsque le document interactif demande l\'achèvement';
$string['completionnotallowed'] = 'Les règles d\'achèvement actuelles ne permettent pas de terminer cette activité depuis le document.';
$string['connectionanother'] = 'Vous souhaitez connecter une autre application ChatGPT ou Claude ou autoriser à nouveau votre compte ? Suivez les instructions suivantes :';
$string['connectionnew'] = 'Configurer la connexion MCP';
$string['connectionrequired'] = 'Votre compte Moodle ne dispose pas encore de connexion MCP active. Connectez ChatGPT ou Claude à ce site Moodle, autorisez l\'accès avec votre compte, puis revenez utiliser les instructions ci-dessous.';
$string['content'] = 'Document HTML (avec CSS et JavaScript)';
$string['content_help'] = 'Utilisez un document HTML complet avec des styles et scripts intégrés. Les scripts s\'exécutent dans une iframe isolée.';
$string['copyinstructions'] = 'Copiez ces instructions dans ChatGPT ou Claude pour créer l\'activité :';
$string['draftsaved'] = 'Brouillon enregistré ; les étudiants voient toujours la version publiée.';
$string['editcontent'] = 'Modifier le contenu de l\'activité';
$string['finishattempt'] = 'Envoyer les réponses et terminer';
$string['grade'] = 'Note maximale';
$string['gradescalenotsupported'] = 'Les activités MCP prennent en charge les notes en points ou l\'absence de note, mais pas les barèmes.';
$string['manageactivity'] = 'Gérer l\'activité MCP';
$string['mcp:addinstance'] = 'Ajouter des activités MCP';
$string['mcp:manage'] = 'Modifier et publier des activités MCP';
$string['mcp:submit'] = 'Envoyer les réponses de l\'évaluation MCP';
$string['mcp:view'] = 'Voir les activités MCP';
$string['missingcontent'] = 'Créer l\'activité';
$string['missingcontenthelp'] = 'Créez le contenu de cette activité avec ChatGPT ou Claude à l\'aide des outils MCP de Moodle. Les étudiants n\'y auront pas accès avant la publication d\'une version HTML valide.';
$string['modulename'] = 'Activité MCP';
$string['modulename_help'] = 'Créez des activités interactives avec un seul document HTML, géré avec ChatGPT ou Claude via Moodle MCP.';
$string['modulenameplural'] = 'Activités MCP';
$string['pluginadministration'] = 'Administration de l\'activité MCP';
$string['pluginname'] = 'Activité MCP';
$string['preview'] = 'Prévisualiser le brouillon actuel';
$string['privacy:metadata:mcp_attempts'] = 'Enregistre l\'utilisateur, le statut de chaque tentative et l\'utilisation des questions natives.';
$string['privacy:metadata:mcp_attempts:userid'] = 'Utilisateur ayant envoyé la tentative.';
$string['privacy:metadata:mcp_revisions'] = 'Enregistre l\'auteur de chaque révision HTML.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'Utilisateur ayant créé cette révision.';
$string['privacy:metadata:mcp_state'] = 'Enregistre les données de reprise par utilisateur et l\'indicateur d\'achèvement personnalisé.';
$string['privacy:metadata:mcp_state:statejson'] = 'Données de progression saisies par l\'étudiant dans le document interactif.';
$string['privacy:metadata:mcp_state:userid'] = 'Utilisateur propriétaire de la progression enregistrée.';
$string['privacy:metadata:mcp_tracking'] = 'Enregistre les événements d\'interaction limités envoyés depuis le document.';
$string['privacy:metadata:mcp_tracking:datajson'] = 'Données de résultat des événements produits pendant l\'interaction de l\'étudiant.';
$string['privacy:metadata:mcp_tracking:userid'] = 'Utilisateur ayant interagi avec l\'activité.';
$string['progressconflict'] = 'La progression a été modifiée dans une autre fenêtre. Rechargez-la avant de l\'enregistrer.';
$string['promptcolours'] = 'Pour les couleurs du thème {$a->theme}, utilisez les variables CSS {$a->variables} avec des valeurs de repli dans var(), car le HTML s\'affiche dans une iframe isolée.';
$string['promptdarkmode'] = 'Le CSS DOIT prendre en charge les modes clair et sombre : définissez les styles clairs par défaut, utilisez @media (prefers-color-scheme: dark) et prévoyez les sélections [data-bs-theme="dark"] et [data-bs-theme="light"] lorsqu\'elles sont disponibles. Le HTML s\'exécute dans une iframe isolée ; fournissez ses propres styles sans dépendre de ceux de Moodle. Assurez un contraste suffisant pour les fonds, textes, bordures et commandes.';
$string['promptselecthelp'] = 'Choisissez le type d\'activité pour générer les instructions adaptées à ChatGPT ou Claude.';
$string['promptselectinteractive'] = 'Interactive (quiz, exercices...)';
$string['promptselectpage'] = 'Contenu (page)';
$string['promptselecttitle'] = 'Que souhaitez-vous créer ?';
$string['publish'] = 'Publier pour les étudiants';
$string['published'] = 'L\'activité a été publiée.';
$string['questionnotconfigured'] = 'Aucune question notée n\'a été associée à cette activité.';
$string['questions'] = 'Évaluation';
$string['response'] = 'Réponse';
$string['revisionconflict'] = 'Un autre auteur a modifié cette activité. Rechargez-la avant d\'enregistrer.';
$string['saveanswers'] = 'Enregistrer les réponses';
$string['savedraft'] = 'Enregistrer le brouillon';
$string['securecontent'] = 'Le code personnalisé s\'exécute sans accès à la session Moodle.';
$string['studentview'] = 'Vue étudiant';
$string['submissionsclosed'] = 'Cette tentative est déjà terminée.';
$string['trackinglimit'] = 'Le nombre maximal d\'événements d\'interaction a été atteint pour cette activité.';
$string['yourgrade'] = 'Votre note : {$a}';
