<?php
/**
 * PHP unit tests for JSON-LD.
 *
 * @author Dave Longley
 *
 * Copyright (c) 2011-2012 Digital Bazaar, Inc. All rights reserved.
 */
require_once('jsonld.php');

// determine EOL for output based on command line php or webpage php
$isCli = defined('STDIN');
$eol = $isCli ? "\n" : '<br/>';

function error_handler($errno, $errstr, $errfile, $errline)
{
   global $eol;
   echo "$eol$errstr$eol";
   array_walk(
      debug_backtrace(),
      create_function(
         '$a,$b',
         'echo "{$a[\'function\']}()' .
         '(".basename($a[\'file\']).":{$a[\'line\']}); ' . $eol . '";'));
   throw new Exception();
   return false;
}
if(!$isCli)
{
   set_error_handler('error_handler');
}

function _sortKeys($obj)
{
   $rval;

   if($obj === null)
   {
      $rval = null;
   }
   else if(is_array($obj))
   {
      $rval = array();
      foreach($obj as $o)
      {
         $rval[] = _sortKeys($o);
      }
   }
   else if(is_object($obj))
   {
      $rval = new stdClass();
      $keys = array_keys((array)$obj);
      sort($keys);
      foreach($keys as $key)
      {
         $rval->$key = _sortKeys($obj->$key);
      }
   }
   else
   {
      $rval = $obj;
   }

   return $rval;
}

function _stringifySorted($obj, $indent)
{
   /*
   $flags = JSON_UNESCAPED_SLASHES;
   if($indent)
   {
      $flags |= JSON_PRETTY_PRINT;
   }*/
   return str_replace('\\/', '/', json_encode(_sortKeys($obj)));//, $flags);
}

/**
 * Reads test JSON files.
 *
 * @param file the file to read.
 * @param filepath the test filepath.
 *
 * @return the read JSON.
 */
function _readTestJson($file, $filepath)
{
   $rval;
   global $eol;

   try
   {
      $file = $filepath . '/' . $file;
      $rval = json_decode(file_get_contents($file));
   }
   catch(Exception $e)
   {
      echo "Exception while parsing file: '$file'$eol";
      throw $e;
   }

   return $rval;
}

class TestRunner
{
   public function __construct()
   {
      // set up groups, add root group
      $this->groups = array();
      $this->group('');
   }

   public function group($name)
   {
      $group = new stdClass();
      $group->name = $name;
      $group->tests = array();
      $group->count = 1;
      $this->groups[] = $group;
   }

   public function ungroup()
   {
      array_pop($this->groups);
   }

   public function test($name)
   {
      $this->groups[count($this->groups) - 1]->tests[] = $name;

      $line = '';
      foreach($this->groups as $g)
      {
         $line .= ($line === '') ? $g->name : ('/' . $g->name);
      }

      $g = $this->groups[count($this->groups) - 1];
      if($g->name !== '')
      {
         $count = '' . $g->count;
         $end = 4 - strlen($count);
         for($i = 0; $i < $end; ++$i)
         {
            $count = '0' . $count;
         }
         $line .= ' ' . $count;
         $g->count += 1;
      }
      $line .= '/' . array_pop($g->tests) . '... ';
      echo $line;
   }

   public function check($expect, $result, $indent=false)
   {
      global $eol;

      // sort and use given indent level
      $expect = _stringifySorted($expect, $indent);
      $result = _stringifySorted($result, $indent);

      $fail = false;
      if($expect === $result)
      {
         $line = 'PASS';
      }
      else
      {
         $line = 'FAIL';
         $fail = true;
      }

      echo "$line$eol";
      if($fail)
      {
         echo 'Expect: ' . print_r($expect, true) . $eol;
         echo 'Result: ' . print_r($result, true) . $eol;

         /*
         $flags = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
         echo 'Legible Expect: ' .
            json_encode(json_decode(expect, $flags)) . $eol;
         echo 'Legible Result: ' .
            json_encode(json_decode(result, $flags)) . $eol;
         */

         // FIXME: remove me
         throw new Exception('FAIL');
      }
   }

   public function load($filepath)
   {
      global $eol;
      $manifests = array();

      // get full path
      $filepath = realpath($filepath);
      echo "Reading manifest files from: '$filepath'$eol";

      // read each test file from the directory
      $files = array();
      $handle = opendir($filepath);
      if($handle)
      {
         while(($file = readdir($handle)) !== false)
         {
            if($file !== '..' and $file !== '.')
            {
               $files[] = $filepath . '/' . $file;
            }
         }
         closedir($handle);
      }
      else
      {
         throw new Exception('Could not open directory.');
      }

      foreach($files as $file)
      {
         $info = pathinfo($file);
         // FIXME: hackish, manifests are now JSON-LD
         if(strstr($info['basename'], 'manifest') !== false and
            $info['extension'] == 'jsonld')
         {
            echo "Reading manifest file: '$file'$eol";

            try
            {
               $manifest = json_decode(file_get_contents($file));
            }
            catch(Exception $e)
            {
               echo "Exception while parsing file: '$file'$eol";
               throw $e;
            }

            $manifest->filepath = $filepath;
            $manifests[] = $manifest;
         }
      }

      echo count($manifests) . " manifest file(s) read.$eol";

      return $manifests;
   }

   public function run($manifests)
   {
      /* Manifest format:
         {
            name: <optional manifest name>,
            sequence: [{
               'name': <test name>,
               '@type': ["test:TestCase", "jld:<type of test>"],
               'input': <input file for test>,
               'context': <context file for add context test type>,
               'frame': <frame file for frame test type>,
               'expect': <expected result file>,
            }]
         }
       */
      global $eol;

      foreach($manifests as $manifest)
      {
         $this->group($manifest->name);
         $filepath = $manifest->filepath;

         foreach($manifest->sequence as $test)
         {
            // read test input files
            $indent = 2;
            $type = $test->{'@type'};
            if(in_array('jld:NormalizeTest', $type))
            {
               $indent = 0;
               $input = _readTestJson($test->input, $filepath);
               $test->expect = _readTestJson($test->expect, $filepath);
               $result = jsonld_normalize($input);
            }
            else if(in_array('jld:ExpandTest', $type))
            {
               $input = _readTestJson($test->input, $filepath);
               $test->expect = _readTestJson($test->expect, $filepath);
               $result = jsonld_expand($input);
            }
            else if(in_array('jld:CompactTest', $type))
            {
               $input = _readTestJson($test->input, $filepath);
               $test->context = _readTestJson($test->context, $filepath);
               $test->expect = _readTestJson($test->expect, $filepath);
               $result = jsonld_compact($test->context->{'@context'}, $input);
            }
            else if(in_array('jld:FrameTest', $type))
            {
               $input = _readTestJson($test->input, $filepath);
               $test->frame = _readTestJson($test->frame, $filepath);
               $test->expect = _readTestJson($test->expect, $filepath);
               $result = jsonld_frame($input, $test->frame);
            }
            else
            {
               echo 'Skipping test "' . $test->name . '" of type: ' .
                  json_encode($type) . $eol;
               continue;
            }

            // check results (only indent output on non-normalize tests)
            $this->test($test->name);
            $this->check($test->expect, $result, $indent);
         }
      }
   }
}

// load and run tests
$tr = new TestRunner();
$tr->group('JSON-LD');
$tr->run($tr->load('tests'));
$tr->ungroup();
echo "All tests complete.$eol";

?>
