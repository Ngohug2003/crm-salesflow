<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class CustomerMergeService
{
    /**
     * Scan and group potential duplicate Companies.
     *
     * @return array<int, array{reason: string, master: Company, duplicates: EloquentCollection<int, Company>}>
     */
    public function scanCompanyDuplicates(User $actor): array
    {
        /** @var EloquentCollection<int, Company> $allCompanies */
        $allCompanies = Company::query()->orderBy('id')->get();
        $duplicateGroups = [];

        $groupedByName = $allCompanies->groupBy(static fn (Company $c): string => mb_strtolower(trim($c->name)));

        foreach ($groupedByName as $name => $items) {
            if ($name !== '' && $items->count() > 1) {
                /** @var Company $master */
                $master = $items->first();
                /** @var EloquentCollection<int, Company> $duplicates */
                $duplicates = $items->slice(1)->values();

                if (Gate::forUser($actor)->allows('view', $master)) {
                    $duplicateGroups[] = [
                        'reason' => "Trùng tên doanh nghiệp: '{$master->name}'",
                        'master' => $master,
                        'duplicates' => $duplicates,
                    ];
                }
            }
        }

        return $duplicateGroups;
    }

    /**
     * Scan and group potential duplicate Contacts.
     *
     * @return array<int, array{reason: string, master: Contact, duplicates: EloquentCollection<int, Contact>}>
     */
    public function scanContactDuplicates(User $actor): array
    {
        /** @var EloquentCollection<int, Contact> $allContacts */
        $allContacts = Contact::query()->orderBy('id')->get();
        $duplicateGroups = [];

        $groupedByEmail = $allContacts->groupBy(static fn (Contact $c): string => mb_strtolower(trim((string) $c->email)));

        foreach ($groupedByEmail as $email => $items) {
            if ($email !== '' && $items->count() > 1) {
                /** @var Contact $master */
                $master = $items->first();
                /** @var EloquentCollection<int, Contact> $duplicates */
                $duplicates = $items->slice(1)->values();

                if (Gate::forUser($actor)->allows('view', $master)) {
                    $duplicateGroups[] = [
                        'reason' => "Trùng địa chỉ email: '{$master->email}'",
                        'master' => $master,
                        'duplicates' => $duplicates,
                    ];
                }
            }
        }

        return $duplicateGroups;
    }

    /**
     * Merge source Company into master Company safely.
     *
     * @param  array<string, mixed>  $fieldOverrides
     */
    public function mergeCompanies(User $actor, Company $master, Company $source, array $fieldOverrides = []): Company
    {
        Gate::forUser($actor)->authorize('update', $master);
        Gate::forUser($actor)->authorize('update', $source);

        return DB::transaction(function () use ($actor, $master, $source, $fieldOverrides): Company {
            // 1. Update master field overrides if provided
            if ($fieldOverrides !== []) {
                $master->fill($fieldOverrides);
                $master->updated_by = $actor->getKey();
                $master->save();
            }

            // 2. Transfer child Contacts
            Contact::query()
                ->where('company_id', $source->id)
                ->update(['company_id' => $master->id]);

            // 3. Transfer child Opportunities
            Opportunity::query()
                ->where('company_id', $source->id)
                ->update(['company_id' => $master->id]);

            // 4. Transfer Activities
            Activity::query()
                ->where('subject_type', Company::class)
                ->where('subject_id', $source->id)
                ->update(['subject_id' => $master->id]);

            // 5. Transfer Tasks
            Task::query()
                ->where('subject_type', Company::class)
                ->where('subject_id', $source->id)
                ->update(['subject_id' => $master->id]);

            // 6. Transfer Attachments
            Attachment::query()
                ->where('attachable_type', Company::class)
                ->where('attachable_id', $source->id)
                ->update(['attachable_id' => $master->id]);

            // 7. Audit log & soft-delete source
            activity()
                ->causedBy($actor)
                ->performedOn($master)
                ->event('merged')
                ->log("Hợp nhất Doanh nghiệp #{$source->id} ({$source->name}) vào #{$master->id} ({$master->name})");

            $source->delete();

            return $master;
        });
    }

    /**
     * Merge source Contact into master Contact safely.
     *
     * @param  array<string, mixed>  $fieldOverrides
     */
    public function mergeContacts(User $actor, Contact $master, Contact $source, array $fieldOverrides = []): Contact
    {
        Gate::forUser($actor)->authorize('update', $master);
        Gate::forUser($actor)->authorize('update', $source);

        return DB::transaction(function () use ($actor, $master, $source, $fieldOverrides): Contact {
            // 1. Update master field overrides if provided
            if ($fieldOverrides !== []) {
                $master->fill($fieldOverrides);
                $master->updated_by = $actor->getKey();
                $master->save();
            }

            // 2. Transfer Opportunities
            Opportunity::query()
                ->where('contact_id', $source->id)
                ->update(['contact_id' => $master->id]);

            // 3. Transfer Activities
            Activity::query()
                ->where('subject_type', Contact::class)
                ->where('subject_id', $source->id)
                ->update(['subject_id' => $master->id]);

            // 4. Transfer Tasks
            Task::query()
                ->where('subject_type', Contact::class)
                ->where('subject_id', $source->id)
                ->update(['subject_id' => $master->id]);

            // 5. Transfer Attachments
            Attachment::query()
                ->where('attachable_type', Contact::class)
                ->where('attachable_id', $source->id)
                ->update(['attachable_id' => $master->id]);

            // 6. Audit log & soft-delete source
            activity()
                ->causedBy($actor)
                ->performedOn($master)
                ->event('merged')
                ->log("Hợp nhất Người liên hệ #{$source->id} ({$source->full_name}) vào #{$master->id} ({$master->full_name})");

            $source->delete();

            return $master;
        });
    }
}
