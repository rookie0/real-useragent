
# real-useragent

Up to date useragent faker with real world database(grab from [WhatIsMyBrowser.com](https://developers.whatismybrowser.com/useragents/explore/)).

## Installation

```shell
composer require rookie0/real-useragent
```

## Usage

Basic usage

```php
use Rookie0\RealUserAgent\UserAgent;

// optional
$config = [
    'timeout'          => 5, // grab request timeout
    'cache_ttl'        => 60 * 60, // cache grab content expired time
    'cache_key_prefix' => 'realuseragent',
    'page_num'         => 3, // grab content page num
];

// optional default array cache
$cache = instance of Psr\SimpleCache\CacheInterface;

$ua = new UserAgent($config, $cache);

// get fake useragent by accessing properties
echo $ua->chrome;

// filter by call methods
echo $ua->firefox();

echo $ua->wechat, PHP_EOL, $us->ucBrowser;

```

Or filter a useragent by yourself, check needed info from [WhatIsMyBrowser.com](https://developers.whatismybrowser.com/useragents/explore/).
- [Software Names](https://developers.whatismybrowser.com/useragents/explore/software_name/)
- [Operating Systems](https://developers.whatismybrowser.com/useragents/explore/operating_system_name/)
- [Operating Platforms](https://developers.whatismybrowser.com/useragents/explore/operating_platform/)
- [Software Types](https://developers.whatismybrowser.com/useragents/explore/software_type_specific/)
- [Hardware Types](https://developers.whatismybrowser.com/useragents/explore/hardware_type_specific/)
- [Layout Engine Names](https://developers.whatismybrowser.com/useragents/explore/layout_engine_name/)

```php
// optional 
$filter = [
    'category'         => 'software_name',
    'name'             => 'chrome',
    'order_by'         => '-times_seen',
    'software_version' => '',
    'operating_system' => '',
    'hardware_type'    => '',
];

// refresh cache  optional defalut false
$refresh = true;

echo $ua->random($filter, $refresh);


// grab useragents content
// software_name operating_system_name operating_platform software_type_specific hardware_type_specific layout_engine_name
$category = 'operating_system_name';

$name = 'linux';

// - for desc
$orderBy = 'software_type_specific';

var_dump($ua->collect($category, $name, $orderBy, $refresh));

```

