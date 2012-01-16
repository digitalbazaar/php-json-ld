<?php
/**
 * PHP implementation of JSON-LD.
 *
 * @author Dave Longley
 *
 * Copyright (c) 2011-2012 Digital Bazaar, Inc. All rights reserved.
 */
define('JSONLD_XSD', 'http://www.w3.org/2001/XMLSchema#');
define('JSONLD_XSD_BOOLEAN', JSONLD_XSD . 'boolean');
define('JSONLD_XSD_DOUBLE', JSONLD_XSD . 'double');
define('JSONLD_XSD_INTEGER', JSONLD_XSD . 'integer');

/**
 * Normalizes a JSON-LD object.
 *
 * @param input the JSON-LD object to normalize.
 *
 * @return the normalized JSON-LD object.
 */
function jsonld_normalize($input)
{
   $p = new JsonLdProcessor();
   return $p->normalize($input);
}

/**
 * Removes the context from a JSON-LD object, expanding it to full-form.
 *
 * @param input the JSON-LD object to remove the context from.
 *
 * @return the context-neutral JSON-LD object.
 */
function jsonld_expand($input)
{
   $p = new JsonLdProcessor();
   return $p->expand(new stdClass(), null, $input);
}

/**
 * Expands the given JSON-LD object and then compacts it using the
 * given context.
 *
 * @param ctx the new context to use.
 * @param input the input JSON-LD object.
 *
 * @return the output JSON-LD object.
 */
function jsonld_compact($ctx, $input)
{
   $rval = null;

   // TODO: should context simplification be optional? (ie: remove context
   // entries that are not used in the output)

   if($input !== null)
   {
      // fully expand input
      $input = jsonld_expand($input);

      // merge context if it is an array
      if(is_array($ctx))
      {
         $ctx = jsonld_merge_contexts(new stdClass, $ctx);
      }

      // setup output context
      $ctxOut = new stdClass();

      // compact
      $p = new JsonLdProcessor();
      $rval = $out = $p->compact(_clone($ctx), null, $input, $ctxOut);

      // add context if used
      if(count(array_keys((array)$ctxOut)) > 0)
      {
         $rval = new stdClass();
         $rval->{'@context'} = $ctxOut;
         if(is_array($out))
         {
            $rval->{_getKeywords($ctxOut)->{'@id'}} = $out;
         }
         else
         {
            foreach($out as $k => $v)
            {
               $rval->{$k} = $v;
            }
         }
      }
   }

   return $rval;
}

/**
 * Merges one context with another.
 *
 * @param ctx1 the context to overwrite/append to.
 * @param ctx2 the new context to merge onto ctx1.
 *
 * @return the merged context.
 */
function jsonld_merge_contexts($ctx1, $ctx2)
{
   // merge first context if it is an array
   if(is_array($ctx1))
   {
      $ctx1 = jsonld_merge_contexts($ctx1, $ctx2);
   }

   // copy context to merged output
   $merged = _clone($ctx1);

   if(is_array($ctx2))
   {
      // merge array of contexts in order
      foreach($ctx2 as $ctx)
      {
         $merged = jsonld_merge_contexts($merged, $ctx);
      }
   }
   else
   {
      // if the new context contains any IRIs that are in the merged context,
      // remove them from the merged context, they will be overwritten
      foreach($ctx2 as $key => $value)
      {
         // ignore special keys starting with '@'
         if(strpos($key, '@') !== 0)
         {
            foreach($merged as $mkey => $mvalue)
            {
               if($mvalue === $value)
               {
                  // FIXME: update related coerce rules
                  unset($merged->$mkey);
                  break;
               }
            }
         }
      }

      // merge contexts
      foreach($ctx2 as $key => $value)
      {
         $merged->$key = _clone($value);
      }
   }

   return $merged;
}

/**
 * Expands a term into an absolute IRI. The term may be a regular term, a
 * prefix, a relative IRI, or an absolute IRI. In any case, the associated
 * absolute IRI will be returned.
 *
 * @param ctx the context to use.
 * @param term the term to expand.
 *
 * @return the expanded term as an absolute IRI.
 */
function jsonld_expand_term($ctx, $term)
{
   return _expandTerm($ctx, $term);
}

/**
 * Compacts an IRI into a term or prefix if it can be. IRIs will not be
 * compacted to relative IRIs if they match the given context's default
 * vocabulary.
 *
 * @param ctx the context to use.
 * @param iri the IRI to compact.
 *
 * @return the compacted IRI as a term or prefix or the original IRI.
 */
function jsonld_compact_iri($ctx, $iri)
{
   return _compactIri($ctx, $iri, null);
}

/**
 * Frames JSON-LD input.
 *
 * @param input the JSON-LD input.
 * @param frame the frame to use.
 * @param options framing options to use.
 *
 * @return the framed output.
 */
function jsonld_frame($input, $frame, $options=null)
{
   $rval;

   // normalize input
   $input = jsonld_normalize($input);

   // save frame context
   $ctx = null;
   if(is_object($frame) and property_exists($frame, '@context'))
   {
      $ctx = _clone($frame->{'@context'});

      // remove context from frame
      $frame = jsonld_expand($frame);
   }
   else if(is_array($frame))
   {
      // save first context in the array
      if(count($frame) > 0 and property_exists($frame[0], '@context'))
      {
         $ctx = _clone($frame[0]->{'@context'});
      }

      // expand all elements in the array
      $tmp = array();
      foreach($frame as $f)
      {
         $tmp[] = jsonld_expand($f);
      }
      $frame = $tmp;
   }

   // create framing options
   // TODO: merge in options from function parameter
   $options = new stdClass();
   $options->defaults = new stdClass();
   $options->defaults->embedOn = true;
   $options->defaults->explicitOn = false;
   $options->defaults->omitDefaultOn = false;

   // build map of all subjects
   $subjects = new stdClass();
   foreach($input as $i)
   {
      $subjects->{$i->{'@id'}} = $i;
   }

   // frame input
   $rval = _frame(
      $subjects, $input, $frame, new stdClass(), false, null, null, $options);

   // apply context
   if($ctx !== null and $rval !== null)
   {
      // preserve top-level array by compacting individual entries
      if(is_array($rval))
      {
         $tmp = $rval;
         $rval = array();
         foreach($tmp as $value)
         {
            $rval[] = jsonld_compact($ctx, $value);
         }
      }
      else
      {
         $rval = jsonld_compact($ctx, $rval);
      }
   }

   return $rval;
}

/**
 * Resolves external @context URLs. Every @context URL in the given JSON-LD
 * object is resolved using the given URL-resolver function. Once all of
 * the @contexts have been resolved, the method will return. If an error
 * is encountered, an exception will be thrown.
 *
 * @param input the JSON-LD input object (or array).
 * @param resolver the resolver method that takes a URL and returns a JSON-LD
 *           serialized @context or throws an exception.
 *
 * @return the fully-resolved JSON-LD output (object or array).
 */
function jsonld_resolve($input, $resolver)
{
   // find all @context URLs
   $urls = new ArrayObject();
   _findUrls($input, $urls, false);

   // resolve all URLs
   foreach($urls as $url => $value)
   {
      $result = call_user_func($resolver, $url);
      if(!is_string($result))
      {
         // already deserialized
         $urls[$url] = $result->{'@context'};
      }
      else
      {
         // deserialize JSON
         $tmp = json_decode($result);
         if($tmp === null)
         {
            throw new Exception(
               'Could not resolve @context URL ("$url"), ' .
               'malformed JSON detected.');
         }
         $urls[$url] = $tmp->{'@context'};
      }
   }

   // replace @context URLs in input
   _findUrls($input, $urls, true);

   return $input;
}

/**
 * Finds all of the @context URLs in the given input and replaces them
 * if requested by their associated values in the given URL map.
 *
 * @param input the JSON-LD input object.
 * @param urls the URLs ArrayObject.
 * @param replace true to replace, false not to.
 */
function _findUrls($input, $urls, $replace)
{
   if(is_array($input))
   {
      foreach($input as $v)
      {
         _findUrls($v);
      }
   }
   else if(is_object($input))
   {
      foreach($input as $key => $value)
      {
         if($key === '@context')
         {
            // @context is an array that might contain URLs
            if(is_array($value))
            {
               foreach($value as $idx => $v)
               {
                  if(is_string($v))
                  {
                     // replace w/resolved @context if appropriate
                     if($replace)
                     {
                        $input->{$key}[$idx] = $urls[$v];
                     }
                     // unresolved @context found
                     else
                     {
                        $urls[$v] = new stdClass();
                     }
                  }
               }
            }
            else if(is_string($value))
            {
               // replace w/resolved @context if appropriate
               if($replace)
               {
                  $input->$key = $urls[$value];
               }
               // unresolved @context found
               else
               {
                  $urls[$value] = new stdClass();
               }
            }
         }
      }
   }
}

/**
 * Gets the keywords from a context.
 *
 * @param ctx the context.
 *
 * @return the keywords.
 */
function _getKeywords($ctx)
{
   // TODO: reduce calls to this function by caching keywords in processor
   // state

   $rval = (object)array(
      '@id' => '@id',
      '@language' => '@language',
      '@value' => '@value',
      '@type' => '@type'
   );

   if($ctx)
   {
      // gather keyword aliases from context
      $keywords = new stdClass();
      foreach($ctx as $key => $value)
      {
         if(is_string($value) and property_exists($rval, $value))
         {
            $keywords->{$value} = $key;
         }
      }

      // overwrite keywords
      foreach($keywords as $key => $value)
      {
         $rval->$key = $value;
      }
   }

   return $rval;
}

/**
 * Sets a subject's property to the given object value. If a value already
 * exists, it will be appended to an array.
 *
 * @param s the subject.
 * @param p the property.
 * @param o the object.
 */
function _setProperty($s, $p, $o)
{
   if(property_exists($s, $p))
   {
      if(is_array($s->$p))
      {
         array_push($s->$p, $o);
      }
      else
      {
         $s->$p = array($s->$p, $o);
      }
   }
   else
   {
      $s->$p = $o;
   }
}

/**
 * Clones an object, array, or string/number. If cloning an object, the keys
 * will be sorted.
 *
 * @param value the value to clone.
 *
 * @return the cloned value.
 */
function _clone($value)
{
   $rval;

   if(is_object($value))
   {
      $rval = new stdClass();
      $keys = array_keys((array)$value);
      sort($keys);
      foreach($keys as $key)
      {
         $rval->$key = _clone($value->$key);
      }
   }
   else if(is_array($value))
   {
      $rval = array();
      foreach($value as $v)
      {
         $rval[] = _clone($v);
      }
   }
   else
   {
      $rval = $value;
   }

   return $rval;
}

/**
 * Gets the iri associated with a term.
 *
 * @param ctx the context.
 * @param term the term.
 *
 * @return the iri or NULL.
 */
function _getTermIri($ctx, $term)
{
   $rval = null;
   if(property_exists($ctx, $term))
   {
      if(is_string($ctx->$term))
      {
         $rval = $ctx->$term;
      }
      else if(is_object($ctx->$term) and property_exists($ctx->$term, '@id'))
      {
         $rval = $ctx->$term->{'@id'};
      }
   }
   return $rval;
}

/**
 * Compacts an IRI into a term or prefix if it can be. IRIs will not be
 * compacted to relative IRIs if they match the given context's default
 * vocabulary.
 *
 * @param ctx the context to use.
 * @param iri the IRI to compact.
 * @param usedCtx a context to update if a value was used from "ctx".
 *
 * @return the compacted IRI as a term or prefix or the original IRI.
 */
function _compactIri($ctx, $iri, $usedCtx)
{
   $rval = null;

   // check the context for a term that could shorten the IRI
   // (give preference to terms over prefixes)
   foreach($ctx as $key => $value)
   {
      // skip special context keys (start with '@')
      if(strlen($key) > 0 and $key[0] !== '@')
      {
         // compact to a term
         if($iri === _getTermIri($ctx, $key))
         {
            $rval = $key;
            if($usedCtx !== null)
            {
               $usedCtx->$key = _clone($ctx->$key);
            }
            break;
         }
      }
   }

   // term not found, if term is keyword, use alias
   if($rval === null)
   {
      $keywords = _getKeywords($ctx);
      if(property_exists($keywords, $iri))
      {
         $rval = $keywords->{$iri};
         if($rval !== $iri and $usedCtx !== null)
         {
            $usedCtx->$rval = $iri;
         }
      }
   }

   // term not found, check the context for a prefix
   if($rval === null)
   {
      foreach($ctx as $key => $value)
      {
         // skip special context keys (start with '@')
         if(strlen($key) > 0 and $key[0] !== '@')
         {
            // see if IRI begins with the next IRI from the context
            $ctxIri = _getTermIri($ctx, $key);
            if($ctxIri !== null)
            {
               $idx = strpos($iri, $ctxIri);

               // compact to a prefix
               if($idx === 0 and strlen($iri) > strlen($ctxIri))
               {
                  $rval = $key . ':' . substr($iri, strlen($ctxIri));
                  if($usedCtx !== null)
                  {
                     $usedCtx->$key = _clone($ctx->$key);
                  }
                  break;
               }
            }
         }
      }
   }

   // could not compact IRI
   if($rval === null)
   {
      $rval = $iri;
   }

   return $rval;
}

/**
 * Expands a term into an absolute IRI. The term may be a regular term, a
 * prefix, a relative IRI, or an absolute IRI. In any case, the associated
 * absolute IRI will be returned.
 *
 * @param ctx the context to use.
 * @param term the term to expand.
 * @param usedCtx a context to update if a value was used from "ctx".
 *
 * @return the expanded term as an absolute IRI.
 */
function _expandTerm($ctx, $term, $usedCtx)
{
   $rval = $term;

   // get JSON-LD keywords
   $keywords = _getKeywords($ctx);

   // 1. If the property has a colon, it is a prefix or an absolute IRI:
   $idx = strpos($term, ':');
   if($idx !== false)
   {
      // get the potential prefix
      $prefix = substr($term, 0, $idx);

      // expand term if prefix is in context, otherwise leave it be
      if(property_exists($ctx, $prefix))
      {
         // prefix found, expand property to absolute IRI
         $iri = _getTermIri($ctx, $prefix);
         $rval = $iri . substr($term, $idx + 1);
         if($usedCtx !== null)
         {
            $usedCtx->$prefix = _clone($ctx->$prefix);
         }
      }
   }
   // 2. If the property is in the context, then it's a term.
   else if(property_exists($ctx, $term))
   {
      $rval = _getTermIri($ctx, $term);
      if($usedCtx !== null)
      {
         $usedCtx->$term = _clone($ctx->$term);
      }
   }
   // 3. The property is a keyword.
   else
   {
      foreach($keywords as $key => $value)
      {
         if($term === $value)
         {
            $rval = $key;
            break;
         }
      }
   }

   return $rval;
}

/**
 * Gets whether or not a value is a reference to a subject (or a subject with
 * no properties).
 *
 * @param value the value to check.
 *
 * @return true if the value is a reference to a subject, false if not.
 */
function _isReference($value)
{
   // Note: A value is a reference to a subject if all of these hold true:
   // 1. It is an Object.
   // 2. It is has an @id key.
   // 3. It has only 1 key.
   return ($value !== null and
      is_object($value) and
      property_exists($value, '@id') and
      count(get_object_vars($value)) === 1);
};

/**
 * Gets whether or not a value is a subject with properties.
 *
 * @param value the value to check.
 *
 * @return true if the value is a subject with properties, false if not.
 */
function _isSubject($value)
{
   $rval = false;

   // Note: A value is a subject if all of these hold true:
   // 1. It is an Object.
   // 2. It is not a literal (@value).
   // 3. It has more than 1 key OR any existing key is not '@id'.
   if($value !== null and is_object($value) and
      !property_exists($value, '@value'))
   {
      $keyCount = count(get_object_vars($value));
      $rval = ($keyCount > 1 or !property_exists($value, '@id'));
   }

   return $rval;
}

function _isBlankNodeIri($v)
{
   return strpos($v, '_:') === 0;
}

function _isNamedBlankNode($v)
{
   // look for "_:" at the beginning of the subject
   return (
      is_object($v) and property_exists($v, '@id') and
      _isBlankNodeIri($v->{'@id'}));
}

function _isBlankNode($v)
{
   // look for a subject with no ID or a blank node ID
   return (
      _isSubject($v) and
      (!property_exists($v, '@id') or _isNamedBlankNode($v)));
}

/**
 * Compares two values.
 *
 * @param v1 the first value.
 * @param v2 the second value.
 *
 * @return -1 if v1 < v2, 0 if v1 == v2, 1 if v1 > v2.
 */
function _compare($v1, $v2)
{
   $rval = 0;

   if(is_array($v1) and is_array($v2))
   {
      $length = count($v1);
      for($i = 0; $i < $length and $rval === 0; ++$i)
      {
         $rval = _compare($v1[$i], $v2[$i]);
      }
   }
   else
   {
      $rval = ($v1 < $v2 ? -1 : ($v1 > $v2 ? 1 : 0));
   }

   return $rval;
}

/**
 * Compares two keys in an object. If the key exists in one object
 * and not the other, the object with the key is less. If the key exists in
 * both objects, then the one with the lesser value is less.
 *
 * @param o1 the first object.
 * @param o2 the second object.
 * @param key the key.
 *
 * @return -1 if o1 < o2, 0 if o1 == o2, 1 if o1 > o2.
 */
function _compareObjectKeys($o1, $o2, $key)
{
   $rval = 0;
   if(property_exists($o1, $key))
   {
      if(property_exists($o2, $key))
      {
         $rval = _compare($o1->$key, $o2->$key);
      }
      else
      {
         $rval = -1;
      }
   }
   else if(property_exists($o2, $key))
   {
      $rval = 1;
   }
   return $rval;
}

/**
 * Compares two object values.
 *
 * @param o1 the first object.
 * @param o2 the second object.
 *
 * @return -1 if o1 < o2, 0 if o1 == o2, 1 if o1 > o2.
 */
function _compareObjects($o1, $o2)
{
   $rval = 0;

   if(is_string($o1))
   {
      if(!is_string($o2))
      {
         $rval = -1;
      }
      else
      {
         $rval = _compare($o1, $o2);
      }
   }
   else if(is_string($o2))
   {
      $rval = 1;
   }
   else
   {
      $rval = _compareObjectKeys($o1, $o2, '@value');
      if($rval === 0)
      {
         if(property_exists($o1, '@value'))
         {
            $rval = _compareObjectKeys($o1, $o2, '@type');
            if($rval === 0)
            {
               $rval = _compareObjectKeys($o1, $o2, '@language');
            }
         }
         // both are '@id' objects
         else
         {
            $rval = _compare($o1->{'@id'}, $o2->{'@id'});
         }
      }
   }

   return $rval;
}

/**
 * Filter function for bnodes.
 *
 * @param e the array element.
 *
 * @return true to return the element in the filter results, false not to.
 */
function _filterBlankNodes($e)
{
   return !_isNamedBlankNode($e);
}

/**
 * Compares the object values between two bnodes.
 *
 * @param a the first bnode.
 * @param b the second bnode.
 *
 * @return -1 if a < b, 0 if a == b, 1 if a > b.
 */
function _compareBlankNodeObjects($a, $b)
{
   $rval = 0;

   /*
   3. For each property, compare sorted object values.
   3.1. The bnode with fewer objects is first.
   3.2. For each object value, compare only literals (@values) and non-bnodes.
   3.2.1.  The bnode with fewer non-bnodes is first.
   3.2.2. The bnode with a string object is first.
   3.2.3. The bnode with the alphabetically-first string is first.
   3.2.4. The bnode with a @value is first.
   3.2.5. The bnode with the alphabetically-first @value is first.
   3.2.6. The bnode with the alphabetically-first @type is first.
   3.2.7. The bnode with a @language is first.
   3.2.8. The bnode with the alphabetically-first @language is first.
   3.2.9. The bnode with the alphabetically-first @id is first.
   */

   foreach($a as $p => $value)
   {
      // skip IDs (IRIs)
      if($p !== '@id')
      {
         // step #3.1
         $lenA = is_array($a->$p) ? count($a->$p) : 1;
         $lenB = is_array($b->$p) ? count($b->$p) : 1;
         $rval = _compare($lenA, $lenB);

         // step #3.2.1
         if($rval === 0)
         {
            // normalize objects to an array
            $objsA = $a->$p;
            $objsB = $b->$p;
            if(!is_array($objsA))
            {
               $objsA = array($objsA);
               $objsB = array($objsB);
            }

            // compare non-bnodes (remove bnodes from comparison)
            $objsA = array_filter($objsA, '_filterBlankNodes');
            $objsB = array_filter($objsB, '_filterBlankNodes');
            $objsALen = count($objsA);
            $rval = _compare($objsALen, count($objsB));
         }

         // steps #3.2.2-3.2.9
         if($rval === 0)
         {
            usort($objsA, '_compareObjects');
            usort($objsB, '_compareObjects');
            for($i = 0; $i < $objsALen and $rval === 0; ++$i)
            {
               $rval = _compareObjects($objsA[$i], $objsB[$i]);
            }
         }

         if($rval !== 0)
         {
            break;
         }
      }
   }

   return $rval;
}

/**
 * A blank node name generator that uses a prefix and counter.
 */
class NameGenerator
{
   public function __construct($prefix)
   {
      $this->count = -1;
      $this->base = '_:' . $prefix;
   }

   public function next()
   {
      $this->count += 1;
      return $this->current();
   }

   public function current()
   {
      return $this->base . $this->count;
   }

   public function inNamespace($iri)
   {
      return strpos($iri, $this->base) === 0;
   }
}

/**
 * Populates a map of all named subjects from the given input and an array
 * of all unnamed bnodes (includes embedded ones).
 *
 * @param input the input (must be expanded, no context).
 * @param subjects the subjects map to populate.
 * @param bnodes the bnodes array to populate.
 */
function _collectSubjects($input, $subjects, $bnodes)
{
   if($input === null)
   {
      // nothing to collect
   }
   else if(is_array($input))
   {
      foreach($input as $value)
      {
         _collectSubjects($value, $subjects, $bnodes);
      }
   }
   else if(is_object($input))
   {
      if(property_exists($input, '@id'))
      {
         // graph literal/disjoint graph
         if(is_array($input->{'@id'}))
         {
            _collectSubjects($input->{'@id'}, $subjects, $bnodes);
         }
         // named subject
         else if(_isSubject($input))
         {
            $subjects->{$input->{'@id'}} = $input;
         }
      }
      // unnamed blank node
      else if(_isBlankNode($input))
      {
         $bnodes[] = $input;
      }

      // recurse through subject properties
      foreach($input as $value)
      {
         _collectSubjects($value, $subjects, $bnodes);
      }
   }
}

/**
 * Filters duplicate objects.
 */
class DuplicateFilter
{
   public function __construct($obj)
   {
      $this->obj = $obj;
   }

   public function filter($e)
   {
      return (_compareObjects($e, $this->obj) === 0);
   }
}

/**
 * Flattens the given value into a map of unique subjects. It is assumed that
 * all blank nodes have been uniquely named before this call. Array values for
 * properties will be sorted.
 *
 * @param parent the value's parent, NULL for none.
 * @param parentProperty the property relating the value to the parent.
 * @param value the value to flatten.
 * @param subjects the map of subjects to write to.
 */
function _flatten($parent, $parentProperty, $value, $subjects)
{
   $flattened = null;

   if($value === null)
   {
      // drop null values
   }
   else if(is_array($value))
   {
      // list of objects or a disjoint graph
      foreach($value as $v)
      {
         _flatten($parent, $parentProperty, $v, $subjects);
      }
   }
   else if(is_object($value))
   {
      // already-expanded value or special-case reference-only @type
      if(property_exists($value, '@value') or $parentProperty === '@type')
      {
         $flattened = _clone($value);
      }
      // graph literal/disjoint graph
      else if(is_array($value->{'@id'}))
      {
         // cannot flatten embedded graph literals
         if($parent !== null)
         {
            throw new Exception('Embedded graph literals cannot be flattened.');
         }

         // top-level graph literal
         foreach($value->{'@id'} as $k => $v)
         {
            _flatten($parent, $parentProperty, $v, $subjects);
         }
      }
      // regular subject
      else
      {
         // create or fetch existing subject
         if(property_exists($subjects, $value->{'@id'}))
         {
            // FIXME: '@id' might be a graph literal (as {})
            $subject = $subjects->{$value->{'@id'}};
         }
         else
         {
            // FIXME: '@id' might be a graph literal (as {})
            $subject = new stdClass();
            $subject->{'@id'} = $value->{'@id'};
            $subjects->{$value->{'@id'}} = $subject;
         }
         $flattened = new stdClass();
         $flattened->{'@id'} = $subject->{'@id'};

         // flatten embeds
         foreach($value as $key => $v)
         {
            // drop null values, skip @id (it is already set above)
            if($v !== null and $key !== '@id')
            {
               if(property_exists($subject, $key))
               {
                  if(!is_array($subject->$key))
                  {
                     $subject->$key = new ArrayObject(array($subject->$key));
                  }
                  else
                  {
                     $subject->$key = new ArrayObject($subject->$key);
                  }
               }
               else
               {
                  $subject->$key = new ArrayObject();
               }

               _flatten($subject->$key, $key, $value->$key, $subjects);
               $subject->$key = (array)$subject->$key;
               if(count($subject->$key) === 1)
               {
                  // convert subject[key] to object if it has only 1
                  $arr = $subject->$key;
                  $subject->$key = $arr[0];
               }
            }
         }
      }
   }
   // string value
   else
   {
      $flattened = $value;
   }

   // add flattened value to parent
   if($flattened !== null and $parent !== null)
   {
      if($parent instanceof ArrayObject)
      {
         // do not add duplicate IRIs for the same property
         $duplicate = count(array_filter(
            (array)$parent, array(
               new DuplicateFilter($flattened), 'filter'))) > 0;
         if(!$duplicate)
         {
            $parent[] = $flattened;
         }
      }
      else
      {
         $parent->$parentProperty = $flattened;
      }
   }
}

/**
 * A MappingBuilder is used to build a mapping of existing blank node names
 * to a form for serialization. The serialization is used to compare blank
 * nodes against one another to determine a sort order.
 */
class MappingBuilder
{
   /**
    * Constructs a new MappingBuilder.
    */
   public function __construct()
   {
      $this->count = 1;
      $this->processed = new stdClass();
      $this->mapping = new stdClass();
      $this->adj = new stdClass();
      $this->keyStack = array();
      $entry = new stdClass();
      $entry->keys = array('s1');
      $entry->idx = 0;
      $this->keyStack[] = $entry;
      $this->done = new stdClass();
      $this->s = '';
   }

   /**
    * Copies this MappingBuilder.
    *
    * @return the MappingBuilder copy.
    */
   public function copy()
   {
      $rval = new MappingBuilder();
      $rval->count = $this->count;
      $rval->processed = _clone($this->processed);
      $rval->mapping = _clone($this->mapping);
      $rval->adj = _clone($this->adj);
      $rval->keyStack = _clone($this->keyStack);
      $rval->done = _clone($this->done);
      $rval->s = $this->s;
      return $rval;
   }

   /**
    * Maps the next name to the given bnode IRI if the bnode IRI isn't already
    * in the mapping. If the given bnode IRI is canonical, then it will be
    * given a shortened form of the same name.
    *
    * @param iri the blank node IRI to map the next name to.
    *
    * @return the mapped name.
    */
   public function mapNode($iri)
   {
      if(!property_exists($this->mapping, $iri))
      {
         if(strpos($iri, '_:c14n') === 0)
         {
            $this->mapping->$iri = 'c' . substr($iri, 6);
         }
         else
         {
            $this->mapping->$iri = 's' . $this->count++;
         }
      }
      return $this->mapping->$iri;
   }
}

/**
 * Rotates the elements in an array one position.
 *
 * @param a the array.
 */
function _rotate(&$a)
{
  $e = array_shift($a);
  array_push($a, $e);
  return $e;
}

/**
 * Compares two serializations for the same blank node. If the two
 * serializations aren't complete enough to determine if they are equal (or if
 * they are actually equal), 0 is returned.
 *
 * @param s1 the first serialization.
 * @param s2 the second serialization.
 *
 * @return -1 if s1 < s2, 0 if s1 == s2 (or indeterminate), 1 if s1 > v2.
 */
function _compareSerializations($s1, $s2)
{
   $rval = 0;

   $s1Len = strlen($s1);
   $s2Len = strlen($s2);
   if($s1Len == $s2Len)
   {
      $rval = strcmp($s1, $s2);
   }
   else
   {
      $rval = strncmp($s1, $s2, ($s1Len > $s2Len) ? $s2Len : $s1Len);
   }

   return $rval;
}

/**
 * Compares two nodes based on their iris.
 *
 * @param a the first node.
 * @param b the second node.
 *
 * @return -1 if iriA < iriB, 0 if iriA == iriB, 1 if iriA > iriB.
 */
function _compareIris($a, $b)
{
   return _compare($a->{'@id'}, $b->{'@id'});
}

/**
 * Filters blank node edges.
 *
 * @param e the edge to check.
 *
 * @return true if the edge is a blank node edge.
 */
function _filterBlankNodeEdges($e)
{
   return _isBlankNodeIri($e->s);
}

/**
 * Sorts mapping keys based on their associated mapping values.
 */
class MappingKeySorter
{
   public function __construct($mapping)
   {
      $this->mapping = $mapping;
   }

   public function compare($a, $b)
   {
      return _compare($this->mapping->$a, $this->mapping->$b);
   }
}

/**
 * A JSON-LD processor.
 */
class JsonLdProcessor
{
   /**
    * Constructs a JSON-LD processor.
    */
   public function __construct()
   {
   }

   /**
    * Recursively compacts a value. This method will compact IRIs to prefixes
    * or terms and do reverse type coercion to compact a value.
    *
    * @param ctx the context to use.
    * @param property the property that points to the value, NULL for none.
    * @param value the value to compact.
    * @param usedCtx a context to update if a value was used from "ctx".
    *
    * @return the compacted value.
    */
   public function compact($ctx, $property, $value, $usedCtx)
   {
      $rval;

      // get JSON-LD keywords
      $keywords = _getKeywords($ctx);

      if($value === null)
      {
         // return null, but check coerce type to add to usedCtx
         $rval = null;
         $this->getCoerceType($ctx, $property, $usedCtx);
      }
      else if(is_array($value))
      {
         // recursively add compacted values to array
         $rval = array();
         foreach($value as $v)
         {
            $rval[] = $this->compact($ctx, $property, $v, $usedCtx);
         }
      }
      // graph literal/disjoint graph
      else if(
         is_object($value) and
         property_exists($value, '@id') and
         is_array($value->{'@id'}))
      {
         $rval = new stdClass();
         $rval->{$keywords->{'@id'}} = $this->compact(
            $ctx, $property, $value->{'@id'}, $usedCtx);
      }
      // recurse if value is a subject
      else if(_isSubject($value))
      {
         // recursively handle sub-properties that aren't a sub-context
         $rval = new stdClass();
         foreach($value as $key => $v)
         {
            if($v !== '@context')
            {
               // set object to compacted property, only overwrite existing
               // properties if the property actually compacted
               $p = _compactIri($ctx, $key, $usedCtx);
               if($p !== $key or !property_exists($rval, $p))
               {
                  $rval->$p = $this->compact($ctx, $key, $v, $usedCtx);
               }
            }
         }
      }
      else
      {
         // get coerce type
         $coerce = $this->getCoerceType($ctx, $property, $usedCtx);

         // get type from value, to ensure coercion is valid
         $type = null;
         if(is_object($value))
         {
            // type coercion can only occur if language is not specified
            if(!property_exists($value, '@language'))
            {
               // type must match coerce type if specified
               if(property_exists($value, '@type'))
               {
                  $type = $value->{'@type'};
               }
               // type is ID (IRI)
               else if(property_exists($value, '@id'))
               {
                  $type = '@id';
               }
               // can be coerced to any type
               else
               {
                  $type = $coerce;
               }
            }
         }
         // type can be coerced to anything
         else if(is_string($value))
         {
            $type = $coerce;
         }

         // types that can be auto-coerced from a JSON-builtin
         if($coerce === null and
            ($type === JSONLD_XSD_BOOLEAN or $type === JSONLD_XSD_INTEGER or
            $type === JSONLD_XSD_DOUBLE))
         {
            $coerce = $type;
         }

         // do reverse type-coercion
         if($coerce !== null)
         {
            // type is only null if a language was specified, which is an error
            // if type coercion is specified
            if($type === null)
            {
               throw new Exception(
                  'Cannot coerce type when a language is specified. ' .
                  'The language information would be lost.');
            }
            // if the value type does not match the coerce type, it is an error
            else if($type !== $coerce)
            {
               throw new Exception(
                  'Cannot coerce type because the type does not match.');
            }
            // do reverse type-coercion
            else
            {
               if(is_object($value))
               {
                  if(property_exists($value, '@id'))
                  {
                     $rval = $value->{'@id'};
                  }
                  else if(property_exists($value, '@value'))
                  {
                     $rval = $value->{'@value'};
                  }
               }
               else
               {
                  $rval = $value;
               }

               // do basic JSON types conversion
               if($coerce === JSONLD_XSD_BOOLEAN)
               {
                  $rval = ($rval === 'true' or $rval != 0);
               }
               else if($coerce === JSONLD_XSD_DOUBLE)
               {
                  $rval = floatval($rval);
               }
               else if($coerce === JSONLD_XSD_INTEGER)
               {
                  $rval = intval($rval);
               }
            }
         }
         // no type-coercion, just change keywords/copy value
         else if(is_object($value))
         {
            $rval = new stdClass();
            foreach($value as $key => $v)
            {
               $rval->{$keywords->$key} = $v;
            }
         }
         else
         {
            $rval = _clone($value);
         }

         // compact IRI
         if($type === '@id')
         {
            if(is_object($rval))
            {
               $rval->{$keywords->{'@id'}} = _compactIri(
                  $ctx, $rval->{$keywords->{'@id'}}, $usedCtx);
            }
            else
            {
               $rval = _compactIri($ctx, $rval, $usedCtx);
            }
         }
      }

      return $rval;
   }

   /**
    * Recursively expands a value using the given context. Any context in
    * the value will be removed.
    *
    * @param ctx the context.
    * @param property the property that points to the value, NULL for none.
    * @param value the value to expand.
    *
    * @return the expanded value.
    */
   public function expand($ctx, $property, $value)
   {
      $rval;

      // TODO: add data format error detection?

      // value is null, nothing to expand
      if($value === null)
      {
         $rval = null;
      }
      // if no property is specified and the value is a string (this means the
      // value is a property itself), expand to an IRI
      else if($property === null and is_string($value))
      {
         $rval = _expandTerm($ctx, $value, null);
      }
      else if(is_array($value))
      {
         // recursively add expanded values to array
         $rval = array();
         foreach($value as $v)
         {
            $rval[] = $this->expand($ctx, $property, $v);
         }
      }
      else if(is_object($value))
      {
         // if value has a context, use it
         if(property_exists($value, '@context'))
         {
            $ctx = jsonld_merge_contexts($ctx, $value->{'@context'});
         }

         // recursively handle sub-properties that aren't a sub-context
         $rval = new stdClass();
         foreach($value as $key => $v)
         {
            // preserve frame keywords
            if($key === '@embed' or $key === '@explicit' or
               $key === '@default' or $key === '@omitDefault')
            {
               _setProperty($rval, $key, _clone($v));
            }
            else if($key !== '@context')
            {
               // set object to expanded property
               _setProperty(
                  $rval, _expandTerm($ctx, $key, null),
                  $this->expand($ctx, $key, $v));
            }
         }
      }
      else
      {
         // do type coercion
         $coerce = $this->getCoerceType($ctx, $property, null);

         // get JSON-LD keywords
         $keywords = _getKeywords($ctx);

         // automatic coercion for basic JSON types
         if($coerce === null and !is_string($value) and
            (is_numeric($value) or is_bool($value)))
         {
            if(is_bool($value))
            {
               $coerce = JSONLD_XSD_BOOLEAN;
            }
            else if(is_int($value))
            {
               $coerce = JSONLD_XSD_INTEGER;
            }
            else
            {
               $coerce = JSONLD_XSD_DOUBLE;
            }
         }

         // special-case expand @id and @type (skips '@id' expansion)
         if($property === '@id' or $property === $keywords->{'@id'} or
            $property === '@type' or $property === $keywords->{'@type'})
         {
            $rval = _expandTerm($ctx, $value, null);
         }
         // coerce to appropriate type
         else if($coerce !== null)
         {
            $rval = new stdClass();

            // expand ID (IRI)
            if($coerce === '@id')
            {
               $rval->{'@id'} = _expandTerm($ctx, $value, null);
            }
            // other type
            else
            {
               $rval->{'@type'} = $coerce;
               if($coerce === JSONLD_XSD_DOUBLE)
               {
                  // do special JSON-LD double format
                  $value = preg_replace(
                     '/(e(?:\+|-))([0-9])$/', '${1}0${2}',
                     sprintf('%1.6e', $value));
               }
               else if($coerce === JSONLD_XSD_BOOLEAN)
               {
                  $value = $value ? 'true' : 'false';
               }
               $rval->{'@value'} = '' . $value;
            }
         }
         // nothing to coerce
         else
         {
            $rval = '' . $value;
         }
      }

      return $rval;
   }

   /**
    * Normalizes a JSON-LD object.
    *
    * @param input the JSON-LD object to normalize.
    *
    * @return the normalized JSON-LD object.
    */
   public function normalize($input)
   {
      $rval = array();

      // TODO: validate context

      if($input !== null)
      {
         // create name generator state
         $this->ng = new stdClass();
         $this->ng->tmp = null;
         $this->ng->c14n = null;

         // expand input
         $expanded = $this->expand(new stdClass(), null, $input);

         // assign names to unnamed bnodes
         $this->nameBlankNodes($expanded);

         // flatten
         $subjects = new stdClass();
         _flatten(null, null, $expanded, $subjects);

         // append subjects with sorted properties to array
         foreach($subjects as $key => $s)
         {
            $sorted = new stdClass();
            $keys = array_keys((array)$s);
            sort($keys);
            foreach($keys as $i => $k)
            {
               $sorted->$k = $s->$k;
            }
            $rval[] = $sorted;
         }

         // canonicalize blank nodes
         $this->canonicalizeBlankNodes($rval);

         // sort output
         usort($rval, '_compareIris');
      }

      return $rval;
   }

   /**
    * Gets the coerce type for the given property.
    *
    * @param ctx the context to use.
    * @param property the property to get the coerced type for.
    * @param usedCtx a context to update if a value was used from "ctx".
    *
    * @return the coerce type, null for none.
    */
   public function getCoerceType($ctx, $property, $usedCtx)
   {
      $rval = null;

      // get expanded property
      $p = _expandTerm($ctx, $property, null);

      // built-in type coercion JSON-LD-isms
      if($p === '@id' or $p === '@type')
      {
         $rval = '@id';
      }
      else
      {
         // look up compacted property in coercion map
         $p = _compactIri($ctx, $p, null);
         if(property_exists($ctx, $p) and is_object($ctx->$p) and
            property_exists($ctx->$p, '@type'))
         {
            // property found, return expanded type
            $type = $ctx->$p->{'@type'};
            $rval = _expandTerm($ctx, $type, $usedCtx);
            if($usedCtx !== null)
            {
               $usedCtx->$p = _clone($ctx->$p);
            }
         }
      }

      return $rval;
   }

   /**
    * Assigns unique names to blank nodes that are unnamed in the given input.
    *
    * @param input the input to assign names to.
    */
   public function nameBlankNodes($input)
   {
      // create temporary blank node name generator
      $ng = $this->ng->tmp = new NameGenerator('tmp');

      // collect subjects and unnamed bnodes
      $subjects = new stdClass();
      $bnodes = new ArrayObject();
      _collectSubjects($input, $subjects, $bnodes);

      // uniquely name all unnamed bnodes
      foreach($bnodes as $i => $bnode)
      {
         if(!property_exists($bnode, '@id'))
         {
            // generate names until one is unique
            while(property_exists($subjects, $ng->next()));
            $bnode->{'@id'} = $ng->current();
            $subjects->{$ng->current()} = $bnode;
         }
      }
   }

   /**
    * Renames a blank node, changing its references, etc. The method assumes
    * that the given name is unique.
    *
    * @param b the blank node to rename.
    * @param id the new name to use.
    */
   public function renameBlankNode($b, $id)
   {
      $old = $b->{'@id'};

      // update bnode IRI
      $b->{'@id'} = $id;

      // update subjects map
      $subjects = $this->subjects;
      $subjects->$id = $subjects->$old;
      unset($subjects->$old);

      // update reference and property lists
      $this->edges->refs->$id = $this->edges->refs->$old;
      $this->edges->props->$id = $this->edges->props->$old;
      unset($this->edges->refs->$old);
      unset($this->edges->props->$old);

      // update references to this bnode
      $refs = $this->edges->refs->$id->all;
      foreach($refs as $i => $r)
      {
         $iri = $r->s;
         if($iri === $old)
         {
            $iri = $id;
         }
         $ref = $subjects->$iri;
         $props = $this->edges->props->$iri->all;
         foreach($props as $prop)
         {
            if($prop->s === $old)
            {
               $prop->s = $id;

               // normalize property to array for single code-path
               $p = $prop->p;
               $tmp = is_object($ref->$p) ? array($ref->$p) :
                  (is_array($ref->$p) ? $ref->$p : array());
               $length = count($tmp);
               for($n = 0; $n < $length; ++$n)
               {
                  if(is_object($tmp[$n]) and
                     property_exists($tmp[$n], '@id') and
                     $tmp[$n]->{'@id'} === $old)
                  {
                     $tmp[$n]->{'@id'} = $id;
                  }
               }
            }
         }
      }

      // update references from this bnode
      $props = $this->edges->props->$id->all;
      foreach($props as $prop)
      {
         $iri = $prop->s;
         $refs = $this->edges->refs->$iri->all;
         foreach($refs as $r)
         {
            if($r->s === $old)
            {
               $r->s = $id;
            }
         }
      }
   }

   /**
    * Canonically names blank nodes in the given input.
    *
    * @param input the flat input graph to assign names to.
    */
   public function canonicalizeBlankNodes($input)
   {
      // create serialization state
      $this->renamed = new stdClass();
      $this->mappings = new stdClass();
      $this->serializations = new stdClass();

      // collect subjects and bnodes from flat input graph
      $edges = $this->edges = new stdClass();
      $edges->refs = new stdClass();
      $edges->props = new stdClass();
      $subjects = $this->subjects = new stdClass();
      $bnodes = array();
      foreach($input as $v)
      {
         $iri = $v->{'@id'};
         $subjects->$iri = $v;
         $edges->refs->$iri = new stdClass();
         $edges->refs->$iri->all = array();
         $edges->refs->$iri->bnodes = array();
         $edges->props->$iri = new stdClass();
         $edges->props->$iri->all = array();
         $edges->props->$iri->bnodes = array();
         if(_isBlankNodeIri($iri))
         {
            $bnodes[] = $v;
         }
      }

      // collect edges in the graph
      $this->collectEdges();

      // create canonical blank node name generator
      $c14n = $this->ng->c14n = new NameGenerator('c14n');
      $ngTmp = $this->ng->tmp;

      // rename all bnodes that happen to be in the c14n namespace
      // and initialize serializations
      foreach($bnodes as $i => $bnode)
      {
         $iri = $bnode->{'@id'};
         if($c14n->inNamespace($iri))
         {
            // generate names until one is unique
            while(property_exists($subjects, $ngTmp->next()));
            $this->renameBlankNode($bnode, $ngTmp->current());
            $iri = $bnode->{'@id'};
         }
         $this->serializations->$iri = new stdClass();
         $this->serializations->$iri->props = null;
         $this->serializations->$iri->refs = null;
      }

      // keep sorting and naming blank nodes until they are all named
      $resort = true;
      while(count($bnodes) > 0)
      {
         if($resort)
         {
            $resort = false;
            usort($bnodes, array($this, 'deepCompareBlankNodes'));
         }

         // name all bnodes according to the first bnode's relation mappings
         // (if it has mappings then a resort will be necessary)
         $bnode = array_shift($bnodes);
         $iri = $bnode->{'@id'};
         $resort = ($this->serializations->$iri->{'props'} !== null);
         $dirs = array('props', 'refs');
         foreach($dirs as $dir)
         {
            // if no serialization has been computed, name only the first node
            if($this->serializations->$iri->$dir === null)
            {
               $mapping = new stdClass();
               $mapping->$iri = 's1';
            }
            else
            {
               $mapping = $this->serializations->$iri->$dir->m;
            }

            // sort keys by value to name them in order
            $keys = array_keys((array)$mapping);
            usort($keys, array(new MappingKeySorter($mapping), 'compare'));

            // name bnodes in mapping
            $renamed = array();
            foreach($keys as $i => $iriK)
            {
               if(!$c14n->inNamespace($iri) and
                  property_exists($subjects, $iriK))
               {
                  $this->renameBlankNode($subjects->$iriK, $c14n->next());
                  $renamed[] = $iriK;
               }
            }

            // only keep non-canonically named bnodes
            $tmp = $bnodes;
            $bnodes = array();
            foreach($tmp as $i => $b)
            {
               $iriB = $b->{'@id'};
               if(!$c14n->inNamespace($iriB))
               {
                  // mark serializations related to the named bnodes as dirty
                  foreach($renamed as $r)
                  {
                     if($this->markSerializationDirty($iriB, $r, $dir))
                     {
                        // resort if a serialization was marked dirty
                        $resort = true;
                     }
                  }
                  $bnodes[] = $b;
               }
            }
         }
      }

      // sort property lists that now have canonically-named bnodes
      foreach($edges->props as $key => $value)
      {
         if(count($value->bnodes) > 0)
         {
            $bnode = $subjects->$key;
            foreach($bnode as $p => $v)
            {
               if(strpos($p, '@') !== 0 and is_array($v))
               {
                  usort($v, '_compareObjects');
                  $bnode->$p = $v;
               }
            }
         }
      }
   }

   /**
    * Marks a relation serialization as dirty if necessary.
    *
    * @param iri the IRI of the bnode to check.
    * @param changed the old IRI of the bnode that changed.
    * @param dir the direction to check ('props' or 'refs').
    *
    * @return true if the serialization was marked dirty, false if not.
    */
   public function markSerializationDirty($iri, $changed, $dir)
   {
      $rval = false;
      $s = $this->serializations->$iri;
      if($s->$dir !== null and property_exists($s->$dir->m, $changed))
      {
         $s->$dir = null;
         $rval = true;
      }
      return $rval;
   }

   /**
    * Serializes the properties of the given bnode for its relation
    * serialization.
    *
    * @param b the blank node.
    *
    * @return the serialized properties.
    */
   public function serializeProperties($b)
   {
      $rval = '';

      $first = true;
      foreach($b as $p => $o)
      {
         if($p !== '@id')
         {
            if($first)
            {
               $first = false;
            }
            else
            {
               $rval .= '|';
            }

            // property
            $rval .= '<' . $p . '>';

            // object(s)
            $objs = is_array($o) ? $o : array($o);
            foreach($objs as $obj)
            {
               if(is_object($obj))
               {
                  // ID (IRI)
                  if(property_exists($obj, '@id'))
                  {
                     if(_isBlankNodeIri($obj->{'@id'}))
                     {
                        $rval .= '_:';
                     }
                     else
                     {
                        $rval .= '<' . $obj->{'@id'} . '>';
                     }
                  }
                  // literal
                  else
                  {
                     $rval .= '"' . $obj->{'@value'} . '"';

                     // type literal
                     if(property_exists($obj, '@type'))
                     {
                        $rval .= '^^<' . $obj->{'@type'} . '>';
                     }
                     // language literal
                     else if(property_exists($obj, '@language'))
                     {
                        $rval .= '@' . $obj->{'@language'};
                     }
                  }
               }
               // plain literal
               else
               {
                  $rval .= '"' . $obj . '"';
               }
            }
         }
      }

      return $rval;
   }

   /**
    * Recursively increments the relation serialization for a mapping.
    *
    * @param mb the mapping builder to update.
    */
   public function serializeMapping($mb)
   {
      if(count($mb->keyStack) > 0)
      {
         // continue from top of key stack
         $next = array_pop($mb->keyStack);
         $len = count($next->keys);
         for(; $next->idx < $len; ++$next->idx)
         {
            $k = $next->keys[$next->idx];
            if(!property_exists($mb->adj, $k))
            {
               $mb->keyStack[] = $next;
               break;
            }

            if(property_exists($mb->done, $k))
            {
               // mark cycle
               $mb->s .= '_' . $k;
            }
            else
            {
               // mark key as serialized
               $mb->done->$k = true;

               // serialize top-level key and its details
               $s = $k;
               $adj = $mb->adj->$k;
               $iri = $adj->i;
               if(property_exists($this->subjects, $iri))
               {
                  $b = $this->subjects->$iri;

                  // serialize properties
                  $s .= '[' . $this->serializeProperties($b) . ']';

                  // serialize references
                  $s .= '[';
                  $first = true;
                  $refs = $this->edges->refs->$iri->all;
                  foreach($refs as $r)
                  {
                     if($first)
                     {
                        $first = false;
                     }
                     else
                     {
                        $s .= '|';
                     }
                     $s .= '<' . $r->p . '>';
                     $s .= _isBlankNodeIri($r->s) ?
                        '_:' : ('<' . $refs->s . '>');
                  }
                  $s .= ']';
               }

               // serialize adjacent node keys
               $s .= implode($adj->k);
               $mb->s .= $s;
               $entry = new stdClass();
               $entry->keys = $adj->k;
               $entry->idx = 0;
               $mb->keyStack[] = $entry;
               $this->serializeMapping($mb);
            }
         }
      }
   }

   /**
    * Recursively serializes adjacent bnode combinations.
    *
    * @param s the serialization to update.
    * @param iri the IRI of the bnode being serialized.
    * @param siri the serialization name for the bnode IRI.
    * @param mb the MappingBuilder to use.
    * @param dir the edge direction to use ('props' or 'refs').
    * @param mapped all of the already-mapped adjacent bnodes.
    * @param notMapped all of the not-yet mapped adjacent bnodes.
    */
   public function serializeCombos(
      $s, $iri, $siri, $mb, $dir, $mapped, $notMapped)
   {
      // handle recursion
      if(count($notMapped) > 0)
      {
         // copy mapped nodes
         $mapped = _clone($mapped);

         // map first bnode in list
         $mapped->{$mb->mapNode($notMapped[0]->s)} = $notMapped[0]->s;

         // recurse into remaining possible combinations
         $original = $mb->copy();
         $notMapped = array_slice($notMapped, 1);
         $rotations = max(1, count($notMapped));
         for($r = 0; $r < $rotations; ++$r)
         {
            $m = ($r === 0) ? $mb : $original->copy();
            $this->serializeCombos(
               $s, $iri, $siri, $m, $dir, $mapped, $notMapped);

            // rotate not-mapped for next combination
            _rotate($notMapped);
         }
      }
      // no more adjacent bnodes to map, update serialization
      else
      {
         $keys = array_keys((array)$mapped);
         sort($keys);
         $entry = new stdClass();
         $entry->i = $iri;
         $entry->k = $keys;
         $entry->m = $mapped;
         $mb->adj->$siri = $entry;
         $this->serializeMapping($mb);

         // optimize away mappings that are already too large
         if($s->$dir === null or
            _compareSerializations($mb->s, $s->$dir->s) <= 0)
         {
            // recurse into adjacent values
            foreach($keys as $i => $k)
            {
               $this->serializeBlankNode($s, $mapped->$k, $mb, $dir);
            }

            // update least serialization if new one has been found
            $this->serializeMapping($mb);
            if($s->$dir === null or
               (_compareSerializations($mb->s, $s->$dir->s) <= 0 and
               strlen($mb->s) >= strlen($s->$dir->s)))
            {
               $s->$dir = new stdClass();
               $s->$dir->s = $mb->s;
               $s->$dir->m = $mb->mapping;
            }
         }
      }
   }

   /**
    * Computes the relation serialization for the given blank node IRI.
    *
    * @param s the serialization to update.
    * @param iri the current bnode IRI to be mapped.
    * @param mb the MappingBuilder to use.
    * @param dir the edge direction to use ('props' or 'refs').
    */
   public function serializeBlankNode($s, $iri, $mb, $dir)
   {
      // only do mapping if iri not already processed
      if(!property_exists($mb->processed, $iri))
      {
         // iri now processed
         $mb->processed->$iri = true;
         $siri = $mb->mapNode($iri);

         // copy original mapping builder
         $original = $mb->copy();

         // split adjacent bnodes on mapped and not-mapped
         $adjs = $this->edges->$dir->$iri->bnodes;
         $mapped = new stdClass();
         $notMapped = array();
         foreach($adjs as $adj)
         {
            if(property_exists($mb->mapping, $adj->s))
            {
               $mapped->{$mb->mapping->{$adj->s}} = $adj->s;
            }
            else
            {
               $notMapped[] = $adj;
            }
         }

         // TODO: ensure this optimization does not alter canonical order

         // if the current bnode already has a serialization, reuse it
         /*$hint = property_exists($this->serializations, $iri) ?
            $this->serializations->$iri->$dir : null;
         if($hint !== null)
         {
            $hm = $hint->m;
            usort(notMapped,
            {
               return _compare(hm[a.s], hm[b.s]);
            });
            for($i in notMapped)
            {
               mapped[mb.mapNode(notMapped[i].s)] = notMapped[i].s;
            }
            notMapped = array();
         }*/

         // loop over possible combinations
         $combos = max(1, count($notMapped));
         for($i = 0; $i < $combos; ++$i)
         {
            $m = ($i === 0) ? $mb : $original->copy();
            $this->serializeCombos(
               $s, $iri, $siri, $mb, $dir, $mapped, $notMapped);
         }
      }
   }

   /**
    * Compares two blank nodes for equivalence.
    *
    * @param a the first blank node.
    * @param b the second blank node.
    *
    * @return -1 if a < b, 0 if a == b, 1 if a > b.
    */
   public function deepCompareBlankNodes($a, $b)
   {
      $rval = 0;

      // compare IRIs
      $iriA = $a->{'@id'};
      $iriB = $b->{'@id'};
      if($iriA === $iriB)
      {
         $rval = 0;
      }
      else
      {
         // do shallow compare first
         $rval = $this->shallowCompareBlankNodes($a, $b);

         // deep comparison is necessary
         if($rval === 0)
         {
            // compare property edges and then reference edges
            $dirs = array('props', 'refs');
            for($i = 0; $rval === 0 and $i < 2; ++$i)
            {
               // recompute 'a' and 'b' serializations as necessary
               $dir = $dirs[$i];
               $sA = $this->serializations->$iriA;
               $sB = $this->serializations->$iriB;
               if($sA->$dir === null)
               {
                  $mb = new MappingBuilder();
                  if($dir === 'refs')
                  {
                     // keep same mapping and count from 'props' serialization
                     $mb->mapping = _clone($sA->props->m);
                     $mb->count = count(array_keys((array)$mb->mapping)) + 1;
                  }
                  $this->serializeBlankNode($sA, $iriA, $mb, $dir);
               }
               if($sB->$dir === null)
               {
                  $mb = new MappingBuilder();
                  if($dir === 'refs')
                  {
                     // keep same mapping and count from 'props' serialization
                     $mb->mapping = _clone($sB->props->m);
                     $mb->count = count(array_keys((array)$mb->mapping)) + 1;
                  }
                  $this->serializeBlankNode($sB, $iriB, $mb, $dir);
               }

               // compare serializations
               $rval = _compare($sA->$dir->s, $sB->$dir->s);
            }
         }
      }

      return $rval;
   }

   /**
    * Performs a shallow sort comparison on the given bnodes.
    *
    * @param a the first bnode.
    * @param b the second bnode.
    *
    * @return -1 if a < b, 0 if a == b, 1 if a > b.
    */
   public function shallowCompareBlankNodes($a, $b)
   {
      $rval = 0;

      /* ShallowSort Algorithm (when comparing two bnodes):
         1. Compare the number of properties.
         1.1. The bnode with fewer properties is first.
         2. Compare alphabetically sorted-properties.
         2.1. The bnode with the alphabetically-first property is first.
         3. For each property, compare object values.
         4. Compare the number of references.
         4.1. The bnode with fewer references is first.
         5. Compare sorted references.
         5.1. The bnode with the reference iri (vs. bnode) is first.
         5.2. The bnode with the alphabetically-first reference iri is first.
         5.3. The bnode with the alphabetically-first reference property is
            first.
       */
      $pA = array_keys((array)$a);
      $pB = array_keys((array)$b);

      // step #1
      $rval = _compare(count($pA), count($pB));

      // step #2
      if($rval === 0)
      {
         sort($pA);
         sort($pB);
         $rval = _compare($pA, $pB);
      }

      // step #3
      if($rval === 0)
      {
         $rval = _compareBlankNodeObjects($a, $b);
      }

      // step #4
      if($rval === 0)
      {
         $edgesA = $this->edges->refs->{$a->{'@id'}}->all;
         $edgesB = $this->edges->refs->{$b->{'@id'}}->all;
         $edgesALen = count($edgesA);
         $rval = _compare($edgesALen, count($edgesB));
      }

      // step #5
      if($rval === 0)
      {
         for($i = 0; $i < $edgesALen and $rval === 0; ++$i)
         {
            $rval = $this->compareEdges($edgesA[$i], $edgesB[$i]);
         }
      }

      return $rval;
   }

   /**
    * Compares two edges. Edges with an IRI (vs. a bnode ID) come first, then
    * alphabetically-first IRIs, then alphabetically-first properties. If a
    * blank node has been canonically named, then blank nodes will be compared
    * after properties (with a preference for canonically named over
    * non-canonically named), otherwise they won't be.
    *
    * @param a the first edge.
    * @param b the second edge.
    *
    * @return -1 if a < b, 0 if a == b, 1 if a > b.
    */
   public function compareEdges($a, $b)
   {
      $rval = 0;

      $bnodeA = _isBlankNodeIri($a->s);
      $bnodeB = _isBlankNodeIri($b->s);
      $c14n = $this->ng->c14n;

      // if not both bnodes, one that is a bnode is greater
      if($bnodeA != $bnodeB)
      {
         $rval = $bnodeA ? 1 : -1;
      }
      else
      {
         if(!$bnodeA)
         {
            $rval = _compare($a->s, $b->s);
         }
         if($rval === 0)
         {
            $rval = _compare($a->p, $b->p);
         }

         // do bnode IRI comparison if canonical naming has begun
         if($rval === 0 and $c14n !== null)
         {
            $c14nA = $c14n->inNamespace($a->s);
            $c14nB = $c14n->inNamespace($b->s);
            if($c14nA != $c14nB)
            {
               $rval = $c14nA ? 1 : -1;
            }
            else if($c14nA)
            {
               $rval = _compare($a->s, $b->s);
            }
         }
      }

      return $rval;
   }

   /**
    * Populates the given reference map with all of the subject edges in the
    * graph. The references will be categorized by the direction of the edges,
    * where 'props' is for properties and 'refs' is for references to a subject
    * as an object. The edge direction categories for each IRI will be sorted
    * into groups 'all' and 'bnodes'.
    */
   public function collectEdges()
   {
      $refs = $this->edges->refs;
      $props = $this->edges->props;

      // collect all references and properties
      foreach($this->subjects as $iri => $subject)
      {
         foreach($subject as $key => $object)
         {
            if($key !== '@id')
            {
               // normalize to array for single codepath
               $tmp = !is_array($object) ? array($object) : $object;
               foreach($tmp as $o)
               {
                  if(is_object($o) and property_exists($o, '@id') and
                     property_exists($this->subjects, $o->{'@id'}))
                  {
                     $objIri = $o->{'@id'};

                     // map object to this subject
                     $e = new stdClass();
                     $e->s = $iri;
                     $e->p = $key;
                     $refs->$objIri->all[] = $e;

                     // map this subject to object
                     $e = new stdClass();
                     $e->s = $objIri;
                     $e->p = $key;
                     $props->$iri->all[] = $e;
                  }
               }
            }
         }
      }

      // create sorted categories
      foreach($refs as $iri => $ref)
      {
         usort($ref->all, array($this, 'compareEdges'));
         $ref->bnodes = array_filter($ref->all, '_filterBlankNodeEdges');
      }
      foreach($props as $iri => $prop)
      {
         usort($prop->all, array($this, 'compareEdges'));
         $prop->bnodes = array_filter($prop->all, '_filterBlankNodeEdges');
      }
   }
}

/**
 * Returns true if the given input is a subject and has one of the given types
 * in the given frame.
 *
 * @param input the input.
 * @param frame the frame with types to look for.
 *
 * @return true if the input has one of the given types.
 */
function _isType($input, $frame)
{
   $rval = false;

   // check if type(s) are specified in frame and input
   $type = '@type';
   if(property_exists($frame, $type) and
      is_object($input) and
      property_exists($input, $type))
   {
      $tmp = is_array($input->$type) ? $input->$type : array($input->$type);
      $types = is_array($frame->$type) ? $frame->$type : array($frame->$type);
      $length = count($types);
      for($t = 0; $t < $length and !$rval; ++$t)
      {
         $type = $types[$t];
         foreach($tmp as $e)
         {
            if($e === $type)
            {
               $rval = true;
               break;
            }
         }
      }
   }

   return $rval;
}

/**
 * Filters non-keywords.
 *
 * @param e the element to check.
 *
 * @return true if the element is a non-keyword.
 */
function _filterNonKeyWords($e)
{
   return strpos($e, '@') !== 0;
}

/**
 * Returns true if the given input matches the given frame via duck-typing.
 *
 * @param input the input.
 * @param frame the frame to check against.
 *
 * @return true if the input matches the frame.
 */
function _isDuckType($input, $frame)
{
   $rval = false;

   // frame must not have a specific type
   if(!property_exists($frame, '@type'))
   {
      // get frame properties that must exist on input
      $props = array_filter(array_keys((array)$frame), '_filterNonKeywords');
      if(count($props) === 0)
      {
         // input always matches if there are no properties
         $rval = true;
      }
      // input must be a subject with all the given properties
      else if(is_object($input) and property_exists($input, '@id'))
      {
         $rval = true;
         foreach($props as $prop)
         {
            if(!property_exists($input, $prop))
            {
               $rval = false;
               break;
            }
         }
      }
   }

   return $rval;
}

/**
 * Recursively removes dependent dangling embeds.
 *
 * @param iri the iri of the parent to remove embeds for.
 * @param embeds the embeds map.
 */
function removeDependentEmbeds($iri, $embeds)
{
   $iris = get_object_vars($embeds);
   foreach($iris as $i => $embed)
   {
      if($embed->parent !== null and
         $embed->parent->{'@id'} === $iri)
      {
         unset($embeds->$i);
         removeDependentEmbeds($i, $embeds);
      }
   }
}

/**
 * Subframes a value.
 *
 * @param subjects a map of subjects in the graph.
 * @param value the value to subframe.
 * @param frame the frame to use.
 * @param embeds a map of previously embedded subjects, used to prevent cycles.
 * @param autoembed true if auto-embed is on, false if not.
 * @param parent the parent object.
 * @param parentKey the parent object.
 * @param options the framing options.
 *
 * @return the framed input.
 */
function _subframe(
   $subjects, $value, $frame, $embeds, $autoembed,
   $parent, $parentKey, $options)
{
   // get existing embed entry
   $iri = $value->{'@id'};
   $embed = property_exists($embeds, $iri) ? $embeds->{$iri} : null;

   // determine if value should be embedded or referenced,
   // embed is ON if:
   // 1. The frame OR default option specifies @embed as ON, AND
   // 2. There is no existing embed OR it is an autoembed, AND
   //    autoembed mode is off.
   $embedOn = (
      ((property_exists($frame, '@embed') and $frame->{'@embed'}) or
      (!property_exists($frame, '@embed') and $options->defaults->embedOn)) and
      ($embed === null or ($embed->autoembed and !$autoembed)));

   if(!$embedOn)
   {
      // not embedding, so only use subject IRI as reference
      $tmp = new stdClass();
      $tmp->{'@id'} = $value->{'@id'};
      $value = $tmp;
   }
   else
   {
      // create new embed entry
      if($embed === null)
      {
         $embed = new stdClass();
         $embeds->{$iri} = $embed;
      }
      // replace the existing embed with a reference
      else if($embed->parent !== null)
      {
         if(is_array($embed->parent->{$embed->key}))
         {
            // find and replace embed in array
            $arrLen = count($embed->parent->{$embed->key});
            for($i = 0; $i < $arrLen; ++$i)
            {
               $obj = $embed->parent->{$embed->key}[$i];
               if(is_object($obj) and property_exists($obj, '@id') and
                  $obj->{'@id'} === $iri)
               {
                  $tmp = new stdClass();
                  $tmp->{'@id'} = $value->{'@id'};
                  $embed->parent->{$embed->key}[$i] = $tmp;
                  break;
               }
            }
         }
         else
         {
            $tmp = new stdClass();
            $tmp->{'@id'} = $value->{'@id'};
            $embed->parent->{$embed->key} = $tmp;
         }

         // recursively remove any dependent dangling embeds
         removeDependentEmbeds($iri, $embeds);
      }

      // update embed entry
      $embed->autoembed = $autoembed;
      $embed->parent = $parent;
      $embed->key = $parentKey;

      // check explicit flag
      $explicitOn = property_exists($frame, '@explicit') ?
         $frame->{'@explicit'} : $options->defaults->explicitOn;
      if($explicitOn)
      {
         // remove keys from the value that aren't in the frame
         foreach($value as $key => $v)
         {
            // do not remove @id or any frame key
            if($key !== '@id' and !property_exists($frame, $key))
            {
               unset($value->$key);
            }
         }
      }

      // iterate over keys in value
      $vars = get_object_vars($value);
      foreach($vars as $key => $v)
      {
         // skip keywords
         if(strpos($key, '@') !== 0)
         {
            // get the subframe if available
            if(property_exists($frame, $key))
            {
               $f = $frame->{$key};
               $_autoembed = false;
            }
            // use a catch-all subframe to preserve data from graph
            else
            {
               $f = is_array($v) ? array() : new stdClass();
               $_autoembed = true;
            }

            // build input and do recursion
            $input = is_array($v) ? $v : array($v);
            $length = count($input);
            for($n = 0; $n < $length; ++$n)
            {
               // replace reference to subject w/embedded subject
               if(is_object($input[$n]) and
                  property_exists($input[$n], '@id') and
                  property_exists($subjects, $input[$n]->{'@id'}))
               {
                  $input[$n] = $subjects->{$input[$n]->{'@id'}};
               }
            }
            $value->$key = _frame(
               $subjects, $input, $f, $embeds, $_autoembed,
               $value, $key, $options);
         }
      }

      // iterate over frame keys to add any missing values
      foreach($frame as $key => $f)
      {
         // skip keywords and non-null keys in value
         if(strpos($key, '@') !== 0 and
            (!property_exists($value, $key) || $value->{$key} === null))
         {
            // add empty array to value
            if(is_array($f))
            {
               // add empty array/null property to value
               $value->$key = array();
            }
            // add default value to value
            else
            {
               // use first subframe if frame is an array
               if(is_array($f))
               {
                  $f = (count($f) > 0) ? $f[0] : new stdClass();
               }

               // determine if omit default is on
               $omitOn = property_exists($f, '@omitDefault') ?
                  $f->{'@omitDefault'} :
                  $options->defaults->omitDefaultOn;
               if(!$omitOn)
               {
                  if(property_exists($f, '@default'))
                  {
                     // use specified default value
                     $value->{$key} = $f->{'@default'};
                  }
                  else
                  {
                     // build-in default value is: null
                     $value->{$key} = null;
                  }
               }
            }
         }
      }
   }

   return $value;
}

/**
 * Recursively frames the given input according to the given frame.
 *
 * @param subjects a map of subjects in the graph.
 * @param input the input to frame.
 * @param frame the frame to use.
 * @param embeds a map of previously embedded subjects, used to prevent cycles.
 * @param autoembed true if auto-embed is on, false if not.
 * @param parent the parent object (for subframing), null for none.
 * @param parentKey the parent key (for subframing), null for none.
 * @param options the framing options.
 *
 * @return the framed input.
 */
function _frame(
   $subjects, $input, $frame, $embeds, $autoembed,
   $parent, $parentKey, $options)
{
   $rval = null;

   // prepare output, set limit, get array of frames
   $limit = -1;
   if(is_array($frame))
   {
      $rval = array();
      $frames = $frame;
      if(count($frames) == 0)
      {
         $frames[] = new stdClass();
      }
   }
   else
   {
      $frames = array($frame);
      $limit = 1;
   }

   // iterate over frames adding input matches to list
   $frameLen = count($frames);
   $values = array();
   for($i = 0; $i < $frameLen and $limit !== 0; ++$i)
   {
      // get next frame
      $frame = $frames[$i];
      if(!is_object($frame))
      {
         throw new Exception(
            'Invalid JSON-LD frame. Frame type is not a map or array.');
      }

      // create array of values for each frame
      $inLen = count($input);
      $v = array();
      for($n = 0; $n < $inLen and $limit !== 0; ++$n)
      {
         // dereference input if it refers to a subject
         $next = $input[$n];
         if(is_object($next) and property_exists($next, '@id') and
            property_exists($subjects, $next->{'@id'}))
         {
            $next = $subjects->{$next->{'@id'}};
         }

         // add input to list if it matches frame specific type or duck-type
         if(_isType($next, $frame) or _isDuckType($next, $frame))
         {
            $v[] = $next;
            --$limit;
         }
      }
      $values[$i] = $v;
   }

   // for each matching value, add it to the output
   $vaLen = count($values);
   for($i1 = 0; $i1 < $vaLen; ++$i1)
   {
      foreach($values[$i1] as $value)
      {
         $frame = $frames[$i1];

         // if value is a subject, do subframing
         if(_isSubject($value))
         {
            $value = _subframe(
               $subjects, $value, $frame, $embeds, $autoembed,
               $parent, $parentKey, $options);
         }

         // add value to output
         if($rval === null)
         {
            $rval = $value;
         }
         else
         {
            // determine if value is a reference to an embed
            $isRef = (_isReference($value) and
               property_exists($embeds, $value->{'@id'}));

            // push any value that isn't a parentless reference
            if(!($parent === null and $isRef))
            {
               $rval[] = $value;
            }
         }
      }
   }

   return $rval;
}

?>
