<?php

namespace Pterodactyl\Http\ViewComposers;

use Illuminate\View\View;
use Pterodactyl\Services\Helpers\AssetHashService;
use Pterodactyl\Http\Controllers\Admin\SuperAdminController;

class AssetComposer
{
    public function __construct(private AssetHashService $assetHashService) {}

    public function compose(View $view): void
    {
        $view->with('asset', $this->assetHashService);
        $view->with('siteConfiguration', array_merge([
            'name'      => config('app.name') ?? 'wolfXcore',
            'locale'    => config('app.locale') ?? 'en',
            'recaptcha' => [
                'enabled' => config('recaptcha.enabled', false),
                'siteKey' => config('recaptcha.website_key') ?? '',
            ],
            'repoClone' => SuperAdminController::getRepoCloneConfig(),
        ], SuperAdminController::getThemeJson()));
    }
}
