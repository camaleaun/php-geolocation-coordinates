# php-geolocation-coordinates

Handles geolocation coordinates Latitude and Longitude values and output in DD or DMS formats.

## Installation guide

### If your project doesn't use composer
Just do a _require_once()_ of the _geolocation-coordinates.php_ file.

### If your project uses composer

First you need to add this repository in your _composer.json_ file. For instance:
```
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/camaleaun/php-geolocation-coordinates.git"
    }
]
```

Then you have to do a

```
composer require camaleaun/php-geolocation-coordinates
```

Finally, in your code, you just have to call the library directly, or, if your project
uses namespaces, you need to include it in your class:

```
use Camaleaun\GeolocationCoordinates;
```

## Utilization
You can either use it as an object or directly:

```
$coordinates = new GeolocationCoordinates($coordinates);
// DD
$coordinates = $converter->format('dd');
// DMS
$coordinates = $converter->format('dms');
```
