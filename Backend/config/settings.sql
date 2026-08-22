-- Site Settings Table
USE wangari_db;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default settings
INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES
('farm_name', 'Wangari Farm'),
('farm_email', 'info@imeantech.com'),
('farm_phone', '+254 114 971 070'),
('farm_address', 'Wangari, Kenya'),
('currency', 'KES'),
('mpesa_shortcode', '174379'),
('mpesa_passkey', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
('enable_registration', '1'),
('maintenance_mode', '0');
