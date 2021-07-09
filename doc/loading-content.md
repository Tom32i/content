# Loading content

Stenope offers tools to fetch contents from local or distant sources, parse them and hydrate them as domain PHP objects.

To illustrate how that works, let's code a simple blog.

## Setup

### Create your model

Create a simple class that describe your model, here a blog Article:

```php
<?php

namespace App\Model;

class Article {
    public string $slug;
    public string $title;
    public string $content;
    public \DateTimeInterface $created;
    public \DateTimeInterface $lastModified;
}
```

### Register content provider

Register your model in `config/packages/stenope.yaml` by attributing a _path_ to the model class:

```yaml
stenope:
  providers:
    App\Model\Article: '%kernel.project_dir%/content/articles' # Local directory
```

Articles sources files are now expected to be found in the `content/articles` path.

_Note: See other possible [type of sources](supported-sources.md)._

### Write your first content

Write your first article in `content/articles/how-to-train-your-dragon.md`:

```markdown
---
title: "How to train your dragon"
---

# This is Berk

It's twelve days north of Hopeless and a few degrees south of Freezing to Death. It's located solidly on the Meridian of Misery. My village. In a word, sturdy. It's been here for seven generations, but every single building is new. We've got hunting, fishing, and a charming view of the sunsets. The only problems are the pests. Most places have mice or mosquitoes. We have... dragons.
```

By default, the content of the source file are mapped on the `content` property (Stenope supports Markdown out of the box) and the name of the file is mapped on the `slug` property._

_Note: Check out all the natively [supported formats](supported-formats.md)._

## Usage

### Listing contents

In your controller (or service):

```php
<?php

namespace App\Controller;

use App\Model\Article;
use Stenope\Bundle\ContentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/blog")
 */
class BlogController extends AbstractController
{
    /**
     * @Route("/", name="blog")
     */
    public function index(ContentManagerInterface $contentManager)
    {
        return $this->render(
            'blog/index.html.twig',
            ['articles' => $contentManager->getContents(Article::class)]
        );
    }
```

_Note: contents of the same type can very well be writen in different formats._

### Fetching a specific content

The content manager uses slugs to identify your content.

The `slug` argument must exactly match the static file name in your content directory.

Example: `$contentManager->getContent(Article::class, 'how-to-train-your-dragon');` will fetch the `content/articles/how-to-train-your-dragon.md` article.

```php
<?php

namespace App\Controller;

use App\Model\Article;
use Stenope\Bundle\ContentManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/blog")
 */
class BlogController extends AbstractController
{
    // ...

    /**
     * @Route("/{slug}", name="article")
     */
    public function article(ContentManagerInterface $contentManager, string $slug)
    {
        return $this->render(
            'blog/article.html.twig',
            ['article' => $contentManager->getContent(Article::class, $slug)]
        );
    }
```

### Sorting contents

The `getContents` method have a second parameters `$sortBy` that allows sorting the content list.

It accepts:

#### A property name (string)

Alphabetically sorted categories:

```php
$categories = $contentManager->getContents(
    Category::class,
    'title'
);
```

#### An array of properties and sorting mode

All articles sorted by descending `date` (most recent first) and then by ascending `title` (Alphabetically):

```php
$latestArticles = $contentManager->getContents(
    Category::class,
    ['date' => false, 'title' => true]
);
```

#### A custom callable supported by the PHP [usort](https://www.php.net/manual/fr/function.usort.php) function

```php
$tasks = $contentManager->getContents(
    Task::class,
    fn($a, $b) => $a->priority <=> $b->priority
);
```

### Filtering contents

The `getContents` method have a third parameters `$filterBy` that allows filtering the content list.

It accepts:

#### An array of properties and values

```php
$articles = $contentManager->getContents(
    Article::class,
    null,
    ['category' => 'symfony']
);
```

When passing multiple requirements, all must be met:

```php
$myDrafts = $contentManager->getContents(
    Article::class,
    null,
    ['author' => 'ogizanagi', 'draft' => true]
);
```

#### A custom callable supported by the PHP [usort](https://www.php.net/manual/fr/function.usort.php) function

```php
$taggedMobileArticles = $contentManager->getContents(
    Article::class,
    null,
    fn (Article $article): bool => in_array('mobile', $article->tags)
);
```

#### An ExpressionLanguage expression

```php
use function Stenope\Bundle\ExpressionLanguage\expr;

$taggedMobileArticles = $contentManager->getContents(
    Article::class,
    null,
    expr('"mobile" in _.tags')
);

// expr() and exprOr() are optional syntax sugar,
// but you can also provide the expression as a string directly:
$activeUsers = $contentManager->getContents(User::class, '_.active'); // Equivalent to ['active' => true]
$activeDevUsers = $contentManager->getContents(User::class, '_.active and _.dev');
```

!!! Note
`expr` accepts multiple expressions it'll combine using `and`.  
Use `exprOr` to combine expressions using `or`.

See the [ExpressionLanguage syntax](https://symfony.com/doc/current/components/expression_language/syntax.html).
You may also want to extend the expression language capabilities for your own contents by [registering a custom expression provider](https://symfony.com/doc/current/components/expression_language/extending.html#using-expression-providers) tagged with `stenope.expression_language_provider`.

Built-in functions are:

- date
- datetime
- upper
- lower
- contains
- starts_with
- ends_with

## Debug

See [CLI - Debug](./cli.md#debug)

## Advanced usage and extension

### Register a custom denormalizer

Unless specified otherwise, Stenope will denormalize your objects using the [default Symfony serializer](https://symfony.com/doc/current/components/serializer.html#deserializing-an-object).

For more control over your model denormalization, you can register your own Denormalizer.

Simply, create a service that implements Symfony's `DenormalizerInterface` and supports your model:

```php
<?php

namespace App\Stenope\Denormalizer;

use App\Model\Article;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class ArticleDenormalizer implements DenormalizerInterface
{
    /**
     * Must return true for supported models.
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return is_a($type, Article::class, true);
    }

    /**
     * Instanciate your model from the denormalized data array.
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        return new Article(
            $data['title'],
            $data['slug'],
            $data['content'],
            new \DateTimeImmutable($data['date']),
            new \DateTimeImmutable($data['lastModified'])
        );
    }
}
```

_Note: Using autowiring, denormalizers are automaticaly registered in Symfony serializer._
