CREATE TABLE IF NOT EXISTS service_accounts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL,
  password_value VARCHAR(255) NULL,
  buyer VARCHAR(160) NULL,
  service VARCHAR(160) NULL,
  status ENUM('empty','sold','pending') NOT NULL DEFAULT 'empty',
  paid TINYINT(1) NOT NULL DEFAULT 0,
  price_cents INT NULL,
  renewal_date DATE NULL,
  notes TEXT NULL,
  custom_fields JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_service_accounts_email(email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE service_accounts ADD COLUMN IF NOT EXISTS password_value VARCHAR(255) NULL AFTER email;
ALTER TABLE service_accounts ADD COLUMN IF NOT EXISTS paid TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
ALTER TABLE service_accounts ADD COLUMN IF NOT EXISTS price_cents INT NULL AFTER paid;
ALTER TABLE service_accounts ADD COLUMN IF NOT EXISTS renewal_date DATE NULL AFTER price_cents;
ALTER TABLE service_accounts ADD COLUMN IF NOT EXISTS custom_fields JSON NULL AFTER notes;

UPDATE service_accounts SET password_value='Juanra09' WHERE email='francisco.ibanez10@gmail.com' AND (password_value IS NULL OR password_value='');
UPDATE service_accounts SET password_value='Sokani3912' WHERE email='normankaminski@gmx.de' AND (password_value IS NULL OR password_value='');
UPDATE service_accounts SET password_value='Juanra10' WHERE email='ernestfrancisco7@gmail.com' AND (password_value IS NULL OR password_value='');
UPDATE service_accounts SET password_value='Maikelsi234' WHERE email='florian.flasbeck@gmx.de' AND (password_value IS NULL OR password_value='');
UPDATE service_accounts SET password_value='Juanra12' WHERE email IN ('perokdices88+89@gmail.com','melanie.pracht@gmx.net') AND (password_value IS NULL OR password_value='');
UPDATE service_accounts SET password_value='Giulia15@4!' WHERE email='califfo78@gmail.com' AND (password_value IS NULL OR password_value='');
