<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Smart INSERT trigger
        DB::unprepared("
            DROP TRIGGER IF EXISTS smart_farm_expenses_insert;

            CREATE TRIGGER smart_farm_expenses_insert
            BEFORE INSERT ON farm_expenses
            FOR EACH ROW
            BEGIN
                DECLARE msg VARCHAR(255);

                -- Case 1: Both quantity and unit_cost provided
                IF NEW.quantity IS NOT NULL AND NEW.unit_cost IS NOT NULL THEN
                    SET NEW.amount = NEW.quantity * NEW.unit_cost;

                -- Case 2: Only amount provided (allow this)
                ELSEIF NEW.amount IS NOT NULL AND NEW.quantity IS NULL AND NEW.unit_cost IS NULL THEN
                    SET NEW.amount = NEW.amount; -- Dummy statement to avoid syntax error

                -- Case 3: Amount + quantity provided, calculate unit_cost
                ELSEIF NEW.amount IS NOT NULL AND NEW.quantity IS NOT NULL AND NEW.unit_cost IS NULL THEN
                    IF NEW.quantity = 0 THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Quantity cannot be zero when calculating unit cost';
                    ELSE
                        SET NEW.unit_cost = NEW.amount / NEW.quantity;
                    END IF;

                -- Case 4: Amount + unit_cost provided, calculate quantity
                ELSEIF NEW.amount IS NOT NULL AND NEW.unit_cost IS NOT NULL AND NEW.quantity IS NULL THEN
                    IF NEW.unit_cost = 0 THEN
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Unit cost cannot be zero when calculating quantity';
                    ELSE
                        SET NEW.quantity = NEW.amount / NEW.unit_cost;
                    END IF;

                -- Case 5: Invalid - only one of quantity or unit_cost
                ELSEIF (NEW.quantity IS NULL XOR NEW.unit_cost IS NULL) THEN
                    SET msg = CONCAT('Provide both quantity & unit_cost, or only amount');
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;

                -- Case 6: All NULL (if business allows)
                -- Do nothing

                END IF;

                -- Validate amount is positive
                IF NEW.amount IS NOT NULL AND NEW.amount <= 0 THEN
                    SET msg = CONCAT('Amount must be positive: ', NEW.amount);
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
                END IF;
            END
        ");

        // Smart UPDATE trigger
        DB::unprepared("
            DROP TRIGGER IF EXISTS smart_farm_expenses_update;

            CREATE TRIGGER smart_farm_expenses_update
            BEFORE UPDATE ON farm_expenses
            FOR EACH ROW
            BEGIN
                DECLARE msg VARCHAR(255);

                -- Determine what's being changed
                SET @quantity_changed = NEW.quantity <> OLD.quantity OR
                                       (NEW.quantity IS NULL XOR OLD.quantity IS NULL);
                SET @unit_cost_changed = NEW.unit_cost <> OLD.unit_cost OR
                                        (NEW.unit_cost IS NULL XOR OLD.unit_cost IS NULL);
                SET @amount_changed = NEW.amount <> OLD.amount OR
                                     (NEW.amount IS NULL XOR OLD.amount IS NULL);

                -- Priority 1: If both quantity and unit_cost are set/updated
                IF NEW.quantity IS NOT NULL AND NEW.unit_cost IS NOT NULL THEN
                    SET NEW.amount = NEW.quantity * NEW.unit_cost;

                -- Priority 2: If amount was explicitly changed
                ELSEIF @amount_changed THEN
                    -- Recalculate missing fields if possible
                    IF NEW.amount IS NOT NULL AND NEW.quantity IS NOT NULL AND NEW.unit_cost IS NULL THEN
                        IF NEW.quantity = 0 THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Quantity cannot be zero';
                        ELSE
                            SET NEW.unit_cost = NEW.amount / NEW.quantity;
                        END IF;

                    ELSEIF NEW.amount IS NOT NULL AND NEW.unit_cost IS NOT NULL AND NEW.quantity IS NULL THEN
                        IF NEW.unit_cost = 0 THEN
                            SIGNAL SQLSTATE '45000'
                            SET MESSAGE_TEXT = 'Unit cost cannot be zero';
                        ELSE
                            SET NEW.quantity = NEW.amount / NEW.unit_cost;
                        END IF;

                    -- If only amount changed, keep it
                    END IF;

                -- Priority 3: If quantity or unit_cost changed but amount wasn't explicitly set
                ELSEIF (@quantity_changed OR @unit_cost_changed) AND NOT @amount_changed THEN
                    -- Try to calculate based on available data
                    IF NEW.quantity IS NOT NULL AND NEW.unit_cost IS NOT NULL THEN
                        SET NEW.amount = NEW.quantity * NEW.unit_cost;

                    ELSEIF NEW.quantity IS NOT NULL AND OLD.unit_cost IS NOT NULL THEN
                        SET NEW.amount = NEW.quantity * OLD.unit_cost;
                        SET NEW.unit_cost = OLD.unit_cost;

                    ELSEIF NEW.unit_cost IS NOT NULL AND OLD.quantity IS NOT NULL THEN
                        SET NEW.amount = OLD.quantity * NEW.unit_cost;
                        SET NEW.quantity = OLD.quantity;

                    -- Can't calculate, set amount to NULL
                    ELSE
                        SET NEW.amount = NULL;
                    END IF;
                END IF;

                -- Final validation
                IF NEW.amount IS NOT NULL AND NEW.amount <= 0 THEN
                    SET msg = CONCAT('Amount must be positive: ', NEW.amount);
                    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('
            DROP TRIGGER IF EXISTS set_farm_expenses_amount_on_insert;
            DROP TRIGGER IF EXISTS update_farm_expenses_amount;
        ');
    }
};
