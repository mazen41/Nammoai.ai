@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Integrations & Channels'))
@section('titlebar_subtitle', __('Manage your AI auto-reply channels, messaging platforms, and connected e-commerce stores'))

@section('content')
<div class="py-6 max-w-7xl mx-auto" x-data="{ activeTab: 'all', search: '' }">

    {{-- Top Metrics Header --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/60 rounded-xl p-4 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-lg bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">@lang('Connected Channels')</span>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">{{ $totalConnected ?? 0 }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/60 rounded-xl p-4 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-lg bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
            <div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">@lang('Messaging & Auto-Reply')</span>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">3 <span class="text-xs font-normal text-gray-400">@lang('Channels')</span></div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/60 rounded-xl p-4 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-lg bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            </div>
            <div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">@lang('E-Commerce Stores')</span>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">3 <span class="text-xs font-normal text-gray-400">@lang('Platforms')</span></div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800/80 border border-gray-100 dark:border-gray-700/60 rounded-xl p-4 shadow-sm flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-lg bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
            </div>
            <div>
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">@lang('Social Networks')</span>
                <div class="text-2xl font-bold text-gray-900 dark:text-white mt-0.5">4 <span class="text-xs font-normal text-gray-400">@lang('Networks')</span></div>
            </div>
        </div>
    </div>

    {{-- Tabs & Filters Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-2 border-b border-gray-100 dark:border-gray-800">
        <div class="flex items-center gap-1.5 p-1 bg-gray-100/80 dark:bg-gray-800/60 rounded-xl overflow-x-auto">
            <button
                @click="activeTab = 'all'"
                :class="activeTab === 'all' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm font-semibold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap"
            >
                @lang('All Integrations') ({{ count($platforms) }})
            </button>
            <button
                @click="activeTab = 'messaging'"
                :class="activeTab === 'messaging' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm font-semibold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
            >
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                @lang('Auto-Reply & Messaging')
            </button>
            <button
                @click="activeTab = 'stores'"
                :class="activeTab === 'stores' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm font-semibold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
            >
                <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                @lang('E-Commerce Stores')
            </button>
            <button
                @click="activeTab = 'social'"
                :class="activeTab === 'social' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm font-semibold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
            >
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                @lang('Social Publishing')
            </button>
        </div>

        {{-- Quick Search --}}
        <div class="relative w-full sm:w-64">
            <input
                type="text"
                x-model="search"
                placeholder="{{ __('Search platforms...') }}"
                class="w-full text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg pl-8 pr-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all text-gray-900 dark:text-white"
            />
            <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
    </div>

    {{-- Cards Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">
        @foreach ($platforms as $platformKey => $platform)
            @if ($platform['enabled'])
            @php
                $accounts = $connectedAccounts->get($platformKey, collect());
                $hasActiveConnection = $accounts->isNotEmpty() && $accounts->contains(fn ($acc) => $acc->isConnected());
                $category = $platform['category'] ?? 'messaging';
            @endphp
            <div
                x-show="(activeTab === 'all' || activeTab === '{{ $category }}') && ('{{ strtolower($platform['name']) }}'.includes(search.toLowerCase()) || search === '')"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="flex flex-col justify-between bg-white dark:bg-gray-800/90 border border-gray-100 dark:border-gray-700/70 rounded-2xl p-5 shadow-sm hover:shadow-md hover:border-gray-200 dark:hover:border-gray-600 transition-all duration-200"
            >
                <div>
                    {{-- Platform Header --}}
                    <div class="flex items-start justify-between gap-3 mb-3.5">
                        <div class="flex items-center gap-3.5">
                            {{-- Brand Icon --}}
                            <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 shadow-sm
                                @if($platformKey === 'whatsapp_cloud') bg-emerald-50 text-emerald-600 border border-emerald-100 dark:bg-emerald-950/30 dark:border-emerald-800
                                @elseif($platformKey === 'messenger') bg-blue-50 text-[#1877F2] border border-blue-100 dark:bg-blue-950/30 dark:border-blue-800
                                @elseif($platformKey === 'instagram') bg-pink-50 text-[#E4405F] border border-pink-100 dark:bg-pink-950/30 dark:border-pink-800
                                @elseif($platformKey === 'salla') bg-teal-50 text-[#004D40] border border-teal-100 dark:bg-teal-950/30 dark:border-teal-800
                                @elseif($platformKey === 'shopify') bg-emerald-50 text-[#96bf48] border border-emerald-100 dark:bg-emerald-950/30 dark:border-emerald-800
                                @elseif($platformKey === 'woocommerce') bg-purple-50 text-[#7F54B3] border border-purple-100 dark:bg-purple-950/30 dark:border-purple-800
                                @elseif($platformKey === 'tiktok') bg-gray-50 text-black dark:text-white border border-gray-200 dark:bg-gray-900 dark:border-gray-700
                                @elseif($platformKey === 'youtube') bg-red-50 text-[#FF0000] border border-red-100 dark:bg-red-950/30 dark:border-red-800
                                @elseif($platformKey === 'x') bg-gray-50 text-black dark:text-white border border-gray-200 dark:bg-gray-900 dark:border-gray-700
                                @elseif($platformKey === 'linkedin') bg-blue-50 text-[#0A66C2] border border-blue-100 dark:bg-blue-950/30 dark:border-blue-800
                                @else bg-gray-100 text-gray-700 border border-gray-200
                                @endif
                            ">
                                @if($platformKey === 'whatsapp_cloud')
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.582 2.128 2.182-.573c.978.58 1.911.928 3.145.929 3.178 0 5.767-2.587 5.768-5.766.001-3.187-2.575-5.77-5.764-5.771zm3.392 8.244c-.144.405-.837.774-1.17.824-.312.045-.694.06-2.148-.541-1.859-.769-3.044-2.658-3.137-2.781-.092-.122-.751-.998-.751-1.904s.475-1.353.644-1.538c.169-.185.369-.231.492-.231.123 0 .246.002.354.007.113.006.262-.042.409.311.15.358.513 1.25.558 1.343.045.093.076.201.015.324-.061.123-.092.2-.184.308-.092.107-.194.24-.277.323-.092.093-.188.193-.081.378.107.185.476.786 1.022 1.272.703.628 1.296.822 1.48.915.185.092.292.077.4-.047.108-.123.461-.538.584-.723.123-.185.246-.154.415-.092.17.062 1.077.508 1.262.599.185.092.308.138.354.215.045.078.045.449-.099.854zM12 2C6.477 2 2 6.477 2 12c0 1.891.524 3.662 1.435 5.176L2 22l4.981-1.306A9.957 9.957 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2z"/></svg>
                                @elseif($platformKey === 'messenger')
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.477 2 2 6.145 2 11.258c0 2.91 1.455 5.515 3.734 7.185V22l3.411-1.872c.913.253 1.884.389 2.855.389 5.523 0 10-4.145 10-9.259C22 6.145 17.523 2 12 2zm1.002 12.441l-2.553-2.723-4.984 2.723 5.485-5.823 2.616 2.723 4.921-2.723-5.485 5.823z"/></svg>
                                @elseif($platformKey === 'instagram')
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                @elseif($platformKey === 'salla')
                                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                                @elseif($platformKey === 'shopify')
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M19.53 5.08c-.14-.42-.51-.68-.94-.68l-3.24.01c-.13-.39-.41-1.5-1.07-2.17C13.43 1.39 12.38 1 11.23 1c-1.35 0-2.34.52-2.94 1.54-.42.71-.56 1.62-.57 2.53L4.73 6.06c-.47.16-.76.62-.71 1.12l1.6 15.02c.05.47.44.82.91.82h11.88c.47 0 .86-.36.91-.83l1.24-16.14c.03-.43-.16-.83-.49-.97zm-7.3-2.61c.64 0 1.25.23 1.71.65.41.38.64.92.76 1.52l-4.14.93c.12-.87.41-1.63.85-2.19.26-.33.56-.55.82-.69zm-1.84 8.79c-.06.05-.14.07-.22.07-.15 0-.29-.08-.36-.21l-1.04-1.85c-.09-.16-.07-.36.05-.5.12-.14.31-.2.49-.15l1.83.5c.2.06.33.24.32.45-.01.21-.16.38-.36.43l-.71.26zm3.93 4.88c-.68 1.19-1.92 1.84-3.32 1.84-1.5 0-2.73-.78-3.37-2.13-.19-.4-.01-.88.39-1.07.4-.19.88-.01 1.07.39.38.81 1.09 1.26 1.91 1.26.8 0 1.55-.42 1.95-1.12.36-.63.34-1.39-.06-1.97-.24-.34-.58-.61-1-.8-.5-.22-1.04-.42-1.58-.62-.89-.33-1.85-.75-2.45-1.46-.72-.85-.92-1.99-.54-3.04.47-1.3 1.7-2.1 3.14-2.1 1.34 0 2.45.65 3.05 1.79.22.42.06.94-.36 1.16-.42.22-.94.06-1.16-.36-.37-.7-.99-1.04-1.53-1.04-.76 0-1.45.47-1.7 1.18-.21.58-.09 1.19.32 1.66.36.42.98.69 1.57.91.56.21 1.14.43 1.68.68.85.39 1.52.99 1.88 1.74.38.78.36 1.69-.03 2.37z"/></svg>
                                @elseif($platformKey === 'woocommerce')
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M2.227 4.545C.998 4.545 0 5.543 0 6.773v10.455c0 1.229.998 2.227 2.227 2.227h19.545c1.23 0 2.228-.998 2.228-2.227V6.773c0-1.23-.998-2.228-2.228-2.228H2.227zm3.178 3.541c.643 0 1.197.352 1.487.876.417-.557 1.08-.876 1.785-.876.657 0 1.237.29 1.633.784.45-.515 1.11-.784 1.834-.784 1.472 0 2.502 1.19 2.502 2.89v4.205h-1.964V11.23c0-.853-.473-1.353-1.18-1.353-.67 0-1.187.5-1.187 1.353v3.946h-1.963V11.23c0-.853-.474-1.353-1.18-1.353-.672 0-1.188.5-1.188 1.353v3.946H3.443V11.23c0-.853-.473-1.353-1.18-1.353-.67 0-1.187.5-1.187 1.353v3.946H.001V11.23c0-1.7 1.03-2.89 2.502-2.89.724 0 1.383.27 1.833.784.397-.494.977-.784 1.633-.784l-.564-.244z"/></svg>
                                @elseif($platformKey === 'tiktok')
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.24 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                                @elseif($platformKey === 'youtube')
                                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
                                @elseif($platformKey === 'x')
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                @elseif($platformKey === 'linkedin')
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                @endif
                            </div>

                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $platform['name'] }}</h3>
                                    @if(!empty($platform['badge']))
                                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700/80 text-gray-600 dark:text-gray-300">
                                            {{ $platform['badge'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-1">
                                    @if ($hasActiveConnection)
                                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                            @lang('Connected') ({{ $accounts->count() }})
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-400 dark:text-gray-500">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                            @lang('Not Connected')
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Quick Connect / Reconnect Action Button --}}
                        @if ($platform['connect_url'] && $platform['connect_url'] !== '#')
                            <a
                                href="{{ $platform['connect_url'] }}"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg transition-all shadow-sm
                                    @if(!$hasActiveConnection)
                                        bg-primary text-white hover:bg-primary/90 hover:shadow-primary/20
                                    @else
                                        bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200
                                    @endif"
                            >
                                @if(!$hasActiveConnection)
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                    @lang('Connect')
                                @else
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    @lang('Reconnect')
                                @endif
                            </a>
                        @endif
                    </div>

                    {{-- Description --}}
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed mb-4">
                        {{ $platform['description'] }}
                    </p>

                    {{-- Connected Accounts List / Empty State --}}
                    @if ($accounts->isEmpty())
                        <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700/80 bg-gray-50/50 dark:bg-gray-800/30 p-3.5 text-center flex items-center justify-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            @lang('No account connected yet')
                        </div>
                    @else
                        <div class="space-y-2.5">
                            @foreach ($accounts as $account)
                                @php
                                    $displayName = $account->getDisplayName();
                                    $username = $account->account_username ? ltrim($account->account_username, '@') : null;
                                    $avatar = $account->account_avatar;
                                    $isConnected = $account->isConnected();
                                    $disconnectUrl = $account->disconnect_url
                                        ?? (!empty($account->id) ? route('dashboard.user.integrations.disconnect', $account->id) : null);
                                @endphp
                                <div class="flex items-center justify-between rounded-xl border border-gray-100 dark:border-gray-700/80 bg-gray-50/70 dark:bg-gray-800/50 px-3.5 py-2.5 transition-all">
                                    <div class="flex items-center gap-3 min-w-0">
                                        @if ($avatar)
                                            <img
                                                src="{{ custom_theme_url($avatar) }}"
                                                class="w-8 h-8 rounded-full object-cover border border-gray-200 dark:border-gray-600 shrink-0"
                                                alt="{{ $displayName }}"
                                            />
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0 border border-primary/20">
                                                {{ strtoupper(substr($displayName, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="truncate">
                                            <p class="font-semibold text-xs text-gray-900 dark:text-gray-100 truncate flex items-center gap-1.5">
                                                <span>{{ $displayName }}</span>
                                                @if($isConnected)
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                @else
                                                    <span class="text-[10px] text-amber-500 font-normal">(@lang('Expired'))</span>
                                                @endif
                                            </p>
                                            @if ($username)
                                                <p class="text-[11px] text-gray-500 dark:text-gray-400 font-mono truncate" dir="ltr">{{ '@' . $username }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    @if ($disconnectUrl)
                                        <form method="POST" action="{{ $disconnectUrl }}" class="shrink-0 ml-2">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 font-medium px-2.5 py-1 rounded-md border border-red-200/80 dark:border-red-900/60 hover:bg-red-50 dark:hover:bg-red-950/40 transition-all flex items-center gap-1"
                                                onclick="return confirm('{{ __('Are you sure you want to disconnect this account?') }}')"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                @lang('Disconnect')
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
            @endif
        @endforeach
    </div>
</div>
@endsection