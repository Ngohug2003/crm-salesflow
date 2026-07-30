<?php

declare(strict_types=1);

namespace App\Data;

final readonly class ImportMappingSchema
{
    /** @return array<string, array{label: string, required: bool, keywords: list<string>}> */
    public static function leadFields(): array
    {
        return [
            'last_name' => [
                'label' => 'Họ và tên đệm',
                'required' => true,
                'keywords' => ['ho', 'họ', 'last name', 'lastname', 'surname', 'family name', 'ho va ten dem'],
            ],
            'first_name' => [
                'label' => 'Tên',
                'required' => true,
                'keywords' => ['ten', 'tên', 'first name', 'firstname', 'given name', 'ho va ten', 'họ và tên', 'full name', 'fullname', 'name'],
            ],
            'email' => [
                'label' => 'Email',
                'required' => false,
                'keywords' => ['email', 'e-mail', 'thu dien tu', 'thư điện tử', 'mail'],
            ],
            'phone' => [
                'label' => 'Số điện thoại',
                'required' => false,
                'keywords' => ['phone', 'dien thoai', 'điện thoại', 'so dien thoai', 'số điện thoại', 'mobile', 'cell', 'tel'],
            ],
            'company_name' => [
                'label' => 'Tên công ty',
                'required' => false,
                'keywords' => ['company', 'cong ty', 'công ty', 'to chuc', 'tổ chức', 'organization', 'enterprise'],
            ],
            'title' => [
                'label' => 'Chức danh',
                'required' => false,
                'keywords' => ['title', 'chuc danh', 'chức danh', 'vi tri', 'vị trí', 'job title', 'position', 'chuc vu'],
            ],
            'lead_source_id' => [
                'label' => 'Nguồn Lead',
                'required' => false,
                'keywords' => ['source', 'nguon', 'nguồn', 'lead source', 'nguon lead', 'nguồn lead'],
            ],
            'status' => [
                'label' => 'Trạng thái Lead',
                'required' => false,
                'keywords' => ['status', 'trang thai', 'trạng thái', 'lead status'],
            ],
            'notes' => [
                'label' => 'Ghi chú',
                'required' => false,
                'keywords' => ['notes', 'ghi chu', 'ghi chú', 'mo ta', 'mô tả', 'description', 'remark'],
            ],
        ];
    }
}
