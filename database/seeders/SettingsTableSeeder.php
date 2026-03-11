<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $settings = [
            // Company group
            [
                'group' => 'company',
                'key' => 'name',
                'value' => json_encode('FlockSense'),
                'type' => 'string',
                'is_encrypted' => 0,
                'description' => 'Company name',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'company',
                'key' => 'url',
                'value' => json_encode('https://flocksense.ai'),
                'type' => 'url',
                'is_encrypted' => 0,
                'description' => 'Company website URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'company',
                'key' => 'logo',
                'value' => json_encode('assets/img/logo.png'),
                'type' => 'string',
                'is_encrypted' => 0,
                'description' => 'Company logo path or URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'company',
                'key' => 'mobile-logo',
                'value' => json_encode('assets/img/mobile_logo.png'),
                'type' => 'string',
                'is_encrypted' => 0,
                'description' => 'Company logo path or URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'company',
                'key' => 'slogan',
                'value' => json_encode('Transforming poultry farming with smart technology and data-driven insights.'),
                'type' => 'string',
                'is_encrypted' => 0,
                'description' => 'Company slogan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'company',
                'key' => 'demo',
                'value' => json_encode('http://flocksense.cosmosalgos.com/'),
                'type' => 'url',
                'is_encrypted' => 1,
                'description' => 'Demo url',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'video',
                'key' => 'background1',
                'value' => json_encode([
                    'cover' => 'assets/video/video_cover.webp',
                    'video' => 'assets/video/homepage_background_video.mp4',
                    'selected' => false,
                ]),
                'type' => 'json',
                'is_encrypted' => 0,
                'description' => 'Company slogan',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'video',
                'key' => 'background2',
                'value' => json_encode([
                    'cover' => 'assets/video/video_cover2.webp',
                    'video' => 'assets/video/homepage_background_video2.mp4',
                    'selected' => true,
                ]),
                'type' => 'json',
                'is_encrypted' => 0,
                'description' => 'Company slogan',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Social group
            [
                'group' => 'social',
                'key' => 'linkedin',
                'value' => json_encode([
                    'id' => 'http://linkedin.com/',
                    'icon' => 'fa-linkedin-in',
                ]),
                'type' => 'json',
                'is_encrypted' => 0,
                'description' => 'LinkedIn profile',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'social',
                'key' => 'facebook',
                'value' => json_encode([
                    'id' => 'https://www.facebook.com/',
                    'icon' => 'fa-facebook-f',
                ]),
                'type' => 'json',
                'is_encrypted' => 0,
                'description' => 'Facebook profile',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'social',
                'key' => 'instagram',
                'value' => json_encode([
                    'id' => 'https://www.instagram.com/',
                    'icon' => 'fa-instagram',
                ]),
                'type' => 'json',
                'is_encrypted' => 0,
                'description' => 'Instagram profile',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'social',
                'key' => 'twitter',
                'value' => json_encode([
                    'id' => 'https://x.com/',
                    'icon' => 'fa-x-twitter',
                ]),
                'type' => 'json',
                'is_encrypted' => 0,
                'description' => 'Instagram profile',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // Contact groups
            [
                'group' => 'contact',
                'key' => 'email',
                'value' => json_encode('contact@flocksense.ai'),
                'type' => 'string',
                'is_encrypted' => 0,
                'description' => 'Contact email address',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'contact',
                'key' => 'phone',
                'value' => json_encode('+92 (0)300 073 0490'),
                'type' => 'string',
                'is_encrypted' => 0,
                'description' => 'Contact phone number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'contact',
                'key' => 'address1',
                'value' => json_encode('23 Roundtree Cl, Norwich, NR7 8SX, UK'),
                'type' => 'string',
                'is_encrypted' => 0,
                'description' => 'Primary address line',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'group' => 'contact',
                'key' => 'address2',
                'value' => json_encode('300 Street‑17, G‑15/2, Islamabad, Pakistan'),
                'type' => 'string',
                'is_encrypted' => 0,
                'description' => 'Secondary address line',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('settings')->updateOrInsert(
                [
                    'group' => $setting['group'],
                    'key' => $setting['key'],
                ],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'is_encrypted' => $setting['is_encrypted'],
                    'description' => $setting['description'],
                    'updated_at' => $setting['updated_at'],
                    'created_at' => $setting['created_at'],
                ]
            );
        }
    }
}
