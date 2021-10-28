Quickstart
==========


The best way to install Kdyby/Autowired is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/autowired
```



Include in application
----------------------

Package contains two traits that you can include in your Components.

The first one is just for properties and the second one is for component factories.


```php
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	use Kdyby\Autowired\AutowireProperties;
	use Kdyby\Autowired\AutowireComponentFactories;

	// ...
}
```

For debugging purposes you may also add the AutowiredExtension in config, which registers Tracy panel


```php
extensions:
	autowired: Kdyby\Autowired\DI\AutowiredExtension
```



Autowired properties
--------------------

Every `protected` or `public` property marked with `@autowire` annotation will be under control of `AutowireProperties` trait, we will call them "autowired properties".

The properties are analysed and result of the analysis is cached. This means, that you will see errors in your configuration instantly, and not after you use it in some conditional code, that might not even be executed every time. This is here to help you find errors, as early as possible.

Every autowired property will be unsetted, when presenter is created, and then the trait takes over using `__get()` and `__set()` magic. The service will be created only if it's really used and it cannot be overwritten, once it has some value.

This behaviour is inspired by article [DI and property injection](http://phpfashion.com/di-a-property-injection) by [David Grudl](http://davidgrudl.com/).


```php
class ArticlePresenter extends BasePresenter
{

	/**
	 * @autowire
	 */
	protected App\ArticleRepository $articleRepository;

	/**
	 * @autowire(MyApp\Blog\Article, factory=\Kdyby\Doctrine\EntityDaoFactory)
	 */
	protected Kdyby\Doctrine\EntityDao $factoryResult;

	// ..

}
```

Or using PHP 8 attributes:

```php
use Kdyby\Autowired\Attributes\Autowire;
use Kdyby\Doctrine\EntityDao;
use Kdyby\Doctrine\EntityDaoFactory;
use App\Article;
use App\ArticleRepository;

class ArticlePresenter extends BasePresenter
{

	#[Autowire]
	protected ArticleRepository $articleRepository;

	#[Autowire(factory: EntityDaoFactory::class, arguments: [Article::class])]
	protected EntityDao $factoryResult;

	// ..

}
```



Autowired component factories
-----------------------------

There is often need to inject a component factory object to the presenter and then use it in lazy component factory method that connects the created component to the component tree.

What if the component factory object from DI Container could be passed directly to the component factory method, without all that boilerplate code?


```php
class ArticlePresenter extends BasePresenter
{

	// ..

	protected function createComponentDatagrid($name, IDatagridFactory $factory): My\Awesome\Datagrid
	{
		return $factory->create();
	}

}
```

The `$name` parameter can be omitted, `createComponentDatagrid(IDatagridFactory $factory)` works as well.

Cool right?


Not so clean...
---------------

Just keep in mind, that is not a clean approach - it's pragmatic. Never try to use these traits in model classes. Strictly use constructor injection whenever it's possible!
