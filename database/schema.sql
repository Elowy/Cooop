-- Net-Trade Hungary – adatbázis séma (MySQL / MariaDB)

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id    INT AUTO_INCREMENT PRIMARY KEY,
    slug  VARCHAR(80) NOT NULL UNIQUE,
    name  VARCHAR(120) NOT NULL,
    icon  VARCHAR(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    slug        VARCHAR(140) NOT NULL UNIQUE,
    name        VARCHAR(200) NOT NULL,
    short       VARCHAR(300) DEFAULT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit        VARCHAR(20) NOT NULL DEFAULT 'db',
    image       VARCHAR(140) DEFAULT 'placeholder.svg',
    stock       INT NOT NULL DEFAULT 0,
    featured    TINYINT(1) NOT NULL DEFAULT 0,
    active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orders (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(160) NOT NULL,
    email       VARCHAR(160) NOT NULL,
    phone       VARCHAR(40) DEFAULT NULL,
    zip         VARCHAR(20) DEFAULT NULL,
    address     VARCHAR(255) DEFAULT NULL,
    note        TEXT,
    total       DECIMAL(10,2) NOT NULL DEFAULT 0,
    status      VARCHAR(30) NOT NULL DEFAULT 'new',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS order_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    order_id   INT NOT NULL,
    product_id INT DEFAULT NULL,
    name       VARCHAR(200) NOT NULL,
    price      DECIMAL(10,2) NOT NULL,
    qty        INT NOT NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id)
        REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
