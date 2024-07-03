## XMPReader

Reader for XMP data containing properties relevant to images, written in PHP.


### Usage


```php
composer require wikimedia/xmp-reader
```

```php
use Wikimedia\XMPReader\Reader as XMPReader;

//...

// Check if the php instance has the required supported libraries to parse XMP
$isXMPSupported = XMPReader::isSupported();

// Create a new instance
$xmp = new XMPReader();
// or
$xmp = new XMPReader( $logger, $filename );
// where $logger is an implementation of Psr\Log\LoggerInterface and $filename a string describing the origin of your XMP content

// To parse XMP data in $string
$xmp->parse( $string );

// To parse XMP Extended data in $string
$xmp->parseExtended( $string );

// To retrieve the parsed results
$results = $xmp->getResults();
```

The parsed results are returned in an array of 3 potential groups,
which indicate their priority according to the Metadata Working Group guidance.
http://www.metadataworkinggroup.org/pdf/mwg_guidance.pdf

```php
[
	'xmp-exif' => [],
	'xmp-general' => [],
	'xmp-deprecated' => [],
]
```

### Supported XMP
Most of the following metadata sets are supported
* http://ns.adobe.com/exif/1.0/
* http://ns.adobe.com/tiff/1.0/
* http://ns.adobe.com/exif/1.0/aux/
* http://purl.org/dc/elements/1.1/
* http://ns.adobe.com/xap/1.0/rights/
* http://creativecommons.org/ns#
* http://ns.adobe.com/xmp/note/

Some of:
* http://ns.adobe.com/xap/1.0/
* http://ns.adobe.com/xap/1.0/mm/
* http://ns.adobe.com/photoshop/1.0/
* http://iptc.org/std/Iptc4xmpCore/1.0/xmlns/
* http://iptc.org/std/Iptc4xmpExt/2008-02-29/

## License
GNU General Public License v2.0