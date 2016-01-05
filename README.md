php-json-ld
===========

[![Build Status][travis-ci-png]][travis-ci-site]
[travis-ci-png]: https://travis-ci.org/digitalbazaar/php-json-ld.png?branch=master
[travis-ci-site]: https://travis-ci.org/digitalbazaar/php-json-ld

Introduction
------------

This library is an implementation of the [JSON-LD][] specification in [PHP][].

JSON, as specified in [RFC7159][], is a simple language for representing
objects on the Web. Linked Data is a way of describing content across
different documents or Web sites. Web resources are described using
IRIs, and typically are dereferencable entities that may be used to find
more information, creating a "Web of Knowledge". [JSON-LD][] is intended
to be a simple publishing method for expressing not only Linked Data in
JSON, but for adding semantics to existing JSON.

JSON-LD is designed as a light-weight syntax that can be used to express
Linked Data. It is primarily intended to be a way to express Linked Data
in JavaScript and other Web-based programming environments. It is also
useful when building interoperable Web Services and when storing Linked
Data in JSON-based document storage engines. It is practical and
designed to be as simple as possible, utilizing the large number of JSON
parsers and existing code that is in use today. It is designed to be
able to express key-value pairs, RDF data, [RDFa][] data,
[Microformats][] data, and [Microdata][]. That is, it supports every
major Web-based structured data model in use today.

The syntax does not require many applications to change their JSON, but
easily add meaning by adding context in a way that is either in-band or
out-of-band. The syntax is designed to not disturb already deployed
systems running on JSON, but provide a smooth migration path from JSON
to JSON with added semantics. Finally, the format is intended to be fast
to parse, fast to generate, stream-based and document-based processing
compatible, and require a very small memory footprint in order to operate.

## Quick Examples

```php
$doc = (object)array(
  "http://schema.org/name" => "Manu Sporny",
  "http://schema.org/url" => (object)array("@id" => "http://manu.sporny.org/"),
  "http://schema.org/image" => (object)array("@id" => "http://manu.sporny.org/images/manu.png")
);

$context = (object)array(
  "name" => "http://schema.org/name",
  "homepage" => (object)array("@id" => "http://schema.org/url", "@type" => "@id"),
  "image" => (object)array("@id" => "http://schema.org/image", "@type" => "@id")
);

// compact a document according to a particular context
// see: http://json-ld.org/spec/latest/json-ld/#compacted-document-form
$compacted = jsonld_compact($doc, $context);

echo json_encode($compacted, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
/* Output:
{
  "@context": {...},
  "image": "http://manu.sporny.org/images/manu.png",
  "homepage": "http://manu.sporny.org/",
  "name": "Manu Sporny"
}
*/

// compact using URLs
jsonld_compact('http://example.org/doc', 'http://example.org/context');

// expand a document, removing its context
// see: http://json-ld.org/spec/latest/json-ld/#expanded-document-form
$expanded = jsonld_expand($compacted) {
echo json_encode($expanded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
/* Output:
{
  "http://schema.org/image": [{"@id": "http://manu.sporny.org/images/manu.png"}],
  "http://schema.org/name": [{"@value": "Manu Sporny"}],
  "http://schema.org/url": [{"@id": "http://manu.sporny.org/"}]
}
*/

// expand using URLs
jsonld_expand('http://example.org/doc');

// flatten a document
// see: http://json-ld.org/spec/latest/json-ld/#flattened-document-form
$flattened = jsonld_flatten($doc);
// all deep-level trees flattened to the top-level

// frame a document
// see: http://json-ld.org/spec/latest/json-ld-framing/#introduction
$framed = jsonld_frame($doc, $frame);
// document transformed into a particular tree structure per the given frame

// normalize a document using the RDF Dataset Normalization Algorithm
// (URDNA2015), see: http://json-ld.github.io/normalization/spec/
$normalized = jsonld_normalize(
  $doc, array('algorithm' => 'URDNA2015', 'format' => 'application/nquads'));
// normalized is a string that is a canonical representation of the document
// that can be used for hashing, comparison, etc.

// force HTTPS-only context loading:
// use built-in secure document loader
jsonld_set_document_loader('jsonld_default_secure_document_loader');

// set a default custom document loader
jsonld_set_document_loader('my_custom_doc_loader');

// a custom loader that demonstrates using a simple in-memory mock for
// certain contexts before falling back to the default loader
// note: if you want to set this loader as the new default, you'll need to
// store the previous default in another variable first and access that inside
// the loader
global $mocks;
$mocks = array('http://example.com/mycontext' => (object)array(
  'hombre' => 'http://schema.org/name'));
function mock_load($url) {
  global $jsonld_default_load_document, $mocks;
  if(isset($mocks[$url])) {
    // return a "RemoteDocument", it has these three properties:
    return (object)array(
      'contextUrl' => null,
      'document' => $mocks[$url],
      'documentUrl' => $url);
  }
  // use default loader
  return call_user_func($jsonld_default_load_document, $url);
}

// use the mock loader for just this call, witout modifying the default one
$compacted = jsonld_compact($foo, 'http://example.com/mycontext', array(
  'documentLoader' => 'mock_load'));

// a custom loader that uses a simplistic in-memory cache (no invalidation)
global $cache;
$cache = array();
function cache_load($url) {
  global $jsonld_default_load_document, $cache;
  if(isset($cache[$url])) {
    return $cache[$url];
  }
  // use default loader
  $doc = call_user_func($jsonld_default_load_document, $url);
  $cache[$url] = $doc;
  return $doc;
}

// use the cache loader for just this call, witout modifying the default one
$compacted = jsonld_compact($foo, 'http://schema.org', array(
  'documentLoader' => 'cache_load'));
```

Commercial Support
------------------

Commercial support for this library is available upon request from
[Digital Bazaar][]: support@digitalbazaar.com

Source
------

The source code for the PHP implementation of the JSON-LD API
is available at:

http://github.com/digitalbazaar/php-json-ld

Tests
-----

This library includes a sample testing utility which may be used to verify
that changes to the processor maintain the correct output.

To run the sample tests you will need to get the test suite files by cloning
the `json-ld.org` and `normalization` repositories hosted on GitHub:

- https://github.com/json-ld/json-ld.org
- https://github.com/json-ld/normalization

Then run the PHPUnit test.php application and point it at the directories
containing the tests:

    phpunit --group json-ld.org test.php -d {PATH_TO_JSON_LD_ORG/test-suite}
    phpunit --group normalization test.php -d {PATH_TO_NORMALIZATION/tests}

[Digital Bazaar]: http://digitalbazaar.com/
[JSON-LD]: http://json-ld.org/
[Microdata]: http://www.w3.org/TR/microdata/
[Microformats]: http://microformats.org/
[PHP]: http://php.net
[RDFa]: http://www.w3.org/TR/rdfa-core/
[RFC7159]: http://tools.ietf.org/html/rfc7159
