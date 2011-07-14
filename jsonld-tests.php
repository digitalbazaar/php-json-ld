<?php
/**
 * PHP unit tests for JSON-LD.
 *
 * @author Dave Longley
 *
 * Copyright (c) 2011 Digital Bazaar, Inc. All rights reserved.
 */
require_once('jsonld.php');

function error_handler($errno, $errstr, $errfile, $errline)
{
   echo "</br>$errstr</br>";
   array_walk(
      debug_backtrace(),
      create_function(
         '$a,$b',
         'echo "{$a[\'function\']}()' .
         '(".basename($a[\'file\']).":{$a[\'line\']}); </br>";'));
   throw new Exception();
   return false;
}
set_error_handler('error_handler');

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

   try
   {
      $file = $filepath . '/' . $file;
      $rval = json_decode(file_get_contents($file));
   }
   catch(Exception $e)
   {
      echo "Exception while parsing file: '$file'</br>";
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

      echo $line . '</br>';
      if($fail)
      {
         echo 'Expect: ' . print_r($expect, true) . '</br>';
         echo 'Result: ' . print_r($result, true) . '</br>';

         /*
         $flags = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
         echo 'Legible Expect: ' .
            json_encode(json_decode(expect, $flags)) . '</br>';
         echo 'Legible Result: ' .
            json_encode(json_decode(result, $flags)) . '</br>';
         */

         // FIXME: remove me
         throw new Exception('FAIL');
      }
   }

   public function load($filepath)
   {
      $tests = array();

      // get full path
      $filepath = realpath($filepath);
      echo "Reading test files from: '$filepath'</br>";

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
         if($info['extension'] == 'test')
         {
            echo "Reading test file: '$file'</br>";

            try
            {
               $test = json_decode(file_get_contents($file));
            }
            catch(Exception $e)
            {
               echo "Exception while parsing file: '$file'</br>";
               throw $e;
            }

            if(!isset($test->filepath))
            {
               $test->filepath = $filepath;
            }
            $tests[] = $test;
         }
      }

      echo count($tests) . ' test file(s) read.</br>';

      return $tests;
   }

   public function run($tests, $filepath='jsonld')
   {
      /* Test format:
         {
            group: <optional group name>,
            tests: [{
               'name': <test name>,
               'type': <type of test>,
               'input': <input file for test>,
               'context': <context file for add context test type>,
               'frame': <frame file for frame test type>,
               'expect': <expected result file>,
            }]
         }

         If 'group' is present, then 'tests' must be present and list all of the
         tests in the group. If 'group' is not present then 'name' must be present
         as well as 'input' and 'expect'. Groups may be embedded.
       */
      foreach($tests as $test)
      {
         if(isset($test->group))
         {
            $this->group($test->group);
            $this->run($test->tests, $test->filepath);
            $this->ungroup();
         }
         else if(!isset($test->name))
         {
            throw new Exception(
               '"group" or "name" must be specified in test file.');
         }
         else
         {
            $this->test($test->name);

            // use parent test filepath as necessary
            if(!isset($test->filepath))
            {
               $test->filepath = realpath($filepath);
            }

            // read test files
            $input = _readTestJson($test->input, $test->filepath);
            $test->expect = _readTestJson($test->expect, $test->filepath);
            if(isset($test->context))
            {
               $test->context = _readTestJson($test->context, $test->filepath);
            }
            if(isset($test->frame))
            {
               $test->frame = _readTestJson($test->frame, $test->filepath);
            }

            // perform test
            $type = $test->type;
            if($type === 'normalize')
            {
               $input = jsonld_normalize($input);
            }
            else if($type === 'expand')
            {
               $input = jsonld_expand($input);
            }
            else if($type === 'compact')
            {
               $input = jsonld_compact($test->context, $input);
            }
            else if($type === 'frame')
            {
               $input = jsonld_frame($input, $test->frame);
            }
            else
            {
               throw new Exception("Unknown test type: '$type'");
            }

            // check results (only indent output on non-normalize tests)
            $this->check($test->expect, $input, $test->type !== 'normalize');
         }
      }
   }
}

// load and run tests
$tr = new TestRunner();
$tr->group('JSON-LD');
$tr->run($tr->load('tests'));
$tr->ungroup();
echo 'All tests complete.</br>';

?>
