# chronos-doctrine

The warhuhn/chronos-doctrine library adds Doctrine DBAL Types that convert Date/DateTime-based database values to
Immutable Chronos DateTime-Implementations.

## Installation

```bash
composer.phar require warhuhn/chronos-doctrine
```

## Configuration

### doctrine/dbal in raw PHP

```php
<?php

\Doctrine\DBAL\Types::addType('chronos_date', \Warhuhn\Doctrine\DBAL\Types\ChronosDateType::class);
\Doctrine\DBAL\Types::addType('chronos_datetime', \Warhuhn\Doctrine\DBAL\Types\ChronosDateTimeType::class);
\Doctrine\DBAL\Types::addType('chronos_datetimetz', \Warhuhn\Doctrine\DBAL\Types\ChronosDateTimeTzType::class);
```

### Symfony

```yaml
# app/config/config.yml
doctrine:
   dbal:
       types:
           chronos_date: Warhuhn\Doctrine\DBAL\Types\ChronosDateType
           chronos_datetime: Warhuhn\Doctrine\DBAL\Types\ChronosDateTimeType
           chronos_datetimetz: Warhuhn\Doctrine\DBAL\Types\ChronosDateTimeTzType
```

## Usage in Doctrine ORM

```php
<?php

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Example
{
    /**
     * @var \Cake\Chronos\ChronosDate 
     * @ORM\Column(type="chronos_date")
     */
    private $date;
    
    /**
     * @var \Cake\Chronos\Chronos
     * @ORM\Column(type="chronos_datetime")
     */
    private $dateTime;
    
    /**
     * @var \Cake\Chronos\Chronos
     * @ORM\Column(type="chronos_datetimetz")
     */
    private $dateTimeTz;
}
```