# Mise en ligne sur OVH (hébergement Pro)

Ce guide décrit la **première mise en ligne** du site, puis les **mises à jour**.
L'offre Pro inclut l'accès SSH et Git : tout se fait sur le serveur, à partir du dépôt GitHub.

Durée : environ 1 h la première fois (hors propagation DNS), puis 2 minutes par mise à jour.

Dans ce guide, remplacez :

| Exemple | Par |
|---|---|
| `lesdeuxweb.fr` | votre nom de domaine |
| `lesdeuxweb/les-deux-web` | `compte-github/nom-du-depot` |
| `identifiant-ssh`, `ssh.clusterXXX.hosting.ovh.net` | les valeurs de votre espace client |

---

## 1. Dans l'espace client OVH

### 1.1 Créer la base de données

**Hébergements → votre hébergement → Bases de données → Créer une base de données**

- Type : **MySQL**, version **8.0**
- Notez : le **serveur** (ex. `xxxxx.mysql.db`), le **nom de la base**, l'**utilisateur** et le **mot de passe**.

### 1.2 Créer l'adresse email du site

**Emails → votre domaine → Créer une adresse** : `bonjour@lesdeuxweb.fr`

Notez son mot de passe : le site l'utilise pour envoyer les emails du formulaire de contact.

### 1.3 Activer l'accès SSH

**Hébergements → votre hébergement → FTP - SSH**

- Notez l'**identifiant** et le **serveur SSH** (ex. `ssh.cluster031.hosting.ovh.net`).
- Définissez un mot de passe si besoin (« Modifier le mot de passe »).

---

## 2. Sur le serveur, en SSH

Connectez-vous depuis le Terminal de votre Mac :

```bash
ssh identifiant-ssh@ssh.clusterXXX.hosting.ovh.net
```

### 2.1 Choisir la version de PHP (une seule fois)

Le dépôt contient un fichier `.ovhconfig` (PHP 8.3). Il doit se trouver à la racine de l'hébergement.
On récupère d'abord le code (étape suivante), puis on le copie.

### 2.2 Autoriser le serveur à lire le dépôt GitHub (une seule fois)

Le dépôt est privé : on crée une **clé de déploiement** en lecture seule.

```bash
ssh-keygen -t ed25519 -f ~/.ssh/github_les_deux_web -N "" -C "deploiement OVH"
cat ~/.ssh/github_les_deux_web.pub
```

Copiez la ligne affichée, puis sur GitHub : **dépôt → Settings → Deploy keys → Add deploy key**
(titre « OVH », coller la clé, **ne pas** cocher « Allow write access »).

Indiquez au serveur d'utiliser cette clé pour GitHub :

```bash
cat >> ~/.ssh/config <<'FIN'
Host github.com
    IdentityFile ~/.ssh/github_les_deux_web
    IdentitiesOnly yes
FIN
chmod 600 ~/.ssh/config
```

### 2.3 Récupérer le code

```bash
cd ~
git clone git@github.com:lesdeuxweb/les-deux-web.git les-deux-web
cp ~/les-deux-web/.ovhconfig ~/.ovhconfig
```

Déconnectez-vous (`exit`), reconnectez-vous, puis vérifiez la version de PHP :

```bash
php -v        # doit afficher PHP 8.3
```

### 2.4 Créer le fichier de configuration secret `.env.local`

Ce fichier contient les mots de passe : il reste **uniquement sur le serveur**, jamais dans Git.

Générez d'abord une clé secrète :

```bash
php -r 'echo bin2hex(random_bytes(16)), PHP_EOL;'
```

Puis créez le fichier :

```bash
nano ~/les-deux-web/.env.local
```

```dotenv
APP_ENV=prod
APP_SECRET=collez-ici-la-cle-generee

# Base de données (étape 1.1)
DATABASE_URL="mysql://UTILISATEUR:MOT_DE_PASSE@SERVEUR.mysql.db:3306/NOM_DE_LA_BASE?serverVersion=8.0.32&charset=utf8mb4"

# Envoi des emails par la boîte OVH (étape 1.2) — le « @ » de l'identifiant s'écrit %40
MAILER_DSN="smtp://bonjour%40lesdeuxweb.fr:MOT_DE_PASSE_EMAIL@ssl0.ovh.net:465"

# Adresse qui reçoit les demandes du formulaire, et expéditeur des emails
CONTACT_EMAIL_TO=bonjour@lesdeuxweb.fr
MAILER_FROM="Les deux web <bonjour@lesdeuxweb.fr>"
```

Enregistrez avec `Ctrl+O`, `Entrée`, puis quittez avec `Ctrl+X`.

> **Mots de passe avec caractères spéciaux** : dans `DATABASE_URL` et `MAILER_DSN`, les caractères
> `@ : / ? # % &` doivent être encodés (`@` → `%40`, `#` → `%23`, `%` → `%25`, `&` → `%26`, `/` → `%2F`, `:` → `%3A`).
> Le plus simple : choisir des mots de passe composés de lettres et de chiffres uniquement.

### 2.5 Installer le site

```bash
cd ~/les-deux-web
bin/deployer
```

Le script installe les dépendances, compile les styles et scripts, crée les tables de la base et vide le cache.
Il s'arrête à la première erreur en l'affichant.

### 2.6 Remplir les offres et créer votre compte admin (une seule fois)

```bash
php bin/console app:initialiser-offres --env=prod
php bin/console app:create-admin bonjour@lesdeuxweb.fr --env=prod
```

La première commande crée la grille tarifaire de lancement (5 offres, 3 packs, 6 options).
Ensuite, les offres se modifient dans l'admin (`/admin`).

---

## 3. Brancher le domaine

### 3.1 Pointer le domaine vers le dossier `public/`

**Hébergements → votre hébergement → Multisite → Ajouter un domaine**

- Domaine : `lesdeuxweb.fr`, cochez « Créer également www.lesdeuxweb.fr »
- **Dossier racine : `les-deux-web/public`** ← important : seul le dossier `public/` doit être accessible
- Cochez **SSL**

La prise en compte prend quelques minutes (jusqu'à quelques heures si le domaine vient d'être créé).

### 3.2 Activer HTTPS

**Hébergements → votre hébergement → Informations générales → Certificat SSL → Commander / Regénérer** (Let's Encrypt, gratuit).

Quand `https://lesdeuxweb.fr` fonctionne, **forcez le HTTPS** : sur votre Mac, dans `public/.htaccess`,
décommentez les 3 lignes sous « forcer HTTPS », puis :

```bash
git commit -am "Activation de la redirection HTTPS" && git push
```

et sur le serveur : `cd ~/les-deux-web && bin/deployer`.

---

## 4. Tâche planifiée (suppression des messages de plus de 3 ans)

La politique de confidentialité annonce une conservation de 3 ans : cette tâche l'applique.

**Hébergements → votre hébergement → Tâches planifiées - Cron → Ajouter une planification**

- Commande à exécuter : `les-deux-web/bin/purger-messages.php`
- Langage : **PHP 8.3**
- Fréquence : **tous les jours** (par exemple à 3 h)
- Activer les notifications par email en cas d'erreur

---

## 5. Checklist de mise en ligne

**Contenu obligatoire** (`config/packages/site.yaml`, puis commit + `bin/deployer`)

- [ ] Raison sociale, forme juridique, SIRET, adresse, directeur de la publication (mentions légales : obligatoire)
- [ ] Date de mise à jour de la politique de confidentialité (`politique_mise_a_jour`)
- [ ] Téléphone, prénoms et photos des associés, photo du hero (facultatif)

**Vérifications sur le site en ligne**

- [ ] `https://lesdeuxweb.fr` s'affiche, le cadenas HTTPS est présent, `http://` redirige vers `https://`
- [ ] `https://lesdeuxweb.fr/mentions-legales` et `/confidentialite` s'affichent (sinon : le dossier racine n'est pas `public/`)
- [ ] `https://lesdeuxweb.fr/.env` renvoie une erreur 404 (le fichier ne doit **jamais** être accessible)
- [ ] Envoyer une demande avec le formulaire : l'email de notification arrive sur `bonjour@lesdeuxweb.fr`, l'accusé de réception sur l'adresse saisie (vérifier les spams)
- [ ] Le message apparaît dans `/admin` → Messages
- [ ] Connexion à `/admin` avec votre compte
- [ ] `https://lesdeuxweb.fr/robots.txt` et `/sitemap.xml` s'affichent avec le bon domaine
- [ ] Test Lighthouse (Chrome → outils de développement → Lighthouse) : objectif ≥ 90 partout
- [ ] Aucune barre de debug Symfony en bas des pages

**Référencement**

- [ ] Déclarer le site dans [Google Search Console](https://search.google.com/search-console) et y soumettre `https://lesdeuxweb.fr/sitemap.xml`
- [ ] Créer la fiche Google Business Profile (adresse à Limoges, zone desservie)

---

## 6. Mettre à jour le site

Sur votre Mac, après vos modifications :

```bash
git add -A && git commit -m "Description de la modification" && git push
```

Sur le serveur :

```bash
ssh identifiant-ssh@ssh.clusterXXX.hosting.ovh.net
cd ~/les-deux-web && bin/deployer
```

> Si vous modifiez `.env.local` sur le serveur, relancez `bin/deployer` : il recompile la configuration (`.env.local.php`).

---

## 7. En cas de problème

| Symptôme | Piste |
|---|---|
| Erreur 500 / page « Une erreur est survenue » | Lire le journal : `tail -n 50 ~/les-deux-web/var/log/prod-$(date +%F).log` |
| Seul l'accueil fonctionne, les autres pages font 404 | Le dossier racine du multisite n'est pas `les-deux-web/public`, ou `public/.htaccess` est absent |
| Mise en page cassée (pas de styles) | Relancer `bin/deployer` (étape « Assets ») |
| `php -v` n'affiche pas 8.3 | Vérifier que `~/.ovhconfig` existe, puis se reconnecter en SSH |
| Les emails ne partent pas | Vérifier `MAILER_DSN` (identifiant `bonjour%40lesdeuxweb.fr`, mot de passe de la boîte, `ssl0.ovh.net:465`) ; les messages restent enregistrés dans l'admin et l'erreur est dans le journal |
| `git pull` refuse de se faire | Un fichier a été modifié sur le serveur : `git status`, puis `git checkout -- <fichier>` (les modifications se font sur le Mac, jamais sur le serveur) |
| « Trop de tentatives » à la connexion admin | Attendre 15 minutes (protection contre les attaques par force brute) |
