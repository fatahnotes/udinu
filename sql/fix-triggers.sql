-- Fix trigger: wrap in exception handler so it NEVER fails and rolls back UPDATE
CREATE OR REPLACE FUNCTION auto_assign_satker_verifikator()
RETURNS TRIGGER AS $$
BEGIN
    BEGIN
        IF NEW.status = 'submitted' AND OLD.status = 'draft' THEN
            INSERT INTO status_history (submission_id, old_status, new_status, notes)
            VALUES (NEW.id, OLD.status, 'pending_verification', 'Menunggu verifikasi satker');
        END IF;
    EXCEPTION WHEN OTHERS THEN
        RAISE WARNING 'auto_assign_satker_verifikator: %', SQLERRM;
    END;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Also fix record_status_change to never fail
CREATE OR REPLACE FUNCTION record_status_change()
RETURNS TRIGGER AS $$
BEGIN
    BEGIN
        IF OLD.status IS DISTINCT FROM NEW.status THEN
            INSERT INTO status_history (submission_id, old_status, new_status, notes)
            VALUES (NEW.id, OLD.status, NEW.status, 
                    'Status berubah dari ' || COALESCE(OLD.status, 'NULL') || ' ke ' || NEW.status);
        END IF;
    EXCEPTION WHEN OTHERS THEN
        RAISE WARNING 'record_status_change: %', SQLERRM;
    END;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
