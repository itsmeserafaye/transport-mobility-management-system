-- Fix missing revenue records for paid violations
-- Insert missing revenue collection records for paid violations

INSERT INTO revenue_collections (
    collection_id, 
    violation_id, 
    operator_id, 
    vehicle_id, 
    collection_date, 
    amount_collected, 
    receipt_number, 
    collected_by, 
    status
)
SELECT 
    CONCAT('RC-', DATE_FORMAT(NOW(), '%Y-%m-%d-'), LPAD(ROW_NUMBER() OVER (ORDER BY vh.violation_id), 3, '0')) as collection_id,
    vh.violation_id,
    vh.operator_id,
    vh.vehicle_id,
    CURDATE() as collection_date,
    vh.fine_amount as amount_collected,
    CONCAT('RCP-', DATE_FORMAT(NOW(), '%Y%m%d-'), LPAD(ROW_NUMBER() OVER (ORDER BY vh.violation_id), 3, '0')) as receipt_number,
    'System Auto-Sync' as collected_by,
    'deposited' as status
FROM violation_history vh 
LEFT JOIN revenue_collections rc ON vh.violation_id = rc.violation_id 
WHERE vh.settlement_status = 'paid' 
AND rc.violation_id IS NULL;

-- Verify the fix
SELECT 'After Fix - Paid Violations:' as description, COUNT(*) as count 
FROM violation_history 
WHERE settlement_status = 'paid';

SELECT 'After Fix - Revenue Records:' as description, COUNT(*) as count 
FROM revenue_collections;

SELECT 'Missing Records (should be 0):' as description, COUNT(*) as count
FROM violation_history vh 
LEFT JOIN revenue_collections rc ON vh.violation_id = rc.violation_id 
WHERE vh.settlement_status = 'paid' 
AND rc.violation_id IS NULL;