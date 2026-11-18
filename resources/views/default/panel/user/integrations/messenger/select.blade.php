@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Select Facebook Pages'))
@section('titlebar_subtitle', __('Choose the Facebook Page(s) you want to connect to Messenger'))
@section('titlebar_back', route('dashboard.user.integrations.index'))
@section('titlebar_actions', '')

@section('content')
<div class="py-10">
    <div class="max-w-2xl mx-auto">
        <x-card class="p-6">

            {{-- Header --}}
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="size-11 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-6 text-blue-600 dark:text-blue-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.912 1.453 5.518 3.727 7.197V22l3.39-1.86c.928.257 1.91.396 2.883.396 5.523 0 10-4.145 10-9.278C22 6.145 17.523 2 12 2zm1.06 12.44-2.73-2.91-5.33 2.91 5.86-6.22 2.79 2.91 5.27-2.91-5.86 6.22z"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold">@lang('Select Facebook Page(s)')</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            @lang('Found') <strong>{{ count($pages) }}</strong> @lang('page(s) on your Facebook account. Select the ones you want to connect.')
                        </p>
                    </div>
                </div>

                {{-- Select / Deselect All --}}
                @if(count($pages) > 1)
                    <button type="button" id="toggleSelectAllBtn" onclick="toggleSelectAll()" class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
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

            <form method="POST" action="{{ route('dashboard.user.integrations.messenger.store') }}" id="messengerSelectForm">
                @csrf
                <input type="hidden" name="pick_token" value="{{ $pick_token }}">

                <div class="space-y-2 mb-6 max-h-[480px] overflow-y-auto pr-1">
                    @foreach ($pages as $index => $page)
                        @php
                            $isAlreadyConnected = in_array((string) $page['id'], $alreadyConnectedIds, true);
                            $avatarUrl = data_get($page, 'picture.data.url');
                        @endphp
                        <label class="flex items-center gap-4 p-4 rounded-xl border border-gray-200 dark:border-gray-700
                                      cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-blue-900/10
                                      has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20
                                      transition-colors duration-100">

                            <input type="checkbox"
                                   name="page_ids[]"
                                   value="{{ $page['id'] }}"
                                   class="page-checkbox accent-blue-600 size-4 rounded flex-shrink-0"
                                   {{ $isAlreadyConnected || $index === 0 ? 'checked' : '' }}>

                            {{-- Page Avatar / Icon --}}
                            @if ($avatarUrl)
                                <img src="{{ $avatarUrl }}" class="size-10 rounded-full object-cover flex-shrink-0" alt="{{ $page['name'] }}">
                            @else
                                <div class="size-10 rounded-full bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 flex items-center justify-center font-bold text-sm flex-shrink-0">
                                    {{ strtoupper(substr($page['name'], 0, 1)) }}
                                </div>
                            @endif

                            {{-- Details --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 truncate">
                                        {{ $page['name'] }}
                                    </p>
                                    @if ($isAlreadyConnected)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                                            @lang('Connected')
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ $page['category'] ?? __('Facebook Page') }}
                                </p>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500 mt-0.5 font-mono">
                                    Page ID: {{ $page['id'] }}
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
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700
                                   text-white text-sm font-semibold transition-colors duration-150 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        @lang('Connect Selected Page(s)')
                    </button>
                </div>
            </form>

        </x-card>
    </div>
</div>

<script>
    function toggleSelectAll() {
        const checkboxes = document.querySelectorAll('.page-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        const btn = document.getElementById('toggleSelectAllBtn');
        if (btn) {
            btn.textContent = allChecked ? "{{ __('Select All') }}" : "{{ __('Deselect All') }}";
        }
    }
</script>
@endsection
