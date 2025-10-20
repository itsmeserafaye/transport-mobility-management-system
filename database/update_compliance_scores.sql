-- Update compliance scores based on current franchise and inspection status
UPDATE compliance_status cs
LEFT JOIN (
    SELECT operator_id, vehicle_id, COUNT(*) as violation_count
    FROM violation_history 
    WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
    GROUP BY operator_id, vehicle_id
) vh ON cs.operator_id = vh.operator_id AND cs.vehicle_id = vh.vehicle_id
SET 
    cs.compliance_score = (
        CASE 
            WHEN cs.franchise_status = 'valid' THEN 40
            WHEN cs.franchise_status = 'pending' THEN 20
            ELSE 0
        END +
        CASE 
            WHEN cs.inspection_status = 'passed' THEN 40
            WHEN cs.inspection_status = 'pending' THEN 20
            ELSE 0
        END +
        CASE 
            WHEN COALESCE(vh.violation_count, 0) = 0 THEN 20
            WHEN COALESCE(vh.violation_count, 0) <= 2 THEN 10
            ELSE 0
        END
    ),
    cs.violation_count = COALESCE(vh.violation_count, 0);