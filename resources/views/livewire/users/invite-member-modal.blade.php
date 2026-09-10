<div>
    <flux:modal name="invite-member-modal" class="w-full" style="width: 38rem; max-width: 95vw;" wire:close="closeModal">
        @if ($showModal)
            <div class="space-y-6">
                @if ($generatedLink)
                    {{-- Success State with 1-click copyable link --}}
                    <div class="text-center sm:text-left">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400 sm:mx-0">
                            <flux:icon name="check" class="h-6 w-6" />
                        </div>
                        <flux:heading size="lg" class="mt-4">Đã tạo lời mời thành công!</flux:heading>
                        <flux:subheading class="mt-1">
                            Hệ thống đã gửi email (nếu SMTP khả dụng) và tự động đồng bộ hồ sơ nhân viên cho <strong>{{ $email }}</strong>.
                        </flux:subheading>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900/50">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">
                            Liên kết kích hoạt tài khoản trực tiếp (hạn 7 ngày):
                        </label>
                        <div class="mt-2 flex items-center gap-2">
                            <input
                                type="text"
                                readonly
                                value="{{ $generatedLink }}"
                                id="modal-invitation-link-input"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-mono text-slate-800 focus:outline-none dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            />
                            <button
                                type="button"
                                x-data="{ copied: false }"
                                x-on:click="navigator.clipboard.writeText('{{ $generatedLink }}'); copied = true; setTimeout(() => copied = false, 2500)"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-emerald-500 focus:outline-none"
                            >
                                <span x-show="!copied" class="flex items-center gap-1">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 0 0-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 0 1-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 0 0-3.375-3.375h-1.5a1.125 1.125 0 0 1-1.125-1.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H9.75" />
                                    </svg>
                                    Sao chép
                                </span>
                                <span x-show="copied" x-cloak class="flex items-center gap-1 font-semibold">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                    Đã sao chép!
                                </span>
                            </button>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">
                            Bạn có thể sao chép liên kết này để gửi trực tiếp qua Zalo, Slack hoặc tin nhắn cho nhân viên.
                        </p>
                    </div>

                    <div class="flex flex-col-reverse gap-3 pt-4 sm:flex-row sm:justify-end">
                        <flux:button variant="ghost" wire:click="closeModal">Đóng</flux:button>
                        <flux:button variant="primary" wire:click="resetForAnother">Mời thêm thành viên khác</flux:button>
                    </div>
                @else
                    {{-- Form State --}}
                    <div>
                        <flux:heading size="lg">Mời thành viên mới tham gia CRM</flux:heading>
                        <flux:subheading class="mt-1">
                            Hệ thống sẽ gửi email chứa liên kết bảo mật và tự động đồng bộ hồ sơ nhân sự.
                        </flux:subheading>
                    </div>

                    <form wire:submit="sendInvitation" class="space-y-4">
                        <flux:input wire:model.blur="name" label="Họ và tên" placeholder="Ví dụ: Trần Văn Nam" required />
                        <flux:error name="name" />

                        <flux:input wire:model.blur="email" type="email" label="Email nhận lời mời" placeholder="nam@salesflow.test" required />
                        <flux:error name="email" />

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <x-forms.smart-select wire:model="departmentId" label="Phòng ban" placeholder="Chưa gán phòng ban">
                                    <option value="">Chưa gán phòng ban</option>
                                    @foreach ($this->departmentOptions as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }} ({{ $dept->code }})</option>
                                    @endforeach
                                </x-forms.smart-select>
                                <flux:error name="departmentId" />
                            </div>

                            <div>
                                <x-forms.smart-select wire:model="role" label="Vai trò hệ thống">
                                    @foreach ($this->roleOptions as $roleKey => $roleLabel)
                                        <option value="{{ $roleKey }}">{{ $roleLabel }}</option>
                                    @endforeach
                                </x-forms.smart-select>
                                <flux:error name="role" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-800">
                            <flux:button variant="ghost" wire:click="closeModal">Hủy</flux:button>
                            <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" wire:target="sendInvitation">
                                Gửi lời mời
                            </flux:button>
                        </div>
                    </form>
                @endif
            </div>
        @endif
    </flux:modal>
</div>
