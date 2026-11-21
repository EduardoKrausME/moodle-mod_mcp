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
 * Russian strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = 'Это задание ещё не опубликовано.';
$string['addinstance'] = 'Добавить элемент MCP';
$string['attempt'] = 'Попытка';
$string['attemptfinished'] = 'Ваша попытка отправлена.';
$string['attemptinprogress'] = 'Ваша попытка продолжается.';
$string['beginattempt'] = 'Начать оценивание';
$string['completioninteractive'] = 'Когда интерактивный документ запрашивает завершение';
$string['completionnotallowed'] = 'Текущие правила завершения не позволяют завершить этот элемент через документ.';
$string['connectionanother'] = 'Нужно подключить ещё одно приложение ChatGPT или Claude либо повторно разрешить доступ к учётной записи? Следуйте этим инструкциям:';
$string['connectionnew'] = 'Настроить подключение MCP';
$string['connectionrequired'] = 'У вашей учётной записи Moodle пока нет активного подключения MCP. Подключите ChatGPT или Claude к этому сайту Moodle, разрешите доступ и вернитесь к инструкциям ниже.';
$string['content'] = 'HTML-документ (включая CSS и JavaScript)';
$string['content_help'] = 'Используйте полный HTML-документ со встроенными стилями и скриптами. Скрипты выполняются в изолированном iframe.';
$string['copyinstructions'] = 'Скопируйте эти инструкции в ChatGPT или Claude для создания задания:';
$string['draftsaved'] = 'Черновик сохранён; учащиеся по-прежнему видят опубликованную версию.';
$string['editcontent'] = 'Изменить содержимое задания';
$string['finishattempt'] = 'Отправить ответы и завершить';
$string['grade'] = 'Максимальная оценка';
$string['gradescalenotsupported'] = 'Элементы MCP поддерживают баллы или отсутствие оценки, но не шкалы оценивания.';
$string['manageactivity'] = 'Управление заданием MCP';
$string['mcp:addinstance'] = 'Добавлять задания MCP';
$string['mcp:manage'] = 'Редактировать и публиковать задания MCP';
$string['mcp:submit'] = 'Отправлять ответы на задания MCP';
$string['mcp:view'] = 'Просматривать задания MCP';
$string['missingcontent'] = 'Создать задание';
$string['missingcontenthelp'] = 'Создайте содержимое этого задания с помощью ChatGPT или Claude и инструментов Moodle MCP. Учащиеся получат доступ только после публикации действительной HTML-версии.';
$string['modulename'] = 'Задание MCP';
$string['modulename_help'] = 'Создавайте интерактивные задания в одном HTML-документе с помощью ChatGPT или Claude и Moodle MCP.';
$string['modulenameplural'] = 'Задания MCP';
$string['pluginadministration'] = 'Администрирование заданий MCP';
$string['pluginname'] = 'Задание MCP';
$string['preview'] = 'Предпросмотр текущего черновика';
$string['privacy:metadata:mcp_attempts'] = 'Хранит пользователя, статус каждой попытки оценивания и использование встроенных вопросов.';
$string['privacy:metadata:mcp_attempts:userid'] = 'Пользователь, отправивший попытку.';
$string['privacy:metadata:mcp_revisions'] = 'Хранит автора каждой версии HTML.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'Пользователь, создавший эту версию.';
$string['privacy:metadata:mcp_state'] = 'Хранит данные возобновления работы для каждого пользователя и признак завершения.';
$string['privacy:metadata:mcp_state:statejson'] = 'Данные о прогрессе, введённые учащимся в интерактивном документе.';
$string['privacy:metadata:mcp_state:userid'] = 'Пользователь, которому принадлежит сохранённый прогресс.';
$string['privacy:metadata:mcp_tracking'] = 'Хранит ограниченное количество событий взаимодействия, отправленных документом.';
$string['privacy:metadata:mcp_tracking:datajson'] = 'Данные результатов событий взаимодействия учащегося.';
$string['privacy:metadata:mcp_tracking:userid'] = 'Пользователь, взаимодействовавший с заданием.';
$string['progressconflict'] = 'Прогресс изменён в другом окне. Перезагрузите данные перед сохранением.';
$string['promptcolours'] = 'Для цветов темы {$a->theme} используйте CSS-переменные {$a->variables} с резервными значениями в var(), поскольку HTML выполняется в изолированном iframe.';
$string['promptdarkmode'] = 'CSS ДОЛЖЕН поддерживать светлый и тёмный режимы: задавайте светлые стили по умолчанию, используйте @media (prefers-color-scheme: dark) и учитывайте [data-bs-theme="dark"] и [data-bs-theme="light"], если они доступны. HTML выполняется в изолированном iframe и должен иметь собственные стили, не зависящие от страницы Moodle. Обеспечьте достаточную контрастность фона, текста, границ и элементов управления.';
$string['promptselecthelp'] = 'Выберите тип задания, чтобы создать соответствующие инструкции для ChatGPT или Claude.';
$string['promptselectinteractive'] = 'Интерактивное (тест, упражнения...)';
$string['promptselectpage'] = 'Материал (страница)';
$string['promptselecttitle'] = 'Что вы хотите создать?';
$string['publish'] = 'Опубликовать для учащихся';
$string['published'] = 'Задание опубликовано.';
$string['questionnotconfigured'] = 'К этому заданию не привязаны оцениваемые вопросы.';
$string['questions'] = 'Оценивание';
$string['response'] = 'Ответ';
$string['revisionconflict'] = 'Другой автор изменил это задание. Обновите данные перед сохранением.';
$string['saveanswers'] = 'Сохранить ответы';
$string['savedraft'] = 'Сохранить черновик';
$string['securecontent'] = 'Пользовательский код выполняется без доступа к сессии Moodle.';
$string['studentview'] = 'Просмотр для учащегося';
$string['submissionsclosed'] = 'Эта попытка уже завершена.';
$string['trackinglimit'] = 'Достигнут предел количества событий взаимодействия с этим заданием.';
$string['yourgrade'] = 'Ваша оценка: {$a}';
