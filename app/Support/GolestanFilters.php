<?php

namespace App\Support;

class GolestanFilters
{
    /** @return array<string, array{id: int, label: string, type: string, group: string}> */
    public static function fields(): array
    {
        return [
            'student_number' => ['id' => 4, 'label' => 'شماره دانشجو', 'type' => 'number', 'group' => 'Identity / مشخصات فردی'],
            'national_id' => ['id' => 6, 'label' => 'کد ملی', 'type' => 'national_id', 'group' => 'Identity / مشخصات فردی'],
            'first_name' => ['id' => 10, 'label' => 'نام', 'type' => 'text', 'group' => 'Identity / مشخصات فردی'],
            'last_name' => ['id' => 8, 'label' => 'نام خانوادگی', 'type' => 'text', 'group' => 'Identity / مشخصات فردی'],
            'passport_number' => ['id' => 7, 'label' => 'شماره گذرنامه', 'type' => 'text', 'group' => 'Identity / مشخصات فردی'],
            'birth_certificate_number' => ['id' => 40, 'label' => 'شماره شناسنامه', 'type' => 'number', 'group' => 'Identity / مشخصات فردی'],
            'birth_date' => ['id' => 42, 'label' => 'تاریخ تولد', 'type' => 'date', 'group' => 'Identity / مشخصات فردی'],
            'gender' => ['id' => 30, 'label' => 'جنسیت (کد)', 'type' => 'number', 'group' => 'Identity / مشخصات فردی'],
            'email' => ['id' => 102, 'label' => 'ایمیل دانشجو', 'type' => 'text', 'group' => 'Identity / مشخصات فردی'],
            'foreign_national_number' => ['id' => 103, 'label' => 'شماره اتباع خارجی', 'type' => 'number', 'group' => 'Identity / مشخصات فردی'],
            'faculty' => ['id' => 18, 'label' => 'دانشکده (کد)', 'type' => 'number', 'group' => 'Academic / مشخصات تحصیلی'],
            'department' => ['id' => 20, 'label' => 'گروه آموزشی (کد)', 'type' => 'number', 'group' => 'Academic / مشخصات تحصیلی'],
            'major' => ['id' => 22, 'label' => 'رشته تحصیلی (کد)', 'type' => 'number', 'group' => 'Academic / مشخصات تحصیلی'],
            'degree_level' => ['id' => 24, 'label' => 'مقطع تحصیلی (کد)', 'type' => 'number', 'group' => 'Academic / مشخصات تحصیلی'],
            'study_period' => ['id' => 26, 'label' => 'دوره (کد)', 'type' => 'number', 'group' => 'Academic / مشخصات تحصیلی'],
            'entry_term' => ['id' => 27, 'label' => 'ترم ورود', 'type' => 'number', 'group' => 'Academic / مشخصات تحصیلی'],
            'last_term' => ['id' => 37, 'label' => 'آخرین ترم', 'type' => 'number', 'group' => 'Academic / مشخصات تحصیلی'],
            'student_status' => ['id' => 38, 'label' => 'وضعیت دانشجو (کد)', 'type' => 'number', 'group' => 'Academic / مشخصات تحصیلی'],
            'education_program' => ['id' => 29, 'label' => 'نوع برنامه آموزشی (کد)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
            'higher_education_entry_type' => ['id' => 32, 'label' => 'نوع ورود به آموزش عالی (کد)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
            'education_facility_type' => ['id' => 34, 'label' => 'نوع تسهیلات آموزشی (کد)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
            'university_entry_type' => ['id' => 36, 'label' => 'نوع ورود به دانشگاه (کد)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
            'advisor' => ['id' => 48, 'label' => 'استاد راهنمای تحصیلی (کد)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
            'second_advisor' => ['id' => 101, 'label' => 'استاد راهنمای دوم (کد)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
            'report_source' => ['id' => 1, 'label' => 'منبع گزارش (کد)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
            'student_origin' => ['id' => 2, 'label' => 'مبدأ دانشجو (کد)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
            'unit' => ['id' => 3, 'label' => 'واحد یا مرکز (کد)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
            'academic_term' => ['id' => 10, 'label' => 'ترم تحصیلی (فیلتر اختصاصی)', 'type' => 'number', 'group' => 'Advanced / سایر فیلترها'],
        ];
    }

    /** @param array<string, mixed> $filters
     * @return array{0: string, 1: string}
     */
    public static function range(array $filters, string $name): array
    {
        $from = (string) ($filters[$name] ?? '');
        $to = (string) ($filters[$name.'_to'] ?? '');

        return [$from, $to !== '' ? $to : $from];
    }

    public static function compareNumbers(string $left, string $right): int
    {
        $left = ltrim($left, '0') ?: '0';
        $right = ltrim($right, '0') ?: '0';

        return strlen($left) <=> strlen($right) ?: strcmp($left, $right);
    }
}
