<div>
    @if ($canDelete)
        <flux:button variant="danger" icon="trash" wire:click="openDelete">Đưa vào thùng rác</flux:button>

        <flux:modal name="delete-lead" class="md:w-[32rem]">
            <div class="space-y-5">
                <div>
                    <flux:heading size="lg">Đưa Lead vào thùng rác?</flux:heading>
                    <flux:text class="mt-2">
                        Lead <strong>{{ $leadName }}</strong> sẽ biến mất khỏi danh sách chính nhưng vẫn giữ tag, workflow history và audit để có thể khôi phục.
                    </flux:text>
                </div>

                <flux:textarea wire:model="deleteReason" label="Lý do xóa" rows="3" maxlength="500" placeholder="Không bắt buộc" />

                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" x-on:click="$flux.modal('delete-lead').close()">Hủy</flux:button>
                    <flux:button variant="danger" wire:click="confirmDelete" wire:loading.attr="disabled" wire:target="confirmDelete">Xác nhận xóa</flux:button>
                </div>
            </div>
        </flux:modal>
    @endif
</div>
