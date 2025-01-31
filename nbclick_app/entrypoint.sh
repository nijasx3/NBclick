#!/bin/sh
set -e

echo "Démarrage de l'entrée du script..."

# Attendre que MySQL soit prêt avec un timeout
MAX_ATTEMPTS=30
attempt=0

while [ $attempt -lt $MAX_ATTEMPTS ]; do
    if mysqladmin ping -h"db" -P"3306" --silent; then
        echo "MySQL est prêt."
        break
    else
        echo "En attente de MySQL (tentative $((attempt+1))/$MAX_ATTEMPTS)..."
        sleep 5
        attempt=$((attempt+1))
    fi
done

if [ $attempt -eq $MAX_ATTEMPTS ]; then
    echo "Erreur : Impossible de se connecter à MySQL après $MAX_ATTEMPTS tentatives"
    exit 1
fi

# Créer la base de données
echo "Création de la base de données..."
php bin/console doctrine:database:create --if-not-exists || {
    echo "Erreur lors de la création de la base de données"
    exit 1
}

# Exécuter les migrations
echo "Exécution des migrations..."
php bin/console doctrine:migrations:migrate --no-interaction || {
    echo "Erreur lors de l'exécution des migrations"
    exit 1
}

echo "Migrations terminées avec succès. Démarrage de PHP-FPM..."

# Démarrer PHP-FPM en arrière-plan
php-fpm &

# Attendre que PHP-FPM soit prêt
until nc -z -v -w30 app 9000; do
    echo "En attente de PHP-FPM..."
    sleep 1
done

# Démarrer Nginx
echo "Démarrage de Nginx..."
nginx -g "daemon off;"
