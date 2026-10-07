# Les deux web

Site vitrine de **Les deux web**, studio de création de sites internet basé à Limoges.
Site en une page (maquette Figma), formulaire de contact, back-office pour les offres et les demandes.

**Stack** : Symfony 7.4 · PHP ≥ 8.2 · MySQL 8 · Twig · AssetMapper (sans Node) · EasyAdmin · hébergement OVH.

- Mise en ligne et mises à jour : **[docs/DEPLOIEMENT-OVH.md](docs/DEPLOIEMENT-OVH.md)**
- Identité du site (coordonnées, infos légales, associés, mention TVA) : **[config/packages/site.yaml](config/packages/site.yaml)**
- Textes de la page : **[translations/messages.fr.yaml](translations/messages.fr.yaml)**
- Charte graphique (couleurs, polices, espacements) : section 2 de **[assets/styles/app.css](assets/styles/app.css)**

---

## Installation locale

Prérequis : PHP 8.2+ (extensions `intl`, `pdo_mysql`), [Composer](https://getcomposer.org), Docker Desktop.

```bash
composer install
docker compose up -d                          # MySQL (port 3307) + Mailpit
php bin/console doctrine:migrations:migrate   # crée les tables
php bin/console doctrine:fixtures:load        # offres, options et admin de démo
php -S 127.0.0.1:8000 -t public               # serveur web
```

| Adresse | Contenu |
|---|---|
| http://127.0.0.1:8000 | Le site |
| http://127.0.0.1:8000/admin | Back-office — `admin@lesdeuxweb.test` / `admin-dev-lesdeuxweb` |
| http://localhost:8025 | Mailpit : les emails envoyés en local (aucun ne part réellement) |

> Le dossier du projet ne doit pas être synchronisé par iCloud Drive (copies « fichier 2 » qui cassent le site).
> Placez-le hors de `Documents`, ou nommez le dossier `….nosync`.

---

## Commandes utiles

| Commande | Rôle |
|---|---|
| `php bin/phpunit` | Lancer les tests (base de test séparée, remise à zéro à chaque test) |
| `php bin/console app:create-admin email@exemple.fr` | Créer un compte admin (mot de passe demandé, 12 caractères min.) |
| `php bin/console app:initialiser-offres` | Remplir une base vide avec la grille tarifaire de lancement |
| `php bin/console app:purger-messages [--simulation]` | Supprimer les messages de plus de 3 ans (RGPD) |
| `php bin/console doctrine:fixtures:load` | Recharger les données de démo (**efface la base de dev**) |
| `php bin/console make:migration` | Générer une migration après modification d'une entité |
| `php bin/console cache:clear` | Vider le cache |

Première base de test (une seule fois) :

```bash
php bin/console --env=test doctrine:database:create
php bin/console --env=test doctrine:migrations:migrate -n
```

---

## Organisation du code

```
src/
├── Controller/        HomeController (page unique + formulaire), PageController (pages légales),
│                      RobotsController, SecurityController, Admin/ (EasyAdmin)
├── Entity/            Offre, OptionTarifaire, MessageContact, User
├── Data/              GrilleTarifaire (offres de lancement)
├── Form/              ContactType
├── Service/           ContactService (enregistrement + emails)
├── Command/           app:create-admin, app:initialiser-offres, app:purger-messages
└── EventListener/     slug unique, sitemap
templates/             home/ (page unique), components/ (Logo, Bouton, CarteOffre, CartePack…),
                       partials/, contact/, emails/, page/ (légal), form/ (thème accessible)
assets/                styles/, fonts/ (auto-hébergées), images/logo/, controllers/ (menu mobile)
bin/deployer           Script de déploiement OVH
bin/purger-messages.php  Tâche planifiée OVH
```

## Variables d'environnement

Définies dans `.env` (valeurs de développement) et surchargées par `.env.local` (jamais commité).

| Variable | Rôle |
|---|---|
| `APP_ENV`, `APP_SECRET` | Environnement (`dev` / `prod`) et clé secrète |
| `DATABASE_URL` | Connexion MySQL |
| `MAILER_DSN` | Serveur d'envoi des emails (Mailpit en dev, SMTP OVH en prod) |
| `CONTACT_EMAIL_TO` | Adresse qui reçoit les demandes du formulaire |
| `MAILER_FROM` | Expéditeur des emails |
| `MATOMO_URL`, `MATOMO_SITE_ID` | Mesure d'audience Matomo, facultative (sans cookie) : rien n'est chargé si vide |

## Principes

- **RGPD** : aucun cookie sur les pages publiques, aucune ressource externe (polices auto-hébergées),
  messages purgés après 3 ans, pas de bandeau cookies nécessaire.
- **Accessibilité** : contrastes AA vérifiés, navigation clavier, lien d'évitement, formulaires annotés.
- **Anti-spam** du formulaire : CSRF, champ piège (honeypot), 5 envois par heure et par IP. Pas de reCAPTCHA.
- **Réutilisable** pour un client : changer `site.yaml`, la section 2 d'`app.css`, les textes et le logo.
