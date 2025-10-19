-- Franchise Application Rejection Logic
-- Triggers and procedures to handle rejected applications

-- Update compliance status when franchise is rejected
DELIMITER //
CREATE TRIGGER franchise_rejection_trigger
AFTER UPDATE ON franchise_applications
FOR EACH ROW
BEGIN
    IF NEW.status = 'rejected' AND OLD.status != 'rejected' THEN
        -- Update compliance status to non-compliant
        UPDATE compliance_status 
        SET franchise_status = 'revoked', 
            compliance_score = GREATEST(compliance_score - 20, 0),
            updated_at = NOW()
        WHERE operator_id = NEW.operator_id AND vehicle_id = NEW.vehicle_id;
        
        -- Update vehicle status to suspended
        UPDATE vehicles 
        SET status = 'suspended' 
        WHERE vehicle_id = NEW.vehicle_id;
        
        -- Log the rejection action
        INSERT INTO workflow_history (
            history_id, application_id, stage_from, stage_to, 
            action_taken, processed_by, processing_date, remarks
        ) VALUES (
            CONCAT('WH-', YEAR(NOW()), '-', LPAD(FLOOR(RAND() * 999) + 1, 3, '0')),
            NEW.application_id, OLD.workflow_stage, 'completed',
            'Application Rejected', NEW.assigned_to, NOW(), NEW.remarks
        );
    END IF;
END//
DELIMITER ;