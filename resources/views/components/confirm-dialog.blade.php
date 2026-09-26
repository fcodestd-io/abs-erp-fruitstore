<div x-data="{
    open: false,
    title: '',
    message: '',
    confirmText: 'Ya, Lanjutkan',
    cancelText: 'Batal',
    variant: 'danger',
    actionUrl: '',
    actionMethod: 'POST',

    setup(detail) {
        this.title = detail.title || 'Konfirmasi Tindakan';
        this.message = detail.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
        this.confirmText = detail.confirmText || 'Ya, Lanjutkan';
        this.cancelText = detail.cancelText || 'Batal';
        this.variant = detail.variant || 'danger';
        this.actionUrl = detail.actionUrl || '';
        this.actionMethod = detail.actionMethod || 'POST';
        this.open = true;
    }
}" @open-confirm.window="setup($event.detail)" x-show="open" x-cloak class="relative z-50"
    aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop Overlay -->
    <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="open = false"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal Dialog Container -->
            <div x-show="open" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <!-- Icon Circle -->
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl sm:mx-0 sm:h-10 sm:w-10"
                            :class="{
                                'bg-red-50 text-red-600': variant === 'danger',
                                'bg-amber-50 text-amber-600': variant === 'warning',
                                'bg-abs-green-50 text-abs-green-600': variant === 'success',
                                'bg-slate-100 text-slate-600': variant === 'info'
                            }">
                            <template x-if="variant === 'danger' || variant === 'warning'">
                                <i data-lucide="alert-triangle" class="w-5 h-5"></i>
                            </template>
                            <template x-if="variant === 'success'">
                                <i data-lucide="check-circle-2" class="w-5 h-5"></i>
                            </template>
                            <template x-if="variant === 'info'">
                                <i data-lucide="help-circle" class="w-5 h-5"></i>
                            </template>
                        </div>

                        <!-- Content -->
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-base font-bold leading-6 text-slate-900" id="modal-title" x-text="title">
                            </h3>
                            <div class="mt-2">
                                <p class="text-xs sm:text-sm text-slate-500 leading-relaxed" x-text="message"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Action Buttons -->
                <div
                    class="bg-slate-50/80 px-6 py-4 sm:flex sm:flex-row-reverse sm:px-6 gap-2 border-t border-slate-100">
                    <form :action="actionUrl" method="POST" class="inline-flex w-full sm:w-auto">
                        @csrf
                        <template x-if="actionMethod.toUpperCase() !== 'POST'">
                            <input type="hidden" name="_method" :value="actionMethod">
                        </template>

                        <button type="submit"
                            class="inline-flex w-full justify-center rounded-xl px-4 py-2.5 text-xs font-bold text-white shadow-sm transition sm:w-auto active:scale-95"
                            :class="{
                                'bg-red-600 hover:bg-red-700 shadow-red-600/20': variant === 'danger',
                                'bg-amber-600 hover:bg-amber-700 shadow-amber-600/20': variant === 'warning',
                                'bg-abs-green-600 hover:bg-abs-green-700 shadow-abs-green-600/20': variant === 'success',
                                'bg-slate-800 hover:bg-slate-900 shadow-slate-800/20': variant === 'info'
                            }"
                            x-text="confirmText">
                        </button>
                    </form>

                    <button type="button" @click="open = false"
                        class="mt-3 sm:mt-0 inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-xs font-bold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 hover:bg-slate-50 transition sm:w-auto"
                        x-text="cancelText">
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
