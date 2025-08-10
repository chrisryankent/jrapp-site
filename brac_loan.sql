-- Create database
CREATE DATABASE IF NOT EXISTS `brac_loan` CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;
USE `brac_loan`;

-- Roles & Permissions
CREATE TABLE `tbl_role` (
  id TINYINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) UNIQUE,
  description VARCHAR(255)
) ENGINE=InnoDB;

CREATE TABLE `tbl_branch` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  address VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tbl_terms_conditions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  content TEXT NOT NULL,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE `tbl_user` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(100) UNIQUE,
  password_hash VARCHAR(255),
  designation VARCHAR(100),
  role_id TINYINT NOT NULL,
  branch_id INT UNSIGNED,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES tbl_role(id),
  FOREIGN KEY (branch_id) REFERENCES tbl_branch(id)
) ENGINE=InnoDB;



-- Now add manager_user_id to tbl_branch (to resolve circular reference)
ALTER TABLE `tbl_branch`
  ADD COLUMN manager_user_id INT UNSIGNED NULL,
  ADD CONSTRAINT fk_manager_user FOREIGN KEY (manager_user_id) REFERENCES tbl_user(id);

CREATE TABLE `tbl_permission` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role_id TINYINT NOT NULL,
  module VARCHAR(50) NOT NULL,
  permission ENUM('READ','WRITE','APPROVE','MANAGE') NOT NULL,
  FOREIGN KEY (role_id) REFERENCES tbl_role(id)
) ENGINE=InnoDB;

-- Borrowers
CREATE TABLE `tbl_borrower` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  nid BIGINT UNSIGNED UNIQUE,
  rejected_count INT UNSIGNED DEFAULT 0,
  gender ENUM('Male','Female','Other'),
  mobile VARCHAR(20),
  email VARCHAR(100),
  dob DATE,
  address VARCHAR(255),
  working_status ENUM('Student','Unemployed','Employed','Self-Employed','Retired'),
  password_hash VARCHAR(255),
  selfie_path VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- KYC Verification Records
CREATE TABLE `tbl_kyc_record` (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  borrower_id INT UNSIGNED NOT NULL,
  scanned_data JSON NOT NULL COMMENT 'Decoded barcode data (e.g. name, dob, document number)',
  front_image_path VARCHAR(255) NOT NULL COMMENT 'Path to front side of ID',
  back_image_path VARCHAR(255) NOT NULL COMMENT 'Path to back side of ID',
  selfie_path VARCHAR(255) NOT NULL COMMENT 'Path to captured selfie for liveness',
  status ENUM('Pending','Verified','Rejected') DEFAULT 'Pending',
  verified_by INT UNSIGNED NULL,
  verified_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (borrower_id) REFERENCES tbl_borrower(id),
  FOREIGN KEY (verified_by) REFERENCES tbl_user(id)
) ENGINE=InnoDB;

-- Live Location Tracking
CREATE TABLE `tbl_borrower_location` (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  borrower_id INT UNSIGNED NOT NULL,
  latitude DECIMAL(9,6) NOT NULL,
  longitude DECIMAL(9,6) NOT NULL,
  location_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  device_id VARCHAR(100) NULL,
  FOREIGN KEY (borrower_id) REFERENCES tbl_borrower(id) ON DELETE CASCADE,
  INDEX (borrower_id), INDEX (location_time)
) ENGINE=InnoDB;

-- Geofencing
CREATE TABLE `tbl_geofence` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  center_lat DECIMAL(9,6),
  center_lng DECIMAL(9,6),
  radius_meters INT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `tbl_geofence_alert` (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  borrower_location_id BIGINT UNSIGNED,
  geofence_id INT UNSIGNED,
  alert_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  message VARCHAR(255),
  FOREIGN KEY (borrower_location_id) REFERENCES tbl_borrower_location(id),
  FOREIGN KEY (geofence_id) REFERENCES tbl_geofence(id)
) ENGINE=InnoDB;

-- Interest Rates
CREATE TABLE `tbl_interest_rate` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  annual_rate_percent DECIMAL(5,2),
  effective_date DATE UNIQUE,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Loan Applications
CREATE TABLE `tbl_loan_application` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  borrower_id INT UNSIGNED NOT NULL,
  branch_id INT UNSIGNED NOT NULL,
  status ENUM('Pending','Approved','Rejected','Disbursed','Closed') NOT NULL DEFAULT 'Pending',
  expected_amount BIGINT UNSIGNED NOT NULL,
  interest_rate_id INT UNSIGNED NOT NULL,
  processing_fee_pct INT NOT NULL COMMENT 'Service fee %',
  installments INT UNSIGNED NOT NULL,
  total_amount BIGINT UNSIGNED NOT NULL,
  emi_amount DECIMAL(12,2) NOT NULL,
  amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
  amount_remaining DECIMAL(12,2) AS (total_amount - amount_paid) STORED,
  current_installment INT UNSIGNED NOT NULL DEFAULT 0,
  remaining_installments INT UNSIGNED AS (installments - current_installment) STORED,
  next_due_date DATE NULL,
  document_path VARCHAR(255) NULL,
  approved_by INT UNSIGNED NULL,
  approved_at TIMESTAMP NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (borrower_id) REFERENCES tbl_borrower(id),
  FOREIGN KEY (branch_id) REFERENCES tbl_branch(id),
  FOREIGN KEY (interest_rate_id) REFERENCES tbl_interest_rate(id),
  FOREIGN KEY (approved_by) REFERENCES tbl_user(id)
) ENGINE=InnoDB;

-- Documents
CREATE TABLE `tbl_document` (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  borrower_id INT UNSIGNED NOT NULL,
  loan_application_id INT UNSIGNED NULL,
  doc_type VARCHAR(50) NOT NULL COMMENT 'e.g. Agreement, Proposal',
  file_path VARCHAR(255) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (borrower_id) REFERENCES tbl_borrower(id),
  FOREIGN KEY (loan_application_id) REFERENCES tbl_loan_application(id)
) ENGINE=InnoDB;

-- Credit Score History
CREATE TABLE `tbl_credit_score` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  borrower_id INT UNSIGNED NOT NULL,
  report_date DATE,
  score SMALLINT,
  remarks VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (borrower_id) REFERENCES tbl_borrower(id)
) ENGINE=InnoDB;


CREATE TABLE tbl_verification_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  borrower_id INT UNSIGNED NOT NULL,
  method ENUM('phone','email') NOT NULL,
  target VARCHAR(100) NOT NULL,
  code CHAR(6) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (borrower_id,method,target,code),
  FOREIGN KEY (borrower_id) REFERENCES tbl_borrower(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Liabilities / Collaterals
CREATE TABLE `tbl_liability` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  borrower_id INT UNSIGNED NOT NULL,
  loan_application_id INT UNSIGNED NOT NULL,
  property_name VARCHAR(255) NOT NULL,
  property_details TEXT NOT NULL,
  market_value BIGINT UNSIGNED NOT NULL,
  outstanding_loan_value BIGINT UNSIGNED NOT NULL,
  expected_return_value BIGINT UNSIGNED NOT NULL,
  valuation_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (borrower_id) REFERENCES tbl_borrower(id),
  FOREIGN KEY (loan_application_id) REFERENCES tbl_loan_application(id)
) ENGINE=InnoDB;

-- Guarantors
CREATE TABLE `tbl_guarantor` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_application_id INT UNSIGNED NOT NULL,
  name VARCHAR(100) NOT NULL,
  relationship VARCHAR(50) NOT NULL,
  contact VARCHAR(50) NOT NULL,
  nid BIGINT UNSIGNED NULL,
  address VARCHAR(255) NULL,
  FOREIGN KEY (loan_application_id) REFERENCES tbl_loan_application(id)
) ENGINE=InnoDB;

-- Repayment Schedule
CREATE TABLE `tbl_repayment_schedule` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_application_id INT UNSIGNED NOT NULL,
  installment_no INT UNSIGNED NOT NULL,
  due_date DATE NOT NULL,
  due_amount DECIMAL(12,2) NOT NULL,
  is_paid BOOLEAN NOT NULL DEFAULT FALSE,
  paid_date DATE NULL,
  FOREIGN KEY (loan_application_id) REFERENCES tbl_loan_application(id)
) ENGINE=InnoDB;

-- Payments
CREATE TABLE `tbl_payment` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  borrower_id INT UNSIGNED NOT NULL,
  loan_application_id INT UNSIGNED NOT NULL,
  schedule_id INT UNSIGNED NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_date DATE NOT NULL,
  installment_no INT UNSIGNED NOT NULL,
  remaining_installments INT UNSIGNED NOT NULL,
  fine_amount DECIMAL(12,2) DEFAULT 0,
  method VARCHAR(50) DEFAULT 'Cash',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (borrower_id) REFERENCES tbl_borrower(id),
  FOREIGN KEY (loan_application_id) REFERENCES tbl_loan_application(id),
  FOREIGN KEY (schedule_id) REFERENCES tbl_repayment_schedule(id)
) ENGINE=InnoDB;

-- Notifications
CREATE TABLE `tbl_notification` (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recipient_user INT UNSIGNED NULL,
  borrower_id INT UNSIGNED NULL,
  loan_application_id INT UNSIGNED NULL,
  type ENUM('SMS','EMAIL','PUSH'),
  message TEXT,
  is_read BOOLEAN DEFAULT FALSE,
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (recipient_user) REFERENCES tbl_user(id),
  FOREIGN KEY (borrower_id) REFERENCES tbl_borrower(id),
  FOREIGN KEY (loan_application_id) REFERENCES tbl_loan_application(id)
) ENGINE=InnoDB;

-- Risk Ratings
CREATE TABLE `tbl_risk_rating` (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_application_id INT UNSIGNED NOT NULL,
  rating_score SMALLINT,
  calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_application_id) REFERENCES tbl_loan_application(id)
) ENGINE=InnoDB;

-- Audit Logs
CREATE TABLE `tbl_audit_log` (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  entity VARCHAR(50),
  entity_id BIGINT UNSIGNED,
  changed_by INT UNSIGNED,
  change_type VARCHAR(20),
  change_data JSON,
  changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (changed_by) REFERENCES tbl_user(id)
) ENGINE=InnoDB;



CREATE TABLE `tbl_expenses`(
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description VARCHAR(255) NOT NULL,
  expense_amount DECIMAL(12,2) NOT NULL,
  branch_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES tbl_branch(id) ON DELETE CASCADE   
)

-- 1) Drop if it already exists
DROP TABLE IF EXISTS `tbl_capital`;
CREATE TABLE `tbl_capital` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `branch_id`       INT UNSIGNED NOT NULL UNIQUE,
  `amount`          DECIMAL(15,2) NOT NULL DEFAULT 0,
  `amount_used`     DECIMAL(15,2) NOT NULL DEFAULT 0,
  `amount_remaining` DECIMAL(15,2) AS (`amount` - `amount_used`) VIRTUAL,
  `entry_type`      ENUM('initial','topup','withdrawal') NOT NULL DEFAULT 'initial',
  `description`     VARCHAR(255) DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_capital_branch`
    FOREIGN KEY (`branch_id`) REFERENCES `tbl_branch`(`id`)
      ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Roles
INSERT INTO tbl_role (name, description) VALUES
('Branch Officer', 'Handles branch operations'),
('Head Officer', 'Manages all branches'),
('Verifier Officer', 'Verifies KYC and documents');

-- Branches
INSERT INTO tbl_branch (name, address) VALUES
('Dhaka Main', '123 Main Road, Dhaka'),
('Chittagong Branch', '456 Port Road, Chittagong');

-- Users (password_hash is for '123' using password_hash('123', PASSWORD_DEFAULT))
INSERT INTO tbl_user (name, email, password_hash, designation, role_id, branch_id)
VALUES
('Branch Officer', 'branch@gmail.com', '$2y$10$eImiTXuWVxfM37uY4JANjQ==', 'Branch Officer', 1, 1),
('Head Officer', 'head@gmail.com', '$2y$10$eImiTXuWVxfM37uY4JANjQ==', 'Head Officer', 2, 1),
('Verifier Officer', 'verifier@gmail.com', '$2y$10$eImiTXuWVxfM37uY4JANjQ==', 'Verifier Officer', 3, 2);

-- Borrowers
INSERT INTO tbl_borrower (name, nid, gender, mobile, email, dob, address, working_status, password_hash, selfie_path)
VALUES
('Rahim Uddin', 1234567890, 'Male', '01710000001', 'rahim@gmail.com', '1990-01-01', 'Dhaka', 'Employed', '$2y$10$eImiTXuWVxfM37uY4JANjQ==', 'selfies/rahim.jpg'),
('Karim Banu', 1234567891, 'Female', '01710000002', 'karim@gmail.com', '1992-02-02', 'Chittagong', 'Self-Employed', '$2y$10$eImiTXuWVxfM37uY4JANjQ==', 'selfies/karim.jpg');

-- Interest Rates
INSERT INTO tbl_interest_rate (annual_rate_percent, effective_date)
VALUES (12.50, '2024-01-01'), (13.00, '2025-01-01');

-- Loan Applications
INSERT INTO tbl_loan_application (borrower_id, branch_id, status, expected_amount, interest_rate_id, processing_fee_pct, installments, total_amount, emi_amount, amount_paid, current_installment, next_due_date, approved_by)
VALUES
(1, 1, 'Pending', 100000, 1, 2, 12, 112000, 9333.33, 0, 0, '2024-07-01', 2),
(2, 2, 'Pending', 50000, 2, 2, 6, 56000, 9333.33, 0, 0, '2024-07-01', 3);

-- Repayment Schedule (for first loan)
INSERT INTO tbl_repayment_schedule (loan_application_id, installment_no, due_date, due_amount)
VALUES
(1, 1, '2024-07-01', 9333.33),
(1, 2, '2024-08-01', 9333.33);

-- Documents
INSERT INTO tbl_document (borrower_id, loan_application_id, doc_type, file_path)
VALUES
(1, 1, 'Agreement', 'docs/agreement_1.pdf'),
(2, 2, 'Proposal', 'docs/proposal_2.pdf');

-- Credit Score
INSERT INTO tbl_credit_score (borrower_id, report_date, score, remarks)
VALUES
(1, '2024-06-01', 720, 'Good'),
(2, '2024-06-01', 650, 'Average');

-- Guarantors
INSERT INTO tbl_guarantor (loan_application_id, name, relationship, contact, nid, address)
VALUES
(1, 'Sultan Ahmed', 'Brother', '01710000003', 1234567892, 'Dhaka'),
(2, 'Fatema Begum', 'Mother', '01710000004', 1234567893, 'Chittagong');

-- Payments
INSERT INTO tbl_payment (borrower_id, loan_application_id, schedule_id, amount, payment_date, installment_no, remaining_installments, fine_amount, method)
VALUES
(1, 1, 1, 9333.33, '2024-07-01', 1, 11, 0, 'Cash'),
(2, 2, 2, 9333.33, '2024-07-01', 1, 5, 0, 'Cash');

-- Notifications
INSERT INTO tbl_notification (recipient_user, borrower_id, loan_application_id, type, message)
VALUES
(1, 1, 1, 'EMAIL', 'Your loan application is under review.'),
(2, 2, 2, 'SMS', 'Your payment is due soon.');

-- Risk Ratings
INSERT INTO tbl_risk_rating (loan_application_id, rating_score)
VALUES
(1, 80),
(2, 65);

-- Audit Logs
INSERT INTO tbl_audit_log (entity, entity_id, changed_by, change_type, change_data)
VALUES
('tbl_loan_application', 1, 2, 'UPDATE', '{"status":"Approved"}'),
('tbl_borrower', 2, 3, 'INSERT', '{"name":"Karim Banu"}');
-- Triggers & Procedures
DELIMITER $$

CREATE PROCEDURE sp_generate_schedule(IN p_loan_id INT)
BEGIN
  DECLARE i INT DEFAULT 1;
  DECLARE n INT;
  DECLARE emi DECIMAL(12,2);
  SELECT installments, emi_amount INTO n, emi FROM tbl_loan_application WHERE id=p_loan_id;
  WHILE i<=n DO
    INSERT INTO tbl_repayment_schedule(loan_application_id,installment_no,due_date,due_amount)
    VALUES (p_loan_id, i, DATE_ADD(CURDATE(), INTERVAL i MONTH), emi);
    SET i = i + 1;
  END WHILE;
END$$

CREATE TRIGGER trg_after_loan_approve
AFTER UPDATE ON tbl_loan_application
FOR EACH ROW
BEGIN
  IF NEW.status='Approved' AND OLD.status<>'Approved' THEN
    CALL sp_generate_schedule(NEW.id);
  END IF;
END$$

DELIMITER ;

-- Final settings
SET SQL_MODE='STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION';
COMMIT;