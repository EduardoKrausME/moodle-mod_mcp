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
 * Arabic strings for MCP activity.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['activitynotpublished'] = 'لم يتم نشر هذا النشاط بعد.';
$string['addinstance'] = 'إضافة تجربة تفاعلية';
$string['attempt'] = 'محاولة';
$string['attemptfinished'] = 'تم إرسال محاولتك.';
$string['attemptinprogress'] = 'محاولتك قيد التنفيذ.';
$string['beginattempt'] = 'بدء التقييم';
$string['completioninteractive'] = 'عندما يطلب المستند التفاعلي إكمال النشاط';
$string['completionnotallowed'] = 'لا تسمح قواعد الإكمال الحالية بإنهاء هذا النشاط من خلال المستند.';
$string['connectionanother'] = 'هل تحتاج إلى ربط تطبيق ChatGPT أو Claude آخر أو إعادة تفويض حسابك؟ اتبع التعليمات التالية:';
$string['connectionnew'] = 'إعداد اتصال MCP';
$string['connectionrequired'] = 'لا يتوفر لحسابك في Moodle اتصال MCP نشط حتى الآن. اربط ChatGPT أو Claude بموقع Moodle هذا، وامنح الإذن باستخدام حسابك، ثم عد لاستخدام التعليمات أدناه.';
$string['content'] = 'مستند HTML (بما في ذلك CSS وJavaScript)';
$string['content_help'] = 'استخدم مستند HTML كاملاً يحتوي على أنماط ونصوص برمجية مضمنة. تُنفذ النصوص داخل إطار iframe معزول.';
$string['copyinstructions'] = 'انسخ هذه التعليمات إلى ChatGPT أو Claude لإنشاء النشاط:';
$string['draftsaved'] = 'تم حفظ المسودة؛ لا يزال الطلاب يرون النسخة المنشورة.';
$string['editcontent'] = 'تحرير محتوى النشاط';
$string['finishattempt'] = 'إرسال الإجابات والإنهاء';
$string['grade'] = 'الدرجة القصوى';
$string['gradescalenotsupported'] = 'تدعم التجارب التفاعلية التقييم بالنقاط أو دون درجة، ولا تدعم سلالم التقدير.';
$string['manageactivity'] = 'إدارة تجربة تفاعلية';
$string['mcp:addinstance'] = 'إضافة تجارب تفاعلية';
$string['mcp:manage'] = 'تحرير التجارب التفاعلية ونشرها';
$string['mcp:submit'] = 'إرسال إجابات تقييم التجربة التفاعلية';
$string['mcp:view'] = 'عرض التجارب التفاعلية';
$string['missingcontent'] = 'إنشاء النشاط';
$string['missingcontenthelp'] = 'أنشئ محتوى هذا النشاط باستخدام ChatGPT أو Claude وأدوات Moodle MCP. لن يتمكن الطلاب من الوصول إليه حتى تُنشر نسخة HTML صالحة.';
$string['modulename'] = 'تجربة تفاعلية';
$string['modulename_help'] = 'أنشئ تجارب تفاعلية باستخدام HTML وCSS وJavaScript، وأدِرها عبر ChatGPT أو Claude باستخدام Moodle MCP.';
$string['modulenameplural'] = 'تجارب تفاعلية';
$string['pluginadministration'] = 'إدارة التجارب التفاعلية';
$string['pluginname'] = 'تجربة تفاعلية';
$string['preview'] = 'معاينة المسودة الحالية';
$string['privacy:metadata:mcp_attempts'] = 'يخزن المستخدم وحالة كل محاولة تقييم واستخدام الأسئلة الأصلية.';
$string['privacy:metadata:mcp_attempts:userid'] = 'المستخدم الذي أرسل المحاولة.';
$string['privacy:metadata:mcp_revisions'] = 'يخزن مؤلف كل مراجعة HTML.';
$string['privacy:metadata:mcp_revisions:createdby'] = 'المستخدم الذي أنشأ هذه المراجعة.';
$string['privacy:metadata:mcp_state'] = 'يخزن بيانات استئناف التقدم لكل مستخدم وعلامة الإكمال المخصصة.';
$string['privacy:metadata:mcp_state:statejson'] = 'بيانات التقدم التي أدخلها الطالب في المستند التفاعلي.';
$string['privacy:metadata:mcp_state:userid'] = 'المستخدم الذي تعود إليه بيانات التقدم المحفوظة.';
$string['privacy:metadata:mcp_tracking'] = 'يخزن أحداث التفاعل المحدودة المرسلة من المستند.';
$string['privacy:metadata:mcp_tracking:datajson'] = 'بيانات نتائج الأحداث الناتجة عن تفاعل الطالب.';
$string['privacy:metadata:mcp_tracking:userid'] = 'المستخدم الذي تفاعل مع النشاط.';
$string['progressconflict'] = 'تم تحديث التقدم في نافذة أخرى. أعد تحميله قبل الحفظ.';
$string['promptcolours'] = 'بالنسبة لألوان السمة {$a->theme}، استخدم متغيرات CSS ‏{$a->variables} مع قيم احتياطية في var() لأن HTML يُعرض داخل iframe معزول.';
$string['promptdarkmode'] = 'يجب أن يدعم CSS الوضعين الفاتح والداكن: اجعل الأنماط الفاتحة افتراضية، واستخدم @media (prefers-color-scheme: dark) وادعم [data-bs-theme="dark"] و[data-bs-theme="light"] عند توفرهما. يعمل HTML في iframe معزول ويجب أن يعرّف أنماطه الخاصة دون الاعتماد على أنماط صفحة Moodle. احرص على تباين واضح بين الخلفيات والنصوص والحدود وعناصر التحكم.';
$string['promptselecthelp'] = 'اختر نوع النشاط لإنشاء التعليمات المناسبة لـ ChatGPT أو Claude.';
$string['promptselectinteractive'] = 'تفاعلي (اختبار، تمارين...)';
$string['promptselectpage'] = 'محتوى (صفحة)';
$string['promptselecttitle'] = 'ماذا تريد إنشاءه؟';
$string['publish'] = 'نشر للطلاب';
$string['published'] = 'تم نشر النشاط.';
$string['questionnotconfigured'] = 'لم تُربط بهذا النشاط أي أسئلة خاضعة للتقييم.';
$string['questions'] = 'التقييم';
$string['response'] = 'الإجابة';
$string['revisionconflict'] = 'عدّل مؤلف آخر هذا النشاط. أعد تحميله قبل الحفظ.';
$string['saveanswers'] = 'حفظ الإجابات';
$string['savedraft'] = 'حفظ المسودة';
$string['securecontent'] = 'تعمل الشيفرة المخصصة دون الوصول إلى جلسة Moodle.';
$string['studentview'] = 'عرض الطالب';
$string['submissionsclosed'] = 'انتهت هذه المحاولة بالفعل.';
$string['trackinglimit'] = 'تم بلوغ الحد الأقصى لأحداث التفاعل في هذا النشاط.';
$string['yourgrade'] = 'درجتك: {$a}';
