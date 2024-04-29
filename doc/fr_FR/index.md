Description
===

1.1. Doorbird, portier connecté
===
Ce plugin permet d’interconnecter Jeedom avec un portier Doorbird.

Il permet deux types d’interconnexions :

On peut être notifier des 3 types d’évènements détectés par Doorbird : mouvement, sonnerie, ouverture

Jeedom peut déclencher l’ouverture de porte et l’éclairage

Configuration
===

1.2 Configuration du plugin
===

Le plugin ne comporte pas de configuration générale.

Il faut configurer l’adresse, l’utilisateur et le mot de passe du Doorbird dans l’équipement.

FAQ
===

Est-ce que le plugin s’appuie sur des API tiers ?
Non, le plugin est en connexion directe avec le Doorbird

Est-ce qu’il est possible de récupérer le flux vidéo de Doorbird ?
Oui , avec plugin Camera de Jeedom ou directement dans le plugin Doorbird V2.

Il y a également un widget fourni par Doorbird accessible sur :

http://<deviceip>/bha-api/view.html

Comment ouvrir le flux vidéo sur déclenchement d’alerte du Doorbird ?
C’est possible avec le plugin Clink par exemple.

Dans Clink vous configurer un équipement avec un modal sur le flux vidéo ou le widget (voir les liens plus haut)

Et dans un scénario, sur déclenchement de la détection de mouvement vous actionnez l’action Clink

Troubleshoting
===

Je n’ai pas d’informations qui remontent
Il faut bien vérifier que votre Doorbird est en version supérieure à 000091
