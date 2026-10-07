<?php

/*
 * Tâche planifiée OVH : supprime les messages de contact de plus de 3 ans (RGPD).
 *
 * Les tâches planifiées des hébergements mutualisés OVH exécutent un fichier PHP,
 * pas une ligne de commande : ce script lance « php bin/console app:purger-messages ».
 * Configuration : espace client OVH > Hébergement > Tâches planifiées - Cron,
 * script « les-deux-web/bin/purger-messages.php », langage PHP 8.3, une fois par jour.
 */

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

$_SERVER['argv'] = [__FILE__, 'app:purger-messages', '--no-interaction'];

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Application(new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']));
};
