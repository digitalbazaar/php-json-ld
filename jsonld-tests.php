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

function error_handler($errno, $errstr, $errfile, $errline) {
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
if(!$isCli) {
  set_error_handler('error_handler');
}

function deep_compare($expect, $result) {
  if(is_array($expect)) {
    if(!is_array($result)) {
      return false;
    }
    if(count($expect) !== count($result)) {
      return false;
    }
    foreach($expect as $i => $v) {
      if(!deep_compare($v, $result[$i])) {
        return false;
      }
    }
    return true;
  }

  if(is_object($expect)) {
    if(!is_object($result)) {
      return false;
    }
    if(count(get_object_vars($expect)) !== count(get_object_vars($result))) {
      return false;
    }
    foreach($expect as $k => $v) {
      if(!property_exists($result, $k) || !deep_compare($v, $result->{$k})) {
        return false;
      }
    }
    return true;
  }

  return $expect === $result;
}

/**
 * Reads test JSON files.
 *
 * @param string $file the file to read.
 * @param string $filepath the test filepath.
 *
 * @return string the read JSON.
 */
function read_test_json($file, $filepath) {
  global $eol;

  try {
    $file = $filepath . '/' . $file;
    return json_decode(file_get_contents($file));
  }
  catch(Exception $e) {
    echo "Exception while parsing file: '$file'$eol";
    throw $e;
  }
}

/**
 * Reads test N-Quads files.
 *
 * @param string $file the file to read.
 * @param string $filepath the test filepath.
 *
 * @return string the read N-Quads.
 */
function read_test_nquads($file, $filepath) {
  global $eol;

  try {
    $file = $filepath . '/' . $file;
    return file_get_contents($file);
  }
  catch(Exception $e) {
    echo "Exception while parsing file: '$file'$eol";
    throw $e;
  }
}

class TestRunner {
  public function __construct() {
    // set up groups, add root group
    $this->groups = array();
    $this->group('');

    $this->passed = 0;
    $this->failed = 0;
    $this->total = 0;
  }

  public function group($name) {
    $this->groups[] = (object)array(
      'name' => $name,
      'tests' => array(),
      'count' => 1);
  }

  public function ungroup() {
    array_pop($this->groups);
  }

  public function test($name) {
    $this->groups[count($this->groups) - 1]->tests[] = $name;
    $this->total += 1;

    $line = '';
    foreach($this->groups as $g) {
      $line .= ($line === '') ? $g->name : ('/' . $g->name);
    }

    $g = $this->groups[count($this->groups) - 1];
    if($g->name !== '') {
      $count = '' . $g->count;
      $end = 4 - strlen($count);
      for($i = 0; $i < $end; ++$i) {
        $count = '0' . $count;
      }
      $line .= ' ' . $count;
      $g->count += 1;
    }
    $line .= '/' . array_pop($g->tests) . '... ';
    echo $line;
  }

  public function check($test, $expect, $result) {
    global $eol;

    if(deep_compare($expect, $result)) {
      $this->passed += 1;
      echo "PASS$eol";
    }
    else {
      $this->failed += 1;
      echo "FAIL$eol";
      echo 'Expect: ' . print_r($expect, true) . $eol;
      echo 'Result: ' . print_r($result, true) . $eol;

      /*
      $flags = JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
      echo 'JSON Expect: ' .
        json_encode(json_decode(expect, $flags)) . $eol;
      echo 'JSON Result: ' .
        json_encode(json_decode(result, $flags)) . $eol;
      */
    }
  }

  public function load($filepath) {
    global $eol;
    $manifests = array();

    // get full path
    $filepath = realpath($filepath);
    echo "Reading manifest files from: '$filepath'$eol";

    // read each test file from the directory
    $files = array();
    $handle = opendir($filepath);
    if($handle) {
      while(($file = readdir($handle)) !== false) {
        if($file !== '..' and $file !== '.') {
          $files[] = $filepath . '/' . $file;
        }
      }
      closedir($handle);
    }
    else {
      throw new Exception('Could not open directory.');
    }

    foreach($files as $file) {
      $info = pathinfo($file);
      // FIXME: hackish, manifests are now JSON-LD
      if(strstr($info['basename'], 'manifest') !== false &&
        $info['extension'] == 'jsonld') {
        echo "Reading manifest file: '$file'$eol";

        try {
          $manifest = json_decode(file_get_contents($file));
        }
        catch(Exception $e) {
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

  public function run($manifests) {
    /* Manifest format: {
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
    foreach($manifests as $manifest) {
      if(property_exists($manifest, 'name')) {
        $this->group($manifest->name);
      }
      $filepath = $manifest->filepath;
      foreach($manifest->sequence as $test) {
        // read test input files
        $type = $test->{'@type'};
        $options = array(
          'base' => 'http://json-ld.org/test-suite/tests/' . $test->input);
        if(in_array('jld:NormalizeTest', $type)) {
          $this->test($test->name);
          $input = read_test_json($test->input, $filepath);
          $test->expect = read_test_nquads($test->expect, $filepath);
          $options['format'] = 'application/nquads';
          $result = jsonld_normalize($input, $options);
        }
        else if(in_array('jld:ExpandTest', $type)) {
          $this->test($test->name);
          $input = read_test_json($test->input, $filepath);
          $test->expect = read_test_json($test->expect, $filepath);
          $result = jsonld_expand($input, $options);
        }
        else if(in_array('jld:CompactTest', $type)) {
          $this->test($test->name);
          $input = read_test_json($test->input, $filepath);
          $test->context = read_test_json($test->context, $filepath);
          $test->expect = read_test_json($test->expect, $filepath);
          $result = jsonld_compact($input, $test->context, $options);
        }
        else if(in_array('jld:FrameTest', $type)) {
          $this->test($test->name);
          $input = read_test_json($test->input, $filepath);
          $test->frame = read_test_json($test->frame, $filepath);
          $test->expect = read_test_json($test->expect, $filepath);
          $result = jsonld_frame($input, $test->frame, $options);
        }
        else if(in_array('jld:FromRDFTest', $type)) {
          $this->test($test->name);
          $input = read_test_nquads($test->input, $filepath);
          $test->expect = read_test_json($test->expect, $filepath);
          $result = jsonld_from_rdf($input, $options);
        }
        else if(in_array('jld:ToRDFTest', $type)) {
          $this->test($test->name);
          $input = read_test_json($test->input, $filepath);
          $test->expect = read_test_nquads($test->expect, $filepath);
          $options['format'] = 'application/nquads';
          $result = jsonld_to_rdf($input, $options);
        }
        else {
          echo "Skipping test \"{$test->name}\" of type: " .
            json_encode($type) . $eol;
          continue;
        }

        // check results
        $this->check($test, $test->expect, $result);
      }
      if(property_exists($manifest, 'name')) {
        $this->ungroup();
      }
    }
  }
}

// get command line options
$options = getopt('d:');
if($options === false || !array_key_exists('d', $options)) {
  $var = 'path to json-ld.org/test-suite/tests';
  echo "Usage: php jsonld-tests.php -d <$var>$eol";
  exit(0);
}

// load and run tests
$tr = new TestRunner();
$tr->group('JSON-LD');
$tr->run($tr->load($options['d']));
$tr->ungroup();
echo "Done. Total:{$tr->total} Passed:{$tr->passed} Failed:{$tr->failed}$eol";

/* end of file, omit ?> */
