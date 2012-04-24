<?php
/**
 * PHP implementation of the JSON-LD API.
 *
 * @author Dave Longley
 *
 * BSD 3-Clause License
 * Copyright (c) 2011-2012 Digital Bazaar, Inc.
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
 *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
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
 *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
 *
 * @return array the expanded JSON-LD output.
 */
function jsonld_expand($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->expand($input, $options);
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
 *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
 *
 * @return stdClass the framed JSON-LD output.
 */
function jsonld_frame($input, $frame, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->frame($input, $frame, $options);
}

/**
 * Performs JSON-LD normalization.
 *
 * @param mixed $input the JSON-LD object to normalize.
 * @param assoc [$options] the options to use:
 *          [base] the base IRI to use.
 *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
 *
 * @return array the normalized JSON-LD output.
 */
function jsonld_normalize($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->normalize($input, $options);
}

/**
 * Outputs the RDF statements found in the given JSON-LD object.
 *
 * @param mixed $input the JSON-LD object.
 * @param assoc [$options] the options to use:
 *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
 *
 * @return array all RDF statements in the JSON-LD object.
 */
function jsonld_to_rdf($input, $options=array()) {
  $p = new JsonLdProcessor();
  return $p->toRdf($input, $options);
}

/**
 * A JSON-LD processor.
 */
class JsonLdProcessor {
  /** XSD constants */
  const XSD_BOOLEAN = 'http://www.w3.org/2001/XMLSchema#boolean';
  const XSD_DOUBLE = 'http://www.w3.org/2001/XMLSchema#double';
  const XSD_INTEGER = 'http://www.w3.org/2001/XMLSchema#integer';

  /** RDF constants */
  const RDF_FIRST = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#first';
  const RDF_REST = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#rest';
  const RDF_NIL = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#nil';
  const RDF_TYPE = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type';

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
   *          [activeCtx] true to also return the active context used.
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
    isset($options['strict']) or $options['strict'] = true;
    isset($options['optimize']) or $options['optimize'] = false;
    isset($options['graph']) or $options['graph'] = false;
    isset($options['activeCtx']) or $options['activeCtx'] = false;
    // FIXME: implement jsonld_resolve_url
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    // expand input
    try {
      $expanded = $this->expand($input, $options);
    }
    catch(JsonLdException $e) {
      throw new JsonLdException(
        'Could not expand input before compaction.',
        'jsonld.CompactError', null, $e);
    }

    // process context
    $active_ctx = $this->_getInitialContext();
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

    // always use an array if graph options is on
    if($options['graph'] === true) {
      $compacted = self::arrayify($compacted);
    }
    // else if compacted is an array with 1 entry, remove array
    else if(is_array($compacted) && count($compacted) === 1) {
      $compacted = $compacted[0];
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
    foreach($tmp as $i => $v) {
      if(!is_object($v) || count(get_object_vars($v)) > 0) {
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
      return array(
        'compacted' => $compacted,
        'activeCtx' => $active_ctx);
    }
    else {
      return $compacted;
    }
  }

  /**
   * Performs JSON-LD expansion.
   *
   * @param mixed $input the JSON-LD object to expand.
   * @param assoc $options the options to use:
   *          [base] the base IRI to use.
   *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
   *
   * @return array the expanded JSON-LD output.
   */
  public function expand($input, $options) {
    // set default options
    isset($options['base']) or $options['base'] = '';
    // FIXME: implement jsonld_resolve_url
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    // resolve all @context URLs in the input
    $input = self::copy($input);
    $this->_resolveUrls($input, $options['resolver']);

    // do expansion
    $ctx = $this->_getInitialContext();
    $expanded = $this->_expand($ctx, null, $input, $options, false);

    // optimize away @graph with no other properties
    if(is_object($expanded) && property_exists($expanded, '@graph') &&
      count(get_object_vars($expanded)) === 1) {
      $expanded = $expanded->{'@graph'};
    }
    // normalize to an array
    return self::arrayify($expanded);
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
   *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
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
    // FIXME: implement jsonld_resolve_url
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    // preserve frame context
    $ctx = (property_exists($frame, '@context') ?
      $frame->{'@context'} : new stdClass());

    try {
      // expand input
      $_input = $this->expand($input, $options);
    }
    catch(JsonLdException $e) {
      throw new JsonLdException(
        'Could not expand input before framing.',
        'jsonld.FrameError', $e);
    }

    try {
      // expand frame
      $_frame = $this->expand($frame, $options);
    }
    catch(JsonLdException $e) {
      throw new JsonLdException(
        'Could not expand frame before framing.',
        'jsonld.FrameError', $e);
    }

    // do framing
    $framed = $this->_frame($_input, $_frame, $options);

    try {
      // compact result (force @graph option to true)
      $options['graph'] = true;
      $options['activeCtx'] = true;
      $result = $this->compact($framed, $ctx, $options);
    }
    catch(JsonLdException $e) {
      throw new JsonLdException(
        'Could not compact framed output.',
        'jsonld.FrameError', $e);
    }

    $compacted = $result['compacted'];
    $ctx = $result['activeCtx'];

    // get graph alias
    $graph = $this->_compactIri($ctx, '@graph');
    // remove @preserve from results
    $compacted->{$graph} = $this->_removePreserve($ctx, $compacted->{$graph});
    return $compacted;
  }

  /**
   * Performs JSON-LD normalization.
   *
   * @param mixed $input the JSON-LD object to normalize.
   * @param assoc $options the options to use:
   *          [base] the base IRI to use.
   *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
   *
   * @return array the JSON-LD normalized output.
   */
  public function normalize($input, $options) {
    // set default options
    isset($options['base']) or $options['base'] = '';
    // FIXME: implement jsonld_resolve_url
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    try {
      // expand input then do normalization
      $expanded = $this->expand($input, $options);
    }
    catch(JsonLdException $e) {
      throw new JsonLdException(
        'Could not expand input before normalization.',
        'jsonld.NormalizeError', $e);
    }

    // do normalization
    return $this->_normalize($expanded);
  }

  /**
   * Outputs the RDF statements found in the given JSON-LD object.
   *
   * @param mixed $input the JSON-LD object.
   * @param assoc $options the options to use:
   *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
   *
   * @return array the RDF statements.
   */
  public function toRDF($input, $options) {
    // set default options
    isset($options['base']) or $options['base'] = '';
    // FIXME: implement jsonld_resolve_url
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    // resolve all @context URLs in the input
    $input = self::copy($input);
    $this->_resolveUrls($input, $options['resolver']);

    // output RDF statements
    return $this->_toRDF($input);
  }

  /**
   * Processes a local context, resolving any URLs as necessary, and returns a
   * new active context in its callback.
   *
   * @param stdClass $active_ctx the current active context.
   * @param mixed $local_ctx the local context to process.
   * @param assoc $options the options to use:
   *          [resolver(url, callback(err, jsonCtx))] the URL resolver to use.
   *
   * @return stdClass the new active context.
   */
  public function processContext($active_ctx, $local_ctx) {
    // return initial context early for null context
    if($local_ctx === null) {
      return $this->_getInitialContext();
    }

    // set default options
    isset($options['base']) or $options['base'] = '';
    // FIXME: implement jsonld_resolve_url
    isset($options['resolver']) or $options['resolver'] = 'jsonld_resolve_url';

    // resolve URLs in localCtx
    $local_ctx = self::copy($local_ctx);
    if(is_object($local_ctx) && !property_exists($local_ctx, '@context')) {
      $local_ctx = (object)array('@context' => $local_ctx);
    }
    $ctx = $this->_resolveUrls($local_ctx, $options['resolver']);

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
      $isList = self::_isListValue($val);
      if(is_array($val) || $isList) {
        if($isList) {
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
   * Adds a value to a subject. If the subject already has the value, it will
   * not be added. If the value is an array, all values in the array will be
   * added.
   *
   * Note: If the value is a subject that already exists as a property of the
   * given subject, this method makes no attempt to deeply merge properties.
   * Instead, the value will not be added.
   *
   * @param stdClass $subject the subject to add the value to.
   * @param string $property the property that relates the value to the subject.
   * @param mixed $value the value to add.
   * @param bool [$propertyIsArray] true if the property is always an array,
   *          false if not (default: false).
   */
  public static function addValue(
    $subject, $property, $value, $propertyIsArray=false) {
    if(is_array($value)) {
      if(count($value) === 0 && $propertyIsArray &&
        !property_exists($subject, $property)) {
        $subject->{$property} = array();
      }
      foreach($value as $v) {
        self::addValue($subject, $property, $v, $propertyIsArray);
      }
    }
    else if(property_exists($subject, $property)) {
      $has_value = self::hasValue($subject, $property, $value);

      // make property an array if value not present or always an array
      if(!is_array($subject->{$property}) &&
        (!$has_value || $propertyIsArray)) {
        $subject->{$property} = array($subject->{$property});
      }

      // add new value
      if(!$has_value) {
        $subject->{$property}[] = $value;
      }
    }
    else {
      // add new value as set or single value
      $subject->{$property} = $propertyIsArray ? array($value) : $value;
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
    $rval = $subject->{$property} ?: array();
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
   * @param bool [$propertyIsArray] true if the property is always an array,
   *          false if not (default: false).
   */
  public static function removeValue(
    $subject, $property, $value, $propertyIsArray=false) {

    // filter out value
    $filter = function($e) use ($value) {
      return !self::compareValues($e, $value);
    };
    $values = self::getValues($subject, $property);
    $values = array_filter($values, $filter);

    if(count($values) === 0) {
      self::removeProperty($subject, $property);
    }
    else if(count($values) === 1 && !$propertyIsArray) {
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
   * 2. They are both @values with the same @value, @type, and @language, OR
   * 3. They both have @ids they are the same.
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
    if(self::_isValue($v1) && self::_isValue($v2) &&
      $v1->{'@value'} === $v2->{'@value'} &&
      property_exists($v1, '@type') === property_exists($v2, '@type') &&
      property_exists($v1, '@language') === property_exists($v2, '@language') &&
      (!property_exists($v1, '@type') || $v1->{'@type'} === $v2->{'@type'}) &&
      (!property_exists($v1, '@language') ||
        $v2->{'@language'} === $v2->{'@language'})) {
      return true;
    }

    // 3. equal @ids
    if(is_object($v1) && property_exists($v1, '@id') &&
      is_object($v2) && property_exists($v2, '@id')) {
      return $v1->{'@id'} === $v2->{'@id'};
    }

    return false;
  }

  /**
   * Compares two JSON-LD normalized inputs for equality.
   *
   * @param array $n1 the first normalized input.
   * @param array $n2 the second normalized input.
   *
   * @return bool true if the inputs are equivalent, false if not.
   */
  public static function compareNormalized($n1, $n2) {
    if(!is_array($n1) || !is_array($n2)) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; normalized JSON-LD must be an array.',
        'jsonld.SyntaxError');
    }

    // different # of subjects
    if(count($n1) !== count($n2)) {
      return false;
    }

    // assume subjects are in the same order because of normalization
    foreach($n1 as $i => $s1) {
      $s2 = $n2[$i];

      // different @ids
      if($s1->{'@id'} !== $s2->{'@id'}) {
        return false;
      }

      // subjects have different properties
      if(count(get_object_vars($s1)) !== count(get_object_vars($s2))) {
        return false;
      }

      foreach($s1 as $p => $objects) {
        // skip @id property
        if($p === '@id') {
          continue;
        }

        // s2 is missing s1 property
        if(!self::hasProperty($s2, $p)) {
          return false;
        }

        // subjects have different objects for the property
        if(count($objects) !== count($s2->{$p})) {
          return false;
        }

        foreach($objects as $oi => $o) {
          // s2 is missing s1 object
          if(!self::hasValue($s2, $p, $o)) {
            return false;
          }
        }
      }
    }

    return true;
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
   * If $value is an array, returns $value, otherwise returns an array
   * containing $value as the only element.
   *
   * @param mixed $value the value.
   *
   * @return an array.
   */
  public static function arrayify($value) {
    if(is_array($value)) {
      return $value;
    }
    return array($value);
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
   * @param stdClass $ctx the active context to use.
   * @param mixed $property the property that points to the element, null for
   *          none.
   * @param mixed $element the element to compact.
   * @param array $options the compaction options.
   *
   * @return mixed the compacted value.
   */
  protected function _compact($ctx, $property, $element, $options) {
    // recursively compact array
    if(is_array($element)) {
      $rval = array();
      foreach($element as $e) {
        $e = $this->_compact($ctx, $property, $e, $options);
        // drop null values
        if($e !== null) {
          $rval[] = $e;
        }
      }
      if(count($rval) === 1) {
        // use single element if no container is specified
        $container = self::getContextValue($ctx, $property, '@container');
        if($container !== '@list' && $container !== '@set') {
          $rval = $rval[0];
        }
      }
      return $rval;
    }

    // recursively compact object
    if(is_object($element)) {
      // element is a @value
      if(self::_isValue($element)) {
        $type = self::getContextValue($ctx, $property, '@type');
        $language = self::getContextValue($ctx, $property, '@language');

        // matching @type specified in context, compact element
        if($type !== null &&
          property_exists($element, '@type') && $element->{'@type'} === $type) {
          $element = $element->{'@value'};

          // use native datatypes for certain xsd types
          if($type === self::XSD_BOOLEAN) {
            $element = !($element === 'false' || $element === '0');
          }
          else if($type === self::XSD_INTEGER) {
            $element = intval($element);
          }
          else if($type === self::XSD_DOUBLE) {
            $element = doubleval($element);
          }
        }
        // matching @language specified in context, compact element
        else if($language !== null &&
          property_exists($element, '@language') &&
          $element->{'@language'} === $language) {
          $element = $element->{'@value'};
        }
        // compact @type IRI
        else if(property_exists($element, '@type')) {
          $element->{'@type'} = $this->_compactIri($ctx, $element->{'@type'});
        }
        return $element;
      }

      // compact subject references
      if(self::_isSubjectReference($element)) {
        $type = self::getContextValue($ctx, $property, '@type');
        if($type === '@id') {
          $element = $this->_compactIri($ctx, $element->{'@id'});
          return $element;
        }
      }

      // recursively process element keys
      $rval = new stdClass();
      foreach($element as $key => $value) {
        // compact @id and @type(s)
        if($key === '@id' || $key === '@type') {
          // compact single @id
          if(is_string($value)) {
            $value = $this->_compactIri($ctx, $value);
          }
          // value must be a @type array
          else {
            $types = array();
            foreach($value as $v) {
              $types[] = $this->_compactIri($ctx, $v);
            }
            $value = $types;
          }

          // compact property and add value
          $prop = $this->_compactIri($ctx, $key);
          $isArray = (is_array($value) && count($value) === 0);
          self::addValue($rval, $prop, $value, $isArray);
          continue;
        }

        // Note: value must be an array due to expansion algorithm.

        // preserve empty arrays
        if(count($value) === 0) {
          $prop = $this->_compactIri($ctx, $key);
          self::addValue($rval, $prop, array(), true);
        }

        // recusively process array values
        foreach($value as $v) {
          $isList = self::_isListValue($v);

          // compact property
          $prop = $this->_compactIri($ctx, $key, $v);

          // remove @list for recursion (will be re-added if necessary)
          if($isList) {
            $v = $v->{'@list'};
          }

          // recursively compact value
          $v = $this->_compact($ctx, $prop, $v, $options);

          // get container type for property
          $container = self::getContextValue($ctx, $prop, '@container');

          // handle @list
          if($isList && $container !== '@list') {
            // handle messy @list compaction
            if(property_exists($rval, $prop) && $options['strict']) {
              throw new JsonLdException(
                'JSON-LD compact error; property has a "@list" @container ' +
                'rule but there is more than a single @list that matches ' +
                'the compacted term in the document. Compaction might mix ' +
                'unwanted items into the list.',
                'jsonld.SyntaxError');
            }
            // reintroduce @list keyword
            $kwlist = $this->_compactIri($ctx, '@list');
            $val = new stdClass();
            $val->{$kwlist} = $v;
            $v = $val;
          }

          // if @container is @set or @list or value is an empty array, use
          // an array when adding value
          $isArray = ($container === '@set' || $container === '@list' ||
            (is_array($v) && count($v) === 0));

          // add compact value
          self::addValue($rval, $prop, $v, $isArray);
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
   * @param stdClass $ctx the context to use.
   * @param mixed $property the property for the element, null for none.
   * @param mixed $element the element to expand.
   * @param array $options the expansion options.
   * @param bool $propertyIsList true if the property is a list, false if not.
   *
   * @return mixed the expanded value.
   */
  protected function _expand(
    $ctx, $property, $element, $options, $propertyIsList) {
    // recursively expand array
    if(is_array($element)) {
      $rval = array();
      foreach($element as $e) {
        // expand element
        $e = $this->_expand($ctx, $property, $e, $options, $propertyIsList);
        if(is_array($e) && $propertyIsList) {
          // lists of lists are illegal
          throw new JsonLdException(
            'Invalid JSON-LD syntax; lists of lists are not permitted.',
            'jsonld.SyntaxError');
        }
        // drop null values
        else if($e !== null) {
          $rval[] = $e;
        }
      }
      return $rval;
    }

    // recursively expand object
    if(is_object($element)) {
      // if element has a context, process it
      if(property_exists($element, '@context')) {
        $ctx = $this->_processContext($ctx, $element->{'@context'}, $options);
        unset($element->{'@context'});
      }

      $rval = new stdClass();
      foreach($element as $key => $value) {
        // expand property
        $prop = $this->_expandTerm($ctx, $key);

        // drop non-absolute IRI keys that aren't keywords
        if(!self::_isAbsoluteIri($prop) && !self::_isKeyword($prop, $ctx)) {
          continue;
        }

        // if value is null and property is not @value, continue
        $value = $element->{$key};
        if($value === null && $prop !== '@value') {
          continue;
        }

        // syntax error if @id is not a string
        if($prop === '@id' && !is_string($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@id" value must a string.',
            'jsonld.SyntaxError', array('value' => $value));
        }

        // @type must be a string, array of strings, or an empty JSON object
        if($prop === '@type' &&
          !(is_string($value) || self::_isArrayOfStrings($value) ||
          self::_isEmptyObject($value))) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@type" value must a string, an array ' +
            'of strings, or an empty object.',
            'jsonld.SyntaxError', array('value' => $value));
        }

        // @graph must be an array or an object
        if($prop === '@graph' && !(is_object($value) || is_array($value))) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@value" value must not be an ' +
            'object or an array.',
            'jsonld.SyntaxError', array('value' => $value));
        }

        // @value must not be an object or an array
        if($prop === '@value' && (is_object($value) || is_array($value))) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@value" value must not be an ' +
            'object or an array.',
            'jsonld.SyntaxError', array('value' => $value));
        }

        // @language must be a string
        if($prop === '@language' && !is_string($value)) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; "@language" value must not be a string.',
            'jsonld.SyntaxError', array('value' => $value));
        }

        // recurse into @list, @set, or @graph, keeping the active property
        $isList = ($prop === '@list');
        if($isList || $prop === '@set' || $prop === '@graph') {
          $value = $this->_expand($ctx, $property, $value, $options, $isList);
          if($isList && self::_isListValue($value)) {
            throw new JsonLdException(
              'Invalid JSON-LD syntax; lists of lists are not permitted.',
              'jsonld.SyntaxError');
          }
        }
        else {
          // update active property and recursively expand value
          $property = $key;
          $value = $this->_expand($ctx, $property, $value, $options, false);
        }

        // drop null values if property is not @value (dropped below)
        if($value !== null || $prop === '@value') {
          // convert value to @list if container specifies it
          if($prop !== '@list' && !self::_isListValue($value)) {
            $container = self::getContextValue($ctx, $property, '@container');
            if($container === '@list') {
              // ensure value is an array
              $value = (object)array('@list' => self::arrayify($value));
            }
          }

          // add value, use an array if not @id, @type, @value, or @language
          $useArray = !($prop === '@id' || $prop === '@type' ||
            $prop === '@value' || $prop === '@language');
          self::addValue($rval, $prop, $value, $useArray);
        }
      }

      // get property count on expanded output
      $count = count(get_object_vars($rval));

      // @value must only have @language or @type
      if(property_exists($rval, '@value')) {
        if(($count === 2 && !property_exists($rval, '@type') &&
          !property_exists($rval, '@language')) ||
          $count > 2) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; an element containing "@value" must have ' +
            'at most one other property which can be "@type" or "@language".',
            'jsonld.SyntaxError', array('element' => $rval));
        }
        // value @type must be a string
        if(property_exists($rval, '@type') && !is_string($rval->{'@type'})) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; the "@type" value of an element ' +
            'containing "@value" must be a string.',
            'jsonld.SyntaxError', array('element' => $rval));
        }
        // return only the value of @value if there is no @type or @language
        else if($count === 1) {
          $rval = $rval->{'@value'};
        }
        // drop null @values
        else if($rval->{'@value'} === null) {
          $rval = null;
        }
      }
      // convert @type to an array
      else if(property_exists($rval, '@type') && !is_array($rval->{'@type'})) {
        $rval->{'@type'} = array($rval->{'@type'});
      }
      // handle @set and @list
      else if(property_exists($rval, '@set') ||
        property_exists($rval, '@list')) {
        if($count !== 1) {
          throw new JsonLdException(
            'Invalid JSON-LD syntax; if an element has the property "@set" ' +
            'or "@list", then it must be its only property.',
            'jsonld.SyntaxError', array('element' => $rval));
        }
        // optimize away @set
        if(property_exists($rval, '@set')) {
          $rval = $rval->{'@set'};
        }
      }
      // drop objects with only @language
      else if(property_exists($rval, '@language') && $count === 1) {
        $rval = null;
      }

      return $rval;
    }

    // expand element according to value expansion rules
    return $this->_expandValue($ctx, $property, $element, $options['base']);
  }

  /**
   * Performs JSON-LD framing.
   *
   * @param array $input the expanded JSON-LD to frame.
   * @param array $frame the expanded JSON-LD frame to use.
   * @param array $options the framing options.
   *
   * @return array the framed output.
   */
  protected function _frame($input, $frame, $options) {
    // create framing state
    $state = (object)array(
      'options' => $options,
      'subjects' => new stdClass());

    // produce a map of all subjects and name each bnode
    $namer = new UniqueNamer('_:t');
    $this->_flatten($state->subjects, $input, $namer, null, null);

    // frame the subjects
    $framed = new ArrayObject();
    $this->_match_frame(
      $state, array_keys((array)$state->subjects), $frame, $framed, null);
    return (array)$framed;
  }

  /**
   * Performs JSON-LD normalization.
   *
   * @param input the expanded JSON-LD object to normalize.
   *
   * @return the normalized output.
   */
  protected function _normalize($input) {
    // get statements
    $namer = new UniqueNamer('_:t');
    $bnodes = new stdClass();
    $subjects = new stdClass();
    $this->_getStatements($input, $namer, $bnodes, $subjects);

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
        $statements = $bnodes->{$bnode};
        $hash = $this->_hashStatements($statements, $namer);

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
        $results[] = $this->_hashPaths(
          $bnodes, $bnodes->{$bnode}, $namer, $path_namer);
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

    // create JSON-LD array
    $output = array();

    // add all bnodes
    foreach($bnodes as $id => $statements) {
      // add all property statements to bnode
      $bnode = (object)array('@id' => $namer->getName($id));
      foreach($statements as $statement) {
        if($statement->s === '_:a') {
          $z = $this->_getBlankNodeName($statement->o);
          $o = $z ? (object)array('@id' => $namer->getName($z)) : $statement->o;
          self::addValue($bnode, $statement->p, $o, true);
        }
      }
      $output[] = $bnode;
    }

    // add all non-bnodes
    foreach($subjects as $id => $statements) {
      // add all statements to subject
      $subject = (object)array('@id' => $id);
      foreach($statements as $statement) {
        $z = $this->_getBlankNodeName($statement->o);
        $o = $z ? (object)array('@id' => $namer->getName($z)) : $statement->o;
        self::addValue($subject, $statement->p, $o, true);
      }
      $output[] = $subject;
    }

    // sort normalized output by @id
    usort($output, function($a, $b) {
      $a = $a->{'@id'};
      $b = $b->{'@id'};
      return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
    });

    return $output;
  }

  /**
   * Outputs the RDF statements found in the given JSON-LD object.
   *
   * @param mixed $input the JSON-LD object.
   *
   * @return array the RDF statements.
   */
  protected function _toRDF($input) {
    // FIXME: implement
    throw new JsonLdException('Not implemented', 'jsonld.NotImplemented');
  }

  /**
   * Processes a local context and returns a new active context.
   *
   * @param stdClass $active_ctx the current active context.
   * @param mixed $local_ctx the local context to process.
   * @param array $options the context processing options.
   *
   * @return stdClass the new active context.
   */
  protected function _processContext($active_ctx, $local_ctx, $options) {
    // initialize the resulting context
    $rval = self::copy($active_ctx);

    // normalize local context to an array
    $ctxs = self::arrayify($local_ctx);

    // process each context in order
    foreach($ctxs as $ctx) {
      // reset to initial context
      if($ctx === null) {
        $rval = $this->_getInitialContext();
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
      foreach($ctx as $k => $v) {
        $this->_defineContextMapping(
          $rval, $ctx, $k, $options['base'], $defined);
      }
    }

    return $rval;
  }

  /**
   * Expands the given value by using the coercion and keyword rules in the
   * given context.
   *
   * @param stdClass $ctx the active context to use.
   * @param string $property the property the value is associated with.
   * @param mixed $value the value to expand.
   * @param string $base the base IRI to use.
   *
   * @return mixed the expanded value.
   */
  protected function _expandValue($ctx, $property, $value, $base) {
    // default to simple string return value
    $rval = $value;

    // special-case expand @id and @type (skips '@id' expansion)
    $prop = $this->_expandTerm($ctx, $property);
    if($prop === '@id') {
      $rval = $this->_expandTerm($ctx, $value, $base);
    }
    else if($prop === '@type') {
      $rval = $this->_expandTerm($ctx, $value);
    }
    else {
      // get type definition from context
      $type = self::getContextValue($ctx, $property, '@type');

      // do @id expansion
      if($type === '@id') {
        $rval = (object)array('@id' => $this->_expandTerm($ctx, $value, $base));
      }
      // other type
      else if($type !== null) {
        $rval = (object)array('@value' => strval($value), '@type' => $type);
      }
      // check for language tagging
      else {
        $language = self::getContextValue($ctx, $property, '@language');
        if($language !== null) {
          $rval = (object)array(
            '@value' => strval($value), '@language' => $language);
        }
      }
    }

    return $rval;
  }

  /**
   * Recursively gets all statements from the given expanded JSON-LD input.
   *
   * @param mixed $input the valid expanded JSON-LD input.
   * @param UniqueNamer $namer the namer to use when encountering blank nodes.
   * @param stdClass $bnodes the blank node statements map to populate.
   * @param stdClass $subjects the subject statements map to populate.
   * @param mixed [$name] the name (@id) assigned to the current input.
   */
  protected function _getStatements(
    $input, $namer, $bnodes, $subjects, $name=null) {
    // recurse into arrays
    if(is_array($input)) {
      foreach($input as $e) {
        $this->_getStatements($e, $namer, $bnodes, $subjects);
      }
    }
    // safe to assume input is a subject/blank node
    else {
      $isBnode = self::_isBlankNode($input);

      // name blank node if appropriate, use passed name if given
      if($name === null) {
        if(property_exists($input, '@id')) {
          $name = $input->{'@id'};
        }
        if($isBnode) {
          $name = $namer->getName($name);
        }
      }

      // use a subject of '_:a' for blank node statements
      $s = $isBnode ? '_:a' : $name;

      // get statements for the blank node
      $entries;
      if($isBnode) {
        if(!property_exists($bnodes, $name)) {
          $entries = $bnodes->{$name} = new ArrayObject();
        }
        else {
          $entries = $bnodes->{$name};
        }
      }
      else if(!property_exists($subjects, $name)) {
        $entries = $subjects->{$name} = new ArrayObject();
      }
      else {
        $entries = $subjects->{$name};
      }

      // add all statements in input
      foreach($input as $p => $objects) {
        // skip @id
        if($p === '@id') {
          continue;
        }

        // convert @lists into embedded blank node linked lists
        foreach($objects as $i => $o) {
          if(self::_isListValue($o)) {
            $objects[$i] = $this->_makeLinkedList($o);
          }
        }

        foreach($objects as $o) {
          // convert boolean to @value
          if(is_bool($o)) {
            $o = (object)array(
              '@value' => ($o ? 'true' : 'false'),
              '@type' => self::XSD_BOOLEAN);
          }
          // convert double to @value
          else if(is_double($o)) {
            // do special JSON-LD double format, printf('%1.16e') equivalent
            $o = preg_replace('/(e(?:\+|-))([0-9])$/', '${1}0${2}',
              sprintf('%1.16e', $o));
            $o = (object)array('@value' => $o, '@type' => self::XSD_DOUBLE);
          }
          // convert integer to @value
          else if(is_integer($o)) {
            $o = (object)array(
              '@value' => strval($o), '@type' => self::XSD_INTEGER);
          }

          // object is a blank node
          if(self::_isBlankNode($o)) {
            // name object position blank node
            $o_name = property_exists($o, '@id') ? $o->{'@id'} : null;
            $o_name = $namer->getName($o_name);

            // add property statement
            $this->_addStatement($entries, (object)array(
              's' => $s, 'p' => $p, 'o' => (object)array('@id' => $o_name)));

            // add reference statement
            if(!property_exists($bnodes, $o_name)) {
              $o_entries = $bnodes->{$o_name} = new ArrayObject();
            }
            else {
              $o_entries = $bnodes->{$o_name};
            }
            $this->_addStatement(
              $o_entries, (object)array(
                's' => $name, 'p' => $p, 'o' => (object)array('@id' => '_:a')));

            // recurse into blank node
            $this->_getStatements($o, $namer, $bnodes, $subjects, $o_name);
          }
          // object is a string, @value, subject reference
          else if(is_string($o) || self::_isValue($o) ||
            self::_isSubjectReference($o)) {
            // add property statement
            $this->_addStatement($entries, (object)array(
              's' => $s, 'p' => $p, 'o' => $o));

            // ensure a subject entry exists for subject reference
            if(self::_isSubjectReference($o) &&
              !property_exists($subjects, $o->{'@id'})) {
              $subjects->{$o->{'@id'}} = new ArrayObject();
            }
          }
          // object must be an embedded subject
          else {
            // add property statement
            $this->_addStatement($entries, (object)array(
              's' => $s, 'p' => $p, 'o' => (object)array(
                '@id' => $o->{'@id'})));

            // recurse into subject
            $this->_getStatements($o, $namer, $bnodes, $subjects);
          }
        }
      }
    }
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
    // convert @list array into embedded blank node linked list
    $list = $value->{'@list'};

    // build linked list in reverse
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
   * Adds a statement to an array of statements. If the statement already exists
   * in the array, it will not be added.
   *
   * @param ArrayObject $statements the statements array.
   * @param stdClass $statement the statement to add.
   */
  protected function _addStatement($statements, $statement) {
    foreach($statements as $s) {
      if($s->s === $statement->s && $s->p === $statement->p &&
        self::compareValues($s->o, $statement->o)) {
        return;
      }
    }
    $statements[] = $statement;
  }

  /**
   * Hashes all of the statements about a blank node.
   *
   * @param ArrayObject $statements the statements about the bnode.
   * @param UniqueNamer $namer the canonical bnode namer.
   *
   * @return string the new hash.
   */
  protected function _hashStatements($statements, $namer) {
    // serialize all statements
    $triples = array();
    foreach($statements as $statement) {
      // serialize triple
      $triple = '';

      // serialize subject
      if($statement->s === '_:a') {
        $triple .= '_:a';
      }
      else if(strpos($statement->s, '_:') === 0) {
        $id = $statement->s;
        $id = $namer->isNamed($id) ? $namer->getName($id) : '_:z';
        $triple .= $id;
      }
      else {
        $triple .= '<' . $statement->s . '>';
      }

      // serialize property
      $p = ($statement->p === '@type') ? self::RDF_TYPE : $statement->p;
      $triple .= ' <' . $p . '> ';

      // serialize object
      if(self::_isBlankNode($statement->o)) {
        if($statement->o->{'@id'} === '_:a') {
          $triple .= '_:a';
        }
        else {
          $id = $statement->o->{'@id'};
          $id = $namer->isNamed($id) ? $namer->getName($id) : '_:z';
          $triple .= $id;
        }
      }
      else if(is_string($statement->o)) {
        $triple .= '"' . $statement->o . '"';
      }
      else if(self::_isSubjectReference($statement->o)) {
        $triple .= '<' . $statement->o->{'@id'} . '>';
      }
      // must be a value
      else {
        $triple .= '"' . $statement->o->{'@value'} . '"';

        if(property_exists($statement->o, '@type')) {
          $triple .= '^^<' . $statement->o->{'@type'} . '>';
        }
        else if(property_exists($statement->o, '@language')) {
          $triple .= '@' . $statement->o{'@language'};
        }
      }

      // add triple
      $triples[] = $triple;
    }

    // sort serialized triples
    sort($triples);

    // return hashed triples
    return sha1(implode($triples));
  }

  /**
   * Produces a hash for the paths of adjacent bnodes for a bnode,
   * incorporating all information about its subgraph of bnodes. This
   * method will recursively pick adjacent bnode permutations that produce the
   * lexicographically-least 'path' serializations.
   *
   * @param stdClass $bnodes the map of bnode statements.
   * @param ArrayObject $statements the statements for the bnode to produce
   *          the hash for.
   * @param UniqueNamer $namer the canonical bnode namer.
   * @param UniqueNamer $path_namer the namer used to assign names to adjacent
   *          bnodes.
   *
   * @return stdClass the hash and path namer used.
   */
  protected function _hashPaths($bnodes, $statements, $namer, $path_namer) {
    // create SHA-1 digest
    $md = hash_init('sha1');

    // group adjacent bnodes by hash, keep properties and references separate
    $groups = new stdClass();
    $cache = new stdClass();
    foreach($statements as $statement) {
      if($statement->s !== '_:a' && strpos($statement->s, '_:') === 0) {
        $bnode = $statement->s;
        $direction = 'p';
      }
      else {
        $bnode = $this->_getBlankNodeName($statement->o);
        $direction = 'r';
      }

      if($bnode !== null) {
        // get bnode name (try canonical, path, then hash)
        if($namer->isNamed($bnode)) {
          $name = $namer->getName($bnode);
        }
        else if($path_namer->isNamed($bnode)) {
          $name = $path_namer->getName($bnode);
        }
        else if(property_exists($cache, $bnode)) {
          $name = $cache->{$bnode};
        }
        else {
          $name = $this->_hashStatements($bnodes->{$bnode}, $namer);
          $cache->{$bnode} = $name;
        }

        // hash direction, property, and bnode name/hash
        $group_md = hash_init('sha1');
        hash_update($group_md, $direction);
        hash_update($group_md,
          ($statement->p === '@type') ? self::RDF_TYPE : $statement->p);
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
              $bnodes, $bnodes->{$bnode}, $namer, $path_namer_copy);
            $path .= $path_namer_copy->getName($bnode);
            $path .= "<{$result->hash}>";
            $path_namer_copy = $result->pathNamer;

            // skip permutation if path is already >= chosen path
            if($chosen_path !== null && strlen($path) >= strlen($chosen_path) &&
              $path > $chosen_path) {
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
   * A helper function that gets the blank node name from a statement value
   * (a subject or object). If the statement value is not a blank node or it
   * has an @id of '_:a', then null will be returned.
   *
   * @param mixed $value the statement value.
   *
   * @return mixed the blank node name or null if none was found.
   */
  protected function _getBlankNodeName($value) {
    return ((self::_isBlankNode($value) && $value->{'@id'} !== '_:a') ?
      $value->{'@id'} : null);
  }

  /**
   * Recursively flattens the subjects in the given JSON-LD expanded input.
   *
   * @param stdClass $subjects a map of subject @id to subject.
   * @param mixed $input the JSON-LD expanded input.
   * @param UniqueNamer $namer the blank node namer.
   * @param mixed $name the name assigned to the current input if it is a bnode.
   * @param mixed $list the list to append to, null for none.
   */
  protected function _flatten($subjects, $input, $namer, $name, $list) {
    // recurse through array
    if(is_array($input)) {
      foreach($input as $e) {
        $this->_flatten($subjects, $e, $namer, null, $list);
      }
    }
    // handle subject
    else if(is_object($input)) {
      // add value to list
      if(self::_isValue($input) && $list) {
        $list[] = $input;
        return;
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
      if(property_exists($subjects, $name)) {
        $subject = $subjects->{$name};
      }
      else {
        $subject = $subjects->{$name} = new stdClass();
      }

      $subject->{'@id'} = $name;
      foreach($input as $prop => $objects) {
        // skip @id
        if($prop === '@id') {
          continue;
        }

        // copy keywords
        if(self::_isKeyword($prop)) {
          $subject->{$prop} = $objects;
          continue;
        }

        // iterate over objects
        foreach($objects as $o) {
          // handle embedded subject or subject reference
          if(self::_isSubject($o) || self::_isSubjectReference($o)) {
            // rename blank node @id
            $id = property_exists($o, '@id') ? $o->{'@id'} : '_:';
            if(strpos($id, '_:') === 0) {
              $id = $namer->getName($id);
            }

            // add reference and recurse
            self::addValue($subject, $prop, (object)array('@id' => $id), true);
            $this->_flatten($subjects, $o, $namer, $id, null);
          }
          else {
            // recurse into list
            if(self::_isListValue($o)) {
              $l = new ArrayObject();
              $this->_flatten($subjects, $o->{'@list'}, $namer, $name, $l);
              $o = (object)array('@list' => (array)$l);
            }

            // add non-subject
            self::addValue($subject, $prop, $o, true);
          }
        }
      }
    }
    // add non-object to list
    else if($list !== null) {
      $list[] = $input;
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
  protected function _match_frame(
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
            if(self::_isListValue($o)) {
              // add empty list
              $list = (object)array('@list' => array());
              $this->_addFrameOutput($state, $output, $prop, $list);

              // add list objects
              $src = $o->{'@list'};
              foreach($src as $o) {
                // recurse into subject reference
                if(self::_isSubjectReference($o)) {
                  $this->_match_frame(
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
              $this->_match_frame(
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
      if(self::_isListValue($o)) {
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
      $useArray = is_array($embed->parent->{$property});
      self::removeValue($embed->parent, $property, $subject, $useArray);
      self::addValue($embed->parent, $property, $subject, $useArray);
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
      self::addValue($parent, $property, $output, true);
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
      if(self::_isListValue($input)) {
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
   * Compares two strings first based on length and then lexicographically.
   *
   * @param string $a the first string.
   * @param string $b the second string.
   *
   * @return integer -1 if a < b, 1 if a > b, 0 if a == b.
   */
  protected function _compareShortestLeast($a, $b) {
    if(strlen($a) < strlen($b)) {
      return -1;
    }
    else if(strlen($b) < strlen($a)) {
      return 1;
    }
    return ($a < $b) ? -1 : (($a > $b) ? 1 : 0);
  }

  /**
   * Ranks a term that is possible choice for compacting an IRI associated with
   * the given value.
   *
   * @param stdClass $ctx the active context.
   * @param string $term the term to rank.
   * @param mixed $value the associated value.
   *
   * @return integer the term rank.
   */
  protected function _rankTerm($ctx, $term, $value) {
    // no term restrictions for a null value
    if($value === null) {
      return 3;
    }

    // get context entry for term
    $entry = $ctx->mappings->{$term};
    $has_type = property_exists($entry, '@type');
    $has_language = property_exists($entry, '@language');
    $has_default_language = property_exists($ctx, '@language');

    // @list rank is the sum of its values' ranks
    if(self::_isListValue($value)) {
      $list = $value->{'@list'};
      if(count($list) === 0) {
        return ($entry->{'@container'} === '@list') ? 1 : 0;
      }
      // sum term ranks for each list value
      $sum = 0;
      foreach($list as $v) {
        $sum += $this->_rankTerm($ctx, $term, $v);
      }
      return $sum;
    }

    // rank boolean or number
    if(is_bool($value) || is_double($value) || is_integer($value)) {
      if(is_bool($value)) {
        $type = self::XSD_BOOLEAN;
      }
      else if(is_double($value)) {
        $type = self::XSD_DOUBLE;
      }
      else {
        $type = self::XSD_INTEGER;
      }
      if($has_type && $entry->{'@type'} === $type) {
        return 3;
      }
      return (!$has_type && !$has_language) ? 2 : 1;
    }

    // rank string (this means the value has no @language)
    if(is_string($value)) {
      // entry @language is specifically null or no @type, @language, or default
      if(($has_language && $entry->{'@language'} === null) ||
        (!$has_type && !$has_language && !$has_default_language)) {
        return 3;
      }
      return 0;
    }

    // Note: Value must be an object that is a @value or subject/reference.

    // @value must have either @type or @language
    if(self::_isValue($value)) {
      if(property_exists($value, '@type')) {
        // @types match
        if($has_type && $value->{'@type'} === $entry->{'@type'}) {
          return 3;
        }
        return (!$has_type && !$has_language) ? 1 : 0;
      }

      // @languages match or entry has no @type or @language but default
      // @language matches
      if(($has_language && $value->{'@language'} === $entry->{'@language'}) ||
        (!$has_type && !$has_language && $has_default_language &&
          $value->{'@language'} === $ctx->{'@language'})) {
        return 3;
      }
      return (!$has_type && !$has_language) ? 1 : 0;
    }

    // value must be a subject/reference
    if($has_type && $entry->{'@type'} === '@id') {
      return 3;
    }
    return (!$has_type && !$has_language) ? 1 : 0;
  }

  /**
   * Compacts an IRI or keyword into a term or prefix if it can be. If the
   * IRI has an associated value it may be passed.
   *
   * @param stdClass $ctx the active context to use.
   * @param string $iri the IRI to compact.
   * @param mixed $value the value to check or null.
   *
   * @return string the compacted term, prefix, keyword alias, or original IRI.
   */
  protected function _compactIri($ctx, $iri, $value=null) {
    // can't compact null
    if($iri === null) {
      return $iri;
    }

    // compact rdf:type
    if($iri === self::RDF_TYPE) {
      return '@type';
    }

    // term is a keyword
    if(self::_isKeyword($iri)) {
      // return alias if available
      $aliases = $ctx->keywords->{$iri};
      if(count($aliases) > 0) {
        return $aliases[0];
      }
      else {
        // no alias, keep original keyword
        return $iri;
      }
    }

    // find all possible term matches
    $terms = array();
    $highest = 0;
    $listContainer = false;
    $isList = self::_isListValue($value);
    foreach($ctx->mappings as $term => $entry) {
      $has_container = property_exists($entry, '@container');

      // skip terms with non-matching iris
      if($entry->{'@id'} !== $iri) {
        continue;
      }
      // skip @set containers for @lists
      if($isList && $has_container && $entry->{'@container'} === '@set') {
        continue;
      }
      // skip @list containers for non-@lists
      if(!$isList && $has_container && $entry->{'@container'} === '@list') {
        continue;
      }
      // for @lists, if listContainer is set, skip non-list containers
      if($isList && $listContainer && (!$has_container ||
        $entry->{'@container'} !== '@list')) {
        continue;
      }

      // rank term
      $rank = $this->_rankTerm($ctx, $term, $value);
      if($rank > 0) {
        // add 1 to rank if container is a @set
        if($has_container && $entry->{'@container'} === '@set') {
          $rank += 1;
        }

        // for @lists, give preference to @list containers
        if($isList && !$listContainer && $has_container &&
          $entry->{'@container'} === '@list') {
          $listContainer = true;
          $terms = array();
          $highest = $rank;
          $terms[] = $term;
        }
        // only push match if rank meets current threshold
        else if($rank >= $highest) {
          if($rank > $highest) {
            $terms = array();
            $highest = $rank;
          }
          $terms[] = $term;
        }
      }
    }

    // no term matches, add possible CURIEs
    if(count($terms) === 0) {
      foreach($ctx->mappings as $term => $entry) {
        // skip terms with colons, they can't be prefixes
        if(strpos($term, ':') !== false) {
          continue;
        }
        // skip entries with @ids that are not partial matches
        if($entry->{'@id'} === $iri || strpos($iri, $entry->{'@id'}) !== 0) {
          continue;
        }

        // add CURIE as term if it has no mapping
        $curie = $term . ':' . substr($iri, strlen($entry->{'@id'}));
        if(!property_exists($ctx->mappings, $curie)) {
          $terms[] = $curie;
        }
      }
    }

    // no matching terms, use IRI
    if(count($terms) === 0) {
      return $iri;
    }

    // return shortest and lexicographically-least term
    usort($terms, array($this, '_compareShortestLeast'));
    return $terms[0];
  }

  /**
   * Defines a context mapping during context processing.
   *
   * @param stdClass $active_ctx the current active context.
   * @param stdClass $ctx the local context being processed.
   * @param string $key the key in the local context to define the mapping for.
   * @param string $base the base IRI.
   * @param stdClass $defined a map of defining/defined keys to detect cycles
   *          and prevent double definitions.
   */
  protected function _defineContextMapping(
    $active_ctx, $ctx, $key, $base, $defined) {
    if(property_exists($defined, $key)) {
      // key already defined
      if($defined->{$key}) {
        return;
      }
      // cycle detected
      throw new JsonLdException(
        'Cyclical context definition detected.',
        'jsonld.CyclicalContext',
        (object)array('context' => $ctx, 'key' => $key));
    }

    // now defining key
    $defined->{$key} = false;

    // if key has a prefix, define it first
    $colon = strpos($key, ':');
    $prefix = null;
    if($colon !== false) {
      $prefix = substr($key, 0, $colon);
      if(property_exists($ctx, $prefix)) {
        // define parent prefix
        $this->_defineContextMapping(
          $active_ctx, $ctx, $prefix, $base, $defined);
      }
    }

    // get context key value
    $value = $ctx->{$key};

    if(self::_isKeyword($key)) {
      // only @language is permitted
      if($key !== '@language') {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; keywords cannot be overridden.',
          'jsonld.SyntaxError', array('context' => $ctx));
      }

      if($value !== null && !is_string($value)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; the value of "@language" in a ' +
          '@context must be a string or null.',
          'jsonld.SyntaxError', array('context' => $ctx));
      }

      if($value === null) {
        unset($active_ctx->{'@language'});
      }
      else {
        $active_ctx->{'@language'} = $value;
      }
      $defined->{$key} = true;
      return;
    }

    // clear context entry
    if($value === null) {
      if(property_exists($active_ctx->mappings, $key)) {
        // if key is a keyword alias, remove it
        $kw = $active_ctx->mappings->{$key}->{'@id'};
        if(self::_isKeyword($kw)) {
          array_splice($active_ctx->keywords->{$kw},
            in_array($key, $active_ctx->keywords->{$kw}), 1);
        }
        unset($active_ctx->mappings->{$key});
      }
      $defined->{$key} = true;
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

        // uniquely add key as a keyword alias and resort
        $aliases = $active_ctx->keywords->{$value};
        if(in_array($key, $active_ctx->keywords->{$value}) === false) {
          $active_ctx->keywords->{$value}[] = $key;
          usort($active_ctx->keywords->{$value},
            array($this, '_compareShortestLeast'));
        }
      }
      else {
        // expand value to a full IRI
        $value = $this->_expandContextIri(
          $active_ctx, $ctx, $value, $base, $defined);
      }

      // define/redefine key to expanded IRI/keyword
      $active_ctx->mappings->{$key} = (object)array('@id' => $value);
      $defined->{$key} = true;
      return;
    }

    if(!is_object($value)) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; @context property values must be ' +
        'strings or objects.',
        'jsonld.SyntaxError', array('context' => $ctx));
    }

    // create new mapping
    $mapping = new stdClass();

    if(property_exists($value, '@id')) {
      $id = $value->{'@id'};
      if(!is_string($id)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @id values must be strings.',
          'jsonld.SyntaxError', array('context' => $ctx));
      }

      // expand @id to full IRI
      $id = $this->_expandContextIri($active_ctx, $ctx, $id, $base, $defined);

      // add @id to mapping
      $mapping->{'@id'} = $id;
    }
    else {
      // non-IRIs *must* define @ids
      if($prefix === null) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context terms must define an @id.',
          'jsonld.SyntaxError', array('context' => $ctx, 'key' => $key));
      }

      // set @id based on prefix parent
      if(property_exists($active_ctx->mappings, $prefix)) {
        $suffix = substr($key, $colon + 1);
        $mapping->{'@id'} = $active_ctx->mappings->{$prefix}->{'@id'} . $suffix;
      }
      // key is an absolute IRI
      else {
        $mapping->{'@id'} = $key;
      }
    }

    if(property_exists($value, '@type')) {
      $type = $value->{'@type'};
      if(!is_string($type)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @type values must be strings.',
          'jsonld.SyntaxError', array('context' => $ctx));
      }

      if($type !== '@id') {
        // expand @type to full IRI
        $type = $this->_expandContextIri(
          $active_ctx, $ctx, $type, '', $defined);
      }

      // add @type to mapping
      $mapping->{'@type'} = $type;
    }

    if(property_exists($value, '@container')) {
      $container = $value->{'@container'};
      if($container !== '@list' && $container !== '@set') {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @container value must be ' +
          '"@list" or "@set".',
          'jsonld.SyntaxError', array('context' => $ctx));
      }

      // add @container to mapping
      $mapping->{'@container'} = $container;
    }

    if(property_exists($value, '@language') &&
      !property_exists($value, '@type')) {
      $language = $value->{'@language'};
      if($language !== null && !is_string($language)) {
        throw new JsonLdException(
          'Invalid JSON-LD syntax; @context @language value must be ' +
          'a string or null.',
          'jsonld.SyntaxError', array('context' => $ctx));
      }

      // add @language to mapping
      $mapping->{'@language'} = $language;
    }

    // merge onto parent mapping if one exists for a prefix
    if($prefix !== null && property_exists($active_ctx->mappings, $prefix)) {
      $child = $mapping;
      $mapping = self::copy($active_ctx->mappings->{$prefix});
      foreach($child as $k => $v) {
        $mapping->{$k} = $v;
      }
    }

    // define key mapping
    $active_ctx->mappings->{$key} = $mapping;
    $defined->{$key} = true;
  }

  /**
   * Expands a string value to a full IRI during context processing. It can
   * be assumed that the value is not a keyword.
   *
   * @param stdClass $active_ctx the current active context.
   * @param stdClass $ctx the local context being processed.
   * @param string $value the string value to expand.
   * @param string $base the base IRI.
   * @param stdClass $defined a map for tracking cycles in context definitions.
   *
   * @return mixed the expanded value.
   */
  protected function _expandContextIri(
    $active_ctx, $ctx, $value, $base, $defined) {
    // dependency not defined, define it
    if(property_exists($ctx, $value) &&
      (!property_exists($defined, $value) || !$defined->{$value})) {
      $this->_defineContextMapping($active_ctx, $ctx, $value, $base, $defined);
    }

    // recurse if value is a term
    if(property_exists($active_ctx->mappings, $value)) {
      $id = $active_ctx->mappings->{$value}->{'@id'};
      // value is already an absolute IRI
      if($value === $id) {
        return $value;
      }
      return $this->_expandContextIri($active_ctx, $ctx, $id, $base, $defined);
    }

    // split value into prefix:suffix
    if(strpos($value, ':') !== false) {
      list($prefix, $suffix) = explode(':', $value, 2);

      // a prefix of '_' indicates a blank node
      if($prefix === '_') {
        return $value;
      }

      // a suffix of '//' indicates value is an absolute IRI
      if(strpos($suffix, '//') === 0) {
        return $value;
      }

      // dependency not defined, define it
      if(property_exists($ctx, $prefix) &&
        (!property_exists($defined, $prefix) || !$defined->{$prefix})) {
        $this->_defineContextMapping(
          $active_ctx, $ctx, $prefix, $base, $defined);
      }

      // recurse if prefix is defined
      if(property_exists($active_ctx->mappings, $prefix)) {
        $id = $active_ctx->mappings->{$prefix}->{'@id'};
        return $this->_expandContextIri(
          $active_ctx, $ctx, $id, $base, $defined) . $suffix;
      }

      // consider value an absolute IRI
      return $value;
    }

    // prepend base
    $value = "$base$value";

    // value must now be an absolute IRI
    if(!self::_isAbsoluteIri($value)) {
      throw new JsonLdException(
        'Invalid JSON-LD syntax; a @context value does not expand to ' +
        'an absolute IRI.',
        'jsonld.SyntaxError', array('context' => $ctx, 'value' => $value));
    }

    return $value;
  }

  /**
   * Expands a term into an absolute IRI. The term may be a regular term, a
   * prefix, a relative IRI, or an absolute IRI. In any case, the associated
   * absolute IRI will be returned.
   *
   * @param stdClass $ctx the active context to use.
   * @param string $term the term to expand.
   * @param string $base the base IRI to use if a relative IRI is detected.
   *
   * @return string the expanded term as an absolute IRI.
   */
  protected function _expandTerm($ctx, $term, $base='') {
    // nothing to expand
    if($term === null) {
      return null;
    }

    // the term has a mapping, so it is a plain term
    if(property_exists($ctx->mappings, $term)) {
      $id = $ctx->mappings->{$term}->{'@id'};
      // term is already an absolute IRI
      if($term === $id) {
        return $term;
      }
      return $this->_expandTerm($ctx, $id, $base);
    }

    // split term into prefix:suffix
    if(strpos($term, ':') !== false) {
      list($prefix, $suffix) = explode(':', $term, 2);

      // a prefix of '_' indicates a blank node
      if($prefix === '_') {
        return $term;
      }

      // a suffix of '//' indicates value is an absolute IRI
      if(strpos($suffix, '//') === 0) {
        return $term;
      }

      // the term's prefix has a mapping, so it is a CURIE
      if(property_exists($ctx->mappings, $prefix)) {
        return $this->_expandTerm(
          $ctx, $ctx->mappings->{$prefix}->{'@id'}, $base) . $suffix;
      }

      // consider term an absolute IRI
      return $term;
    }

    // prepend base to term
    return "$base$term";
  }

  /**
   * Resolves external @context URLs using the given URL resolver. Each instance
   * of @context in the input that refers to a URL will be replaced with the
   * JSON @context found at that URL.
   *
   * @param mixed $input the JSON-LD object with possible contexts.
   * @param callable $resolver(url, callback(err, jsonCtx)) the URL resolver.
   *
   * @return mixed the result.
   */
  protected function _resolveUrls($input, $resolver) {
    // keeps track of resolved URLs (prevents duplicate work)
    $urls = new stdClass();

    // finds URLs in @context properties and replaces them with their
    // resolved @contexts if replace is true
    $findUrls = function($input, $replace) use (&$findUrls, $urls) {
      if(is_array($input)) {
        $output = array();
        foreach($input as $v) {
          $output[] = $findUrls($v, $replace);
        }
        return $output;
      }
      else if(is_object($input)) {
        foreach($input as $k => $v) {
          if($k !== '@context') {
            $input->{$k} = $findUrls($v, $replace);
            continue;
          }

          // array @context
          if(is_array($v)) {
            foreach($v as $i => $url) {
              if(is_string($url)) {
                // replace w/resolved @context if requested
                if($replace) {
                  $v[$i] = $urls->{$url};
                }
                // unresolved @context found
                else if(!property_exists($urls, $url)) {
                  $urls->{$url} = new stdClass();
                }
              }
            }
          }
          // string @context
          else if(is_string($v)) {
            // replace w/resolved @context if requested
            if($replace) {
              $input->{$key} = $urls->{$v};
            }
            // unresolved @context found
            else if(!property_exists($urls, $v)) {
              $urls->{$v} = new stdClass();
            }
          }
        }
      }
      return $input;
    };
    $input = $findUrls($input, false);

    // resolve all URLs
    foreach($urls as $url => $v) {
      // validate URL
      if(filter_var($url, FILTER_VALIDATE_URL) === false) {
        throw new JsonLdException(
          'Malformed URL.', 'jsonld.InvalidUrl', array('url' => $url));
      }

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

      // FIXME: needs to recurse to resolve URLs in the result, and
      // detect cycles, and limit recursion
      if(property_exists($ctx, '@context')) {
        $urls->{$url} = $ctx->{'@context'};
      }
    }

    // do url replacement
    return $findUrls($input, true);
  }

  /**
   * Gets the initial context.
   *
   * @return stdClass the initial context.
   */
  protected function _getInitialContext() {
    return (object)array(
      'mappings' => new stdClass(),
      'keywords' => (object)array(
        '@context'=> array(),
        '@container'=> array(),
        '@default'=> array(),
        '@embed'=> array(),
        '@explicit'=> array(),
        '@graph'=> array(),
        '@id'=> array(),
        '@language'=> array(),
        '@list'=> array(),
        '@omitDefault'=> array(),
        '@preserve'=> array(),
        '@set'=> array(),
        '@type'=> array(),
        '@value'=> array()
      ));
  }

  /**
   * Returns whether or not the given value is a keyword (or a keyword alias).
   *
   * @param string $value the value to check.
   * @param stdClass [$ctx] the active context to check against.
   *
   * @return bool true if the value is a keyword, false if not.
   */
  protected static function _isKeyword($value, $ctx=null) {
    if($ctx !== null) {
      if(property_exists($ctx->keywords, $value)) {
        return true;
      }
      foreach($ctx->keywords as $kw => $aliases) {
        if(in_array($value, $aliases) !== false) {
          return true;
        }
      }
    }
    else {
      switch($value) {
      case '@context':
      case '@container':
      case '@default':
      case '@embed':
      case '@explicit':
      case '@graph':
      case '@id':
      case '@language':
      case '@list':
      case '@omitDefault':
      case '@preserve':
      case '@set':
      case '@type':
      case '@value':
        return true;
      }
    }
    return false;
  }

  /**
   * Returns true if the given input is an empty Object.
   *
   * @param mixed $input the input to check.
   *
   * @return bool true if the input is an empty Object, false if not.
   */
  protected static function _isEmptyObject($input) {
    return is_object($input) && count(get_object_vars($input)) === 0;
  }

  /**
   * Returns true if the given input is an Array of Strings.
   *
   * @param mixed $input the input to check.
   *
   * @return bool true if the input is an Array of Strings, false if not.
   */
  protected static function _isArrayOfStrings($input) {
    if(!is_array($input)) {
      return false;
    }
    foreach($input as $v) {
      if(!is_string($v)) {
        return false;
      }
    }
    return true;
  }

  /**
   * Returns true if the given value is a subject with properties.
   *
   * @param mixed $value the value to check.
   *
   * @return bool true if the value is a subject with properties, false if not.
   */
  protected static function _isSubject($value) {
    $rval = false;

    // Note: A value is a subject if all of these hold true:
    // 1. It is an Object.
    // 2. It is not a @value, @set, or @list.
    // 3. It has more than 1 key OR any existing key is not @id.
    if(is_object($value) &&
      !property_exists($value, '@value') &&
      !property_exists($value, '@set') &&
      !property_exists($value, '@list')) {
      $count = count(get_object_vars($value));
      $rval = ($count > 1 || !property_exists($value, '@id'));
    }

    return $rval;
  }

  /**
   * Returns true if the given value is a subject reference.
   *
   * @param mixed $value the value to check.
   *
   * @return bool true if the value is a subject reference, false if not.
   */
  protected static function _isSubjectReference($value) {
    // Note: A value is a subject reference if all of these hold true:
    // 1. It is an Object.
    // 2. It has a single key: @id.
    return (is_object($value) && count(get_object_vars($value)) === 1 &&
      property_exists($value, '@id'));
  }

  /**
   * Returns true if the given value is a @value.
   *
   * @param mixed $value the value to check.
   *
   * @return bool true if the value is a @value, false if not.
   */
  protected static function _isValue($value) {
    // Note: A value is a @value if all of these hold true:
    // 1. It is an Object.
    // 2. It has the @value property.
    return is_object($value) && property_exists($value, '@value');
  }

  /**
   * Returns true if the given value is a @set.
   *
   * @param mixed $value the value to check.
   *
   * @return bool true if the value is a @set, false if not.
   */
  protected static function _isSetValue($value) {
    // Note: A value is a @set if all of these hold true:
    // 1. It is an Object.
    // 2. It has the @set property.
    return is_object($value) && property_exists($value, '@set');
  }

  /**
   * Returns true if the given value is a @list.
   *
   * @param mixed $value the value to check.
   *
   * @return bool true if the value is a @list, false if not.
   */
  protected static function _isListValue($value) {
    // Note: A value is a @list if all of these hold true:
    // 1. It is an Object.
    // 2. It has the @list property.
    return is_object($value) && property_exists($value, '@list');
  }

  /**
   * Returns true if the given value is a blank node.
   *
   * @param mixed $value the value to check.
   *
   * @return bool true if the value is a blank node, false if not.
   */
  protected static function _isBlankNode($value) {
    $rval = false;
    // Note: A value is a blank node if all of these hold true:
    // 1. It is an Object.
    // 2. If it has an @id key its value begins with '_:'.
    // 3. It has no keys OR is not a @value, @set, or @list.
    if(is_object($value)) {
      if(property_exists($value, '@id')) {
        $rval = (strpos($value->{'@id'}, '_:') === 0);
      }
      else {
        $rval = (count(get_object_vars($value)) === 0 ||
          !(property_exists($value, '@value') ||
            property_exists($value, '@set') ||
            property_exists($value, '@list')));
      }
    }
    return $rval;
  }

  /**
   * Returns true if the given value is an absolute IRI, false if not.
   *
   * @param string $value the value to check.
   *
   * @return bool true if the value is an absolute IRI, false if not.
   */
  protected static function _isAbsoluteIri($value) {
    return strpos($value, ':') !== false;
  }
}

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
      $rval .= 'details: ' . print_r($this->details, true) . "\n";
    }
    if($this->cause) {
      $rval .= 'cause: ' . $this->cause;
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

/* end of file, omit ?> */
