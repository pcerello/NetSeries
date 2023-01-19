
## **Guide de déploiement de l’application**

Tout d’abord il faut cloner le dépot git depuis le terminal pour pouvoir avoir l’application : 
<br>
	-en shh : **git clone git@gitlab-ce.iut.u-bordeaux.fr:ykadri/s3.01_equipe6.git**
<br>
	-en http : **https://gitlab-ce.iut.u-bordeaux.fr/ykadri/s3.01_equipe6.git**

Une fois cela fait, vous allez dans le fichier qui a était crée, en ensuite il fait allons dans le dossier **NetSeries** qui est l’application (**cd s3.01_equipe6/NetSeries**)

Il faut maintenant installer les composants qu’il faut avoir pour pouvoir démarrer l'application correctement dans cet ordre : 
<br>

```symfony composer instal ```

```composer require knplabs/knp-paginator-bundle ```

```symfony composer require victor-prdh/recaptcha-bundle```

```symfony composer require fzaninotto/faker``` 

```symfony console doctrine:schema:update --force```

```sudo apt-get install php-curlch```

Pour finir, pour démarrer l'application, toujours à l’aide du terminal et depuis le dossier **NetSeries**, il faut exécuter cette commande : 
	-**symfony serve**

Une fois cela fait, il faut aller dans votre navigation internet et tapper via l’url : **127.0.0.1:8000** (pour l’instant plus précisément **127.0.0.1:8000/series**)

**et votre application est fonctionnelle !**

### **Guide pour un admin**
<h3>Comment nommer le premier administrateur du site (avant, inscrivez un nouvel utilisateur qui sera admin) ? </h3>


Avec **phpmyadmin** : 

-Connectez vous à votre base
-Sélectionnez la base de données shows
-Allez dans l’onglet sql, faite : update user set admin=1 where user.email = ‘email de l'utilisateur’; 

**Et votre utilisateur est un administrateur.** 

Pour pouvoir vérifier les normes : 
https://marketplace.visualstudio.com/items?itemName=DEVSENSE.phptools-vscode
Mettre cette ligne de commande (en ayant les droits admin)
phpcs config --colors --standard=PSR12 --warning-serverity=6


Pour mettre en vue un épisode, cela mettra automatiquement tous les épisodes précédents en vue.

Pour la génération d’utilisateur, il faudra avant (Si vous avez déjà généré) supprimer dans la base de données tous les utilisateurs généré dans la table “user”, car il est possible que l’application va générer des mails déjà existants.


### **Création des index depuis la base de données pour rendre le filtre des séries plus rapide :**

-Dans phpmyadmin, dans l'onglet SQL de votre base, faites ces requêtes : 
```
create index index_attributs_trie on series (title, year_start, year_end);

create index index_attributs_trie_user on user (email, register_date, last_activity_at);
```
	
Pour la génération de critique, le nombre de critiques à générer est limité par le nombre de séries qui existe dans la base.


Lancé un test unitaire : 
		```./vendor/bin/phpunit```


