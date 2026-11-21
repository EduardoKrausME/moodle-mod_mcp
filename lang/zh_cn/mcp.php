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
 * Simplified Chinese strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = '此活动尚未发布。';
$string['addinstance'] = '添加 MCP 活动';
$string['attempt'] = '尝试';
$string['attemptfinished'] = '您的尝试已提交。';
$string['attemptinprogress'] = '您的尝试正在进行中。';
$string['beginattempt'] = '开始测评';
$string['completioninteractive'] = '当交互式文档请求完成活动时';
$string['completionnotallowed'] = '当前活动完成规则不允许通过文档完成此活动。';
$string['connectionanother'] = '需要连接另一个 ChatGPT 或 Claude 应用，或重新授权您的账户？请按照以下说明操作：';
$string['connectionnew'] = '设置 MCP 连接';
$string['connectionrequired'] = '您的 Moodle 账户尚无有效的 MCP 连接。请先将 ChatGPT 或 Claude 连接到此 Moodle 站点，使用账户授权访问，然后返回此处使用以下提示词。';
$string['content'] = 'HTML 文档（包含 CSS 和 JavaScript）';
$string['content_help'] = '请使用包含内联样式和脚本的完整 HTML 文档。脚本在隔离的 iframe 中运行。';
$string['copyinstructions'] = '将以下提示词复制到 ChatGPT 或 Claude 以创建活动：';
$string['draftsaved'] = '草稿已保存；学生仍会看到已发布的版本。';
$string['editcontent'] = '编辑活动内容';
$string['finishattempt'] = '提交答案并结束';
$string['grade'] = '最高成绩';
$string['gradescalenotsupported'] = 'MCP 活动支持按分数评分或不评分，但不支持等级量表。';
$string['manageactivity'] = '管理 MCP 活动';
$string['mcp:addinstance'] = '添加 MCP 活动';
$string['mcp:manage'] = '编辑并发布 MCP 活动';
$string['mcp:submit'] = '提交 MCP 测评答案';
$string['mcp:view'] = '查看 MCP 活动';
$string['missingcontent'] = '创建活动';
$string['missingcontenthelp'] = '使用 ChatGPT 或 Claude 通过 Moodle MCP 工具创建此活动内容。在发布有效的 HTML 版本之前，学生无法访问该活动。';
$string['modulename'] = 'MCP 活动';
$string['modulename_help'] = '使用 ChatGPT 或 Claude 通过 Moodle MCP 管理单个 HTML 文档，创建交互式活动。';
$string['modulenameplural'] = 'MCP 活动';
$string['pluginadministration'] = 'MCP 活动管理';
$string['pluginname'] = 'MCP 活动';
$string['preview'] = '预览当前草稿';
$string['privacy:metadata:mcp_attempts'] = '存储每次测评尝试的用户、状态及原生题目使用情况。';
$string['privacy:metadata:mcp_attempts:userid'] = '提交尝试的用户。';
$string['privacy:metadata:mcp_revisions'] = '存储每个 HTML 版本的作者。';
$string['privacy:metadata:mcp_revisions:createdby'] = '创建该版本的用户。';
$string['privacy:metadata:mcp_state'] = '存储每位用户的恢复进度数据及自定义完成标记。';
$string['privacy:metadata:mcp_state:statejson'] = '学生在交互式文档中输入的进度数据。';
$string['privacy:metadata:mcp_state:userid'] = '已保存进度所属的用户。';
$string['privacy:metadata:mcp_tracking'] = '存储文档提交的有限数量的交互事件。';
$string['privacy:metadata:mcp_tracking:datajson'] = '学生交互产生的事件结果数据。';
$string['privacy:metadata:mcp_tracking:userid'] = '与活动交互的用户。';
$string['progressconflict'] = '进度已在另一个窗口中更新。请重新加载后再保存。';
$string['promptcolours'] = '对于 {$a->theme} 主题配色，请使用 CSS 变量 {$a->variables}，并在 var() 中设置备用值，因为 HTML 在隔离的 iframe 中显示。';
$string['promptdarkmode'] = 'CSS 必须同时支持浅色和深色模式：默认使用浅色样式，通过 @media (prefers-color-scheme: dark) 响应系统偏好，并在可用时支持 [data-bs-theme="dark"] 和 [data-bs-theme="light"]。HTML 在隔离的 iframe 中运行，必须提供自己的样式，不能依赖 Moodle 父页面的样式或属性。确保背景、文本、边框和控件具有足够的对比度。';
$string['promptselecthelp'] = '选择活动类型，以生成适用于 ChatGPT 或 Claude 的提示词。';
$string['promptselectinteractive'] = '交互式（测验、练习等）';
$string['promptselectpage'] = '内容（页面）';
$string['promptselecttitle'] = '您想创建什么？';
$string['publish'] = '向学生发布';
$string['published'] = '活动已发布。';
$string['questionnotconfigured'] = '此活动尚未关联计分题目。';
$string['questions'] = '测评';
$string['response'] = '回答';
$string['revisionconflict'] = '另一位作者已修改此活动。请重新读取内容后再保存。';
$string['saveanswers'] = '保存答案';
$string['savedraft'] = '保存草稿';
$string['securecontent'] = '自定义代码运行时无法访问 Moodle 会话。';
$string['studentview'] = '学生视图';
$string['submissionsclosed'] = '此尝试已经结束。';
$string['trackinglimit'] = '此活动的交互事件数量已达到上限。';
$string['yourgrade'] = '您的成绩：{$a}';
