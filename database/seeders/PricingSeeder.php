<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pricing;
use Illuminate\Support\Str;

class PricingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Basic',
                'description' => 'Essential tools for single-farm poultry operations.',
                'price' => 19.99,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'trial_period_days' => 7,
                'max_farms' => 1,
                'max_sheds' => 2,
                'max_flocks' => 2,
                'max_devices' => 5,
                'max_users' => 2,
                'feature_flags' => [
                    'auto_control' => false,
                    'reporting' => true,
                    'analytics' => false,
                    'support' => 'email',
                    'history_days' => 30,
                ],
                'sort_order' => 1,
                'is_active' => true,
                'meta' => [
                    'badge' => null,
                ],
            ],
            [
                'name' => 'Pro',
                'description' => 'Advanced automation and analytics for growing businesses.',
                'price' => 49.99,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'trial_period_days' => 14,
                'max_farms' => 3,
                'max_sheds' => 8,
                'max_flocks' => 10,
                'max_devices' => 20,
                'max_users' => 8,
                'feature_flags' => [
                    'auto_control' => true,
                    'reporting' => true,
                    'analytics' => true,
                    'support' => 'priority_email',
                    'history_days' => 180,
                ],
                'sort_order' => 2,
                'is_active' => true,
                'meta' => [
                    'badge' => 'Popular',
                ],
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Full control, unlimited scale, and premium support for large operations.',
                'price' => 129.00,
                'currency' => 'USD',
                'billing_interval' => 'monthly',
                'trial_period_days' => 30,
                'max_farms' => 9999,
                'max_sheds' => 9999,
                'max_flocks' => 9999,
                'max_devices' => 9999,
                'max_users' => 100,
                'feature_flags' => [
                    'auto_control' => true,
                    'reporting' => true,
                    'analytics' => true,
                    'support' => '24/7_phone',
                    'history_days' => 365,
                    'api_access' => true,
                ],
                'sort_order' => 3,
                'is_active' => true,
                'meta' => [
                    'badge' => 'Best Value',
                    'custom_pricing' => false,
                ],
            ],
        ];

        foreach ($tiers as $tier) {
            $slug = Str::slug($tier['name']);

            Pricing::updateOrCreate([
                'slug' => $slug,
            ], [
                'name' => $tier['name'],
                'slug' => $slug,
                'description' => $tier['description'],
                'price' => $tier['price'],
                'currency' => $tier['currency'],
                'billing_interval' => $tier['billing_interval'],
                'trial_period_days' => $tier['trial_period_days'],
                'max_farms' => $tier['max_farms'],
                'max_sheds' => $tier['max_sheds'],
                'max_flocks' => $tier['max_flocks'],
                'max_devices' => $tier['max_devices'],
                'max_users' => $tier['max_users'],
                'feature_flags' => json_encode($tier['feature_flags']),
                'sort_order' => $tier['sort_order'],
                'is_active' => $tier['is_active'],
                'meta' => json_encode($tier['meta']),
            ]);
        }
    }
}
