<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use App\Repositories\Contracts\CompanyRepository;
use App\Repositories\Contracts\ContactRepository;
use App\Repositories\Contracts\LeadRepository;
use App\Repositories\Contracts\OpportunityRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final readonly class TaskSubjectService
{
    public function __construct(
        private CompanyRepository $companies,
        private ContactRepository $contacts,
        private LeadRepository $leads,
        private OpportunityRepository $opportunities,
    ) {}

    public function validateVisibleSubject(User $actor, ?string $subjectType, ?int $subjectId): ?Model
    {
        if ($subjectType === null && $subjectId === null) {
            return null;
        }

        if ($subjectType === null || $subjectId === null || $subjectId < 1) {
            throw ValidationException::withMessages([
                'subjectId' => 'Đối tượng liên kết của công việc không hợp lệ.',
            ]);
        }

        $subject = match ($subjectType) {
            Company::class => $this->companies->findVisibleOrFail($actor, $subjectId),
            Contact::class => $this->contacts->findVisibleOrFail($actor, $subjectId),
            Lead::class => $this->leads->findVisibleOrFail($actor, $subjectId),
            Opportunity::class => $this->opportunities->findVisibleOrFail($actor, $subjectId),
            default => throw ValidationException::withMessages([
                'subjectType' => 'Loại đối tượng liên kết không được hỗ trợ.',
            ]),
        };

        Gate::forUser($actor)->authorize('view', $subject);

        return $subject;
    }
}
