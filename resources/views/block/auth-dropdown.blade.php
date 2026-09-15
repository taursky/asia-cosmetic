<details class="group relative" id="account-dropdown" data-auth-dropdown>
    <summary
        class="grid size-10 cursor-pointer list-none place-items-center rounded-full text-zinc-700 transition hover:bg-zinc-100 [&::-webkit-details-marker]:hidden"
        aria-label="@auth Личный кабинет @else Войти @endauth"
    >
        @auth
            <span class="flex size-8 items-center justify-center rounded-full bg-[#071d5d] text-xs font-semibold text-white">
                {{ mb_strtoupper(mb_substr(auth()->user()->name ?: 'U', 0, 1)) }}
            </span>
        @else
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5">
                <circle cx="12" cy="8" r="4" stroke-width="1.8"/>
                <path d="M4.5 20c.8-4 3.2-6 7.5-6s6.7 2 7.5 6" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        @endauth
    </summary>

    <div class="fixed left-1/2 top-[5.5rem] z-50 w-[90vw] max-w-[560px] -translate-x-1/2 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-2xl shadow-zinc-900/10 sm:absolute sm:left-auto sm:right-0 sm:top-full sm:mt-3 sm:w-[560px] sm:max-w-[calc(100vw-2rem)] sm:translate-x-0">
        <button
            type="button"
            data-auth-close
            aria-label="Закрыть"
            class="absolute right-4 top-4 z-20 grid size-9 cursor-pointer place-items-center rounded-full bg-white text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-900"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" class="size-5">
                <path d="M6 6l12 12M18 6 6 18" stroke-width="1.8" stroke-linecap="round"/>
            </svg>
        </button>

        <div class="max-h-[calc(100dvh-7rem)] overflow-y-auto overflow-x-hidden overscroll-contain p-5 pr-16 sm:max-h-[calc(100vh-8rem)] sm:p-6 sm:pr-16">
            @auth
                <div class="text-xs font-medium uppercase tracking-[.16em] text-zinc-400">
                    Личный кабинет
                </div>

                <div class="mt-2 text-lg font-semibold text-zinc-950">
                    {{ auth()->user()->name ?: 'Покупатель' }}
                </div>

                @if(auth()->user()->email)
                    <div class="mt-1 break-all text-sm text-zinc-500">
                        {{ auth()->user()->email }}
                    </div>
                @endif

                @if(auth()->user()->phone)
                    <div class="mt-1 text-sm text-zinc-500">
                        {{ auth()->user()->phone }}
                    </div>
                @endif

                <a
                    href="{{ route('account.index') }}"
                    class="mt-4 flex w-full items-center justify-center rounded-xl bg-[#071d5d] px-4 py-3 text-sm font-semibold text-white transition hover:bg-[#0d2e84]"
                >
                    Личный кабинет
                </a>

                <div class="mt-5 border-t border-zinc-100 pt-4">
                    <form method="post" action="{{ url('/logout') }}">
                        @csrf

                        <button
                            type="submit"
                            class="flex w-full items-center justify-center rounded-xl border border-zinc-200 px-4 py-3 text-sm font-semibold text-zinc-800 transition hover:bg-zinc-50"
                        >
                            Выйти
                        </button>
                    </form>
                </div>
            @else
                <livewire:customer-auth-form />
            @endauth
        </div>
    </div>
</details>

@once
    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-auth-close]');

            if (!button) {
                return;
            }

            button.closest('details')?.removeAttribute('open');
        });
    </script>
@endonce
