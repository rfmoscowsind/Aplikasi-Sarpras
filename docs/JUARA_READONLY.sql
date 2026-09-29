-- Run this in the JUARA database using an account allowed to create views.
-- The Sarpras application itself MUST NOT use that privileged account.

CREATE OR REPLACE VIEW sarpras_student_directory AS
SELECT
    u.id AS student_id,
    u.name AS name,
    u.nip AS nis,
    c.name AS class_name,
    c.code AS class_code
FROM users AS u
INNER JOIN classes AS c
    ON c.id = u.class_id
INNER JOIN model_has_roles AS mhr
    ON mhr.model_id = u.id
INNER JOIN roles AS r
    ON r.id = mhr.role_id
   AND r.name = 'siswa'
WHERE u.deleted_at IS NULL;

-- Example only. Replace host and password before running.
-- CREATE USER 'sarpras_reader'@'<SARPRAS_LXC_IP>' IDENTIFIED BY '<STRONG_PASSWORD>';
-- GRANT SELECT ON juara.sarpras_student_directory TO 'sarpras_reader'@'<SARPRAS_LXC_IP>';
-- FLUSH PRIVILEGES;

-- Do not grant SELECT on juara.* and do not grant INSERT/UPDATE/DELETE.
