<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Models\ConnectedAccount;
use App\Services\OAuth\ConnectedAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnifiedIntegrationController extends Controller
{
    public function __construct(
        private ConnectedAccountService $accountService
    ) {}

    public function index()
    {
        $user = Auth::user();

        // 1. Get all connected accounts with active connection
        $connectedAccounts = ConnectedAccount::query()
            ->where('user_id', $user->id)
            ->where('connection_status', 'connected')
            ->orderBy('platform')
            ->orderByDesc('connected_at')
            ->get()
            ->groupBy('platform');

        // Merge any accounts from 'facebook' into 'messenger' so Facebook & Messenger are unified
        if ($connectedAccounts->has('facebook')) {
            $fbAccounts = $connectedAccounts->get('facebook');
            if ($connectedAccounts->has('messenger')) {
                $connectedAccounts->put('messenger', $connectedAccounts->get('messenger')->merge($fbAccounts));
            } else {
                $connectedAccounts->put('messenger', $fbAccounts);
            }
        }

        // 2. Also check SocialMediaPlatform table for connected social accounts and merge if not present in connected_accounts
        if (class_exists(\App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::class)) {
            $socialPlatforms = \App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::query()
                ->where('user_id', $user->id)
                ->get();

            foreach ($socialPlatforms as $sp) {
                $platformKey = (string) $sp->platform;
                if ($platformKey === 'facebook') {
                    $platformKey = 'messenger';
                }

                $creds = $sp->credentials ?? [];
                $name = $creds['name'] ?? $creds['username'] ?? ($sp->username() !== 'John Doe' ? $sp->username() : '');
                $username = $creds['username'] ?? '';
                $avatar = $creds['picture'] ?? $creds['avatar'] ?? null;
                $platformId = $creds['platform_id'] ?? (string) $sp->id;
                $isExpired = $sp->expires_at && $sp->expires_at->lt(now());
                $status = ($sp->isConnected() && ! $isExpired) ? 'connected' : 'expired';

                if (! $connectedAccounts->has($platformKey) || $connectedAccounts->get($platformKey)->isEmpty()) {
                    $virtualAccount = new ConnectedAccount();
                    $virtualAccount->forceFill([
                        'user_id'            => $sp->user_id,
                        'platform'           => $platformKey,
                        'account_identifier' => $platformId,
                        'account_name'       => $name ?: ucfirst($platformKey) . ' Account',
                        'account_username'   => $username,
                        'account_avatar'     => $avatar,
                        'connection_status'  => $status,
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
            // Clear any zombie/incomplete salla rows from connectedAccounts
            $connectedAccounts->forget('salla');

            $sallaConnections = \App\Models\SallaConnection::query()
                ->where('user_id', $user->id)
                ->where('connection_status', 'connected')
                ->get();

            $sallaAccounts = collect();
            foreach ($sallaConnections as $sallaConn) {
                $storeName = $sallaConn->store_name
                    ?: data_get($sallaConn->store_data, 'data.merchant.name')
                    ?: 'متجر سلة التجريبي';
                $storeDomain = $sallaConn->store_domain
                    ?: data_get($sallaConn->store_data, 'data.merchant.domain')
                    ?: 'salla.sa';
                $storeAvatar = data_get($sallaConn->store_data, 'data.merchant.avatar');

                $sallaAccount = new ConnectedAccount();
                $sallaAccount->forceFill([
                    'user_id'            => $sallaConn->user_id,
                    'platform'           => 'salla',
                    'account_identifier' => (string) ($sallaConn->salla_store_id ?? $sallaConn->id),
                    'account_name'       => $storeName,
                    'account_username'   => $storeDomain,
                    'account_avatar'     => $storeAvatar,
                    'connection_status'  => 'connected',
                    'connected_at'       => $sallaConn->connected_at ?? $sallaConn->created_at,
                ]);
                $sallaAccount->id = $sallaConn->id;
                $sallaAccount->source = 'salla';
                $sallaAccounts->push($sallaAccount);
            }

            if ($sallaAccounts->isNotEmpty()) {
                $connectedAccounts->put('salla', $sallaAccounts);
            }
        }

        // Define supported platforms and their OAuth / connect routes
        $platforms = $this->getSupportedPlatforms();

        $totalConnected = 0;
        foreach ($platforms as $pk => $p) {
            $accs = $connectedAccounts->get($pk, collect());
            if ($accs->isNotEmpty() && $accs->contains(fn ($a) => $a->isConnected())) {
                $totalConnected++;
            }
        }

        return view('panel.user.integrations.index', [
            'connectedAccounts' => $connectedAccounts,
            'platforms'         => $platforms,
            'totalConnected'    => $totalConnected,
        ]);
    }

    public function disconnect(Request $request, $account = null)
    {
        $user = Auth::user();
        $platform = $request->input('platform');
        $id = $account ?: $request->input('account_id') ?: $request->input('id');

        \Log::info('[DISCONNECT] Request received', [
            'id' => $id,
            'platform' => $platform,
            'user_id' => $user->id,
            'inputs' => $request->all(),
        ]);

        // 1. If platform is Salla OR id is a Salla connection
        if ($platform === 'salla' || (class_exists(\App\Models\SallaConnection::class) && \App\Models\SallaConnection::where('user_id', $user->id)->where('id', $id)->exists())) {
            if (class_exists(\App\Models\SallaConnection::class)) {
                $sallaQ = \App\Models\SallaConnection::where('user_id', $user->id);
                if ($id) {
                    $sallaQ->where(function ($q) use ($id) {
                        $q->where('id', $id)->orWhere('salla_store_id', $id);
                    });
                }
                $sallaQ->update([
                    'connection_status' => 'disconnected',
                    'webhook_status' => 'inactive',
                    'access_token' => null,
                    'refresh_token' => null,
                ]);
            }
            ConnectedAccount::where('user_id', $user->id)->where('platform', 'salla')->update([
                'connection_status' => 'disconnected',
                'access_token' => null,
                'refresh_token' => null,
            ]);

            return back()->with([
                'type' => 'success',
                'message' => trans('Salla store disconnected successfully.'),
            ]);
        }

        // 2. If it's WooCommerce
        if ($platform === 'woocommerce') {
            $wooIntegration = \App\Models\Integration\Integration::query()->whereIn('slug', ['woocommerce', 'wordpress'])->first();
            if ($wooIntegration) {
                \App\Models\Integration\UserIntegration::where('user_id', $user->id)
                    ->where('integration_id', $wooIntegration->id)
                    ->delete();
            }
            ConnectedAccount::where('user_id', $user->id)->where('platform', 'woocommerce')->delete();
            return back()->with([
                'type' => 'success',
                'message' => trans('WooCommerce disconnected successfully.'),
            ]);
        }

        // 3. Check ext_social_media_platforms (Instagram, TikTok, X, YouTube, LinkedIn)
        $deletedFromSocial = false;
        if (class_exists(\App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::class)) {
            $sp = null;
            if ($id) {
                // Use where('id', $id) instead of find($id) to avoid query builder conflicts
                $sp = \App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::where('user_id', $user->id)->where('id', $id)->first();
            }
            if (! $sp && $platform) {
                $sp = \App\Extensions\SocialMedia\System\Models\SocialMediaPlatform::where('user_id', $user->id)->where('platform', $platform)->first();
            }
            if ($sp) {
                $deletedPlatform = (string) $sp->platform;
                $sp->delete();
                $deletedFromSocial = true;

                \Log::info('[DISCONNECT] Deleted from ext_social_media_platforms', ['id' => $sp->id, 'platform' => $deletedPlatform]);

                // Also update connected_accounts if present
                ConnectedAccount::where('user_id', $user->id)->where('platform', $deletedPlatform)->update([
                    'connection_status' => 'disconnected',
                    'access_token' => null,
                    'refresh_token' => null,
                ]);
            } else {
                // Fallback: direct DB delete if Eloquent model lookup fails
                $directDeleted = \DB::table('ext_social_media_platforms')
                    ->where('user_id', $user->id)
                    ->where(function ($q) use ($id, $platform) {
                        if ($id) {
                            $q->where('id', $id);
                        }
                        if ($platform) {
                            $q->orWhere('platform', $platform);
                        }
                    })
                    ->delete();

                if ($directDeleted > 0) {
                    $deletedFromSocial = true;
                    \Log::info('[DISCONNECT] Fallback DB delete from ext_social_media_platforms', ['id' => $id, 'platform' => $platform, 'rows' => $directDeleted]);

                    if ($platform) {
                        ConnectedAccount::where('user_id', $user->id)->where('platform', $platform)->update([
                            'connection_status' => 'disconnected',
                            'access_token' => null,
                            'refresh_token' => null,
                        ]);
                    }
                }
            }
        }

        // 4. Check connected_accounts
        $ca = null;
        if ($id) {
            $ca = ConnectedAccount::where('user_id', $user->id)->where('id', $id)->first();
        }
        if (! $ca && $platform) {
            $ca = ConnectedAccount::where('user_id', $user->id)->where('platform', $platform)->first();
        }

        if ($ca) {
            if ($ca->user_id !== $user->id) {
                abort(403, 'Unauthorized');
            }
            $this->accountService->disconnect($ca, $user);
        } elseif ($platform && ! $deletedFromSocial) {
            // Last-resort: direct DB update on connected_accounts
            $updated = \DB::table('connected_accounts')
                ->where('user_id', $user->id)
                ->where('platform', $platform)
                ->update([
                    'connection_status' => 'disconnected',
                    'access_token' => null,
                    'refresh_token' => null,
                ]);
            \Log::info('[DISCONNECT] Fallback DB update on connected_accounts', ['platform' => $platform, 'rows' => $updated]);
        }

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
            'whatsapp_cloud' => [
                'name'         => 'WhatsApp Business',
                'badge'        => 'Meta Cloud API',
                'category'     => 'messaging',
                'icon'         => 'whatsapp',
                'connect_route' => 'dashboard.user.integrations.whatsapp-cloud.connect',
                'connect_url'  => \Route::has('dashboard.user.integrations.whatsapp-cloud.connect') ? route('dashboard.user.integrations.whatsapp-cloud.connect') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect official WhatsApp Business Cloud API in 1-click for AI customer support and automated messaging.',
            ],
            'messenger' => [
                'name'         => 'Facebook & Messenger',
                'badge'        => 'Pages & AI Reply',
                'category'     => 'messaging',
                'icon'         => 'facebook-messenger',
                'connect_route' => 'dashboard.user.integrations.messenger.connect',
                'connect_url'  => \Route::has('dashboard.user.integrations.messenger.connect') ? route('dashboard.user.integrations.messenger.connect') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect Facebook Pages to automate Messenger replies, post comments, and 24/7 AI customer care.',
            ],
            'instagram' => [
                'name'         => 'Instagram Direct & Comments',
                'badge'        => 'DMs & Reels',
                'category'     => 'messaging',
                'icon'         => 'instagram',
                'connect_route' => 'dashboard.user.integrations.instagram.connect',
                'connect_url'  => \Route::has('dashboard.user.integrations.instagram.connect') ? route('dashboard.user.integrations.instagram.connect') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect Instagram Business accounts to automate direct messages and reply to post & reel comments.',
            ],
            'salla' => [
                'name'         => 'Salla (سلة)',
                'badge'        => 'E-Commerce',
                'category'     => 'stores',
                'icon'         => 'salla',
                'connect_route' => 'dashboard.user.salla.connect',
                'connect_url'  => \Route::has('dashboard.user.salla.connect') ? route('dashboard.user.salla.connect') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your Salla store via official OAuth to sync products, inventory, and real-time order tracking.',
            ],
            'shopify' => [
                'name'         => 'Shopify',
                'badge'        => 'E-Commerce',
                'category'     => 'stores',
                'icon'         => 'shopify',
                'connect_route' => 'dashboard.user.chatbots.index',
                'connect_url'  => \Route::has('dashboard.user.chatbots.index') ? route('dashboard.user.chatbots.index') : '#',
                'oauth'        => false,
                'enabled'      => true,
                'description'  => 'Connect your Shopify store via Storefront/Admin API to enable product search, prices, and cart checkout.',
            ],
            'woocommerce' => [
                'name'         => 'WooCommerce',
                'badge'        => 'E-Commerce',
                'category'     => 'stores',
                'icon'         => 'woocommerce',
                'connect_route' => 'dashboard.user.integration.index',
                'connect_url'  => \Route::has('dashboard.user.integration.index') ? route('dashboard.user.integration.index') : '#',
                'oauth'        => false,
                'enabled'      => true,
                'description'  => 'Integrate your WooCommerce store via REST API for automated product queries, orders, and AI shopping.',
            ],
            'tiktok' => [
                'name'         => 'TikTok',
                'badge'        => 'Social Media',
                'category'     => 'social',
                'icon'         => 'tiktok',
                'connect_route' => 'social-media.oauth.connect.tiktok',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.tiktok') ? route('social-media.oauth.connect.tiktok') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your TikTok account to publish videos, shorts, and analyze audience growth.',
            ],
            'youtube' => [
                'name'         => 'YouTube & Shorts',
                'badge'        => 'Social Media',
                'category'     => 'social',
                'icon'         => 'youtube',
                'connect_route' => 'social-media.oauth.connect.youtube',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.youtube') ? route('social-media.oauth.connect.youtube') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect YouTube channel to manage video uploads, shorts, titles, and channel performance.',
            ],
            'x' => [
                'name'         => 'X (Twitter)',
                'badge'        => 'Social Media',
                'category'     => 'social',
                'icon'         => 'x',
                'connect_route' => 'social-media.oauth.connect.x',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.x') ? route('social-media.oauth.connect.x') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your X account to schedule tweets, threads, and track post performance.',
            ],
            'linkedin' => [
                'name'         => 'LinkedIn',
                'badge'        => 'Social Media',
                'category'     => 'social',
                'icon'         => 'linkedin',
                'connect_route' => 'social-media.oauth.connect.linkedin',
                'connect_url'  => $hasSocialMedia && \Route::has('social-media.oauth.connect.linkedin') ? route('social-media.oauth.connect.linkedin') : '#',
                'oauth'        => true,
                'enabled'      => true,
                'description'  => 'Connect your LinkedIn personal profile or company pages to publish updates and articles.',
            ],
        ];
    }
}
