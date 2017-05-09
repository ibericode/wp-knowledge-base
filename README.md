WP Knowledge Base
=======

WordPress powered knowledge base for your products. Beautiful.

**Requires PHP 5.3+**

_This plugin is under active development and could be subject to changes breaking backward compatibility._

# Usage

This plugin provides you with a custom post type, some taxonomies and other useful tools for building a knowledge base of documentation articles.

### Functions & Shortcodes

```php
wpkb_article_list( array( 'category' => 'Getting Started', 'title' => 'Start here..' ) );
wpkb_search_form();
wpkb_breadcrumbs();
wpkb_table_of_contents( array( 'title' => 'In this article' ) );
wpkb_category_list();
wpkb_keyword_list();
```

### Settings

On top of that, it comes with some useful rewriting & settings.

- Use a custom page (incl. slug & title) as your overview (archive) page.
- Quickly hide certain articles from overview pages, for example to phase out deprecated articles.

# License

GPL v2