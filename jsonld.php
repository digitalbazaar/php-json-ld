<?php
/**
 * PHP implementation of the JSON-LD API.
 * Version: 0.4.7
 *
 * @author Dave Longley
 *
 * BSD 3-Clause License
 * Copyright (c) 2011-2014 Digital Bazaar, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * Neither the name of the Digital Bazaar, Inc. nor the names of its
 * contributors may be used to endorse or promote products derived from
 * this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS
 * IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED
 * TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * Performs JSON-LD compaction.
 *
 * @param mixed $input the JSON-LD object to compact.
 * @param mixed $ctx the context to compact with.
 * @param assoc [$options] options to use:
 *          [base] the base IRI to use.
 *          [graph] true to always output a top-level graph (default: false).
 *          [documentLoader(url)] the document loader.
 *
 * @return mixed the compacted JSON-LD output.
 */
function jsonld_compact($input, $ctx, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->compact($input, $ctx, $options);
}

/**
 * Performs JSON-LD expansion.
 *
 * @param mixed $input the JSON-LD object to expand.
 * @param assoc[$options] the options to use:
 *          [base] the base IRI to use.
 *          [documentLoader(url)] the document loader.
 *
 * @return array the expanded JSON-LD output.
 */
function jsonld_expand($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->expand($input, $options);
}

/**
 * Performs JSON-LD flattening.
 *
 * @param mixed $input the JSON-LD to flatten.
 * @param mixed $ctx the context to use to compact the flattened output, or
 *          null.
 * @param [options] the options to use:
 *          [base] the base IRI to use.
 *          [documentLoader(url)] the document loader.
 *
 * @return mixed the flattened JSON-LD output.
 */
function jsonld_flatten($input, $ctx, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->flatten($input, $ctx, $options);
}

/**
 * Performs JSON-LD framing.
 *
 * @param mixed $input the JSON-LD object to frame.
 * @param stdClass $frame the JSON-LD frame to use.
 * @param assoc [$options] the framing options.
 *          [base] the base IRI to use.
 *          [embed] default @embed flag (default: true).
 *          [explicit] default @explicit flag (default: false).
 *          [requireAll] default @requireAll flag (default: true).
 *          [omitDefault] default @omitDefault flag (default: false).
 *          [documentLoader(url)] the document loader.
 *
 * @return stdClass the framed JSON-LD output.
 */
function jsonld_frame($input, $frame, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->frame($input, $frame, $options);
}

/**
 * **Experimental**
 *
 * Links a JSON-LD document's nodes in memory.
 *
 * @param mixed $input the JSON-LD document to link.
 * @param mixed $ctx the JSON-LD context to apply or null.
 * @param assoc [$options] the options to use:
 *          [base] the base IRI to use.
 *          [expandContext] a context to expand with.
 *          [documentLoader(url)] the document loader.
 *
 * @return the linked JSON-LD output.
 */
function jsonld_link($input, $ctx, $options) {
  // API matches running frame with a wildcard frame and embed: '@link'
  // get arguments
  $frame = new stdClass();
  if($ctx) {
    $frame->{'@context'} = $ctx;
  }
  $frame->{'@embed'} = '@link';
  return jsonld_frame($input, $frame, $options);
};

/**
 * Performs RDF dataset normalization on the given input. The input is
 * JSON-LD unless the 'inputFormat' option is used. The output is an RDF
 * dataset unless the 'format' option is used.
 *
 * @param mixed $input the JSON-LD object to normalize.
 * @param assoc [$options] the options to use:
 *          [base] the base IRI to use.
 *          [intputFormat] the format if input is not JSON-LD:
 *            'application/nquads' for N-Quads.
 *          [format] the format if output is a string:
 *            'application/nquads' for N-Quads.
 *          [documentLoader(url)] the document loader.
 *
 * @return mixed the normalized output.
 */
function jsonld_normalize($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->normalize($input, $options);
}

/**
 * Converts an RDF dataset to JSON-LD.
 *
 * @param mixed $input a serialized string of RDF in a format specified
 *          by the format option or an RDF dataset to convert.
 * @param assoc [$options] the options to use:
 *          [format] the format if input not an array:
 *            'application/nquads' for N-Quads (default).
 *          [useRdfType] true to use rdf:type, false to use @type
 *            (default: false).
 *          [useNativeTypes] true to convert XSD types into native types
 *            (boolean, integer, double), false not to (default: false).
 *
 * @return array the JSON-LD output.
 */
function jsonld_from_rdf($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->fromRDF($input, $options);
}

/**
 * Outputs the RDF dataset found in the given JSON-LD object.
 *
 * @param mixed $input the JSON-LD object.
 * @param assoc [$options] the options to use:
 *          [base] the base IRI to use.
 *          [format] the format to use to output a string:
 *            'application/nquads' for N-Quads.
 *          [produceGeneralizedRdf] true to output generalized RDF, false
 *            to produce only standard RDF (default: false).
 *          [documentLoader(url)] the document loader.
 *
 * @return mixed the resulting RDF dataset (or a serialization of it).
 */
function jsonld_to_rdf($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->toRDF($input, $options);
}

/**
 * JSON-encodes (with unescaped slashes) the given stdClass or array.
 *
 * @param mixed $input the native PHP stdClass or array which will be
 *          converted to JSON by json_encode().
 * @param int $options the options to use.
 *          [JSON_PRETTY_PRINT] pretty print.
 * @param int $depth the maximum depth to use.
 *
 * @return the encoded JSON data.
 */
function jsonld_encode($input, $options=0, $depth=512) {
  // newer PHP has a flag to avoid escaped '/'
  if(defined('JSON_UNESCAPED_SLASHES')) {
     return json_encode($input, JSON_UNESCAPED_SLASHES | $options, $depth);
  }
  // use a simple string replacement of '\/' to '/'.
  return str_replace('\\/', '/', json_encode($input, $options, $depth));
}

/**
 * Decodes a serialized JSON-LD object.
 *
 * @param string $input the JSON-LD input.
 *
 * @return mixed the resolved JSON-LD object, null on error.
 */
function jsonld_decode($input) {
  return json_decode($input);
}

/**
 * Parses a link header. The results will be key'd by the value of "rel".
 *
 * Link: <http://json-ld.org/contexts/person.jsonld>; rel="http://www.w3.org/ns/json-ld#context"; type="application/ld+json"
 *
 * Parses as: {
 *   'http://www.w3.org/ns/json-ld#context': {
 *     target: http://json-ld.org/contexts/person.jsonld,
 *     type: 'application/ld+json'
 *   }
 * }
 *
 * If there is more than one "rel" with the same IRI, then entries in the
 * resulting map for that "rel" will be arrays of objects, otherwise they will
 * be single objects.
 *
 * @param string $header the link header to parse.
 *
 * @return assoc the parsed result.
 */
function jsonld_parse_link_header($header) {
  $rval = array();
  // split on unbracketed/unquoted commas
  if(!preg_match_all(
    '/(?:<[^>]*?>|"[^"]*?"|[^,])+/', $header, $entries, PREG_SET_ORDER)) {
    return $rval;
  }
  $r_link_header = '/\s*<([^>]*?)>\s*(?:;\s*(.*))?/';
  foreach($entries as $entry) {
    if(!preg_match($r_link_header, $entry[0], $match)) {
      continue;
    }
    $result = (object)array('target' => $match[1]);
    $params = $match[2];
    $r_params = '/(.*?)=(?:(?:"([^"]*?)")|([^"]*?))\s*(?:(?:;\s*)|$)/';
    preg_match_all($r_params, $params, $matches, PREG_SET_ORDER);
    foreach($matches as $match) {
      $result->{$match[1]} = $match[2] ?: $match[3];
    }
    $rel = property_exists($result, 'rel') ? $result->rel : '';
    if(!isset($rval[$rel])) {
      $rval[$rel] = $result;
    } else if(is_array($rval[$rel])) {
      $rval[$rel][] = $result;
    } else {
      $rval[$rel] = array($rval[$rel], $result);
    }
  }
  return $rval;
}

/**
 * Relabels all blank nodes in the given JSON-LD input.
 *
 * @param mixed input the JSON-LD input.
 */
function jsonld_relabel_blank_nodes($input) {
  $p = new JsonLdProcessor();
  return $p->_labelBlankNodes(new UniqueNamer('_:b'), $input);
}

/** JSON-LD shared in-memory cache. */
global $jsonld_cache;
$jsonld_cache = new stdClass();

/** The default active context cache. */
$jsonld_cache->activeCtx = new ActiveContextCache();

/** Stores the default JSON-LD document loader. */
global $jsonld_default_load_document;
$jsonld_default_load_document = 'jsonld_default_document_loader';

/**
 * Sets the default JSON-LD document loader.
 *
 * @param callable load_document(url) the document loader.
 */
function jsonld_set_document_loader($load_document) {
  global $jsonld_default_load_document;
  $jsonld_default_load_document = $load_document;
}

/**
 * Retrieves JSON-LD at the given URL.
 *
 * @param string $url the URL to retrieve.
 *
 * @return the JSON-LD.
 */
function jsonld_get_url($url) {
  global $jsonld_default_load_document;
  if($jsonld_default_load_document !== null) {
    $document_loader = $jsonld_default_load_document;
  } else {
    $document_loader = 'jsonld_default_document_loader';
  }

  $remote_doc = call_user_func($document_loader, $url);
  if($remote_doc) {
    return $remote_doc->document;
  }
  return null;
}

/**
 * The default implementation to retrieve JSON-LD at the given URL.
 *
 * @param string $url the URL to to retrieve.
 *
 * @return stdClass the RemoteDocument object.
 */
function jsonld_default_document_loader($url) {
  $doc = (object)array(
    'contextUrl' => null, 'document' => null, 'documentUrl' => $url);
  $redirects = array();

  $opts = array(
    'http' => array(
      'method' => 'GET',
      'header' =>
        "Accept: application/ld+json\r\n"),
    /* Note: Use jsonld_default_secure_document_loader for security. */
    'ssl' => array(
      'verify_peer' => false,
      'allow_self_signed' => true)
  );

  $context = stream_context_create($opts);
  $content_type = null;
  stream_context_set_params($context, array('notification' =>
    function($notification_code, $severity, $message) use (
      &$redirects, &$content_type) {
      switch($notification_code) {
      case STREAM_NOTIFY_REDIRECTED:
        $redirects[] = $message;
        break;
      case STREAM_NOTIFY_MIME_TYPE_IS:
        $content_type = $message;
        break;
      };
    }));
  $result = @file_get_contents($url, false, $context);
  if($result === false) {
    throw new JsonLdException(
      'Could not retrieve a JSON-LD document from the URL: ' . $url,
      'jsonld.LoadDocumentError', 'loading document failed');
  }
  $link_header = array();
  foreach($http_response_header as $header) {
    if(strpos($header, 'link') === 0) {
      $value = explode(': ', $header);
      if(count($value) > 1) {
        $link_header[] = $value[1];
      }
    }
  }
  $link_header = jsonld_parse_link_header(join(',', $link_header));
  if(isset($link_header['http://www.w3.org/ns/json-ld#context'])) {
    $link_header = $link_header['http://www.w3.org/ns/json-ld#context'];
  } else {
    $link_header = null;
  }
  if($link_header && $content_type !== 'application/ld+json') {
    // only 1 related link header permitted
    if(is_array($link_header)) {
      throw new JsonLdException(
        'URL could not be dereferenced, it has more than one ' .
        'associated HTTP Link Header.', 'jsonld.LoadDocumentError',
        'multiple context link headers', array('url' => $url));
    }
    $doc->{'contextUrl'} = $link_header->target;
  }

  // update document url based on redirects
  $redirs = count($redirects);
  if($redirs > 0) {
    $url = $redirects[$redirs - 1];
  }
  $doc->document = $result;
  $doc->documentUrl = $url;
  return $doc;
}

/**
 * The default implementation to retrieve JSON-LD at the given secure URL.
 *
 * @param string $url the secure URL to to retrieve.
 *
 * @return stdClass the RemoteDocument object.
 */
function jsonld_default_secure_document_loader($url) {
  if(strpos($url, 'https') !== 0) {
    throw new JsonLdException(
      "Could not GET url: '$url'; 'https' is required.",
      'jsonld.LoadDocumentError', 'loading document failed');
  }

  $doc = (object)array(
    'contextUrl' => null, 'document' => null, 'documentUrl' => $url);
  $redirects = array();

  // default JSON-LD https GET implementation
  $opts = array(
    'http' => array(
      'method' => 'GET',
      'header' =>
        "Accept: application/ld+json\r\n"),
    'ssl' => array(
      'verify_peer' => true,
      'allow_self_signed' => false,
      'cafile' => '/etc/ssl/certs/ca-certificates.crt'));
  $context = stream_context_create($opts);
  $content_type = null;
  stream_context_set_params($context, array('notification' =>
    function($notification_code, $severity, $message) use (
      &$redirects, &$content_type) {
      switch($notification_code) {
      case STREAM_NOTIFY_REDIRECTED:
        $redirects[] = $message;
        break;
      case STREAM_NOTIFY_MIME_TYPE_IS:
        $content_type = $message;
        break;
      };
    }));
  $result = @file_get_contents($url, false, $context);
  if($result === false) {
    throw new JsonLdException(
      'Could not retrieve a JSON-LD document from the URL: ' + $url,
      'jsonld.LoadDocumentError', 'loading document failed');
  }
  $link_header = array();
  foreach($http_response_header as $header) {
    if(strpos($header, 'link') === 0) {
      $value = explode(': ', $header);
      if(count($value) > 1) {
        $link_header[] = $value[1];
      }
    }
  }
  $link_header = jsonld_parse_link_header(join(',', $link_header));
  if(isset($link_header['http://www.w3.org/ns/json-ld#context'])) {
    $link_header = $link_header['http://www.w3.org/ns/json-ld#context'];
  } else {
    $link_header = null;
  }
  if($link_header && $content_type !== 'application/ld+json') {
    // only 1 related link header permitted
    if(is_array($link_header)) {
      throw new JsonLdException(
        'URL could not be dereferenced, it has more than one ' .
        'associated HTTP Link Header.', 'jsonld.LoadDocumentError',
        'multiple context link headers', array('url' => $url));
    }
    $doc->{'contextUrl'} = $link_header->target;
  }

  // update document url based on redirects
  foreach($redirects as $redirect) {
    if(strpos($redirect, 'https') !== 0) {
      throw new JsonLdException(
        "Could not GET redirected url: '$redirect'; 'https' is required.",
        'jsonld.LoadDocumentError', 'loading document failed');
    }
    $url = $redirect;
  }
  $doc->document = $result;
  $doc->documentUrl = $url;
  return $doc;
}

/** Registered global RDF dataset parsers hashed by content-type. */
global $jsonld_rdf_parsers;
$jsonld_rdf_parsers = new stdClass();

/**
 * Registers a global RDF dataset parser by content-type, for use with
 * jsonld_from_rdf. Global parsers will be used by JsonLdProcessors that do
 * not register their own parsers.
 *
 * @param string $content_type the content-type for the parser.
 * @param callable $parser(input) the parser function (takes a string as
 *          a parameter and returns an RDF dataset).
 */
function jsonld_register_rdf_parser($content_type, $parser) {
  global $jsonld_rdf_parsers;
  $jsonld_rdf_parsers->{$content_type} = $parser;
}

/**
 * Unregisters a global RDF dataset parser by content-type.
 *
 * @param string $content_type the content-type for the parser.
 */
function jsonld_unregister_rdf_parser($content_type) {
  global $jsonld_rdf_parsers;
  if(property_exists($jsonld_rdf_parsers, $content_type)) {
    unset($jsonld_rdf_parsers->{$content_type});
  }
}

/**
 * Parses a URL into its component parts.
 *
 * @param string $url the URL to parse.
 *
 * @return assoc the parsed URL.
 */
function jsonld_parse_url($url) {
  if($url === null) {
    $url = '';
  }

  $keys = array(
    'href', 'protocol', 'scheme', '?authority', 'authority',
    '?auth', 'auth', 'user', 'pass', 'host', '?port', 'port', 'path',
    '?query', 'query', '?fragment', 'fragment');
  $regex = "/^(([^:\/?#]+):)?(\/\/(((([^:@]*)(?::([^:@]*))?)?@)?([^:\/?#]*)(:(\d*))?))?([^?#]*)(\?([^#]*))?(#(.*))?/";
  preg_match($regex, $url, $match);

  $rval = array();
  $flags = array();
  $len = count($keys);
  for($i = 0; $i < $len; ++$i) {
    $key = $keys[$i];
    if(strpos($key, '?') === 0) {
      $flags[substr($key, 1)] = !empty($match[$i]);
    } else if(!isset($match[$i])) {
      $rval[$key] = null;
    } else {
      $rval[$key] = $match[$i];
    }
  }

  if(!$flags['authority']) {
    $rval['authority'] = null;
  }
  if(!$flags['auth']) {
    $rval['auth'] = $rval['user'] = $rval['pass'] = null;
  }
  if(!$flags['port']) {
    $rval['port'] = null;
  }
  if(!$flags['query']) {
    $rval['query'] = null;
  }
  if(!$flags['fragment']) {
    $rval['fragment'] = null;
  }

  $rval['normalizedPath'] = jsonld_remove_dot_segments(
    $rval['path'], !!$rval['authority']);

  return $rval;
}

/**
 * Removes dot segments from a URL path.
 *
 * @param string $path the path to remove dot segments from.
 * @param bool $has_authority true if the URL has an authority, false if not.
 */
function jsonld_remove_dot_segments($path, $has_authority) {
  $rval = '';

  if(strpos($path, '/') === 0) {
    $rval = '/';
  }

  // RFC 3986 5.2.4 (reworked)
  $input = explode('/', $path);
  $output = array();
  while(count($input) > 0) {
    if($input[0] === '.' || ($input[0] === '' && count($input) > 1)) {
      array_shift($input);
      continue;
    }
    if($input[0] === '..') {
      array_shift($input);
      if($has_authority ||
        (count($output) > 0 && $output[count($output) - 1] !== '..')) {
        array_pop($output);
      } else {
        // leading relative URL '..'
        $output[] = '..';
      }
      continue;
    }
    $output[] = array_shift($input);
  }

  return $rval . implode('/', $output);
}

/**
 * Prepends a base IRI to the given relative IRI.
 *
 * @param mixed $base a string or the parsed base IRI.
 * @param string $iri the relative IRI.
 *
 * @return string the absolute IRI.
 */
function jsonld_prepend_base($base, $iri) {
  // skip IRI processing
  if($base === null) {
    return $iri;
  }

  // already an absolute IRI
  if(strpos($iri, ':') !== false) {
    return $iri;
  }

  // parse base if it is a string
  if(is_string($base)) {
    $base = jsonld_parse_url($base);
  }

  // parse given IRI
  $rel = jsonld_parse_url($iri);

  // per RFC3986 5.2.2
  $transform = array('protocol' => $base['protocol']);

  if($rel['authority'] !== null) {
    $transform['authority'] = $rel['authority'];
    $transform['path'] = $rel['path'];
    $transform['query'] = $rel['query'];
  } else {
    $transform['authority'] = $base['authority'];

    if($rel['path'] === '') {
      $transform['path'] = $base['path'];
      if($rel['query'] !== null) {
        $transform['query'] = $rel['query'];
      } else {
        $transform['query'] = $base['query'];
      }
    } else {
      if(strpos($rel['path'], '/') === 0) {
        // IRI represents an absolute path
        $transform['path'] = $rel['path'];
      } else {
        // merge paths
        $path = $base['path'];

        // append relative path to the end of the last directory from base
        if($rel['path'] !== '') {
          $idx = strrpos($path, '/');
          $idx = ($idx === false) ? 0 : $idx + 1;
          $path = substr($path, 0, $idx);
          if(strlen($path) > 0 && substr($path, -1) !== '/') {
            $path .= '/';
          }
          $path .= $rel['path'];
        }

        $transform['path'] = $path;
      }
      $transform['query'] = $rel['query'];
    }
  }

  // remove slashes and dots in path
  $transform['path'] = jsonld_remove_dot_segments(
    $transform['path'], !!$transform['authority']);

  // construct URL
  $rval = $transform['protocol'];
  if($transform['authority'] !== null) {
    $rval .= '//' . $transform['authority'];
  }
  $rval .= $transform['path'];
  if($transform['query'] !== null) {
    $rval .= '?' . $transform['query'];
  }
  if($rel['fragment'] !== null) {
    $rval .= '#' . $rel['fragment'];
  }

  // handle empty base
  if($rval === '') {
    $rval = './';
  }

  return $rval;
}

/**
 * Removes a base IRI from the given absolute IRI.
 *
 * @param mixed $base the base IRI.
 * @param string $iri the absolute IRI.
 *
 * @return string the relative IRI if relative to base, otherwise the absolute
 *           IRI.
 */
function jsonld_remove_base($base, $iri) {
  // skip IRI processing
  if($base === null) {
    return $iri;
  }

  if(is_string($base)) {
    $base = jsonld_parse_url($base);
  }

  // establish base root
  $root = '';
  if($base['href'] !== '') {
    $root .= "{$base['protocol']}//{$base['authority']}";
  } else if(strpos($iri, '//') === false) {
    // support network-path reference with empty base
    $root .= '//';
  }

  // IRI not relative to base
  if($root === '' || strpos($iri, $root) !== 0) {
    return $iri;
  }

  // remove root from IRI
  $rel = jsonld_parse_url(substr($iri, strlen($root)));

  // remove path segments that match (do not remove last segment unless there
  // is a hash or query)
  $base_segments = explode('/', $base['normalizedPath']);
  $iri_segments = explode('/', $rel['normalizedPath']);
  $last = ($rel['query'] || $rel['fragment']) ? 0 : 1;
  while(count($base_segments) > 0 && count($iri_segments) > $last) {
    if($base_segments[0] !== $iri_segments[0]) {
      break;
    }
    array_shift($base_segments);
    array_shift($iri_segments);
  }

  // use '../' for each non-matching base segment
  $rval = '';
  if(count($base_segments) > 0) {
    // don't count the last segment (if it ends with '/' last path doesn't
    // count and if it doesn't end with '/' it isn't a path)
    array_pop($base_segments);
    foreach($base_segments as $segment) {
      $rval .= '../';
    }
  }

  // prepend remaining segments
  $rval .= implode('/', $iri_segments);

  // add query and hash
  if($rel['query'] !== null) {
    $rval .= "?{$rel['query']}";
  }
  if($rel['fragment'] !== null) {
    $rval .= "#{$rel['fragment']}";
  }

  if($rval === '') {
    $rval = './';
  }

  return $rval;
}


/**
 * A JSON-LD processor.
 */
class JsonLdProcessor {
  /** XSD constants */
  const XSD_BOOLEAN = 'http://www.w3.org/2001/XMLSchema#boolean';
  const XSD_DOUBLE = 'http://www.w3.org/2001/XMLSchema#double';
  const XSD_INTEGER = 'http://www.w3.org/2001/XMLSchema#integer';
  const XSD_STRING = 'http://www.w3.org/2001/XMLSchema#string';

  /** RDF constants */
  const RDF_LIST = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#List';
  const RDF_FIRST = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#first';
  const RDF_REST = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#rest';
  const RDF_NIL = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil';
  const RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';
  const RDF_LANGSTRING =
    'http://www.w3.org/1999/02/22-rdf-syntax-ns#langString';

  /** Restraints */
  const MAX_CONTEXT_URLS = 10;

  /** Processor-specific RDF dataset parsers. */
  protected $rdfParsers = null;

  /**
   * Constructs a JSON-LD processor.
   */
  public function __construct() {}

  /**
   * Performs JSON-LD compaction.
   *
   * @param mixed $input the JSON-LD object to compact.
   * @param mixed $ctx the context to compact with.
   * @param assoc $options the compaction options.
   *          [base] the base IRI to use.
   *          [compactArrays] true to compact arrays to single values when
   *            appropriate, false not to (default: true).
   *          [graph] true to always output a top-level graph (default: false).
   *          [skipExpansion] true to assume the input is expanded and skip
   *            expansion, false not to, defaults to false.
   *          [activeCtx] true to also return the active context used.
   *          [documentLoader(url)] the document loader.
   *
   * @return mixed the compacted JSON-LD output.
   */
  public function compact($input, $ctx, $options) {
    global $jsonld_default_load_document;

    if($ctx === null) {
      throw new JsonLdException(
        'The compaction context must not be null.',
        'jsonld.CompactError', 'invalid local context');
    }

    // nothing to compact
    if($input === null) {
      return null;
    }

    self::setdefaults($options, array(
      'base' => is_string($input) ? $input : '',
      'compactArrays' => true,
      'graph' => false,
      'skipExpansion' => false,
      'activeCtx' => false,
      'documentLoader' => $jsonld_default_load_document,
      'link' => false));
    if($options['link']) {
      // force skip expansion when linking, "link" is not part of the
      // public API, it should only be called from framing
      $options['skipExpansion'] = true;
    }

    if($options['skipExpansion'] === true) {
      $expanded = $input;
    } else {
      // expand input
      try {
        $expanded = $this->expand($input, $options);
      } catch(JsonLdException $e) {
        throw new JsonLdException(
          'Could not expand input before compaction.',
          'jsonld.CompactError', null, null, $e);
      }
    }

    // process context
    $active_ctx = $this->_getInitialContext($options);
    try {
      $active_ctx = $this->processContext($active_ctx, $ctx, $options);
    } catch(JsonLdException $e) {
      throw new JsonLdException(
        'Could not process context before compaction.',
        'jsonld.CompactError', null, null, $e);
    }

    // do compaction
    $compacted = $this->_compact($active_ctx, null, $expanded, $options);

    if($options['compactArrays'] &&
      !$options['graph'] && is_array($compacted)) {
      if(count($compacted) === 1) {
        // simplify to a single item
        $compacted = $compacted[0];
      } else if(count($compacted) === 0) {
        // simplify to an empty object
        $compacted = new stdClass();
      }
    } else if($options['graph']) {
      // always use array if graph option is on
      $compacted = self::arrayify($compacted);
    }

    // follow @context key
    if(is_object($ctx) && property_exists($ctx, '@context')) {
      $ctx = $ctx->{'@context'};
    }

    // build output context
    $ctx = self::copy($ctx);
    $ctx = self::arrayify($ctx);

    // remove empty contexts
    $tmp = $ctx;
    $ctx = array();
    foreach($tmp as $v) {
      if(!is_object($v) || count(array_keys((array)$v)) > 0) {
        $ctx[] = $v;
      }
    }

    // remove array if only one context
    $ctx_length = count($ctx);
    $has_context = ($ctx_length > 0);
    if($ctx_length === 1) {
      $ctx = $ctx[0];
    }

    // add context and/or @graph
    if(is_array($compacted)) {
      // use '@graph' keyword
      $kwgraph = $this->_compactIri($active_ctx, '@graph');
      $graph = $compacted;
      $compacted = new stdClass();
      if($has_context) {
        $compacted->{'@context'} = $ctx;
      }
      $compacted->{$kwgraph} = $graph;
    } else if(is_object($compacted) && $has_context) {
      // reorder keys so @context is first
      $graph = $compacted;
      $compacted = new stdClass();
      $compacted->{'@context'} = $ctx;
      foreach($graph as $k => $v) {
        $compacted->{$k} = $v;
      }
    }

    if($options['activeCtx']) {
      return array('compacted' => $compacted, 'activeCtx' => $active_ctx);
    }

    return $compacted;
  }

  /**
   * Performs JSON-LD expansion.
   *
   * @param mixed $input the JSON-LD object to expand.
   * @param assoc $options the options to use:
   *          [base] the base IRI to use.
   *          [expandContext] a context to expand with.
   *          [keepFreeFloatingNodes] true to keep free-floating nodes,
   *            false not to, defaults to false.
   *          [documentLoader(url)] the document loader.
   *
   * @return array the expanded JSON-LD output.
   */
  public function expand($input, $options) {
    global $jsonld_default_load_document;
    self::setdefaults($options, array(
      'keepFreeFloatingNodes' => false,
      'documentLoader' => $jsonld_default_load_document));

    // if input is a string, attempt to dereference remote document
    if(is_string($input)) {
      $remote_doc = call_user_func($options['documentLoader'], $input);
    } else {
      $remote_doc = (object)array(
        'contextUrl' => null,
        'documentUrl' => null,
        'document' => $input);
    }

    try {
      if($remote_doc->document === null) {
        throw new JsonLdException(
          'No remote document found at the given URL.',
          'jsonld.NullRemoteDocument');
      }
      if(is_string($remote_doc->document)) {
        $remote_doc->document = self::_parse_json($remote_doc->document);
      }
    } catch(Exception $e) {
      throw new JsonLdException(
        'Could not retrieve a JSON-LD document from the URL.',
        'jsonld.LoadDocumentError', 'loading document failed',
        array('remoteDoc' => $remote_doc), $e);
    }

    // set default base
    self::setdefault($options, 'base', $remote_doc->documentUrl ?: '');

    // build meta-object and retrieve all @context urls
    $input = (object)array(
      'document' => self::copy($remote_doc->document),
      'remoteContext' => (object)array(
        '@context' => $remote_doc->contextUrl));
    if(isset($options['expandContext'])) {
      $expand_context = self::copy($options['expandContext']);
      if(is_object($expand_context) &&
        property_exists($expand_context, '@context')) {
        $input->expandContext = $expand_context;
      } else {
        $input->expandContext = (object)array('@context' => $expand_context);
      }
    }

    // retrieve all @context URLs in the input
    try {
      $this->_retrieveContextUrls(
        $input, new stdClass(), $options['documentLoader'], $options['base']);
    } catch(Exception $e) {
      throw new JsonLdException(
        'Could not perform JSON-LD expansion.',
        'jsonld.ExpandError', null, null, $e);
    }

    $active_ctx = $this->_getInitialContext($options);
    $document = $input->document;
    $remote_context = $input->remoteContext->{'@context'};

    // process optional expandContext
    if(property_exists($input, 'expandContext')) {
      $active_ctx = self::_processContext(
        $active_ctx, $input->expandContext, $options);
    }

    // process remote context from HTTP Link Header
    if($remote_context) {
      $active_ctx = self::_processContext(
        $active_ctx, $remote_context, $options);
    }

    // do expansion
    $expanded = $this->_expand($active_ctx, null, $document, $options, false);

    // optimize away @graph with no other properties
    if(is_object($expanded) && property_exists($expanded, '@graph') &&
      count(array_keys((array)$expanded)) === 1) {
      $expanded = $expanded->{'@graph'};
    } else if($expanded === null) {
      $expanded = array();
    }
    // normalize to an array
    return self::arrayify($expanded);
  }

  /**
   * Performs JSON-LD flattening.
   *
   * @param mixed $input the JSON-LD to flatten.
   * @param ctx the context to use to compact the flattened output, or null.
   * @param assoc $options the options to use:
   *          [base] the base IRI to use.
   *          [expandContext] a context to expand with.
   *          [documentLoader(url)] the document loader.
   *
   * @return array the flattened output.
   */
  public function flatten($input, $ctx, $options) {
    global $jsonld_default_load_document;
    self::setdefaults($options, array(
      'base' => is_string($input) ? $input : '',
      'documentLoader' => $jsonld_default_load_document));

    try {
      // expand input
      $expanded = $this->expand($input, $options);
    } catch(Exception $e) {
      throw new JsonLdException(
        'Could not expand input before flattening.',
        'jsonld.FlattenError', null, null, $e);
    }

    // do flattening
    $flattened = $this->_flatten($expanded);

    if($ctx === null) {
      return $flattened;
    }

    // compact result (force @graph option to true, skip expansion)
    $options['graph'] = true;
    $options['skipExpansion'] = true;
    try {
      $compacted = $this->compact($flattened, $ctx, $options);
    } catch(Exception $e) {
      throw new JsonLdException(
        'Could not compact flattened output.',
        'jsonld.FlattenError', null, null, $e);
    }

    return $compacted;
  }

  /**
   * Performs JSON-LD framing.
   *
   * @param mixed $input the JSON-LD object to frame.
   * @param stdClass $frame the JSON-LD frame to use.
   * @param $options the framing options.
   *          [base] the base IRI to use.
   *          [expandContext] a context to expand with.
   *          [embed] default @embed flag: '@last', '@always', '@never', '@link'
   *            (default: '@last').
   *          [explicit] default @explicit flag (default: false).
   *          [requireAll] default @requireAll flag (default: true).
   *          [omitDefault] default @omitDefault flag (default: false).
   *          [documentLoader(url)] the document loader.
   *
   * @return stdClass the framed JSON-LD output.
   */
  public function frame($input, $frame, $options) {
    global $jsonld_default_load_document;
    self::setdefaults($options, array(
      'base' => is_string($input) ? $input : '',
      'compactArrays' => true,
      'embed' => '@last',
      'explicit' => false,
      'requireAll' => true,
      'omitDefault' => false,
      'documentLoader' => $jsonld_default_load_document));

    // if frame is a string, attempt to dereference remote document
    if(is_string($frame)) {
      $remote_frame = call_user_func($options['documentLoader'], $frame);
    } else {
      $remote_frame = (object)array(
        'contextUrl' => null,
        'documentUrl' => null,
        'document' => $frame);
    }

    try {
      if($remote_frame->document === null) {
        throw new JsonLdException(
          'No remote document found at the given URL.',
          'jsonld.NullRemoteDocument');
      }
      if(is_string($remote_frame->document)) {
        $remote_frame->document = self::_parse_json($remote_frame->document);
      }
    } catch(Exception $e) {
      throw new JsonLdException(
        'Could not retrieve a JSON-LD document from the URL.',
        'jsonld.LoadDocumentError', 'loading document failed',
        array('remoteDoc' => $remote_frame), $e);
    }

    // preserve frame context
    $frame = $remote_frame->document;
    if($frame !== null) {
      $ctx = (property_exists($frame, '@context') ?
        $frame->{'@context'} : new stdClass());
      if($remote_frame->contextUrl !== null) {
        if($ctx !== null) {
          $ctx = $remote_frame->contextUrl;
        } else {
          $ctx = self::arrayify($ctx);
          $ctx[] = $remote_frame->contextUrl;
        }
        $frame->{'@context'} = $ctx;
      }
    }

    try {
      // expand input
      $expanded = $this->expand($input, $options);
    } catch(Exception $e) {
      throw new JsonLdException(
        'Could not expand input before framing.',
        'jsonld.FrameError', null, null, $e);
    }

    try {
      // expand frame
      $opts = $options;
      $opts['keepFreeFloatingNodes'] = true;
      $expanded_frame = $this->expand($frame, $opts);
    } catch(Exception $e) {
      throw new JsonLdException(
        'Could not expand frame before framing.',
        'jsonld.FrameError', null, null, $e);
    }

    // do framing
    $framed = $this->_frame($expanded, $expanded_frame, $options);

    try {
      // compact result (force @graph option to true, skip expansion, check
      // for linked embeds)
      $options['graph'] = true;
      $options['skipExpansion'] = true;
      $options['link'] = new ArrayObject();
      $options['activeCtx'] = true;
      $result = $this->compact($framed, $ctx, $options);
    } catch(Exception $e) {
      throw new JsonLdException(
        'Could not compact framed output.',
        'jsonld.FrameError', null, null, $e);
    }

    $compacted = $result['compacted'];
    $active_ctx = $result['activeCtx'];

    // get graph alias
    $graph = $this->_compactIri($active_ctx, '@graph');
    // remove @preserve from results
    $options['link'] = new ArrayObject();
    $compacted->{$graph} = $this->_removePreserve(
      $active_ctx, $compacted->{$graph}, $options);
    return $compacted;
  }

  /**
   * Performs JSON-LD normalization.
   *
   * @param mixed $input the JSON-LD object to normalize.
   * @param assoc $options the options to use:
   *          [base] the base IRI to use.
   *          [expandContext] a context to expand with.
   *          [inputFormat] the format if input is not JSON-LD:
   *            'application/nquads' for N-Quads.
   *          [format] the format if output is a string:
   *            'application/nquads' for N-Quads.
   *          [documentLoader(url)] the document loader.
   *
   * @return mixed the normalized output.
   */
  public function normalize($input, $options) {
    global $jsonld_default_load_document;
    self::setdefaults($options, array(
      'base' => is_string($input) ? $input : '',
      'documentLoader' => $jsonld_default_load_document));

    if(isset($options['inputFormat'])) {
      if($options['inputFormat'] != 'application/nquads') {
         throw new JsonLdException(
           'Unknown normalization input format.', 'jsonld.NormalizeError');
      }
      $dataset = $this->parseNQuads($input);
    } else {
      try {
        // convert to RDF dataset then do normalization
        $opts = $options;
        if(isset($opts['format'])) {
          unset($opts['format']);
        }
        $opts['produceGeneralizedRdf'] = false;
        $dataset = $this->toRDF($input, $opts);
      } catch(Exception $e) {
        throw new JsonLdException(
          'Could not convert input to RDF dataset before normalization.',
          'jsonld.NormalizeError', null, null, $e);
      }
    }

    // do normalization
    return $this->_normalize($dataset, $options);
  }

  /**
   * Converts an RDF dataset to JSON-LD.
   *
   * @param mixed $dataset a serialized string of RDF in a format specified
   *          by the format option or an RDF dataset to convert.
   * @param assoc $options the options to use:
   *          [format] the format if input is a string:
   *            'application/nquads' for N-Quads (default).
   *          [useRdfType] true to use rdf:type, false to use @type
   *            (default: false).
   *          [useNativeTypes] true to convert XSD types into native types
   *            (boolean, integer, double), false not to (default: false).
   *
   * @return array the JSON-LD output.
   */
  public function fromRDF($dataset, $options) {
    global $jsonld_rdf_parsers;

    self::setdefaults($options, array(
      'useRdfType' => false,
      'useNativeTypes' => false));

    if(!isset($options['format']) && is_string($dataset)) {
      // set default format to nquads
      $options['format'] = 'application/nquads';
    }

    // handle special format
    if(isset($options['format']) && $options['format']) {
      // supported formats (processor-specific and global)
      if(($this->rdfParsers !== null &&
        !property_exists($this->rdfParsers, $options['format'])) ||
        $this->rdfParsers === null &&
        !property_exists($jsonld_rdf_parsers, $options['format'])) {
        throw new JsonLdException(
          'Unknown input format.',
          'jsonld.UnknownFormat', null, array('format' => $options['format']));
      }
      if($this->rdfParsers !== null) {
        $callable = $this->rdfParsers->{$options['format']};
      } else {
        $callable = $jsonld_rdf_parsers->{$options['format']};
      }
      $dataset = call_user_func($callable, $dataset);
    }

    // convert from RDF
    return $this->_fromRDF($dataset, $options);
  }

  /**
   * Outputs the RDF dataset found in the given JSON-LD object.
   *
   * @param mixed $input the JSON-LD object.
   * @param assoc $options the options to use:
   *          [base] the base IRI to use.
   *          [expandContext] a context to expand with.
   *          [format] the format to use to output a string:
   *            'application/nquads' for N-Quads.
   *          [produceGeneralizedRdf] true to output generalized RDF, false
   *            to produce only standard RDF (default: false).
   *          [documentLoader(url)] the document loader.
   *
   * @return mixed the resulting RDF dataset (or a serialization of it).
   */
  public function toRDF($input, $options) {
    global $jsonld_default_load_document;
    self::setdefaults($options, array(
      'base' => is_string($input) ? $input : '',
      'produceGeneralizedRdf' => false,
      'documentLoader' => $jsonld_default_load_document));

    try {
      // expand input
      $expanded = $this->expand($input, $options);
    } catch(JsonLdException $e) {
      throw new JsonLdException(
        'Could not expand input before serialization to RDF.',
        'jsonld.RdfError', null, null, $e);
    }

    // create node map for default graph (and any named graphs)
    $namer = new UniqueNamer('_:b');
    $node_map = (object)array('@default' => new stdClass());
    $this->_createNodeMap($expanded, $node_map, '@default', $namer);

    // output RDF dataset
    $dataset = new stdClass();
    $graph_names = array_keys((array)$node_map);
    sort($graph_names);
    foreach($graph_names as $graph_name) {
      $graph = $node_map->{$graph_name};
      // skip relative IRIs
      if($graph_name === '@default' || self::_isAbsoluteIri($graph_name)) {
        $dataset->{$graph_name} = $this->_graphToRDF($graph, $namer, $options);
      }
    }

    $rval = $dataset;

    // convert to output format
    if(isset($options['format']) && $options['format']) {
      // supported formats
      if($options['format'] === 'application/nquads') {
        $rval = self::toNQuads($dataset);
      } else {
        throw new JsonLdException(
          'Unknown output format.', 'jsonld.UnknownFormat',
          null, array('format' => $options['format']));
      }
    }

    return $rval;
  }

  /**
   * Processes a local context, resolving any URLs as necessary, and returns a
   * new active context in its callback.
   *
   * @param stdClass $active_ctx the current active context.
   * @param mixed $local_ctx the local context to process.
   * @param assoc $options the options to use:
   *          [documentLoader(url)] the document loader.
   *
   * @return stdClass the new active context.
   */
  public function processContext($active_ctx, $local_ctx, $options) {
    global $jsonld_default_load_document;
    self::setdefaults($options, array(
      'base' => '',
      'documentLoader' => $jsonld_default_load_document));

    // return initial context early for null context
    if($local_ctx === null) {
      return $this->_getInitialContext($options);
    }

    // retrieve URLs in local_ctx
    $local_ctx = self::copy($local_ctx);
    if(is_string($local_ctx) or (
      is_object($local_ctx) && !property_exists($local_ctx, '@context'))) {
      $local_ctx = (object)array('@context' => $local_ctx);
    }
    try {
      $this->_retrieveContextUrls(
        $local_ctx, new stdClass(),
        $options['documentLoader'], $options['base']);
    } catch(Exception $e) {
      throw new JsonLdException(
        'Could not process JSON-LD context.',
        'jsonld.ContextError', null, null, $e);
    }

    // process context
    return $this->_processContext($active_ctx, $local_ctx, $options);
  }

  /**
   * Returns true if the given subject has the given property.
   *
   * @param stdClass $subject the subject to check.
   * @param string $property the property to look for.
   *
   * @return bool true if the subject has the given property, false if not.
   */
  public static function hasProperty($subject, $property) {
    $rval = false;
    if(property_exists($subject, $property)) {
      $value = $subject->{$property};
      $rval = (!is_array($value) || count($value) > 0);
    }
    return $rval;
  }

  /**
   * Determines if the given value is a property of the given subject.
   *
   * @param stdClass $subject the subject to check.
   * @param string $property the property to check.
   * @param mixed $value the value to check.
   *
   * @return bool true if the value exists, false if not.
   */
  public static function hasValue($subject, $property, $value) {
    $rval = false;
    if(self::hasProperty($subject, $property)) {
      $val = $subject->{$property};
      $is_list = self::_isList($val);
      if(is_array($val) || $is_list) {
        if($is_list) {
          $val = $val->{'@list'};
        }
        foreach($val as $v) {
          if(self::compareValues($value, $v)) {
            $rval = true;
            break;
          }
        }
      } else if(!is_array($value)) {
        // avoid matching the set of values with an array value parameter
        $rval = self::compareValues($value, $val);
      }
    }
    return $rval;
  }

  /**
   * Adds a value to a subject. If the value is an array, all values in the
   * array will be added.
   *
   * Note: If the value is a subject that already exists as a property of the
   * given subject, this method makes no attempt to deeply merge properties.
   * Instead, the value will not be added.
   *
   * @param stdClass $subject the subject to add the value to.
   * @param string $property the property that relates the value to the subject.
   * @param mixed $value the value to add.
   * @param assoc [$options] the options to use:
   *          [propertyIsArray] true if the property is always an array, false
   *            if not (default: false).
   *          [allowDuplicate] true to allow duplicates, false not to (uses a
   *            simple shallow comparison of subject ID or value)
   *            (default: true).
   */
  public static function addValue(
    $subject, $property, $value, $options=array()) {
    self::setdefaults($options, array(
      'allowDuplicate' => true,
      'propertyIsArray' => false));

    if(is_array($value)) {
      if(count($value) === 0 && $options['propertyIsArray'] &&
        !property_exists($subject, $property)) {
        $subject->{$property} = array();
      }
      foreach($value as $v) {
        self::addValue($subject, $property, $v, $options);
      }
    } else if(property_exists($subject, $property)) {
      // check if subject already has value if duplicates not allowed
      $has_value = (!$options['allowDuplicate'] &&
        self::hasValue($subject, $property, $value));

      // make property an array if value not present or always an array
      if(!is_array($subject->{$property}) &&
        (!$has_value || $options['propertyIsArray'])) {
        $subject->{$property} = array($subject->{$property});
      }

      // add new value
      if(!$has_value) {
        $subject->{$property}[] = $value;
      }
    } else {
      // add new value as set or single value
      $subject->{$property} = ($options['propertyIsArray'] ?
        array($value) : $value);
    }
  }

  /**
   * Gets all of the values for a subject's property as an array.
   *
   * @param stdClass $subject the subject.
   * @param string $property the property.
   *
   * @return array all of the values for a subject's property as an array.
   */
  public static function getValues($subject, $property) {
    $rval = (property_exists($subject, $property) ?
      $subject->{$property} : array());
    return self::arrayify($rval);
  }

  /**
   * Removes a property from a subject.
   *
   * @param stdClass $subject the subject.
   * @param string $property the property.
   */
  public static function removeProperty($subject, $property) {
    unset($subject->{$property});
  }

  /**
   * Removes a value from a subject.
   *
   * @param stdClass $subject the subject.
   * @param string $property the property that relates the value to the subject.
   * @param mixed $value the value to remove.
   * @param assoc [$options] the options to use:
   *          [propertyIsArray] true if the property is always an array,
   *          false if not (default: false).
   */
  public static function removeValue(
    $subject, $property, $value, $options=array()) {
    self::setdefaults($options, array(
      'propertyIsArray' => false));

    // filter out value
    $filter = function($e) use ($value) {
      return !self::compareValues($e, $value);
    };
    $values = self::getValues($subject, $property);
    $values = array_values(array_filter($values, $filter));

    if(count($values) === 0) {
      self::removeProperty($subject, $property);
    } else if(count($values) === 1 && !$options['propertyIsArray']) {
      $subject->{$property} = $values[0];
    } else {
      $subject->{$property} = $values;
    }
  }

  /**
   * Compares two JSON-LD values for equality. Two JSON-LD values will be
   * considered equal if:
   *
   * 1. They are both primitives of the same type and value.
   * 2. They are both @values with the same @value, @type, @language,
   *   and @index, OR
   * 3. They both have @ids that are the same.
   *
   * @param mixed $v1 the first value.
   * @param mixed $v2 the second value.
   *
   * @return bool true if v1 and v2 are considered equal, false if not.
   */
  public static function compareValues($v1, $v2) {
    // 1. equal primitives
    if($v1 === $v2) {
      return true;
    }

    // 2. equal @values
    if(self::_isValue($v1) && self::_isValue($v2)) {
      return (
        self::_compareKeyValues($v1, $v2, '@value') &&
        self::_compareKeyValues($v1, $v2, '@type') &&
        self::_compareKeyValues($v1, $v2, '@language') &&
        self::_compareKeyValues($v1, $v2, '@index'));
    }

    // 3. equal @ids
    if(is_object($v1) && property_exists($v1, '@id') &&
      is_object($v2) && property_exists($v2, '@id')) {
      return $v1->{'@id'} === $v2->{'@id'};
    }

    return false;
  }

  /**
   * Gets the value for the given active context key and type, null if none is
   * set.
   *
   * @param stdClass $ctx the active context.
   * @param string $key the context key.
   * @param string [$type] the type of value to get (eg: '@id', '@type'), if not
   *          specified gets the entire entry for a key, null if not found.
   *
   * @return mixed the value.
   */
  public static function getContextValue($ctx, $key, $type) {
    $rval = null;

    // return null for invalid key
    if($key === null) {
      return $rval;
    }

    // get default language
    if($type === '@language' && property_exists($ctx, $type)) {
      $rval = $ctx->{$type};
    }

    // get specific entry information
    if(property_exists($ctx->mappings, $key)) {
      $entry = $ctx->mappings->{$key};
      if($entry === null) {
        return null;
      }

      if($type === null) {
        // return whole entry
        $rval = $entry;
      } else if(property_exists($entry, $type)) {
        // return entry value for type
        $rval = $entry->{$type};
      }
    }

    return $rval;
  }

  /**
   * Parses RDF in the form of N-Quads.
   *
   * @param string $input the N-Quads input to parse.
   *
   * @return stdClass an RDF dataset.
   */
  public static function parseNQuads($input) {
    // define partial regexes
    $iri = '(?:<([^:]+:[^>]*)>)';
    $bnode = '(_:(?:[A-Za-z][A-Za-z0-9]*))';
    $plain = '"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"';
    $datatype = "(?:\\^\\^$iri)";
    $language = '(?:@([a-z]+(?:-[a-z0-9]+)*))';
    $literal = "(?:$plain(?:$datatype|$language)?)";
    $ws = '[ \\t]';
    $eoln = '/(?:\r\n)|(?:\n)|(?:\r)/';
    $empty = "/^$ws*$/";

    // define quad part regexes
    $subject = "(?:$iri|$bnode)$ws+";
    $property = "$iri$ws+";
    $object = "(?:$iri|$bnode|$literal)$ws*";
    $graph_name = "(?:\\.|(?:(?:$iri|$bnode)$ws*\\.))";

    // full quad regex
    $quad = "/^$ws*$subject$property$object$graph_name$ws*$/";

    // build RDF dataset
    $dataset = new stdClass();

    // split N-Quad input into lines
    $lines = preg_split($eoln, $input);
    $line_number = 0;
    foreach($lines as $line) {
      $line_number += 1;

      // skip empty lines
      if(preg_match($empty, $line)) {
        continue;
      }

      // parse quad
      if(!preg_match($quad, $line, $match)) {
        throw new JsonLdException(
          'Error while parsing N-Quads; invalid quad.',
          'jsonld.ParseError', null, array('line' => $line_number));
      }

      // create RDF triple
      $triple = (object)array(
        'subject' => new stdClass(),
        'predicate' => new stdClass(),
        'object' => new stdClass());

      // get subject
      if($match[1] !== '') {
        $triple->subject->type = 'IRI';
        $triple->subject->value = $match[1];
      } else {
        $triple->subject->type = 'blank node';
        $triple->subject->value = $match[2];
      }

      // get predicate
      $triple->predicate->type = 'IRI';
      $triple->predicate->value = $match[3];

      // get object
      if($match[4] !== '') {
        $triple->object->type = 'IRI';
        $triple->object->value = $match[4];
      } else if($match[5] !== '') {
        $triple->object->type = 'blank node';
        $triple->object->value = $match[5];
      } else {
        $triple->object->type = 'literal';
        $unescaped = str_replace(
          array('\"', '\t', '\n', '\r', '\\\\'),
          array('"', "\t", "\n", "\r", '\\'),
          $match[6]);
        if(isset($match[7]) && $match[7] !== '') {
          $triple->object->datatype = $match[7];
        } else if(isset($match[8]) && $match[8] !== '') {
          $triple->object->datatype = self::RDF_LANGSTRING;
          $triple->object->language = $match[8];
        } else {
          $triple->object->datatype = self::XSD_STRING;
        }
        $triple->object->value = $unescaped;
      }

      // get graph name ('@default' is used for the default graph)
      $name = '@default';
      if(isset($match[9]) && $match[9] !== '') {
        $name = $match[9];
      } else if(isset($match[10]) && $match[10] !== '') {
        $name = $match[10];
      }

      // initialize graph in dataset
      if(!property_exists($dataset, $name)) {
        $dataset->{$name} = array($triple);
      } else {
        // add triple if unique to its graph
        $unique = true;
        $triples = &$dataset->{$name};
        foreach($triples as $t) {
          if(self::_compareRDFTriples($t, $triple)) {
            $unique = false;
            break;
          }
        }
        if($unique) {
          $triples[] = $triple;
        }
      }
    }

    return $dataset;
  }

  /**
   * Converts an RDF dataset to N-Quads.
   *
   * @param stdClass $dataset the RDF dataset to convert.
   *
   * @return string the N-Quads string.
   */
  public static function toNQuads($dataset) {
    $quads = array();
    foreach($dataset as $graph_name => $triples) {
      foreach($triples as $triple) {
        if($graph_name === '@default') {
          $graph_name = null;
        }
        $quads[] = self::toNQuad($triple, $graph_name);
      }
    }
    sort($quads);
    return implode($quads);
  }

  /**
   * Converts an RDF triple and graph name to an N-Quad string (a single quad).
   *
   * @param stdClass $triple the RDF triple to convert.
   * @param mixed $graph_name the name of the graph containing the triple, null
   *          for the default graph.
   * @param string $bnode the bnode the quad is mapped to (optional, for
   *          use during normalization only).
   *
   * @return string the N-Quad string.
   */
  public static function toNQuad($triple, $graph_name, $bnode=null) {
    $s = $triple->subject;
    $p = $triple->predicate;
    $o = $triple->object;
    $g = $graph_name;

    $quad = '';

    // subject is an IRI
    if($s->type === 'IRI') {
      $quad .= "<{$s->value}>";
    } else if($bnode !== null) {
      // bnode normalization mode
      $quad .= ($s->value === $bnode) ? '_:a' : '_:z';
    } else {
      // bnode normal mode
      $quad .= $s->value;
    }
    $quad .= ' ';

    // predicate is an IRI
    if($p->type === 'IRI') {
      $quad .= "<{$p->value}>";
    } else if($bnode !== null) {
      // FIXME: TBD what to do with bnode predicates during normalization
      // bnode normalization mode
      $quad .= '_:p';
    } else {
      // bnode normal mode
      $quad .= $p->value;
    }
    $quad .= ' ';

    // object is IRI, bnode, or literal
    if($o->type === 'IRI') {
      $quad .= "<{$o->value}>";
    } else if($o->type === 'blank node') {
      if($bnode !== null) {
        // normalization mode
        $quad .= ($o->value === $bnode) ? '_:a' : '_:z';
      } else {
        // normal mode
        $quad .= $o->value;
      }
    } else {
      $escaped = str_replace(
        array('\\', "\t", "\n", "\r", '"'),
        array('\\\\', '\t', '\n', '\r', '\"'),
        $o->value);
      $quad .= '"' . $escaped . '"';
      if($o->datatype === self::RDF_LANGSTRING) {
        if($o->language) {
          $quad .= "@{$o->language}";
        }
      } else if($o->datatype !== self::XSD_STRING) {
        $quad .= "^^<{$o->datatype}>";
      }
    }

    // graph
    if($g !== null) {
      if(strpos($g, '_:') !== 0) {
        $quad .= " <$g>";
      } else if($bnode) {
        $quad .= ' _:g';
      } else {
        $quad .= " $g";
      }
    }

    $quad .= " .\n";
    return $quad;
  }

  /**
   * Registers a processor-specific RDF dataset parser by content-type.
   * Global parsers will no longer be used by this processor.
   *
   * @param string $content_type the content-type for the parser.
   * @param callable $parser(input) the parser function (takes a string as
   *           a parameter and returns an RDF dataset).
   */
  public function registerRDFParser($content_type, $parser) {
    if($this->rdfParsers === null) {
      $this->rdfParsers = new stdClass();
    }
    $this->rdfParsers->{$content_type} = $parser;
  }

  /**
   * Unregisters a process-specific RDF dataset parser by content-type. If
   * there are no remaining processor-specific parsers, then the global
   * parsers will be re-enabled.
   *
   * @param string $content_type the content-type for the parser.
   */
  public function unregisterRDFParser($content_type) {
    if($this->rdfParsers !== null &&
      property_exists($this->rdfParsers, $content_type)) {
      unset($this->rdfParsers->{$content_type});
      if(count(get_object_vars($content_type)) === 0) {
        $this->rdfParsers = null;
      }
    }
  }

  /**
   * If $value is an array, returns $value, otherwise returns an array
   * containing $value as the only element.
   *
   * @param mixed $value the value.
   *
   * @return array an array.
   */
  public static function arrayify($value) {
    return is_array($value) ? $value : array($value);
  }

  /**
   * Clones an object, array, or string/number.
   *
   * @param mixed $value the value to clone.
   *
   * @return mixed the cloned value.
   */
  public static function copy($value) {
    if(is_object($value) || is_array($value)) {
      return unserialize(serialize($value));
    }
    return $value;
  }

  /**
   * Sets the value of a key for the given array if that property
   * has not already been set.
   *
   * @param &assoc $arr the object to update.
   * @param string $key the key to update.
   * @param mixed $value the value to set.
   */
  public static function setdefault(&$arr, $key, $value) {
    isset($arr[$key]) or $arr[$key] = $value;
  }

  /**
   * Sets default values for keys in the given array.
   *
   * @param &assoc $arr the object to update.
   * @param assoc $defaults the default keys and values.
   */
  public static function setdefaults(&$arr, $defaults) {
    foreach($defaults as $key => $value) {
      self::setdefault($arr, $key, $value);
    }
  }

  /**
   * Recursively compacts an element using the given active context. All values
   * must be in expanded form before this method is called.
   *
   * @param stdClass $active_ctx the active context to use.
   * @param mixed $active_property the compacted property with the element
   *          to compact, null for none.
   * @param mixed $element the element to compact.
   * @param assoc $options the compaction options.
   *
   * @return mixed the compacted value.
   */
  protected function _compact(
    $active_ctx, $active_property, $element, $options) {
    // recursively compact array
    if(is_array($element)) {
      $rval = array();
      foreach($element as $e) {
        // compact, dropping any null values
        $compacted = $this->_compact(
          $active_ctx, $active_property, $e, $options);
        if($compacted !== null) {
          $rval[] = $compacted;
        }
      }
      if($options['compactArrays'] && count($rval) === 1) {
        // use single element if no container is specified
        $container = self::getContextValue(
          $active_ctx, $active_property, '@container');
        if($container === null) {
          $rval = $rval[0];
        }
      }
      return $rval;
    }

    // recursively compact object
    if(is_object($element)) {
      if($options['link'] && property_exists($element, '@id') &&
        isset($options['link'][$element->{'@id'}])) {
        // check for a linked element to reuse
        $linked = $options['link'][$element->{'@id'}];
        foreach($linked as $link) {
          if($link['expanded'] === $element) {
            return $link['compacted'];
          }
        }
      }

      // do value compaction on @values and subject references
      if(self::_isValue($element) || self::_isSubjectReference($element)) {
        $rval = $this->_compactValue($active_ctx, $active_property, $element);
        if($options['link'] && self::_isSubjectReference($element)) {
          // store linked element
          if(!isset($options['link'][$element->{'@id'}])) {
            $options['link'][$element->{'@id'}] = array();
          }
          $options['link'][$element->{'@id'}][] = array(
            'expanded' => $element, 'compacted' => $rval);
        }
        return $rval;
      }

      // FIXME: avoid misuse of active property as an expanded property?
      $inside_reverse = ($active_property === '@reverse');

      $rval = new stdClass();

      if($options['link'] && property_exists($element, '@id')) {
        // store linked element
        if(!isset($options['link'][$element->{'@id'}])) {
          $options['link'][$element->{'@id'}] = array();
        }
        $options['link'][$element->{'@id'}][] = array(
          'expanded' => $element, 'compacted' => $rval);
      }

      // process element keys in order
      $keys = array_keys((array)$element);
      sort($keys);
      foreach($keys as $expanded_property) {
        $expanded_value = $element->{$expanded_property};

        // compact @id and @type(s)
        if($expanded_property === '@id' || $expanded_property === '@type') {
          if(is_string($expanded_value)) {
            // compact single @id
            $compacted_value = $this->_compactIri(
              $active_ctx, $expanded_value, null,
              array('vocab' => ($expanded_property === '@type')));
          } else {
            // expanded value must be a @type array
            $compacted_value = array();
            foreach($expanded_value as $ev) {
              $compacted_value[] = $this->_compactIri(
                $active_ctx, $ev, null, array('vocab' => true));
            }
          }

          // use keyword alias and add value
          $alias = $this->_compactIri($active_ctx, $expanded_property);
          $is_array = (is_array($compacted_value) &&
            count($expanded_value) === 0);
          self::addValue(
            $rval, $alias, $compacted_value,
            array('propertyIsArray' => $is_array));
          continue;
        }

        // handle @reverse
        if($expanded_property === '@reverse') {
          // recursively compact expanded value
          $compacted_value = $this->_compact(
            $active_ctx, '@reverse', $expanded_value, $options);

          // handle double-reversed properties
          foreach($compacted_value as $compacted_property => $value) {
            if(property_exists($active_ctx->mappings, $compacted_property) &&
              $active_ctx->mappings->{$compacted_property} &&
              $active_ctx->mappings->{$compacted_property}->reverse) {
                $container = self::getContextValue(
                  $active_ctx, $compacted_property, '@container');
              $use_array = ($container === '@set' ||
                !$options['compactArrays']);
              self::addValue(
                $rval, $compacted_property, $value,
                array('propertyIsArray' => $use_array));
              unset($compacted_value->{$compacted_property});
            }
          }

          if(count(array_keys((array)$compacted_value)) > 0) {
            // use keyword alias and add value
            $alias = $this->_compactIri($active_ctx, $expanded_property);
            self::addValue($rval, $alias, $compacted_value);
          }

          continue;
        }

        // handle @index property
        if($expanded_property === '@index') {
          // drop @index if inside an @index container
          $container = self::getContextValue(
            $active_ctx, $active_property, '@container');
          if($container === '@index') {
            continue;
          }

          // use keyword alias and add value
          $alias = $this->_compactIri($active_ctx, $expanded_property);
          self::addValue($rval, $alias, $expanded_value);
          continue;
        }

        // skip array processing for keywords that aren't @graph or @list
        if($expanded_property !== '@graph' && $expanded_property !== '@list' &&
          self::_isKeyword($expanded_property)) {
          // use keyword alias and add value as is
          $alias = $this->_compactIri($active_ctx, $expanded_property);
          self::addValue($rval, $alias, $expanded_value);
          continue;
        }

        // Note: expanded value must be an array due to expansion algorithm.

        // preserve empty arrays
        if(count($expanded_value) === 0) {
          $item_active_property = $this->_compactIri(
            $active_ctx, $expanded_property, $expanded_value,
            array('vocab' => true), $inside_reverse);
          self::addValue(
            $rval, $item_active_property, array(),
            array('propertyIsArray' => true));
        }

        // recusively process array values
        foreach($expanded_value as $expanded_item) {
          // compact property and get container type
          $item_active_property = $this->_compactIri(
            $active_ctx, $expanded_property, $expanded_item,
            array('vocab' => true), $inside_reverse);
          $container = self::getContextValue(
            $active_ctx, $item_active_property, '@container');

          // get @list value if appropriate
          $is_list = self::_isList($expanded_item);
          $list = null;
          if($is_list) {
            $list = $expanded_item->{'@list'};
          }

          // recursively compact expanded item
          $compacted_item = $this->_compact(
            $active_ctx, $item_active_property,
            $is_list ? $list : $expanded_item, $options);

          // handle @list
          if($is_list) {
            // ensure @list value is an array
            $compacted_item = self::arrayify($compacted_item);

            if($container !== '@list') {
              // wrap using @list alias
              $compacted_item = (object)array(
                $this->_compactIri($active_ctx, '@list') => $compacted_item);

              // include @index from expanded @list, if any
              if(property_exists($expanded_item, '@index')) {
                $compacted_item->{$this->_compactIri($active_ctx, '@index')} =
                  $expanded_item->{'@index'};
              }
            } else if(property_exists($rval, $item_active_property)) {
              // can't use @list container for more than 1 list
              throw new JsonLdException(
                'JSON-LD compact error; property has a "@list" @container ' .
                'rule but there is more than a single @list that matches ' .
                'the compacted term in the document. Compaction might mix ' .
                'unwanted items into the list.', 'jsonld.SyntaxError',
                'compaction to list of lists');
            }
          }

          // handle language and index maps
          if($container === '@language' || $container === '@index') {
            // get or create the map object
            if(property_exists($rval, $item_active_property)) {
              $map_object = $rval->{$item_active_property};
            } else {
              $rval->{$item_active_property} = $map_object = new stdClass();
            }

            // if container is a language map, simplify compacted value to
            // a simple string
            if($container === '@language' && self::_isValue($compacted_item)) {
              $compacted_item = $compacted_item->{'@value'};
            }

            // add compact value to map object using key from expanded value
            // based on the container type
            self::addValue(
              $map_object, $expanded_item->{$container}, $compacted_item);
          } else {
            // use an array if: compactArrays flag is false,
            // @container is @set or @list, value is an empty
            // array, or key is @graph
            $is_array = (!$options['compactArrays'] ||
              $container === '@set' || $container === '@list' ||
              (is_array($compacted_item) && count($compacted_item) === 0) ||
              $expanded_property === '@list' ||
              $expanded_property === '@graph');

            // add compact value
            self::addValue(
              $rval, $item_active_property, $compacted_item,
              array('propertyIsArray' => $is_array));
          }
        }
      }

      return $rval;
    }

    // only primitives remain which are already compact
    return $element;
  }

  /**
   * Recursively expands an element using the given context. Any context in
   * the element will be removed. All context URLs must have been retrieved
   * before calling this method.
   *
   * @param stdClass $active_ctx the active context to use.
   * @param mixed $active_property the property for the element, null for none.
   * @param mixed $element the element to expand.
   * @param assoc $options the expansion options.
   * @param bool $inside_list true if the property is a list, false if not.
   *
   * @return mixed the expanded value.
   */
  protected function _expand(
    $active_ctx, $active_property, $element, $options, $inside_list) {
    // nothing to expand
    if($element === null) {
      return $element;
    }

    // recursively expand array
    if(is_array($element)) {
      $rval = array();
      $container = self::getContextValue(
        $active_ctx, $active_property, '@container');
      $inside_list = $inside_list || $container === '@list';
      foreach($element as $e) {
        // expand element
        $e = $this->_expand(
          $active_ctx, $active_property, $e, $options, $inside_list);
        if($inside_list && (is_array($e) || self::_isList($e))) {
          // lists of lists are illegal
          throw new JsonLdException(
            'Invalid JSON-LD syntax; lists of lists are not permitted.',
            'jsonld.SyntaxError', 'list of lists');
        }
        // drop null values
        if($e !== null) {
          if(is_array($e)) {
            $rval = array_merge($rval, $e);
          } else {
            $rval[] = $e;
          }
        }
      }
      return $rval;
    }

    if(!is_object($element)) {
      // drop free-floating scalars that are not in lists
      if(!$inside_list &&
        ($active_property === null ||
        $this->_expandIri($active_ctx, $active_property,
          array('vocab' => true)) === '@graph')) {
        return null;
      }

      // expand element according to value expansion rules
      return $this->_expandValue($active_ctx, $active_property, $element);
    }

    // recursively expand object:

    // if element has a context, process it
    if(property_exists($element, '@context')) {
      $active_ctx = $this->_processContext(
        $active_ctx, $element->{'@context'}, $options);
    }

    // expand the active property
    $expanded_active_property = $this->_expandIri(
      $active_ctx, $active_property, array('vocab' => true));

    $rval = new stdClass();
    $keys = array_keys((array)$element);
    sort($keys);
    foreach($keys as $key) {
      $value = $element->{$key};

      if($key === '@context') {
        continue;
      }

      // expand key to IRI
      $expanded_property = $this->_expandIri(
        $active_ctx, $key, array('vocab' => true));

      // drop non-absolute IRI keys that aren't keywords
      if($expanded_property === null ||
        !(self::_isAbsoluteIri($expanded_property) ||
        self::_isKeyword($expanded_property))) {
        continue;
      }

      if(self::_isKeyword($expanded_property)) {
        if($expanded_active_property === '@reverse') {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; a keyword cannot be used as a @reverse ' .
            'property.', 'jsonld.SyntaxError', 'invalid reverse property map',
            array('value' => $value));
        }
        if(property_exists($rval, $expanded_property)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; colliding keywords detected.',
            'jsonld.SyntaxError', 'colliding keywords',
            array('keyword' => $expanded_property));
        }
      }

      // syntax error if @id is not a string
      if($expanded_property === '@id' && !is_string($value)) {
        if(!isset($options['isFrame']) || !$options['isFrame']) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@id" value must a string.',
            'jsonld.SyntaxError', 'invalid @id value',
            array('value' => $value));
        }
        if(!is_object($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@id" value must a string or an object.',
            'jsonld.SyntaxError', 'invalid @id value',
            array('value' => $value));
        }
      }

      // validate @type value
      if($expanded_property === '@type') {
        $this->_validateTypeValue($value);
      }

      // @graph must be an array or an object
      if($expanded_property === '@graph' &&
        !(is_object($value) || is_array($value))) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; "@graph" value must not be an ' .
          'object or an array.', 'jsonld.SyntaxError',
          'invalid @graph value', array('value' => $value));
      }

      // @value must not be an object or an array
      if($expanded_property === '@value' &&
        (is_object($value) || is_array($value))) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; "@value" value must not be an ' .
          'object or an array.', 'jsonld.SyntaxError',
          'invalid value object value', array('value' => $value));
      }

      // @language must be a string
      if($expanded_property === '@language') {
        if($value === null) {
          // drop null @language values, they expand as if they didn't exist
          continue;
        }
        if(!is_string($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@language" value must not be a string.',
            'jsonld.SyntaxError', 'invalid language-tagged string',
            array('value' => $value));
        }
        // ensure language value is lowercase
        $value = strtolower($value);
      }

      // @index must be a string
      if($expanded_property === '@index') {
        if(!is_string($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@index" value must be a string.',
            'jsonld.SyntaxError', 'invalid @index value',
            array('value' => $value));
        }
      }

      // @reverse must be an object
      if($expanded_property === '@reverse') {
        if(!is_object($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@reverse" value must be an object.',
            'jsonld.SyntaxError', 'invalid @reverse value',
            array('value' => $value));
        }

        $expanded_value = $this->_expand(
          $active_ctx, '@reverse', $value, $options, $inside_list);

        // properties double-reversed
        if(property_exists($expanded_value, '@reverse')) {
          foreach($expanded_value->{'@reverse'} as $rproperty => $rvalue) {
            self::addValue(
              $rval, $rproperty, $rvalue, array('propertyIsArray' => true));
          }
        }

        // FIXME: can this be merged with code below to simplify?
        // merge in all reversed properties
        if(property_exists($rval, '@reverse')) {
          $reverse_map = $rval->{'@reverse'};
        } else {
          $reverse_map = null;
        }
        foreach($expanded_value as $property => $items) {
          if($property === '@reverse') {
            continue;
          }
          if($reverse_map === null) {
            $reverse_map = $rval->{'@reverse'} = new stdClass();
          }
          self::addValue(
            $reverse_map, $property, array(),
            array('propertyIsArray' => true));
          foreach($items as $item) {
            if(self::_isValue($item) || self::_isList($item)) {
              throw new JsonLdException(
                'Invalid JSON-LD syntax; "@reverse" value must not be a ' +
                '@value or an @list.', 'jsonld.SyntaxError',
                'invalid reverse property value',
                array('value' => $expanded_value));
            }
            self::addValue(
              $reverse_map, $property, $item,
              array('propertyIsArray' => true));
          }
        }

        continue;
      }

      $container = self::getContextValue($active_ctx, $key, '@container');

      if($container === '@language' && is_object($value)) {
        // handle language map container (skip if value is not an object)
        $expanded_value = $this->_expandLanguageMap($value);
      } else if($container === '@index' && is_object($value)) {
        // handle index container (skip if value is not an object)
        $expanded_value = array();
        $value_keys = array_keys((array)$value);
        sort($value_keys);
        foreach($value_keys as $value_key) {
          $val = $value->{$value_key};
          $val = self::arrayify($val);
          $val = $this->_expand($active_ctx, $key, $val, $options, false);
          foreach($val as $item) {
            if(!property_exists($item, '@index')) {
              $item->{'@index'} = $value_key;
            }
            $expanded_value[] = $item;
          }
        }
      } else {
        // recurse into @list or @set
        $is_list = ($expanded_property === '@list');
        if($is_list || $expanded_property === '@set') {
          $next_active_property = $active_property;
          if($is_list && $expanded_active_property === '@graph') {
            $next_active_property = null;
          }
          $expanded_value = $this->_expand(
            $active_ctx, $next_active_property, $value, $options, $is_list);
          if($is_list && self::_isList($expanded_value)) {
            throw new JsonLdException(
              'Invalid JSON-LD syntax; lists of lists are not permitted.',
              'jsonld.SyntaxError', 'list of lists');
          }
        } else {
          // recursively expand value with key as new active property
          $expanded_value = $this->_expand(
            $active_ctx, $key, $value, $options, false);
        }
      }

      // drop null values if property is not @value
      if($expanded_value === null && $expanded_property !== '@value') {
        continue;
      }

      // convert expanded value to @list if container specifies it
      if($expanded_property !== '@list' && !self::_isList($expanded_value) &&
        $container === '@list') {
        // ensure expanded value is an array
        $expanded_value = (object)array(
          '@list' => self::arrayify($expanded_value));
      }

      // FIXME: can this be merged with code above to simplify?
      // merge in reverse properties
      if(property_exists($active_ctx->mappings, $key) &&
        $active_ctx->mappings->{$key} &&
        $active_ctx->mappings->{$key}->reverse) {
        if(property_exists($rval, '@reverse')) {
          $reverse_map = $rval->{'@reverse'};
        } else {
          $reverse_map = $rval->{'@reverse'} = new stdClass();
        }
        $expanded_value = self::arrayify($expanded_value);
        foreach($expanded_value as $item) {
          if(self::_isValue($item) || self::_isList($item)) {
            throw new JsonLdException(
              'Invalid JSON-LD syntax; "@reverse" value must not be a ' +
              '@value or an @list.', 'jsonld.SyntaxError',
              'invalid reverse property value',
              array('value' => $expanded_value));
          }
          self::addValue(
            $reverse_map, $expanded_property, $item,
            array('propertyIsArray' => true));
        }
        continue;
      }

      // add value for property
      // use an array except for certain keywords
      $use_array = (!in_array(
        $expanded_property, array(
          '@index', '@id', '@type', '@value', '@language')));
      self::addValue(
        $rval, $expanded_property, $expanded_value,
        array('propertyIsArray' => $use_array));
    }

    // get property count on expanded output
    $keys = array_keys((array)$rval);
    $count = count($keys);

    // @value must only have @language or @type
    if(property_exists($rval, '@value')) {
      // @value must only have @language or @type
      if(property_exists($rval, '@type') &&
        property_exists($rval, '@language')) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; an element containing "@value" may not ' .
          'contain both "@type" and "@language".',
          'jsonld.SyntaxError', 'invalid value object',
          array('element' => $rval));
      }
      $valid_count = $count - 1;
      if(property_exists($rval, '@type')) {
        $valid_count -= 1;
      }
      if(property_exists($rval, '@index')) {
        $valid_count -= 1;
      }
      if(property_exists($rval, '@language')) {
        $valid_count -= 1;
      }
      if($valid_count !== 0) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; an element containing "@value" may only ' .
          'have an "@index" property and at most one other property ' .
          'which can be "@type" or "@language".',
          'jsonld.SyntaxError', 'invalid value object',
          array('element' => $rval));
      }
      // drop null @values
      if($rval->{'@value'} === null) {
        $rval = null;
      } else if(property_exists($rval, '@language') &&
        !is_string($rval->{'@value'})) {
        // if @language is present, @value must be a string
        throw new JsonLdException(
          'Invalid JSON-LD syntax; only strings may be language-tagged.',
          'jsonld.SyntaxError', 'invalid language-tagged value',
          array('element' => $rval));
      } else if(property_exists($rval, '@type') &&
        (!self::_isAbsoluteIri($rval->{'@type'}) ||
        strpos($rval->{'@type'}, '_:') === 0)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; an element containing "@value" ' .
          'and "@type" must have an absolute IRI for the value ' .
          'of "@type".', 'jsonld.SyntaxError', 'invalid typed value',
          array('element' => $rval));
      }
    } else if(property_exists($rval, '@type') && !is_array($rval->{'@type'})) {
      // convert @type to an array
      $rval->{'@type'} = array($rval->{'@type'});
    } else if(property_exists($rval, '@set') ||
      property_exists($rval, '@list')) {
      // handle @set and @list
      if($count > 1 && !($count === 2 && property_exists($rval, '@index'))) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; if an element has the property "@set" ' .
          'or "@list", then it can have at most one other property that is ' .
          '"@index".', 'jsonld.SyntaxError', 'invalid set or list object',
          array('element' => $rval));
      }
      // optimize away @set
      if(property_exists($rval, '@set')) {
        $rval = $rval->{'@set'};
        $keys = array_keys((array)$rval);
        $count = count($keys);
      }
    } else if($count === 1 && property_exists($rval, '@language')) {
      // drop objects with only @language
      $rval = null;
    }

    // drop certain top-level objects that do not occur in lists
    if(is_object($rval) &&
      !$options['keepFreeFloatingNodes'] && !$inside_list &&
      ($active_property === null || $expanded_active_property === '@graph')) {
      // drop empty object or top-level @value/@list, or object with only @id
      if($count === 0 || property_exists($rval, '@value') ||
        property_exists($rval, '@list') ||
        ($count === 1 && property_exists($rval, '@id'))) {
        $rval = null;
      }
    }

    return $rval;
  }

  /**
   * Performs JSON-LD flattening.
   *
   * @param array $input the expanded JSON-LD to flatten.
   *
   * @return array the flattened output.
   */
  protected function _flatten($input) {
    // produce a map of all subjects and name each bnode
    $namer = new UniqueNamer('_:b');
    $graphs = (object)array('@default' => new stdClass());
    $this->_createNodeMap($input, $graphs, '@default', $namer);

    // add all non-default graphs to default graph
    $default_graph = $graphs->{'@default'};
    $graph_names = array_keys((array)$graphs);
    foreach($graph_names as $graph_name) {
      if($graph_name === '@default') {
        continue;
      }
      $node_map = $graphs->{$graph_name};
      if(!property_exists($default_graph, $graph_name)) {
        $default_graph->{$graph_name} = (object)array(
          '@id' => $graph_name, '@graph' => array());
      }
      $subject = $default_graph->{$graph_name};
      if(!property_exists($subject, '@graph')) {
        $subject->{'@graph'} = array();
      }
      $ids = array_keys((array)$node_map);
      sort($ids);
      foreach($ids as $id) {
        $node = $node_map->{$id};
        // only add full subjects
        if(!self::_isSubjectReference($node)) {
          $subject->{'@graph'}[] = $node;
        }
      }
    }

    // produce flattened output
    $flattened = array();
    $keys = array_keys((array)$default_graph);
    sort($keys);
    foreach($keys as $key) {
      $node = $default_graph->{$key};
      // only add full subjects to top-level
      if(!self::_isSubjectReference($node)) {
        $flattened[] = $node;
      }
    }
    return $flattened;
  }

  /**
   * Performs JSON-LD framing.
   *
   * @param array $input the expanded JSON-LD to frame.
   * @param array $frame the expanded JSON-LD frame to use.
   * @param assoc $options the framing options.
   *
   * @return array the framed output.
   */
  protected function _frame($input, $frame, $options) {
    // create framing state
    $state = (object)array(
      'options' => $options,
      'graphs' => (object)array(
        '@default' => new stdClass(),
        '@merged' => new stdClass()),
      'subjectStack' => array(),
      'link' => new stdClass());

    // produce a map of all graphs and name each bnode
    // FIXME: currently uses subjects from @merged graph only
    $namer = new UniqueNamer('_:b');
    $this->_createNodeMap($input, $state->graphs, '@merged', $namer);
    $state->subjects = $state->graphs->{'@merged'};

    // frame the subjects
    $framed = new ArrayObject();
    $keys = array_keys((array)$state->subjects);
    sort($keys);
    $this->_matchFrame($state, $keys, $frame, $framed, null);
    return (array)$framed;
  }

  /**
   * Performs normalization on the given RDF dataset.
   *
   * @param stdClass $dataset the RDF dataset to normalize.
   * @param assoc $options the normalization options.
   *
   * @return mixed the normalized output.
   */
  protected function _normalize($dataset, $options) {
    // create quads and map bnodes to their associated quads
    $quads = array();
    $bnodes = new stdClass();
    foreach($dataset as $graph_name => $triples) {
      if($graph_name === '@default') {
        $graph_name = null;
      }
      foreach($triples as $triple) {
        $quad = $triple;
        if($graph_name !== null) {
          if(strpos($graph_name, '_:') === 0) {
            $quad->name = (object)array(
              'type' => 'blank node', 'value' => $graph_name);
          } else {
            $quad->name = (object)array(
              'type' => 'IRI', 'value' => $graph_name);
          }
        }
        $quads[] = $quad;

        foreach(array('subject', 'object', 'name') as $attr) {
          if(property_exists($quad, $attr) &&
            $quad->{$attr}->type === 'blank node') {
            $id = $quad->{$attr}->value;
            if(property_exists($bnodes, $id)) {
              $bnodes->{$id}->quads[] = $quad;
            } else {
              $bnodes->{$id} = (object)array('quads' => array($quad));
            }
          }
        }
      }
    }

    // mapping complete, start canonical naming
    $namer = new UniqueNamer('_:c14n');

    // continue to hash bnode quads while bnodes are assigned names
    $unnamed = null;
    $nextUnnamed = array_keys((array)$bnodes);
    $duplicates = null;
    do {
      $unnamed = $nextUnnamed;
      $nextUnnamed = array();
      $duplicates = new stdClass();
      $unique = new stdClass();
      foreach($unnamed as $bnode) {
        // hash quads for each unnamed bnode
        $hash = $this->_hashQuads($bnode, $bnodes, $namer);

        // store hash as unique or a duplicate
        if(property_exists($duplicates, $hash)) {
          $duplicates->{$hash}[] = $bnode;
          $nextUnnamed[] = $bnode;
        } else if(property_exists($unique, $hash)) {
          $duplicates->{$hash} = array($unique->{$hash}, $bnode);
          $nextUnnamed[] = $unique->{$hash};
          $nextUnnamed[] = $bnode;
          unset($unique->{$hash});
        } else {
          $unique->{$hash} = $bnode;
        }
      }

      // name unique bnodes in sorted hash order
      $hashes = array_keys((array)$unique);
      sort($hashes);
      foreach($hashes as $hash) {
        $namer->getName($unique->{$hash});
      }
    }
    while(count($unnamed) > count($nextUnnamed));

    // enumerate duplicate hash groups in sorted order
    $hashes = array_keys((array)$duplicates);
    sort($hashes);
    foreach($hashes as $hash) {
      // process group
      $group = $duplicates->{$hash};
      $results = array();
      foreach($group as $bnode) {
        // skip already-named bnodes
        if($namer->isNamed($bnode)) {
          continue;
        }

        // hash bnode paths
        $path_namer = new UniqueNamer('_:b');
        $path_namer->getName($bnode);
        $results[] = $this->_hashPaths($bnode, $bnodes, $namer, $path_namer);
      }

      // name bnodes in hash order
      usort($results, function($a, $b) {
        $a = $a->hash;
        $b = $b->hash;
        return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
      });
      foreach($results as $result) {
        // name all bnodes in path namer in key-entry order
        foreach($result->pathNamer->order as $bnode) {
          $namer->getName($bnode);
        }
      }
    }

    // create normalized array
    $normalized = array();

    /* Note: At this point all bnodes in the set of RDF quads have been
     assigned canonical names, which have been stored in the 'namer' object.
     Here each quad is updated by assigning each of its bnodes its new name
     via the 'namer' object. */

    // update bnode names in each quad and serialize
    foreach($quads as $quad) {
      foreach(array('subject', 'object', 'name') as $attr) {
        if(property_exists($quad, $attr) &&
          $quad->{$attr}->type === 'blank node' &&
          strpos($quad->{$attr}->value, '_:c14n') !== 0) {
          $quad->{$attr}->value = $namer->getName($quad->{$attr}->value);
        }
      }
      $normalized[] = $this->toNQuad($quad, property_exists($quad, 'name') ?
        $quad->name->value : null);
    }

    // sort normalized output
    sort($normalized);

    // handle output format
    if(isset($options['format']) && $options['format']) {
      if($options['format'] === 'application/nquads') {
        return implode($normalized);
      }
      throw new JsonLdException(
        'Unknown output format.',
        'jsonld.UnknownFormat', null, array('format' => $options['format']));
    }

    // return RDF dataset
    return $this->parseNQuads(implode($normalized));
  }

  /**
   * Converts an RDF dataset to JSON-LD.
   *
   * @param stdClass $dataset the RDF dataset.
   * @param assoc $options the RDF serialization options.
   *
   * @return array the JSON-LD output.
   */
  protected function _fromRDF($dataset, $options) {
    $default_graph = new stdClass();
    $graph_map = (object)array('@default' => $default_graph);
    $referenced_once = (object)array();

    foreach($dataset as $name => $graph) {
      if(!property_exists($graph_map, $name)) {
        $graph_map->{$name} = new stdClass();
      }
      if($name !== '@default' && !property_exists($default_graph, $name)) {
        $default_graph->{$name} = (object)array('@id' => $name);
      }
      $node_map = $graph_map->{$name};
      foreach($graph as $triple) {
        // get subject, predicate, object
        $s = $triple->subject->value;
        $p = $triple->predicate->value;
        $o = $triple->object;

        if(!property_exists($node_map, $s)) {
          $node_map->{$s} = (object)array('@id' => $s);
        }
        $node = $node_map->{$s};

        $object_is_id = ($o->type === 'IRI' || $o->type === 'blank node');
        if($object_is_id && !property_exists($node_map, $o->value)) {
          $node_map->{$o->value} = (object)array('@id' => $o->value);
        }

        if($p === self::RDF_TYPE && !$options['useRdfType'] && $object_is_id) {
          self::addValue(
            $node, '@type', $o->value, array('propertyIsArray' => true));
          continue;
        }

        $value = self::_RDFToObject($o, $options['useNativeTypes']);
        self::addValue($node, $p, $value, array('propertyIsArray' => true));

        // object may be an RDF list/partial list node but we can't know
        // easily until all triples are read
        if($object_is_id) {
          if($o->value === self::RDF_NIL) {
            $object = $node_map->{$o->value};
            if(!property_exists($object, 'usages')) {
              $object->usages = array();
            }
            $object->usages[] = (object)array(
              'node' => $node,
              'property' => $p,
              'value' => $value);
          } else if(property_exists($referenced_once, $o->value)) {
            // object referenced more than once
            $referenced_once->{$o->value} = false;
          } else {
            // track single reference
            $referenced_once->{$o->value} = (object)array(
              'node' => $node,
              'property' => $p,
              'value' => $value);
          }
        }
      }
    }

    // convert linked lists to @list arrays
    foreach($graph_map as $name => $graph_object) {
      // no @lists to be converted, continue
      if(!property_exists($graph_object, self::RDF_NIL)) {
        continue;
      }

      // iterate backwards through each RDF list
      $nil = $graph_object->{self::RDF_NIL};
      foreach($nil->usages as $usage) {
        $node = $usage->node;
        $property = $usage->property;
        $head = $usage->value;
        $list = array();
        $list_nodes = array();

        // ensure node is a well-formed list node; it must:
        // 1. Be referenced only once.
        // 2. Have an array for rdf:first that has 1 item.
        // 3. Have an array for rdf:rest that has 1 item.
        // 4. Have no keys other than: @id, rdf:first, rdf:rest, and,
        //   optionally, @type where the value is rdf:List.
        $node_key_count = count(array_keys((array)$node));
        while($property === self::RDF_REST &&
          property_exists($referenced_once, $node->{'@id'}) &&
          is_object($referenced_once->{$node->{'@id'}}) &&
          property_exists($node, self::RDF_FIRST) &&
          property_exists($node, self::RDF_REST) &&
          is_array($node->{self::RDF_FIRST}) &&
          is_array($node->{self::RDF_REST}) &&
          count($node->{self::RDF_FIRST}) === 1 &&
          count($node->{self::RDF_REST}) === 1 &&
          ($node_key_count === 3 || ($node_key_count === 4 &&
            property_exists($node, '@type') && is_array($node->{'@type'}) &&
            count($node->{'@type'}) === 1 &&
            $node->{'@type'}[0] === self::RDF_LIST))) {
          $list[] = $node->{self::RDF_FIRST}[0];
          $list_nodes[] = $node->{'@id'};

          // get next node, moving backwards through list
          $usage = $referenced_once->{$node->{'@id'}};
          $node = $usage->node;
          $property = $usage->property;
          $head = $usage->value;
          $node_key_count = count(array_keys((array)$node));

          // if node is not a blank node, then list head found
          if(strpos($node->{'@id'}, '_:') !== 0) {
            break;
          }
        }

        // list is nested in another list
        if($property === self::RDF_FIRST) {
          // empty list
          if($node->{'@id'} === self::RDF_NIL) {
            // can't convert rdf:nil to a @list object because it would
            // result in a list of lists which isn't supported
            continue;
          }

          // preserve list head
          $head = $graph_object->{$head->{'@id'}}->{self::RDF_REST}[0];
          array_pop($list);
          array_pop($list_nodes);
        }

        // transform list into @list object
        unset($head->{'@id'});
        $head->{'@list'} = array_reverse($list);
        foreach($list_nodes as $list_node) {
          unset($graph_object->{$list_node});
        }
      }

      unset($nil->usages);
    }

    $result = array();
    $subjects = array_keys((array)$default_graph);
    sort($subjects);
    foreach($subjects as $subject) {
      $node = $default_graph->{$subject};
      if(property_exists($graph_map, $subject)) {
        $node->{'@graph'} = array();
        $graph_object = $graph_map->{$subject};
        $subjects_ = array_keys((array)$graph_object);
        sort($subjects_);
        foreach($subjects_ as $subject_) {
          $node_ = $graph_object->{$subject_};
          // only add full subjects to top-level
          if(!self::_isSubjectReference($node_)) {
            $node->{'@graph'}[] = $node_;
          }
        }
      }
      // only add full subjects to top-level
      if(!self::_isSubjectReference($node)) {
        $result[] = $node;
      }
    }

    return $result;
  }

  /**
   * Processes a local context and returns a new active context.
   *
   * @param stdClass $active_ctx the current active context.
   * @param mixed $local_ctx the local context to process.
   * @param assoc $options the context processing options.
   *
   * @return stdClass the new active context.
   */
  protected function _processContext($active_ctx, $local_ctx, $options) {
    global $jsonld_cache;

    // normalize local context to an array
    if(is_object($local_ctx) && property_exists($local_ctx, '@context') &&
      is_array($local_ctx->{'@context'})) {
      $local_ctx = $local_ctx->{'@context'};
    }
    $ctxs = self::arrayify($local_ctx);

    // no contexts in array, clone existing context
    if(count($ctxs) === 0) {
      return self::_cloneActiveContext($active_ctx);
    }

    // process each context in order, update active context
    // on each iteration to ensure proper caching
    $rval = $active_ctx;
    foreach($ctxs as $ctx) {
      // reset to initial context
      if($ctx === null) {
        $rval = $active_ctx = $this->_getInitialContext($options);
        continue;
      }

      // dereference @context key if present
      if(is_object($ctx) && property_exists($ctx, '@context')) {
        $ctx = $ctx->{'@context'};
      }

      // context must be an object by now, all URLs retrieved before this call
      if(!is_object($ctx)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context must be an object.',
          'jsonld.SyntaxError', 'invalid local context',
          array('context' => $ctx));
      }

      // get context from cache if available
      if(property_exists($jsonld_cache, 'activeCtx')) {
        $cached = $jsonld_cache->activeCtx->get($active_ctx, $ctx);
        if($cached) {
          $rval = $active_ctx = $cached;
          $must_clone = true;
          continue;
        }
      }

      // update active context and clone new one before updating
      $active_ctx = $rval;
      $rval = self::_cloneActiveContext($rval);

      // define context mappings for keys in local context
      $defined = new stdClass();

      // handle @base
      if(property_exists($ctx, '@base')) {
        $base = $ctx->{'@base'};
        if($base === null) {
          $base = null;
        } else if(!is_string($base)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; the value of "@base" in a ' .
            '@context must be a string or null.',
            'jsonld.SyntaxError', 'invalid base IRI', array('context' => $ctx));
        } else if($base !== '' && !self::_isAbsoluteIri($base)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; the value of "@base" in a ' .
            '@context must be an absolute IRI or the empty string.',
            'jsonld.SyntaxError', 'invalid base IRI', array('context' => $ctx));
        }
        if($base !== null) {
          $base = jsonld_parse_url($base);
        }
        $rval->{'@base'} = $base;
        $defined->{'@base'} = true;
      }

      // handle @vocab
      if(property_exists($ctx, '@vocab')) {
        $value = $ctx->{'@vocab'};
        if($value === null) {
          unset($rval->{'@vocab'});
        } else if(!is_string($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; the value of "@vocab" in a ' .
            '@context must be a string or null.',
            'jsonld.SyntaxError', 'invalid vocab mapping',
            array('context' => $ctx));
        } else if(!self::_isAbsoluteIri($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; the value of "@vocab" in a ' .
            '@context must be an absolute IRI.',
            'jsonld.SyntaxError', 'invalid vocab mapping',
            array('context' => $ctx));
        } else {
          $rval->{'@vocab'} = $value;
        }
        $defined->{'@vocab'} = true;
      }

      // handle @language
      if(property_exists($ctx, '@language')) {
        $value = $ctx->{'@language'};
        if($value === null) {
          unset($rval->{'@language'});
        } else if(!is_string($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; the value of "@language" in a ' .
            '@context must be a string or null.',
            'jsonld.SyntaxError', 'invalid default language',
            array('context' => $ctx));
        } else {
          $rval->{'@language'} = strtolower($value);
        }
        $defined->{'@language'} = true;
      }

      // process all other keys
      foreach($ctx as $k => $v) {
        $this->_createTermDefinition($rval, $ctx, $k, $defined);
      }

      // cache result
      if(property_exists($jsonld_cache, 'activeCtx')) {
        $jsonld_cache->activeCtx->set($active_ctx, $ctx, $rval);
      }
    }

    return $rval;
  }

  /**
   * Expands a language map.
   *
   * @param stdClass $language_map the language map to expand.
   *
   * @return array the expanded language map.
   */
  protected function _expandLanguageMap($language_map) {
    $rval = array();
    $keys = array_keys((array)$language_map);
    sort($keys);
    foreach($keys as $key) {
      $values = $language_map->{$key};
      $values = self::arrayify($values);
      foreach($values as $item) {
        if($item === null) {
          continue;
        }
        if(!is_string($item)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; language map values must be strings.',
            'jsonld.SyntaxError', 'invalid language map value',
            array('languageMap', $language_map));
        }
        $rval[] = (object)array(
          '@value' => $item,
          '@language' => strtolower($key));
      }
    }
    return $rval;
  }

  /**
   * Labels the blank nodes in the given value using the given UniqueNamer.
   *
   * @param UniqueNamer $namer the UniqueNamer to use.
   * @param mixed $element the element with blank nodes to rename.
   *
   * @return mixed the element.
   */
  public function _labelBlankNodes($namer, $element) {
    if(is_array($element)) {
      $length = count($element);
      for($i = 0; $i < $length; ++$i) {
        $element[$i] = $this->_labelBlankNodes($namer, $element[$i]);
      }
    } else if(self::_isList($element)) {
      $element->{'@list'} = $this->_labelBlankNodes(
        $namer, $element->{'@list'});
    } else if(is_object($element)) {
      // rename blank node
      if(self::_isBlankNode($element)) {
        $name = null;
        if(property_exists($element, '@id')) {
          $name = $element->{'@id'};
        }
        $element->{'@id'} = $namer->getName($name);
      }

      // recursively apply to all keys
      $keys = array_keys((array)$element);
      sort($keys);
      foreach($keys as $key) {
        if($key !== '@id') {
          $element->{$key} = $this->_labelBlankNodes($namer, $element->{$key});
        }
      }
    }

    return $element;
  }

  /**
   * Expands the given value by using the coercion and keyword rules in the
   * given context.
   *
   * @param stdClass $active_ctx the active context to use.
   * @param string $active_property the property the value is associated with.
   * @param mixed $value the value to expand.
   *
   * @return mixed the expanded value.
   */
  protected function _expandValue($active_ctx, $active_property, $value) {
    // nothing to expand
    if($value === null) {
      return null;
    }

    // special-case expand @id and @type (skips '@id' expansion)
    $expanded_property = $this->_expandIri(
      $active_ctx, $active_property, array('vocab' => true));
    if($expanded_property === '@id') {
      return $this->_expandIri($active_ctx, $value, array('base' => true));
    } else if($expanded_property === '@type') {
      return $this->_expandIri(
        $active_ctx, $value, array('vocab' => true, 'base' => true));
    }

    // get type definition from context
    $type = self::getContextValue($active_ctx, $active_property, '@type');

    // do @id expansion (automatic for @graph)
    if($type === '@id' || ($expanded_property === '@graph' &&
      is_string($value))) {
      return (object)array('@id' => $this->_expandIri(
        $active_ctx, $value, array('base' => true)));
    }
    // do @id expansion w/vocab
    if($type === '@vocab') {
      return (object)array('@id' => $this->_expandIri(
        $active_ctx, $value, array('vocab' => true, 'base' => true)));
    }

    // do not expand keyword values
    if(self::_isKeyword($expanded_property)) {
      return $value;
    }

    $rval = new stdClass();

    // other type
    if($type !== null) {
      $rval->{'@type'} = $type;
    } else if(is_string($value)) {
      // check for language tagging for strings
      $language = self::getContextValue(
        $active_ctx, $active_property, '@language');
      if($language !== null) {
        $rval->{'@language'} = $language;
      }
    }
    $rval->{'@value'} = $value;

    return $rval;
  }

  /**
   * Creates an array of RDF triples for the given graph.
   *
   * @param stdClass $graph the graph to create RDF triples for.
   * @param UniqueNamer $namer for assigning bnode names.
   * @param assoc $options the RDF serialization options.
   *
   * @return array the array of RDF triples for the given graph.
   */
  protected function _graphToRDF($graph, $namer, $options) {
    $rval = array();

    $ids = array_keys((array)$graph);
    sort($ids);
    foreach($ids as $id) {
      $node = $graph->{$id};
      if($id === '"') {
        $id = '';
      }
      $properties = array_keys((array)$node);
      sort($properties);
      foreach($properties as $property) {
        $items = $node->{$property};
        if($property === '@type') {
          $property = self::RDF_TYPE;
        } else if(self::_isKeyword($property)) {
          continue;
        }

        foreach($items as $item) {
          // skip relative IRI subjects and predicates
          if(!(self::_isAbsoluteIri($id) && self::_isAbsoluteIri($property))) {
            continue;
          }

          // RDF subject
          $subject = new stdClass();
          $subject->type = (strpos($id, '_:') === 0) ? 'blank node' : 'IRI';
          $subject->value = $id;

          // RDF predicate
          $predicate = new stdClass();
          $predicate->type = (strpos($property, '_:') === 0 ?
            'blank node' : 'IRI');
          $predicate->value = $property;

          // skip bnode predicates unless producing generalized RDF
          if($predicate->type === 'blank node' &&
            !$options['produceGeneralizedRdf']) {
            continue;
          }

          if(self::_isList($item)) {
            // convert @list to triples
            $this->_listToRDF(
              $item->{'@list'}, $namer, $subject, $predicate, $rval);
          } else {
            // convert value or node object to triple
            $object = $this->_objectToRDF($item);
            // skip null objects (they are relative IRIs)
            if($object) {
              $rval[] = (object)array(
                'subject' => $subject,
                'predicate' => $predicate,
                'object' => $object);
            }
          }
        }
      }
    }

    return $rval;
  }

  /**
   * Converts a @list value into linked list of blank node RDF triples
   * (an RDF collection).
   *
   * @param array $list the @list value.
   * @param UniqueNamer $namer for assigning blank node names.
   * @param stdClass $subject the subject for the head of the list.
   * @param stdClass $predicate the predicate for the head of the list.
   * @param &array $triples the array of triples to append to.
   */
  protected function _listToRDF(
    $list, $namer, $subject, $predicate, &$triples) {
    $first = (object)array('type' => 'IRI', 'value' => self::RDF_FIRST);
    $rest = (object)array('type' => 'IRI', 'value' => self::RDF_REST);
    $nil = (object)array('type' => 'IRI', 'value' => self::RDF_NIL);

    foreach($list as $item) {
      $blank_node = (object)array(
        'type' => 'blank node', 'value' => $namer->getName());
      $triples[] = (object)array(
        'subject' => $subject,
        'predicate' => $predicate,
        'object' => $blank_node);

      $subject = $blank_node;
      $predicate = $first;
      $object = $this->_objectToRDF($item);
      // skip null objects (they are relative IRIs)
      if($object) {
        $triples[] = (object)array(
          'subject' => $subject,
          'predicate' => $predicate,
          'object' => $object);
      }

      $predicate = $rest;
    }

    $triples[] = (object)array(
      'subject' => $subject, 'predicate' => $predicate, 'object' => $nil);
  }

  /**
   * Converts a JSON-LD value object to an RDF literal or a JSON-LD string or
   * node object to an RDF resource.
   *
   * @param mixed $item the JSON-LD value or node object.
   *
   * @return stdClass the RDF literal or RDF resource.
   */
  protected function _objectToRDF($item) {
    $object = new stdClass();

    if(self::_isValue($item)) {
      $object->type = 'literal';
      $value = $item->{'@value'};
      $datatype = property_exists($item, '@type') ? $item->{'@type'} : null;

      // convert to XSD datatypes as appropriate
      if(is_bool($value)) {
        $object->value = ($value ? 'true' : 'false');
        $object->datatype = $datatype ? $datatype : self::XSD_BOOLEAN;
      } else if(is_double($value) || $datatype == self::XSD_DOUBLE) {
        // canonical double representation
        $object->value = preg_replace(
          '/(\d)0*E\+?/', '$1E', sprintf('%1.15E', $value));
        $object->datatype = $datatype ? $datatype : self::XSD_DOUBLE;
      } else if(is_integer($value)) {
        $object->value = strval($value);
        $object->datatype = $datatype ? $datatype : self::XSD_INTEGER;
      } else if(property_exists($item, '@language')) {
        $object->value = $value;
        $object->datatype = $datatype ? $datatype : self::RDF_LANGSTRING;
        $object->language = $item->{'@language'};
      } else {
        $object->value = $value;
        $object->datatype = $datatype ? $datatype : self::XSD_STRING;
      }
    } else {
      // convert string/node object to RDF
      $id = is_object($item) ? $item->{'@id'} : $item;
      $object->type = (strpos($id, '_:') === 0) ? 'blank node' : 'IRI';
      $object->value = $id;
    }

    // skip relative IRIs
    if($object->type === 'IRI' && !self::_isAbsoluteIri($object->value)) {
      return null;
    }

    return $object;
  }

  /**
   * Converts an RDF triple object to a JSON-LD object.
   *
   * @param stdClass $o the RDF triple object to convert.
   * @param bool $use_native_types true to output native types, false not to.
   *
   * @return stdClass the JSON-LD object.
   */
  protected function _RDFToObject($o, $use_native_types) {
    // convert IRI/blank node object to JSON-LD
    if($o->type === 'IRI' || $o->type === 'blank node') {
      return (object)array('@id' => $o->value);
    }

    // convert literal object to JSON-LD
    $rval = (object)array('@value' => $o->value);

    if(property_exists($o, 'language')) {
      // add language
      $rval->{'@language'} = $o->language;
    } else {
      // add datatype
      $type = $o->datatype;
      // use native types for certain xsd types
      if($use_native_types) {
        if($type === self::XSD_BOOLEAN) {
          if($rval->{'@value'} === 'true') {
            $rval->{'@value'} = true;
          } else if($rval->{'@value'} === 'false') {
            $rval->{'@value'} = false;
          }
        } else if(is_numeric($rval->{'@value'})) {
          if($type === self::XSD_INTEGER) {
            $i = intval($rval->{'@value'});
            if(strval($i) === $rval->{'@value'}) {
              $rval->{'@value'} = $i;
            }
          } else if($type === self::XSD_DOUBLE) {
            $rval->{'@value'} = doubleval($rval->{'@value'});
          }
        }
        // do not add native type
        if(!in_array($type, array(
          self::XSD_BOOLEAN, self::XSD_INTEGER, self::XSD_DOUBLE,
          self::XSD_STRING))) {
          $rval->{'@type'} = $type;
        }
      } else if($type !== self::XSD_STRING) {
        $rval->{'@type'} = $type;
      }
    }

    return $rval;
  }

  /**
   * Recursively flattens the subjects in the given JSON-LD expanded input
   * into a node map.
   *
   * @param mixed $input the JSON-LD expanded input.
   * @param stdClass $graphs a map of graph name to subject map.
   * @param string $graph the name of the current graph.
   * @param UniqueNamer $namer the blank node namer.
   * @param mixed $name the name assigned to the current input if it is a bnode.
   * @param mixed $list the list to append to, null for none.
   */
  protected function _createNodeMap(
    $input, $graphs, $graph, $namer, $name=null, $list=null) {
    // recurse through array
    if(is_array($input)) {
      foreach($input as $e) {
        $this->_createNodeMap($e, $graphs, $graph, $namer, null, $list);
      }
      return;
    }

    // add non-object to list
    if(!is_object($input)) {
      if($list !== null) {
        $list[] = $input;
      }
      return;
    }

    // add values to list
    if(self::_isValue($input)) {
      if(property_exists($input, '@type')) {
        $type = $input->{'@type'};
        // rename @type blank node
        if(strpos($type, '_:') === 0) {
          $type = $input->{'@type'} = $namer->getName($type);
        }
      }
      if($list !== null) {
        $list[] = $input;
      }
      return;
    }

    // Note: At this point, input must be a subject.

    // spec requires @type to be named first, so assign names early
    if(property_exists($input, '@type')) {
      foreach($input->{'@type'} as $type) {
        if(strpos($type, '_:') === 0) {
          $namer->getName($type);
        }
      }
    }

    // get name for subject
    if($name === null) {
      if(property_exists($input, '@id')) {
        $name = $input->{'@id'};
      }
      if(self::_isBlankNode($input)) {
        $name = $namer->getName($name);
      }
    }

    // add subject reference to list
    if($list !== null) {
      $list[] = (object)array('@id' => $name);
    }

    // create new subject or merge into existing one
    if(!property_exists($graphs, $graph)) {
      $graphs->{$graph} = new stdClass();
    }
    $subjects = $graphs->{$graph};
    if(!property_exists($subjects, $name)) {
      if($name === '') {
        $subjects->{'"'} = new stdClass();
      } else {
        $subjects->{$name} = new stdClass();
      }
    }
    if($name === '') {
      $subject = $subjects->{'"'};
    } else {
      $subject = $subjects->{$name};
    }
    $subject->{'@id'} = $name;
    $properties = array_keys((array)$input);
    sort($properties);
    foreach($properties as $property) {
      // skip @id
      if($property === '@id') {
        continue;
      }

      // handle reverse properties
      if($property === '@reverse') {
        $referenced_node = (object)array('@id' => $name);
        $reverse_map = $input->{'@reverse'};
        foreach($reverse_map as $reverse_property => $items) {
          foreach($items as $item) {
            $item_name = null;
            if(property_exists($item, '@id')) {
              $item_name = $item->{'@id'};
            }
            if(self::_isBlankNode($item)) {
              $item_name = $namer->getName($item_name);
            }
            $this->_createNodeMap($item, $graphs, $graph, $namer, $item_name);
            if($item_name === '') {
              $item_name = '"';
            }
            self::addValue(
              $subjects->{$item_name}, $reverse_property, $referenced_node,
              array('propertyIsArray' => true, 'allowDuplicate' => false));
          }
        }
        continue;
      }

      // recurse into graph
      if($property === '@graph') {
        // add graph subjects map entry
        if(!property_exists($graphs, $name)) {
          // FIXME: temporary hack to avoid empty property bug
          if(!$name) {
            $name = '"';
          }
          $graphs->{$name} = new stdClass();
        }
        $g = ($graph === '@merged') ? $graph : $name;
        $this->_createNodeMap(
          $input->{$property}, $graphs, $g, $namer, null, null);
        continue;
      }

      // copy non-@type keywords
      if($property !== '@type' && self::_isKeyword($property)) {
        if($property === '@index' && property_exists($subject, '@index') &&
          ($input->{'@index'} !== $subject->{'@index'} ||
          $input->{'@index'}->{'@id'} !== $subject->{'@index'}->{'@id'})) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; conflicting @index property detected.',
            'jsonld.SyntaxError', 'conflicting indexes',
            array('subject' => $subject));
        }
        $subject->{$property} = $input->{$property};
        continue;
      }

      // iterate over objects
      $objects = $input->{$property};

      // if property is a bnode, assign it a new id
      if(strpos($property, '_:') === 0) {
        $property = $namer->getName($property);
      }

      // ensure property is added for empty arrays
      if(count($objects) === 0) {
        self::addValue(
          $subject, $property, array(), array('propertyIsArray' => true));
        continue;
      }
      foreach($objects as $o) {
        if($property === '@type') {
          // rename @type blank nodes
          $o = (strpos($o, '_:') === 0) ? $namer->getName($o) : $o;
        }

        // handle embedded subject or subject reference
        if(self::_isSubject($o) || self::_isSubjectReference($o)) {
          // rename blank node @id
          $id = property_exists($o, '@id') ? $o->{'@id'} : null;
          if(self::_isBlankNode($o)) {
            $id = $namer->getName($id);
          }

          // add reference and recurse
          self::addValue(
            $subject, $property, (object)array('@id' => $id),
            array('propertyIsArray' => true, 'allowDuplicate' => false));
          $this->_createNodeMap($o, $graphs, $graph, $namer, $id, null);
        } else if(self::_isList($o)) {
          // handle @list
          $_list = new ArrayObject();
          $this->_createNodeMap(
            $o->{'@list'}, $graphs, $graph, $namer, $name, $_list);
          $o = (object)array('@list' => (array)$_list);
          self::addValue(
            $subject, $property, $o,
            array('propertyIsArray' => true, 'allowDuplicate' => false));
        } else {
          // handle @value
          $this->_createNodeMap($o, $graphs, $graph, $namer, $name, null);
          self::addValue(
            $subject, $property, $o,
            array('propertyIsArray' => true, 'allowDuplicate' => false));
        }
      }
    }
  }

  /**
   * Frames subjects according to the given frame.
   *
   * @param stdClass $state the current framing state.
   * @param array $subjects the subjects to filter.
   * @param array $frame the frame.
   * @param mixed $parent the parent subject or top-level array.
   * @param mixed $property the parent property, initialized to null.
   */
  protected function _matchFrame(
    $state, $subjects, $frame, $parent, $property) {
    // validate the frame
    $this->_validateFrame($frame);
    $frame = $frame[0];

    // get flags for current frame
    $options = $state->options;
    $flags = array(
      'embed' => $this->_getFrameFlag($frame, $options, 'embed'),
      'explicit' => $this->_getFrameFlag($frame, $options, 'explicit'),
      'requireAll' => $this->_getFrameFlag($frame, $options, 'requireAll'));

    // filter out subjects that match the frame
    $matches = $this->_filterSubjects($state, $subjects, $frame, $flags);

    // add matches to output
    foreach($matches as $id => $subject) {
      if($flags['embed'] === '@link' && property_exists($state->link, $id)) {
        // TODO: may want to also match an existing linked subject against
        // the current frame ... so different frames could produce different
        // subjects that are only shared in-memory when the frames are the same

        // add existing linked subject
        $this->_addFrameOutput($parent, $property, $state->link->{$id});
        continue;
      }

      /* Note: In order to treat each top-level match as a compartmentalized
      result, clear the unique embedded subjects map when the property is null,
      which only occurs at the top-level. */
      if($property === null) {
        $state->uniqueEmbeds = new stdClass();
      }

      // start output for subject
      $output = new stdClass();
      $output->{'@id'} = $id;
      $state->link->{$id} = $output;

      // if embed is @never or if a circular reference would be created by an
      // embed, the subject cannot be embedded, just add the reference;
      // note that a circular reference won't occur when the embed flag is
      // `@link` as the above check will short-circuit before reaching this point
      if($flags['embed'] === '@never' ||
        $this->_createsCircularReference($subject, $state->subjectStack)) {
        $this->_addFrameOutput($parent, $property, $output);
        continue;
      }

      // if only the last match should be embedded
      if($flags['embed'] === '@last') {
        // remove any existing embed
        if(property_exists($state->uniqueEmbeds, $id)) {
          $this->_removeEmbed($state, $id);
        }
        $state->uniqueEmbeds->{$id} = array(
          'parent' => $parent, 'property' => $property);
      }

      // push matching subject onto stack to enable circular embed checks
      $state->subjectStack[] = $subject;

      // iterate over subject properties
      $props = array_keys((array)$subject);
      sort($props);
      foreach($props as $prop) {
        // copy keywords to output
        if(self::_isKeyword($prop)) {
          $output->{$prop} = self::copy($subject->{$prop});
          continue;
        }

        // explicit is on and property isn't in the frame, skip processing
        if($flags['explicit'] && !property_exists($frame, $prop)) {
          continue;
        }

        // add objects
        $objects = $subject->{$prop};
        foreach($objects as $o) {
          // recurse into list
          if(self::_isList($o)) {
            // add empty list
            $list = (object)array('@list' => array());
            $this->_addFrameOutput($output, $prop, $list);

            // add list objects
            $src = $o->{'@list'};
            foreach($src as $o) {
              if(self::_isSubjectReference($o)) {
                // recurse into subject reference
                $subframe = (property_exists($frame, $prop) ?
                  $frame->{$prop}[0]->{'@list'} :
                  $this->_createImplicitFrame($flags));
                $this->_matchFrame(
                  $state, array($o->{'@id'}), $subframe, $list, '@list');
              } else {
                // include other values automatically
                $this->_addFrameOutput($list, '@list', self::copy($o));
              }
            }
            continue;
          }

          if(self::_isSubjectReference($o)) {
            // recurse into subject reference
            $subframe = (property_exists($frame, $prop) ?
              $frame->{$prop} : $this->_createImplicitFrame($flags));
            $this->_matchFrame(
              $state, array($o->{'@id'}), $subframe, $output, $prop);
          } else {
            // include other values automatically
            $this->_addFrameOutput($output, $prop, self::copy($o));
          }
        }
      }

      // handle defaults
      $props = array_keys((array)$frame);
      sort($props);
      foreach($props as $prop) {
        // skip keywords
        if(self::_isKeyword($prop)) {
          continue;
        }

        // if omit default is off, then include default values for properties
        // that appear in the next frame but are not in the matching subject
        $next = $frame->{$prop}[0];
        $omit_default_on = $this->_getFrameFlag(
          $next, $options, 'omitDefault');
        if(!$omit_default_on && !property_exists($output, $prop)) {
          $preserve = '@null';
          if(property_exists($next, '@default')) {
            $preserve = self::copy($next->{'@default'});
          }
          $preserve = self::arrayify($preserve);
          $output->{$prop} = array((object)array('@preserve' => $preserve));
        }
      }

      // add output to parent
      $this->_addFrameOutput($parent, $property, $output);

      // pop matching subject from circular ref-checking stack
      array_pop($state->subjectStack);
    }
  }

  /**
   * Creates an implicit frame when recursing through subject matches. If
   * a frame doesn't have an explicit frame for a particular property, then
   * a wildcard child frame will be created that uses the same flags that the
   * parent frame used.
   *
   * @param assoc flags the current framing flags.
   *
   * @return array the implicit frame.
   */
  function _createImplicitFrame($flags) {
    $frame = new stdClass();
    foreach($flags as $key => $value) {
      $frame->{'@' . $key} = array($flags[$key]);
    }
    return array($frame);
  }

  /**
   * Checks the current subject stack to see if embedding the given subject
   * would cause a circular reference.
   *
   * @param stdClass subject_to_embed the subject to embed.
   * @param assoc subject_stack the current stack of subjects.
   *
   * @return bool true if a circular reference would be created, false if not.
   */
  function _createsCircularReference($subject_to_embed, $subject_stack) {
    for($i = count($subject_stack) - 1; $i >= 0; --$i) {
      if($subject_stack[$i]->{'@id'} === $subject_to_embed->{'@id'}) {
        return true;
      }
    }
    return false;
  }

  /**
   * Gets the frame flag value for the given flag name.
   *
   * @param stdClass $frame the frame.
   * @param stdClass $options the framing options.
   * @param string $name the flag name.
   *
   * @return mixed $the flag value.
   */
  protected function _getFrameFlag($frame, $options, $name) {
    $flag = "@$name";
    $rval = (property_exists($frame, $flag) ?
      $frame->{$flag}[0] : $options[$name]);
    if($name === 'embed') {
      // default is "@last"
      // backwards-compatibility support for "embed" maps:
      // true => "@last"
      // false => "@never"
      if($rval === true) {
        $rval = '@last';
      } else if($rval === false) {
        $rval = '@never';
      } else if($rval !== '@always' && $rval !== '@never' &&
        $rval !== '@link') {
        $rval = '@last';
      }
    }
    return $rval;
  }

  /**
   * Validates a JSON-LD frame, throwing an exception if the frame is invalid.
   *
   * @param array $frame the frame to validate.
   */
  protected function _validateFrame($frame) {
    if(!is_array($frame) || count($frame) !== 1 || !is_object($frame[0])) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; a JSON-LD frame must be a single object.',
        'jsonld.SyntaxError', null, array('frame' => $frame));
    }
  }

  /**
   * Returns a map of all of the subjects that match a parsed frame.
   *
   * @param stdClass $state the current framing state.
   * @param array $subjects the set of subjects to filter.
   * @param stdClass $frame the parsed frame.
   * @param assoc $flags the frame flags.
   *
   * @return stdClass all of the matched subjects.
   */
  protected function _filterSubjects($state, $subjects, $frame, $flags) {
    $rval = new stdClass();
    sort($subjects);
    foreach($subjects as $id) {
      $subject = $state->subjects->{$id};
      if($this->_filterSubject($subject, $frame, $flags)) {
        $rval->{$id} = $subject;
      }
    }
    return $rval;
  }

  /**
   * Returns true if the given subject matches the given frame.
   *
   * @param stdClass $subject the subject to check.
   * @param stdClass $frame the frame to check.
   * @param assoc $flags the frame flags.
   *
   * @return bool true if the subject matches, false if not.
   */
  protected function _filterSubject($subject, $frame, $flags) {
    // check @type (object value means 'any' type, fall through to ducktyping)
    if(property_exists($frame, '@type') &&
      !(count($frame->{'@type'}) === 1 && is_object($frame->{'@type'}[0]))) {
      $types = $frame->{'@type'};
      foreach($types as $type) {
        // any matching @type is a match
        if(self::hasValue($subject, '@type', $type)) {
          return true;
        }
      }
      return false;
    }

    // check ducktype
    $wildcard = true;
    $matches_some = false;
    foreach($frame as $k => $v) {
      if(self::_isKeyword($k)) {
        // skip non-@id and non-@type
        if($k !== '@id' && $k !== '@type') {
          continue;
        }
        $wildcard = false;

        // check @id for a specific @id value
        if($k === '@id' && is_string($v)) {
          if(!property_exists($subject, $k) || $subject->{$k} !== $v) {
            return false;
          }
          $matches_some = true;
          continue;
        }
      }

      $wildcard = false;

      if(property_exists($subject, $k)) {
        // $v === [] means do not match if property is present
        if(is_array($v) && count($v) === 0) {
          return false;
        }
        $matches_some = true;
        continue;
      }

      // all properties must match to be a duck unless a @default is specified
      $has_default = (is_array($v) && count($v) === 1 && is_object($v[0]) &&
        property_exists($v[0], '@default'));
      if($flags['requireAll'] && !$has_default) {
        return false;
      }
    }

    // return true if wildcard or subject matches some properties
    return $wildcard || $matches_some;
  }

  /**
   * Removes an existing embed.
   *
   * @param stdClass $state the current framing state.
   * @param string $id the @id of the embed to remove.
   */
  protected function _removeEmbed($state, $id) {
    // get existing embed
    $embeds = $state->uniqueEmbeds;
    $embed = $embeds->{$id};
    $property = $embed['property'];

    // create reference to replace embed
    $subject = (object)array('@id' => $id);

    // remove existing embed
    if(is_array($embed->parent)) {
      // replace subject with reference
      foreach($embed->parent as $i => $parent) {
        if(self::compareValues($parent, $subject)) {
          $embed->parent[$i] = $subject;
          break;
        }
      }
    } else {
      // replace subject with reference
      $use_array = is_array($embed->parent->{$property});
      self::removeValue($embed->parent, $property, $subject,
        array('propertyIsArray' => $use_array));
      self::addValue($embed->parent, $property, $subject,
        array('propertyIsArray' => $use_array));
    }

    // recursively remove dependent dangling embeds
    $removeDependents = function($id) {
      // get embed keys as a separate array to enable deleting keys in map
      $ids = array_keys((array)$embeds);
      foreach($ids as $next) {
        if(property_exists($embeds, $next) &&
          is_object($embeds->{$next}->parent) &&
          $embeds->{$next}->parent->{'@id'} === $id) {
          unset($embeds->{$next});
          $removeDependents($next);
        }
      }
    };
    $removeDependents($id);
  }

  /**
   * Adds framing output to the given parent.
   *
   * @param mixed $parent the parent to add to.
   * @param string $property the parent property.
   * @param mixed $output the output to add.
   */
  protected function _addFrameOutput($parent, $property, $output) {
    if(is_object($parent) && !($parent instanceof ArrayObject)) {
      self::addValue(
        $parent, $property, $output, array('propertyIsArray' => true));
    } else {
      $parent[] = $output;
    }
  }

  /**
   * Removes the @preserve keywords as the last step of the framing algorithm.
   *
   * @param stdClass $ctx the active context used to compact the input.
   * @param mixed $input the framed, compacted output.
   * @param assoc $options the compaction options used.
   *
   * @return mixed the resulting output.
   */
  protected function _removePreserve($ctx, $input, $options) {
    // recurse through arrays
    if(is_array($input)) {
      $output = array();
      foreach($input as $e) {
        $result = $this->_removePreserve($ctx, $e, $options);
        // drop nulls from arrays
        if($result !== null) {
          $output[] = $result;
        }
      }
      $input = $output;
    } else if(is_object($input)) {
      // remove @preserve
      if(property_exists($input, '@preserve')) {
        if($input->{'@preserve'} === '@null') {
          return null;
        }
        return $input->{'@preserve'};
      }

      // skip @values
      if(self::_isValue($input)) {
        return $input;
      }

      // recurse through @lists
      if(self::_isList($input)) {
        $input->{'@list'} = $this->_removePreserve(
          $ctx, $input->{'@list'}, $options);
        return $input;
      }

      // handle in-memory linked nodes
      $id_alias = $this->_compactIri($ctx, '@id');
      if(property_exists($input, $id_alias)) {
        $id = $input->{$id_alias};
        if(isset($options['link'][$id])) {
          $idx = array_search($input, $options['link'][$id]);
          if($idx === false) {
            // prevent circular visitation
            $options['link'][$id][] = $input;
          } else {
            // already visited
            return $options['link'][$id][$idx];
          }
        } else {
          // prevent circular visitation
          $options['link'][$id] = array($input);
        }
      }

      // recurse through properties
      foreach($input as $prop => $v) {
        $result = $this->_removePreserve($ctx, $v, $options);
        $container = self::getContextValue($ctx, $prop, '@container');
        if($options['compactArrays'] &&
          is_array($result) && count($result) === 1 &&
          $container !== '@set' && $container !== '@list') {
          $result = $result[0];
        }
        $input->{$prop} = $result;
      }
    }
    return $input;
  }

  /**
   * Compares two RDF triples for equality.
   *
   * @param stdClass $t1 the first triple.
   * @param stdClass $t2 the second triple.
   *
   * @return true if the triples are the same, false if not.
   */
  protected static function _compareRDFTriples($t1, $t2) {
    foreach(array('subject', 'predicate', 'object') as $attr) {
      if($t1->{$attr}->type !== $t2->{$attr}->type ||
        $t1->{$attr}->value !== $t2->{$attr}->value) {
        return false;
      }
    }
    if(property_exists($t1->object, 'language') !==
      property_exists($t1->object, 'language')) {
      return false;
    }
    if(property_exists($t1->object, 'language') &&
      $t1->object->language !== $t2->object->language) {
      return false;
    }
    if(property_exists($t1->object, 'datatype') &&
      $t1->object->datatype !== $t2->object->datatype) {
      return false;
    }
    return true;
  }

  /**
   * Hashes all of the quads about a blank node.
   *
   * @param string $id the ID of the bnode to hash quads for.
   * @param stdClass $bnodes the mapping of bnodes to quads.
   * @param UniqueNamer $namer the canonical bnode namer.
   *
   * @return string the new hash.
   */
  protected function _hashQuads($id, $bnodes, $namer) {
    // return cached hash
    if(property_exists($bnodes->{$id}, 'hash')) {
      return $bnodes->{$id}->hash;
    }

    // serialize all of bnode's quads
    $quads = $bnodes->{$id}->quads;
    $nquads = array();
    foreach($quads as $quad) {
      $nquads[] = $this->toNQuad($quad, property_exists($quad, 'name') ?
        $quad->name->value : null, $id);
    }

    // sort serialized quads
    sort($nquads);

    // cache and return hashed quads
    $hash = $bnodes->{$id}->hash = sha1(implode($nquads));
    return $hash;
  }

  /**
   * Produces a hash for the paths of adjacent bnodes for a bnode,
   * incorporating all information about its subgraph of bnodes. This
   * method will recursively pick adjacent bnode permutations that produce the
   * lexicographically-least 'path' serializations.
   *
   * @param string $id the ID of the bnode to hash paths for.
   * @param stdClass $bnodes the map of bnode quads.
   * @param UniqueNamer $namer the canonical bnode namer.
   * @param UniqueNamer $path_namer the namer used to assign names to adjacent
   *          bnodes.
   *
   * @return stdClass the hash and path namer used.
   */
  protected function _hashPaths($id, $bnodes, $namer, $path_namer) {
    // create SHA-1 digest
    $md = hash_init('sha1');

    // group adjacent bnodes by hash, keep properties and references separate
    $groups = new stdClass();
    $quads = $bnodes->{$id}->quads;
    foreach($quads as $quad) {
      // get adjacent bnode
      $bnode = $this->_getAdjacentBlankNodeName($quad->subject, $id);
      if($bnode !== null) {
        // normal property
        $direction = 'p';
      } else {
        $bnode = $this->_getAdjacentBlankNodeName($quad->object, $id);
        if($bnode !== null) {
          // reverse property
          $direction = 'r';
        }
      }
      if($bnode !== null) {
        // get bnode name (try canonical, path, then hash)
        if($namer->isNamed($bnode)) {
          $name = $namer->getName($bnode);
        } else if($path_namer->isNamed($bnode)) {
          $name = $path_namer->getName($bnode);
        } else {
          $name = $this->_hashQuads($bnode, $bnodes, $namer);
        }

        // hash direction, property, and bnode name/hash
        $group_md = hash_init('sha1');
        hash_update($group_md, $direction);
        hash_update($group_md, $quad->predicate->value);
        hash_update($group_md, $name);
        $group_hash = hash_final($group_md);

        // add bnode to hash group
        if(property_exists($groups, $group_hash)) {
          $groups->{$group_hash}[] = $bnode;
        } else {
          $groups->{$group_hash} = array($bnode);
        }
      }
    }

    // iterate over groups in sorted hash order
    $group_hashes = array_keys((array)$groups);
    sort($group_hashes);
    foreach($group_hashes as $group_hash) {
      // digest group hash
      hash_update($md, $group_hash);

      // choose a path and namer from the permutations
      $chosen_path = null;
      $chosen_namer = null;
      $permutator = new Permutator($groups->{$group_hash});
      while($permutator->hasNext()) {
        $permutation = $permutator->next();
        $path_namer_copy = clone $path_namer;

        // build adjacent path
        $path = '';
        $skipped = false;
        $recurse = array();
        foreach($permutation as $bnode) {
          // use canonical name if available
          if($namer->isNamed($bnode)) {
            $path .= $namer->getName($bnode);
          } else {
            // recurse if bnode isn't named in the path yet
            if(!$path_namer_copy->isNamed($bnode)) {
              $recurse[] = $bnode;
            }
            $path .= $path_namer_copy->getName($bnode);
          }

          // skip permutation if path is already >= chosen path
          if($chosen_path !== null && strlen($path) >= strlen($chosen_path) &&
            $path > $chosen_path) {
            $skipped = true;
            break;
          }
        }

        // recurse
        if(!$skipped) {
          foreach($recurse as $bnode) {
            $result = $this->_hashPaths(
              $bnode, $bnodes, $namer, $path_namer_copy);
            $path .= $path_namer_copy->getName($bnode);
            $path .= "<{$result->hash}>";
            $path_namer_copy = $result->pathNamer;

            // skip permutation if path is already >= chosen path
            if($chosen_path !== null &&
              strlen($path) >= strlen($chosen_path) && $path > $chosen_path) {
              $skipped = true;
              break;
            }
          }
        }

        if(!$skipped && ($chosen_path === null || $path < $chosen_path)) {
          $chosen_path = $path;
          $chosen_namer = $path_namer_copy;
        }
      }

      // digest chosen path and update namer
      hash_update($md, $chosen_path);
      $path_namer = $chosen_namer;
    }

    // return SHA-1 hash and path namer
    return (object)array(
      'hash' => hash_final($md), 'pathNamer' => $path_namer);
  }

  /**
   * A helper function that gets the blank node name from an RDF quad
   * node (subject or object). If the node is not a blank node or its
   * value does not match the given blank node ID, it will be returned.
   *
   * @param stdClass $node the RDF quad node.
   * @param string $id the ID of the blank node to look next to.
   *
   * @return mixed the adjacent blank node name or null if none was found.
   */
  protected function _getAdjacentBlankNodeName($node, $id) {
    if($node->type === 'blank node' && $node->value !== $id) {
      return $node->value;
    }
    return null;
  }

  /**
   * Compares two strings first based on length and then lexicographically.
   *
   * @param string $a the first string.
   * @param string $b the second string.
   *
   * @return integer -1 if a < b, 1 if a > b, 0 if a == b.
   */
  protected function _compareShortestLeast($a, $b) {
    $len_a = strlen($a);
    $len_b = strlen($b);
    if($len_a < $len_b) {
      return -1;
    }
    if($len_b < $len_a) {
      return 1;
    }
    if($a === $b) {
      return 0;
    }
    return ($a < $b) ? -1 : 1;
  }

  /**
   * Picks the preferred compaction term from the given inverse context entry.
   *
   * @param active_ctx the active context.
   * @param iri the IRI to pick the term for.
   * @param value the value to pick the term for.
   * @param containers the preferred containers.
   * @param type_or_language either '@type' or '@language'.
   * @param type_or_language_value the preferred value for '@type' or
   *          '@language'.
   *
   * @return mixed the preferred term.
   */
  protected function _selectTerm(
    $active_ctx, $iri, $value, $containers,
    $type_or_language, $type_or_language_value) {
    if($type_or_language_value === null) {
      $type_or_language_value = '@null';
    }

    // options for the value of @type or @language
    $prefs = array();

    // determine prefs for @id based on whether or not value compacts to a term
    if(($type_or_language_value === '@id' ||
      $type_or_language_value === '@reverse') &&
      self::_isSubjectReference($value)) {
      // prefer @reverse first
      if($type_or_language_value === '@reverse') {
        $prefs[] = '@reverse';
      }
      // try to compact value to a term
      $term = $this->_compactIri(
        $active_ctx, $value->{'@id'}, null, array('vocab' => true));
      if(property_exists($active_ctx->mappings, $term) &&
        $active_ctx->mappings->{$term} &&
        $active_ctx->mappings->{$term}->{'@id'} === $value->{'@id'}) {
        // prefer @vocab
        array_push($prefs, '@vocab', '@id');
      } else {
        // prefer @id
        array_push($prefs, '@id', '@vocab');
      }
    } else {
      $prefs[] = $type_or_language_value;
    }
    $prefs[] = '@none';

    $container_map = $active_ctx->inverse->{$iri};
    foreach($containers as $container) {
      // if container not available in the map, continue
      if(!property_exists($container_map, $container)) {
        continue;
      }

      $type_or_language_value_map =
        $container_map->{$container}->{$type_or_language};
      foreach($prefs as $pref) {
        // if type/language option not available in the map, continue
        if(!property_exists($type_or_language_value_map, $pref)) {
          continue;
        }

        // select term
        return $type_or_language_value_map->{$pref};
      }
    }
    return null;
  }

  /**
   * Compacts an IRI or keyword into a term or prefix if it can be. If the
   * IRI has an associated value it may be passed.
   *
   * @param stdClass $active_ctx the active context to use.
   * @param string $iri the IRI to compact.
   * @param mixed $value the value to check or null.
   * @param assoc $relative_to options for how to compact IRIs:
   *          vocab: true to split after @vocab, false not to.
   * @param bool $reverse true if a reverse property is being compacted, false
   *          if not.
   *
   * @return string the compacted term, prefix, keyword alias, or original IRI.
   */
  protected function _compactIri(
    $active_ctx, $iri, $value=null, $relative_to=array(), $reverse=false) {
    // can't compact null
    if($iri === null) {
      return $iri;
    }

    $inverse_ctx = $this->_getInverseContext($active_ctx);

    if(self::_isKeyword($iri)) {
      // a keyword can only be compacted to simple alias
      if(property_exists($inverse_ctx, $iri)) {
        return $inverse_ctx->$iri->{'@none'}->{'@type'}->{'@none'};
      }
      return $iri;
    }

    if(!isset($relative_to['vocab'])) {
      $relative_to['vocab'] = false;
    }

    // use inverse context to pick a term if iri is relative to vocab
    if($relative_to['vocab'] && property_exists($inverse_ctx, $iri)) {
      $default_language = '@none';
      if(property_exists($active_ctx, '@language')) {
        $default_language = $active_ctx->{'@language'};
      }

      // prefer @index if available in value
      $containers = array();
      if(is_object($value) && property_exists($value, '@index')) {
        $containers[] = '@index';
      }

      // defaults for term selection based on type/language
      $type_or_language = '@language';
      $type_or_language_value = '@null';

      if($reverse) {
        $type_or_language = '@type';
        $type_or_language_value = '@reverse';
        $containers[] = '@set';
      } else if(self::_isList($value)) {
        // choose the most specific term that works for all elements in @list
        // only select @list containers if @index is NOT in value
        if(!property_exists($value, '@index')) {
          $containers[] = '@list';
        }
        $list = $value->{'@list'};
        $common_language = (count($list) === 0) ? $default_language : null;
        $common_type = null;
        foreach($list as $item) {
          $item_language = '@none';
          $item_type = '@none';
          if(self::_isValue($item)) {
            if(property_exists($item, '@language')) {
              $item_language = $item->{'@language'};
            } else if(property_exists($item, '@type')) {
              $item_type = $item->{'@type'};
            } else {
              // plain literal
              $item_language = '@null';
            }
          } else {
            $item_type = '@id';
          }
          if($common_language === null) {
            $common_language = $item_language;
          } else if($item_language !== $common_language &&
            self::_isValue($item)) {
            $common_language = '@none';
          }
          if($common_type === null) {
            $common_type = $item_type;
          } else if($item_type !== $common_type) {
            $common_type = '@none';
          }
          // there are different languages and types in the list, so choose
          // the most generic term, no need to keep iterating the list
          if($common_language === '@none' && $common_type === '@none') {
            break;
          }
        }
        if($common_language === null) {
          $common_language = '@none';
        }
        if($common_type === null) {
          $common_type = '@none';
        }
        if($common_type !== '@none') {
          $type_or_language = '@type';
          $type_or_language_value = $common_type;
        } else {
          $type_or_language_value = $common_language;
        }
      } else {
        if(self::_isValue($value)) {
          if(property_exists($value, '@language') &&
            !property_exists($value, '@index')) {
            $containers[] = '@language';
            $type_or_language_value = $value->{'@language'};
          } else if(property_exists($value, '@type')) {
            $type_or_language = '@type';
            $type_or_language_value = $value->{'@type'};
          }
        } else {
          $type_or_language = '@type';
          $type_or_language_value = '@id';
        }
        $containers[] = '@set';
      }

      // do term selection
      $containers[] = '@none';
      $term = $this->_selectTerm(
        $active_ctx, $iri, $value,
        $containers, $type_or_language, $type_or_language_value);
      if($term !== null) {
        return $term;
      }
    }

    // no term match, use @vocab if available
    if($relative_to['vocab']) {
      if(property_exists($active_ctx, '@vocab')) {
        // determine if vocab is a prefix of the iri
        $vocab = $active_ctx->{'@vocab'};
        if(strpos($iri, $vocab) === 0 && $iri !== $vocab) {
          // use suffix as relative iri if it is not a term in the active
          // context
          $suffix = substr($iri, strlen($vocab));
          if(!property_exists($active_ctx->mappings, $suffix)) {
            return $suffix;
          }
        }
      }
    }

    // no term or @vocab match, check for possible CURIEs
    $choice = null;
    $idx = 0;
    $partial_matches = array();
    $iri_map = $active_ctx->fast_curie_map;
    // check for partial matches of against `iri`, which means look until
    // iri.length - 1, not full length
    $max_partial_length = strlen($iri) - 1;
    for(; $idx < $max_partial_length && isset($iri_map[$iri[$idx]]); ++$idx) {
      $iri_map = $iri_map[$iri[$idx]];
      if(isset($iri_map[''])) {
        $entry = $iri_map[''][0];
        $entry->iri_length = $idx + 1;
        $partial_matches[] = $entry;
      }
    }
    // check partial matches in reverse order to prefer longest ones first
    $partial_matches = array_reverse($partial_matches);
    foreach($partial_matches as $entry) {
      $terms = $entry->terms;
      foreach($terms as $term) {
        // a CURIE is usable if:
        // 1. it has no mapping, OR
        // 2. value is null, which means we're not compacting an @value, AND
        //   the mapping matches the IRI
        $curie = $term . ':' . substr($iri, $entry->iri_length);
        $is_usable_curie = (!property_exists($active_ctx->mappings, $curie) ||
          ($value === null &&
          $active_ctx->mappings->{$curie}->{'@id'} === $iri));

        // select curie if it is shorter or the same length but
        // lexicographically less than the current choice
        if($is_usable_curie && ($choice === null ||
          self::_compareShortestLeast($curie, $choice) < 0)) {
          $choice = $curie;
        }
      }
    }

    // return chosen curie
    if($choice !== null) {
      return $choice;
    }

    // compact IRI relative to base
    if(!$relative_to['vocab']) {
      return jsonld_remove_base($active_ctx->{'@base'}, $iri);
    }

    // return IRI as is
    return $iri;
  }

  /**
   * Performs value compaction on an object with '@value' or '@id' as the only
   * property.
   *
   * @param stdClass $active_ctx the active context.
   * @param string $active_property the active property that points to the
   *          value.
   * @param mixed $value the value to compact.
   *
   * @return mixed the compaction result.
   */
  protected function _compactValue($active_ctx, $active_property, $value) {
    // value is a @value
    if(self::_isValue($value)) {
      // get context rules
      $type = self::getContextValue($active_ctx, $active_property, '@type');
      $language = self::getContextValue(
        $active_ctx, $active_property, '@language');
      $container = self::getContextValue(
        $active_ctx, $active_property, '@container');

      // whether or not the value has an @index that must be preserved
      $preserve_index = (property_exists($value, '@index') &&
        $container !== '@index');

      // if there's no @index to preserve
      if(!$preserve_index) {
        // matching @type or @language specified in context, compact value
        if(self::_hasKeyValue($value, '@type', $type) ||
          self::_hasKeyValue($value, '@language', $language)) {
          return $value->{'@value'};
        }
      }

      // return just the value of @value if all are true:
      // 1. @value is the only key or @index isn't being preserved
      // 2. there is no default language or @value is not a string or
      //   the key has a mapping with a null @language
      $key_count = count(array_keys((array)$value));
      $is_value_only_key = ($key_count === 1 ||
        ($key_count === 2 && property_exists($value, '@index') &&
        !$preserve_index));
      $has_default_language = property_exists($active_ctx, '@language');
      $is_value_string = is_string($value->{'@value'});
      $has_null_mapping = (
        property_exists($active_ctx->mappings, $active_property) &&
        $active_ctx->mappings->{$active_property} !== null &&
        self::_hasKeyValue(
          $active_ctx->mappings->{$active_property}, '@language', null));
      if($is_value_only_key &&
        (!$has_default_language || !$is_value_string || $has_null_mapping)) {
        return $value->{'@value'};
      }

      $rval = new stdClass();

      // preserve @index
      if($preserve_index) {
        $rval->{$this->_compactIri($active_ctx, '@index')} = $value->{'@index'};
      }

      // compact @type IRI
      if(property_exists($value, '@type')) {
        $rval->{$this->_compactIri($active_ctx, '@type')} = $this->_compactIri(
          $active_ctx, $value->{'@type'}, null, array('vocab' => true));
      } else if(property_exists($value, '@language')) {
        // alias @language
        $rval->{$this->_compactIri($active_ctx, '@language')} =
          $value->{'@language'};
      }

      // alias @value
      $rval->{$this->_compactIri($active_ctx, '@value')} = $value->{'@value'};

      return $rval;
    }

    // value is a subject reference
    $expanded_property = $this->_expandIri(
      $active_ctx, $active_property, array('vocab' => true));
    $type = self::getContextValue($active_ctx, $active_property, '@type');
    $compacted = $this->_compactIri(
      $active_ctx, $value->{'@id'}, null,
      array('vocab' => ($type === '@vocab')));

    // compact to scalar
    if($type === '@id' || $type === '@vocab' ||
      $expanded_property === '@graph') {
      return $compacted;
    }

    $rval = (object)array(
      $this->_compactIri($active_ctx, '@id') => $compacted);
    return $rval;
  }

  /**
   * Creates a term definition during context processing.
   *
   * @param stdClass $active_ctx the current active context.
   * @param stdClass $local_ctx the local context being processed.
   * @param string $term the key in the local context to define the mapping for.
   * @param stdClass $defined a map of defining/defined keys to detect cycles
   *          and prevent double definitions.
   */
  protected function _createTermDefinition(
    $active_ctx, $local_ctx, $term, $defined) {
    if(property_exists($defined, $term)) {
      // term already defined
      if($defined->{$term}) {
        return;
      }
      // cycle detected
      throw new JsonLdException(
        'Cyclical context definition detected.',
        'jsonld.CyclicalContext', 'cyclic IRI mapping',
        array('context' => $local_ctx, 'term' => $term));
    }

    // now defining term
    $defined->{$term} = false;

    if(self::_isKeyword($term)) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; keywords cannot be overridden.',
        'jsonld.SyntaxError', 'keyword redefinition',
        array('context' => $local_ctx, 'term' => $term));
    }

    // remove old mapping
    if(property_exists($active_ctx->mappings, $term)) {
      unset($active_ctx->mappings->{$term});
    }

    // get context term value
    $value = $local_ctx->{$term};

    // clear context entry
    if($value === null || (is_object($value) &&
      self::_hasKeyValue($value, '@id', null))) {
      $active_ctx->mappings->{$term} = null;
      $defined->{$term} = true;
      return;
    }

    // convert short-hand value to object w/@id
    if(is_string($value)) {
      $value = (object)array('@id' => $value);
    }

    if(!is_object($value)) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; @context property values must be ' .
        'strings or objects.', 'jsonld.SyntaxError', 'invalid term definition',
        array('context' => $local_ctx));
    }

    // create new mapping
    $mapping = $active_ctx->mappings->{$term} = new stdClass();
    $mapping->reverse = false;

    if(property_exists($value, '@reverse')) {
      if(property_exists($value, '@id')) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; a @reverse term definition must not ' +
          'contain @id.', 'jsonld.SyntaxError', 'invalid reverse property',
          array('context' => $local_ctx));
      }
      $reverse = $value->{'@reverse'};
      if(!is_string($reverse)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; a @context @reverse value must be a string.',
          'jsonld.SyntaxError', 'invalid IRI mapping',
          array('context' => $local_ctx));
      }

      // expand and add @id mapping
      $id = $this->_expandIri(
        $active_ctx, $reverse, array('vocab' => true, 'base' => false),
        $local_ctx, $defined);
      if(!self::_isAbsoluteIri($id)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @reverse value must be ' .
          'an absolute IRI or a blank node identifier.',
          'jsonld.SyntaxError', 'invalid IRI mapping',
          array('context' => $local_ctx));
      }
      $mapping->{'@id'} = $id;
      $mapping->reverse = true;
    } else if(property_exists($value, '@id')) {
      $id = $value->{'@id'};
      if(!is_string($id)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @id value must be a string.',
          'jsonld.SyntaxError', 'invalid IRI mapping',
          array('context' => $local_ctx));
      }
      if($id !== $term) {
        // add @id to mapping
        $id = $this->_expandIri(
          $active_ctx, $id, array('vocab' => true, 'base' => false),
          $local_ctx, $defined);
        if(!self::_isAbsoluteIri($id) && !self::_isKeyword($id)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; @context @id value must be an ' .
            'absolute IRI, a blank node identifier, or a keyword.',
            'jsonld.SyntaxError', 'invalid IRI mapping',
            array('context' => $local_ctx));
        }
        $mapping->{'@id'} = $id;
      }
    }

    // always compute whether term has a colon as an optimization for
    // _compactIri
    $colon = strpos($term, ':');
    $mapping->_term_has_colon = ($colon !== false);

    if(!property_exists($mapping, '@id')) {
      // see if the term has a prefix
      if($mapping->_term_has_colon) {
        $prefix = substr($term, 0, $colon);
        if(property_exists($local_ctx, $prefix)) {
          // define parent prefix
          $this->_createTermDefinition(
            $active_ctx, $local_ctx, $prefix, $defined);
        }

        if(property_exists($active_ctx->mappings, $prefix) &&
          $active_ctx->mappings->{$prefix}) {
          // set @id based on prefix parent
          $suffix = substr($term, $colon + 1);
          $mapping->{'@id'} = $active_ctx->mappings->{$prefix}->{'@id'} .
            $suffix;
        } else {
          // term is an absolute IRI
          $mapping->{'@id'} = $term;
        }
      } else {
        // non-IRIs *must* define @ids if @vocab is not available
        if(!property_exists($active_ctx, '@vocab')) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; @context terms must define an @id.',
            'jsonld.SyntaxError', 'invalid IRI mapping',
            array('context' => $local_ctx, 'term' => $term));
        }
        // prepend vocab to term
        $mapping->{'@id'} = $active_ctx->{'@vocab'} . $term;
      }
    }

    // optimization to store length of @id once for _compactIri
    $mapping->_id_length = strlen($mapping->{'@id'});

    // IRI mapping now defined
    $defined->{$term} = true;

    if(property_exists($value, '@type')) {
      $type = $value->{'@type'};
      if(!is_string($type)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @type values must be strings.',
          'jsonld.SyntaxError', 'invalid type mapping',
          array('context' => $local_ctx));
      }

      if($type !== '@id' && $type !== '@vocab') {
        // expand @type to full IRI
        $type = $this->_expandIri(
          $active_ctx, $type, array('vocab' => true), $local_ctx, $defined);
        if(!self::_isAbsoluteIri($type)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; an @context @type value must ' .
            'be an absolute IRI.', 'jsonld.SyntaxError',
            'invalid type mapping', array('context' => $local_ctx));
        }
        if(strpos($type, '_:') === 0) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; an @context @type values must ' .
            'be an IRI, not a blank node identifier.',
            'jsonld.SyntaxError', 'invalid type mapping',
            array('context' => $local_ctx));
        }
      }

      // add @type to mapping
      $mapping->{'@type'} = $type;
    }

    if(property_exists($value, '@container')) {
      $container = $value->{'@container'};
      if($container !== '@list' && $container !== '@set' &&
        $container !== '@index' && $container !== '@language') {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @container value must be ' .
          'one of the following: @list, @set, @index, or @language.',
          'jsonld.SyntaxError', 'invalid container mapping',
          array('context' => $local_ctx));
      }
      if($mapping->reverse && $container !== '@index' &&
        $container !== '@set' && $container !== null) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @container value for a @reverse ' +
          'type definition must be @index or @set.',
          'jsonld.SyntaxError', 'invalid reverse property',
          array('context' => $local_ctx));
      }

      // add @container to mapping
      $mapping->{'@container'} = $container;
    }

    if(property_exists($value, '@language') &&
      !property_exists($value, '@type')) {
      $language = $value->{'@language'};
      if($language !== null && !is_string($language)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @language value must be ' .
          'a string or null.', 'jsonld.SyntaxError',
          'invalid language mapping', array('context' => $local_ctx));
      }

      // add @language to mapping
      if($language !== null) {
        $language = strtolower($language);
      }
      $mapping->{'@language'} = $language;
    }

    // disallow aliasing @context and @preserve
    $id = $mapping->{'@id'};
    if($id === '@context' || $id === '@preserve') {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; @context and @preserve cannot be aliased.',
        'jsonld.SyntaxError', 'invalid keyword alias',
        array('context' => $local_ctx));
    }
  }

  /**
   * Expands a string to a full IRI. The string may be a term, a prefix, a
   * relative IRI, or an absolute IRI. The associated absolute IRI will be
   * returned.
   *
   * @param stdClass $active_ctx the current active context.
   * @param string $value the string to expand.
   * @param assoc $relative_to options for how to resolve relative IRIs:
   *          base: true to resolve against the base IRI, false not to.
   *          vocab: true to concatenate after @vocab, false not to.
   * @param stdClass $local_ctx the local context being processed (only given
   *          if called during document processing).
   * @param defined a map for tracking cycles in context definitions (only given
   *          if called during document processing).
   *
   * @return mixed the expanded value.
   */
  function _expandIri(
    $active_ctx, $value, $relative_to=array(), $local_ctx=null, $defined=null) {
    // already expanded
    if($value === null || self::_isKeyword($value)) {
      return $value;
    }

    // define term dependency if not defined
    if($local_ctx !== null && property_exists($local_ctx, $value) &&
      !self::_hasKeyValue($defined, $value, true)) {
      $this->_createTermDefinition($active_ctx, $local_ctx, $value, $defined);
    }

    if(isset($relative_to['vocab']) && $relative_to['vocab']) {
      if(property_exists($active_ctx->mappings, $value)) {
        $mapping = $active_ctx->mappings->{$value};

        // value is explicitly ignored with a null mapping
        if($mapping === null) {
          return null;
        }

        // value is a term
        return $mapping->{'@id'};
      }
    }

    // split value into prefix:suffix
    $colon = strpos($value, ':');
    if($colon !== false) {
      $prefix = substr($value, 0, $colon);
      $suffix = substr($value, $colon + 1);

      // do not expand blank nodes (prefix of '_') or already-absolute
      // IRIs (suffix of '//')
      if($prefix === '_' || strpos($suffix, '//') === 0) {
        return $value;
      }

      // prefix dependency not defined, define it
      if($local_ctx !== null && property_exists($local_ctx, $prefix)) {
        $this->_createTermDefinition(
          $active_ctx, $local_ctx, $prefix, $defined);
      }

      // use mapping if prefix is defined
      if(property_exists($active_ctx->mappings, $prefix)) {
        $mapping = $active_ctx->mappings->{$prefix};
        if($mapping) {
          return $mapping->{'@id'} . $suffix;
        }
      }

      // already absolute IRI
      return $value;
    }

    // prepend vocab
    if(isset($relative_to['vocab']) && $relative_to['vocab'] &&
      property_exists($active_ctx, '@vocab')) {
      return $active_ctx->{'@vocab'} . $value;
    }

    // prepend base
    $rval = $value;
    if(isset($relative_to['base']) && $relative_to['base']) {
      $rval = jsonld_prepend_base($active_ctx->{'@base'}, $rval);
    }

    return $rval;
  }

  /**
   * Finds all @context URLs in the given JSON-LD input.
   *
   * @param mixed $input the JSON-LD input.
   * @param stdClass $urls a map of URLs (url => false/@contexts).
   * @param bool $replace true to replace the URLs in the given input with
   *           the @contexts from the urls map, false not to.
   * @param string $base the base URL to resolve relative URLs with.
   */
  protected function _findContextUrls($input, $urls, $replace, $base) {
    if(is_array($input)) {
      foreach($input as $e) {
        $this->_findContextUrls($e, $urls, $replace, $base);
      }
    } else if(is_object($input)) {
      foreach($input as $k => &$v) {
        if($k !== '@context') {
          $this->_findContextUrls($v, $urls, $replace, $base);
          continue;
        }

        // array @context
        if(is_array($v)) {
          $length = count($v);
          for($i = 0; $i < $length; ++$i) {
            if(is_string($v[$i])) {
              $url = jsonld_prepend_base($base, $v[$i]);
              // replace w/@context if requested
              if($replace) {
                $ctx = $urls->{$url};
                if(is_array($ctx)) {
                  // add flattened context
                  array_splice($v, $i, 1, $ctx);
                  $i += count($ctx) - 1;
                  $length = count($v);
                } else {
                  $v[$i] = $ctx;
                }
              } else if(!property_exists($urls, $url)) {
                // @context URL found
                $urls->{$url} = false;
              }
            }
          }
        } else if(is_string($v)) {
          // string @context
          $v = jsonld_prepend_base($base, $v);
          // replace w/@context if requested
          if($replace) {
            $input->{$k} = $urls->{$v};
          } else if(!property_exists($urls, $v)) {
            // @context URL found
            $urls->{$v} = false;
          }
        }
      }
    }
  }

  /**
   * Retrieves external @context URLs using the given document loader. Each
   * instance of @context in the input that refers to a URL will be replaced
   * with the JSON @context found at that URL.
   *
   * @param mixed $input the JSON-LD input with possible contexts.
   * @param stdClass $cycles an object for tracking context cycles.
   * @param callable $load_document(url) the document loader.
   * @param base $base the base URL to resolve relative URLs against.
   *
   * @return mixed the result.
   */
  protected function _retrieveContextUrls(
    &$input, $cycles, $load_document, $base='') {
    if(count(get_object_vars($cycles)) > self::MAX_CONTEXT_URLS) {
      throw new JsonLdException(
        'Maximum number of @context URLs exceeded.',
        'jsonld.ContextUrlError', 'loading remote context failed',
        array('max' => self::MAX_CONTEXT_URLS));
    }

    // for tracking the URLs to retrieve
    $urls = new stdClass();

    // regex for validating URLs
    $regex = '/(http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/';

    // find all URLs in the given input
    $this->_findContextUrls($input, $urls, false, $base);

    // queue all unretrieved URLs
    $queue = array();
    foreach($urls as $url => $ctx) {
      if($ctx === false) {
        // validate URL
        if(!preg_match($regex, $url)) {
          throw new JsonLdException(
            'Malformed or unsupported URL.', 'jsonld.InvalidUrl',
            'loading remote context failed', array('url' => $url));
        }
        $queue[] = $url;
      }
    }

    // retrieve URLs in queue
    foreach($queue as $url) {
      // check for context URL cycle
      if(property_exists($cycles, $url)) {
        throw new JsonLdException(
          'Cyclical @context URLs detected.',
          'jsonld.ContextUrlError', 'recursive context inclusion',
          array('url' => $url));
      }
      $_cycles = self::copy($cycles);
      $_cycles->{$url} = true;

      // retrieve URL
      $remote_doc = call_user_func($load_document, $url);
      $ctx = $remote_doc->document;

      // parse string context as JSON
      if(is_string($ctx)) {
        try {
          $ctx = self::_parse_json($ctx);
        } catch(Exception $e) {
          throw new JsonLdException(
            'Could not parse JSON from URL.',
            'jsonld.ParseError', 'loading remote context failed',
            array('url' => $url), $e);
        }
      }

      // ensure ctx is an object
      if(!is_object($ctx)) {
        throw new JsonLdException(
          'Derefencing a URL did not result in a valid JSON-LD object.',
          'jsonld.InvalidUrl', 'invalid remote context', array('url' => $url));
      }

      // use empty context if no @context key is present
      if(!property_exists($ctx, '@context')) {
        $ctx = (object)array('@context' => new stdClass());
      } else {
        $ctx = (object)array('@context' => $ctx->{'@context'});
      }

      // append context URL to context if given
      if($remote_doc->contextUrl !== null) {
        $ctx->{'@context'} = self::arrayify($ctx->{'@context'});
        $ctx->{'@context'}[] = $remote_doc->contextUrl;
      }

      // recurse
      $this->_retrieveContextUrls($ctx, $_cycles, $load_document, $url);
      $urls->{$url} = $ctx->{'@context'};
    }

    // replace all URLS in the input
    $this->_findContextUrls($input, $urls, true, $base);
  }

  /**
   * Gets the initial context.
   *
   * @param assoc $options the options to use.
   *          base the document base IRI.
   *
   * @return stdClass the initial context.
   */
  protected function _getInitialContext($options) {
    return (object)array(
      '@base' => jsonld_parse_url($options['base']),
      'mappings' => new stdClass(),
      'inverse' => null);
  }

  /**
   * Generates an inverse context for use in the compaction algorithm, if
   * not already generated for the given active context.
   *
   * @param stdClass $active_ctx the active context to use.
   *
   * @return stdClass the inverse context.
   */
  protected function _getInverseContext($active_ctx) {
    // inverse context already generated
    if($active_ctx->inverse) {
      return $active_ctx->inverse;
    }

    $inverse = $active_ctx->inverse = new stdClass();

    // variables for building fast CURIE map
    $fast_curie_map = $active_ctx->fast_curie_map = new ArrayObject();
    $iris_to_terms = array();

    // handle default language
    $default_language = '@none';
    if(property_exists($active_ctx, '@language')) {
      $default_language = $active_ctx->{'@language'};
    }

    // create term selections for each mapping in the context, ordered by
    // shortest and then lexicographically least
    $mappings = $active_ctx->mappings;
    $terms = array_keys((array)$mappings);
    usort($terms, array($this, '_compareShortestLeast'));
    foreach($terms as $term) {
      $mapping = $mappings->{$term};
      if($mapping === null) {
        continue;
      }

      // add term selection where it applies
      if(property_exists($mapping, '@container')) {
        $container = $mapping->{'@container'};
      } else {
        $container = '@none';
      }

      // iterate over every IRI in the mapping
      $iris = $mapping->{'@id'};
      $iris = self::arrayify($iris);
      foreach($iris as $iri) {
        $is_keyword = self::_isKeyword($iri);

        // initialize container map
        if(!property_exists($inverse, $iri)) {
          $inverse->{$iri} = new stdClass();
          if(!$is_keyword && !$mapping->_term_has_colon) {
            // init IRI to term map and fast CURIE map
            $iris_to_terms[$iri] = new ArrayObject();
            $iris_to_terms[$iri][] = $term;
            $fast_curie_entry = (object)array(
              'iri' => $iri, 'terms' => $iris_to_terms[$iri]);
            if(!array_key_exists($iri[0], (array)$fast_curie_map)) {
              $fast_curie_map[$iri[0]] = new ArrayObject();
            }
            $fast_curie_map[$iri[0]][] = $fast_curie_entry;
          }
        } else if(!$is_keyword && !$mapping->_term_has_colon) {
          // add IRI to term match
          $iris_to_terms[$iri][] = $term;
        }
        $container_map = $inverse->{$iri};

        // add new entry
        if(!property_exists($container_map, $container)) {
          $container_map->{$container} = (object)array(
            '@language' => new stdClass(),
            '@type' => new stdClass());
        }
        $entry = $container_map->{$container};

        if($mapping->reverse) {
          // term is preferred for values using @reverse
          $this->_addPreferredTerm(
            $mapping, $term, $entry->{'@type'}, '@reverse');
        } else if(property_exists($mapping, '@type')) {
          // term is preferred for values using specific type
          $this->_addPreferredTerm(
            $mapping, $term, $entry->{'@type'}, $mapping->{'@type'});
        } else if(property_exists($mapping, '@language')) {
          // term is preferred for values using specific language
          $language = $mapping->{'@language'};
          if($language === null) {
            $language = '@null';
          }
          $this->_addPreferredTerm(
            $mapping, $term, $entry->{'@language'}, $language);
        } else {
          // term is preferred for values w/default language or no type and
          // no language
          // add an entry for the default language
          $this->_addPreferredTerm(
            $mapping, $term, $entry->{'@language'}, $default_language);

          // add entries for no type and no language
          $this->_addPreferredTerm(
            $mapping, $term, $entry->{'@type'}, '@none');
          $this->_addPreferredTerm(
            $mapping, $term, $entry->{'@language'}, '@none');
        }
      }
    }

    // build fast CURIE map
    foreach($fast_curie_map as $key => $value) {
      $this->_buildIriMap($fast_curie_map, $key, 1);
    }

    return $inverse;
  }

  /**
   * Runs a recursive algorithm to build a lookup map for quickly finding
   * potential CURIEs.
   *
   * @param ArrayObject $iri_map the map to build.
   * @param string $key the current key in the map to work on.
   * @param int $idx the index into the IRI to compare.
   */
  function _buildIriMap($iri_map, $key, $idx) {
    $entries = $iri_map[$key];
    $next = $iri_map[$key] = new ArrayObject();

    foreach($entries as $entry) {
      $iri = $entry->iri;
      if($idx >= strlen($iri)) {
        $letter = '';
      } else {
        $letter = $iri[$idx];
      }
      if(!isset($next[$letter])) {
        $next[$letter] = new ArrayObject();
      }
      $next[$letter][] = $entry;
    }

    foreach($next as $key => $value) {
      if($key === '') {
        continue;
      }
      $this->_buildIriMap($next, $key, $idx + 1);
    }
  }

  /**
   * Adds the term for the given entry if not already added.
   *
   * @param stdClass $mapping the term mapping.
   * @param string $term the term to add.
   * @param stdClass $entry the inverse context type_or_language entry to
   *          add to.
   * @param string $type_or_language_value the key in the entry to add to.
   */
  function _addPreferredTerm($mapping, $term, $entry, $type_or_language_value) {
    if(!property_exists($entry, $type_or_language_value)) {
      $entry->{$type_or_language_value} = $term;
    }
  }

  /**
   * Clones an active context, creating a child active context.
   *
   * @return stdClass a clone (child) of the active context.
   */
  protected function _cloneActiveContext($active_ctx) {
    $child = new stdClass();
    $child->{'@base'} = $active_ctx->{'@base'};
    $child->mappings = self::copy($active_ctx->mappings);
    $child->inverse = null;
    if(property_exists($active_ctx, '@language')) {
      $child->{'@language'} = $active_ctx->{'@language'};
    }
    if(property_exists($active_ctx, '@vocab')) {
      $child->{'@vocab'} = $active_ctx->{'@vocab'};
    }
    return $child;
  }

  /**
   * Returns whether or not the given value is a keyword.
   *
   * @param string $v the value to check.
   *
   * @return bool true if the value is a keyword, false if not.
   */
  protected static function _isKeyword($v) {
    if(!is_string($v)) {
      return false;
    }
    switch($v) {
    case '@base':
    case '@context':
    case '@container':
    case '@default':
    case '@embed':
    case '@explicit':
    case '@graph':
    case '@id':
    case '@index':
    case '@language':
    case '@list':
    case '@omitDefault':
    case '@preserve':
    case '@requireAll':
    case '@reverse':
    case '@set':
    case '@type':
    case '@value':
    case '@vocab':
      return true;
    }
    return false;
  }

  /**
   * Returns true if the given value is an empty Object.
   *
   * @param mixed $v the value to check.
   *
   * @return bool true if the value is an empty Object, false if not.
   */
  protected static function _isEmptyObject($v) {
    return is_object($v) && count(get_object_vars($v)) === 0;
  }

  /**
   * Throws an exception if the given value is not a valid @type value.
   *
   * @param mixed $v the value to check.
   */
  protected static function _validateTypeValue($v) {
    // must be a string or empty object
    if(is_string($v) || self::_isEmptyObject($v)) {
      return;
    }

    // must be an array
    $is_valid = false;
    if(is_array($v)) {
      // must contain only strings
      $is_valid = true;
      foreach($v as $e) {
        if(!(is_string($e))) {
          $is_valid = false;
          break;
        }
      }
    }

    if(!$is_valid) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; "@type" value must a string, an array ' .
        'of strings, or an empty object.',
        'jsonld.SyntaxError', 'invalid type value', array('value' => $v));
    }
  }

  /**
   * Returns true if the given value is a subject with properties.
   *
   * @param mixed $v the value to check.
   *
   * @return bool true if the value is a subject with properties, false if not.
   */
  protected static function _isSubject($v) {
    // Note: A value is a subject if all of these hold true:
    // 1. It is an Object.
    // 2. It is not a @value, @set, or @list.
    // 3. It has more than 1 key OR any existing key is not @id.
    $rval = false;
    if(is_object($v) &&
      !property_exists($v, '@value') &&
      !property_exists($v, '@set') &&
      !property_exists($v, '@list')) {
      $count = count(get_object_vars($v));
      $rval = ($count > 1 || !property_exists($v, '@id'));
    }
    return $rval;
  }

  /**
   * Returns true if the given value is a subject reference.
   *
   * @param mixed $v the value to check.
   *
   * @return bool true if the value is a subject reference, false if not.
   */
  protected static function _isSubjectReference($v) {
    // Note: A value is a subject reference if all of these hold true:
    // 1. It is an Object.
    // 2. It has a single key: @id.
    return (is_object($v) && count(get_object_vars($v)) === 1 &&
      property_exists($v, '@id'));
  }

  /**
   * Returns true if the given value is a @value.
   *
   * @param mixed $v the value to check.
   *
   * @return bool true if the value is a @value, false if not.
   */
  protected static function _isValue($v) {
    // Note: A value is a @value if all of these hold true:
    // 1. It is an Object.
    // 2. It has the @value property.
    return is_object($v) && property_exists($v, '@value');
  }

  /**
   * Returns true if the given value is a @list.
   *
   * @param mixed $v the value to check.
   *
   * @return bool true if the value is a @list, false if not.
   */
  protected static function _isList($v) {
    // Note: A value is a @list if all of these hold true:
    // 1. It is an Object.
    // 2. It has the @list property.
    return is_object($v) && property_exists($v, '@list');
  }

  /**
   * Returns true if the given value is a blank node.
   *
   * @param mixed $v the value to check.
   *
   * @return bool true if the value is a blank node, false if not.
   */
  protected static function _isBlankNode($v) {
    // Note: A value is a blank node if all of these hold true:
    // 1. It is an Object.
    // 2. If it has an @id key its value begins with '_:'.
    // 3. It has no keys OR is not a @value, @set, or @list.
    $rval = false;
    if(is_object($v)) {
      if(property_exists($v, '@id')) {
        $rval = (strpos($v->{'@id'}, '_:') === 0);
      } else {
        $rval = (count(get_object_vars($v)) === 0 ||
          !(property_exists($v, '@value') ||
            property_exists($v, '@set') ||
            property_exists($v, '@list')));
      }
    }
    return $rval;
  }

  /**
   * Returns true if the given value is an absolute IRI, false if not.
   *
   * @param string $v the value to check.
   *
   * @return bool true if the value is an absolute IRI, false if not.
   */
  protected static function _isAbsoluteIri($v) {
    return strpos($v, ':') !== false;
  }

  /**
   * Returns true if the given target has the given key and its
   * value equals is the given value.
   *
   * @param stdClass $target the target object.
   * @param string key the key to check.
   * @param mixed $value the value to check.
   *
   * @return bool true if the target has the given key and its value matches.
   */
  protected static function _hasKeyValue($target, $key, $value) {
    return (property_exists($target, $key) && $target->{$key} === $value);
  }

  /**
   * Returns true if both of the given objects have the same value for the
   * given key or if neither of the objects contain the given key.
   *
   * @param stdClass $o1 the first object.
   * @param stdClass $o2 the second object.
   * @param string key the key to check.
   *
   * @return bool true if both objects have the same value for the key or
   *   neither has the key.
   */
  protected static function _compareKeyValues($o1, $o2, $key) {
    if(property_exists($o1, $key)) {
      return property_exists($o2, $key) && $o1->{$key} === $o2->{$key};
    }
    return !property_exists($o2, $key);
  }

  /**
   * Parses JSON and sets an appropriate exception message on error.
   *
   * @param string $json the JSON to parse.
   *
   * @return mixed the parsed JSON object or array.
   */
  protected static function _parse_json($json) {
    $rval = json_decode($json);
    $error = json_last_error();
    if($error === JSON_ERROR_NONE && $rval === null) {
      $error = JSON_ERROR_SYNTAX;
    }
    switch($error) {
    case JSON_ERROR_NONE:
      break;
    case JSON_ERROR_DEPTH:
      throw new JsonLdException(
        'Could not parse JSON; the maximum stack depth has been exceeded.',
        'jsonld.ParseError');
    case JSON_ERROR_STATE_MISMATCH:
      throw new JsonLdException(
        'Could not parse JSON; invalid or malformed JSON.',
        'jsonld.ParseError');
    case JSON_ERROR_CTRL_CHAR:
    case JSON_ERROR_SYNTAX:
      throw new JsonLdException(
        'Could not parse JSON; syntax error, malformed JSON.',
        'jsonld.ParseError');
    case JSON_ERROR_UTF8:
      throw new JsonLdException(
        'Could not parse JSON from URL; malformed UTF-8 characters.',
        'jsonld.ParseError');
    default:
      throw new JsonLdException(
        'Could not parse JSON from URL; unknown error.',
        'jsonld.ParseError');
    }
    return $rval;
  }
}

// register the N-Quads RDF parser
jsonld_register_rdf_parser(
  'application/nquads', array('JsonLdProcessor', 'parseNQuads'));

/**
 * A JSON-LD Exception.
 */
class JsonLdException extends Exception {
  public function __construct(
    $msg, $type, $code='error', $details=null, $previous=null) {
    $this->type = $type;
    $this->code = $code;
    $this->details = $details;
    $this->cause = $previous;
    parent::__construct($msg, 0, $previous);
  }
  public function __toString() {
    $rval = __CLASS__ . ": [{$this->type}]: {$this->message}\n";
    if($this->code) {
      $rval .= 'Code: ' . $this->code . "\n";
    }
    if($this->details) {
      $rval .= 'Details: ' . print_r($this->details, true) . "\n";
    }
    if($this->cause) {
      $rval .= 'Cause: ' . $this->cause;
    }
    $rval .= $this->getTraceAsString() . "\n";
    return $rval;
  }
};

/**
 * A UniqueNamer issues unique names, keeping track of any previously issued
 * names.
 */
class UniqueNamer {
  /**
   * Constructs a new UniqueNamer.
   *
   * @param prefix the prefix to use ('<prefix><counter>').
   */
  public function __construct($prefix) {
    $this->prefix = $prefix;
    $this->counter = 0;
    $this->existing = new stdClass();
    $this->order = array();
  }

  /**
   * Clones this UniqueNamer.
   */
  public function __clone() {
    $this->existing = clone $this->existing;
  }

  /**
   * Gets the new name for the given old name, where if no old name is given
   * a new name will be generated.
   *
   * @param mixed [$old_name] the old name to get the new name for.
   *
   * @return string the new name.
   */
  public function getName($old_name=null) {
    // return existing old name
    if($old_name && property_exists($this->existing, $old_name)) {
      return $this->existing->{$old_name};
    }

    // get next name
    $name = $this->prefix . $this->counter;
    $this->counter += 1;

    // save mapping
    if($old_name !== null) {
      $this->existing->{$old_name} = $name;
      $this->order[] = $old_name;
    }

    return $name;
  }

  /**
   * Returns true if the given old name has already been assigned a new name.
   *
   * @param string $old_name the old name to check.
   *
   * @return true if the old name has been assigned a new name, false if not.
   */
  public function isNamed($old_name) {
    return property_exists($this->existing, $old_name);
  }
}

/**
 * A Permutator iterates over all possible permutations of the given array
 * of elements.
 */
class Permutator {
  /**
   * Constructs a new Permutator.
   *
   * @param array $list the array of elements to iterate over.
   */
  public function __construct($list) {
    // original array
    $this->list = $list;
    sort($this->list);
    // indicates whether there are more permutations
    $this->done = false;
    // directional info for permutation algorithm
    $this->left = new stdClass();
    foreach($list as $v) {
      $this->left->{$v} = true;
    }
  }

  /**
   * Returns true if there is another permutation.
   *
   * @return bool true if there is another permutation, false if not.
   */
  public function hasNext() {
    return !$this->done;
  }

  /**
   * Gets the next permutation. Call hasNext() to ensure there is another one
   * first.
   *
   * @return array the next permutation.
   */
  public function next() {
    // copy current permutation
    $rval = $this->list;

    /* Calculate the next permutation using the Steinhaus-Johnson-Trotter
     permutation algorithm. */

    // get largest mobile element k
    // (mobile: element is greater than the one it is looking at)
    $k = null;
    $pos = 0;
    $length = count($this->list);
    for($i = 0; $i < $length; ++$i) {
      $element = $this->list[$i];
      $left = $this->left->{$element};
      if(($k === null || $element > $k) &&
        (($left && $i > 0 && $element > $this->list[$i - 1]) ||
        (!$left && $i < ($length - 1) && $element > $this->list[$i + 1]))) {
        $k = $element;
        $pos = $i;
      }
    }

    // no more permutations
    if($k === null) {
      $this->done = true;
    } else {
      // swap k and the element it is looking at
      $swap = $this->left->{$k} ? $pos - 1 : $pos + 1;
      $this->list[$pos] = $this->list[$swap];
      $this->list[$swap] = $k;

      // reverse the direction of all elements larger than k
      for($i = 0; $i < $length; ++$i) {
        if($this->list[$i] > $k) {
          $this->left->{$this->list[$i]} = !$this->left->{$this->list[$i]};
        }
      }
    }

    return $rval;
  }
}

/**
 * An ActiveContextCache caches active contexts so they can be reused without
 * the overhead of recomputing them.
 */
class ActiveContextCache {
  /**
   * Constructs a new ActiveContextCache.
   *
   * @param int size the maximum size of the cache, defaults to 100.
   */
  public function __construct($size=100) {
    $this->order = array();
    $this->cache = new stdClass();
    $this->size = $size;
  }

  /**
   * Gets an active context from the cache based on the current active
   * context and the new local context.
   *
   * @param stdClass $active_ctx the current active context.
   * @param stdClass $local_ctx the new local context.
   *
   * @return mixed a shared copy of the cached active context or null.
   */
  public function get($active_ctx, $local_ctx) {
    $key1 = serialize($active_ctx);
    $key2 = serialize($local_ctx);
    if(property_exists($this->cache, $key1)) {
      $level1 = $this->cache->{$key1};
      if(property_exists($level1, $key2)) {
        return $level1->{$key2};
      }
    }
    return null;
  }

  /**
   * Sets an active context in the cache based on the previous active
   * context and the just-processed local context.
   *
   * @param stdClass $active_ctx the previous active context.
   * @param stdClass $local_ctx the just-processed local context.
   * @param stdClass $result the resulting active context.
   */
  public function set($active_ctx, $local_ctx, $result) {
    if(count($this->order) === $this->size) {
      $entry = array_shift($this->order);
      unset($this->cache->{$entry->activeCtx}->{$entry->localCtx});
    }
    $key1 = serialize($active_ctx);
    $key2 = serialize($local_ctx);
    $this->order[] = (object)array(
      'activeCtx' => $key1, 'localCtx' => $key2);
    if(!property_exists($this->cache, $key1)) {
      $this->cache->{$key1} = new stdClass();
    }
    $this->cache->{$key1}->{$key2} = JsonLdProcessor::copy($result);
  }
}

/* end of file, omit ?> */
