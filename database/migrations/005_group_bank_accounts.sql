-- Release 5: Nigerian-first improvements.
-- Additive only.

ALTER TABLE `alumni_groups`
    ADD COLUMN `bank_name` VARCHAR(120) NULL AFTER `currency`,
    ADD COLUMN `bank_account_number` VARCHAR(40) NULL AFTER `bank_name`,
    ADD COLUMN `bank_account_holder` VARCHAR(160) NULL AFTER `bank_account_number`;
