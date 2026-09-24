=== HindBoutik Core ===
Contributors: webpixelia
Requires at least: 5.9
Tested up to: 6.6
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Plugin généraliste pour HindBoutik. Regroupe tous les mu-plugins, snippets et le widget WhatsApp. Remplace ACF par des méta natives WordPress.

== Description ==

HindBoutik Core est un plugin tout-en-un qui centralise l'ensemble des fonctionnalités personnalisées de la boutique en ligne HindBoutik.
Il remplace ACF (Advanced Custom Fields) par des solutions natives WordPress (méta personnalisés, pages d'options Settings API).

Fonctionnalités :
* Guide des tailles (modal)
* Détails produit (onglets)
* Carousel cross-sell sur la page panier
* Grille d'images produit
* Boutons +/- quantité WooCommerce
* Slider d'images produit (Slick)
* Icônes panier/favoris dans les menus
* Badge Klarna
* Read-more pour les catégories produits
* Badges de vente personnalisés
* Menu de compte WooCommerce réorganisé
* Barre de progressivité livraison gratuite
* Badges de couleur pour variations
* Colonne d'état TVA dans l'admin
* Label "OFFERT" pour points relais
* Widget WhatsApp flottant
* Barre d'achat collante (mobile) sur la fiche produit
* Tiroir panier (cart drawer) avec ajout au panier ajaxifié

== Installation ==

1. Uploadez le dossier `hindboutik-core` dans `/wp-content/plugins/`
2. Activez le plugin depuis le tableau de bord WordPress
3. La migration ACF s'exécute automatiquement à l'activation
4. Configurez le widget WhatsApp depuis HindBoutik → Réglages

== Changelog ==

= 1.3.0 =
* Nouveau réglage (HindBoutik → Fonctionnalités) pour désactiver l'icône panier flottante au scroll, indépendamment du reste du tiroir panier
* Amélioration de l'affichage des informations de taille selon la configuration de la catégorie produit
* Affiche « Taille » avec le bouton du guide et la modale lorsque la catégorie est sélectionnée dans le guide et que le produit n'est pas à taille unique
* Affiche « Taille » avec « Taille unique » ou le texte personnalisé configuré lorsque la catégorie est sélectionnée et que le produit est à taille unique
* Masque entièrement la section de taille lorsque la catégorie n'est pas sélectionnée dans le guide, quelle que soit la configuration de taille unique

= 1.2.0 =
* Nouvelle feature : tiroir panier (cart drawer)
* Ajaxifie l'ajout au panier de la fiche produit (endpoint natif wc-ajax=add_to_cart), le site n'ayant pas d'AJAX sur cet ajout jusqu'ici
* Ouverture à l'ajout d'un article, ou au clic sur l'icône panier du header
* Icône panier du header rendue flottante au scroll (pas de duplication, simple classe CSS/JS) — le header du site n'étant pas sticky
* Fermeture par croix, clic sur le voile, ou touche Échap
* Jauge de livraison offerte réutilisant le seuil de FreeShippingProgressFeature
* Suggestion de complément (1 à 2 articles) réutilisant le moteur de CrosssellCarouselFeature

= 1.1.0 =
* Nouvelle feature : barre d'achat collante (mobile) sur la fiche produit
* Apparaît quand le bouton d'ajout au panier sort du viewport
* Libellé adapté à l'état de la sélection de variation (aucune / valide / rupture)
* Rappel du nom, du coloris/taille sélectionné et du prix
* Sans sélection : remontée et surlignage bref de la zone de variations, sans message d'erreur
* En rupture : bouton inactif "Épuisé" + nombre d'alternatives encore disponibles

= 1.0.0 =
* Première version générique regroupant tous les mu-plugins et snippets
* Migration ACF → méta natives
* Widget WhatsApp intégré
* Plugin Settings API pour "Guide tailles" et "Top bar"