@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Select Instagram Accounts'))
@section('titlebar_subtitle', __('Choose the Instagram Business account(s) you want to connect'))
@section('titlebar_back', route('dashboard.user.integrations.index'))
@section('titlebar_actions', '')

@section('content')
<div class="py-10">
    <div class="max-w-2xl mx-auto">
        <x-card class="p-6">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="size-11 rounded-xl bg-gradient-to-tr from-amber-500 via-pink-500 to-purple-600 flex items-center justify-center flex-shrink-0 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">@lang('Select Instagram Account(s)')</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @lang('Found') <strong>{{ count($accounts) }}</strong> @lang('Instagram Business account(s). Select the ones you want to connect.')
                        </p>
                    </div>
                </div>

                {{-- Select / Deselect All --}}
                @if(count($accounts) > 1)
                    <button type="button" id="toggleSelectAllBtn" onclick="toggleSelectAll()" class="text-xs font-medium text-pink-600 hover:text-pink-700 dark:text-pink-400 dark:hover:text-pink-300">
                        @lang('Select All')
                    </button>
                @endif
            </div>

            {{-- Flash error --}}
            @if (session('type') === 'error')
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                    {{ session('message') }}
                </div>
            @endif

            <form method="POST" action="{{ route('dashboard.user.integrations.instagram.store') }}" id="instagramSelectForm">
                @csrf
                <input type="hidden" name="pick_token" value="{{ $pick_token }}">

                <div class="space-y-2 mb-6 max-h-[480px] overflow-y-auto pr-1">
                    @foreach ($accounts as $index => $acc)
                        @php
                            $isAlreadyConnected = in_array((string) $acc['ig_id'], $alreadyConnectedIds, true);
                        @endphp
                        <label class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700
                                      cursor-pointer hover:border-pink-400 hover:bg-pink-50/50 dark:hover:bg-pink-900/10
                                      has-[:checked]:border-pink-500 has-[:checked]:bg-pink-50 dark:has-[:checked]:bg-pink-900/20
                                      transition-colors duration-100">

                            <input type="checkbox"
                                   name="ig_ids[]"
                                   value="{{ $acc['ig_id'] }}"
                                   class="ig-checkbox accent-pink-600 size-4 rounded flex-shrink-0"
                                   {{ $isAlreadyConnected || $index === 0 ? 'checked' : '' }}>

                            {{-- Avatar --}}
                            @if (!empty($acc['avatar']))
                                <img src="{{ $acc['avatar'] }}" class="size-10 rounded-full object-cover flex-shrink-0" alt="{{ $acc['name'] }}">
                            @else
                                <div class="size-10 rounded-full bg-gradient-to-tr from-amber-500 via-pink-500 to-purple-600 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    {{ strtoupper(substr($acc['name'], 0, 1)) }}
                                </div>
                            @endif

                            {{-- Details --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                                        {{ $acc['name'] }}
                                    </p>
                                    @if ($isAlreadyConnected)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            @lang('Connected')
                                        </span>
                                    @endif
                                </div>
                                @if (!empty($acc['username']))
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        &#64;{{ ltrim($acc['username'], '@') }}
                                    </p>
                                @endif
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5">
                                    @lang('Linked Page:') <span class="font-medium text-gray-600 dark:text-gray-300">{{ $acc['page_name'] }}</span>
                                </p>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('dashboard.user.integrations.index') }}"
                       class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        ← @lang('Cancel')
                    </a>
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-pink-600 to-purple-600 hover:from-pink-700 hover:to-purple-700
                                   text-white text-sm font-semibold transition-colors duration-150 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        @lang('Connect Selected Account(s)')
                    </button>
                </div>
            </form>

        </x-card>
    </div>
</div>

<script>
    function toggleSelectAll() {
        const checkboxes = document.querySelectorAll('.ig-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        const btn = document.getElementById('toggleSelectAllBtn');
        if (btn) {
            btn.textContent = allChecked ? "{{ __('Select All') }}" : "{{ __('Deselect All') }}";
        }
    }
</script>
@endsection
