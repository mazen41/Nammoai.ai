<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use App\Services\OAuth\ConnectedAccountService;
use App\Services\OAuth\OAuthStateService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp Business Cloud API — Meta Embedded Signup flow.
 *
 * Supported flows:
 *   1. 1-Click "Log in with Facebook" via Meta JavaScript SDK Popup (Fastest & Zero Friction)
 *   2. Direct In-App Setup (Phone Number ID + Permanent Token)
 *   3. Fallback URL Redirect
 */
class WhatsAppCloudOAuthController extends Controller
{
    public function __construct(
        private OAuthStateService $stateService,
        private ConnectedAccountService $accountService
    ) {}

    protected function getAppId(): ?string
    {
        return config('services.meta.whatsapp.app_id')
            ?: setting('WHATSAPP_FACEBOOK_APP_ID')
            ?: setting('INSTAGRAM_APP_ID')
            ?: env('WHATSAPP_FACEBOOK_APP_ID')
            ?: env('FACEBOOK_APP_ID');
    }

    protected function getAppSecret(): ?string
    {
        return config('services.meta.whatsapp.app_secret')
            ?: setting('WHATSAPP_FACEBOOK_APP_SECRET')
            ?: setting('INSTAGRAM_APP_SECRET')
            ?: env('WHATSAPP_FACEBOOK_APP_SECRET')
            ?: env('FACEBOOK_APP_SECRET');
    }

    protected function getConfigId(): ?string
    {
        return config('services.meta.whatsapp.config_id')
            ?: setting('WHATSAPP_FACEBOOK_CONFIG_ID')
            ?: env('WHATSAPP_FACEBOOK_CONFIG_ID');
    }

    /**
     * Show the 1-Click WhatsApp Connection page.
     */
    public function connect(Request $request)
    {
        $user     = Auth::user();
        $appId    = $this->getAppId();
        $configId = $this->getConfigId();
        $state    = $this->stateService->generate('whatsapp_cloud', $user->id);

        return view('panel.user.integrations.whatsapp-cloud.connect', [
            'appId'    => $appId,
            'configId' => $configId,
            'state'    => $state,
        ]);
    }

    /**
     * Process 1-Click Meta Embedded Signup callback from JavaScript SDK popup.
     */
    public function complete(Request $request): JsonResponse
    {
        $user = Auth::user();

        $code          = $request->input('code');
        $wabaId        = $request->input('waba_id');
        $phoneNumberId = $request->input('phone_number_id');
        $state         = $request->input('state');

        if (!$code) {
            return response()->json([
                'success' => false,
                'message' => trans('No authorization code received from Meta.'),
            ], 400);
        }

        if ($state && !$this->stateService->validate('whatsapp_cloud', $user->id, $state)) {
            return response()->json([
                'success' => false,
                'message' => trans('Invalid or expired OAuth state. Please try again.'),
            ], 403);
        }

        try {
            $graphVersion = config('services.meta.graph_version', 'v18.0');
            $appId        = $this->getAppId();
            $appSecret    = $this->getAppSecret();

            // Exchange code for access token
            $tokenRes = Http::asForm()->post("https://graph.facebook.com/{$graphVersion}/oauth/access_token", [
                'client_id'     => $appId,
                'client_secret' => $appSecret,
                'code'          => $code,
            ])->throw()->json();

            $accessToken = $tokenRes['access_token'];

            // If WABA ID is missing, attempt to discover it via debug_token
            if (empty($wabaId)) {
                $debugRes = Http::get("https://graph.facebook.com/{$graphVersion}/debug_token", [
                    'input_token'  => $accessToken,
                    'access_token' => "{$appId}|{$appSecret}",
                ])->json();

                $targetIds = data_get($debugRes, 'data.granular_scopes.0.target_ids', []);
                if (!empty($targetIds[0])) {
                    $wabaId = $targetIds[0];
                }
            }

            // If phone number ID is missing, get phone numbers associated with the WABA
            if (empty($phoneNumberId) && !empty($wabaId)) {
                $phoneListRes = Http::withToken($accessToken)
                    ->get("https://graph.facebook.com/{$graphVersion}/{$wabaId}/phone_numbers", [
                        'fields' => 'id,display_phone_number,verified_name,quality_rating',
                    ])->json();

                $phoneNumbers = $phoneListRes['data'] ?? [];
                if (!empty($phoneNumbers)) {
                    $phoneNumberId = $phoneNumbers[0]['id'];
                }
            }

            if (empty($phoneNumberId)) {
                return response()->json([
                    'success' => false,
                    'message' => trans('Could not retrieve a WhatsApp phone number. Please ensure your number is verified in Meta Business Manager.'),
                ], 400);
            }

            // Fetch phone number details
            $phoneRes = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}", [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating',
                ])->throw()->json();

            $displayPhoneNumber = $phoneRes['display_phone_number'] ?? null;
            $displayLabel       = $phoneRes['verified_name'] ?? $displayPhoneNumber ?? 'WhatsApp Business';

            // Subscribe phone number to webhooks
            try {
                Http::withToken($accessToken)
                    ->post("https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}/subscribed_apps");

                Log::info('WhatsApp Cloud: Webhook subscribed successfully for ' . $phoneNumberId);
            } catch (\Throwable $e) {
                Log::warning('WhatsApp Cloud: Webhook subscription notice: ' . $e->getMessage());
            }

            // Save to connected_accounts
            $this->accountService->createOrUpdate(
                user:              $user,
                platform:          'whatsapp_cloud',
                accountIdentifier: $phoneNumberId,
                accessToken:       $accessToken,
                accountData: [
                    'name'     => $displayLabel,
                    'username' => $displayPhoneNumber,
                    'avatar'   => null,
                    'metadata' => [
                        'phone_number_id'              => $phoneNumberId,
                        'whatsapp_business_account_id' => $wabaId,
                        'phone_number'                 => $displayPhoneNumber,
                        'display_name'                 => $displayLabel,
                        'quality_rating'               => $phoneRes['quality_rating'] ?? null,
                    ],
                ]
            );

            Log::info('WhatsApp Cloud: Account connected successfully', [
                'user_id'         => $user->id,
                'phone_number_id' => $phoneNumberId,
                'phone_number'    => $displayPhoneNumber,
            ]);

            return response()->json([
                'success'      => true,
                'message'      => trans('WhatsApp Business account connected successfully!'),
                'redirect_url' => route('dashboard.user.integrations.index'),
            ]);

        } catch (Exception $e) {
            Log::error('WhatsApp Cloud completion failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => trans('Failed to complete WhatsApp connection: ') . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Direct In-Dashboard Setup (for users with existing Phone Number ID & Token).
     */
    public function directConnect(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'phone_number_id' => 'required|string',
            'access_token'    => 'required|string',
            'waba_id'         => 'nullable|string',
            'phone_number'    => 'nullable|string',
        ]);

        $phoneNumberId = trim($request->input('phone_number_id'));
        $accessToken   = trim($request->input('access_token'));
        $wabaId        = trim((string) $request->input('waba_id'));

        try {
            $graphVersion = config('services.meta.graph_version', 'v18.0');

            // Verify credentials with Meta
            $phoneRes = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}", [
                    'fields' => 'id,display_phone_number,verified_name,quality_rating',
                ]);

            if (!$phoneRes->successful()) {
                $errorData = $phoneRes->json();
                $errorMsg  = $errorData['error']['message'] ?? 'Could not verify WhatsApp Phone Number ID or Access Token with Meta.';
                return back()->with(['type' => 'error', 'message' => $errorMsg]);
            }

            $phoneData          = $phoneRes->json();
            $displayPhoneNumber = $phoneData['display_phone_number'] ?? $request->input('phone_number');
            $displayLabel       = $phoneData['verified_name'] ?? $displayPhoneNumber ?? 'WhatsApp Business';

            // Subscribe to webhooks
            try {
                Http::withToken($accessToken)
                    ->post("https://graph.facebook.com/{$graphVersion}/{$phoneNumberId}/subscribed_apps");
            } catch (\Throwable $e) {
                Log::warning('WhatsApp Cloud Direct: Webhook subscription notice: ' . $e->getMessage());
            }

            // Save to connected_accounts
            $this->accountService->createOrUpdate(
                user:              $user,
                platform:          'whatsapp_cloud',
                accountIdentifier: $phoneNumberId,
                accessToken:       $accessToken,
                accountData: [
                    'name'     => $displayLabel,
                    'username' => $displayPhoneNumber,
                    'avatar'   => null,
                    'metadata' => [
                        'phone_number_id'              => $phoneNumberId,
                        'whatsapp_business_account_id' => $wabaId,
                        'phone_number'                 => $displayPhoneNumber,
                        'display_name'                 => $displayLabel,
                        'quality_rating'               => $phoneData['quality_rating'] ?? null,
                    ],
                ]
            );

            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'success', 'message' => trans('WhatsApp Business account connected successfully.')]);

        } catch (Exception $e) {
            Log::error('WhatsApp Cloud Direct Connect failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return back()->with(['type' => 'error', 'message' => trans('Connection failed: ') . $e->getMessage()]);
        }
    }

    /**
     * Legacy URL redirect callback (backward compatibility).
     */
    public function callback(Request $request)
    {
        $user = Auth::user();

        if ($request->has('error')) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('WhatsApp authorization was cancelled or failed.')]);
        }

        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('No authorization code received.')]);
        }

        // Delegate to complete method internally
        $req = new Request([
            'code'            => $code,
            'waba_id'         => $request->input('wa_id') ?? $request->input('waba_id'),
            'phone_number_id' => $request->input('phone_number_id'),
            'state'           => $request->input('state'),
        ]);

        $res = $this->complete($req);
        $data = $res->getData(true);

        if (!empty($data['success'])) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'success', 'message' => trans('WhatsApp Business account connected successfully.')]);
        }

        return redirect()->route('dashboard.user.integrations.index')
            ->with(['type' => 'error', 'message' => $data['message'] ?? trans('Failed to connect WhatsApp account.')]);
    }
}
