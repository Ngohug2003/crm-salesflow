<?php

declare(strict_types=1);

use App\Livewire\Customers\CustomerAttachmentManager;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use App\Services\CustomerAttachmentService;
use App\Services\CustomerTimelineService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(RolePermissionSeeder::class);
    Storage::fake('local');
});

function p406Actor(string $role): User
{
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $user->assignRole($role);

    return $user;
}

it('allows uploading valid attachments for Company and Contact', function (): void {
    $admin = p406Actor('admin');
    $company = Company::factory()->create();
    $contact = Contact::factory()->create();

    $pdfFile = UploadedFile::fake()->create('bao_gia_mau.pdf', 100, 'application/pdf');
    $imgFile = UploadedFile::fake()->image('avatar_khach_hang.png');

    /** @var CustomerAttachmentService $service */
    $service = app(CustomerAttachmentService::class);

    $attachmentComp = $service->upload($admin, $company, $pdfFile);
    expect($attachmentComp)->not->toBeNull()
        ->and($attachmentComp->file_name)->toBe('bao_gia_mau.pdf')
        ->and($attachmentComp->attachable_type)->toBe($company->getMorphClass())
        ->and($attachmentComp->attachable_id)->toBe($company->id);

    $attachmentCont = $service->upload($admin, $contact, $imgFile);
    expect($attachmentCont)->not->toBeNull()
        ->and($attachmentCont->file_name)->toBe('avatar_khach_hang.png')
        ->and($attachmentCont->attachable_type)->toBe($contact->getMorphClass())
        ->and($attachmentCont->attachable_id)->toBe($contact->id);

    // Verify storage file exists
    Storage::disk('local')->assertExists($attachmentComp->file_path);
    Storage::disk('local')->assertExists($attachmentCont->file_path);
});

it('blocks uploading dangerous file extensions', function (): void {
    $admin = p406Actor('admin');
    $company = Company::factory()->create();
    $phpFile = UploadedFile::fake()->create('malicious.php', 50, 'text/x-php');

    /** @var CustomerAttachmentService $service */
    $service = app(CustomerAttachmentService::class);

    $this->expectException(InvalidArgumentException::class);
    $service->upload($admin, $company, $phpFile);
});

it('generates unified timeline feed containing audit logs and attachments', function (): void {
    $admin = p406Actor('admin');
    $company = Company::factory()->create(['name' => 'Công ty Timeline Test']);
    $pdfFile = UploadedFile::fake()->create('hop_dong_mau.pdf', 200, 'application/pdf');

    /** @var CustomerAttachmentService $attService */
    $attService = app(CustomerAttachmentService::class);
    $attService->upload($admin, $company, $pdfFile);

    /** @var CustomerTimelineService $timeService */
    $timeService = app(CustomerTimelineService::class);
    $items = $timeService->timelineForModel($admin, $company);

    expect($items)->not->toBeEmpty();
    $attachmentEvents = array_filter($items, fn ($i) => $i->event === 'attachment_added');
    expect($attachmentEvents)->not->toBeEmpty();
});

it('allows deleting an attachment with audit log', function (): void {
    $admin = p406Actor('admin');
    $company = Company::factory()->create();
    $pdfFile = UploadedFile::fake()->create('to_trinh.pdf', 150, 'application/pdf');

    /** @var CustomerAttachmentService $service */
    $service = app(CustomerAttachmentService::class);
    $attachment = $service->upload($admin, $company, $pdfFile);

    Livewire::actingAs($admin)
        ->test(CustomerAttachmentManager::class, ['modelType' => Company::class, 'modelId' => $company->id])
        ->call('deleteAttachment', $attachment->id);

    expect(Attachment::query()->find($attachment->id))->toBeNull();
    expect(Attachment::withTrashed()->find($attachment->id))->not->toBeNull();
});
