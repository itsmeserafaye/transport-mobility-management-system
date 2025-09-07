<?php
require_once '../../../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Get violation analytics data
$analytics = [];

// Total violations by type
$query = "SELECT violation_type, COUNT(*) as count, SUM(fine_amount) as total_fines
          FROM violation_history 
          GROUP BY violation_type 
          ORDER BY count DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$analytics['violation_types'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Settlement status breakdown
$query = "SELECT settlement_status, COUNT(*) as count, SUM(fine_amount) as amount
          FROM violation_history 
          GROUP BY settlement_status";
$stmt = $conn->prepare($query);
$stmt->execute();
$analytics['settlement_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly trends (last 12 months)
$query = "SELECT DATE_FORMAT(violation_date, '%Y-%m') as month,
                 COUNT(*) as violations,
                 SUM(fine_amount) as fines
          FROM violation_history 
          WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
          GROUP BY month 
          ORDER BY month";
$stmt = $conn->prepare($query);
$stmt->execute();
$analytics['monthly_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Repeat offenders
$query = "SELECT o.first_name, o.last_name, o.operator_id, v.plate_number,
                 COUNT(*) as violation_count, SUM(vh.fine_amount) as total_fines
          FROM violation_history vh
          JOIN operators o ON vh.operator_id = o.operator_id
          JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
          GROUP BY vh.operator_id, vh.vehicle_id
          HAVING violation_count >= 3
          ORDER BY violation_count DESC
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$analytics['repeat_offenders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top locations
$query = "SELECT location, COUNT(*) as count
          FROM violation_history 
          WHERE location IS NOT NULL AND location != ''
          GROUP BY location 
          ORDER BY count DESC 
          LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->execute();
$analytics['top_locations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <!-- Violation Types Chart -->
    <div class="bg-white p-6 rounded-lg border">
        <h3 class="text-lg font-semibold mb-4">Violations by Type</h3>
        <div class="space-y-3">
            <?php foreach ($analytics['violation_types'] as $type): ?>
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium"><?php echo $type['violation_type']; ?></span>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-600"><?php echo $type['count']; ?> violations</span>
                    <span class="text-sm font-medium text-red-600">₱<?php echo number_format($type['total_fines'], 0); ?></span>
                </div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <?php 
                $maxCount = $analytics['violation_types'][0]['count'];
                $percentage = ($type['count'] / $maxCount) * 100;
                ?>
                <div class="bg-red-500 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Settlement Status -->
    <div class="bg-white p-6 rounded-lg border">
        <h3 class="text-lg font-semibold mb-4">Settlement Status</h3>
        <div class="grid grid-cols-3 gap-4">
            <?php foreach ($analytics['settlement_breakdown'] as $status): ?>
            <div class="text-center">
                <div class="text-2xl font-bold <?php echo $status['settlement_status'] == 'paid' ? 'text-green-600' : ($status['settlement_status'] == 'partial' ? 'text-yellow-600' : 'text-red-600'); ?>">
                    <?php echo $status['count']; ?>
                </div>
                <div class="text-sm text-gray-600"><?php echo ucfirst($status['settlement_status']); ?></div>
                <div class="text-xs text-gray-500">₱<?php echo number_format($status['amount'], 0); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Monthly Trends -->
    <div class="bg-white p-6 rounded-lg border">
        <h3 class="text-lg font-semibold mb-4">Monthly Trends (Last 12 Months)</h3>
        <div class="space-y-2">
            <?php foreach ($analytics['monthly_trends'] as $trend): ?>
            <div class="flex items-center justify-between py-2 border-b">
                <span class="text-sm font-medium"><?php echo date('M Y', strtotime($trend['month'] . '-01')); ?></span>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600"><?php echo $trend['violations']; ?> violations</span>
                    <span class="text-sm font-medium text-orange-600">₱<?php echo number_format($trend['fines'], 0); ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Repeat Offenders -->
    <div class="bg-white p-6 rounded-lg border">
        <h3 class="text-lg font-semibold mb-4">Top Repeat Offenders</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b">
                        <th class="text-left py-2">Operator</th>
                        <th class="text-left py-2">Vehicle</th>
                        <th class="text-center py-2">Violations</th>
                        <th class="text-right py-2">Total Fines</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($analytics['repeat_offenders'] as $offender): ?>
                    <tr class="border-b">
                        <td class="py-2">
                            <div class="font-medium"><?php echo $offender['first_name'] . ' ' . $offender['last_name']; ?></div>
                            <div class="text-xs text-gray-500"><?php echo $offender['operator_id']; ?></div>
                        </td>
                        <td class="py-2"><?php echo $offender['plate_number']; ?></td>
                        <td class="text-center py-2">
                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                                <?php echo $offender['violation_count']; ?>
                            </span>
                        </td>
                        <td class="text-right py-2 font-medium">₱<?php echo number_format($offender['total_fines'], 0); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Violation Locations -->
    <div class="bg-white p-6 rounded-lg border">
        <h3 class="text-lg font-semibold mb-4">Top Violation Locations</h3>
        <div class="space-y-3">
            <?php foreach ($analytics['top_locations'] as $location): ?>
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium"><?php echo $location['location']; ?></span>
                <span class="text-sm text-gray-600"><?php echo $location['count']; ?> violations</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>