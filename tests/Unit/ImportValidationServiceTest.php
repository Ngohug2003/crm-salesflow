<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Import\ImportValidationService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ImportValidationServiceTest extends TestCase
{
    private ImportValidationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ImportValidationService;
    }

    public function test_it_automaps_vietnamese_and_english_headers(): void
    {
        $headers = ['Họ', 'Tên', 'Email', 'Số điện thoại', 'Công ty', 'Ghi chú'];

        $mapping = $this->service->autoMapHeaders($headers);

        $this->assertSame('Họ', $mapping['last_name']);
        $this->assertSame('Tên', $mapping['first_name']);
        $this->assertSame('Email', $mapping['email']);
        $this->assertSame('Số điện thoại', $mapping['phone']);
        $this->assertSame('Công ty', $mapping['company_name']);
        $this->assertSame('Ghi chú', $mapping['notes']);
    }

    public function test_it_validates_mapping_schema_requires_primary_identifier(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->validateMappingSchema([
            'notes' => 'Ghi chú',
            'title' => 'Chức danh',
        ]);
    }

    public function test_it_passes_valid_mapping_schema(): void
    {
        $this->service->validateMappingSchema([
            'email' => 'Email',
        ]);

        $this->assertTrue(true);
    }
}
