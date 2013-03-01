<?php
/**
 * PHP unit tests for JSON-LD.
 *
 * @author Dave Longley
 *
 * Copyright (c) 2011-2013 Digital Bazaar, Inc. All rights reserved.
 */
require_once('jsonld.php');

// send errors to stdout
ini_set('display_errors', 'stdout');

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

function expanded_compare($x, $y, $is_list=false) {
  if($x === $y) {
    return true;
  }
  if(gettype($x) !== gettype($y)) {
    return false;
  }
  if(is_array($x)) {
    if(!is_array($y) || count($x) !== count($y)) {
      return false;
    }
    $rval = true;
    if($is_list) {
      // compare in order
      for($i = 0; $rval && $i < count($x); ++$i) {
        $rval = expanded_compare($x[$i], $y[$i], false);
      }
    }
    else {
      // compare in any order
      $iso = array();
      for($i = 0; $rval && $i < count($x); ++$i) {
        $rval = false;
        for($j = 0; !$rval && $j < count($y); ++$j) {
          if(!isset($iso[$j])) {
            if(expanded_compare($x[$i], $y[$j], false)) {
              $iso[$j] = $i;
              $rval = true;
            }
          }
        }
      }
      $rval = $rval && (count($iso) === count($x));
    }
    return $rval;
  }
  if(is_object($x)) {
    $x_keys = array_keys((array)$x);
    $y_keys = array_keys((array)$y);
    if(count($x_keys) !== count($y_keys)) {
      return false;
    }
    foreach($x_keys as $key) {
      if(!property_exists($y, $key)) {
        return false;
      }
      if(!expanded_compare($x->{$key}, $y->{$key}, $key === '@list')) {
        return false;
      }
    }
    return true;
  }

  return false;
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

/**
 * JSON-encodes the given input (does not escape slashes).
 *
 * @param mixed $input the input to encode.
 *
 * @return the encoded input.
 */
function jsonld_encode($input) {
  // newer PHP has a flag to avoid escaped '/'
  if(defined('JSON_UNESCAPED_SLASHES')) {
    $options = JSON_UNESCAPED_SLASHES;
    if(defined('JSON_PRETTY_PRINT')) {
      $options |= JSON_PRETTY_PRINT;
    }
    $json = json_encode($input, $options);
  }
  else {
    // use a simple string replacement of '\/' to '/'.
    $json = str_replace('\\/', '/', json_encode($input));
  }

  return $json;
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

  public function check($test, $expect, $result, $type) {
    global $eol;

    $compare_json = !in_array('jld:ToRDFTest', $type);
    $relabel = in_array('jld:FlattenTest', $type);
    $expanded = $relabel || in_array('jld:ExpandTest', $type);
    if($relabel) {
      $expect = jsonld_relabel_blank_nodes($expect);
      $result = jsonld_relabel_blank_nodes($result);
    }

    $pass = false;
    if($compare_json) {
      $pass = (json_encode($expect) === json_encode($result));
    }
    if(!$pass) {
      $pass = deep_compare($expect, $result) ||
        ($expanded && expanded_compare($expect, $result, $relabel));
    }
    if(!$pass && $relabel) {
      echo "WARN tried normalization...";
      $expect_normalized = jsonld_normalize(
        $expect, array('format' => 'application/nquads'));
      $result_normalized = jsonld_normalize(
        $result, array('format' => 'application/nquads'));
      $pass = ($expect_normalized === $result_normalized);
    }

    if($pass) {
      $this->passed += 1;
      echo "PASS$eol";
    }
    else {
      $this->failed += 1;
      echo "FAIL$eol";
      echo 'Expect: ' . jsonld_encode($expect) . $eol;
      echo 'Result: ' . jsonld_encode($result) . $eol;
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

        try {
          if(in_array('jld:ApiErrorTest', $type)) {
            echo "Skipping test \"{$test->name}\" of type: " .
              json_encode($type) . $eol;
            continue;
          }
          else if(in_array('jld:NormalizeTest', $type)) {
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
          else if(in_array('jld:FlattenTest', $type)) {
            $this->test($test->name);
            $input = read_test_json($test->input, $filepath);
            $test->expect = read_test_json($test->expect, $filepath);
            $result = jsonld_flatten($input, null, $options);
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
          $this->check($test, $test->expect, $result, $type);
        }
        catch(JsonLdException $e) {
          echo $eol . $e;
          $this->failed += 1;
          echo "FAIL$eol";
        }
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
