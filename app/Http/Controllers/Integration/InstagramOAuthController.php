<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use App\Services\OAuth\ConnectedAccountService;
use App\Services\OAuth\OAuthStateService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramOAuthController extends Controller
{
    public function __construct(
        private OAuthStateService $stateService,
        private ConnectedAccountService $accountService
    ) {}

    protected function getAppId(): ?string
    {
        return setting('INSTAGRAM_APP_ID')
            ?: setting('FACEBOOK_APP_ID')
            ?: config('services.facebook.client_id')
            ?: env('INSTAGRAM_APP_ID')
            ?: env('FACEBOOK_APP_ID');
    }

    protected function getAppSecret(): ?string
    {
        return setting('INSTAGRAM_APP_SECRET')
            ?: setting('FACEBOOK_APP_SECRET')
            ?: config('services.facebook.client_secret')
            ?: env('INSTAGRAM_APP_SECRET')
            ?: env('FACEBOOK_APP_SECRET');
    }

    /**
     * Exchange short-lived token for long-lived user access token (60-day).
     */
    protected function exchangeForLongLivedToken(string $shortToken): string
    {
        try {
            $appId     = $this->getAppId();
            $appSecret = $this->getAppSecret();

            if (!$appId || !$appSecret) {
                return $shortToken;
            }

            $res = Http::asForm()->get('https://graph.facebook.com/v18.0/oauth/access_token', [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $appId,
                'client_secret'     => $appSecret,
                'fb_exchange_token' => $shortToken,
            ]);

            if ($res->successful()) {
                $data = $res->json();
                if (!empty($data['access_token'])) {
                    Log::info('Instagram OAuth: Successfully exchanged short-lived token for long-lived user token');
                    return $data['access_token'];
                }
            } else {
                Log::warning('Instagram OAuth: Long-lived token exchange returned non-success, using short token', [
                    'status' => $res->status(),
                    'body'   => $res->json(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Instagram OAuth: Exception during long-lived token exchange: ' . $e->getMessage());
        }

        return $shortToken;
    }

    public function redirect()
    {
        $user = Auth::user();

        if (!$this->getAppId() || !$this->getAppSecret()) {
            return back()->with(['type' => 'error', 'message' => trans('Instagram App ID and Secret are not configured.')]);
        }

        $state = $this->stateService->generate('instagram', $user->id);

        $params = [
            'response_type' => 'code',
            'client_id'     => $this->getAppId(),
            'redirect_uri'  => $this->redirectUri(),
            'scope'         => 'instagram_basic,instagram_manage_messages,pages_read_engagement,pages_show_list,pages_manage_posts',
            'state'         => $state,
        ];

        $url = 'https://www.facebook.com/dialog/oauth?' . http_build_query($params);

        return redirect()->away($url);
    }

    public function callback(Request $request)
    {
        $user = Auth::user();

        if ($request->has('error')) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Instagram authorization was denied.')]);
        }

        $code  = $request->input('code');
        $state = $request->input('state');

        if (!$code || !$state) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Invalid callback parameters.')]);
        }

        if (!$this->stateService->validate('instagram', $user->id, $state)) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Invalid or expired OAuth state. Please try again.')]);
        }

        try {
            // Exchange code for user token
            $tokenRes = Http::asForm()->post('https://graph.facebook.com/v18.0/oauth/access_token', [
                'client_id'     => $this->getAppId(),
                'client_secret' => $this->getAppSecret(),
                'code'          => $code,
                'redirect_uri'  => $this->redirectUri(),
            ])->throw()->json();

            $shortToken = $tokenRes['access_token'];

            // Exchange for 60-day long-lived user token
            $userToken = $this->exchangeForLongLivedToken($shortToken);

            // Get pages and their connected Instagram Business accounts
            $pagesRes = Http::withToken($userToken)
                ->get('https://graph.facebook.com/v18.0/me/accounts', [
                    'fields' => 'id,name,access_token,connected_instagram_account{id,name,username,profile_picture_url}',
                    'limit'  => 100,
                ])->throw()->json();

            $pages = $pagesRes['data'] ?? [];
            $igAccounts = [];

            foreach ($pages as $page) {
                if (!empty($page['connected_instagram_account']['id'])) {
                    $ig = $page['connected_instagram_account'];
                    $igAccounts[] = [
                        'page_id'             => $page['id'],
                        'page_name'           => $page['name'],
                        'page_access_token'   => $page['access_token'],
                        'ig_id'               => $ig['id'],
                        'name'                => $ig['name'] ?? $ig['username'] ?? $page['name'],
                        'username'            => $ig['username'] ?? null,
                        'avatar'              => $ig['profile_picture_url'] ?? null,
                    ];
                }
            }

            if (empty($igAccounts)) {
                throw new Exception(trans('No Instagram Business account found connected to your Facebook Pages. Please ensure your Instagram account is a Professional account and linked to a Facebook Page.'));
            }

            // Cache candidate Instagram accounts for selection (15 minutes TTL)
            $pickToken = bin2hex(random_bytes(24));
            Cache::put("instagram_pick_{$pickToken}", [
                'user_id'     => $user->id,
                'ig_accounts' => $igAccounts,
            ], 900);

            return redirect()->route('dashboard.user.integrations.instagram.select', [
                'pick_token' => $pickToken,
            ]);

        } catch (Exception $e) {
            Log::error('Instagram OAuth callback failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Failed to connect Instagram: ') . $e->getMessage()]);
        }
    }

    /**
     * Show account selection view allowing the user to select which Instagram account(s) to connect.
     */
    public function select(Request $request)
    {
        $user      = Auth::user();
        $pickToken = $request->query('pick_token');

        if (!$pickToken) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Invalid selection session.')]);
        }

        $sessionData = Cache::get("instagram_pick_{$pickToken}");
        if (!$sessionData || ($sessionData['user_id'] ?? null) !== $user->id) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Selection session expired. Please connect again.')]);
        }

        $igAccounts = $sessionData['ig_accounts'] ?? [];

        $alreadyConnectedIds = ConnectedAccount::query()
            ->where('user_id', $user->id)
            ->where('platform', 'instagram')
            ->where('connection_status', 'connected')
            ->pluck('account_identifier')
            ->toArray();

        return view('panel.user.integrations.instagram.select', [
            'accounts'            => $igAccounts,
            'pick_token'          => $pickToken,
            'alreadyConnectedIds' => $alreadyConnectedIds,
        ]);
    }

    /**
     * Store selected Instagram account(s) into connected_accounts and subscribe page webhooks.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'pick_token' => 'required|string',
            'ig_ids'     => 'required|array|min:1',
            'ig_ids.*'   => 'required|string',
        ]);

        $pickToken   = $request->input('pick_token');
        $selectedIds = $request->input('ig_ids', []);

        $sessionData = Cache::get("instagram_pick_{$pickToken}");
        if (!$sessionData || ($sessionData['user_id'] ?? null) !== $user->id) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Selection session expired. Please connect again.')]);
        }

        $accounts     = $sessionData['ig_accounts'] ?? [];
        $accountsById = collect($accounts)->keyBy('ig_id');

        $connectedCount = 0;

        foreach ($selectedIds as $igId) {
            $item = $accountsById->get($igId);
            if (!$item || empty($item['page_access_token'])) {
                continue;
            }

            // Save to connected_accounts
            $this->accountService->createOrUpdate(
                user:              $user,
                platform:          'instagram',
                accountIdentifier: $item['ig_id'],
                accessToken:       $item['page_access_token'],
                accountData: [
                    'name'     => $item['name'],
                    'username' => $item['username'],
                    'avatar'   => $item['avatar'],
                    'metadata' => [
                        'page_id'   => $item['page_id'],
                        'page_name' => $item['page_name'],
                        'ig_id'     => $item['ig_id'],
                        'ig_name'   => $item['name'],
                    ],
                ]
            );

            // Subscribe the parent Page to webhooks for Instagram messages
            try {
                $subscribeRes = Http::withToken($item['page_access_token'])
                    ->post("https://graph.facebook.com/v18.0/{$item['page_id']}/subscribed_apps", [
                        'subscribed_fields' => 'messages,messaging_postbacks,messaging_optins',
                    ]);

                Log::info('Instagram OAuth: parent page subscribed to webhooks', [
                    'page_id' => $item['page_id'],
                    'ig_id'   => $item['ig_id'],
                    'success' => $subscribeRes->successful(),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Instagram OAuth: subscription error for page ' . $item['page_id'] . ': ' . $e->getMessage());
            }

            $connectedCount++;
        }

        Cache::forget("instagram_pick_{$pickToken}");

        $message = $connectedCount === 1
            ? trans('Instagram account connected successfully.')
            : trans(':count Instagram accounts connected successfully.', ['count' => $connectedCount]);

        return redirect()->route('dashboard.user.integrations.index')
            ->with(['type' => 'success', 'message' => $message]);
    }

    private function redirectUri(): string
    {
        return route('dashboard.user.integrations.instagram.callback');
    }
}
