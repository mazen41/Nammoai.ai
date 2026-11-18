@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Integrations & Channels'))
@section('titlebar_subtitle', __('Manage your AI auto-reply channels, messaging platforms, and connected e-commerce stores'))

@section('content')
<div class="py-6 max-w-7xl mx-auto" x-data="{ activeTab: 'all', search: '' }">

    {{-- Top Metrics Header (Theme-adaptive with x-card) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <x-card class="border border-card-border">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-emerald-500/10 text-emerald-500 flex items-center justify-center shrink-0 border border-emerald-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-foreground/60">@lang('Connected Channels')</span>
                    <div class="text-2xl font-bold text-heading-foreground mt-0.5">{{ $totalConnected ?? 0 }}</div>
                </div>
            </div>
        </x-card>

        <x-card class="border border-card-border">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-blue-500/10 text-blue-500 flex items-center justify-center shrink-0 border border-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-foreground/60">@lang('Messaging & Auto-Reply')</span>
                    <div class="text-2xl font-bold text-heading-foreground mt-0.5">3 <span class="text-xs font-normal text-foreground/50">@lang('Channels')</span></div>
                </div>
            </div>
        </x-card>

        <x-card class="border border-card-border">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-teal-500/10 text-teal-500 flex items-center justify-center shrink-0 border border-teal-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-foreground/60">@lang('E-Commerce Stores')</span>
                    <div class="text-2xl font-bold text-heading-foreground mt-0.5">3 <span class="text-xs font-normal text-foreground/50">@lang('Platforms')</span></div>
                </div>
            </div>
        </x-card>

        <x-card class="border border-card-border">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-purple-500/10 text-purple-500 flex items-center justify-center shrink-0 border border-purple-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                </div>
                <div>
                    <span class="text-xs font-medium text-foreground/60">@lang('Social Networks')</span>
                    <div class="text-2xl font-bold text-heading-foreground mt-0.5">4 <span class="text-xs font-normal text-foreground/50">@lang('Networks')</span></div>
                </div>
            </div>
        </x-card>
    </div>

    {{-- Tabs & Filters Bar --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-2 border-b border-card-border">
        <div class="flex items-center gap-1.5 p-1 bg-foreground/5 rounded-xl overflow-x-auto">
            <button
                @click="activeTab = 'all'"
                :class="activeTab === 'all' ? 'bg-primary text-white shadow-sm font-semibold' : 'text-foreground/70 hover:text-foreground font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap"
            >
                @lang('All Integrations') ({{ count($platforms) }})
            </button>
            <button
                @click="activeTab = 'messaging'"
                :class="activeTab === 'messaging' ? 'bg-primary text-white shadow-sm font-semibold' : 'text-foreground/70 hover:text-foreground font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
            >
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                @lang('Auto-Reply & Messaging')
            </button>
            <button
                @click="activeTab = 'stores'"
                :class="activeTab === 'stores' ? 'bg-primary text-white shadow-sm font-semibold' : 'text-foreground/70 hover:text-foreground font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
            >
                <span class="w-2 h-2 rounded-full bg-teal-400"></span>
                @lang('E-Commerce Stores')
            </button>
            <button
                @click="activeTab = 'social'"
                :class="activeTab === 'social' ? 'bg-primary text-white shadow-sm font-semibold' : 'text-foreground/70 hover:text-foreground font-medium'"
                class="px-3.5 py-1.5 rounded-lg text-xs transition-all whitespace-nowrap flex items-center gap-1.5"
            >
                <span class="w-2 h-2 rounded-full bg-blue-400"></span>
                @lang('Social Publishing')
            </button>
        </div>

        {{-- Quick Search Input --}}
        <div class="relative w-full sm:w-64">
            <input
                type="text"
                x-model="search"
                placeholder="{{ __('Search platforms...') }}"
                class="w-full text-xs bg-card-background text-heading-foreground border border-card-border rounded-xl pl-8 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all placeholder:text-foreground/40"
            />
            <svg class="w-4 h-4 text-foreground/40 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
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
                $iconName = $platform['icon'] ?? $platformKey;
            @endphp
            <div
                x-show="(activeTab === 'all' || activeTab === '{{ $category }}') && ('{{ strtolower($platform['name']) }}'.includes(search.toLowerCase()) || search === '')"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="h-full"
            >
                <x-card class="h-full flex flex-col justify-between border border-card-border hover:border-primary/40 transition-all duration-200 shadow-sm hover:shadow-md">
                    <div>
                        {{-- Platform Header --}}
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3.5">
                                {{-- Official Real Brand Image --}}
                                <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 p-2 bg-foreground/5 border border-card-border shadow-xs">
                                    <img
                                        src="{{ asset('vendor/integrations/icons/' . $iconName . '.svg') }}"
                                        class="w-8 h-8 object-contain"
                                        alt="{{ $platform['name'] }}"
                                        onerror="this.onerror=null; this.src='{{ asset('vendor/social-media/icons/' . $iconName . '.svg') }}';"
                                    />
                                </div>

                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-bold text-heading-foreground">{{ $platform['name'] }}</h3>
                                        @if(!empty($platform['badge']))
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-foreground/5 border border-card-border text-foreground/70">
                                                {{ $platform['badge'] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        @if ($hasActiveConnection)
                                            <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-500">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                                @lang('Connected') ({{ $accounts->count() }})
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-foreground/40">
                                                <span class="w-1.5 h-1.5 rounded-full bg-foreground/20"></span>
                                                @lang('Not Connected')
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Action Connect / Reconnect Button --}}
                            @if ($platform['connect_url'] && $platform['connect_url'] !== '#')
                                <a
                                    href="{{ $platform['connect_url'] }}"
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-xl transition-all shadow-sm
                                        @if(!$hasActiveConnection)
                                            bg-primary text-white hover:bg-primary/90 hover:shadow-primary/20
                                        @else
                                            bg-foreground/10 hover:bg-foreground/15 text-heading-foreground border border-card-border
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
                        <p class="text-xs text-foreground/70 leading-relaxed mb-4">
                            {{ $platform['description'] }}
                        </p>

                        {{-- Connected Accounts List / Empty State --}}
                        @if ($accounts->isEmpty())
                            <div class="rounded-xl border border-dashed border-card-border bg-foreground/[0.02] p-3.5 text-center flex items-center justify-center gap-2 text-xs text-foreground/50">
                                <svg class="w-4 h-4 text-foreground/40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
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
                                    <div class="flex items-center justify-between rounded-xl border border-card-border bg-foreground/[0.04] px-3.5 py-2.5 transition-all">
                                        <div class="flex items-center gap-3 min-w-0">
                                            @if ($avatar)
                                                <img
                                                    src="{{ custom_theme_url($avatar) }}"
                                                    class="w-8 h-8 rounded-full object-cover border border-card-border shrink-0"
                                                    alt="{{ $displayName }}"
                                                />
                                            @else
                                                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary font-bold text-xs flex items-center justify-center shrink-0 border border-primary/20">
                                                    {{ strtoupper(substr($displayName, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div class="truncate">
                                                <p class="font-semibold text-xs text-heading-foreground truncate flex items-center gap-1.5">
                                                    <span>{{ $displayName }}</span>
                                                    @if($isConnected)
                                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
                                                    @else
                                                        <span class="text-[10px] text-amber-500 font-normal">(@lang('Expired'))</span>
                                                    @endif
                                                </p>
                                                @if ($username)
                                                    <p class="text-[11px] text-foreground/50 font-mono truncate" dir="ltr">{{ '@' . $username }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        @if ($disconnectUrl)
                                            <form method="POST" action="{{ $disconnectUrl }}" class="shrink-0 ml-2">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="text-xs text-red-500 hover:text-red-600 font-medium px-2.5 py-1 rounded-lg border border-red-500/20 hover:bg-red-500/10 transition-all flex items-center gap-1"
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
                </x-card>
            </div>
            @endif
        @endforeach
    </div>
</div>
@endsection