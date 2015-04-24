#!/usr/bin/env php
<?php

if (php_sapi_name() != 'cli') {
  fwrite(STDERR, "error: not using CLI!\n");
  exit(1);
}

/*

    THE UPLOAD TEST PROCESSOR

        just deletes the files

*/

$text_input = '';
while (!feof(STDIN)) {
  $text_input .= fgets(STDIN, 4096);
}

$batch_info = json_decode($text_input, true);

if ($batch_info == null) {
  bailout('Invalid JSON data given.', null, null, $text_input);
}

/*

  incoming $batch_info array should look like...

  $batch_info['user_id'] => median user ID of who is uploading
  $batch_info['files'] => array of files
    $file in $batch_info['files'] => 'path', 'size', and 'title'

*/

if (!isset($batch_info['files']) || count($batch_info['files']) == 0) {
  bailout('It looks like no files were actually uploaded.', null, null, $batch_info);
}

if (!isset($batch_info['user_id']) || !is_numeric($batch_info['user_id'])) {
  bailout('No valid user ID provided with the uploads.', null, null, $batch_info);
}

// who's doing the uploading
$uid = (int) $batch_info['user_id'] * 1;

// from here on out we do not abort the whole process if something fails, we move on
// and we send any failure conditions back to the client once done going through each file

// go through each file and figure out what to do with it
foreach ($batch_info['files'] as $batched) {
  // each $batched should have keys 'path', 'size', and 'title'

  // if no title, make it "Untitled"
  if (isset($batched['title']) && trim($batched['title']) != '') {
    $batch_title = trim($batched['title']);
  } else {
    $batch_title = 'Untitled';
  }

  // make sure path is set for each
  if (!isset($batched['path']) || trim($batched['path']) == '') {
    continue; // go on to the next one
  }

  $batch_path = trim($batched['path']);

  $deleted = unlink($batch_path);

}

echo json_encode(array( 'error' => 'deleted!' ));
