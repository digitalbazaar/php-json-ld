<?php
/**
 * PHP implementation of the JSON-LD API.
 * Version: 0.0.2
 *
 * @author Dave Longley
 *
 * BSD 3-Clause License
 * Copyright (c) 2011-2013 Digital Bazaar, Inc.
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
 *          [strict] use strict mode (default: true).
 *          [optimize] true to optimize the compaction (default: false).
 *          [graph] true to always output a top-level graph (default: false).
 *          [resolver(url)] the URL resolver to use.
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
 *          [resolver(url)] the URL resolver to use.
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
 *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
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
 *          [omitDefault] default @omitDefault flag (default: false).
 *          [optimize] optimize when compacting (default: false).
 *          [resolver(url)] the URL resolver to use.
 *
 * @return stdClass the framed JSON-LD output.
 */
function jsonld_frame($input, $frame, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->frame($input, $frame, $options);
}

/**
 * Performs RDF normalization on the given JSON-LD input.
 *
 * @param mixed $input the JSON-LD object to normalize.
 * @param assoc [$options] the options to use:
 *          [base] the base IRI to use.
 *          [format] the format if output is a string:
 *            'application/nquads' for N-Quads (default).
 *          [resolver(url)] the URL resolver to use.
 *
 * @return array the normalized output.
 */
function jsonld_normalize($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->normalize($input, $options);
}

/**
 * Converts RDF statements into JSON-LD.
 *
 * @param mixed $statements a serialized string of RDF statements in a format
 *          specified by the format option or an array of the RDF statements
 *          to convert.
 * @param assoc [$options] the options to use:
 *          [format] the format if input not an array:
 *            'application/nquads' for N-Quads (default).
 *          [useRdfType] true to use rdf:type, false to use @type
 *            (default: false).
 *          [useNativeTypes] true to convert XSD types into native types
 *            (boolean, integer, double), false not to (default: true).
 *
 * @return array the JSON-LD output.
 */
function jsonld_from_rdf($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->fromRDF($input, $options);
}

/**
 * Outputs the RDF statements found in the given JSON-LD object.
 *
 * @param mixed $input the JSON-LD object.
 * @param assoc [$options] the options to use:
 *          [base] the base IRI to use.
 *          [format] the format to use to output a string:
 *            'application/nquads' for N-Quads (default).
 *          [resolver(url)] the URL resolver to use.
 *
 * @return array all RDF statements in the JSON-LD object.
 */
function jsonld_to_rdf($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->toRDF($input, $options);
}

/** JSON-LD shared in-memory cache. */
global $jsonld_cache;
$jsonld_cache = new stdClass();

/** The default active context cache. */
// FIXME: turn on
//$jsonld_cache->activeCtx = new ActiveContextCache();

/** The default JSON-LD URL resolver. */
global $jsonld_default_url_resolver;
$jsonld_default_url_resolver = null;

/**
 * Sets the default JSON-LD URL resolver.
 *
 * @param callable resolver(url) the URL resolver to use.
 */
function jsonld_set_url_resolver($resolver) {
  global $jsonld_default_url_resolver;
  $jsonld_default_url_resolver = $resolver;
}

/**
 * Retrieves JSON-LD at the given URL.
 *
 * @param string $url the URL to to resolve.
 *
 * @return the JSON-LD.
 */
function jsonld_resolve_url($url) {
  global $jsonld_default_url_resolver;
  if($jsonld_default_url_resolver !== null) {
    return call_user_func($jsonld_default_url_resolver, $url);
  }

  // default JSON-LD GET implementation
  return jsonld_default_resolve_url($url);
}

/**
 * The default implementation to retrieve JSON-LD at the given URL.
 *
 * @param string $url the URL to to resolve.
 *
 * @return the JSON-LD.
 */
function jsonld_default_resolve_url($url) {
  // default JSON-LD GET implementation
  $opts = array('http' =>
    array(
      'method' => "GET",
      'header' =>
      "Accept: application/ld+json\r\n" .
      "User-Agent: PaySwarm PHP Client/1.0\r\n"));
  $stream = stream_context_create($opts);
  $result = @file_get_contents($url, false, $stream);
  if($result === false) {
    throw new Exception("Could not GET url: '$url'");
  }
  return $result;
}

/** Registered global RDF Statement parsers hashed by content-type. */
global $jsonld_rdf_parsers;
$jsonld_rdf_parsers = new stdClass();

/**
 * Registers a global RDF Statement parser by content-type, for use with
 * jsonld_from_rdf. Global parsers will be used by JsonLdProcessors that do
 * not register their own parsers.
 *
 * @param string $content_type the content-type for the parser.
 * @param callable $parser(input) the parser function (takes a string as
 *           a parameter and returns an array of RDF statements).
 */
function jsonld_register_rdf_parser($content_type, $parser) {
  global $jsonld_rdf_parsers;
  $jsonld_rdf_parsers->{$content_type} = $parser;
}

/**
 * Unregisters a global RDF Statement parser by content-type.
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
  $rval = parse_url($url);
  if(isset($rval['host'])) {
    if(!isset($rval['path']) || $rval['path'] === '') {
      $rval['path'] = '/';
    }
  }
  else if(!isset($rval['path'])) {
    $rval['path'] = '';
  }
  return $rval;
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
  if(is_string($base)) {
    $base = jsonld_parse_url($base);
  }
  $authority = $base['host'];
  $rel = jsonld_parse_url($iri);

  // per RFC3986 normalize slashes and dots in path

  // IRI contains authority
  if(strpos($iri, '//') === 0) {
    $path = substr($iri, 2);
    $authority = substr($path, 0, strrpos($path, '/'));
    $path = substr($path, strlen($authority));
  }
  // IRI represents an absolute path
  else if(strpos($rel['path'], '/') === 0) {
    $path = $rel['path'];
  }
  else {
    $path = $base['path'];

    // prepend last directory for base
    if($rel['path'] !== '') {
      $idx = strrpos($path, '/');
      $idx = ($idx === false) ? 0 : $idx + 1;
      $path = substr($path, 0, $idx) . $rel['path'];
    }
  }

  $segments = explode('/', $path);

  // remove '.' and '' (do not remove trailing empty path)
  $idx = -1;
  $end = count($segments) - 1;
  $filter = function($e) use (&$idx, $end) {
    $idx += 1;
    return $e !== '.' && ($e !== '' || $idx === $end);
  };
  $segments = array_values(array_filter($segments, $filter));

  // remove as many '..' as possible
  for($i = 0; $i < count($segments);) {
    $segment = $segments[$i];
    if($segment === '..') {
      // too many reverse dots
      if($i === 0) {
        $last = $segments[count($segments) - 1];
        if($last !== '..') {
          $segments = array($last);
        }
        else {
          $segments = array();
        }
        break;
      }

      // remove '..' and previous segment
      array_splice($segments, $i - 1, 2);
      $segments = array_values($segments);
      $i -= 1;
    }
    else {
      $i += 1;
    }
  }

  $path = '/' . implode('/', $segments);

  // add query and hash
  if(isset($rel['query'])) {
    $path .= '?' . $rel['query'];
  }
  if(isset($rel['fragment'])) {
    $path .= '#' . $rel['fragment'];
  }

  return $base['scheme'] . "://$authority$path";
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
  const RDF_FIRST = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#first';
  const RDF_REST = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#rest';
  const RDF_NIL = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil';
  const RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

  /** Restraints */
  const MAX_CONTEXT_URLS = 10;

  /** Processor-specific RDF Statement parsers. */
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
   *          [skipExpansion] true to assume the input is expanded and skip
   *            expansion, false not to, defaults to false.
   *          [activeCtx] true to also return the active context used.
   *          [resolver(url)] the URL resolver to use.
   *
   * @return mixed the compacted JSON-LD output.
   */
  public function compact($input, $ctx, $options) {
    // nothing to compact
    if($input === null) {
      return null;
    }

    // set default options
    isset($options['base']) or $options['base'] = '';
    isset($options['renameBlankNodes']) or $options['renameBlankNodes'] =
      true;
    isset($options['strict']) or $options['strict'] = true;
    isset($options['optimize']) or $options['optimize'] = false;
    isset($options['graph']) or $options['graph'] = false;
    isset($options['skipExpansion']) or $options['skipExpansion'] = false;
    isset($options['activeCtx']) or $options['activeCtx'] = false;
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    if($options['skipExpansion'] === true) {
      $expanded = $input;
    }
    else {
      // expand input
      try {
        $expanded = $this->expand($input, $options);
      }
      catch(JsonLdException $e) {
        throw new JsonLdException(
          'Could not expand input before compaction.',
          'jsonld.CompactError', null, $e);
      }
    }

    // process context
    $active_ctx = $this->_getInitialContext($options);
    try {
      $active_ctx = $this->processContext($active_ctx, $ctx, $options);
    }
    catch(JsonLdException $e) {
      throw new JsonLdException(
        'Could not process context before compaction.',
        'jsonld.CompactError', null, $e);
    }

    // do compaction
    $compacted = $this->_compact($active_ctx, null, $expanded, $options);

    // if compacted is an array with 1 entry, remove array unless
    // graph option is set
    if($options['graph'] !== true &&
      is_array($compacted) && count($compacted) === 1) {
      $compacted = $compacted[0];
    }
    // always use array if graph option is on
    else if($options['graph'] === true) {
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

    // add context
    if($has_context || $options['graph']) {
      if(is_array($compacted)) {
        // use '@graph' keyword
        $kwgraph = $this->_compactIri($active_ctx, '@graph');
        $graph = $compacted;
        $compacted = new stdClass();
        if($has_context) {
          $compacted->{'@context'} = $ctx;
        }
        $compacted->{$kwgraph} = $graph;
      }
      else if(is_object($compacted)) {
        // reorder keys so @context is first
        $graph = $compacted;
        $compacted = new stdClass();
        $compacted->{'@context'} = $ctx;
        foreach($graph as $k => $v) {
          $compacted->{$k} = $v;
        }
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
   *          [renameBlankNodes] true to rename blank nodes, false not to,
   *            defaults to true.
   *          [keepFreeFloatingNodes] true to keep free-floating nodes,
   *            false not to, defaults to false.
   *          [resolver(url)] the URL resolver to use.
   *
   * @return array the expanded JSON-LD output.
   */
  public function expand($input, $options) {
    // set default options
    isset($options['base']) or $options['base'] = '';
    isset($options['renameBlankNodes']) or $options['renameBlankNodes'] =
      true;
    isset($options['keepFreeFloatingNodes']) or
      $options['keepFreeFloatingNodes'] = false;
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    // resolve all @context URLs in the input
    $input = self::copy($input);
    try {
      $this->_resolveContextUrls(
        $input, new stdClass(), $options['resolver'], $options['base']);
    }
    catch(Exception $e) {
      throw new JsonLdException(
        'Could not perform JSON-LD expansion.',
        'jsonld.ExpandError', null, $e);
    }

    // do expansion
    $active_ctx = $this->_getInitialContext($options);
    $expanded = $this->_expand($active_ctx, null, $input, $options, false);

    // optimize away @graph with no other properties
    if(is_object($expanded) && property_exists($expanded, '@graph') &&
      count(array_keys((array)$expanded)) === 1) {
      $expanded = $expanded->{'@graph'};
    }
    else if($expanded === null) {
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
   *          [resolver(url)] the URL resolver to use.
   *
   * @return array the flattened output.
   */
  public function flatten($input, $ctx, $options) {
    // set default options
    isset($options['base']) or $options['base'] = '';
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    try {
      // expand input
      $expanded = $this->expand($input, $options);
    }
    catch(Exception $e) {
      throw new JsonLdException(
        'Could not expand input before flattening.',
        'jsonld.FlattenError', null, $e);
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
    }
    catch(Exception $e) {
      throw new JsonLdException(
        'Could not compact flattened output.',
        'jsonld.FlattenError', null, $e);
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
   *          [embed] default @embed flag (default: true).
   *          [explicit] default @explicit flag (default: false).
   *          [omitDefault] default @omitDefault flag (default: false).
   *          [optimize] optimize when compacting (default: false).
   *          [resolver(url)] the URL resolver to use.
   *
   * @return stdClass the framed JSON-LD output.
   */
  public function frame($input, $frame, $options) {
    // set default options
    isset($options['base']) or $options['base'] = '';
    isset($options['embed']) or $options['embed'] = true;
    isset($options['explicit']) or $options['explicit'] = false;
    isset($options['omitDefault']) or $options['omitDefault'] = false;
    isset($options['optimize']) or $options['optimize'] = false;
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    // preserve frame context
    $ctx = (property_exists($frame, '@context') ?
      $frame->{'@context'} : new stdClass());

    try {
      // expand input
      $expanded = $this->expand($input, $options);
    }
    catch(Exception $e) {
      throw new JsonLdException(
        'Could not expand input before framing.',
        'jsonld.FrameError', null, $e);
    }

    try {
      // expand frame
      $opts = self::copy($options);
      $opts['keepFreeFloatingNodes'] = true;
      $expanded_frame = $this->expand($frame, $opts);
    }
    catch(Exception $e) {
      throw new JsonLdException(
        'Could not expand frame before framing.',
        'jsonld.FrameError', null, $e);
    }

    // do framing
    $framed = $this->_frame($expanded, $expanded_frame, $options);

    try {
      // compact result (force @graph option to true)
      $options['graph'] = true;
      $options['skipExpansion'] = true;
      $options['activeCtx'] = true;
      $result = $this->compact($framed, $ctx, $options);
    }
    catch(Exception $e) {
      throw new JsonLdException(
        'Could not compact framed output.',
        'jsonld.FrameError', null, $e);
    }

    $compacted = $result['compacted'];
    $active_ctx = $result['activeCtx'];

    // get graph alias
    $graph = $this->_compactIri($active_ctx, '@graph');
    // remove @preserve from results
    $compacted->{$graph} = $this->_removePreserve(
      $active_ctx, $compacted->{$graph});
    return $compacted;
  }

  /**
   * Performs JSON-LD normalization.
   *
   * @param mixed $input the JSON-LD object to normalize.
   * @param assoc $options the options to use:
   *          [base] the base IRI to use.
   *          [resolver(url)] the URL resolver to use.
   *
   * @return array the JSON-LD normalized output.
   */
  public function normalize($input, $options) {
    // set default options
    isset($options['base']) or $options['base'] = '';
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    try {
      // expand input then do normalization
      $expanded = $this->expand($input, $options);
    }
    catch(Exception $e) {
      throw new JsonLdException(
        'Could not expand input before normalization.',
        'jsonld.NormalizeError', null, $e);
    }

    // do normalization
    return $this->_normalize($expanded, $options);
  }

  /**
   * Converts RDF statements into JSON-LD.
   *
   * @param mixed $statements a serialized string of RDF statements in a format
   *          specified by the format option or an array of the RDF statements
   *          to convert.
   * @param assoc $options the options to use:
   *          [format] the format if input is a string:
   *            'application/nquads' for N-Quads (default).
   *          [useRdfType] true to use rdf:type, false to use @type
   *            (default: false).
   *          [useNativeTypes] true to convert XSD types into native types
   *            (boolean, integer, double), false not to (default: true).
   *
   * @return array the JSON-LD output.
   */
  public function fromRDF($statements, $options) {
    global $jsonld_rdf_parsers;

    // set default options
    isset($options['format']) or $options['format'] = 'application/nquads';
    isset($options['useRdfType']) or $options['useRdfType'] = false;
    isset($options['useNativeTypes']) or $options['useNativeTypes'] = true;

    if(!is_array($statements)) {
      // supported formats (processor-specific and global)
      if(($this->rdfParsers !== null &&
        !property_exists($this->rdfParsers, $options['format'])) ||
        $this->rdfParsers === null &&
        !property_exists($jsonld_rdf_parsers, $options['format'])) {
        throw new JsonLdException(
          'Unknown input format.',
          'jsonld.UnknownFormat', array('format' => $options['format']));
      }
      if($this->rdfParsers !== null) {
        $callable = $this->rdfParsers->{$options['format']};
      }
      else {
        $callable = $jsonld_rdf_parsers->{$options['format']};
      }
      $statements = call_user_func($callable, $statements);
    }

    // convert from RDF
    return $this->_fromRDF($statements, $options);
  }

  /**
   * Outputs the RDF statements found in the given JSON-LD object.
   *
   * @param mixed $input the JSON-LD object.
   * @param assoc $options the options to use:
   *          [base] the base IRI to use.
   *          [format] the format to use to output a string:
   *            'application/nquads' for N-Quads (default).
   *          [resolver(url)] the URL resolver to use.
   *
   * @return array all RDF statements in the JSON-LD object.
   */
  public function toRDF($input, $options) {
    // set default options
    isset($options['base']) or $options['base'] = '';
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    try {
      // expand input
      $expanded = $this->expand($input, $options);
    }
    catch(JsonLdException $e) {
      throw new JsonLdException(
        'Could not expand input before conversion to RDF.',
        'jsonld.RdfError', $e);
    }

    // get RDF statements
    $namer = new UniqueNamer('_:t');
    $statements = array();
    $this->_toRDF($expanded, $namer, null, null, null, $statements);

    // convert to output format
    if(isset($options['format'])) {
      // supported formats
      if($options['format'] === 'application/nquads') {
        $nquads = array();
        foreach($statements as $statement) {
          $nquads[] = $this->toNQuad($statement);
        }
        sort($nquads);
        $statements = implode($nquads);
      }
      else {
        throw new JsonLdException(
          'Unknown output format.',
          'jsonld.UnknownFormat', array('format' => $options['format']));
      }
    }

    // output RDF statements
    return $statements;
  }

  /**
   * Processes a local context, resolving any URLs as necessary, and returns a
   * new active context in its callback.
   *
   * @param stdClass $active_ctx the current active context.
   * @param mixed $local_ctx the local context to process.
   * @param assoc $options the options to use:
   *          [renameBlankNodes] true to rename blank nodes, false not to,
   *            defaults to true.
   *          [resolver(url)] the URL resolver to use.
   *
   * @return stdClass the new active context.
   */
  public function processContext($active_ctx, $local_ctx, $options) {
    // set default options
    isset($options['base']) or $options['base'] = '';
    isset($options['renameBlankNodes']) or $options['renameBlankNodes'] =
      true;
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    // return initial context early for null context
    if($local_ctx === null) {
      return $this->_getInitialContext($options);
    }

    // resolve URLs in local_ctx
    $ctx = self::copy($local_ctx);
    if(is_object($ctx) && !property_exists($ctx, '@context')) {
      $ctx = (object)array('@context' => $ctx);
    }
    try {
      $this->_resolveContextUrls(
        $ctx, new stdClass(), $options['resolver'], $options['base']);
    }
    catch(Exception $e) {
      throw new JsonLdException(
        'Could not process JSON-LD context.',
        'jsonld.ContextError', null, $e);
    }

    // process context
    return $this->_processContext($active_ctx, $ctx, $options);
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
      }
      // avoid matching the set of values with an array value parameter
      else if(!is_array($value)) {
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
    isset($options['allowDuplicate']) or $options['allowDuplicate'] = true;
    isset($options['propertyIsArray']) or $options['propertyIsArray'] = false;

    if(is_array($value)) {
      if(count($value) === 0 && $options['propertyIsArray'] &&
        !property_exists($subject, $property)) {
        $subject->{$property} = array();
      }
      foreach($value as $v) {
        self::addValue($subject, $property, $v, $options);
      }
    }
    else if(property_exists($subject, $property)) {
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
    }
    else {
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
    isset($options['propertyIsArray']) or $options['propertyIsArray'] = false;

    // filter out value
    $filter = function($e) use ($value) {
      return !self::compareValues($e, $value);
    };
    $values = self::getValues($subject, $property);
    $values = array_values(array_filter($values, $filter));

    if(count($values) === 0) {
      self::removeProperty($subject, $property);
    }
    else if(count($values) === 1 && !$options['property_is_array']) {
      $subject->{$property} = $values[0];
    }
    else {
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
   * 3. They are both @lists with the same @list and @index, OR
   * 4. They both have @ids they are the same.
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
    if(self::_isValue($v1) || self::_isValue($v2)) {
      return (
        self::_compareKeyValues($v1, $v2, '@value') &&
        self::_compareKeyValues($v1, $v2, '@type') &&
        self::_compareKeyValues($v1, $v2, '@language') &&
        self::_compareKeyValues($v1, $v2, '@index'));
    }

    // 3. equal @lists
    if(self::_isList($v1) && self::_isList($v2)) {
      if(!self::_compareKeyValues($v1, $v2, '@index')) {
        return false;
      }
      $list1 = $v1->{'@list'};
      $list2 = $v2->{'@list'};
      $count_list1 = count($list1);
      $count_list2 = count($list2);
      if($count_list1 !== $count_list2) {
        return false;
      }
      for($i = 0; $i < $count_list1; ++$i) {
        if(!self::compareValues($list1[$i], $list2[$i])) {
          return false;
        }
      }
      return true;
    }

    // 4. equal @ids
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

      // return whole entry
      if($type === null) {
        $rval = $entry;
      }
      // return entry value for type
      else if(property_exists($entry, $type)) {
        $rval = $entry->{$type};
      }
    }

    return $rval;
  }

  /**
   * Parses statements in the form of N-Quads.
   *
   * @param string $input the N-Quads input to parse.
   *
   * @return array the resulting RDF statements.
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
    $graph = "(?:\\.|(?:(?:$iri|$bnode)$ws*\\.))";

    // full quad regex
    $quad = "/^$ws*$subject$property$object$graph$ws*$/";

    // build RDF statements
    $statements = array();

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
          'jsonld.ParseError', array('line' => $line_number));
      }

      // create RDF statement
      $s = (object)array(
        'subject' => new stdClass(),
        'property' => new stdClass(),
        'object' => new stdClass());

      // get subject
      if($match[1] !== '') {
        $s->subject->nominalValue = $match[1];
        $s->subject->interfaceName = 'IRI';
      }
      else {
        $s->subject->nominalValue = $match[2];
        $s->subject->interfaceName = 'BlankNode';
      }

      // get property
      $s->property->nominalValue = $match[3];
      $s->property->interfaceName = 'IRI';

      // get object
      if($match[4] !== '') {
        $s->object->nominalValue = $match[4];
        $s->object->interfaceName = 'IRI';
      }
      else if($match[5] !== '') {
        $s->object->nominalValue = $match[5];
        $s->object->interfaceName = 'BlankNode';
      }
      else {
        $unescaped = str_replace(
           array('\"', '\t', '\n', '\r', '\\\\'),
           array('"', "\t", "\n", "\r", '\\'),
           $match[6]);
        $s->object->nominalValue = $unescaped;
        $s->object->interfaceName = 'LiteralNode';
        if(isset($match[7]) && $match[7] !== '') {
          $s->object->datatype = (object)array(
            'nominalValue' => $match[7], 'interfaceName' => 'IRI');
        }
        else if(isset($match[8]) && $match[8] !== '') {
          $s->object->language = $match[8];
        }
      }

      // get graph
      if(isset($match[9]) && $match[9] !== '') {
        $s->name = (object)array(
          'nominalValue' => $match[9], 'interfaceName' => 'IRI');
      }
      else if(isset($match[10]) && $match[10] !== '') {
        $s->name = (object)array(
          'nominalValue' => $match[10], 'interfaceName' => 'BlankNode');
      }

      // add statement
      self::_appendUniqueRdfStatement($statements, $s);
    }

    return $statements;
  }

  /**
   * Converts an RDF statement to an N-Quad string (a single quad).
   *
   * @param stdClass $statement the RDF statement to convert.
   * @param string $bnode the bnode the staetment is mapped to (optional, for
   *           use during normalization only).
   *
   * @return the N-Quad string.
   */
  public static function toNQuad($statement, $bnode=null) {
    $s = $statement->subject;
    $p = $statement->property;
    $o = $statement->object;
    $g = property_exists($statement, 'name') ? $statement->name : null;

    $quad = '';

    // subject is an IRI or bnode
    if($s->interfaceName === 'IRI') {
      $quad .= "<{$s->nominalValue}>";
    }
    // normalization mode
    else if($bnode !== null) {
      $quad .= ($s->nominalValue === $bnode) ? '_:a' : '_:z';
    }
    // normal mode
    else {
      $quad .= $s->nominalValue;
    }

    // property is always an IRI
    $quad .= " <{$p->nominalValue}> ";

    // object is IRI, bnode, or literal
    if($o->interfaceName === 'IRI') {
      $quad .= "<{$o->nominalValue}>";
    }
    else if($o->interfaceName === 'BlankNode') {
      // normalization mode
      if($bnode !== null) {
        $quad .= ($o->nominalValue === $bnode) ? '_:a' : '_:z';
      }
      // normal mode
      else {
        $quad .= $o->nominalValue;
      }
    }
    else {
      $escaped = str_replace(
          array('\\', "\t", "\n", "\r", '"'),
          array('\\\\', '\t', '\n', '\r', '\"'),
          $o->nominalValue);
      $quad .= '"' . $escaped . '"';
      if(property_exists($o, 'datatype') &&
        $o->datatype->nominalValue !== self::XSD_STRING) {
        $quad .= "^^<{$o->datatype->nominalValue}>";
      }
      else if(property_exists($o, 'language')) {
        $quad .= '@' . $o->language;
      }
    }

    // graph
    if($g !== null) {
      if($g->interfaceName === 'IRI') {
        $quad .= " <{$g->nominalValue}>";
      }
      else if(bnode) {
        $quad += ' _:g';
      }
      else {
        $quad += " {$g->nominalValue}";
      }
    }

    $quad .= " .\n";
    return $quad;
  }

  /**
   * Registers a processor-specific RDF Statement parser by content-type.
   * Global parsers will no longer be used by this processor.
   *
   * @param string $content_type the content-type for the parser.
   * @param callable $parser(input) the parser function (takes a string as
   *           a parameter and returns an array of RDF statements).
   */
  public function registerRDFParser($content_type, $parser) {
    if($this->rdfParsers === null) {
      $this->rdfParsers = new stdClass();
    }
    $this->rdfParsers->{$content_type} = $parser;
  }

  /**
   * Unregisters a process-specific RDF Statement parser by content-type. If
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
    else {
      return $value;
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
        // drop null values
        if($compacted !== null) {
          $rval[] = $compacted;
        }
      }
      if(count($rval) === 1) {
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
      // do value compaction on @values and subject references
      if(self::_isValue($element) || self::_isSubjectReference($element)) {
        return $this->_compactValue($active_ctx, $active_property, $element);
      }

      // shallow copy element and arrays so keys and values can be removed
      // during property generator compaction
      $shallow = new stdClass();
      foreach($element as $expanded_property => $expanded_value) {
        if(is_array($element->{$expanded_property})) {
          $shallow->{$expanded_property} = $element->{$expanded_property};
        }
        else {
          $shallow->{$expanded_property} = $element->{$expanded_property};
        }
      }
      $element = $shallow;

      // process element keys in order
      $keys = array_keys((array)$element);
      sort($keys);
      $rval = new stdClass();
      foreach($keys as $expanded_property) {
        // skip key if removed during property generator duplicate handling
        if(!property_exists($element, $expanded_property)) {
          continue;
        }

        $expanded_value = $element->{$expanded_property};

        // compact @id and @type(s)
        if($expanded_property === '@id' || $expanded_property === '@type') {
          // compact single @id
          if(is_string($expanded_value)) {
            $compacted_value = $this->_compactIri(
              $active_ctx, $expanded_value, null, array(
                'base' => true, 'vocab' => ($expanded_property === '@type')));
          }
          // expanded value must be a @type array
          else {
            $compacted_value = array();
            foreach($expanded_value as $ev) {
              $compacted_value[] = $this->_compactIri(
                $active_ctx, $ev, null, array('base' => true, 'vocab' => true));
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

        // Note: expanded value must be an array due to expansion algorithm.

        // preserve empty arrays
        if(count($expanded_value) === 0) {
          $active_property = $this->_compactIri(
            $active_ctx, $expanded_property, null, array('vocab' => true),
            $element);
          self::addValue(
            $rval, $active_property, array(), array('propertyIsArray' => true));
        }

        // recusively process array values
        foreach($expanded_value as $expanded_item) {
          // compact property and get container type
          $active_property = $this->_compactIri(
            $active_ctx, $expanded_property, $expanded_item,
            array('vocab' => true), $element);
          $container = self::getContextValue(
            $active_ctx, $active_property, '@container');

          // remove any duplicates that were (presumably) generated by a
          // property generator
          if(property_exists($active_ctx->mappings, $active_property)) {
            $mapping = $active_ctx->mappings->{$active_property};
            if($mapping && $mapping->propertyGenerator) {
              $this->_findAndRemovePropertyGeneratorDuplicates(
                $active_ctx, $element, $expanded_property, $expanded_item,
                $active_property);
            }
          }

          // get @list value if appropriate
          $is_list = self::_isList($expanded_item);
          $list = null;
          if($is_list) {
            $list = $expanded_item->{'@list'};
          }

          // recursively compact expanded item
          $compacted_item = $this->_compact(
            $active_ctx, $active_property, $is_list ? $list : $expanded_item,
            $options);

          // handle @list
          if($is_list) {
            // ensure $list value is an array
            $compacted_item = self::arrayify($compacted_item);

            if($container !== '@list') {
              // wrap using @list alias
              $compacted_item = (object)array(
                $this->_compactIri($active_ctx, '@list'), $compacted_item);

              // include @index from expanded @list, if any
              if(property_exists($expanded_item, '@index')) {
                $compacted_item->{$this->_compactIri($active_ctx, '@index')} =
                  $expanded_item->{'@index'};
              }
            }
            // can't use @list container for more than 1 list
            else if(property_exists($rval, $active_property)) {
              throw new JsonLdException(
                'JSON-LD compact error; property has a "@list" @container ' .
                'rule but there is more than a single @list that matches ' .
                'the compacted term in the document. Compaction might mix ' .
                'unwanted items into the list.',
                'jsonld.SyntaxError');
            }
          }

          // handle language and index maps
          if($container === '@language' || $container === '@index') {
            // get or create the map object
            if(property_exists($rval, $active_property)) {
              $map_object = $rval->{$active_property};
            }
            else {
              $rval->{$active_property} = $map_object = new stdClass();
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
          }
          else {
            // use an array if: @container is @set or @list , value is an empty
            // array, or key is @graph
            $is_array = ($container === '@set' || $container === '@list' ||
              (is_array($compacted_item) && count($compacted_item) === 0) ||
              $expanded_property === '@graph');

            // add compact value
            self::addValue(
              $rval, $active_property, $compacted_item,
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
   * the element will be removed. All context URLs must have been resolved
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
      foreach($element as $e) {
        // expand element
        $e = $this->_expand(
          $active_ctx, $active_property, $e, $options, $inside_list);
        if($inside_list && (is_array($e) || self::_isList($e))) {
          // lists of lists are illegal
          throw new JsonLdException(
            'Invalid JSON-LD syntax; lists of lists are not permitted.',
            'jsonld.SyntaxError');
        }
        // drop null values
        else if($e !== null) {
          if(is_array($e)) {
            $rval = array_merge($rval, $e);
          }
          else {
            $rval[] = $e;
          }
        }
      }
      return $rval;
    }

    // recursively expand object
    if(is_object($element)) {
      // if element has a context, process it
      if(property_exists($element, '@context')) {
        $active_ctx = $this->_processContext($active_ctx, $element->{'@context'}, $options);
        unset($element->{'@context'});
      }

      // expand the active property
      $expanded_active_property = $this->_expandIri(
        $active_ctx, $active_property);

      $rval = new stdClass();
      $keys = array_keys((array)$element);
      sort($keys);
      foreach($keys as $key) {
        $value = $element->{$key};

        // get term definition for key
        if(property_exists($active_ctx->mappings, $key)) {
          $mapping = $active_ctx->mappings->{$key};
        }
        else {
          $mapping = null;
        }

        // expand key using property generator
        if($mapping && $mapping->propertyGenerator) {
          $expanded_property = $mapping->{'@id'};
        }
        // expand key to IRI
        else {
          $expanded_property = $this->_expandIri(
            $active_ctx, $key, array('vocab' => true));
        }

        // drop non-absolute IRI keys that aren't keywords
        if($expanded_property === null ||
          !(is_array($expanded_property) ||
          self::_isAbsoluteIri($expanded_property) ||
          self::_isKeyword($expanded_property))) {
          continue;
        }

        // syntax error if @id is not a string
        if($expanded_property === '@id' && !is_string($value)) {
          throw new JsonLdException(
              'Invalid JSON-LD syntax; "@id" value must a string.',
              'jsonld.SyntaxError', array('value' => $value));
        }

        // validate @type value
        if($expanded_property === '@type') {
          $this->_validateTypeValue($value);
        }

        // @graph must be an array or an object
        if($expanded_property === '@graph' &&
          !(is_object($value) || is_array($value))) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@value" value must not be an ' .
            'object or an array.',
            'jsonld.SyntaxError', array('value' => $value));
        }

        // @value must not be an object or an array
        if($expanded_property === '@value' &&
          (is_object($value) || is_array($value))) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@value" value must not be an ' .
            'object or an array.',
            'jsonld.SyntaxError', array('value' => $value));
        }

        // @language must be a string
        if($expanded_property === '@language' && !is_string($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@language" value must not be a string.',
            'jsonld.SyntaxError', array('value' => $value));
          // ensure language value is lowercase
          $value = strtolower($value);
        }

        // preserve @index
        if($expanded_property === '@index') {
          if(!is_string($value)) {
            throw new JsonLdException(
              'Invalid JSON-LD syntax; "@index" value must be a string.',
              'jsonld.SyntaxError', array('value' => $value));
          }
        }

        $container = self::getContextValue($active_ctx, $key, '@container');

        // handle language map container (skip if value is not an object)
        if($container === '@language' && is_object($value)) {
          $expanded_value = $this->_expandLanguageMap($value);
        }
        // handle index container (skip if value is not an object)
        else if($container === '@index' && is_object($value)) {
          $expand_index_map = function($active_property) use (
            $active_ctx, $active_property, $options, $value) {
            $rval = array();
            $keys = array_keys((array)$value);
            sort($keys);
            foreach($keys as $key) {
              $val = $value->{$key};
              $val = self::arrayify($val);
              $val = $this->_expand(
                $active_ctx, $active_property, $val, $options, false);
              foreach($val as $item) {
                if(!property_exists($item, '@index')) {
                  $item->{'@index'} = $key;
                }
                $rval[] = $item;
              }
            }
            return $rval;
          };
          $expanded_value = $expand_index_map($key);
        }
        else {
          // recurse into @list or @set keeping the active property
          $is_list = ($expanded_property === '@list');
          if($is_list || $expanded_property === '@set') {
            $next_active_property = $active_property;
            if($is_list && $expanded_active_property === '@graph') {
              $next_active_property = null;
            }
            $expanded_value = $this->_expand(
              $active_ctx, $active_property, $value, $options, $is_list);
            if($is_list && self::_isList($expanded_value)) {
              throw new JsonLdException(
                'Invalid JSON-LD syntax; lists of lists are not permitted.',
                'jsonld.SyntaxError');
            }
          }
          else {
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

        // add copy of value for each property from property generator
        if(is_array($expanded_property)) {
          $this->_labelBlankNodes($active_ctx->namer, $expanded_value);
          foreach($expanded_property as $iri) {
            self::addValue(
              $rval, $iri, self::copy($expanded_value),
              array('propertyIsArray' => true));
          }
        }
        // add value for property
        else {
          // use an array except for certain keywords
          $use_array = (!in_array(
            $expanded_property, array(
              '@index', '@id', '@type', '@value', '@language')));
          self::addValue(
            $rval, $expanded_property, $expanded_value,
            array('propertyIsArray' => $use_array));
        }
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
            'jsonld.SyntaxError', array('element' => $rval));
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
            'jsonld.SyntaxError', array('element' => $rval));
        }
        // drop null @values
        if($rval->{'@value'} === null) {
          $rval = null;
        }
        // drop @language if @value isn't a string
        else if(property_exists($rval, '@language') &&
          !is_string($rval->{'@value'})) {
          unset($rval->{'@language'});
        }
      }
      // convert @type to an array
      else if(property_exists($rval, '@type') && !is_array($rval->{'@type'})) {
        $rval->{'@type'} = array($rval->{'@type'});
      }
      // handle @set and @list
      else if(property_exists($rval, '@set') ||
        property_exists($rval, '@list')) {
        if($count > 1 && ($count !== 2 && property_exists($rval, '@index'))) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; if an element has the property "@set" ' .
            'or "@list", then it can have at most one other property that is ' .
            '"@index".',
            'jsonld.SyntaxError', array('element' => $rval));
        }
        // optimize away @set
        if(property_exists($rval, '@set')) {
          $rval = $rval->{'@set'};
          $keys = array_keys((array)$rval);
          $count = count($keys);
        }
      }
      // drop objects with only @language
      else if($count === 1 && property_exists($rval, '@language')) {
        $rval = null;
      }

      // drop certain top-level objects that do not occur in lists
      if(is_object($rval) &&
        !$options['keepFreeFloatingNodes'] && !$inside_list &&
        ($active_property === null || $expanded_active_property === '@graph')) {
        // drop empty object or top-level @value
        if($count === 0 || property_exists($rval, '@value')) {
          $rval = null;
        }
        else {
          // drop subjects that generate no triples
          $has_triples = false;
          $ignore = array('@graph', '@type', '@list');
          foreach($keys as $key) {
            if(!self::_isKeyword($key) || in_array($key, $ignore)) {
              $has_triples = true;
              break;
            }
          }
          if(!$has_triples) {
            $rval = null;
          }
        }
      }

      return $rval;
    }

    // drop top-level scalars that are not in lists
    if(!$inside_list &&
      ($active_property === null ||
      $this->_expandIri($active_ctx, $active_property) === '@graph')) {
      return null;
    }

    // expand element according to value expansion rules
    return $this->_expandValue($active_ctx, $active_property, $element);
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
    $namer = new UniqueNamer('_:t');
    $graphs = (object)array('@default' => new stdClass());
    $this->_createNodeMap($input, $graphs, '@default', $namer);

    // add all non-default graphs to default graph
    $default_graph = $graphs->{'@default'};
    foreach($graphs as $graph_name => $node_map) {
      if($graph_name === '@default') {
        continue;
      }
      if(!property_exists($default_graph, $graph_name)) {
        $default_graph->{$graph_name} = (object)array(
          '@id' => $graph_name, '@graph' => array());
      }
      $subject = $default_graph->{$graph_name};
      if(!property_exists($subject, '@graph')) {
        $subject->{'@graph'} = array();
      }
      $nodeMap = $graphs->{$graph_name};
      $ids = array_keys((array)$node_map);
      sort($ids);
      foreach($ids as $id) {
        $subject->{'@graph'}[] = $node_map->{$id};
      }
    }

    // produce flattened output
    $flattened = array();
    $keys = array_keys((array)$default_graph);
    sort($keys);
    foreach($keys as $key) {
      $flattened[] = $default_graph->{$key};
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
        '@merged' => new stdClass()));

    // produce a map of all graphs and name each bnode
    $namer = new UniqueNamer('_:t');
    $this->_flatten($input, $state->graphs, '@default', $namer, null, null);
    $namer = new UniqueNamer('_:t');
    $this->_flatten($input, $state->graphs, '@merged', $namer, null, null);
    // FIXME: currently uses subjects from @merged graph only
    $state->subjects = $state->graphs->{'@merged'};

    // frame the subjects
    $framed = new ArrayObject();
    $this->_matchFrame(
      $state, array_keys((array)$state->subjects), $frame, $framed, null);
    return (array)$framed;
  }

  /**
   * Performs JSON-LD normalization.
   *
   * @param array $input the expanded JSON-LD object to normalize.
   * @param assoc $options the normalization options.
   *
   * @return mixed the normalized output.
   */
  protected function _normalize($input, $options) {
    // map bnodes to RDF statements
    $statements = array();
    $bnodes = new stdClass();
    $namer = new UniqueNamer('_:t');
    $this->_toRDF($input, $namer, null, null, null, $statements);
    foreach($statements as $statement) {
      foreach(array('subject', 'object') as $node) {
        $id = $statement->{$node}->nominalValue;
        if($statement->{$node}->interfaceName === 'BlankNode') {
          if(property_exists($bnodes, $id)) {
            $bnodes->{$id}->statements[] = $statement;
          }
          else {
            $bnodes->{$id} = (object)array('statements' => array($statement));
          }
        }
      }
    }

    // create canonical namer
    $namer = new UniqueNamer('_:c14n');

    // continue to hash bnode statements while bnodes are assigned names
    $unnamed = null;
    $nextUnnamed = array_keys((array)$bnodes);
    $duplicates = null;
    do {
      $unnamed = $nextUnnamed;
      $nextUnnamed = array();
      $duplicates = new stdClass();
      $unique = new stdClass();
      foreach($unnamed as $bnode) {
        // hash statements for each unnamed bnode
        $hash = $this->_hashStatements($bnode, $bnodes, $namer);

        // store hash as unique or a duplicate
        if(property_exists($duplicates, $hash)) {
          $duplicates->{$hash}[] = $bnode;
          $nextUnnamed[] = $bnode;
        }
        else if(property_exists($unique, $hash)) {
          $duplicates->{$hash} = array($unique->{$hash}, $bnode);
          $nextUnnamed[] = $unique->{$hash};
          $nextUnnamed[] = $bnode;
          unset($unique->{$hash});
        }
        else {
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
        $path_namer = new UniqueNamer('_:t');
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

    // update bnode names in each statement and serialize
    foreach($statements as $statement) {
      foreach(array('subject', 'object') as $node) {
        if($statement->{$node}->interfaceName === 'BlankNode') {
          $statement->{$node}->nominalValue = $namer->getName(
            $statement->{$node}->nominalValue);
        }
      }
      $normalized[] = $this->toNQuad($statement);
    }

    // sort normalized output
    sort($normalized);

    // handle output format
    if(isset($options['format'])) {
      if($options['format'] === 'application/nquads') {
        return implode($normalized);
      }
      else {
        throw new JsonLdException(
          'Unknown output format.',
          'jsonld.UnknownFormat', array('format' => $options['format']));
      }
    }

    // return parsed RDF statements
    return $this->parseNQuads(implode($normalized));
  }

  /**
   * Converts RDF statements into JSON-LD.
   *
   * @param array $statements the RDF statements.
   * @param assoc $options the RDF conversion options.
   *
   * @return array the JSON-LD output.
   */
  protected function _fromRDF($statements, $options) {
    // prepare graph map (maps graph name => subjects, lists)
    $default_graph = (object)array(
      'subjects' => new stdClass(), 'listMap' => new stdClass());
    $graphs = new stdClass();

    foreach($statements as $statement) {
      // get subject, property, object, and graph name (default to '')
      $s = $statement->subject->nominalValue;
      $p = $statement->property->nominalValue;
      $o = $statement->object;
      $name = (property_exists($statement, 'name') ?
        $statement->name->nominalValue : '');

      // use default graph
      if($name === '') {
        $graph = $default_graph;
      }
      // create a named graph entry as needed
      else if(!property_exists($graphs, $name)) {
        $graph = $graphs->{$name} = (object)array(
          'subjects' => new stdClass(), 'listMap' => new stdClass());
      }
      else {
        $graph = $graphs->{$name};
      }

      // handle element in @list
      if($p === self::RDF_FIRST) {
        // create list entry as needed
        $list_map = $graph->listMap;
        if(!property_exists($list_map, $s)) {
          $entry = $list_map->{$s} = new stdClass();
        }
        else {
          $entry = $list_map->{$s};
        }
        // set object value
        $entry->first = $this->_rdfToObject($o, $options['useNativeTypes']);
        continue;
      }

      // handle other element in @list
      if($p === self::RDF_REST) {
        // set next in list
        if($o->interfaceName === 'BlankNode') {
          // create list entry as needed
          $list_map = $graph->listMap;
          if(!property_exists($list_map, $s)) {
            $entry = $list_map->{$s} = new stdClass();
          }
          else {
            $entry = $list_map->{$s};
          }
          $entry->rest = $o->nominalValue;
        }
        continue;
      }

      // if graph is not the default graph
      if($name !== '' && !property_exists($default_graph->subjects, $name)) {
        $default_graph->subjects->{$name} = (object)array('@id' => $name);
      }

      // add subject to graph as needed
      $subjects = $graph->subjects;
      if(!property_exists($subjects, $s)) {
        $value = $subjects->{$s} = (object)array('@id' => $s);
      }
      // use existing subject value
      else {
        $value = $subjects->{$s};
      }

      // convert to @type unless options indicate to treat rdf:type as property
      if($p === self::RDF_TYPE && !$options['useRdfType']) {
        // add value of object as @type
        self::addValue(
          $value, '@type', $o->nominalValue, array('propertyIsArray' => true));
      }
      else {
        // add property to value as needed
        $object = $this->_rdfToObject($o, $options['useNativeTypes']);
        self::addValue($value, $p, $object, array('propertyIsArray' => true));

        // a bnode might be the beginning of a list, so add it to the list map
        if($o->interfaceName === 'BlankNode') {
          $id = $object->{'@id'};
          $list_map = $graph->listMap;
          if(!property_exists($list_map, $id)) {
            $entry = $list_map->{$id} = new stdClass();
          }
          else {
            $entry = $list_map->{$id};
          }
          $entry->head = $object;
        }
      }
    }

    // build @lists
    $all_graphs = array_values((array)$graphs);
    $all_graphs[] = $default_graph;
    foreach($all_graphs as $graph) {
      // find list head
      $list_map = $graph->listMap;
      foreach($list_map as $subject => $entry) {
        // head found, build lists
        if(property_exists($entry, 'head') &&
          property_exists($entry, 'first')) {
          // replace bnode @id with @list
          $value = $entry->head;
          unset($value->{'@id'});
          $list = array($entry->first);
          while(property_exists($entry, 'rest')) {
            $rest = $entry->rest;
            $entry = $list_map->{$rest};
            if(!property_exists($entry, 'first')) {
              throw new JsonLdException(
                'Invalid RDF list entry.',
                'jsonld.RdfError', array('bnode' => $rest));
            }
            $list[] = $entry->first;
          }
          $value->{'@list'} = $list;
        }
      }
    }

    // build default graph in subject @id order
    $output = array();
    $subjects = $default_graph->subjects;
    $ids = array_keys((array)$subjects);
    sort($ids);
    foreach($ids as $i => $id) {
      // add subject to default graph
      $subject = $subjects->{$id};
      $output[] = $subject;

      // output named graph in subject @id order
      if(property_exists($graphs, $id)) {
        $graph = array();
        $_subjects = $graphs->{$id}->subjects;
        $_ids = array_keys((array)$_subjects);
        sort($_ids);
        foreach($_ids as $_i => $_id) {
          $graph[] = $_subjects->{$_id};
        }
        $subject->{'@graph'} = $graph;
      }
    }
    return $output;
  }

  /**
   * Outputs the RDF statements found in the given JSON-LD element.
   *
   * @param mixed element the JSON-LD element.
   * @param UniqueNamer namer the UniqueNamer for assigning bnode names.
   * @param mixed subject the active subject.
   * @param mixed property the active property.
   * @param mixed graph the graph name.
   * @param &array statements the array to add statements to.
   */
  protected function _toRDF(
    $element, $namer, $subject, $property, $graph, &$statements) {
    // recurse into arrays
    if(is_array($element)) {
      // recurse into arrays
      foreach($element as $e) {
        $this->_toRDF($e, $namer, $subject, $property, $graph, $statements);
      }
      return;
    }

    // element must be an rdf:type IRI (@values covered above)
    if(is_string($element)) {
      // emit IRI
      $statement = (object)array(
          'subject' => self::copy($subject),
          'property' => self::copy($property),
          'object' => (object)array(
              'nominalValue' => $element,
              'interfaceName' => 'IRI'));
      if($graph !== null) {
        $statement->name = $graph;
      }
      self::_appendUniqueRdfStatement($statements, $statement);
      return;
    }

    // convert @list
    if(self::_isList($element)) {
      $list = $this->_makeLinkedList($element);
      $this->_toRDF($list, $namer, $subject, $property, $graph, $statements);
      return;
    }

    // convert @value to object
    if(self::_isValue($element)) {
      $value = $element->{'@value'};
      $datatype = (property_exists($element, '@type') ?
          $element->{'@type'} : null);
      if(is_bool($value) || is_double($value) || is_integer($value)) {
        // convert to XSD datatypes as appropriate
        if(is_bool($value)) {
          $value = ($value ? 'true' : 'false');
          $datatype or $datatype = self::XSD_BOOLEAN;
        }
        else if(is_double($value)) {
          // canonical double representation
          $value = preg_replace('/(\d)0*E\+?/', '$1E',
              sprintf('%1.15E', $value));
          $datatype or $datatype = self::XSD_DOUBLE;
        }
        else {
          $value = strval($value);
          $datatype or $datatype = self::XSD_INTEGER;
        }
      }

      // default to xsd:string datatype
      $datatype or $datatype = self::XSD_STRING;

      $object = (object)array(
        'nominalValue' => $value,
        'interfaceName' => 'LiteralNode',
        'datatype' => (object)array(
          'nominalValue' => $datatype,
          'interfaceName' => 'IRI'));
      if(property_exists($element, '@language') &&
        $datatype === self::XSD_STRING) {
        $object->language = $element->{'@language'};
      }

      // emit literal
      $statement = (object)array(
        'subject' => self::copy($subject),
        'property' => self::copy($property),
        'object' => $object);
      if($graph !== null) {
        $statement->name = $graph;
      }
      self::_appendUniqueRdfStatement($statements, $statement);
      return;
    }

    // Note: element must be a subject

    // get subject @id (generate one if it is a bnode)
    $id = property_exists($element, '@id') ? $element->{'@id'} : null;
    $is_bnode = self::_isBlankNode($element);
    if($is_bnode) {
      $id = $namer->getName($id);
    }

    // create object
    $object = (object)array(
      'nominalValue' => $id,
      'interfaceName' => $is_bnode ? 'BlankNode' : 'IRI');

    // emit statement if subject isn't null
    if($subject !== null) {
      $statement = (object)array(
        'subject' => self::copy($subject),
        'property' => self::copy($property),
        'object' => self::copy($object));
      if($graph !== null) {
        $statement->name = $graph;
      }
      self::_appendUniqueRdfStatement($statements, $statement);
    }

    // set new active subject to object
    $subject = $object;

    // recurse over subject properties in order
    $props = array_keys((array)$element);
    sort($props);
    foreach($props as $prop) {
      $p = $prop;

      // convert @type to rdf:type
      if($prop === '@type') {
        $p = self::RDF_TYPE;
      }

      // recurse into @graph
      if($prop === '@graph') {
        $this->_toRDF(
          $element->{$prop}, $namer, null, null, $subject, $statements);
        continue;
      }

      // skip keywords
      if(self::_isKeyword($p)) {
        continue;
      }

      // create new active property
      $property = (object)array(
        'nominalValue' => $p,
        'interfaceName' => 'IRI');

      // recurse into value
      $this->_toRDF(
        $element->{$prop}, $namer, $subject, $property, $graph, $statements);
    }
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

    $rval = null;

    // get context from cache if available
    if(property_exists($jsonld_cache, 'activeCtx')) {
      $rval = $jsonld_cache->activeCtx->get($active_ctx, $local_ctx);
      if($rval) {
        return $rval;
      }
    }

    // initialize the resulting context
    $rval = self::_cloneActiveContext($active_ctx);

    // normalize local context to an array
    if(is_object($local_ctx) && property_exists($local_ctx, '@context') &&
      is_array($local_ctx->{'@context'})) {
      $local_ctx = $local_ctx->{'@context'};
    }
    $ctxs = self::arrayify($local_ctx);

    // process each context in order
    foreach($ctxs as $ctx) {
      // reset to initial context
      if($ctx === null) {
        $rval = $this->_getInitialContext($options);
        $rval->namer = $active_ctx->namer;
        continue;
      }

      // dereference @context key if present
      if(is_object($ctx) && property_exists($ctx, '@context')) {
        $ctx = $ctx->{'@context'};
      }

      // context must be an object by now, all URLs resolved before this call
      if(!is_object($ctx)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context must be an object.',
          'jsonld.SyntaxError', array('context' => $ctx));
      }

      // define context mappings for keys in local context
      $defined = new stdClass();

      // handle @vocab
      if(property_exists($ctx, '@vocab')) {
        $value = $ctx->{'@vocab'};
        if($value === null) {
          unset($rval->{'@vocab'});
        }
        else if(!is_string($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; the value of "@vocab" in a ' .
            '@context must be a string or null.',
            'jsonld.SyntaxError', array('context' => $ctx));
        }
        else if(!self::_isAbsoluteIri($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; the value of "@vocab" in a ' .
            '@context must be an absolute IRI.',
            'jsonld.SyntaxError', array('context' => $ctx));
        }
        else {
          $rval->{'@vocab'} = $value;
        }
        $defined->{'@vocab'} = true;
      }

      // handle @language
      if(property_exists($ctx, '@language')) {
        $value = $ctx->{'@language'};
        if($value === null) {
          unset($rval->{'@language'});
        }
        else if(!is_string($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; the value of "@language" in a ' .
            '@context must be a string or null.',
            'jsonld.SyntaxError', array('context' => $ctx));
        }
        else {
          $rval->{'@language'} = strtolower($value);
        }
        $defined->{'@language'} = true;
      }

      // process all other keys
      foreach($ctx as $k => $v) {
        $this->_createTermDefinition($rval, $ctx, $k, $defined);
      }
    }

    // cache result
    if(property_exists($jsonld_cache, 'activeCtx')) {
      $jsonld_cache->activeCtx->set($active_ctx, $local_ctx, $rval);
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
        if(!is_string($item)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; language map values must be strings.',
            'jsonld.SyntaxError', array('languageMap', $language_map));
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
   * @param bool $is_id true if the given element is an @id (or @type).
   *
   * @return mixed the element.
   */
  protected function _labelBlankNodes($namer, $element, $is_id=false) {
    if(is_array($element)) {
      $length = count($element);
      for($i = 0; $i < $length; ++$i) {
        $element[$i] = $this->_labelBlankNodes($namer, $element[$i], $is_id);
      }
    }
    else if(self::_isList($element)) {
      $element->{'@list'} = $this->_labelBlankNodes(
        $namer, $element->{'@list'}, $is_id);
    }
    else if(is_object($element)) {
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
          $element->{$key} = $this->_labelBlankNodes(
            $namer, $element->{$key}, $key === '@type');
        }
      }
    }
    // rename blank node identifier
    else if(is_string($element) && $is_id && strpos($element, '_:') === 0) {
      $element = $namer->getName($element);
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
    }
    else if($expanded_property === '@type') {
      return $this->_expandIri(
        $active_ctx, $value, array('vocab' => true, 'base' => true));
    }

    // get type definition from context
    $type = self::getContextValue($active_ctx, $active_property, '@type');

    // do @id expansion (automatic for @graph)
    if($type === '@id' || $expanded_property === '@graph') {
      return (object)array('@id' => $this->_expandIri(
        $active_ctx, $value, array('base' => true)));
    }

    // do not expand keyword values
    if(self::_isKeyword($expanded_property)) {
      return $value;
    }

    $rval = new stdClass();

    // other type
    if($type !== null) {
      // rename blank node if requested
      if($active_ctx->namer !== null && strpos($type, '_:') === 0) {
        $type = $active_ctx->namer->getName($type);
      }
      $rval->{'@type'} = $type;
    }
    // check for language tagging for strings
    else if(is_string($value)) {
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
   * Converts an RDF statement object to a JSON-LD object.
   *
   * @param stdClass $o the RDF statement object to convert.
   * @param bool $use_native_types true to output native types, false not to.
   *
   * @return stdClass the JSON-LD object.
   */
  protected function _rdfToObject($o, $use_native_types) {
    // convert empty list
    if($o->interfaceName === 'IRI' && $o->nominalValue === self::RDF_NIL) {
      return (object)array('@list' => array());
    }

    // convert IRI/BlankNode object to JSON-LD
    if($o->interfaceName === 'IRI' || $o->interfaceName === 'BlankNode') {
      return (object)array('@id' => $o->nominalValue);
    }

    // convert literal object to JSON-LD
    $rval = (object)array('@value' => $o->nominalValue);

    // add datatype
    if(property_exists($o, 'datatype')) {
      $type = $o->datatype->nominalValue;
      // use native types for certain xsd types
      if($use_native_types) {
        if($type === self::XSD_BOOLEAN) {
          if($rval->{'@value'} === 'true') {
            $rval->{'@value'} = true;
          }
          else if($rval->{'@value'} === 'false') {
            $rval->{'@value'} = false;
          }
        }
        else if(is_numeric($rval->{'@value'})) {
          if($type === self::XSD_INTEGER) {
            $i = intval($rval->{'@value'});
            if(strval($i) === $rval->{'@value'}) {
              $rval->{'@value'} = $i;
            }
          }
          else if($type === self::XSD_DOUBLE) {
            $rval->{'@value'} = doubleval($rval->{'@value'});
          }
        }
        // do not add native type
        if(!in_array($type, array(
          self::XSD_BOOLEAN, self::XSD_INTEGER, self::XSD_DOUBLE,
          self::XSD_STRING))) {
          $rval->{'@type'} = $type;
        }
      }
      else {
        $rval->{'@type'} = $type;
      }
    }
    // add language
    if(property_exists($o, 'language')) {
      $rval->{'@language'} = $o->language;
    }

    return $rval;
  }

  /**
   * Converts a @list value into an embedded linked list of blank nodes in
   * expanded form. The resulting array can be used as an RDF-replacement for
   * a property that used a @list.
   *
   * @param array $value the @list value.
   *
   * @return stdClass the head of the linked list of blank nodes.
   */
  protected function _makeLinkedList($value) {
    // convert @list array into embedded blank node linked list in reverse
    $list = $value->{'@list'};
    $len = count($list);
    $tail = (object)array('@id' => self::RDF_NIL);
    for($i = $len - 1; $i >= 0; --$i) {
      $tail = (object)array(
        self::RDF_FIRST => array($list[$i]),
        self::RDF_REST => array($tail));
    }
    return $tail;
  }

  /**
   * Recursively flattens the subjects in the given JSON-LD expanded input
   * into a node map.
   *
   * @param mixed $input the JSON-LD expanded input.
   * @param stdClass $graphs a map of graph name to subject map.
   * @[ara, string $graph the name of the current graph.
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
    if(!is_object($input) || self::_isValue($input)) {
      if($list !== null) {
        $list[] = $input;
      }
      return;
    }

    // add entries for @type
    if(property_exists($input, '@type')) {
      $types = $input->{'@type'};
      $types = self::arrayify($types);
      foreach($types as $type) {
        $id = (strpos($type, '_:') === 0) ? $namer->getName($type) : $type;
        if(!property_exists($graphs->{$graph}, $id)) {
          $graphs->{$graph}->{$id} = (object)array('@id' => $id);
        }
      }
    }

    // add add values to list
    if(self::_isValue($input)) {
      if($list !== null) {
        $list[] = $input;
      }
      return;
    }

    // Note: At this point, input must be a subject.

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
      $subjects->{$name} = new stdClass();
    }
    $subject = $subjects->{$name};
    $subject->{'@id'} = $name;
    $properties = array_keys((array)$input);
    sort($properties);
    foreach($properties as $property) {
      // skip @id
      if($property === '@id') {
        continue;
      }

      // recurse into graph
      if($property === '@graph') {
        // add graph subjects map entry
        if(!property_exists($graphs, $name)) {
          $graphs->{$name} = new stdClass();
        }
        $g = ($graph === '@merged') ? $graph : $name;
        $this->_createNodeMap(
          $input->{$property}, $graphs, $g, $namer, null, null);
        continue;
      }

      // copy non-@type keywords
      if($property !== '@type' && self::_isKeyword($property)) {
        $subject->{$property} = $input->{$property};
        continue;
      }

      // iterate over objects
      $objects = $input->{$property};
      if(count($objects) === 0) {
        self::addValue(
          $subject, $property, array(), array('propertyIsArray' => true));
        continue;
      }
      foreach($objects as $o) {
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
            array('propertyIsArray' => true));
          $this->_createNodeMap($o, $graphs, $graph, $namer, $id, null);
        }
        // handle $list
        else if(self::_isList($o)) {
          $_list = new ArrayObject();
          $this->_createNodeMap(
            $o->{'@list'}, $graphs, $graph, $namer, $name, $_list);
          $o = (object)array('@list' => (array)$_list);
          self::addValue(
            $subject, $property, $o, array('propertyIsArray' => true));
        }
        // handle @value
        else {
          $this->_createNodeMap($o, $graphs, $graph, $namer, $name, null);
          self::addValue(
            $subject, $property, $o, array('propertyIsArray' => true));
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
    $this->_validateFrame($state, $frame);
    $frame = $frame[0];

    // filter out subjects that match the frame
    $matches = $this->_filterSubjects($state, $subjects, $frame);

    // get flags for current frame
    $options = $state->options;
    $embed_on = $this->_getFrameFlag($frame, $options, 'embed');
    $explicit_on = $this->_getFrameFlag($frame, $options, 'explicit');

    // add matches to output
    foreach($matches as $id => $subject) {
      /* Note: In order to treat each top-level match as a compartmentalized
      result, create an independent copy of the embedded subjects map when the
      property is null, which only occurs at the top-level. */
      if($property === null) {
        $state->embeds = new stdClass();
      }

      // start output
      $output = new stdClass();
      $output->{'@id'} = $id;

      // prepare embed meta info
      $embed = (object)array('parent' => $parent, 'property' => $property);

      // if embed is on and there is an existing embed
      if($embed_on && property_exists($state->embeds, $id)) {
        // only overwrite an existing embed if it has already been added to its
        // parent -- otherwise its parent is somewhere up the tree from this
        // embed and the embed would occur twice once the tree is added
        $embed_on = false;

        // existing embed's parent is an array
        $existing = $state->embeds->{$id};
        if(is_array($existing->parent)) {
          foreach($existing->parent as $p) {
            if(self::compareValues($output, $p)) {
              $embed_on = true;
              break;
            }
          }
        }
        // existing embed's parent is an object
        else if(self::hasValue(
          $existing->parent, $existing->property, $output)) {
          $embed_on = true;
        }

        // existing embed has already been added, so allow an overwrite
        if($embed_on) {
          $this->_removeEmbed($state, $id);
        }
      }

      // not embedding, add output without any other properties
      if(!$embed_on) {
        $this->_addFrameOutput($state, $parent, $property, $output);
      }
      else {
        // add embed meta info
        $state->embeds->{$id} = $embed;

        // iterate over subject properties
        $props = array_keys((array)$subject);
        sort($props);
        foreach($props as $prop) {
          // copy keywords to output
          if(self::_isKeyword($prop)) {
            $output->{$prop} = self::copy($subject->{$prop});
            continue;
          }

          // if property isn't in the frame
          if(!property_exists($frame, $prop)) {
            // if explicit is off, embed values
            if(!$explicit_on) {
              $this->_embedValues($state, $subject, $prop, $output);
            }
            continue;
          }

          // add objects
          $objects = $subject->{$prop};
          foreach($objects as $o) {
            // recurse into list
            if(self::_isList($o)) {
              // add empty list
              $list = (object)array('@list' => array());
              $this->_addFrameOutput($state, $output, $prop, $list);

              // add list objects
              $src = $o->{'@list'};
              foreach($src as $o) {
                // recurse into subject reference
                if(self::_isSubjectReference($o)) {
                  $this->_matchFrame(
                    $state, array($o->{'@id'}), $frame->{$prop},
                    $list, '@list');
                }
                // include other values automatically
                else {
                  $this->_addFrameOutput(
                    $state, $list, '@list', self::copy($o));
                }
              }
              continue;
            }

            // recurse into subject reference
            if(self::_isSubjectReference($o)) {
              $this->_matchFrame(
                $state, array($o->{'@id'}), $frame->{$prop}, $output, $prop);
            }
            // include other values automatically
            else {
              $this->_addFrameOutput($state, $output, $prop, self::copy($o));
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
            $output->{$prop} = (object)array('@preserve' => $preserve);
          }
        }

        // add output to parent
        $this->_addFrameOutput($state, $parent, $property, $output);
      }
    }
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
    return (property_exists($frame, $flag) ?
      $frame->{$flag}[0] : $options[$name]);
  }

  /**
   * Validates a JSON-LD frame, throwing an exception if the frame is invalid.
   *
   * @param stdClass $state the current frame state.
   * @param array $frame the frame to validate.
   */
  protected function _validateFrame($state, $frame) {
    if(!is_array($frame) || count($frame) !== 1 || !is_object($frame[0])) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; a JSON-LD frame must be a single object.',
        'jsonld.SyntaxError', array('frame' => $frame));
    }
  }

  /**
   * Returns a map of all of the subjects that match a parsed frame.
   *
   * @param stdClass $state the current framing state.
   * @param array $subjects the set of subjects to filter.
   * @param stdClass $frame the parsed frame.
   *
   * @return stdClass all of the matched subjects.
   */
  protected function _filterSubjects($state, $subjects, $frame) {
    $rval = new stdClass();
    sort($subjects);
    foreach($subjects as $id) {
      $subject = $state->subjects->{$id};
      if($this->_filterSubject($subject, $frame)) {
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
   *
   * @return bool true if the subject matches, false if not.
   */
  protected function _filterSubject($subject, $frame) {
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
    foreach($frame as $k => $v) {
      // only not a duck if @id or non-keyword isn't in subject
      if(($k === '@id' || !self::_isKeyword($k)) &&
        !property_exists($subject, $k)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Embeds values for the given subject and property into the given output
   * during the framing algorithm.
   *
   * @param stdClass $state the current framing state.
   * @param stdClass $subject the subject.
   * @param string $property the property.
   * @param mixed $output the output.
   */
  protected function _embedValues($state, $subject, $property, $output) {
    // embed subject properties in output
    $objects = $subject->{$property};
    foreach($objects as $o) {
      // recurse into @list
      if(self::_isList($o)) {
        $list = (object)array('@list' => new ArrayObject());
        $this->_addFrameOutput($state, $output, $property, $list);
        $this->_embedValues($state, $o, '@list', $list->{'@list'});
        $list->{'@list'} = (array)$list->{'@list'};
        return;
      }

      // handle subject reference
      if(self::_isSubjectReference($o)) {
        $id = $o->{'@id'};

        // embed full subject if isn't already embedded
        if(!property_exists($state->embeds, $id)) {
          // add embed
          $embed = (object)array('parent' => $output, 'property' => $property);
          $state->embeds->{$id} = $embed;

          // recurse into subject
          $o = new stdClass();
          $s = $state->subjects->{$id};
          foreach($s as $prop => $v) {
            // copy keywords
            if(self::_isKeyword($prop)) {
              $o->{$prop} = self::copy($v);
              continue;
            }
            $this->_embedValues($state, $s, $prop, $o);
          }
        }
        $this->_addFrameOutput($state, $output, $property, $o);
      }
      // copy non-subject value
      else {
        $this->_addFrameOutput($state, $output, $property, self::copy($o));
      }
    }
  }

  /**
   * Removes an existing embed.
   *
   * @param stdClass $state the current framing state.
   * @param string $id the @id of the embed to remove.
   */
  protected function _removeEmbed($state, $id) {
    // get existing embed
    $embeds = $state->embeds;
    $embed = $embeds->{$id};
    $property = $embed->property;

    // create reference to replace embed
    $subject = (object)array('@id' => id);

    // remove existing embed
    if(is_array($embed->parent)) {
      // replace subject with reference
      foreach($embed->parent as $i => $parent) {
        if(self::compareValues($parent, $subject)) {
          $embed->parent[$i] = $subject;
          break;
        }
      }
    }
    else {
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
   * @param stdClass $state the current framing state.
   * @param mixed $parent the parent to add to.
   * @param string $property the parent property.
   * @param mixed $output the output to add.
   */
  protected function _addFrameOutput($state, $parent, $property, $output) {
    if(is_object($parent) && !($parent instanceof ArrayObject)) {
      self::addValue(
        $parent, $property, $output, array('propertyIsArray' => true));
    }
    else {
      $parent[] = $output;
    }
  }

  /**
   * Removes the @preserve keywords as the last step of the framing algorithm.
   *
   * @param stdClass $ctx the active context used to compact the input.
   * @param mixed $input the framed, compacted output.
   *
   * @return mixed the resulting output.
   */
  protected function _removePreserve($ctx, $input) {
    // recurse through arrays
    if(is_array($input)) {
      $output = array();
      foreach($input as $e) {
        $result = $this->_removePreserve($ctx, $e);
        // drop nulls from arrays
        if($result !== null) {
          $output[] = $result;
        }
      }
      $input = $output;
    }
    else if(is_object($input)) {
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
        $input->{'@list'} = $this->_removePreserve($ctx, $input->{'@list'});
        return $input;
      }

      // recurse through properties
      foreach($input as $prop => $v) {
        $result = $this->_removePreserve($ctx, $v);
        $container = self::getContextValue($ctx, $prop, '@container');
        if(is_array($result) && count($result) === 1 &&
          $container !== '@set' && $container !== '@list') {
          $result = $result[0];
        }
        $input->{$prop} = $result;
      }
    }
    return $input;
  }

  /**
   * Hashes all of the statements about a blank node.
   *
   * @param string $id the ID of the bnode to hash statements for.
   * @param stdClass $bnodes the mapping of bnodes to statements.
   * @param UniqueNamer $namer the canonical bnode namer.
   *
   * @return string the new hash.
   */
  protected function _hashStatements($id, $bnodes, $namer) {
    // return cached hash
    if(property_exists($bnodes->{$id}, 'hash')) {
      return $bnodes->{$id}->hash;
    }

    // serialize all of bnode's statements
    $statements = $bnodes->{$id}->statements;
    $nquads = array();
    foreach($statements as $statement) {
      $nquads[] = $this->toNQuad($statement, $id);
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
   * @param stdClass $bnodes the map of bnode statements.
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
    $statements = $bnodes->{$id}->statements;
    foreach($statements as $statement) {
      // get adjacent bnode
      $bnode = $this->_getAdjacentBlankNodeName($statement->subject, $id);
      if($bnode !== null) {
        $direction = 'p';
      }
      else {
        $bnode = $this->_getAdjacentBlankNodeName($statement->object, $id);
        if($bnode !== null) {
          $direction = 'r';
        }
      }
      if($bnode !== null) {
        // get bnode name (try canonical, path, then hash)
        if($namer->isNamed($bnode)) {
          $name = $namer->getName($bnode);
        }
        else if($path_namer->isNamed($bnode)) {
          $name = $path_namer->getName($bnode);
        }
        else {
          $name = $this->_hashStatements($bnode, $bnodes, $namer);
        }

        // hash direction, property, and bnode name/hash
        $group_md = hash_init('sha1');
        hash_update($group_md, $direction);
        hash_update($group_md, $statement->property->nominalValue);
        hash_update($group_md, $name);
        $group_hash = hash_final($group_md);

        // add bnode to hash group
        if(property_exists($groups, $group_hash)) {
          $groups->{$group_hash}[] = $bnode;
        }
        else {
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
          }
          else {
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
   * A helper function that gets the blank node name from an RDF statement
   * node (subject or object). If the node is not a blank node or its
   * nominal value does not match the given blank node ID, it will be
   * returned.
   *
   * @param stdClass $node the RDF statement node.
   * @param string $id the ID of the blank node to look next to.
   *
   * @return mixed the adjacent blank node name or null if none was found.
   */
  protected function _getAdjacentBlankNodeName($node, $id) {
    if($node->interfaceName === 'BlankNode' && $node->nominalValue !== $id) {
      return $node->nominalValue;
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
    else if($len_b < $len_a) {
      return 1;
    }
    else if($a === $b) {
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
   * @param parent the parent of the value (required for property generators).
   * @param containers the preferred containers.
   * @param type_or_language either '@type' or '@language'.
   * @param type_or_language_value the preferred value for '@type' or
   *          '@language'.
   *
   * @return mixed the preferred term.
   */
  protected function _selectTerm(
    $active_ctx, $iri, $value, $parent, $containers,
    $type_or_language, $type_or_language_value) {
    $containers[] = '@none';
    if($type_or_language_value === null) {
      $type_or_language_value = '@null';
    }
    // options for the value of @type or @language
    $options = array($type_or_language_value, '@none');
    $term = null;
    $container_map = $active_ctx->inverse->{$iri};
    foreach($containers as $container) {
      if($term !== null) {
        break;
      }

      // if container not available in the map, continue
      if(!property_exists($container_map, $container)) {
        continue;
      }

      $type_or_language_value_map =
        $container_map->{$container}->{$type_or_language};
      foreach($options as $option) {
        if($term !== null) {
          break;
        }

        // if type/language option not available in the map, continue
        if(!property_exists($type_or_language_value_map, $option)) {
          continue;
        }

        $term_info = $type_or_language_value_map->{$option};

        // see if a property generator matches
        if(is_object($parent)) {
          foreach($term_info->propertyGenerators as $property_generator) {
            $iris = $active_ctx->mappings->{$property_generator}->{'@id'};
            $match = true;
            foreach($iris as $iri_) {
              if(!property_exists($parent, $iri_)) {
                $match = false;
                break;
              }
            }
            if($match) {
              $term = $property_generator;
              break;
            }
          }
        }

        // no matching property generator, use a simple term instead
        if($term === null) {
          $term = $term_info->term;
        }
      }
    }
    return $term;
  }

  /**
   * Compacts an IRI or keyword into a term or prefix if it can be. If the
   * IRI has an associated value it may be passed.
   *
   * @param stdClass $active_ctx the active context to use.
   * @param string $iri the IRI to compact.
   * @param mixed $value the value to check or null.
   * @param assoc $relative_to options for how to compact IRIs:
   *          base: true to compact against the base IRI, false not to.
   *          vocab: true to split after @vocab, false not to.
   * @param mixed $parent the parent element for the value.
   *
   * @return string the compacted term, prefix, keyword alias, or original IRI.
   */
  protected function _compactIri(
    $active_ctx, $iri, $value=null, $relative_to=array(), $parent=null) {
    // can't compact null
    if($iri === null) {
      return $iri;
    }

    // term is a keyword
    if(self::_isKeyword($iri)) {
      // return alias if available
      $aliases = $active_ctx->keywords->{$iri};
      return (count($aliases) > 0) ? $aliases[0] : $iri;
    }

    // use inverse context to pick a term
    $inverse_ctx = $this->_getInverseContext($active_ctx);

    $default_language = '@none';
    if(property_exists($active_ctx, '@language')) {
      $default_language = $active_ctx->{'@language'};
    }

    if(property_exists($inverse_ctx, $iri)) {
      // prefer @index if available in value
      $containers = array();
      if(is_object($value) && property_exists($value, '@index')) {
        $containers[] = '@index';
      }

      // defaults for term selection based on type/language
      $type_or_language = '@language';
      $type_or_language_value = '@null';

      // choose the most specific term that works for all elements in @list
      if(self::_isList($value)) {
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
            }
            else if(property_exists($item, '@type')) {
              $item_type = $item->{'@type'};
            }
            // plain literal
            else {
              $item_language = '@null';
            }
          }
          if($common_language === null) {
            $common_language = $item_language;
          }
          else if($item_language !== $common_language &&
            self::_isValue($item)) {
            $common_language = '@none';
          }
          if($common_type === null) {
            $common_type = $item_type;
          }
          else if($item_type !== $common_type) {
            $common_type = '@none';
          }
          // there are different languages and types in the list, so choose
          // the most generic term, no need to keep iterating the list
          if($common_language === '@none' && $common_type === '@none') {
            break;
          }
        }
        $common_language = $common_language || '@none';
        $common_type = $common_type || '@none';
        if($common_type !== '@none') {
          $type_or_language = '@type';
          $type_or_language_value = $common_type;
        }
        else {
          $type_or_language_value = $common_language;
        }
      }
      else {
        if(self::_isValue($value)) {
          if(property_exists($value, '@language')) {
            $containers[] = '@language';
            $type_or_language_value = $value->{'@language'};
          }
          else if(property_exists($value, '@type')) {
            $type_or_language = '@type';
            $type_or_language_value = $value->{'@type'};
          }
        }
        else {
          $type_or_language = '@type';
          $type_or_language_value = '@id';
        }
        $containers[] = '@set';
      }

      // do term selection
      $term = $this->_selectTerm(
        $active_ctx, $iri, $value, $parent,
        $containers, $type_or_language, $type_or_language_value);
      if($term !== null) {
        return $term;
      }
    }

    // no term match, check for possible CURIEs
    $choice = null;
    foreach($active_ctx->mappings as $term => $definition) {
      // skip terms with colons, they can't be prefixes
      if(strpos($term, ':') !== false) {
        continue;
      }
      // skip entries with @ids that are not partial matches
      if($definition === null || $definition->propertyGenerator ||
        $definition->{'@id'} === $iri ||
        strpos($iri, $definition->{'@id'}) !== 0) {
        continue;
      }

      // a CURIE is usable if:
      // 1. it has no mapping, OR
      // 2. value is null, which means we're not compacting an @value, AND
      //   the mapping matches the IRI)
      $curie = $term . ':' . substr($iri, strlen($definition->{'@id'}));
      $is_usable_curie = (!property_exists($active_ctx->mappings, $curie) ||
        ($value === null && $active_ctx->mappings->{$curie} &&
        $active_ctx->mappings->{$curie}->{'@id'} === $iri));

      // select curie if it is shorter or the same length but lexicographically
      // less than the current choice
      if($is_usable_curie && ($choice === null ||
        self::_compareShortestLeast($curie, $choice) < 0)) {
        $choice = $curie;
      }
    }

    // return chosen curie
    if($choice !== null) {
      return $choice;
    }

    // no matching terms or curies, use @vocab if available
    if(isset($relative_to['vocab']) && $relative_to['vocab'] &&
      property_exists($active_ctx, '@vocab')) {
      // determine if vocab is a prefix of the iri
      $vocab = $active_ctx->{'@vocab'};
      if(strpos($iri, $vocab) === 0 && $iri !== $vocab) {
        // use suffix as relative iri if it is not a term in the active context
        $suffix = substr($iri, strlen($vocab));
        if(!property_exists($active_ctx->mappings, $suffix)) {
          return $suffix;
        }
      }
    }

    // no compaction choices, return IRI as is
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

      // if there's no @index to preserve ...
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
          $active_ctx, $value->{'@type'}, null,
          array('base' => true, 'vocab' => true));
      }
      // alias @language
      else if(property_exists($value, '@language')) {
        $rval->{$this->_compactIri($active_ctx, '@language')} =
          $value->{'@language'};
      }

      // alias @value
      $rval->{$this->_compactIri($active_ctx, '@value')} = $value->{'@value'};

      return $rval;
    }

    // value is a subject reference
    $expanded_property = $this->_expandIri($active_ctx, $active_property);
    $type = self::getContextValue($active_ctx, $active_property, '@type');
    $term = $this->_compactIri(
      $active_ctx, $value->{'@id'}, null, array('base' => true));

    // compact to scalar
    if($type === '@id' || $expanded_property === '@graph') {
      return $term;
    }

    $rval = (object)array(
      $this->_compactIri($active_ctx, '@id') => $term);
    return $rval;
  }

  /**
   * Finds and removes any duplicate values that were presumably generated by
   * a property generator in the given element.
   *
   * @param stdClass $active_ctx the active context.
   * @param stdClass $element the element to remove duplicates from.
   * @param string $expanded_property the property to map to a property
   *          generator.
   * @param mixed $value the value to compare against when duplicate checking.
   * @param string $active_property the property generator term.
   */
  protected function _findAndRemovePropertyGeneratorDuplicates(
    $active_ctx, $element, $expanded_property, $value, $active_property) {
    // get property generator IRIs
    $iris = $active_ctx->mappings->{$active_property}->{'@id'};

    // for each IRI that isn't 'expandedProperty', remove a single duplicate
    // from element, if found
    foreach($iris as $iri) {
      if($iri === $expanded_property) {
        continue;
      }
      $length = count($element->{$iri});
      for($pi = 0; $pi < $length; ++$pi) {
        if(self::compareValues($element->{$iri}[$pi], $value)) {
          // duplicate found, remove it in place
          array_splice($element->{$iri}, $pi, 1);
          if(count($element->{$iri}) === 0) {
            unset($element->{$iri});
          }
          break;
        }
      }
    }
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
        'jsonld.CyclicalContext',
        (object)array('context' => $local_ctx, 'term' => $term));
    }

    // now defining term
    $defined->{$term} = false;

    // if term has a prefix, define it first
    $colon = strpos($term, ':');
    $prefix = null;
    if($colon !== false) {
      $prefix = substr($term, 0, $colon);
      if(property_exists($local_ctx, $prefix)) {
        // define parent prefix
        $this->_createTermDefinition(
          $active_ctx, $local_ctx, $prefix, $defined);
      }
    }

    if(self::_isKeyword($term)) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; keywords cannot be overridden.',
        'jsonld.SyntaxError', array('context' => $local_ctx));
    }

    if(property_exists($active_ctx->mappings, $term) &&
      $active_ctx->mappings->{$term} !== null) {
      // if term is a keyword alias, remove it
      $kw = $active_ctx->mappings->{$term}->{'@id'};
      if(self::_isKeyword($kw)) {
        array_splice($active_ctx->keywords->{$kw},
          array_search($term, $active_ctx->keywords->{$kw}), 1);
      }
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

    if(is_string($value)) {
      if(self::_isKeyword($value)) {
        // disallow aliasing @context and @preserve
        if($value === '@context' || $value === '@preserve') {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; @context and @preserve cannot be aliased.',
            'jsonld.SyntaxError');
        }

        // uniquely add term as a keyword alias and resort
        if(!in_array($term, $active_ctx->keywords->{$value})) {
          $active_ctx->keywords->{$value}[] = $term;
          usort($active_ctx->keywords->{$value},
            array($this, '_compareShortestLeast'));
        }
      }
      else {
        // expand value to a full IRI
        $value = $this->_expandIri(
          $active_ctx, $value, array('base' => true), $local_ctx, $defined);
      }

      // define/redefine term to expanded IRI/keyword
      $active_ctx->mappings->{$term} = (object)array(
        '@id' => $value, 'propertyGenerator' => false);
      $defined->{$term} = true;
      return;
    }

    if(!is_object($value)) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; @context property values must be ' .
        'strings or objects.',
        'jsonld.SyntaxError', array('context' => $local_ctx));
    }

    // create new mapping
    $mapping = new stdClass();
    $mapping->propertyGenerator = false;

    // merge onto parent mapping if one exists for a prefix
    if($prefix !== null &&
      property_exists($active_ctx->mappings, $prefix) &&
      $active_ctx->mappings->{$prefix} !== null) {
      $mapping = self::copy($active_ctx->mappings->{$prefix});
    }

    if(property_exists($value, '@id')) {
      $id = $value->{'@id'};
      // handle property generator
      if(is_array($id)) {
        if($active_ctx->namer === null) {
          throw new JsonLdException(
            'Incompatible JSON-LD options; a property generator was found ' .
            'in the @context, but blank node renaming has been disabled; ' .
            'it must be enabled to use property generators.',
            'jsonld.OptionsError', array('context' => $local_ctx));
        }

        $property_generator = array();
        $ids = $id;
        foreach($ids as $id) {
          if(!is_string($id)) {
            throw new JsonLdException(
              'Invalid JSON-LD syntax; property generators must consist of ' .
              'an @id array containing only strings.',
              'jsonld.SyntaxError', array('context' => $local_ctx));
          }
          // expand @id if it is not @type
          if($id !== '@type') {
            $id = $this->_expandIri(
              $active_ctx, $id, array('base' => true), $local_ctx, $defined);
          }
          $property_generator[] = $id;
        }
        // add sorted property generator as @id in mapping
        sort($property_generator);
        $mapping->{'@id'} = $property_generator;
        $mapping->propertyGenerator = true;
      }
      else if(!is_string($id)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @id value must be an array ' .
          'of strings or a string.',
          'jsonld.SyntaxError', array('context' => $local_ctx));
      }
      else {
        // add @id to mapping, expanding it if it is not @type
        if($id !== '@type') {
          // expand @id to full IRI
          $id = $this->_expandIri(
            $active_ctx, $id, array('base' => true), $local_ctx, $defined);
        }
        $mapping->{'@id'} = $id;
      }
    }
    else {
      if($prefix === null) {
        //   non-IRIs *must* define @ids if @vocab is not available
        if(!property_exists($active_ctx, '@vocab')) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; @context terms must define an @id.',
            'jsonld.SyntaxError',
            array('context' => $local_ctx, 'term' => $term));
        }
        // prepend vocab to term
        $mapping->{'@id'} = $active_ctx->{'@vocab'} . $term;
      }
      // set @id based on prefix parent
      else if(property_exists($active_ctx->mappings, $prefix)) {
        $suffix = substr($term, $colon + 1);
        $mapping->{'@id'} = $active_ctx->mappings->{$prefix}->{'@id'} . $suffix;
      }
      // term is an absolute IRI
      else {
        $mapping->{'@id'} = $term;
      }
    }

    if(property_exists($value, '@type')) {
      $type = $value->{'@type'};
      if(!is_string($type)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @type values must be strings.',
          'jsonld.SyntaxError', array('context' => $local_ctx));
      }

      if($type !== '@id') {
        // expand @type to full IRI
        $type = $this->_expandIri(
          $active_ctx, $type, array('vocab' => true, 'base' => true),
          $local_ctx, $defined);
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
          'jsonld.SyntaxError', array('context' => $local_ctx));
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
          'a string or null.',
          'jsonld.SyntaxError', array('context' => $local_ctx));
      }

      // add @language to mapping
      if($language !== null) {
        $language = strtolower($language);
      }
      $mapping->{'@language'} = $language;
    }

    // define term mapping
    $active_ctx->mappings->{$term} = $mapping;
    $defined->{$term} = true;
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
    // nothing to expand
    if($value === null) {
      return null;
    }

    // define term dependency if not defined
    if($local_ctx !== null && property_exists($local_ctx, $value) &&
      !self::_hasKeyValue($defined, $value, true)) {
      $this->_createTermDefinition($active_ctx, $local_ctx, $value, $defined);
    }

    if(property_exists($active_ctx->mappings, $value)) {
      $mapping = $active_ctx->mappings->{$value};

      // value is explicitly ignored with a null mapping
      if($mapping === null) {
        return null;
      }
    }
    else {
      $mapping = null;
    }

    // term dependency cannot be a property generator
    if($local_ctx !== null && $mapping && $mapping->propertyGenerator) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; a term definition cannot have a property ' .
        'generator as a dependency.',
        'jsonld.SyntaxError',
        array('context' => $local_ctx, 'value' => $value));
    }

    $is_absolute = false;
    $rval = $value;

    // value is a term
    if($mapping && !$mapping->propertyGenerator) {
      $is_absolute = true;
      $rval = $mapping->{'@id'};
    }

    // keywords need no expanding (aliasing already handled by now)
    if(self::_isKeyword($rval)) {
      return $rval;
    }

    if(!$is_absolute) {
      // split value into prefix:suffix
      $colon = strpos($rval, ':');
      if($colon !== false) {
        $is_absolute = true;
        $prefix = substr($rval, 0, $colon);
        $suffix = substr($rval, $colon + 1);

        // do not expand blank nodes (prefix of '_') or already-absolute
        // IRIs (suffix of '//')
        if($prefix !== '_' && strpos($suffix, '//') !== 0) {
          // prefix dependency not defined, define it
          if($local_ctx !== null && property_exists($local_ctx, $prefix) &&
            !self::_hasKeyValue($defined, $prefix, true)) {
            $this->_createTermDefinition(
              $active_ctx, $local_ctx, $prefix, $defined);
          }

          // use mapping if prefix is defined and not a property generator
          if(property_exists($active_ctx->mappings, $prefix)) {
            $mapping = $active_ctx->mappings->{$prefix};
            if($mapping && !$mapping->propertyGenerator) {
              $rval = $active_ctx->mappings->{$prefix}->{'@id'} . $suffix;
            }
          }
        }
      }
    }

    if($is_absolute) {
      // rename blank node if requested
      if(!$local_ctx && strpos($rval, '_:') === 0 &&
        $active_ctx->namer !== null) {
        $rval = $active_ctx->namer->getName($rval);
      }
    }
    // prepend vocab
    else if(isset($relative_to['vocab']) && $relative_to['vocab'] &&
      property_exists($active_ctx, '@vocab')) {
      $rval = $active_ctx->{'@vocab'} . $rval;
    }
    // prepend base
    else if(isset($relative_to['base']) && $relative_to['base']) {
      $rval = jsonld_prepend_base($active_ctx->{'@base'}, $rval);
    }

    if($local_ctx) {
      // value must now be an absolute IRI
      if(!self::_isAbsoluteIri($rval)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; a @context value does not expand to ' .
          'an absolute IRI.',
          'jsonld.SyntaxError',
          array('context' => $local_ctx, 'value' => $value));
      }
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
    }
    else if(is_object($input)) {
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
                  $i += count($ctx);
                  $length += count($ctx);
                }
                else {
                  $v[$i] = $ctx;
                }
              }
              // @context URL found
              else if(!property_exists($urls, $url)) {
                $urls->{$url} = false;
              }
            }
          }
        }
        // string @context
        else if(is_string($v)) {
          $v = jsonld_prepend_base($base, $v);
          // replace w/@context if requested
          if($replace) {
            $input->{$k} = $urls->{$v};
          }
          // @context URL found
          else if(!property_exists($urls, $v)) {
            $urls->{$v} = false;
          }
        }
      }
    }
  }

  /**
   * Resolves external @context URLs using the given URL resolver. Each
   * instance of @context in the input that refers to a URL will be replaced
   * with the JSON @context found at that URL.
   *
   * @param mixed $input the JSON-LD input with possible contexts.
   * @param stdClass $cycles an object for tracking context cycles.
   * @param callable $resolver(url) the URL resolver.
   * @param base $base the base URL to resolve relative URLs against.
   *
   * @return mixed the result.
   */
  protected function _resolveContextUrls(
    &$input, $cycles, $resolver, $base='') {
    if(count(get_object_vars($cycles)) > self::MAX_CONTEXT_URLS) {
      throw new JsonLdException(
        'Maximum number of @context URLs exceeded.',
        'jsonld.ContextUrlError', array('max' => self::MAX_CONTEXT_URLS));
    }

    // for tracking the URLs to resolve
    $urls = new stdClass();

    // find all URLs in the given input
    $this->_findContextUrls($input, $urls, false, $base);

    // queue all unresolved URLs
    $queue = array();
    foreach($urls as $url => $ctx) {
      if($ctx === false) {
        $queue[] = $url;
      }
    }

    // resolve URLs in queue
    foreach($queue as $url) {
      // check for context URL cycle
      if(property_exists($cycles, $url)) {
        throw new JsonLdException(
          'Cyclical @context URLs detected.',
          'jsonld.ContextUrlError', array('url' => $url));
      }
      $_cycles = self::copy($cycles);
      $_cycles->{$url} = true;

      // resolve URL
      $ctx = $resolver($url);

      // parse string context as JSON
      if(is_string($ctx)) {
        $ctx = json_decode($ctx);
        switch(json_last_error()) {
          case JSON_ERROR_NONE:
            break;
          case JSON_ERROR_DEPTH:
            throw new JsonLdException(
            'Could not parse JSON from URL; the maximum stack depth has ' .
            'been exceeded.', 'jsonld.ParseError', array('url' => $url));
          case JSON_ERROR_STATE_MISMATCH:
            throw new JsonLdException(
            'Could not parse JSON from URL; invalid or malformed JSON.',
            'jsonld.ParseError', array('url' => $url));
          case JSON_ERROR_CTRL_CHAR:
          case JSON_ERROR_SYNTAX:
            throw new JsonLdException(
            'Could not parse JSON from URL; syntax error, malformed JSON.',
            'jsonld.ParseError', array('url' => $url));
          case JSON_ERROR_UTF8:
            throw new JsonLdException(
            'Could not parse JSON from URL; malformed UTF-8 characters.',
            'jsonld.ParseError', array('url' => $url));
          default:
            throw new JsonLdException(
            'Could not parse JSON from URL; unknown error.',
            'jsonld.ParseError', array('url' => $url));
        }
      }

      // ensure ctx is an object
      if(!is_object($ctx)) {
        throw new JsonLdException(
          'URL does not resolve to a valid JSON-LD object.',
          'jsonld.InvalidUrl', array('url' => $url));
      }

      // use empty context if no @context key is present
      if(!property_exists($ctx, '@context')) {
        $ctx = (object)array('@context' => new stdClass());
      }

      // recurse
      $this->_resolveContextUrls($ctx, $_cycles, $resolver, $url);
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
    $namer = null;
    if(isset($options['renameBlankNodes']) && $options['renameBlankNodes']) {
      $namer = new UniqueNamer('_:t');
    }
    return (object)array(
      '@base' => jsonld_parse_url($options['base']),
      'mappings' => new stdClass(),
      'keywords' => (object)array(
        '@context' => array(),
        '@container' => array(),
        '@default' => array(),
        '@embed' => array(),
        '@explicit' => array(),
        '@graph' => array(),
        '@id' => array(),
        '@index' => array(),
        '@language' => array(),
        '@list' => array(),
        '@omitDefault' => array(),
        '@preserve' => array(),
        '@set' => array(),
        '@type' => array(),
        '@value' => array(),
        '@vocab' => array()),
      'namer' => $namer,
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
    $inverse = $active_ctx->inverse = new stdClass();

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

      // iterate over every IRI in the mapping
      $iris = $mapping->{'@id'};
      $iris = self::arrayify($iris);
      foreach($iris as $iri) {
        // initialize container map
        if(!property_exists($inverse, $iri)) {
          $inverse->{$iri} = new stdClass();
        }
        $container_map = $inverse->{$iri};

        // add term selection where it applies
        if(property_exists($mapping, '@container')) {
          $container = $mapping->{'@container'};
        }
        else {
          $container = '@none';
        }

        // add new entry
        if(!property_exists($container_map, $container)) {
          $container_map->{$container} = (object)array(
            '@language' => new stdClass(),
            '@type' => new stdClass());
          $container_map->{$container}->{'@language'}->{$default_language} =
            (object)array('term' => null, 'propertyGenerators' => array());
        }
        $entry = $container_map->{$container};

        // consider updating @language entry if @type is not specified
        if(!property_exists($mapping, '@type')) {
          // if a @language is specified, update its specific entry
          if(property_exists($mapping, '@language')) {
            $language = $mapping->{'@language'};
            if($language === null) {
              $language = '@null';
            }
            $this->_addPreferredTerm(
              $mapping, $term, $entry->{'@language'}, $language);
          }
          // add an entry for the default language and for no @language
          else {
            $this->_addPreferredTerm(
              $mapping, $term, $entry->{'@language'}, $default_language);
            $this->_addPreferredTerm(
              $mapping, $term, $entry->{'@language'}, '@none');
          }
        }

        // consider updating @type entry if @language is not specified
        if(!property_exists($mapping, '@language')) {
          if(property_exists($mapping, '@type')) {
            $type = $mapping->{'@type'};
          }
          else {
            $type = '@none';
          }
          $this->_addPreferredTerm($mapping, $term, $entry->{'@type'}, $type);
        }
      }
    }

    return $inverse;
  }

  /**
   * Adds or updates the term or property generator for the given entry.
   *
   * @param stdClass $mapping the term mapping.
   * @param string $term the term to add.
   * @param stdClass $entry the inverse context type_or_language entry to
   *          add to.
   * @param string $type_or_language_value the key in the entry to add to.
   */
  function _addPreferredTerm($mapping, $term, $entry, $type_or_language_value) {
    if(!property_exists($entry, $type_or_language_value)) {
      $entry->{$type_or_language_value} = (object)array(
        'term' => null, 'propertyGenerators' => array());
    }

    $e = $entry->{$type_or_language_value};
    if($mapping->propertyGenerator) {
      $e->propertyGenerators[] = $term;
    }
    else if($e->term === null) {
      $e->term = $term;
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
    $child->keywords = self::copy($active_ctx->keywords);
    $child->mappings = self::copy($active_ctx->mappings);
    $child->namer = $active_ctx->namer;
    $child->inverse = null;
    return $child;
  }

  /**
   * Returns a copy of this active context that can be shared between
   * different processing algorithms. This method only copies the parts
   * of the active context that can't be shared.
   *
   * @param stdClass $active_ctx the active context to use.
   *
   * @return stdClass a shareable copy of the active context.
   */
  public function _shareActiveContext($active_ctx) {
    $rval = new stdClass();
    $rval->{'@base'} = $active_ctx->{'@base'};
    $rval->keywords = $active_ctx->keywords;
    $rval->mappings = $active_ctx->mappings;
    if($active_ctx->namer !== null) {
      $rval->namer = clone $active_ctx->namer;
    }
    $rval->inverse = $active_ctx->inverse;
    return $rval;
  }

  /**
   * Compares two RDF statements for equality.
   *
   * @param stdClass $s1 the first statement.
   * @param stdClass $s2 the second statement.
   *
   * @return true if the statements are the same, false if not.
   */
  protected static function _compareRdfStatements($s1, $s2) {
    if(is_string($s1) || is_string($s2)) {
      return $s1 === $s2;
    }

    $attrs = array('subject', 'property', 'object');
    foreach($attrs as $attr) {
      if($s1->{$attr}->interfaceName !== $s2->{$attr}->interfaceName ||
      $s1->{$attr}->nominalValue !== $s2->{$attr}->nominalValue) {
        return false;
      }
    }
    if(property_exists($s1->object, 'language') !==
        property_exists($s1->object, 'language')) {
      return false;
    }
    if(property_exists($s1->object, 'language')) {
      if($s1->object->language !== $s2->object->language) {
        return false;
      }
    }
    if(property_exists($s1->object, 'datatype') !==
        property_exists($s1->object, 'datatype')) {
      return false;
    }
    if(property_exists($s1->object, 'datatype')) {
      if($s1->object->datatype->interfaceName !==
          $s2->object->datatype->interfaceName ||
          $s1->object->datatype->nominalValue !==
          $s2->object->datatype->nominalValue) {
        return false;
      }
    }
    if(property_exists($s1, 'name') !== property_exists($s1, 'name')) {
      return false;
    }
    if(property_exists($s1, 'name')) {
      if($s1->name !== $s2->name) {
        return false;
      }
    }
    return true;
  }

  /**
   * Appends an RDF statement to the given array of statements if it is unique.
   *
   * @param array $statements the array to add to.
   * @param stdClass $statement the statement to add.
   */
  protected static function _appendUniqueRdfStatement(
    &$statements, $statement) {
    foreach($statements as $s) {
      if(self::_compareRdfStatements($s, $statement)) {
        return;
      }
    }
    $statements[] = $statement;
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
        'jsonld.SyntaxError', array('value' => $v));
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
      }
      else {
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
  // FIXME: use this function throughout processor, check for "property_exists"
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
}

// register the N-Quads RDF parser
jsonld_register_rdf_parser(
  'application/nquads', 'JsonLdProcessor::parseNQuads');

/**
 * A JSON-LD Exception.
 */
class JsonLdException extends Exception {
  protected $type;
  protected $details;
  protected $cause;
  public function __construct($msg, $type, $details=null, $previous=null) {
    $this->type = $type;
    $this->details = $details;
    $this->cause = $previous;
    parent::__construct($msg, 0, $previous);
  }
  public function __toString() {
    $rval = __CLASS__ . ": [{$this->type}]: {$this->message}\n";
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
    }
    else {
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
        // get shareable copy of cached active context
        return JsonLdProcessor::_shareActiveContext($level1->{$key2});
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
    if(!property_exists($this->cache)) {
      $this->cache->{$key1} = new stdClass();
    }
    $this->cache->{$key1}->{$key2} = $result;
  }
}

/* end of file, omit ?> */
