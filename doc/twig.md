# Twig integration

Stenope provides a Twig extension to help you interact with the `ContentManagerInterface`
from your templates.

## Functions

| Function | Description |
| -- | -- |
| `content_get(type, id)` | Fetch a specific content |
| `content_list(type, sortBy, filterBy)` |  List all contents for a given type |
| `content_expr(...exprs)` |  Allow to build an expression to filter content with `content_list` |
| `content_expr_or(...exprs)` |  Allow to build an expression combined with `or` to filter content with `content_list` |

## Usage

Fetch the auhtor of the article:

```twig
{% set author = content_get('App\\Model\\Member', article.author) %}
```

Get all active job offers:

```twig
{% for offer in content_list('App\\Model\\JobOffer', { date: true }, { active: true }) %}
    <!-- ... -->
{% endfor %}
```

using an expression filter:

```twig
{% for offer in content_list('App\\Model\\JobOffer', { date: true }, '_.active') %}
    <!-- ... -->
{% endfor %}
```
