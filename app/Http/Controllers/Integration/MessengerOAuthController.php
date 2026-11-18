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

class MessengerOAuthController extends Controller
{
    public function __construct(
        private OAuthStateService $stateService,
        private ConnectedAccountService $accountService
    ) {}

    protected function getAppId(): ?string
    {
        return setting('FACEBOOK_APP_ID')
            ?: setting('INSTAGRAM_APP_ID')
            ?: config('services.facebook.client_id')
            ?: env('FACEBOOK_APP_ID')
            ?: env('INSTAGRAM_APP_ID');
    }

    protected function getAppSecret(): ?string
    {
        return setting('FACEBOOK_APP_SECRET')
            ?: setting('INSTAGRAM_APP_SECRET')
            ?: config('services.facebook.client_secret')
            ?: env('FACEBOOK_APP_SECRET')
            ?: env('INSTAGRAM_APP_SECRET');
    }

    /**
     * Exchange a short-lived user token for a 60-day long-lived user access token.
     * When long-lived user tokens query /me/accounts, Facebook returns permanent (never-expiring) page access tokens.
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
                    Log::info('Messenger OAuth: Successfully exchanged short-lived token for long-lived user token');
                    return $data['access_token'];
                }
            } else {
                Log::warning('Messenger OAuth: Long-lived token exchange returned non-success, using short token', [
                    'status' => $res->status(),
                    'body'   => $res->json(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Messenger OAuth: Exception during long-lived token exchange: ' . $e->getMessage());
        }

        return $shortToken;
    }

    public function redirect()
    {
        $user = Auth::user();

        if (!$this->getAppId() || !$this->getAppSecret()) {
            return back()->with(['type' => 'error', 'message' => trans('Facebook App ID and Secret are not configured.')]);
        }

        $state = $this->stateService->generate('messenger', $user->id);

        $params = [
            'response_type' => 'code',
            'client_id'     => $this->getAppId(),
            'redirect_uri'  => $this->redirectUri(),
            'scope'         => 'pages_messaging,pages_read_engagement,pages_show_list,pages_manage_posts',
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
                ->with(['type' => 'error', 'message' => trans('Facebook authorization was denied.')]);
        }

        $code  = $request->input('code');
        $state = $request->input('state');

        if (!$code || !$state) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Invalid callback parameters.')]);
        }

        if (!$this->stateService->validate('messenger', $user->id, $state)) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Invalid or expired OAuth state. Please try again.')]);
        }

        try {
            // Exchange code for user access token
            $tokenRes = Http::asForm()->post('https://graph.facebook.com/v18.0/oauth/access_token', [
                'client_id'     => $this->getAppId(),
                'client_secret' => $this->getAppSecret(),
                'code'          => $code,
                'redirect_uri'  => $this->redirectUri(),
            ])->throw()->json();

            $shortToken = $tokenRes['access_token'];

            // Exchange for 60-day long-lived token so page tokens returned from /me/accounts never expire
            $userToken = $this->exchangeForLongLivedToken($shortToken);

            // Get all Facebook Pages the user manages
            $pagesRes = Http::withToken($userToken)
                ->get('https://graph.facebook.com/v18.0/me/accounts', [
                    'fields' => 'id,name,access_token,category,picture',
                    'limit'  => 100,
                ])->throw()->json();

            $pages = $pagesRes['data'] ?? [];

            if (empty($pages)) {
                throw new Exception('No Facebook Pages found. You must have at least one Facebook Page to use Messenger.');
            }

            // Cache candidate pages for selection (15 minutes TTL)
            $pickToken = bin2hex(random_bytes(24));
            Cache::put("messenger_pick_{$pickToken}", [
                'user_id'        => $user->id,
                'pages'          => $pages,
                'granted_scopes' => $request->input('granted_scopes', []),
            ], 900);

            return redirect()->route('dashboard.user.integrations.messenger.select', [
                'pick_token' => $pickToken,
            ]);

        } catch (Exception $e) {
            Log::error('Messenger OAuth callback failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);

            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Failed to connect Facebook Page: ') . $e->getMessage()]);
        }
    }

    /**
     * Show page selection view allowing the user to select which Facebook Page(s) to connect.
     */
    public function select(Request $request)
    {
        $user      = Auth::user();
        $pickToken = $request->query('pick_token');

        if (!$pickToken) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Invalid selection session.')]);
        }

        $sessionData = Cache::get("messenger_pick_{$pickToken}");
        if (!$sessionData || ($sessionData['user_id'] ?? null) !== $user->id) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Selection session expired. Please connect again.')]);
        }

        $pages = $sessionData['pages'] ?? [];

        $alreadyConnectedIds = ConnectedAccount::query()
            ->where('user_id', $user->id)
            ->where('platform', 'messenger')
            ->where('connection_status', 'connected')
            ->pluck('account_identifier')
            ->toArray();

        return view('panel.user.integrations.messenger.select', [
            'pages'               => $pages,
            'pick_token'          => $pickToken,
            'alreadyConnectedIds' => $alreadyConnectedIds,
        ]);
    }

    /**
     * Store selected Facebook Page(s) into connected_accounts and subscribe to webhooks.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'pick_token' => 'required|string',
            'page_ids'   => 'required|array|min:1',
            'page_ids.*' => 'required|string',
        ]);

        $pickToken   = $request->input('pick_token');
        $selectedIds = $request->input('page_ids', []);

        $sessionData = Cache::get("messenger_pick_{$pickToken}");
        if (!$sessionData || ($sessionData['user_id'] ?? null) !== $user->id) {
            return redirect()->route('dashboard.user.integrations.index')
                ->with(['type' => 'error', 'message' => trans('Selection session expired. Please connect again.')]);
        }

        $pages     = $sessionData['pages'] ?? [];
        $pagesById = collect($pages)->keyBy('id');

        $connectedCount = 0;

        foreach ($selectedIds as $pageId) {
            $page = $pagesById->get($pageId);
            if (!$page || empty($page['access_token'])) {
                continue;
            }

            // Save to connected_accounts
            $this->accountService->createOrUpdate(
                user:              $user,
                platform:          'messenger',
                accountIdentifier: $page['id'],
                accessToken:       $page['access_token'],
                accountData: [
                    'name'     => $page['name'],
                    'username' => null,
                    'avatar'   => data_get($page, 'picture.data.url'),
                    'metadata' => [
                        'page_id'             => $page['id'],
                        'page_name'           => $page['name'],
                        'category'            => $page['category'] ?? null,
                        'granted_permissions' => $sessionData['granted_scopes'] ?? [],
                    ],
                ]
            );

            // Also update legacy SocialMediaPlatform if exists
            $legacyPlatform = \App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::where('platform', 'facebook')
                ->whereJsonContains('credentials->platform_id', $page['id'])
                ->first();

            if ($legacyPlatform) {
                $credentials = $legacyPlatform->credentials;
                if (is_string($credentials)) {
                    $credentials = json_decode($credentials, true);
                }
                $credentials['access_token'] = $page['access_token'];
                $legacyPlatform->credentials = $credentials;
                $legacyPlatform->save();
            }

            // Subscribe the Page to our App's webhooks
            try {
                $subscribeRes = Http::withToken($page['access_token'])
                    ->post("https://graph.facebook.com/v18.0/{$page['id']}/subscribed_apps", [
                        'subscribed_fields' => 'feed,messages,messaging_postbacks,messaging_optins',
                    ]);

                if ($subscribeRes->successful()) {
                    Log::info('Messenger OAuth: page subscribed to app webhooks', [
                        'page_id'   => $page['id'],
                        'page_name' => $page['name'],
                    ]);
                } else {
                    Log::warning('Messenger OAuth: webhook subscription returned non-success', [
                        'page_id'   => $page['id'],
                        'page_name' => $page['name'],
                        'status'    => $subscribeRes->status(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Messenger OAuth: webhook subscription error for page ' . $page['id'] . ': ' . $e->getMessage());
            }

            $connectedCount++;
        }

        Cache::forget("messenger_pick_{$pickToken}");

        $message = $connectedCount === 1
            ? trans('Facebook Page connected successfully.')
            : trans(':count Facebook Pages connected successfully.', ['count' => $connectedCount]);

        return redirect()->route('dashboard.user.integrations.index')
            ->with(['type' => 'success', 'message' => $message]);
    }

    private function redirectUri(): string
    {
        return route('dashboard.user.integrations.messenger.callback');
    }

    /**
     * One-time fix: subscribe all existing connected Messenger pages to the App's webhooks.
     * Visit: GET /dashboard/user/integrations/messenger/resubscribe
     */
    public function resubscribeAll()
    {
        $user = Auth::user();

        $accounts = ConnectedAccount::query()
            ->where('user_id', $user->id)
            ->where('platform', 'messenger')
            ->where('connection_status', 'connected')
            ->get();

        if ($accounts->isEmpty()) {
            return back()->with(['type' => 'error', 'message' => 'No connected Messenger accounts found.']);
        }

        $results = [];

        foreach ($accounts as $account) {
            $pageId    = $account->account_identifier;
            $pageToken = $account->access_token;

            if (!$pageToken) {
                $results[] = "Page {$pageId}: SKIPPED (no token)";
                continue;
            }

            $res = Http::withToken($pageToken)
                ->post("https://graph.facebook.com/v18.0/{$pageId}/subscribed_apps", [
                    'subscribed_fields' => 'feed,messages,messaging_postbacks,messaging_optins',
                ]);

            if ($res->successful()) {
                Log::info('resubscribeAll: page subscribed', ['page_id' => $pageId, 'result' => $res->json()]);
                $results[] = "Page {$pageId} ({$account->account_name}): SUCCESS " . json_encode($res->json());
            } else {
                Log::error('resubscribeAll: page subscription failed', ['page_id' => $pageId, 'status' => $res->status(), 'body' => $res->json()]);
                $results[] = "Page {$pageId} ({$account->account_name}): FAILED {$res->status()} " . $res->body();
            }
        }

        return response('<pre>' . implode("\n", $results) . '</pre>')
            ->header('Content-Type', 'text/html');
    }
}
