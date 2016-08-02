# doctrine-collections
======================

1) Installing
------------------------

### Using Composer

As Symfony uses [Composer][1] to manage its dependencies, the recommended way to install this bundle is to use it.

If you don't have Composer yet, download it following the instructions on
[http://getcomposer.org/][1] or just run the following command:

    curl -s http://getcomposer.org/installer | php

Then, use the `require` command to download this bundle:

    php composer.phar require phppond/doctrine-collections:@stable


2) Usage
------------------------

```php
use PhpPond\ORM\EntityRepository;

public function showCollectionAction(Criteria $criteria)
{
    $collection = $this->get('entity_repository')->all('u');
    $collection->criteria($criteria);
    ...
}

```
 
 
[1]:  http://getcomposer.org/
 