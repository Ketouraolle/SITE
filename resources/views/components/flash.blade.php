{{--
    Notifications "toast". Deux sources :
    - le flash de session (success / error) apres une redirection ;
    - l'evenement navigateur "toast" : $this->dispatch('toast', type: 'success', message: '...').
--}}
<div
    x-data="{
        toasts: [],
        counter: 0,
        push(type, message) {
            if (! message) return;
            const id = ++this.counter;
            this.toasts.push({ id, type, message });
            setTimeout(() => this.dismiss(id), 5000);
        },
        dismiss(id) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },
    }"
    x-init="
        push('success', @js(session('success')));
        push('error', @js(session('error')));
    "
    x-on:toast.window="push($event.detail.type ?? 'success', $event.detail.message)"
    class="pointer-events-none fixed inset-x-0 top-3 z-[60] flex flex-col items-center gap-2 px-4 sm:top-5 sm:items-end sm:px-6"
    aria-live="polite"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            class="pointer-events-auto flex w-full max-w-sm items-start gap-3 rounded-xl bg-white p-3.5 shadow-lg ring-1"
            :class="toast.type === 'success' ? 'ring-emerald-200' : 'ring-red-200'"
            role="status"
        >
            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full" :class="toast.type === 'success' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'">
                <span x-show="toast.type === 'success'"><x-icon name="check-circle" :size="18" /></span>
                <span x-show="toast.type !== 'success'"><x-icon name="alert" :size="18" /></span>
            </span>
            <p class="min-w-0 flex-1 pt-1 text-sm font-medium text-slate-800" x-text="toast.message"></p>
            <button type="button" class="shrink-0 rounded-md p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Fermer" x-on:click="dismiss(toast.id)">
                <x-icon name="close" :size="16" />
            </button>
        </div>
    </template>
</div>
