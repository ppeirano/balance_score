CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    responsable_id INT DEFAULT NULL,
    perfil ENUM('admin','consultor') DEFAULT 'consultor',
    activo TINYINT(1) DEFAULT 1,
    ultimo_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (responsable_id) REFERENCES responsables(id) ON DELETE SET NULL
);

-- Usuario admin inicial (password: admin123)
INSERT INTO usuarios (email, password_hash, perfil) VALUES
('admin@temislostalo.com', '$2y$12$96P1sqEsOGofZnkHmzjSjOv.oJY6aMw4W1nOumQV7GGhWiIA7vaSe', 'admin');
