# Библиотека EnvironmentConfig

Реализация конфига среды. 
Возможно использовать отдельно или с библиотекой [tutu-ru/php-config](https://github.com/tutu-ru/php-config).

## Инициализация и использование

Создание конфига (etcd):
```php
use TutuRu\EnvironmentConfig\EnvironmentConfig;

$envCoonfig = new EnvironmentConfig(new EtcdEnvironmentProviderFactory('application_name'));
```

Создание конфига (etcd) с кэшированием данных на 60 секунд:
```php
use TutuRu\EnvironmentConfig\EnvironmentConfig;
use Cache\Adapter\Apcu\ApcuCachePool;
use Cache\Bridge\SimpleCache\SimpleCacheBridge;

$cache = new SimpleCacheBridge(new ApcuCachePool());
$envCoonfig = new EnvironmentConfig(new EtcdEnvironmentProviderFactory('application_name'), $cache, 60);
```

Далее вся работа идет в соответствии с интерфейсом `TutuRu\Config\EnvironmentConfigInterface`.

### Приоритеты

При запросе `getValue` возвращается первое не-null значение.
Обход происходит следующим образом:
* Сервисный
* Бизнес
* Инфраструктурный

## Миграции

```php
use TutuRu\EnvironmentConfig\EnvironmentConfig;

$envCoonfig = new EnvironmentConfig(new EtcdEnvironmentProviderFactory('application_name'));

$envConfig->getServiceMutator()->init();
$envConfig->getServiceMutator()->setValue('some/node', $value);
```
