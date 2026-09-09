<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Church\ChurchMember;
use App\Models\ConsoSheet;
use App\Models\LifeGroup\LifeGroup;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Policies\ChurchMemberPolicy;
use App\Policies\ConsoSheetPolicy;
use App\Policies\LifeGroupPolicy;
use App\Policies\ProductPolicy;
use App\Policies\SalePolicy;
use App\Policies\StorePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

final class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Sale::class => SalePolicy::class,
        ChurchMember::class => ChurchMemberPolicy::class,
        Product::class => ProductPolicy::class,
        LifeGroup::class => LifeGroupPolicy::class,
        Store::class => StorePolicy::class,
        ConsoSheet::class => ConsoSheetPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
