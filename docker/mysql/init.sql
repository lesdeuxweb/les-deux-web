-- Exécuté une seule fois, à la création du volume MySQL.
-- Donne à l'utilisateur de dev l'accès à la base de test (lesdeuxweb_test).
GRANT ALL PRIVILEGES ON `lesdeuxweb%`.* TO 'app'@'%';
FLUSH PRIVILEGES;
