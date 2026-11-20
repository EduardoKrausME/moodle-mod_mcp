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
 * English strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = 'This activity has not been published.';
$string['addinstance'] = 'Add an MCP activity';
$string['attempt'] = 'Attempt';
$string['attemptfinished'] = 'Your attempt has been submitted.';
$string['attemptinprogress'] = 'Your attempt is in progress.';
$string['beginattempt'] = 'Start assessment';
$string['connectionanother'] = 'Need to connect another ChatGPT or Claude app, or authorize your account again? Follow the instructions to set up another connection:';
$string['connectionconfigured'] = 'Your Moodle account already has an active MCP connection. Open the ChatGPT or Claude app where you authorized it and paste the prompt below to create a draft.';
$string['connectionnew'] = 'Set up MCP connection';
$string['connectionrequired'] = 'Your Moodle account does not yet have an active MCP connection. First connect ChatGPT or Claude to this Moodle site and authorize access with your account; then return here and use the prompt below.';
$string['content'] = 'HTML document (including CSS and JavaScript)';
$string['content_help'] = 'Use one complete HTML document with inline style and script. Scripts run in an isolated iframe.';
$string['copyinstructions'] = 'Copy this prompt to build the activity in ChatGPT or Claude:';
$string['draftsaved'] = 'Draft saved; students still see the published revision.';
$string['editcontent'] = 'Edit activity content';
$string['finishattempt'] = 'Submit answers and finish';
$string['grade'] = 'Maximum grade';
$string['manageactivity'] = 'Manage MCP activity';
$string['mcp:addinstance'] = 'Add MCP activities';
$string['mcp:manage'] = 'Edit and publish MCP activities';
$string['mcp:submit'] = 'Submit MCP assessment answers';
$string['mcp:view'] = 'View MCP activities';
$string['missingcontent'] = 'Build the activity';
$string['missingcontenthelp'] = 'Create this activity\'s content with ChatGPT or Claude using the Moodle MCP tools. Students cannot access it until a valid HTML revision is published.';
$string['modulename'] = 'MCP activity';
$string['modulename_help'] = 'Build interactive activities with a single HTML document, managed through ChatGPT or Claude using Moodle MCP.';
$string['modulenameplural'] = 'MCP activities';
$string['pluginadministration'] = 'MCP activity administration';
$string['pluginname'] = 'MCP activity';
$string['preview'] = 'Preview current draft';
$string['privacy:metadata:mcp_attempts'] = 'Stores the user and status of each assessment attempt and its native question usage.';
$string['privacy:metadata:mcp_attempts:userid'] = 'The user who submitted the attempt.';
$string['privacy:metadata:mcp_revisions'] = 'Stores the author of each HTML revision.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'The user who created this revision.';
$string['prompt'] = 'In Moodle course {$a->courseid}, edit the EXISTING mod_mcp activity "{$a->name}" (course-module ID {$a->cmid}, URL {$a->url}). Build an INTERACTIVE ACTIVITY, such as a quiz, exercise, challenge or simulation, in a complete accessible, responsive HTML document containing its CSS in <style> and JavaScript in <script>. Use the mod_mcp tools exposed by local_mcp to save a draft. For graded questions, create or select native Moodle question bank questions FIRST, obtain their actual IDs, and attach those IDs to the activity; never put correct answers or grades in HTML. Show me a preview and DO NOT publish until I approve.';
$string['promptpage'] = 'In Moodle course {$a->courseid}, edit the EXISTING mod_mcp activity "{$a->name}" (course-module ID {$a->cmid}, URL {$a->url}). Build an educational CONTENT PAGE, not a quiz or assessment. Create a complete, accessible, responsive HTML document organized into meaningful sections with headings, explanations, practical examples, and helpful visual elements as appropriate, including CSS in <style> and JavaScript in <script> when useful. Do not create graded questions, scoring, answer keys or question bank attachments. Use the mod_mcp tools exposed by local_mcp to save a draft, show me a preview and DO NOT publish until I approve.';
$string['promptselecthelp'] = 'Choose the type of activity to generate the appropriate prompt for ChatGPT or Claude.';
$string['promptselectinteractive'] = 'Interactive (quiz, exercises...)';
$string['promptselectpage'] = 'Content (page)';
$string['promptselecttitle'] = 'What do you want to build?';
$string['publish'] = 'Publish to students';
$string['published'] = 'The activity has been published.';
$string['questionnotconfigured'] = 'No graded questions have been attached to this activity.';
$string['questions'] = 'Assessment';
$string['response'] = 'Answer';
$string['revisionconflict'] = 'This activity was modified by another author. Read it again before saving.';
$string['saveanswers'] = 'Save answers';
$string['securecontent'] = 'Custom code runs without access to the Moodle session.';
$string['studentview'] = 'Student view';
$string['submissionsclosed'] = 'This attempt is already finished.';
$string['yourgrade'] = 'Your grade: {$a}';
