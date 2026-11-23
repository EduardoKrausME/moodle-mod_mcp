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
$string['addinstance'] = 'Ajouter une expérience interactive';
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
$string['gradescalenotsupported'] = 'Les expériences interactives prennent en charge les notes en points ou l\'absence de note, mais pas les barèmes.';
$string['manageactivity'] = 'Gérer une expérience interactive';
$string['mcp:addinstance'] = 'Ajouter des expériences interactives';
$string['mcp:manage'] = 'Modifier et publier des expériences interactives';
$string['mcp:submit'] = 'Envoyer les réponses de l\'évaluation de l\'expérience interactive';
$string['mcp:view'] = 'Voir les expériences interactives';
$string['missingcontent'] = 'Créer l\'activité';
$string['missingcontenthelp'] = 'Créez le contenu de cette activité avec ChatGPT ou Claude à l\'aide des outils MCP de Moodle. Les étudiants n\'y auront pas accès avant la publication d\'une version HTML valide.';
$string['modulename'] = 'Expérience interactive';
$string['modulename_help'] = 'Créez des expériences interactives avec HTML, CSS et JavaScript, gérées avec ChatGPT ou Claude via Moodle MCP.';
$string['modulenameplural'] = 'Expériences interactives';
$string['pluginadministration'] = 'Administration de l\'expérience interactive';
$string['pluginname'] = 'Expérience interactive';
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
$string['promptcolours'] = 'RÈGLE OBLIGATOIRE SUR LES COULEURS DE MARQUE du thème {$a->theme} : seules les variables {$a->variables} fournissent les couleurs de marque. N\'inventez aucune palette et n\'utilisez aucune couleur de marque codée en dur (hex, rgb()/rgba(), hsl()/hsla() ou nom) pour les sections principales, les dégradés et leurs arrêts, les boutons, liens, bordures, icônes, cartes, états hover/focus ou pseudo-éléments. Toutes les couleurs de marque DOIVENT utiliser var({$a->primary}, #0d6efd) et var({$a->complementary}, var({$a->primary}, #0d6efd)), o tokens locales derivados de estas variables. Ejemplo válido: .hero { background: linear-gradient(123deg, var({$a->primary}, #0d6efd), var({$a->complementary}, var({$a->primary}, #0d6efd))); }. Pour les nuances intermédiaires et la transparence, utilisez color-mix() avec ces variables. Les couleurs littérales sont admises seulement comme valeurs de repli dans var(), ou pour les neutres et les états sémantiques d\'accessibilité, jamais en remplacement des couleurs de marque. Vérifiez tout le CSS, y compris media queries et pseudo-éléments, et remplacez les couleurs de marque inventées avant de livrer le HTML. mod_mcp transmet les valeurs du thème à l\'iframe isolée de Moodle ; des valeurs de repli restent nécessaires pour les aperçus autonomes et l\'application.';
$string['promptdarkmode'] = 'Le CSS DOIT prendre en charge les modes clair et sombre : définissez les styles clairs par défaut, utilisez @media (prefers-color-scheme: dark) et prévoyez les sélections [data-bs-theme="dark"] et [data-bs-theme="light"] lorsqu\'elles sont disponibles. Le HTML s\'exécute dans une iframe isolée ; fournissez ses propres styles sans dépendre de ceux de Moodle. Assurez un contraste suffisant pour les fonds, textes, bordures et commandes.';
$string['promptdescriptioncase'] = 'Créez une étude de cas dans laquelle les apprenants prennent des décisions, découvrent leurs conséquences et reçoivent des commentaires sur leurs choix. ChatGPT ou Claude demandera : « Quel scénario et quel contexte factuel ? », « Quel public et quel objectif pédagogique ? », « Combien de décisions inclure ? », « Peut-on revenir sur ses choix ? » et « Avez-vous des documents ou exemples de référence ? ». Les parcours et leurs conséquences doivent rester cohérents et compréhensibles.';
$string['promptdescriptioncustom'] = 'Construisez l\'activité pédagogique que vous imaginez, sans vous limiter aux autres onglets. ChatGPT ou Claude demandera d\'abord : « Quel type d\'activité souhaitez-vous créer ? ». Puis : « Comment doit-elle fonctionner ? », « Quel contenu, quel public et quel objectif pédagogique ? », « Quelles interactions inclure ? » et « Avez-vous des documents, images ou références ? ». Il attendra vos réponses et vos documents avant de générer le contenu.';
$string['promptdescriptioninteractive'] = 'Créez un quiz interactif dont les questions apparaissent dans un ordre fixe ou aléatoire et sont évaluées par Moodle. ChatGPT ou Claude demandera : « Quel sujet, quel public et quel objectif pédagogique ? », « Combien de questions ? », « Faut-il mélanger les questions ? », « Combien tirer au sort à chaque tentative ? » et « Avez-vous des fichiers ou références ? ». Les bonnes réponses restent dans le JSON côté serveur et jamais dans le HTML des apprenants.';
$string['promptdescriptionpage'] = 'Créez une page pédagogique comprenant des explications, exemples et illustrations, sans notes ni questions d\'évaluation. ChatGPT ou Claude demandera : « Préférez-vous une leçon explicative, un tutoriel pas à pas, un résumé, une étude de cas ou un autre format ? », « Quel sujet, quel public et quel objectif pédagogique ? », « Des consignes spécifiques ? » et « Disposez-vous de PDF, images ou références ? ». La page suivra vos réponses sans inventer d\'informations.';
$string['promptdescriptionsimulation'] = 'Créez une simulation interactive dans laquelle les apprenants modifient des paramètres et observent des résultats, graphiques ou schémas. ChatGPT ou Claude demandera : « Quel phénomène ou processus simuler ? », « Quel public ? », « Quelles valeurs les apprenants peuvent-ils modifier ? », « Quelles unités, formules et limites de sécurité ? » et « Quels résultats afficher ? ». Si les règles ne sont pas fiables, il demandera des sources et hypothèses avant de construire le modèle.';
$string['promptdescriptionsurvey'] = 'Créez une enquête de satisfaction qui affiche une question à la fois et enregistre les réponses sans attribuer de note. ChatGPT ou Claude demandera : « Quel est l\'objectif ? », « Qui répondra ? », « Combien de questions ? », « Quelles échelles ou formats de réponse ? », « Quelles réponses obligatoires ? » et « Avez-vous besoin de rapports ou d\'anonymat ? ». Les rapports regroupés et l\'anonymat ne sont pas garantis sans prise en charge supplémentaire côté serveur.';
$string['promptdescriptiontimeline'] = 'Créez une chronologie interactive avec des événements classés par date et des détails consultables. ChatGPT ou Claude demandera : « Quel sujet et quelle période ? », « Quels événements clés ? », « Quel public ? », « Préférez-vous des cartes, détails dépliables ou filtres ? » et « Disposez-vous de sources ou références ? ». Les dates et les faits doivent être vérifiables et aucune référence ne doit être inventée.';
$string['promptfreedom'] = '<strong>Ce ne sont que des suggestions :</strong> vous n\'avez pas à vous y limiter. Demandez à l\'intelligence artificielle d\'ajouter des fonctionnalités, des interactions ou toute autre idée que vous souhaitez. Votre imagination est la seule limite.';
$string['promptimagealt'] = 'Exemple visuel de {$a}.';
$string['promptpublishinstructions'] = 'Après avoir enregistré le brouillon, présentez un aperçu et proposez les actions Approuver et Rejeter dans la conversation, jamais dans le HTML. Ne publiez qu\'après l\'accord explicite de l\'enseignant. En cas de rejet, conservez le brouillon non publié et demandez les modifications.';
$string['promptselectcase'] = 'Étude de cas avec décisions';
$string['promptselectcustom'] = 'Je choisis';
$string['promptselecthelp'] = 'Choisissez le type d\'activité pour générer les instructions adaptées à ChatGPT ou Claude.';
$string['promptselectinteractive'] = 'Interactive (quiz, exercices...)';
$string['promptselectpage'] = 'Contenu (page)';
$string['promptselectsimulation'] = 'Simulation interactive';
$string['promptselectsurvey'] = 'Enquête de satisfaction';
$string['promptselecttimeline'] = 'Chronologie';
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
