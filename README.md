Kdyby/Autowired [![Build Status](https://secure.travis-ci.org/Kdyby/Autowired.png?branch=master)](http://travis-ci.org/Kdyby/Autowired)
===========================

**You shouldn't be using this, if you don't know what youre doing!**

discussion: http://forum.nette.org/cs/13084-presentery-property-lazy-autowire-na-steroidech#p93574


Requirements
------------

Kdyby/Autowired requires PHP 5.3.2 or higher.

- [Nette Framework 2.0.x](https://github.com/nette/nette)


Installation
------------

The best way to install Kdyby/Autowired is using  [Composer](http://getcomposer.org/):

```sh
$ composer require kdyby/autowired:@dev
```


-----

Homepage [http://www.kdyby.org](http://www.kdyby.org) and repository [http://github.com/kdyby/autowired](http://github.com/kdyby/autowired).


## Include in application


```php
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	use Kdyby\Autowired\AutowireProperties;

}
```


## Usage


```php
class ArticlePresenter extends BasePresenter
{
    /**
     * @autowire
     * @var App\ArticleRepository
     */
    protected $articleRepository;

	/**
	 * @var Kdyby\Doctrine\EntityDao
	 * @autowire(MyApp\Blog\Article, factory=\Kdyby\Doctrine\IEntityDaoFactory)
	 */
	public $factoryResult;

    // ..
}
```
