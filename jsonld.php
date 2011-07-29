<?php
/**
 * PHP implementation of JSON-LD.
 *
 * @author Dave Longley
 *
 * Copyright (c) 2011 Digital Bazaar, Inc. All rights reserved.
 */
define('__S', '@subject');
define('__T', '@type');
define('JSONLD_RDF', 'http://www.w3.org/1999/02/22-rdf-syntax-ns#');
define('JSONLD_RDF_TYPE', JSONLD_RDF . 'type');
define('JSONLD_XSD', 'http://www.w3.org/2001/XMLSchema#');
define('JSONLD_XSD_ANY_TYPE', JSONLD_XSD . 'anyType');
define('JSONLD_XSD_BOOLEAN', JSONLD_XSD . 'boolean');
define('JSONLD_XSD_DOUBLE', JSONLD_XSD . 'double');
define('JSONLD_XSD_INTEGER', JSONLD_XSD . 'integer');
define('JSONLD_XSD_ANY_URI', JSONLD_XSD . 'anyURI');

/**
 * Creates the JSON-LD default context.
 *
 * @return the JSON-LD default context.
 */
function jsonld_create_default_context()
{
   return (object)array(
      'rdf' => JSONLD_RDF,
      'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
      'owl' => 'http://www.w3.org/2002/07/owl#',
      'xsd' => 'http://www.w3.org/2001/XMLSchema#',
      'dcterms' => 'http://purl.org/dc/terms/',
      'foaf' => 'http://xmlns.com/foaf/0.1/',
      'cal' => 'http://www.w3.org/2002/12/cal/ical#',
      'vcard' => 'http://www.w3.org/2006/vcard/ns#',
      'geo' => 'http://www.w3.org/2003/01/geo/wgs84_pos#',
      'cc' => 'http://creativecommons.org/ns#',
      'sioc' => 'http://rdfs.org/sioc/ns#',
      'doap' => 'http://usefulinc.com/ns/doap#',
      'com' => 'http://purl.org/commerce#',
      'ps' => 'http://purl.org/payswarm#',
      'gr' => 'http://purl.org/goodrelations/v1#',
      'sig' => 'http://purl.org/signature#',
      'ccard' => 'http://purl.org/commerce/creditcard#',
      '@coerce' => (object)array(
         'xsd:anyURI' => array('foaf:homepage', 'foaf:member'),
         'xsd:integer' => 'foaf:age'
      ),
      '@vocab' => ''
   );
}

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
};

/**
 * Removes the context from a JSON-LD object.
 *
 * @param input the JSON-LD object to remove the context from.
 *
 * @return the context-neutral JSON-LD object.
 */
function jsonld_remove_context($input)
{
   $rval = null;

   if($input !== null)
   {
      $ctx = jsonld_create_default_context();
      $rval = _expand($ctx, null, $input, false);
   }

   return $rval;
};
function jsonld_expand($input)
{
   return jsonld_remove_context($input);
}

/**
 * Adds the given context to the given context-neutral JSON-LD object.
 *
 * @param ctx the new context to use.
 * @param input the context-neutral JSON-LD object to add the context to.
 *
 * @return the JSON-LD object with the new context.
 */
function jsonld_add_context($ctx, $input)
{
   $rval;

   // TODO: should context simplification be optional? (ie: remove context
   // entries that are not used in the output)

   $ctx = jsonld_merge_contexts(jsonld_create_default_context(), $ctx);

   // setup output context
   $ctxOut = new stdClass();

   // compact
   $rval = _compact($ctx, null, $input, $ctxOut);

   // add context if used
   if(count(array_keys((array)$ctxOut)) > 0)
   {
      // add copy of context to every entry in output array
      if(is_array($rval))
      {
         foreach($rval as $v)
         {
            $v->{'@context'} = _cloneContext($ctxOut);
         }
      }
      else
      {
         $rval->{'@context'} = $ctxOut;
      }
   }

   return $rval;
}

/**
 * Changes the context of JSON-LD object "input" to "context", returning the
 * output.
 *
 * @param ctx the new context to use.
 * @param input the input JSON-LD object.
 *
 * @return the output JSON-LD object.
 */
function jsonld_change_context($ctx, $input)
{
   // remove context and then add new one
   return jsonld_add_context($ctx, jsonld_remove_context($input));
}
function jsonld_compact($ctx, $input)
{
   return jsonld_change_context($ctx, $input);
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
   // copy contexts
   $merged = _cloneContext($ctx1);
   $copy = _cloneContext($ctx2);

   // if the new context contains any IRIs that are in the merged context,
   // remove them from the merged context, they will be overwritten
   foreach($copy as $key => $value)
   {
      // ignore special keys starting with '@'
      if(strpos($key, '@') !== 0)
      {
         foreach($merged as $mkey => $mvalue)
         {
            if($mvalue === $value)
            {
               unset($merged->$mkey);
               break;
            }
         }
      }
   }

   // @coerce must be specially-merged, remove from contexts
   $coerceExists =
      property_exists($merged, '@coerce') or
      property_exists($copy, '@coerce');
   if($coerceExists)
   {
      $c1 = property_exists($merged, '@coerce') ?
         $merged->{'@coerce'} : new stdClass();
      $c2 = property_exists($copy, '@coerce') ?
         $copy->{'@coerce'} : new stdClass();
      unset($merged->{'@coerce'});
      unset($copy->{'@coerce'});
   }

   // merge contexts
   foreach($copy as $key => $value)
   {
      $merged->$key = $value;
   }

   // special-merge @coerce
   if($coerceExists)
   {
      foreach($c1 as $type => $p1)
      {
         // append existing-type properties that don't already exist
         if(property_exists($c2, $type))
         {
            $p2 = $c2->$type;

            // normalize props in c2 to array for single-code-path iterating
            if(!is_array($p2))
            {
               $p2 = array($p2);
            }

            // add unique properties from p2 to p1
            foreach($p2 as $i => $p)
            {
               if((!is_array($p1) and $p1 !== $p) or
                  (is_array($p1) and array_search($p, $p1) === false))
               {
                  if(is_array($p1))
                  {
                     $p1[] = $p;
                  }
                  else
                  {
                     $p1 = array($p1, $p);
                  }
               }
            }

            $c1->$type = $p1;
         }
      }

      // add new types from new @coerce
      foreach($c2 as $type => $value)
      {
         if(!property_exists($c1, $type))
         {
            $c1->$type = $value;
         }
      }

      // ensure there are no property duplicates in @coerce
      $unique = new stdClass();
      $dups = array();
      foreach($c1 as $type => $p)
      {
         if(is_string($p))
         {
            $p = array($p);
         }
         foreach($p as $v)
         {
            if(!property_exists($unique, $v))
            {
               $unique->$v = true;
            }
            else if(!in_array($v, $dups))
            {
               $dups[] = $v;
            }
         }
      }

      if(count($dups)> 0)
      {
         throw new Exception(
            'Invalid type coercion specification. More than one ' .
            'type specified for at least one property. duplicates=' .
            print_r(dups, true));
      }

      $merged->{'@coerce'} = $c1;
   }

   return $merged;
}

/**
 * Expands a term into an absolute IRI. The term may be a regular term, a
 * CURIE, a relative IRI, or an absolute IRI. In any case, the associated
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
 * Compacts an IRI into a term or CURIE if it can be. IRIs will not be
 * compacted to relative IRIs if they match the given context's default
 * vocabulary.
 *
 * @param ctx the context to use.
 * @param iri the IRI to compact.
 *
 * @return the compacted IRI as a term or CURIE or the original IRI.
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
   if(property_exists($frame, '@context'))
   {
      $ctx = jsonld_merge_contexts(
         jsonld_create_default_context(), $frame->{'@context'});
   }

   // remove context from frame
   $frame = jsonld_remove_context($frame);

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
      $subjects->{$i->{__S}->{'@iri'}} = $i;
   }

   // frame input
   $rval = _frame($subjects, $input, $frame, new stdClass(), $options);

   // apply context
   if($ctx !== null and $rval !== null)
   {
      $rval = jsonld_add_context($ctx, $rval);
   }

   return $rval;
}

/**
 * Compacts an IRI into a term or CURIE if it can be. IRIs will not be
 * compacted to relative IRIs if they match the given context's default
 * vocabulary.
 *
 * @param ctx the context to use.
 * @param iri the IRI to compact.
 * @param usedCtx a context to update if a value was used from "ctx".
 *
 * @return the compacted IRI as a term or CURIE or the original IRI.
 */
function _compactIri($ctx, $iri, $usedCtx)
{
   $rval = null;

   // check the context for a term that could shorten the IRI
   // (give preference to terms over CURIEs)
   foreach($ctx as $key => $value)
   {
      // skip special context keys (start with '@')
      if(strlen($key) > 0 and $key[0] !== '@')
      {
         // compact to a term
         if($iri === $ctx->$key)
         {
            $rval = $key;
            if($usedCtx !== null)
            {
               $usedCtx->$key = $ctx->$key;
            }
            break;
         }
      }
   }

   // term not found, if term is rdf type, use built-in keyword
   if($rval === null and $iri === JSONLD_RDF_TYPE)
   {
      $rval = __T;
   }

   // term not found, check the context for a CURIE prefix
   if($rval === null)
   {
      foreach($ctx as $key => $value)
      {
         // skip special context keys (start with '@')
         if(strlen($key) > 0 and $key[0] !== '@')
         {
            // see if IRI begins with the next IRI from the context
            $ctxIri = $ctx->$key;
            $idx = strpos($iri, $ctxIri);

            // compact to a CURIE
            if($idx === 0 and strlen($iri) > strlen($ctxIri))
            {
               $rval = $key . ':' . substr($iri, strlen($ctxIri));
               if($usedCtx !== null)
               {
                  $usedCtx->$key = $ctxIri;
               }
               break;
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
 * CURIE, a relative IRI, or an absolute IRI. In any case, the associated
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
   $rval;

   // 1. If the property has a colon, then it is a CURIE or an absolute IRI:
   $idx = strpos($term, ':');
   if($idx !== false)
   {
      // get the potential CURIE prefix
      $prefix = substr($term, 0, $idx);

      // 1.1. See if the prefix is in the context:
      if(property_exists($ctx, $prefix))
      {
         // prefix found, expand property to absolute IRI
         $rval = $ctx->$prefix . substr($term, $idx + 1);
         if($usedCtx !== null)
         {
            $usedCtx->$prefix = $ctx->$prefix;
         }
      }
      // 1.2. Prefix is not in context, property is already an absolute IRI:
      else
      {
         $rval = $term;
      }
   }
   // 2. If the property is in the context, then it's a term.
   else if(property_exists($ctx, $term))
   {
      $rval = $ctx->$term;
      if($usedCtx !== null)
      {
         $usedCtx->$term = $rval;
      }
   }
   // 3. The property is the special-case subject.
   else if($term === __S)
   {
      $rval = __S;
   }
   // 4. The property is the special-case rdf type.
   else if($term === __T)
   {
      $rval = JSONLD_RDF_TYPE;
   }
   // 5. The property is a relative IRI, prepend the default vocab.
   else
   {
      $rval = $ctx->{'@vocab'} . $term;
      if($usedCtx !== null)
      {
         $usedCtx->{'@vocab'} = $ctx->{'@vocab'};
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
 * Clones a string/number or an object and sorts the keys. Deep clone
 * is not performed. This function will deep copy arrays, but that feature
 * isn't needed in this implementation at present. If it is needed in the
 * future, it will have to be implemented here.
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
         $rval->$key = $value->$key;
      }
   }
   else
   {
      $rval = $value;
   }

   return $rval;
}

/**
 * Clones a context.
 *
 * @param ctx the context to clone.
 *
 * @return the clone of the context.
 */
function _cloneContext($ctx)
{
   $rval = new stdClass();
   foreach($ctx as $key => $value)
   {
      // deep-copy @coerce
      if($key === '@coerce')
      {
         $rval->{'@coerce'} = new stdClass();
         foreach($ctx->{'@coerce'} as $type => $p)
         {
            $rval->{'@coerce'}->$type = $p;
         }
      }
      else
      {
         $rval->$key = $ctx->$key;
      }
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
function _getCoerceType($ctx, $property, $usedCtx)
{
   $rval = null;

   // get expanded property
   $p = _expandTerm($ctx, $property, null);

   // built-in type coercion JSON-LD-isms
   if($p === __S or $p === JSONLD_RDF_TYPE)
   {
      $rval = JSONLD_XSD_ANY_URI;
   }
   // check type coercion for property
   else
   {
      // force compacted property
      $p = _compactIri($ctx, $p, null);

      foreach($ctx->{'@coerce'} as $type => $props)
      {
         // get coerced properties (normalize to an array)
         if(!is_array($props))
         {
            $props = array($props);
         }

         // look for the property in the array
         foreach($props as $prop)
         {
            // property found
            if($prop === $p)
            {
               $rval = _expandTerm($ctx, $type, $usedCtx);
               if($usedCtx !== null)
               {
                  if(!property_exists($usedCtx, '@coerce'))
                  {
                     $usedCtx->{'@coerce'} = new stdClass();
                  }

                  if(!property_exists($usedCtx->{'@coerce'}, $type))
                  {
                     $usedCtx->{'@coerce'}->$type = $p;
                  }
                  else
                  {
                     $c = $usedCtx->{'@coerce'}->$type;
                     if(is_array($c) and in_array($p, $c) or
                        is_string($c) and $c !== $p)
                     {
                        _setProperty($usedCtx->{'@coerce'}, $type, $p);
                     }
                  }
               }
               break;
            }
         }
      }
   }

   return $rval;
}

/**
 * Recursively compacts a value. This method will compact IRIs to CURIEs or
 * terms and do reverse type coercion to compact a value.
 *
 * @param ctx the context to use.
 * @param property the property that points to the value, NULL for none.
 * @param value the value to compact.
 * @param usedCtx a context to update if a value was used from "ctx".
 *
 * @return the compacted value.
 */
function _compact($ctx, $property, $value, $usedCtx)
{
   $rval;

   if($value === null)
   {
      // return null, but check coerce type to add to usedCtx
      $rval = null;
      _getCoerceType($ctx, $property, $usedCtx);
   }
   else if(is_array($value))
   {
      // recursively add compacted values to array
      $rval = array();
      foreach($value as $v)
      {
         $rval[] = _compact($ctx, $property, $v, $usedCtx);
      }
   }
   // graph literal/disjoint graph
   else if(
      is_object($value) and
      property_exists($value, __S) and is_array($value->{__S}))
   {
      $rval = new stdClass();
      $rval->{__S} = _compact($ctx, $property, $value->{__S}, $usedCtx);
   }
   // value has sub-properties if it doesn't define a literal or IRI value
   else if(
      is_object($value) and
      !property_exists($value, '@literal') and
      !property_exists($value, '@iri'))
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
               $rval->$p = _compact($ctx, $key, $v, $usedCtx);
            }
         }
      }
   }
   else
   {
      // get coerce type
      $coerce = _getCoerceType($ctx, $property, $usedCtx);

      // get type from value, to ensure coercion is valid
      $type = null;
      if(is_object($value))
      {
         // type coercion can only occur if language is not specified
         if(!property_exists($value, '@language'))
         {
            // datatype must match coerce type if specified
            if(property_exists($value, '@datatype'))
            {
               $type = $value->{'@datatype'};
            }
            // datatype is IRI
            else if(property_exists($value, '@iri'))
            {
               $type = JSONLD_XSD_ANY_URI;
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
               'Cannot coerce type because the datatype does not match.');
         }
         // do reverse type-coercion
         else
         {
            if(is_object($value))
            {
               if(property_exists($value, '@iri'))
               {
                  $rval = $value->{'@iri'};
               }
               else if(property_exists($value, '@literal'))
               {
                  $rval = $value->{'@literal'};
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
      // no type-coercion, just copy value
      else
      {
         $rval = _clone($value);
      }

      // compact IRI
      if($type === JSONLD_XSD_ANY_URI)
      {
         if(is_object($rval))
         {
            $rval->{'@iri'} = _compactIri($ctx, $rval->{'@iri'}, $usedCtx);
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
 * @param expandSubjects true to expand subjects (normalize), false not to.
 *
 * @return the expanded value.
 */
function _expand($ctx, $property, $value, $expandSubjects)
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
         $rval[] = _expand($ctx, $property, $v, $expandSubjects);
      }
   }
   else if(is_object($value))
   {
      // value has sub-properties if it doesn't define a literal or IRI value
      if(!(property_exists($value, '@literal') or
         property_exists($value, '@iri')))
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
                  _expand($ctx, $key, $v, $expandSubjects));
            }
         }
      }
      // value is already expanded
      else
      {
         $rval = _clone($value);
      }
   }
   else
   {
      // do type coercion
      $coerce = _getCoerceType($ctx, $property, null);

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

      // coerce to appropriate datatype, only expand subjects if requested
      if($coerce !== null and ($property !== __S or $expandSubjects))
      {
         $rval = new stdClass();

         // expand IRI
         if($coerce === JSONLD_XSD_ANY_URI)
         {
            $rval->{'@iri'} = _expandTerm($ctx, $value, null);
         }
         // other datatype
         else
         {
            $rval->{'@datatype'} = $coerce;
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
            $rval->{'@literal'} = '' . $value;
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

function _isBlankNodeIri($v)
{
   return strpos($v, '_:') === 0;
}

function _isNamedBlankNode($v)
{
   // look for "_:" at the beginning of the subject
   return (
      is_object($v) and property_exists($v, __S) and
      property_exists($v->{__S}, '@iri') and
      _isBlankNodeIri($v->{__S}->{'@iri'}));
}

function _isBlankNode($v)
{
   // look for no subject or named blank node
   return (
      is_object($v) and
      !(property_exists($v, '@iri') or property_exists($v, '@literal')) and
      (!property_exists($v, __S) or _isNamedBlankNode($v)));
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
      $rval = _compareObjectKeys($o1, $o2, '@literal');
      if($rval === 0)
      {
         if(property_exists($o1, '@literal'))
         {
            $rval = _compareObjectKeys($o1, $o2, '@datatype');
            if($rval === 0)
            {
               $rval = _compareObjectKeys($o1, $o2, '@language');
            }
         }
         // both are '@iri' objects
         else
         {
            $rval = _compare($o1->{'@iri'}, $o2->{'@iri'});
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
   return (is_string($e) or
      !(property_exists($e, '@iri') and _isBlankNodeIri($e->{'@iri'})));
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
   3.2. For each object value, compare only literals and non-bnodes.
   3.2.1.  The bnode with fewer non-bnodes is first.
   3.2.2. The bnode with a string object is first.
   3.2.3. The bnode with the alphabetically-first string is first.
   3.2.4. The bnode with a @literal is first.
   3.2.5. The bnode with the alphabetically-first @literal is first.
   3.2.6. The bnode with the alphabetically-first @datatype is first.
   3.2.7. The bnode with a @language is first.
   3.2.8. The bnode with the alphabetically-first @language is first.
   3.2.9. The bnode with the alphabetically-first @iri is first.
   */

   foreach($a as $p => $value)
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

         // filter non-bnodes (remove bnodes from comparison)
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
      if(property_exists($input, __S))
      {
         // graph literal
         if(is_array($input->{__S}))
         {
            _collectSubjects($input->{__S}, $subjects, $bnodes);
         }
         // named subject
         else
         {
            $subjects->{$input->{__S}->{'@iri'}} = $input;
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
 * Filters duplicate IRIs.
 */
class DuplicateIriFilter
{
   public function __construct($iri)
   {
      $this->iri = $iri;
   }

   public function filter($e)
   {
      return (is_object($e) and property_exists($e, '@iri') and
         $e->{'@iri'} === $this->iri);
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

   if(is_array($value))
   {
      // list of objects or a disjoint graph
      foreach($value as $v)
      {
         _flatten($parent, $parentProperty, $v, $subjects);
      }
   }
   else if(is_object($value))
   {
      // graph literal/disjoint graph
      if(property_exists($value, __S) and is_array($value->{__S}))
      {
         // cannot flatten embedded graph literals
         if($parent !== null)
         {
            throw new Exception('Embedded graph literals cannot be flattened.');
         }

         // top-level graph literal
         foreach($value->{__S} as $k => $v)
         {
            _flatten($parent, $parentProperty, $v, $subjects);
         }
      }
      // already-expanded value
      else if(
         property_exists($value, '@literal') or
         property_exists($value, '@iri'))
      {
         $flattened = _clone($value);
      }
      // subject
      else
      {
         // create or fetch existing subject
         if(property_exists($subjects, $value->{__S}->{'@iri'}))
         {
            // FIXME: __S might be a graph literal (as {})
            $subject = $subjects->{$value->{__S}->{'@iri'}};
         }
         else
         {
            $subject = new stdClass();
            if(property_exists($value, __S))
            {
               // FIXME: __S might be a graph literal (as {})
               $subjects->{$value->{__S}->{'@iri'}} = $subject;
            }
         }
         $flattened = $subject;

         // flatten embeds
         foreach($value as $key => $v)
         {
            // drop null values
            if($v !== null)
            {
               if(is_array($v))
               {
                  $subject->$key = new ArrayObject();
                  _flatten($subject->$key, null, $value->$key, $subjects);
                  $subject->$key = (array)$subject->$key;
                  if(count($subject->$key) === 1)
                  {
                     // convert subject[key] to object if it has only 1
                     $arr = $subject->$key;
                     $subject->$key = $arr[0];
                  }
               }
               else
               {
                  _flatten($subject, $key, $value->$key, $subjects);
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
      // remove top-level __s for subjects
      // 'http://mypredicate': {'@subject': {'@iri': 'http://mysubject'}}
      // becomes
      // 'http://mypredicate': {'@iri': 'http://mysubject'}
      if(is_object($flattened) and property_exists($flattened, __S))
      {
         $flattened = $flattened->{__S};
      }

      if($parent instanceof ArrayObject)
      {
         // do not add duplicate IRIs for the same property
         $duplicate = false;
         if(is_object($flattened) and property_exists($flattened, '@iri'))
         {
            $duplicate = count(array_filter(
               (array)$parent, array(
                  new DuplicateIriFilter($flattened->{'@iri'}), 'filter'))) > 0;
         }
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
};

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
      $this->mapped = new stdClass();
      $this->mapping = new stdClass();
      $this->output = new stdClass();
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
      $rval->mapped = _clone($this->mapped);
      $rval->mapping = _clone($this->mapping);
      $rval->output = _clone($this->output);
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
   return _compare($a->{__S}->{'@iri'}, $b->{__S}->{'@iri'});
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
 * A container for JSON-LD processing state.
 */
class JsonLdProcessor
{
   /**
    * Constructs a JSON-LD processor.
    */
   public function __construct()
   {
      $this->ng = new stdClass();
      $this->ng->tmp = null;
      $this->ng->c14n = null;
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
         // get default context
         $ctx = jsonld_create_default_context();

         // expand input
         $expanded = _expand($ctx, null, $input, true);

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
         if(!property_exists($bnode, __S))
         {
            // generate names until one is unique
            while(property_exists($subjects, $ng->next()));
            $bnode->{__S} = new stdClass();
            $bnode->{__S}->{'@iri'} = $ng->current();
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
      $old = $b->{__S}->{'@iri'};

      // update bnode IRI
      $b->{__S}->{'@iri'} = $id;

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
                     property_exists($tmp[$n], '@iri') and
                     $tmp[$n]->{'@iri'} === $old)
                  {
                     $tmp[$n]->{'@iri'} = $id;
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
         $iri = $v->{__S}->{'@iri'};
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
         $iri = $bnode->{__S}->{'@iri'};
         if($c14n->inNamespace($iri))
         {
            // generate names until one is unique
            while(property_exists($subjects, $ngTmp->next()));
            $this->renameBlankNode($bnode, $ngTmp->current());
            $iri = $bnode->{__S}->{'@iri'};
         }
         $this->serializations->$iri = new stdClass();
         $this->serializations->$iri->props = null;
         $this->serializations->$iri->refs = null;
      }

      // keep sorting and naming blank nodes until they are all named
      while(count($bnodes) > 0)
      {
         usort($bnodes, array($this, 'deepCompareBlankNodes'));

         // name all bnodes according to the first bnode's relation mappings
         $bnode = array_shift($bnodes);
         $iri = $bnode->{__S}->{'@iri'};
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
               $iriB = $b->{__S}->{'@iri'};
               if(!$c14n->inNamespace($iriB))
               {
                  // mark serializations related to the named bnodes as dirty
                  foreach($renamed as $r)
                  {
                     $this->markSerializationDirty($iriB, $r, $dir);
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
    */
   public function markSerializationDirty($iri, $changed, $dir)
   {
      $s = $this->serializations->$iri;
      if($s->$dir !== null and property_exists($s->$dir->m, $changed))
      {
         $s->$dir = null;
      }
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

      foreach($b as $p => $o)
      {
         if($p !== '@subject')
         {
            $first = true;
            $objs = is_array($o) ? $o : array($o);
            foreach($objs as $obj)
            {
               if($first)
               {
                  $first = false;
               }
               else
               {
                  $rval .= '|';
               }
               if(is_object($obj) and property_exists($obj, '@iri') and
                  _isBlankNodeIri($obj->{'@iri'}))
               {
                  $rval .= '_:';
               }
               else
               {
                  $rval .= str_replace('\\/', '/', json_encode($obj));
               }
            }
         }
      }

      return $rval;
   }

   /**
    * Recursively creates a relation serialization (partial or full).
    *
    * @param keys the keys to serialize in the current output.
    * @param output the current mapping builder output.
    * @param done the already serialized keys.
    *
    * @return the relation serialization.
    */
   public function recursiveSerializeMapping($keys, $output, $done)
   {
      $rval = '';
      foreach($keys as $k)
      {
         if(!property_exists($output, $k))
         {
            break;
         }

         if(property_exists($done, $k))
         {
            // mark cycle
            $rval .= '_' . $k;
         }
         else
         {
            $done->$k = true;
            $tmp = $output->$k;
            foreach($tmp->k as $s)
            {
               $rval .= $s;
               $iri = $tmp->m->$s;
               if(property_exists($this->subjects, $iri))
               {
                  $b = $this->subjects->$iri;

                  // serialize properties
                  $rval .= '<';
                  $rval .= $this->serializeProperties($b);
                  $rval .= '>';

                  // serialize references
                  $rval .= '<';
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
                        $rval .= '|';
                     }
                     $rval .= _isBlankNodeIri($r->s) ? '_:' : $refs->s;
                  }
                  $rval .= '>';
               }
            }

            $rval .= $this->recursiveSerializeMapping($tmp->k, $output, $done);
         }
      }
      return $rval;
   }

   /**
    * Creates a relation serialization (partial or full).
    *
    * @param output the current mapping builder output.
    *
    * @return the relation serialization.
    */
   public function serializeMapping($output)
   {
      return $this->recursiveSerializeMapping(
         array('s1'), $output, new stdClass());
   }

   /**
    * Recursively serializes adjacent bnode combinations.
    *
    * @param s the serialization to update.
    * @param top the top of the serialization.
    * @param mb the MappingBuilder to use.
    * @param dir the edge direction to use ('props' or 'refs').
    * @param mapped all of the already-mapped adjacent bnodes.
    * @param notMapped all of the not-yet mapped adjacent bnodes.
    */
   public function serializeCombos(
      $s, $top, $mb, $dir, $mapped, $notMapped)
   {
      // copy mapped nodes
      $mapped = _clone($mapped);

      // handle recursion
      if(count($notMapped) > 0)
      {
         // map first bnode in list
         $mapped->{$mb->mapNode($notMapped[0]->s)} = $notMapped[0]->s;

         // recurse into remaining possible combinations
         $original = $mb->copy();
         $notMapped = array_slice($notMapped, 1);
         $rotations = max(1, count($notMapped));
         for($r = 0; $r < $rotations; ++$r)
         {
            $m = ($r === 0) ? $mb : $original->copy();
            $this->serializeCombos($s, $top, $m, $dir, $mapped, $notMapped);

            // rotate not-mapped for next combination
            _rotate($notMapped);
         }
      }
      // handle final adjacent node in current combination
      else
      {
         $keys = array_keys((array)$mapped);
         sort($keys);
         $mb->output->$top = new stdClass();
         $mb->output->$top->k = $keys;
         $mb->output->$top->m = $mapped;

         // optimize away mappings that are already too large
         $_s = $this->serializeMapping($mb->output);
         if($s->$dir === null or _compareSerializations($_s, $s->$dir->s) <= 0)
         {
            $oldCount = $mb->count;

            // recurse into adjacent values
            foreach($keys as $i => $k)
            {
               $this->serializeBlankNode($s, $mapped->$k, $mb, $dir);
            }

            // reserialize if more nodes were mapped
            if($mb->count > $oldCount)
            {
               $_s = $this->serializeMapping($mb->output);
            }

            // update least serialization if new one has been found
            if($s->$dir === null or
               (_compareSerializations($_s, $s->$dir->s) <= 0 and
               count($_s) >= count($s->$dir->s)))
            {
               $s->$dir = new stdClass();
               $s->$dir->s = $_s;
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
      // only do mapping if iri not already mapped
      if(!property_exists($mb->mapped, $iri))
      {
         // iri now mapped
         $mb->mapped->$iri = true;
         $top = $mb->mapNode($iri);

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
            $this->serializeCombos($s, $top, $mb, $dir, $mapped, $notMapped);
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
      $iriA = $a->{__S}->{'@iri'};
      $iriB = $b->{__S}->{'@iri'};
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
         $edgesA = $this->edges->refs->{$a->{__S}->{'@iri'}}->all;
         $edgesB = $this->edges->refs->{$b->{__S}->{'@iri'}}->all;
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
            if($key !== __S)
            {
               // normalize to array for single codepath
               $tmp = !is_array($object) ? array($object) : $object;
               foreach($tmp as $o)
               {
                  if(is_object($o) and property_exists($o, '@iri') and
                     property_exists($this->subjects, $o->{'@iri'}))
                  {
                     $objIri = $o->{'@iri'};

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
   $type = JSONLD_RDF_TYPE;
   if(property_exists($frame, JSONLD_RDF_TYPE) and
      is_object($input) and property_exists($input, __S) and
      property_exists($input, JSONLD_RDF_TYPE))
   {
      $tmp = is_array($input->{JSONLD_RDF_TYPE}) ?
         $input->{JSONLD_RDF_TYPE} : array($input->{JSONLD_RDF_TYPE});
      $types = is_array($frame->$type) ?
         $frame->{JSONLD_RDF_TYPE} : array($frame->{JSONLD_RDF_TYPE});
      $length = count($types);
      for($t = 0; $t < $length and !$rval; ++$t)
      {
         $type = $types[$t]->{'@iri'};
         foreach($tmp as $e)
         {
            if($e->{'@iri'} === $type)
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
   if(!property_exists($frame, JSONLD_RDF_TYPE))
   {
      // get frame properties that must exist on input
      $props = array_filter(array_keys((array)$frame), '_filterNonKeywords');
      if(count($props) === 0)
      {
         // input always matches if there are no properties
         $rval = true;
      }
      // input must be a subject with all the given properties
      else if(is_object($input) and property_exists($input, __S))
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
 * Recursively frames the given input according to the given frame.
 *
 * @param subjects a map of subjects in the graph.
 * @param input the input to frame.
 * @param frame the frame to use.
 * @param embeds a map of previously embedded subjects, used to prevent cycles.
 * @param options the framing options.
 *
 * @return the framed input.
 */
function _frame($subjects, $input, $frame, $embeds, $options)
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
         if(is_object($next) and property_exists($next, '@iri') and
            property_exists($subjects, $next->{'@iri'}))
         {
            $next = $subjects->{$next->{'@iri'}};
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

         // determine if value should be embedded or referenced
         $embedOn = property_exists($frame, '@embed') ?
            $frame->{'@embed'} : $options->defaults->embedOn;
         if(!$embedOn)
         {
            // if value is a subject, only use subject IRI as reference
            if(is_object($value) and property_exists($value, __S))
            {
               $value = $value->{__S};
            }
         }
         else if(
            is_object($value) and property_exists($value, __S) and
            property_exists($embeds, $value->{__S}->{'@iri'}))
         {
            // TODO: possibly support multiple embeds in the future ... and
            // instead only prevent cycles?
            throw new Exception(
               'Multiple embeds of the same subject is not supported. ' .
               'subject=' . $value->{__S}->{'@iri'});
         }
         // if value is a subject, do embedding and subframing
         else if(is_object($value) and property_exists($value, __S))
         {
            $embeds->{$value->{__S}->{'@iri'}} = true;

            // if explicit is on, remove keys from value that aren't in frame
            $explicitOn = property_exists($frame, '@explicit') ?
               $frame->{'@explicit'} : $options->defaults->explicitOn;
            if($explicitOn)
            {
               foreach($value as $key => $v)
               {
                  // do not remove subject or any key in the frame
                  if($key !== __S and !property_exists($frame, $key))
                  {
                     unset($value->$key);
                  }
               }
            }

            // iterate over frame keys to do subframing
            foreach($frame as $key => $f)
            {
               // skip keywords and type query
               if(strpos($key, '@') !== 0 and $key !== JSONLD_RDF_TYPE)
               {
                  if(property_exists($value, $key))
                  {
                     // build input and do recursion
                     $input = is_array($value->$key) ?
                        $value->$key : array($value->$key);
                     $length = count($input);
                     for($n = 0; $n < $length; ++$n)
                     {
                        // replace reference to subject w/subject
                        if(is_object($input[$n]) and
                           property_exists($input[$n], '@iri') and
                           property_exists($subjects, $input[$n]->{'@iri'}))
                        {
                           $input[$n] = $subjects->{$input[$n]->{'@iri'}};
                        }
                     }
                     $value->$key = _frame(
                        $subjects, $input, $f, $embeds, $options);
                  }
                  else
                  {
                     // add empty array/null property to value
                     $value->$key = is_array($f) ? array() : null;
                  }

                  // handle setting default value
                  if($value->$key === null)
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
                     if($omitOn)
                     {
                        unset($value->$key);
                     }
                     else if(property_exists($f, '@default'))
                     {
                        $value->$key = $f->{'@default'};
                     }
                  }
               }
            }
         }

         // add value to output
         if($rval === null)
         {
            $rval = $value;
         }
         else
         {
            $rval[] = $value;
         }
      }
   }

   return $rval;
}

?>
