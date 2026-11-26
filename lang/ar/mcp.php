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

$string['activitynotpublished'] = 'لا يحتوي هذا النشاط على محتوى بعد.';
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
$string['editcontent'] = 'تحرير محتوى النشاط';
$string['finishattempt'] = 'إرسال الإجابات والإنهاء';
$string['grade'] = 'الدرجة القصوى';
$string['gradescalenotsupported'] = 'تدعم التجارب التفاعلية التقييم بالنقاط أو دون درجة، ولا تدعم سلالم التقدير.';
$string['manageactivity'] = 'إدارة تجربة تفاعلية';
$string['mcp:addinstance'] = 'إضافة تجارب تفاعلية';
$string['mcp:manage'] = 'تحرير التجارب التفاعلية';
$string['mcp:submit'] = 'إرسال إجابات تقييم التجربة التفاعلية';
$string['mcp:view'] = 'عرض التجارب التفاعلية';
$string['missingcontent'] = 'إنشاء النشاط';
$string['missingcontenthelp'] = 'أنشئ محتوى هذا النشاط باستخدام ChatGPT أو Claude وأدوات Moodle MCP. يصبح المحتوى متاحًا بعد حفظه وفق إعدادات الرؤية في Moodle.';
$string['modulename'] = 'تجربة تفاعلية';
$string['modulename_help'] = 'أنشئ تجارب تفاعلية باستخدام HTML وCSS وJavaScript، وأدِرها عبر ChatGPT أو Claude باستخدام Moodle MCP.';
$string['modulenameplural'] = 'تجارب تفاعلية';
$string['pluginadministration'] = 'إدارة التجارب التفاعلية';
$string['pluginname'] = 'تجربة تفاعلية';
$string['preview'] = 'معاينة المحتوى';
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
$string['promptcolours'] = 'قاعدة إلزامية للهوية البصرية للسمة {$a->theme}: المتغيرات {$a->variables} هي المصادر الوحيدة لألوان العلامة. لا تخترع لوحة أخرى ولا تستخدم ألوان hex أو rgb()/rgba() أو hsl()/hsla() أو أسماء ألوان ثابتة للأقسام الرئيسية أو التدرجات ونقاطها أو الأزرار والروابط والحدود والرموز والبطاقات وحالات hover/focus والعناصر الزائفة. جميع ألوان العلامة يجب أن تستخدم var({$a->primary}, #0d6efd) و var({$a->complementary}, var({$a->primary}, #0d6efd)), o tokens locales derivados de estas variables. Ejemplo válido: .hero { background: linear-gradient(123deg, var({$a->primary}, #0d6efd), var({$a->complementary}, var({$a->primary}, #0d6efd))); }. استخدم color-mix() مع هذه المتغيرات للألوان الوسيطة والشفافية. يُسمح بالألوان الثابتة فقط كقيمة احتياطية داخل var() أو للألوان المحايدة وحالات الوصول الدلالية، وليس بدلاً من ألوان العلامة. افحص جميع قواعد CSS واستعلامات الوسائط والعناصر الزائفة قبل التسليم واستبدل أي لون مخترع. ينقل mod_mcp قيم السمة الحالية إلى iframe المعزول في Moodle؛ وتظل القيم الاحتياطية ضرورية للمعاينات المستقلة والتطبيق.';
$string['promptdarkmode'] = 'يجب أن يدعم CSS الوضعين الفاتح والداكن: اجعل الأنماط الفاتحة افتراضية، واستخدم @media (prefers-color-scheme: dark) وادعم [data-bs-theme="dark"] و[data-bs-theme="light"] عند توفرهما. يعمل HTML في iframe معزول ويجب أن يعرّف أنماطه الخاصة دون الاعتماد على أنماط صفحة Moodle. احرص على تباين واضح بين الخلفيات والنصوص والحدود وعناصر التحكم.';
$string['promptdescriptioncase'] = 'أنشئ دراسة حالة يتخذ فيها المتعلم قرارات ويستكشف نتائجها ويتلقى تفسيرًا لاختياراته. سيسأل ChatGPT أو Claude المعلم: «ما السيناريو والسياق الواقعي؟»، «من الجمهور وما الهدف التعليمي؟»، «كم قرارًا يجب أن تتضمنه التجربة؟»، «هل يستطيع الطلاب العودة ومراجعة الاختيارات؟» و«هل لديك مستندات أو أمثلة مرجعية؟». يجب أن تتفرع المسارات باتساق وتعرض العواقب والتعليقات المفيدة من دون طرق مسدودة.';
$string['promptdescriptioncustom'] = 'أنشئ النشاط التعليمي الذي تتخيله من دون التقيد بالأنواع الأخرى. سيسأل ChatGPT أو Claude أولاً: «ما نوع النشاط الذي تريد إنشاءه؟» ثم، حسب إجابتك: «كيف ينبغي أن يعمل؟»، «ما المحتوى والجمهور والهدف التعليمي؟»، «ما التفاعلات التي تود تضمينها؟» و«هل لديك مستندات أو صور أو مراجع؟». ينتظر إجاباتك والمواد التي تقدمها قبل توليد المحتوى، ويمكنك طلب خصائص إضافية بحرية.';
$string['promptdescriptioninteractive'] = 'أنشئ اختبارًا تفاعليًا بأسئلة بترتيب ثابت أو عشوائي يصححها Moodle. قبل البدء يسأل ChatGPT أو Claude: «ما الموضوع والجمهور والهدف التعليمي؟»، «كم سؤالًا تريد؟»، «هل نخلط الأسئلة؟»، «كم سؤالًا نختار عشوائيًا في كل محاولة؟» و«هل لديك ملفات أو مواد مرجعية؟». تبقى مفاتيح الإجابات في JSON على الخادم ولا تُدرج في HTML المعروض للطالب، ويجري التصحيح على الخادم.';
$string['promptdescriptionpage'] = 'أنشئ صفحة محتوى تعليمية فيها شروحات وأمثلة ورسوم بصرية، من دون درجات أو أسئلة تقييم. يسأل ChatGPT أو Claude: «هل تفضل درسًا توضيحيًا أو شرحًا خطوة بخطوة أو ملخصًا أو دراسة حالة أو تنسيقًا آخر؟»، «ما الموضوع والجمهور والهدف التعليمي؟»، «هل لديك تعليمات محددة؟» و«هل لديك ملفات PDF أو صور أو مراجع؟». ستتبع الصفحة إجاباتك وتستخدم المواد المقدمة بدلاً من اختراع مصادر.';
$string['promptdescriptionsimulation'] = 'أنشئ محاكاة تفاعلية يغيّر فيها المتعلم القيم ويراقب النتائج أو الرسوم البيانية أو المخططات. يسأل ChatGPT أو Claude: «ما الظاهرة أو العملية المطلوب محاكاتها؟»، «من الجمهور؟»، «ما المعلمات التي يمكن للمتعلم تغييرها؟»، «ما الوحدات والمعادلات والحدود الآمنة؟» و«ما النتائج المطلوب عرضها؟». إذا لم تتوافر قواعد موثوقة، فسيطلب المصادر والافتراضات قبل إنشاء النموذج حتى لا يقدم نتائج غير مدعومة.';
$string['promptdescriptionsurvey'] = 'أنشئ استبيان رضا يعرض سؤالاً في كل مرة ويحفظ الإجابات من دون درجات. يسأل ChatGPT أو Claude: «ما الهدف من الاستبيان؟»، «من سيجيب؟»، «كم سؤالاً تريد؟»، «ما المقاييس أو أنواع الإجابات؟»، «أي إجابات إلزامية؟» و«هل تحتاج إلى تقارير أو إخفاء الهوية؟». لا يمكن ضمان التقارير المجمعة أو إخفاء الهوية من دون دعم إضافي من الخادم، ويجب توضيح هذه القيود للمعلم.';
$string['promptdescriptiontimeline'] = 'أنشئ خطًا زمنيًا يمكن استكشافه لأحداث مرتبة بالتاريخ، مع تفاصيل يمكن للمتعلمين فتحها. يسأل ChatGPT أو Claude: «ما الموضوع والفترة الزمنية؟»، «ما الأحداث الرئيسية؟»، «من الجمهور؟»، «هل تفضل بطاقات الأحداث أم التفاصيل القابلة للتوسيع أم عوامل التصفية؟» و«هل لديك مصادر أو مواد مرجعية؟». يجب التحقق من التواريخ والحقائق وعدم اختراع أحداث أو مراجع غير موجودة.';
$string['promptfreedom'] = '<strong>هذه مجرد اقتراحات:</strong> لا تحتاج إلى الاكتفاء بها. اطلب من الذكاء الاصطناعي إضافة أي ميزات أو تفاعلات أو أفكار أخرى تريدها. الحد الوحيد هو خيالك.';
$string['promptimagealt'] = 'مثال مرئي على {$a}.';
$string['promptselectcase'] = 'دراسة حالة قائمة على القرارات';
$string['promptselectcustom'] = 'أختار بنفسي';
$string['promptselecthelp'] = 'اختر نوع النشاط لإنشاء التعليمات المناسبة لـ ChatGPT أو Claude.';
$string['promptselectinteractive'] = 'تفاعلي (اختبار، تمارين...)';
$string['promptselectpage'] = 'محتوى (صفحة)';
$string['promptselectsimulation'] = 'محاكاة تفاعلية';
$string['promptselectsurvey'] = 'استبيان رضا';
$string['promptselecttimeline'] = 'خط زمني';
$string['promptselecttitle'] = 'ماذا تريد إنشاءه؟';
$string['questionnotconfigured'] = 'لم تُربط بهذا النشاط أي أسئلة خاضعة للتقييم.';
$string['questions'] = 'التقييم';
$string['response'] = 'الإجابة';
$string['revisionconflict'] = 'عدّل مؤلف آخر هذا النشاط. أعد تحميله قبل الحفظ.';
$string['saveanswers'] = 'حفظ الإجابات';
$string['securecontent'] = 'تعمل الشيفرة المخصصة دون الوصول إلى جلسة Moodle.';
$string['studentview'] = 'عرض الطالب';
$string['submissionsclosed'] = 'انتهت هذه المحاولة بالفعل.';
$string['trackinglimit'] = 'تم بلوغ الحد الأقصى لأحداث التفاعل في هذا النشاط.';
$string['yourgrade'] = 'درجتك: {$a}';
