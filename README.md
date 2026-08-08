# HindBoutik Core

> **Plugin modulaire WordPress / WooCommerce fournissant des fonctionnalités personnalisées et des composants réutilisables pour HindBoutik.**

## Objectif

Centraliser **tous** les mu-plugins, snippets et widgets tiers dans un seul plugin structuré, et **remplacer ACF** par des solutions WordPress natives (méta personnalisés, Settings API).

## Architecture

hindboutik-core/
├── hindboutik-core.php       # Bootstrap + PSR-4 autoloader
├── app/
│   ├── Core/                 # Infrastructure
│   ├── Acf/                  # Remplacement ACF (MetaApi, MetaManager, Migration)
│   ├── Admin/                # Pages d'options WordPress
│   ├── Features/             # Classes métier (1 feature = 1 classe)
│   └── Helpers/              # Utilities
├── assets/                   # JS + CSS (admin & frontend)
├── templates/                # Templates PHP (rendering)
├── includes/
│   └── helpers.php           # Fonctions globales
└── languages/                # Traductions


### Principes de design

| Principe | Application |
|----------|------------|
| **Une classe par feature** | Chaque `Features/XxxFeature.php` est autonome |
| **Séparation logique / rendu** | Les classes métier utilisent `TemplateLoader` pour le rendu |
| **PSR-4** | Namespace `HindBoutik\\…` → répertoire `app/...` |
| **Settings API native** | Pages d'options via `register_setting()` / `add_settings_section()` |
| **Méta natives** | `register_meta()` + `show_in_rest => true` |
| **Migration idempotente** | Safe re-run, vérifie l'existence avant d'écrire |

### Remplacement ACF → méta natives

| ACF field name | Contexte | Méta native | Storage |
|---------------|----------|-------------|---------|
| `description` | produit | `hindboutik_product_description` | postmeta |
| `taille_unique` | produit | `hindboutik_product_taille_unique` | postmeta |
| `texte_taille_unique` | produit | `hindboutik_product_texte_taille_unique` | postmeta |
| `category_background` | product_cat | `hindboutik_category_background` | termmeta |
| `categorie_de_produit` | option | `hindboutik_size_guide_data` | wp_options |
| `text_top_bar` | option | `hindboutik_top_bar_text` | wp_options |
| `icone` | option | `hindboutik_top_bar_icon` | wp_options |
| `shipping-returns` | option | `hindboutik_shipping_returns` | wp_options |

### Lire une méta — usage

```php
use HindBoutik\Acf\MetaApi;

// Lire la description d'un produit (anciennement get_field('description', $product_id))
$desc = MetaApi::getField('description', $product_id);

// Lire le guide des tailles (anciennement get_field('categorie_de_produit', 'option'))
$guides = MetaApi::getField('categorie_de_produit', 'option');

// Fonction globale équivalente
$desc = hindboutik_get_field('description', $product_id);
```

## Migration

La migration s'exécute automatiquement à l'activation du plugin:

``` php
register_activation_hook(__FILE__, function() {
    $migration = new \HindBoutik\Acf\Migration();
    $migration->run();
});
```

Pour forcer une nouvelle migration (WP-CLI):

``` php
wp eval "require_once 'wp-content/plugins/hindboutik-core/app/Acf/Migration.php'; (new HindBoutik\Acf\Migration())->run();"
```

## Shortcodes

| Shortcode | Feature | Description |
|---|---|---|
| `[custom_size_guide]` | `SizeGuide` | Modal guide des tailles |
| `[product_content_details]` | `ProductContentDetails` | Onglets description/livraison |
| `[crosssell_carousel]` | `CrosssellCarousel` | Carousel produits recommandés |
| `[custom_product_image_grid]` | `ProductImageGrid` | Grille d'images |
| `[current_product_image_slider]` | `ProductImageSlider` | Slider Slick |
| `[woo_cart_but]` | `MenuIcons` | Bouton panier |
| `[yith_wcwl_items_count]` | `MenuIcons` | Compteur favoris |
| `[klarna_badge]` | `KlarnaBadge` | Badge Klarna |
| `[ts_progress_bar_free_shipping]` | `FreeShippingProgress` | Barre de progression |
| `[ts_message_after]` | `AfterMessage` | Messages retours/SAV |


## `languages/hindboutik-core-fr_FR.po` (extrait)

```po
msgid ""
msgstr ""
"Project-Id-Version: HindBoutik Core 1.0.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"Language: fr_FR\n"

msgid "Guide des tailles"
msgstr "Guide des tailles"

msgid "Livraison & retours"
msgstr "Livraison & retours"

msgid "Une question ?"
msgstr "Une question ?"

msgid "Dernières pièces disponibles"
msgstr "Dernières pièces disponibles"

msgid "Pièce unique"
msgstr "Pièce unique"

msgid "Ajouter au panier"
msgstr "Ajouter au panier"

msgid "Choix des options"
msgstr "Choix des options"

msgid "Mes informations"
msgstr "Mes informations"

msgid "Mes adresses"
msgstr "Mes adresses"

msgid "Mes achats"
msgstr "Mes achats"

msgid "Récompenses et fidélité"
msgstr "Récompenses et fidélité"

msgid "Mes favoris"
msgstr "Mes favoris"

msgid "Déconnexion"
msgstr "Déconnexion"

msgid "Coloris disponibles"
msgstr "Coloris disponibles"

msgid "État de la TVA"
msgstr "État de la TVA"

msgid "Taille"
msgstr "Taille"

msgid "Guide des tailles"
msgstr "Guide des tailles"

msgid "Bravo, la livraison est offerte !"
msgstr "Bravo, la livraison est offerte !"

msgid "Retours & échanges possibles sous 14 jours"
msgstr "Retours & échanges possibles sous 14 jours"

msgid "Service client réactif"
msgstr "Service client réactif"

msgid "Expédié depuis Toulouse – Livraison rapide"
msgstr "Expédié depuis Toulouse – Livraison rapide"

msgid "Les clients adorent aussi ces articles"
msgstr "Les clients adorent aussi ces articles"

msgid "Mon panier"
msgstr "Mon panier"

msgid "Voir votre panier"
msgstr "Voir votre panier"

msgid "Favoris"
msgstr "Favoris"
```

## Utilisation WP-CLI

```cli
wp hindboutik features              # Liste toutes les features + status
wp hindboutik toggle whatsapp_widget # Active/désactive le widget WhatsApp
wp hindboutik enable-all            # Active tout
wp hindboutik disable-all           # Désactive tout
wp hindboutik migrate               # (Re)lance la migration ACF -> natif
wp hindboutik migrate --force       # Idem, en écrasant les options déjà migrées
```

## Options de configuration des modules créées

| Option | Valeur par défaut | Description |
|--------|-------------------|-------------|
| `hindboutik_feature_enabled_whatsapp_widget` | 1 | Widget WhatsApp |
| `hindboutik_feature_enabled_size_guide` | 1 | Guide des tailles |
| `hindboutik_feature_enabled_product_content_details` | 1 | Détails produit |
| `hindboutik_feature_enabled_crosssell_carousel` | 1 | Carousel cross-sell |
| `hindboutik_feature_enabled_product_image_grid` | 1 | Grille d'images |
| `hindboutik_feature_enabled_plus_minus_quantity` | 1 | Boutons +/- |
| `hindboutik_feature_enabled_product_image_slider` | 1 | Slider images |
| `hindboutik_feature_enabled_menu_icons` | 1 | Icônes menu |
| `hindboutik_feature_enabled_klarna_badge` | 1 | Badge Klarna |
| `hindboutik_feature_enabled_category_read_more` | 1 | Read-more catégorie |
| `hindboutik_feature_enabled_sale_badge` | 1 | Badge promotion |
| `hindboutik_feature_enabled_account_menu` | 1 | Menu compte |
| `hindboutik_feature_enabled_account_scripts` | 1 | Scripts compte |
| `hindboutik_feature_enabled_taille_unique` | 1 | Taille unique |
| `hindboutik_feature_enabled_free_shipping_progress` | 1 | Barre progress |
| `hindboutik_feature_enabled_after_message` | 1 | Message après panier |
| `hindboutik_feature_enabled_color_swatches` | 1 | Pastilles couleur |
| `hindboutik_feature_enabled_tax_status_column` | 1 | Colonne TVA admin |
| `hindboutik_feature_enabled_free_shipping_label` | 1 | Label "OFFERT" |

## Dépendances

WordPress ≥ 5.9
WooCommerce ≥ 6.0
PHP ≥ 7.4
YITH WooCommerce Wishlist (optionnel, pour les favoris)

## Licence

GPLv3 — Webpixelia