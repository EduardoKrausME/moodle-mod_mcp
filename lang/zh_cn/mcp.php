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
$string['addinstance'] = '添加互动体验';
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
$string['gradescalenotsupported'] = '互动体验支持按分数评分或不评分，但不支持等级量表。';
$string['manageactivity'] = '管理互动体验';
$string['mcp:addinstance'] = '添加互动体验';
$string['mcp:manage'] = '编辑并发布互动体验';
$string['mcp:submit'] = '提交互动体验测评答案';
$string['mcp:view'] = '查看互动体验';
$string['missingcontent'] = '创建活动';
$string['missingcontenthelp'] = '使用 ChatGPT 或 Claude 通过 Moodle MCP 工具创建此活动内容。在发布有效的 HTML 版本之前，学生无法访问该活动。';
$string['modulename'] = '互动体验';
$string['modulename_help'] = '使用 ChatGPT 或 Claude 通过 Moodle MCP 管理 HTML、CSS 和 JavaScript，创建互动体验。';
$string['modulenameplural'] = '互动体验';
$string['pluginadministration'] = '互动体验管理';
$string['pluginname'] = '互动体验';
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
$string['promptcolours'] = '主题 {$a->theme} 的品牌颜色强制规则：变量 {$a->variables} 是品牌颜色的唯一来源。不要虚构其他配色方案，也不要在主视觉区域、渐变及其每个色标、按钮、链接、边框、图标、卡片、hover/focus 状态或伪元素中硬编码 hex、rgb()/rgba()、hsl()/hsla() 或颜色名称。所有品牌颜色必须使用 var({$a->primary}, #0d6efd) and var({$a->complementary}, var({$a->primary}, #0d6efd)), or local tokens derived from these variables. Valid example: .hero { background: linear-gradient(123deg, var({$a->primary}, #0d6efd), var({$a->complementary}, var({$a->primary}, #0d6efd))); }. 对于中间色和透明度，请通过 color-mix() 混合这些变量。固定颜色仅允许作为 var() 内的回退值，或用于中性色与无障碍语义状态，绝不能替代品牌颜色。交付 HTML 前请检查包括媒体查询和伪元素在内的全部 CSS，并替换任何虚构的品牌颜色。mod_mcp 会将主题变量的当前值传递到 Moodle 的隔离 iframe；独立预览和应用程序仍然需要回退值。';
$string['promptdarkmode'] = 'CSS 必须同时支持浅色和深色模式：默认使用浅色样式，通过 @media (prefers-color-scheme: dark) 响应系统偏好，并在可用时支持 [data-bs-theme="dark"] 和 [data-bs-theme="light"]。HTML 在隔离的 iframe 中运行，必须提供自己的样式，不能依赖 Moodle 父页面的样式或属性。确保背景、文本、边框和控件具有足够的对比度。';
$string['promptdescriptioncase'] = '创建一个案例研究，让学习者做出决定、探索后果并获得针对选择的反馈。ChatGPT 或 Claude 会询问教师：“要采用什么情境和事实背景？”，“目标受众和学习目标是什么？”，“需要多少个决策节点？”，“学生能否返回并重新考虑其他选项？”以及“是否有可参考的文档或实例？”。不同决策路线必须保持逻辑一致，具有明确的后果和解释性反馈，不能出现不完整或无法继续的分支。教师还可以提供参考资料以保证事实准确。';
$string['promptdescriptioncustom'] = '创建你真正想要的学习活动，不必局限于其他选项卡提供的类型。ChatGPT 或 Claude 会先问：“你想创建哪种活动？”，然后根据回答继续询问：“它应该如何运行？”，“内容、学习对象和学习目标是什么？”，“需要哪些交互方式？”以及“你有文档、图片或其他参考资料吗？”。在收到你的回答和材料之前，不会直接生成内容；你也可以自由提出其他功能或自己的教学构想。';
$string['promptdescriptioninteractive'] = '创建交互式测验，题目可以采用固定或随机顺序，并由 Moodle 负责评分。开始前，ChatGPT 或 Claude 会询问：“主题、受众和学习目标是什么？”，“需要多少道题？”，“是否打乱题目顺序？”，“每次作答随机抽取多少道？”以及“你有文件或参考材料吗？”。正确答案保存在服务器端 JSON 中，不能嵌入学生能够看到的 HTML，测验评分应由服务端执行，而不是由页面脚本自行计算。';
$string['promptdescriptionpage'] = '创建包含讲解、示例和可视化内容的教学页面，不包含成绩或评价题目。ChatGPT 或 Claude 会询问：“你更希望采用讲解课程、分步教程、摘要、案例研究还是其他形式？”，“主题、目标受众和学习目标是什么？”，“有没有特别要求？”以及“是否有 PDF、图片或其他参考材料？”。页面应根据教师的回答组织内容，使用教师提供的资料，不能虚构不存在的引用或事实。教学内容可以按需要安排清晰的章节。';
$string['promptdescriptionsimulation'] = '创建交互式模拟，让学习者调整参数并观察结果、图表或示意图。ChatGPT 或 Claude 会询问：“要模拟什么现象或过程？”，“面向哪些学生？”，“学习者可以改变哪些数值？”，“适用的单位、公式和安全范围是什么？”以及“应展示哪些输出结果？”。如果缺少可靠的规则、公式或事实基础，必须在建立模型之前向教师索取来源和假设，避免生成看似准确但缺乏依据的结果。';
$string['promptdescriptionsurvey'] = '创建满意度调查，每次展示一道题并保存回答，不为回答打分。ChatGPT 或 Claude 会询问：“调查的目标是什么？”，“谁来填写？”，“需要多少个问题？”，“使用什么量表或回答格式？”，“哪些答案必须填写？”以及“是否需要报表或匿名性？”。如果服务端没有额外支持，就不能保证汇总报告或匿名性；必须先向教师说明这些限制，再设计问题和交互流程。调查结果应遵守实际的数据保存和访问规则。';
$string['promptdescriptiontimeline'] = '创建按日期排列的可探索时间线，允许学习者查看每个事件的详细资料。ChatGPT 或 Claude 会询问：“主题和时间范围是什么？”，“需要展示哪些关键事件？”，“目标受众是谁？”，“更喜欢事件卡片、可展开详情还是筛选器？”以及“有没有可靠的来源或参考材料？”。日期和其他历史事实必须可以核实，不能随意编造事件、来源或引用。时间线中的交互应有助于理解事件之间的顺序与关联。';
$string['promptfreedom'] = '<strong>这些仅供参考：</strong>你不必局限于这些建议。可以要求人工智能添加任何你想要的功能、交互或其他创意，想象力就是唯一的边界。';
$string['promptimagealt'] = '{$a} 的可视化示例。';
$string['promptpublishinstructions'] = '保存草稿后，在对话中提供预览以及“批准”和“拒绝”两个操作，不要将这些操作放入 HTML。只有教师明确批准后才能发布；如果被拒绝，请保留未发布的草稿并询问需要修改的内容。';
$string['promptselectcase'] = '基于决策的案例研究';
$string['promptselectcustom'] = '我来选择';
$string['promptselecthelp'] = '选择活动类型，以生成适用于 ChatGPT 或 Claude 的提示词。';
$string['promptselectinteractive'] = '交互式（测验、练习等）';
$string['promptselectpage'] = '内容（页面）';
$string['promptselectsimulation'] = '交互式模拟';
$string['promptselectsurvey'] = '满意度调查';
$string['promptselecttimeline'] = '时间线';
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
