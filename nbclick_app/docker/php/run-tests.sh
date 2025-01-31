#!/bin/bash

# Démarrer les tests PHPUnit
echo "Running PHPUnit tests..."

# Exécuter PHPUnit sur le dossier des tests
php vendor/bin/phpunit --configuration phpunit.xml.dist