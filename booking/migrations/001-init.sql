SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS events (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type ENUM('dinner', 'tasting') NOT NULL,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    capacity SMALLINT UNSIGNED NOT NULL,
    booked SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    price_per_guest INT UNSIGNED NOT NULL COMMENT 'в копейках',
    duration_minutes SMALLINT UNSIGNED DEFAULT NULL,
    status ENUM('active', 'closed', 'sold_out') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_type_date (type, event_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_id INT UNSIGNED NOT NULL,
    guests SMALLINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(32) NOT NULL,
    email VARCHAR(255) NOT NULL,
    comment TEXT,
    dietary VARCHAR(500) DEFAULT NULL COMMENT 'JSON-массив для ужинов',
    total_amount INT UNSIGNED NOT NULL COMMENT 'в копейках',
    status ENUM('pending', 'paid', 'cancelled', 'refunded', 'expired') NOT NULL DEFAULT 'pending',
    payment_id VARCHAR(128) DEFAULT NULL,
    payment_provider VARCHAR(32) DEFAULT NULL,
    booking_token VARCHAR(64) NOT NULL UNIQUE COMMENT 'для ссылок отмены/просмотра',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    paid_at DATETIME DEFAULT NULL,
    expires_at DATETIME NOT NULL COMMENT 'до этого момента место зарезервировано',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE RESTRICT,
    INDEX idx_status (status),
    INDEX idx_expires (expires_at),
    INDEX idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(64) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip VARCHAR(45) NOT NULL,
    action VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_action_time (ip, action, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
