
## **Guide de déploiement de l’application**

Tout d’abord il faut cloner le dépot git depuis le terminal pour pouvoir avoir l’application : 
<br>
	-en shh : **git clone git@gitlab-ce.iut.u-bordeaux.fr:ykadri/s3.01_equipe6.git**
<br>
	-en http : **https://gitlab-ce.iut.u-bordeaux.fr/ykadri/s3.01_equipe6.git**

Une fois cela fait, vous allez dans le fichier qui a était crée, en ensuite il fait allons dans le dossier **NetSeries** qui est l’application (**cd s3.01_equipe6/NetSeries**)

Il faut maintenant installer les composants qu’il faut avoir pour pouvoir démarrer l'application correctement dans cet ordre : 
<br>
	1) symfony composer install <br>
	2) composer require knplabs/knp-paginator-bundle

Pour finir, pour démarrer l'application, toujours à l’aide du terminal et depuis le dossier **NetSeries**, il faut exécuter cette commande : 
	-**symfony serve**

Une fois cela fait, il faut aller dans votre navigation internet et tapper via l’url : **127.0.0.1:8000** (pour l’instant plus précisément **127.0.0.1:8000/series**)

et votre application est fonctionnelle ! 

