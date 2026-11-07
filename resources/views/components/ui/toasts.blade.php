<div
    class="fixed inset-x-4 bottom-4 z-50 grid justify-items-end gap-2 sm:left-auto sm:right-6"
    role="status"
    aria-live="polite"
    x-data="{
        toasts: [],
        add(message) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, message });
            setTimeout(() => this.toasts = this.toasts.filter(toast => toast.id !== id), 5000);
        },
    }"
    x-init="@if (session('toast')) add(@js(session('toast'))) @endif"
    x-on:toast.window="add($event.detail.message)"
>
    <template x-for="toast in toasts" x-bind:key="toast.id">
        <p class="toast" x-text="toast.message"></p>
    </template>
</div>
