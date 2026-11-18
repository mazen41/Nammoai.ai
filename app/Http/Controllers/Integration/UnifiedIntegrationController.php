<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use App\Services\OAuth\ConnectedAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnifiedIntegrationController extends Controller
{
    public function __construct(
        private ConnectedAccountService $accountService
    ) {}

    public function index()
    {
        $user = Auth::user();

        // 1. Get all connected accounts from connected_accounts table
        $connectedAccounts = ConnectedAccount::query()
            ->where('user_id', $user->id)
            ->orderBy('platform')
            ->orderByDesc('connected_at')
            ->get()
            ->groupBy('platform');

        // 2. Also check SocialMediaPlatform table for connected social accounts and merge if not present in connected_accounts
        if (class_exists(\App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::class)) {
            $socialPlatforms = \App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::query()
                ->where('user_id', $user->id)
                ->get();

            foreach ($socialPlatforms as $sp) {
                $platformKey = (string) $sp->platform;
                $creds = $sp->credentials ?? [];

                $name = $creds['name'] ?? $creds['username'] ?? ($sp->username() !== 'John Doe' ? $sp->username() : '');
                $username = $creds['username'] ?? '';
                $avatar = $creds['picture'] ?? $creds['avatar'] ?? null;
                $platformId = $creds['platform_id'] ?? (string) $sp->id;

                if (! $connectedAccounts->has($platformKey) || $connectedAccounts->get($platformKey)->isEmpty()) {
                    $virtualAccount = new ConnectedAccount();
                    $virtualAccount->forceFill([
                        'user_id'            => $sp->user_id,
                        'platform'           => $platformKey,
                        'account_identifier' => $platformId,
                        'account_name'       => $name ?: ucfirst($platformKey) . ' Account',
                        'account_username'   => $username,
                        'account_avatar'     => $avatar,
                        'connection_status'  => $sp->isConnected() ? 'connected' : 'disconnected',
                        'connected_at'       => $sp->connected_at,
                    ]);
                    $virtualAccount->id = $sp->id;
                    $virtualAccount->disconnect_url = route('dashboard.user.integrations.disconnect', $sp->id);

                    if (! $connectedAccounts->has($platformKey)) {
                        $connectedAccounts->put($platformKey, collect([$virtualAccount]));
                    } else {
                        $connectedAccounts->get($platformKey)->push($virtualAccount);
                    }
                } else {
                    // Update account_name/avatar on existing connected_account if missing
                    foreach ($connectedAccounts->get($platformKey) as $ca) {
                        if (empty($ca->account_name) && ! empty($name)) {
                            $ca->account_name = $name;
                            if (empty($ca->account_username) && ! empty($username)) {
                                $ca->account_username = $username;
                            }
                            if (empty($ca->account_avatar) && ! empty($avatar)) {
                                $ca->account_avatar = $avatar;
                            }
                            $ca->save();
                        }
                    }
                }
            }
        }

        // 3. Check WooCommerce integration status
        $wooIntegration = \App\Models\Integration\Integration::query()->whereIn('slug', ['woocommerce', 'wordpress'])->first();
        if ($wooIntegration) {
            $userWoo = \App\Models\Integration\UserIntegration::query()
                ->where('user_id', $user->id)
                ->where('integration_id', $wooIntegration->id)
                ->first();

            if ($userWoo && ! empty($userWoo->credentials)) {
                $siteUrl = data_get($userWoo->credentials, 'domain')
                    ?: data_get($userWoo->credentials, 'url')
                    ?: data_get($userWoo->credentials, 'site_url')
                    ?: 'WooCommerce Store';

                $wooAccount = new ConnectedAccount();
                $wooAccount->forceFill([
                    'user_id'            => $user->id,
                    'platform'           => 'woocommerce',
                    'account_identifier' => (string) $userWoo->id,
                    'account_name'       => $siteUrl,
                    'account_username'   => 'Store Admin',
                    'account_avatar'     => null,
                    'connection_status'  => 'connected',
                    'connected_at'       => $userWoo->updated_at ?? now(),
                ]);
                $wooAccount->id = $userWoo->id;

                $connectedAccounts->put('woocommerce', collect([$wooAccount]));
            }
        }

        // 4. Check Salla integration status
        if (class_exists(\App\Models\SallaConnection::class)) {
            $sallaConnections = \App\Models\SallaConnection::query()
                ->where('user_id', $user->id)
                ->where('connection_status', 'connected')
                ->get();

            foreach ($sallaConnections as $sallaConn) {
                if (! $connectedAccounts->has('salla') || $connectedAccounts->get('salla')->isEmpty()) {
                    $sallaAccount = new ConnectedAccount();
                    $sallaAccount->forceFill([
                        'user_id'            => $sallaConn->user_id,
                        'platform'           => 'salla',
                        'account_identifier' => (string) ($sallaConn->salla_store_id ?? $sallaConn->id),
                        'account_name'       => $sallaConn->store_name ?: 'Salla Store',
                        'account_username'   => $sallaConn->store_domain,
                        'account_avatar'     => null,
                        'connection_status'  => 'connected',
                        'connected_at'       => $sallaConn->connected_at ?? $sallaConn->created_at,
                    ]);
                    $sallaAccount->id = $sallaConn->id;
                    $sallaAccount->disconnect_url = \Route::has('dashboard.user.salla.disconnect')
                        ? route('dashboard.user.salla.disconnect', $sallaConn->id)
                        : null;

                    if (! $connectedAccounts->has('salla')) {
                        $connectedAccounts->put('salla', collect([$sallaAccount]));
                    } else {
                        $connectedAccounts->get('salla')->push($sallaAccount);
                    }
                }
            }
        }

        // Define supported platforms and their OAuth / connect routes
        $platforms = $this->getSupportedPlatforms();

        return view('panel.user.integrations.index', [
            'connectedAccounts' => $connectedAccounts,
            'platforms'         => $platforms,
        ]);
    }

    public function disconnect(Request $request, $account)
    {
        $user = Auth::user();

        if ($account instanceof ConnectedAccount) {
            $connectedAccount = $account;
        } else {
            $connectedAccount = ConnectedAccount::where('user_id', $user->id)->find($account);
        }

        if (! $connectedAccount) {
            // Check SocialMediaPlatform
            if (class_exists(\App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::class)) {
                $sp = \App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::query()
                    ->where('user_id', $user->id)
                    ->where('id', $account)
                    ->first();
                if ($sp) {
                    $sp->delete();
                    return back()->with([
                        'type'    => 'success',
                        'message' => trans('Account disconnected successfully.'),
                    ]);
                }
            }

            return back()->with([
                'type'    => 'error',
                'message' => trans('Account not found or already disconnected.'),
            ]);
        }

        \Log::info('[CONNECTED ACCOUNT] Disconnect request received', [
            'account_id' => $connectedAccount->id,
            'platform' => $connectedAccount->platform,
            'account_identifier' => $connectedAccount->account_identifier,
            'user_id' => $user->id,
            'account_user_id' => $connectedAccount->user_id,
        ]);

        if ($connectedAccount->user_id !== $user->id) {
            \Log::error('[CONNECTED ACCOUNT] Disconnect failed: unauthorized', [
                'account_id' => $connectedAccount->id,
                'account_user_id' => $connectedAccount->user_id,
                'current_user_id' => $user->id,
            ]);
            abort(403, 'Unauthorized');
        }

        // Handle disconnect in SocialMediaPlatform table if exists
        if (class_exists(\App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::class)) {
            \App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::query()
                ->where('user_id', $user->id)
                ->where('platform', $connectedAccount->platform)
                ->delete();
        }

        $result = $this->accountService->disconnect($connectedAccount, $user);

        return back()->with([
            'type'    => 'success',
            'message' => trans('Account disconnected successfully.'),
        ]);
    }

    public function apiList(Request $request)
    {
        $user = Auth::user();
        $platform = $request->query('platform');

        $query = ConnectedAccount::query()
            ->where('user_id', $user->id)
            ->where('connection_status', 'connected');

        if ($platform) {
            $query->where('platform', $platform);
        }

        $platformLabels = [
            'facebook'        => 'Facebook',
            'instagram'       => 'Instagram',
            'x'               => 'X (Twitter)',
            'linkedin'        => 'LinkedIn',
            'tiktok'          => 'TikTok',
            'youtube'         => 'YouTube',
            'youtube_shorts'  => 'YouTube Shorts',
            'woocommerce'     => 'WooCommerce',
            'salla'           => 'Salla',
            'messenger'       => 'Facebook Messenger',
            'telegram'        => 'Telegram',
            'whatsapp_cloud'  => 'WhatsApp Business',
        ];

        return response()->json([
            'status' => 'success',
            'data'   => $query->orderByDesc('connected_at')->get()->map(fn ($a) => [
                'id'                 => $a->id,
                'platform'           => $a->platform,
                'platform_label'     => $platformLabels[$a->platform] ?? ucfirst(str_replace('_', ' ', $a->platform)),
                'account_identifier' => $a->account_identifier,
                'account_name'       => $a->getDisplayName(),
                'account_username'   => $a->account_username,
                'account_avatar'     => $a->account_avatar,
                'connection_status'  => $a->connection_status,
                'connected_at'       => $a->connected_at?->toDateTimeString(),
            ]),
        ]);
    }

    private function getSupportedPlatforms(): array
    {
        $hasSocialMedia = \App\Helpers\Classes\MarketplaceHelper::isRegistered('social-media');

        return [
            'facebook' => [
                'name'         => 'Facebook',
                'icon'         => 'facebook.svg',
                'connect_route' => 'social-media.oauth.connect.facebook',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.facebook') ? route('social-media.oauth.connect.facebook') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your Facebook pages and profiles for social posting and engagement.',
            ],
            'instagram' => [
                'name'         => 'Instagram',
                'icon'         => 'instagram.svg',
                'connect_route' => 'social-media.oauth.connect.instagram',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.instagram') ? route('social-media.oauth.connect.instagram') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect Instagram business or creator account to publish posts and view analytics.',
            ],
            'x' => [
                'name'         => 'X (Twitter)',
                'icon'         => 'x.svg',
                'connect_route' => 'social-media.oauth.connect.x',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.x') ? route('social-media.oauth.connect.x') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your X account to schedule tweets, threads, and track post performance.',
            ],
            'linkedin' => [
                'name'         => 'LinkedIn',
                'icon'         => 'linkedin.svg',
                'connect_route' => 'social-media.oauth.connect.linkedin',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.linkedin') ? route('social-media.oauth.connect.linkedin') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your LinkedIn personal profile or company pages to publish updates.',
            ],
            'tiktok' => [
                'name'         => 'TikTok',
                'icon'         => 'tiktok.svg',
                'connect_route' => 'social-media.oauth.connect.tiktok',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.tiktok') ? route('social-media.oauth.connect.tiktok') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your TikTok account to publish videos, shorts, and analyze audience growth.',
            ],
            'youtube' => [
                'name'         => 'YouTube',
                'icon'         => 'youtube.svg',
                'connect_route' => 'social-media.oauth.connect.youtube',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.youtube') ? route('social-media.oauth.connect.youtube') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect YouTube channel to manage video uploads, titles, and channel stats.',
            ],
            'youtube_shorts' => [
                'name'         => 'YouTube Shorts',
                'icon'         => 'youtube-shorts.svg',
                'connect_route' => 'social-media.oauth.connect.youtube-shorts',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.youtube-shorts') ? route('social-media.oauth.connect.youtube-shorts') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect YouTube Shorts for quick short-form video publishing.',
            ],
            'whatsapp_cloud' => [
                'name'         => 'WhatsApp Business',
                'icon'         => 'whatsapp.svg',
                'connect_route' => 'dashboard.user.integrations.whatsapp-cloud.connect',
                'connect_url'  => \Route::has('dashboard.user.integrations.whatsapp-cloud.connect') ? route('dashboard.user.integrations.whatsapp-cloud.connect') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect official WhatsApp Business Cloud API in 1-click for AI customer support and automated messaging.',
            ],
            'messenger' => [
                'name'         => 'Facebook Messenger',
                'icon'         => 'facebook-messenger.svg',
                'connect_route' => 'dashboard.user.integrations.messenger.connect',
                'connect_url'  => \Route::has('dashboard.user.integrations.messenger.connect') ? route('dashboard.user.integrations.messenger.connect') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your Facebook Pages to automate Messenger replies, post comments, and 24/7 AI customer care.',
            ],
            'woocommerce' => [
                'name'         => 'WooCommerce',
                'icon'         => 'woocommerce.svg',
                'connect_route' => 'dashboard.user.integration.index',
                'connect_url'  => \Route::has('dashboard.user.integration.index') ? route('dashboard.user.integration.index') : '#',
                'oauth'        => false,
                'enabled'      => true,
                'description'  => 'Integrate your WooCommerce store for automated product queries, orders, and AI shopping.',
            ],
            'salla' => [
                'name'         => 'Salla (سلة)',
                'icon'         => 'salla.svg',
                'connect_route' => 'dashboard.user.salla.connect',
                'connect_url'  => \Route::has('dashboard.user.salla.connect') ? route('dashboard.user.salla.connect') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your Salla store via official OAuth to sync products, inventory, and real-time order tracking.',
            ],
            'shopify' => [
                'name'         => 'Shopify',
                'icon'         => 'shopify.svg',
                'connect_route' => 'dashboard.user.chatbots.index',
                'connect_url'  => \Route::has('dashboard.user.chatbots.index') ? route('dashboard.user.chatbots.index') : '#',
                'oauth'        => false,
                'enabled'      => true,
                'description'  => 'Connect your Shopify store via Storefront/Admin API to enable product search, prices, and cart checkout.',
            ],
        ];
    }
}
