@extends('panel.layout.app', ['disable_tblr' => true])
@section('title', __('Connect WhatsApp Business'))
@section('titlebar_subtitle', __('Official Meta WhatsApp Cloud API — 1-Click Connection'))
@section('titlebar_back', route('dashboard.user.integrations.index'))
@section('titlebar_actions', '')

@section('content')
<div class="py-10">
    <div class="max-w-xl mx-auto">
        <x-card class="p-8">

            {{-- Header --}}
            <div class="flex items-center gap-3 mb-6">
                <div class="size-12 rounded-2xl bg-green-100 dark:bg-green-900/30 flex items-center justify-center flex-shrink-0 text-green-600 dark:text-green-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-7" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.549 4.103 1.508 5.828L0 24l6.345-1.477A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.81 9.81 0 01-4.994-1.368l-.358-.214-3.767.877.914-3.682-.234-.377A9.778 9.778 0 012.182 12C2.182 6.575 6.575 2.182 12 2.182S21.818 6.575 21.818 12 17.425 21.818 12 21.818z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">@lang('WhatsApp Business Cloud')</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">@lang('Official Meta Cloud API — Zero-ban risk, always online')</p>
                </div>
            </div>

            {{-- Flash messages --}}
            @if (session('type') === 'error')
                <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                    {{ session('message') }}
                </div>
            @endif

            <div id="statusAlert" class="hidden mb-5 rounded-xl border p-4 text-xs transition-all"></div>

            {{-- 3-Step Preview --}}
            <div class="mb-7 rounded-2xl border border-gray-100 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/40 p-4 space-y-3">
                <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wider block">@lang('How it works (45 seconds)')</span>
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="rounded-xl border border-gray-200/60 dark:border-gray-700/60 bg-white dark:bg-gray-800 p-2.5 shadow-2xs">
                        <span class="size-5 rounded-full bg-blue-100 text-blue-700 font-bold inline-flex items-center justify-center text-[10px] mb-1">1</span>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">@lang('Click Connect')</p>
                        <p class="text-[10px] text-gray-400">@lang('Opens Meta Popup')</p>
                    </div>
                    <div class="rounded-xl border border-gray-200/60 dark:border-gray-700/60 bg-white dark:bg-gray-800 p-2.5 shadow-2xs">
                        <span class="size-5 rounded-full bg-blue-100 text-blue-700 font-bold inline-flex items-center justify-center text-[10px] mb-1">2</span>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">@lang('Verify Phone')</p>
                        <p class="text-[10px] text-gray-400">@lang('6-digit SMS OTP')</p>
                    </div>
                    <div class="rounded-xl border border-gray-200/60 dark:border-gray-700/60 bg-white dark:bg-gray-800 p-2.5 shadow-2xs">
                        <span class="size-5 rounded-full bg-green-100 text-green-700 font-bold inline-flex items-center justify-center text-[10px] mb-1">3</span>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">@lang('All Done!')</p>
                        <p class="text-[10px] text-gray-400">@lang('Bot Ready 24/7')</p>
                    </div>
                </div>
            </div>

            {{-- 1-Click Connect Button --}}
            <div class="space-y-3">
                <button type="button"
                        id="fbConnectBtn"
                        onclick="launchWhatsAppSignup()"
                        class="w-full flex items-center justify-center gap-3 rounded-xl bg-[#25D366] hover:bg-[#1ebe5d] active:scale-[0.99]
                               text-white font-semibold text-sm px-6 py-4 transition-all duration-150 shadow-md hover:shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-5 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.117.549 4.103 1.508 5.828L0 24l6.345-1.477A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.81 9.81 0 01-4.994-1.368l-.358-.214-3.767.877.914-3.682-.234-.377A9.778 9.778 0 012.182 12C2.182 6.575 6.575 2.182 12 2.182S21.818 6.575 21.818 12 17.425 21.818 12 21.818z"/>
                    </svg>
                    <span id="btnText">@lang('Connect WhatsApp in 1-Click')</span>
                </button>

                <p class="text-center text-[11px] text-gray-400 dark:text-gray-500">
                    🔒 @lang('Official Meta Embedded Signup. Zero technical setup required.')
                </p>
            </div>

            {{-- Manual / Direct Setup Toggle --}}
            <div class="mt-8 pt-5 border-t border-gray-100 dark:border-gray-800">
                <button type="button"
                        onclick="document.getElementById('directForm').classList.toggle('hidden')"
                        class="text-xs font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 flex items-center justify-between w-full">
                    <span>@lang('Have an existing Phone Number ID & Token? Connect manually')</span>
                    <span class="text-xs">▾</span>
                </button>

                <form id="directForm" method="POST" action="{{ route('dashboard.user.integrations.whatsapp-cloud.direct-connect') }}" class="hidden mt-4 space-y-3 pt-3 border-t border-dashed border-gray-200 dark:border-gray-700">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('Phone Number ID') *</label>
                        <input type="text" name="phone_number_id" required placeholder="e.g. 109823485729384"
                               class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('WhatsApp Business Account (WABA) ID')</label>
                        <input type="text" name="waba_id" placeholder="e.g. 982347192837412"
                               class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-green-500 focus:ring-1 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">@lang('Permanent Access Token') *</label>
                        <input type="password" name="access_token" required placeholder="EAAG..."
                               class="w-full text-xs rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-gray-900 dark:text-gray-100 focus:border-green-500 focus:ring-1 focus:ring-green-500 font-mono">
                    </div>
                    <button type="submit"
                            class="w-full mt-2 rounded-lg bg-gray-900 dark:bg-gray-100 text-white dark:text-gray-900 text-xs font-semibold px-4 py-2.5 hover:opacity-90">
                        @lang('Verify & Save Credentials')
                    </button>
                </form>
            </div>

            {{-- Back Link --}}
            <div class="mt-6 pt-4 text-center">
                <a href="{{ route('dashboard.user.integrations.index') }}"
                   class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                    ← @lang('Back to Integrations')
                </a>
            </div>

        </x-card>
    </div>
</div>

{{-- Load Facebook JavaScript SDK --}}
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>

<script>
    let fbSessionData = {};

    window.fbAsyncInit = function() {
        FB.init({
            appId      : '{{ $appId }}',
            cookie     : true,
            xfbml      : true,
            version    : 'v18.0'
        });
    };

    // Listen to message events from Meta WhatsApp Embedded Signup popup
    window.addEventListener('message', (event) => {
        if (event.origin !== "https://www.facebook.com" && event.origin !== "https://web.facebook.com") {
            return;
        }
        try {
            const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
            if (data.type === 'WA_EMBEDDED_SIGNUP') {
                if (data.event === 'FINISH') {
                    fbSessionData = data.data || {};
                    console.log('WhatsApp Signup finished with data:', fbSessionData);
                } else if (data.event === 'CANCEL') {
                    console.log('WhatsApp Signup cancelled by user');
                }
            }
        } catch (e) {}
    });

    function showStatus(message, isError = false) {
        const el = document.getElementById('statusAlert');
        if (!el) return;
        el.className = isError
            ? 'mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-xs text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400 block'
            : 'mb-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-xs text-blue-700 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400 block';
        el.textContent = message;
    }

    function launchWhatsAppSignup() {
        const configId = '{{ $configId }}';
        const appId    = '{{ $appId }}';

        if (!appId) {
            showStatus("{{ __('Meta App ID is not configured.') }}", true);
            return;
        }

        // If configId is not configured, fallback to direct input or redirect
        if (!configId) {
            document.getElementById('directForm').classList.remove('hidden');
            showStatus("{{ __('Embedded Signup Config ID is not configured. Please use manual setup below.') }}", true);
            return;
        }

        const btn = document.getElementById('fbConnectBtn');
        const btnText = document.getElementById('btnText');
        btnText.textContent = "{{ __('Waiting for Meta authorization...') }}";
        btn.classList.add('opacity-75', 'pointer-events-none');

        // Launch official FB.login with WhatsApp Embedded Signup configuration
        FB.login(function(response) {
            if (response.authResponse && response.authResponse.code) {
                btnText.textContent = "{{ __('Connecting your WhatsApp account...') }}";
                showStatus("{{ __('Authorization received. Connecting your account with Meta...') }}");

                fetch("{{ route('dashboard.user.integrations.whatsapp-cloud.complete') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        code: response.authResponse.code,
                        waba_id: fbSessionData.waba_id || null,
                        phone_number_id: fbSessionData.phone_number_id || null,
                        state: '{{ $state }}'
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        btnText.textContent = "{{ __('Connected!') }}";
                        showStatus("{{ __('WhatsApp connected successfully! Redirecting...') }}");
                        window.location.href = data.redirect_url || "{{ route('dashboard.user.integrations.index') }}";
                    } else {
                        btnText.textContent = "{{ __('Connect WhatsApp in 1-Click') }}";
                        btn.classList.remove('opacity-75', 'pointer-events-none');
                        showStatus(data.message || "{{ __('Failed to connect WhatsApp account.') }}", true);
                    }
                })
                .catch(err => {
                    btnText.textContent = "{{ __('Connect WhatsApp in 1-Click') }}";
                    btn.classList.remove('opacity-75', 'pointer-events-none');
                    showStatus("{{ __('Error connecting to server: ') }}" + err.message, true);
                });
            } else {
                btnText.textContent = "{{ __('Connect WhatsApp in 1-Click') }}";
                btn.classList.remove('opacity-75', 'pointer-events-none');
                showStatus("{{ __('Meta authorization was cancelled or closed.') }}", true);
            }
        }, {
            config_id: configId,
            response_type: 'code',
            override_default_response_type: true,
            extras: {
                feature: 'whatsapp_embedded_signup',
                version: 2,
                sessionInfoVersion: 3
            }
        });
    }
</script>
@endsection
