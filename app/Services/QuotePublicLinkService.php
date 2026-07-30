<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\QuoteStatus;
use App\Models\Activity;
use App\Models\Opportunity;
use App\Models\Quote;
use App\Models\QuoteCustomerResponse;
use App\Models\QuotePublicEvent;
use App\Models\QuotePublicLink;
use App\Models\User;
use App\Notifications\QuoteWorkflowNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class QuotePublicLinkService
{
    /**
     * Generate a new secure public link for a Quote.
     *
     * @return array{link: QuotePublicLink, plain_token: string}
     */
    public function generateLink(Quote $quote, User $creator, ?Carbon $expiresAt = null, ?string $accessCode = null): array
    {
        return DB::transaction(function () use ($quote, $creator, $expiresAt, $accessCode): array {
            Gate::forUser($creator)->authorize('issue', $quote);
            if (! in_array($quote->status, [QuoteStatus::Issued, QuoteStatus::Sent], true)
                || $quote->issued_snapshot === null) {
                throw ValidationException::withMessages(['quote' => 'Chỉ Báo giá đã phát hành và có snapshot mới được tạo link công khai.']);
            }

            // Revoke active existing links for previous versions if requested
            QuotePublicLink::query()
                ->where('quote_id', $quote->id)
                ->whereNull('revoked_at')
                ->update(['revoked_at' => now()]);

            $plainToken = Str::random(64);
            $tokenHash = hash('sha256', $plainToken);

            $link = QuotePublicLink::query()->create([
                'quote_id' => $quote->id,
                'token_hash' => $tokenHash,
                'version_issued' => $quote->version,
                'public_snapshot' => $quote->issued_snapshot,
                'expires_at' => $expiresAt ?? now()->addDays(30),
                'revoked_at' => null,
                'access_code_hash' => $accessCode ? Hash::make($accessCode) : null,
                'view_count' => 0,
                'created_by' => $creator->id,
            ]);

            return [
                'link' => $link,
                'plain_token' => $plainToken,
            ];
        });
    }

    /**
     * Revoke a public link.
     */
    public function revokeLink(QuotePublicLink $link): void
    {
        $link->update(['revoked_at' => now()]);
    }

    /**
     * Resolve valid QuotePublicLink by plain token string.
     */
    public function resolveLinkFromToken(string $plainToken): ?QuotePublicLink
    {
        $tokenHash = hash('sha256', $plainToken);

        /** @var QuotePublicLink|null $link */
        $link = QuotePublicLink::query()
            ->with(['quote.opportunity', 'finalResponse'])
            ->where('token_hash', $tokenHash)
            ->first();

        if ($link === null
            || $link->isValid() === false
            || $link->public_snapshot === null
            || (int) data_get($link->public_snapshot, 'quote.version', 0) !== $link->version_issued) {
            return null;
        }

        return $link;
    }

    public function verifyAccessCode(QuotePublicLink $link, string $accessCode): bool
    {
        return $link->isValid()
            && ($link->access_code_hash === null || Hash::check($accessCode, $link->access_code_hash));
    }

    public function accessSessionKey(int $linkId): string
    {
        return "public-quote-access.{$linkId}";
    }

    /**
     * Record a view event on the public link.
     */
    public function recordView(QuotePublicLink $link, ?string $ip = null, ?string $userAgent = null): void
    {
        $link->increment('view_count');
        $link->update(['last_viewed_at' => now()]);

        QuotePublicEvent::query()->create([
            'quote_public_link_id' => $link->id,
            'event_type' => 'viewed',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }

    /**
     * Record customer response (accept, decline, comment).
     *
     * @param  array{signer_name: string, signer_email: string, signer_title?: string|null, feedback_notes?: string|null}  $data
     */
    public function recordResponse(
        QuotePublicLink $link,
        string $responseType,
        array $data,
        ?string $ip = null,
        ?string $userAgent = null
    ): QuoteCustomerResponse {
        return DB::transaction(function () use ($link, $responseType, $data, $ip, $userAgent): QuoteCustomerResponse {
            if (! in_array($responseType, ['accept', 'decline', 'comment'], true)) {
                throw ValidationException::withMessages(['response' => 'Loại phản hồi không hợp lệ.']);
            }
            if ($responseType === 'decline' && trim((string) ($data['feedback_notes'] ?? '')) === '') {
                throw ValidationException::withMessages(['feedbackNotes' => 'Vui lòng nêu lý do từ chối báo giá.']);
            }

            /** @var QuotePublicLink $lockedLink */
            $lockedLink = QuotePublicLink::query()->with('quote.opportunity')->lockForUpdate()->findOrFail($link->id);
            if (! $lockedLink->isValid()
                || $lockedLink->public_snapshot === null
                || (int) data_get($lockedLink->public_snapshot, 'quote.version', 0) !== $lockedLink->version_issued) {
                throw ValidationException::withMessages(['link' => 'Đường dẫn báo giá không còn hiệu lực.']);
            }
            if (in_array($responseType, ['accept', 'decline'], true)
                && QuoteCustomerResponse::query()
                    ->where('quote_public_link_id', $lockedLink->id)
                    ->whereIn('response_type', ['accept', 'decline'])
                    ->exists()) {
                throw ValidationException::withMessages(['response' => 'Báo giá này đã có quyết định cuối từ khách hàng.']);
            }

            /** @var Quote $quote */
            $quote = Quote::query()->lockForUpdate()->findOrFail($lockedLink->quote_id);
            if (in_array($responseType, ['accept', 'decline'], true)
                && ! in_array($quote->status, [QuoteStatus::Issued, QuoteStatus::Sent], true)) {
                throw ValidationException::withMessages(['response' => 'Báo giá không còn ở trạng thái có thể phản hồi.']);
            }

            $response = QuoteCustomerResponse::query()->create([
                'quote_id' => $lockedLink->quote_id,
                'quote_public_link_id' => $lockedLink->id,
                'response_type' => $responseType,
                'signer_name' => $data['signer_name'],
                'signer_email' => $data['signer_email'],
                'signer_title' => $data['signer_title'] ?? null,
                'feedback_notes' => $data['feedback_notes'] ?? null,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'acted_at' => now(),
            ]);

            if ($responseType === 'accept') {
                $quote->update([
                    'status' => QuoteStatus::Accepted,
                ]);

                if ($quote->opportunity_id > 0) {
                    Activity::query()->create([
                        'activity_type' => ActivityType::Note,
                        'subject_type' => Opportunity::class,
                        'subject_id' => $quote->opportunity_id,
                        'user_id' => $quote->created_by,
                        'performed_at' => now(),
                        'title' => 'Khách hàng CHẤP THUẬN Báo giá',
                        'description' => "Khách hàng {$response->signer_name} ({$response->signer_email}) đã chấp thuận Báo giá [{$quote->quote_number}].",
                    ]);
                }
            } elseif ($responseType === 'decline') {
                $quote->update([
                    'status' => QuoteStatus::Declined,
                    'rejection_reason' => $response->feedback_notes ?? 'Khách hàng từ chối qua link công khai.',
                ]);

                if ($quote->opportunity_id > 0) {
                    Activity::query()->create([
                        'activity_type' => ActivityType::Note,
                        'subject_type' => Opportunity::class,
                        'subject_id' => $quote->opportunity_id,
                        'user_id' => $quote->created_by,
                        'performed_at' => now(),
                        'title' => 'Khách hàng TỪ CHỐI Báo giá',
                        'description' => "Khách hàng {$response->signer_name} ({$response->signer_email}) đã từ chối Báo giá [{$quote->quote_number}]. Lý do: {$response->feedback_notes}",
                    ]);
                }
            }

            QuotePublicEvent::query()->create([
                'quote_public_link_id' => $lockedLink->id,
                'event_type' => 'response_submitted',
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'payload' => [
                    'response_id' => $response->id,
                    'response_type' => $responseType,
                    'signer_name' => $response->signer_name,
                ],
                'created_at' => now(),
            ]);

            if (in_array($responseType, ['accept', 'decline'], true)) {
                DB::afterCommit(function () use ($quote, $responseType): void {
                    $quote->loadMissing('opportunity.owner', 'creator');
                    $recipients = collect([$quote->creator, $quote->opportunity?->owner])
                        ->filter()
                        ->unique('id');

                    Notification::send($recipients, new QuoteWorkflowNotification(
                        $quote,
                        $responseType === 'accept' ? 'Khách hàng đã chấp thuận báo giá' : 'Khách hàng đã từ chối báo giá',
                        $responseType === 'accept'
                            ? "Khách hàng đã chấp thuận {$quote->quote_number}."
                            : "Khách hàng đã từ chối {$quote->quote_number}.",
                        $responseType === 'accept' ? 'quote_customer_accepted' : 'quote_customer_declined',
                    ));
                });
            }

            return $response;
        });
    }
}
