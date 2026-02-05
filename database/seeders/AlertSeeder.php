<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlertSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        $alerts = [];
        $alertResponses = [];

        // Alert types and severities for variety
        $types = ['system', 'security', 'billing', 'activity', 'maintenance'];
        $severities = get_alert_levels();
        $statuses = ['queued', 'sent', 'failed', 'delivered'];
        $channels = ['in_app', 'email', 'sms', 'push'];
        $actionTypes = ['Pending', 'Acknowledged', 'Resolved', 'Dismissed', 'Escalated'];

        // Alert titles and messages
        $alertTemplates = [
            [
                'title' => 'Temperature Alert',
                'message' => 'Temperature in shed has exceeded safe limits. Current temperature: 32°C.',
                'type' => 'system',
                'severity' => 'critical',
            ],
            [
                'title' => 'Feed Low Inventory',
                'message' => 'Feed inventory is running low. Only 200kg remaining.',
                'type' => 'activity',
                'severity' => 'warning',
            ],
            [
                'title' => 'Water Consumption High',
                'message' => 'Water consumption is 25% higher than average for flock #9.',
                'type' => 'activity',
                'severity' => 'warning',
            ],
            [
                'title' => 'Vaccination Due',
                'message' => 'Vaccination is due for flock #9. Schedule vaccination within 3 days.',
                'type' => 'maintenance',
                'severity' => 'info',
            ],
            [
                'title' => 'Humidity Alert',
                'message' => 'Humidity levels are above optimal range. Current: 85%.',
                'type' => 'system',
                'severity' => 'warning',
            ],
            [
                'title' => 'Security Breach Attempt',
                'message' => 'Unauthorized access attempt detected at farm entrance.',
                'type' => 'security',
                'severity' => 'critical',
            ],
            [
                'title' => 'Payment Successful',
                'message' => 'Monthly subscription payment processed successfully.',
                'type' => 'billing',
                'severity' => 'success',
            ],
            [
                'title' => 'Equipment Maintenance Due',
                'message' => 'Feed dispenser #3 requires scheduled maintenance.',
                'type' => 'maintenance',
                'severity' => 'info',
            ],
            [
                'title' => 'Flock Weight Update',
                'message' => 'Average weight of flock #9: 2.8kg (within target range).',
                'type' => 'activity',
                'severity' => 'success',
            ],
            [
                'title' => 'Power Outage Detected',
                'message' => 'Backup generator activated due to main power failure.',
                'type' => 'system',
                'severity' => 'critical',
            ],
            [
                'title' => 'Mortality Rate Alert',
                'message' => 'Mortality rate increased to 0.5% in last 24 hours.',
                'type' => 'activity',
                'severity' => 'warning',
            ],
            [
                'title' => 'Subscription Renewal Due',
                'message' => 'Your subscription will renew in 7 days.',
                'type' => 'billing',
                'severity' => 'info',
            ],
            [
                'title' => 'Ventilation System Issue',
                'message' => 'Ventilation fan #2 running at reduced capacity.',
                'type' => 'maintenance',
                'severity' => 'warning',
            ],
            [
                'title' => 'Biosecurity Alert',
                'message' => 'Visitor without proper sanitization detected in shed area.',
                'type' => 'security',
                'severity' => 'critical',
            ],
            [
                'title' => 'Feed Conversion Ratio Update',
                'message' => 'FCR improved to 1.65 (excellent performance).',
                'type' => 'activity',
                'severity' => 'success',
            ],
            [
                'title' => 'Backup Completed',
                'message' => 'Daily system backup completed successfully.',
                'type' => 'system',
                'severity' => 'success',
            ],
            [
                'title' => 'Water pH Alert',
                'message' => 'Water pH level is outside optimal range (6.8).',
                'type' => 'system',
                'severity' => 'warning',
            ],
            [
                'title' => 'Payment Failed',
                'message' => 'Automatic payment failed. Please update payment method.',
                'type' => 'billing',
                'severity' => 'critical',
            ],
            [
                'title' => 'Lighting Schedule Updated',
                'message' => 'Lighting schedule adjusted for flock growth stage.',
                'type' => 'activity',
                'severity' => 'info',
            ],
            [
                'title' => 'Fire Alarm Test',
                'message' => 'Monthly fire alarm test completed successfully.',
                'type' => 'security',
                'severity' => 'info',
            ],
            [
                'title' => 'Feed Delivery Scheduled',
                'message' => 'New feed delivery scheduled for tomorrow 10 AM.',
                'type' => 'activity',
                'severity' => 'success',
            ],
            [
                'title' => 'System Update Available',
                'message' => 'New system update available. Schedule update at your convenience.',
                'type' => 'system',
                'severity' => 'info',
            ],
            [
                'title' => 'Carbon Dioxide Levels High',
                'message' => 'CO2 levels elevated. Increasing ventilation.',
                'type' => 'system',
                'severity' => 'warning',
            ],
            [
                'title' => 'Daily Health Check Complete',
                'message' => 'All birds in flock #9 passed daily health inspection.',
                'type' => 'activity',
                'severity' => 'success',
            ],
        ];

        // Create alerts
        foreach ($alertTemplates as $index => $template) {
            $scheduledAt = $now->copy()->subDays(rand(1, 10))->addHours(rand(1, 12));
            $sentAt = $scheduledAt->copy()->addMinutes(rand(1, 60));

            $alerts[] = [
                'user_id' => null,
                'farm_id' => 1,
                'shed_id' => 1,
                'flock_id' => 9,
                'title' => $template['title'],
                'message' => $template['message'],
                'type' => $template['type'],
                'severity' => $template['severity'],
                'channel' => $channels[array_rand($channels)],
                'data' => json_encode([
                    'farm_id' => 1,
                    'shed_id' => 1,
                    'flock_id' => 9,
                    'timestamp' => $scheduledAt->toISOString(),
                    'additional_info' => 'FlockSense automated alert system for monitoring.',
                ]),
                'status' => $statuses[array_rand($statuses)],
                'scheduled_at' => $scheduledAt,
                'sent_at' => in_array($template['severity'], ['critical', 'warning']) ? $sentAt : null,
                'is_read' => rand(0, 1),
                'read_at' => rand(0, 1) ? $sentAt->copy()->addMinutes(rand(5, 120)) : null,
                'is_dismissed' => rand(0, 1),
                'dismissed_at' => rand(0, 1) ? $sentAt->copy()->addHours(rand(1, 24)) : null,
                'created_at' => $scheduledAt->copy()->subHours(rand(1, 6)),
                'updated_at' => $sentAt->copy()->addHours(rand(1, 48)),
                'deleted_at' => null,
            ];
        }

        // Insert alerts
        DB::table('alerts')->insert($alerts);

        // Get inserted alert IDs
        $alertIds = DB::table('alerts')->pluck('id')->toArray();

        // Create responses for each alert
        foreach ($alertIds as $alertId) {
            $responseCount = rand(1, 3); // 1-3 responses per alert

            for ($i = 0; $i < $responseCount; $i++) {
                $respondedAt = $now->copy()->subDays(rand(0, 15))->addHours(rand(1, 24));
                $actionType = $actionTypes[array_rand($actionTypes)];

                $actionDetails = match ($actionType) {
                    'Acknowledged' => 'Alert acknowledged and being monitored.',
                    'Resolved' => 'Issue has been resolved. Root cause identified and fixed.',
                    'Dismissed' => 'Alert dismissed as false positive.',
                    'Escalated' => 'Alert escalated to senior management for review.',
                    default => 'Awaiting action from assigned personnel.'
                };

                $alertResponses[] = [
                    'alert_id' => $alertId,
                    'creator_id' => null, // Assuming system-generated
                    'responder_id' => rand(1, 5), // Random user IDs 1-5
                    'responded_at' => $respondedAt,
                    'action_type' => $actionType,
                    'action_details' => $actionDetails.' Follow-up required: '.(rand(0, 1) ? 'Yes' : 'No'),
                    'created_at' => $respondedAt,
                    'updated_at' => $respondedAt->copy()->addHours(rand(1, 12)),
                ];
            }
        }

        // Insert alert responses
        DB::table('alert_responses')->insert($alertResponses);
    }
}
