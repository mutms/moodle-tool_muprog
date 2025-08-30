<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Program enrolment plugin language file.
 *
 * @package    tool_muprog
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @copyright  2025 Petr Skoda
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addset'] = 'أضف مرحلة جديدة';
$string['allocation'] = 'الانضمام';
$string['allocation_archive'] = 'أرشفة سجل الانضمام';
$string['allocation_reset'] = 'حذف التقد في المستوى';
$string['allocation_reset_updateallocation'] = 'تحديث سجل الانضمام';
$string['allocation_restore'] = 'استعادة سجل الانضمام';
$string['allocation_update'] = 'تحديث سجل الانضمام';
$string['allocationdate'] = 'تاريخ الانضمام';
$string['allocationend'] = 'انتهاء الانضمام';
$string['allocationend_help'] = 'يعتمد معنى تاريخ انتهاء التخصيص على مصادر التخصيص المُفعّلة. عادةً، لا يُمكن إجراء تخصيصات جديدة بعد هذا التاريخ إذا تم تحديده.';
$string['allocations'] = 'الانضمامات';
$string['allocationsources'] = 'طُرق الانضمام';
$string['allocationstart'] = 'يبدأ الانضمام';
$string['allocationstart_help'] = 'يعتمد معنى تاريخ بدء التخصيص على مصادر التخصيص المُفعّلة. عادةً، لا يُمكن إجراء تخصيصات جديدة إلا بعد هذا التاريخ إذا تم تحديده..';
$string['allprograms'] = 'كل المستويات';
$string['appendinto'] = 'أضف إلى داخل عنصر';
$string['appenditem'] = 'أضف عنصراً (مادة/مرحلة)';
$string['archived'] = 'تمت الأرشفة';
$string['calendarprogramdue'] = '{$a} مستحق';
$string['calendarprogramend'] = '{$a} منتهي';
$string['calendarprogramstart'] = '{$a} قد بدأ';
$string['catalogue'] = 'إجراءات البرنامج';
$string['catalogue_actions'] = 'إجراءات الكتالوج';
$string['catalogue_dofilter'] = 'بحث';
$string['catalogue_resetfilter'] = 'احذف';
$string['catalogue_searchtext'] = 'نص البحث';
$string['catalogue_tag'] = 'الفلترة عبر الوسوم';
$string['certificatetemplatechoose'] = 'اختر قالباً....';
$string['cohorts'] = 'مرئي للدفعات';
$string['cohorts_help'] = 'المستويات غير العامة يمكن جعلها مرئية لطلاب الدفعة

حالة الظهور لا تؤثر في المستويات التي تم الانضمام لها مسبقاً.';
$string['columnusedalready'] = 'تم استعمال هذا العمود بالفعل';
$string['completiondate'] = 'تاريخ الانتهاء';
$string['completiondelay'] = 'تأخير الإنجاز';
$string['completionoverride'] = 'تجاوز الإكمال';
$string['creategroups'] = 'مجموعات المادة';
$string['creategroups_help'] = 'إذا تم تمكينه، فسيتم إنشاء مجموعة في كل دورة تدريبية تمت إضافتها إلى البرنامج وسيتم إضافة جميع المستخدمين المخصصين كأعضاء في المجموعة.';
$string['currentcontextonly'] = 'استبعاد الفئات الفرعية';
$string['customfields'] = 'الحقول الإضافية لكل مستوى';
$string['customfields_allocation'] = 'الحقول الإضافية لسجل الانضمام للمستوى';
$string['customfieldsettings'] = 'إعدادات الحقول الإضافية الشائعة للمستوى';
$string['customfieldvisible:allocated'] = 'الطلاب المنضمين للمستوى';
$string['customfieldvisible:allocatee'] = 'الطالب المنضم';
$string['customfieldvisible:everyone'] = 'الجميع يمكنه مشاهدة تفاصيل باقي المستويات';
$string['customfieldvisible:viewcapability'] = 'المستخدمون بصلاحيات مشاهدة المستويات';
$string['customfieldvisibleto'] = 'محتوى الحقل سيكون ظاهراً ل: ';
$string['deleteallocation'] = 'احذ سجل الانضمام للمستوى';
$string['deletecourse'] = 'احذف المادة';
$string['deleteset'] = 'احذ المرحلة';
$string['deletetraining'] = 'احذف التدريب';
$string['duedate'] = 'تاريخ الاستحقاق';
$string['enrolrole'] = 'دور المادة';
$string['enrolrole_desc'] = 'اختر دوراً للمنضمين للمادة';
$string['errorcontentproblem'] = '!تم رصد مشاكل في ترتيب و ترككيب محتوى المادة, لن يتم تتبع إكمال المحتوى في المستوى بشكل صحيح';
$string['errorcoursemissing'] = 'المادة مفقودة';
$string['errorcoursesmissing'] = 'مواد مفقودة: {$a}';
$string['errordifferenttenant'] = 'لا يمكن الوصول إلى المستوى من مستأجر آخر';
$string['errorinvalidoverridedates'] = 'Invalid date overrides';
$string['errornoallocation'] = 'لا يوجد طلاب منضمين لهذا المستوى';
$string['errornoallocations'] = 'لا يوجد سجل انضمام للطلاب';
$string['errornomyprograms'] = 'أنت غير منضم لأي مستوى.';
$string['errornoprograms'] = 'لا يوجد أي مستويات.';
$string['errornorequests'] = 'لا يوجد طلبات للالتحاق بالمستوى';
$string['errornotenabled'] = 'إضافة المستويات غير مفعلة';
$string['event_allocation_archived'] = 'تم أرشفة سجل انضمام الطالب';
$string['event_allocation_completed'] = 'نجح الطالب في المستوى';
$string['event_allocation_created'] = 'تم تسجيل الطالب في المستوى';
$string['event_allocation_deleted'] = 'تم إلغاء انضمام الطالب من المستوى';
$string['event_allocation_restored'] = 'سجل انضمام الطالب تم استعادته';
$string['event_allocation_updated'] = 'سجل انضمام الطالب تم تحديثه';
$string['event_catalogue_program_viewed'] = 'تمت رؤية كتالوج المستوى';
$string['event_program_archived'] = 'تم أرشفة امستوى';
$string['event_program_created'] = 'تم إنشاء امستوى';
$string['event_program_deleted'] = 'تم حذف امستوى';
$string['event_program_restored'] = 'تم استعادة امستوى';
$string['event_program_updated'] = 'تم تحديث امستوى';
$string['event_program_viewed'] = 'تم استعراض امستوى';
$string['evidence'] = 'وثائق نجاح أخرى';
$string['evidence_details'] = 'التفاصيل';
$string['evidence_detailsdefault'] = 'التفاصيل الافتراضية';
$string['evidencedate'] = 'تاريخ وثائق النجاح';
$string['evidenceupdate'] = 'تحديث وثائق النجاح الأخرى';
$string['evidenceupload'] = 'رفع وثائق النجاح';
$string['evidenceupload_csvfile'] = 'CSV file';
$string['evidenceupload_errors'] = '{$a} معلومات و أسطر غير صالحة تم رصدها';
$string['evidenceupload_skipped'] = '{$a} معلومات و أسطر تم تجاوزها';
$string['evidenceupload_updated'] = 'وثائق النجاح تم تحديثا ل {$a} طالب';
$string['export'] = 'تصدير المستويات';
$string['exportfile_info'] = 'المعلومات';
$string['exportfile_programs'] = 'المستويات';
$string['exportformat'] = 'صيغة الملف';
$string['exportformat_csv'] = 'CSV';
$string['exportformat_json'] = 'JSON';
$string['fixeddate'] = 'في تاريخ ثابت';
$string['importallocationend'] = 'الانضمام ينتهي ({$a})';
$string['importallocationstart'] = 'الانضمام يبدأ ({$a})';
$string['importprogramallocation'] = 'استورد سجلات الانضمام';
$string['importprogramallocationconfirmation'] = 'أنت تستورد سجلا الانضمام من المستوى __{$a->fullname} / {$a->idnumber} / {$a->category}__.

رجاءً قم باختيار الإعدادات التي تريد أن تستوردها.';
$string['importprogramcontent'] = 'استيراد محتوى السمتوى';
$string['importprogramcontentconfirmation'] = 'أنت تستورد محتوى من المستوى __{$a->fullname} / {$a->idnumber} / {$a->category}__.';
$string['importprogramdue'] = 'تاريخ استحقاق المستوى ({$a})';
$string['importprogramend'] = 'نهاية المستوى ({$a})';
$string['importprogramstart'] = 'بداية المستوى ({$a})';
$string['importselectprogram'] = 'اختر مستواً';
$string['invalidallocationdates'] = 'تواريخ انضمام غير صالحة';
$string['invalidcompletiondate'] = 'تاريخ نجاح غير صالح';
$string['item'] = 'عنصر';
$string['itemcompletion'] = 'النجاح في عنصر من عناصر المستوى';
$string['itempoints'] = 'النقاط';
$string['itemrecalculate'] = 'إعادة حساب النجاح في المادة';
$string['management'] = 'إدارة المستوى';
$string['management_allocation_actions'] = 'إجراءات الانضمام';
$string['management_index_actions'] = 'إجراءات البرامج';
$string['management_program_allocation_actions'] = 'إعدادات إجراءات الانضمام';
$string['management_program_general_actions'] = 'Program actions';
$string['management_program_users_actions'] = 'Users actions';
$string['messageprovider:allocation_notification'] = 'إشعار بالانضمام للمستوى';
$string['messageprovider:approval_reject_notification'] = 'تم رفض طلب انضمامك للمستوى';
$string['messageprovider:approval_request_notification'] = 'تم قبولك في المستوى';
$string['messageprovider:completion_notification'] = 'إشعار بالنجاح في مستوى';
$string['messageprovider:deallocation_notification'] = 'إشعار حول الاستبعاد من مستوى';
$string['messageprovider:due_notification'] = 'إشعار التأخير في المستوى';
$string['messageprovider:duesoon_notification'] = 'إشعار اقتراب تاريخ استحقاق المستوى';
$string['messageprovider:endcompleted_notification'] = 'إشعار نهاية المستوى الذي نجحت فيه';
$string['messageprovider:endfailed_notification'] = 'إشعار بنهاية المستوى الذي رسبت به';
$string['messageprovider:endsoon_notification'] = 'إشعار حول اقتراب تاريخ نهاية المستوى';
$string['messageprovider:reset_notification'] = 'إشعار بدء المستوى من جديد';
$string['messageprovider:start_notification'] = 'إشعار بداية المستوى';
$string['moveafter'] = 'نقل "{$a->item}" بعد "{$a->target}"';
$string['movebefore'] = 'نقل "{$a->item}" ثبل "{$a->target}"';
$string['moveinto'] = 'نقل "{$a->item}" إلى "{$a->target}"';
$string['moveitem'] = 'نقل مادة';
$string['moveitemcancel'] = 'ألغِ عملية النقل';
$string['muprog:addcourse'] = 'إضافة المادة للمستويات';
$string['muprog:addtocertifications'] = 'إضافة المستوى للشهادات';
$string['muprog:admin'] = 'إدارة متقدمة للمستوى';
$string['muprog:allocate'] = 'ضمّ المستخديمن إلى المستوى';
$string['muprog:clone'] = 'نسخ محتوى و إعدادات المستوى';
$string['muprog:configurecustomfields'] = 'تهيئة إعدادات الحقول المخصص للمستوى';
$string['muprog:deallocate'] = 'فصل اللاب من المستوى';
$string['muprog:delete'] = 'حذف المستويات';
$string['muprog:edit'] = 'إضافة و تعديل المستويات';
$string['muprog:export'] = 'تصدير محتوى المستويات';
$string['muprog:manageallocation'] = 'إدارة سجلات الانضمام للطلاب';
$string['muprog:manageevidence'] = 'إدارة وثائق الإكمال الأخرى';
$string['muprog:reset'] = 'إعادة ضبط التقدم في المستوى';
$string['muprog:upload'] = 'رفع محتو للمستويات';
$string['muprog:view'] = 'عرض لوحة إدارة المستويات';
$string['muprog:viewcatalogue'] = 'الوصول لكتالوج المستوى';
$string['myprograms'] = 'مستوياتي';
$string['notification_allocation'] = 'تم انضمام المستخدم';
$string['notification_allocation_body'] = 'مرحباً {$a->user_fullname},

لقد انضممت للمستوى "{$a->program_fullname}", تاريخ البدء هو {$a->program_startdate}.
';
$string['notification_allocation_description'] = 'الإشعارات تُرسل للطلاب عندما ينضمون لمستوى جديد.';
$string['notification_allocation_subject'] = 'إضعار الانضمام لمستوى جديد';
$string['notification_completion'] = 'تم إكمال المستوى';
$string['notification_completion_body'] = 'مرحباً {$a->user_fullname},

لقد أكملت مستوى "{$a->program_fullname}".
';
$string['notification_completion_description'] = 'الإشعارات تُرسل للطلاب عندما ينتهون من مستوياتهم';
$string['notification_completion_subject'] = 'تم إكمال المستوى';
$string['notification_deallocation'] = 'تم استبعاد الطالب';
$string['notification_deallocation_body'] = 'مرحباً {$a->user_fullname},

لقد تم استبعادك من المستوى "{$a->program_fullname}".
';
$string['notification_deallocation_description'] = 'الإشعارات تُرسل للطلاب حينما يتم استبعادهم.';
$string['notification_deallocation_subject'] = 'إشعار بشأن الاستبعاد من المستوى';
$string['notification_due'] = 'Program overdue';
$string['notification_due_body'] = 'مرحباً {$a->user_fullname},

إكمال المستوى "{$a->program_fullname}" كان متوقعاً  قبل {$a->program_duedate}.
';
$string['notification_due_description'] = 'يتم إرسال إشعار للمستخدمين عندما يتأخر إكمال برنامجهم.';
$string['notification_due_subject'] = 'كان من المتوقع إكمال البرنامج';
$string['notification_duesoon'] = 'اقترب تاريخ استحقاق المستوى';
$string['notification_duesoon_body'] = 'مرحباً {$a->user_fullname},

إكمال المستوى "{$a->program_fullname}" مُتَوقع في التاريخ {$a->program_duedate}.
';
$string['notification_duesoon_description'] = 'يتم إرسال الإشعارات إلى المستخدمين قبل تاريخ إكمال المستوى ما لم يكن الطالب قد نحج في المستوى بالفعل.';
$string['notification_duesoon_subject'] = 'ومن المتوقع الانتهاء من المستوى قريبًا';
$string['notification_endcompleted'] = 'المستوى الذي نجحت فيه انتهى';
$string['notification_endcompleted_body'] = 'مرحباً {$a->user_fullname},

المستوى "{$a->program_fullname}" انتهى, وقد أنهيته بنجاح مسبقاً.
';
$string['notification_endcompleted_description'] = 'يتم إرسال الإشعارات للطلاب عند ينتهي المستوى الذي نجحوا فيه بالفعل.';
$string['notification_endcompleted_subject'] = 'انتهى المستوى المُنجز';
$string['notification_endfailed'] = 'انتهى المستوى الذي رسبت فيه';
$string['notification_endfailed_body'] = 'مرحباً {$a->user_fullname},

المستوى "{$a->program_fullname}" انتهى و لم تتمكن من إكماله.
';
$string['notification_endfailed_description'] = 'يتم إرسال إشعار للمستخدمين عند انتهاء المستوى الذي فشلوا في إكماله.';
$string['notification_endfailed_subject'] = 'انتهى المستوى الذي رسبت فيه';
$string['notification_endsoon'] = 'المستوى ينتهي قريباً';
$string['notification_endsoon_body'] = 'مرحباً {$a->user_fullname},

المستوى "{$a->program_fullname}" سينتهي {$a->program_enddate}.
';
$string['notification_endsoon_description'] = 'الإشعارات تٌرسل للطلاب قبل نهاية المستوى مالم يكونوا قد اكملوه بالفعل.';
$string['notification_endsoon_subject'] = 'المستوى ينتهي قريباً';
$string['notification_reset'] = 'تم حذف تقدم الطالب';
$string['notification_reset_body'] = 'مرحباً {$a->user_fullname},

"{$a->program_fullname}" تم إعادتك إلى بداية المستوى.
';
$string['notification_reset_description'] = 'الإشعارات تُرسل للطلاب حينما يتم حذف تقدمهم.';
$string['notification_reset_subject'] = 'إشعار حذف التقدم في المستوى';
$string['notification_start'] = 'بدأ المستوى';
$string['notification_start_body'] = 'مرحباً {$a->user_fullname},

المستوى "{$a->program_fullname}" قد بدأ.
';
$string['notification_start_description'] = 'الإشعارات تُرسل للمستخدمين عندما يبدأ المستوى.';
$string['notification_start_subject'] = 'المستوى بدأ';
$string['notificationdates'] = 'تواريخ الإشعارات';
$string['notset'] = 'غير مُعين';
$string['plugindisabled'] = 'اّلية الانضمام للمستوى غير مفعّلة, المستويات لن تكون فعالة.

[Enable plugin now]({$a->url})';
$string['pluginname'] = 'المستويات';
$string['pluginname_desc'] = 'المستويات مصصمة لتحتوي على مراحل.';
$string['privacy:metadata:field:allocationid'] = 'معرّف سجل الانضمام للمستوى';
$string['privacy:metadata:field:archived'] = 'هل السجل مؤرشف؟';
$string['privacy:metadata:field:createdby'] = 'تم إنشاء الوثيقة من قبل';
$string['privacy:metadata:field:datajson'] = 'معلومات عن الطلب';
$string['privacy:metadata:field:evidencejson'] = 'معلومات عن وثيقة الإكمال';
$string['privacy:metadata:field:explanation'] = 'التوضيح';
$string['privacy:metadata:field:issueid'] = 'معرّف سجل منح الشهدة';
$string['privacy:metadata:field:itemid'] = 'معرّف العنصر';
$string['privacy:metadata:field:programid'] = 'معرّف المستوى';
$string['privacy:metadata:field:quantity'] = 'العدد';
$string['privacy:metadata:field:reason'] = 'السبب';
$string['privacy:metadata:field:rejectedby'] = 'تم رفض الطلب من قبل';
$string['privacy:metadata:field:sourcedatajson'] = 'معلومات عن نوع سجل الانضمام';
$string['privacy:metadata:field:sourceid'] = 'نوع الانضمام';
$string['privacy:metadata:field:timeallocated'] = 'تاريخ الانضمام للمستوى';
$string['privacy:metadata:field:timecompleted'] = 'تاريخ الإكمال';
$string['privacy:metadata:field:timecreated'] = 'تاريخ الإنشاء';
$string['privacy:metadata:field:timedue'] = 'تاريخ الاستحقاق';
$string['privacy:metadata:field:timeend'] = 'تاريخ النهاية';
$string['privacy:metadata:field:timerejected'] = 'تاريخ الرفض';
$string['privacy:metadata:field:timerequested'] = 'تاريخ الطلب';
$string['privacy:metadata:field:timestart'] = 'تاريخ البداية';
$string['privacy:metadata:field:userid'] = 'المعرّف الخاص بالطالب';
$string['privacy:metadata:table:tool_muprog_allocation'] = 'معلومات عن سجلات الانضمام للمستوى';
$string['privacy:metadata:table:tool_muprog_cert_issue'] = 'Program allocation certificate issues';
$string['privacy:metadata:table:tool_muprog_completion'] = 'سجلات الطلاب المنضمة المُكملة للمستوى';
$string['privacy:metadata:table:tool_muprog_evidence'] = 'معلومات عن وثائق الإكمال الأخرى';
$string['privacy:metadata:table:tool_muprog_request'] = 'معلومات عن طلب ';
$string['program'] = 'المستوى';
$string['program_actions'] = 'وظائف المستوى';
$string['program_allocations_edit'] = 'تحديث سجلات الانضمام';
$string['program_archive'] = 'أرضف المستوى';
$string['program_archive_info'] = 'أرشفة المستوى:

* تعلق التسجيل في المستوى,
* حذف أحداث المستوى من التقويم,
* منع التعديل على المستوى,
* إخفاء المستوى من الطلاب غير المنتسبين.

الأرضفة خطوة ضرورية قبل حذفه.';
$string['program_create'] = 'إضافة مستوى';
$string['program_delete'] = 'حذف مستوى';
$string['program_delete_info'] = 'يتم حذف جميع بيانات المستوى ويتم إلغاء تسجيل المستخدمين في دورات المستوى أثناء حذفه.';
$string['program_restore'] = 'استعادة المستوى';
$string['program_restore_info'] = 'استعادة المستوى يعيد التغييرات التي تم إجراؤها أثناء أرشفة المستوى.

ومع ذلك، يوصى بالتحقق من جميع إعدادات المستوى و الطلاب المنضمين بعد ذلك.';
$string['program_update'] = 'تحديث المستوى';
$string['programallocations'] = 'عدد المنضمين للمستوى';
$string['programautofix'] = 'تجديد تلقائي للمستوى';
$string['programcompletion'] = 'تاريخ الانتهاء من المستوى';
$string['programcompletionoverride'] = 'تجاوز إكمال المستوى';
$string['programdue'] = 'استحقاق المستوى';
$string['programdue_date'] = 'تاريخ الاستحقاق';
$string['programdue_delay'] = 'الاستحقاق بعد البدء';
$string['programdue_help'] = 'تاريخ الاستحقاق في المستوى يُشير إلى التاريخ الذي يُتوقع فيه من الطلاب الانتهاء من المستوى .';
$string['programend'] = 'نهاية المستوى';
$string['programend_date'] = 'تاريخ انتهاء المستوى';
$string['programend_delay'] = 'الإنهاء بعد البداية';
$string['programend_help'] = 'لا يمكن للطلاب الدخول إلى مواد المستوى بعد انتهاء المستوى.';
$string['programidnumber'] = 'الرمز التعريفي للمستوى';
$string['programimage'] = 'شارة المستوى';
$string['programname'] = 'اسم المستوى';
$string['programs'] = 'المستويات';
$string['programstart'] = 'بداية امستوى';
$string['programstart_allocation'] = 'البدء مباشرة بعد الانضمام';
$string['programstart_date'] = 'تاريخ بداية المستوى';
$string['programstart_delay'] = 'المهلة تبدأ بعد الانضمام';
$string['programstart_help'] = 'لا يمكن للطلاب البدء بمواد المستوى قبل أن يبدأ رسمياً.';
$string['programstatus'] = 'حالة المستوى';
$string['programstatus_any'] = 'حالة عشوائية للمستوى';
$string['programstatus_archived'] = 'أُرشفت';
$string['programstatus_archivedcompleted'] = 'تمت الأرضفة بنجاح';
$string['programstatus_completed'] = 'مكتمل';
$string['programstatus_failed'] = 'فشل';
$string['programstatus_future'] = 'غير مفتوح لحد الاّن';
$string['programstatus_open'] = 'مفتوح';
$string['programstatus_overdue'] = 'متأخر';
$string['programurl'] = 'رابط المستوى';
$string['publicaccess'] = 'عام';
$string['publicaccess_help'] = 'المستويات العامة متاحة للجميع.

حالة ظهور المستوى لا تُؤثر على الطلاب المسجلين في المستوى بالأصل.';
$string['purchaseaccess'] = 'إمكانية الشراء';
$string['resettype'] = 'نوع إعادة الضبط';
$string['resettype_deallocate'] = 'إلغاء التسجيل في المستوى فقط';
$string['resettype_full'] = 'حذف المادة بالكامل';
$string['resettype_none'] = 'لا شيء';
$string['resettype_standard'] = 'حذف المادة بشكل قياسي';
$string['scheduling'] = 'الجدولة';
$string['sequencetype'] = 'نمط النجاح بالمرحلة';
$string['sequencetype_allinanyorder'] = 'إنهاء الجميع بدون ترتيب';
$string['sequencetype_allinorder'] = 'إنهاء الجميع بالترتيب';
$string['sequencetype_atleast'] = 'على الأقل {$a->min}';
$string['sequencetype_minpoints'] = 'على الأقل {$a->minpoints} نقطة';
$string['set'] = 'المرحلة';
$string['settings'] = 'إعدادات المستوى';
$string['source'] = 'المصدر';
$string['source_approval'] = 'طلبات التسجيل المُوافق عليها';
$string['source_approval_allownew'] = 'السماح بقبول الطلبات';
$string['source_approval_allownew_desc'] = 'Allow adding new _requests with approval_ sources to programs';
$string['source_approval_allowrequest'] = 'السماح بالطلبات الجديدة';
$string['source_approval_confirm'] = 'قم بالتأكيد على طلبك للتسجيل في المستوى.';
$string['source_approval_daterejected'] = 'تاريخ رفض التسجيل';
$string['source_approval_daterequested'] = 'تاريخ طلب التسجيل';
$string['source_approval_makerequest'] = 'طلب التسجيل';
$string['source_approval_notification_approval_reject_body'] = 'مرحباً {$a->user_fullname},

طلبك التسجيل في مستوى "{$a->program_fullname}" تم رفضه.

{$a->reason}
';
$string['source_approval_notification_approval_reject_subject'] = 'إشعار رفض طلب التسجيل على المستوى';
$string['source_approval_notification_approval_request_body'] = '
المستخدم {$a->user_fullname} قدّم طلب للتسجيل على "{$a->program_fullname}".
';
$string['source_approval_notification_approval_request_subject'] = 'إشعار طلب التسجيل على المستوى';
$string['source_approval_rejectionreason'] = 'سبب الرفض';
$string['source_approval_request'] = 'الطلب';
$string['source_approval_requestallowed'] = 'طلبات التسجيل مسموحة';
$string['source_approval_requestapprove'] = 'الموافقة على الطلب';
$string['source_approval_requestdelete'] = 'حذف الطلب';
$string['source_approval_requestnotallowed'] = 'طلبات التسجيل غير مسموحة';
$string['source_approval_requestpending'] = 'طلب التسجيل قيد المراجعة';
$string['source_approval_requestreject'] = 'رفض الطلب';
$string['source_approval_requestrejected'] = 'تم رفض طلب التسجيل';
$string['source_approval_requests'] = 'طلبات التسجيل';
$string['source_cohort'] = 'التسجيل التلقائي للدفعة';
$string['source_cohort_allownew'] = 'السماح للدفعات بالتسجيل';
$string['source_cohort_allownew_desc'] = 'Allow adding new _cohort auto allocation_ sources to programs';
$string['source_cohort_cohortstoallocate'] = 'تسجيل الذفعات';
$string['source_manual'] = 'التسجيل الذاتي';
$string['source_manual_allocateusers'] = 'تسجيل الطلاب';
$string['source_manual_csvfile'] = 'CSV ملف';
$string['source_manual_hasheaders'] = 'أول سطر هو الترويسة';
$string['source_manual_potusers'] = 'المرشحون للتسجيل';
$string['source_manual_potusersmatching'] = 'مطابقة المرشحين للانضمام';
$string['source_manual_result_assigned'] = '{$a} تم تسجيل المستخدمين في المستوى.';
$string['source_manual_result_errors'] = '{$a} حدثت مشاكل أثناء عند التسجيل في المستوى.';
$string['source_manual_result_skipped'] = '{$a} المستخدمون مسجلون بالفعل في المستوى';
$string['source_manual_timeduecolumn'] = 'وقت الاستحقاق';
$string['source_manual_timeendcolumn'] = 'وقت الانتهاء';
$string['source_manual_timestartcolumn'] = 'وقت البداية';
$string['source_manual_uploadusers'] = 'رفع معلومات التسجيل الذاتي للطلاب';
$string['source_manual_usercolumn'] = 'عمود تعريف المستخدم';
$string['source_manual_usermapping'] = 'تعيين المستخدم عبر';
$string['source_manual_userupload_allocated'] = 'Allocated to \'{$a}\'';
$string['source_manual_userupload_alreadyallocated'] = 'مُسجل في هذا المستوى بالفعل \'{$a}\'';
$string['source_manual_userupload_invalidprogram'] = 'لا يمكن تسجيله في هذا المستوى \'{$a}\'';
$string['source_mucertify'] = 'الشهادات';
$string['source_mucertify_allownew'] = 'السماح بإسناد شهادات';
$string['source_mucertify_allownew_desc'] = 'Allow adding new _certification_ sources to programs';
$string['source_selfallocation'] = 'التسجيل الذاتي';
$string['source_selfallocation_allocate'] = 'التسجيل الذاي في المستوى';
$string['source_selfallocation_allownew'] = 'السماح بالتسجيل الذاتي';
$string['source_selfallocation_allownew_desc'] = 'Allow adding new _self allocation_ sources to programs';
$string['source_selfallocation_allowsignup'] = 'السماح بالتسجيل الذاتي للطلاب الجدد';
$string['source_selfallocation_confirm'] = 'قم بالتأكيد رجاءً على التسجيل في المستوى.';
$string['source_selfallocation_enable'] = 'تمكين التسجيل الذاتي للطلاب';
$string['source_selfallocation_key'] = 'مفتاح التسجيل';
$string['source_selfallocation_keyrequired'] = 'مفتاح التسجيل مطلوب';
$string['source_selfallocation_maxusers'] = 'الحد الأقصى المتاح من المستخدمين المسجلين ذاتياً';
$string['source_selfallocation_maxusers_status'] = 'المستخدمون {$a->count}/{$a->max}';
$string['source_selfallocation_maxusersreached'] = 'وصلنا للحد الأعلى المتاح من المستخدمين المسجلين ذاتياّ';
$string['source_selfallocation_signupallowed'] = 'التسجيل في المستوى متاح';
$string['source_selfallocation_signupnotallowed'] = 'التسجيل في المستوى غير متاح';
$string['taballocation'] = 'إعدادات الانضمام';
$string['tabcontent'] = 'محتوى';
$string['tabgeneral'] = 'عام';
$string['tabusers'] = 'المستخدمون';
$string['tabvisibility'] = 'رؤية الكتالوج';
$string['tagarea_program'] = 'المستويات';
$string['taskcertificate'] = 'Programs certificate issuing cron';
$string['taskcron'] = 'المهمة الخلفية المستويات';
$string['training'] = 'الترديب';
$string['trainingcompletion'] = 'يتطلب تدريب: {$a}';
$string['trainingprogress'] = 'التقدم في التدريب: {$a->current}/{$a->total}';
$string['unlinkeditems'] = 'عناصر غير مرتبطة';
$string['updatecourse'] = 'تحديث المادة';
$string['updatescheduling'] = 'تحديث الجدولة';
$string['updateset'] = 'تحديث المرحلة';
$string['updatesource'] = 'Update {$a}';
$string['updatetraining'] = 'تحديث التدريب';
$string['upload'] = 'رفع محتوى المستويات';
$string['upload_files'] = 'الملفات التي ستُرفع';
$string['upload_files_error'] = 'يُتوقع إضافة ملف json أو ملف zip';
$string['upload_invalidcount'] = 'بيانات غير صالحة للرفع على الموقع';
$string['upload_preview'] = 'عرض البيانات';
$string['upload_status'] = 'الحالة';
$string['upload_status_invalid'] = 'غير صالح';
$string['upload_targetcontext'] = 'قُم بإضافة مستويات إلى السياق';
$string['upload_uploadcount'] = 'عدد المستويات للرفع';
$string['upload_usecategory'] = 'استخدم عمود الفئة للسياقات';
$string['userupload_completion_error'] = 'لا يمكن تحديث معلومات إكمال المسار';
$string['userupload_completion_updated'] = 'تم تحديث معلومات إكمال المسار';
