-- # create databases
CREATE DATABASE IF NOT EXISTS `isibet`;

-- # create root user and grant rights
CREATE USER 'homestead'@'localhost' IDENTIFIED BY 'homestead';
GRANT ALL PRIVILEGES ON *.* TO 'homestead'@'%';

-- GRANT ALL PRIVILEGES ON *.* TO 'homestead'@'%'; 
-- ALTER USER 'homestead'@'%';
-- FLUSH PRIVILEGES;
