<?php

/*

    FUNCTIONS THAT HAVE TO DO WITH SUBTITLES
        cyle gage, emerson college, 2015
    
    getAmaraSubtitlesID($mid)
    getMediaSubtitles($mid)
    timecodeToSeconds($timecode_string)
    get_subtitles_from_amara($amara_id)
    convert_subtitles_to_json($subtitles_string)
    convert_srt_to_json($array_of_lines)
    convert_webvtt_to_json($array_of_lines)
    sync_captions_from_amara($mid)
    send_entry_to_amara($mid)
    delete_entry_from_amara($mid)
    save_new_subtitles($mid, $subtitles)
    delete_subtitles($mid)
    
    new Amara API docs: http://amara.readthedocs.org/en/api-refactor/new-api.html

*/

require_once('/median-webapp/config/config.php');
require_once('/median-webapp/includes/dbconn_mongo.php');
require_once('/median-webapp/includes/media_functions.php');
require_once('/median-webapp/includes/file_functions.php');

// get the Amara ID for a given median ID
function getAmaraSubtitlesID($mid) {
	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return false;
	}
	$mid = (int) $mid * 1;
	$info = getMediaInfo($mid);
	if (isset($info['amara']) && trim($info['amara']) != '') {
		return $info['amara'];
	} else {
		return false;
	}
}

// get any subtitles we have stored locally on median already based on median ID
function getMediaSubtitles($mid) {
	if (!isset($mid) || !is_numeric($mid) || $mid * 1 == 0) {
		return array();
	}
	global $m6db;
	$mid = (int) $mid * 1;
	$subtitles_doc = $m6db->captions->findOne( array( 'mid' => $mid ) );
	if (isset($subtitles_doc) && isset($subtitles_doc['captions']) && count($subtitles_doc['captions']) > 0) {
        $captions = $subtitles_doc['captions'];
        // format it the way the median video player expects
        for ($i = 0; $i < count($captions); $i++) {
            $captions[$i]['start_secs'] = $captions[$i]['s'];
            $captions[$i]['end_secs'] = $captions[$i]['e'];
            $captions[$i]['text'] = $captions[$i]['t'];
        }
		return $captions; // return the captions array
	} else {
		return array(); // return empty array if none present
	}
}

// this is a helper function we'll use later
// i realize now that this is redundant, it's already elsewhere
// but whatever~~~~~
function timecodeToSeconds($tc) {
    $secs = 0;
	$tc = str_replace(',', '.', $tc); // convert french decimal , to proper . if present
    $tc_pieces = explode(':', $tc);
    if (count($tc_pieces) == 3) {
        $secs = ($tc_pieces[0] * 60 * 60) + ($tc_pieces[1] * 60) + ($tc_pieces[2] * 1);
    } else if (count($tc_pieces) == 2) {
        $secs = ($tc_pieces[0] * 60) + ($tc_pieces[1] * 1);
    } else if (count($tc_pieces) == 1) {
        $secs = $tc_pieces[0] * 1;
    }
    return $secs;
}

// use amara's API to get the subtitle data
function get_subtitles_from_amara($amara_id) {
	global $amara_headers, $amara_base_url;
    
	$amara_api_url = $amara_base_url.'/api/videos/'.$amara_id.'/'; // the URL to get the video subtitle info

	// we'll use curl to get the video entry's info from amara
	$amara_entry_info_curl = curl_init();
	curl_setopt($amara_entry_info_curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($amara_entry_info_curl, CURLOPT_URL, $amara_api_url);
	curl_setopt($amara_entry_info_curl, CURLOPT_PORT , 443);
	curl_setopt($amara_entry_info_curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($amara_entry_info_curl, CURLOPT_HEADER, 0);
	curl_setopt($amara_entry_info_curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($amara_entry_info_curl, CURLOPT_HTTPHEADER, $amara_headers);

	// this should be JSON...
	$amara_entry_info = curl_exec($amara_entry_info_curl);

	// check for curl errors
	if (curl_errno($amara_entry_info_curl) > 0) {
	    return array('ok' => false, 'error' => 'ERROR 107: Error fetching entry info from Amara: ' . curl_error($amara_entry_info_curl));
	}

	// decode it...
	$amara_entry_data = json_decode($amara_entry_info, true);
	if (!$amara_entry_data) {
		return array('ok' => false, 'error' => 'ERROR 102: There was an error fetching the subtitle info from Amara.');
	}

	curl_close($amara_entry_info_curl);

	//echo '<pre>'.print_r($amara_entry_data, true).'</pre>';

	// make sure there are actually subtitles made
	if (!isset($amara_entry_data['languages']) || count($amara_entry_data['languages']) == 0) {
		return array('ok' => false, 'error' => 'ERROR 103: It seems there are no subtitles at all for this entry in Amara.');
	}

	$amara_subtitles_api_url = ''; // temporary

	// go through the languages and try to find an english subtitle
	foreach ($amara_entry_data['languages'] as $subtitle_language) {
	    if ($subtitle_language['code'] == 'en') {
	        // found it!
	        $amara_subtitles_api_url = $subtitle_language['subtitles_uri'];
	    }
	}

	// make sure we found those english subtitles
	if (trim($amara_subtitles_api_url) == '') {
		return array('ok' => false, 'error' => 'ERROR 104: It seems there are no English subtitles for this entry in Amara.');
	}

	// ok -- now this should be the URL for where the subtitles actually live
	// it may change because of amara's API change cycle, so we rely on this
	if (substr($amara_subtitles_api_url, 0, 4) != 'http') {
	    $amara_subtitles_api_url = $amara_base_url.$amara_subtitles_api_url.'?format=vtt';
	} else {
	    $amara_subtitles_api_url = $amara_subtitles_api_url.'?format=vtt';
	}

	// use curl to get the subtitles from amara
	$amara_curl = curl_init();
	curl_setopt($amara_curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($amara_curl, CURLOPT_URL, $amara_subtitles_api_url);
	curl_setopt($amara_curl, CURLOPT_PORT , 443);
	curl_setopt($amara_curl, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($amara_curl, CURLOPT_HEADER, 0);
	curl_setopt($amara_curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($amara_curl, CURLOPT_HTTPHEADER, $amara_headers);

	$amara_data = curl_exec($amara_curl);
	
	// check for curl errors
	if (curl_errno($amara_curl) > 0) {
	    return array('ok' => false, 'error' => 'ERROR 108: Error fetching entry data from Amara: ' . curl_error($amara_curl));
	}
	
	if (trim($amara_data) == '') {
		return array('ok' => false, 'error' => 'ERROR 109: No Amara data was returned!');
	}
	
	return array('ok' => true, 'data' => $amara_data); // should be the captions!
}

// convert unchecked string of subtitles to JSON
function convert_subtitles_to_json($subtitles_string) {
	// detect what format the subtitles are in
	// and then send the incoming string to the right function
	
	global $srt_timecode_regex;
	
	// recode to just single line breaks, not windows style
	$subtitles_string = str_replace("\r\n", "\n", $subtitles_string);

	// break into lines
    $lines = explode("\n", $subtitles_string);
	
	// go through lines and just trim 'em
    $starting_count = count($lines);
    for ($i = 0; $i < $starting_count; $i++) {
    	$lines[$i] = trim($lines[$i]);
    }
	
	// reset the indexes on the lines array
    $lines = array_values($lines);
	
	// is it WebVTT?
    if (strtoupper($lines[0]) == 'WEBVTT') {
        $subtitles = convert_webvtt_to_json($lines);
    } else if (preg_match($srt_timecode_regex, $subtitles_string) == 1) { // or are they SRT?
		$subtitles = convert_srt_to_json($lines);
	} else {
		return array( 'ok' => false, 'error' => 'Not sure what format the subtitles are in, cannot convert to JSON!' );
	}
	
	return array( 'ok' => true, 'data' => $subtitles );
}

// convert WebVTT file format to a JSON array of captions
// expects an array of lines
function convert_webvtt_to_json($webvtt_lines) {
	global $vtt_timecode_regex;
	$subtitles = array();
    for ($i = 1; $i < count($webvtt_lines); $i++) {
        if (preg_match($vtt_timecode_regex, $webvtt_lines[$i], $timecode_matches)) {
            // the next few lines are subtitle text
			//print_r($timecode_matches);
	        $subtitle = array();
	        //$subtitle['start_tc'] = $timecode_matches[1];
	        //$subtitle['end_tc'] = $timecode_matches[2];
	        $subtitle['s'] = timecodeToSeconds($timecode_matches[1]);
	        $subtitle['e'] = timecodeToSeconds($timecode_matches[2]);
	        $subtitle_text = '';
	        for ($j = $i + 1; $j < count($webvtt_lines); $j++) {
				//echo 'line j='.$j.': "'.$lines[$j].'"'."\n";
	            if (!isset($webvtt_lines[$j])) {
	                break;
	            }
				if ($webvtt_lines[$j] == '') {
					break;
				}
	            if (preg_match($vtt_timecode_regex, $webvtt_lines[$j]) == false) {
	                if ($subtitle_text != '') {
	                    $subtitle_text .= "\n";
	                }
	                $subtitle_text .= $webvtt_lines[$j];
	                $i++;
	            } else {
	                break;
	            }
	        }
	        $subtitle['t'] = $subtitle_text;
	        $subtitles[] = $subtitle;
        }
    }
	return $subtitles;
}

// convert SRT file format to a JSON array of captions
// expects an array of lines
function convert_srt_to_json($srt_lines) {
	global $srt_timecode_regex;
	$subtitles = array();
    for ($i = 0; $i < count($srt_lines); $i++) {
        if (preg_match($srt_timecode_regex, $srt_lines[$i], $timecode_matches)) {
            // the next few lines are subtitle text
			//print_r($timecode_matches);
	        $subtitle = array();
	        //$subtitle['start_tc'] = $timecode_matches[1];
	        //$subtitle['end_tc'] = $timecode_matches[2];
	        $subtitle['s'] = timecodeToSeconds($timecode_matches[1]);
	        $subtitle['e'] = timecodeToSeconds($timecode_matches[2]);
	        $subtitle_text = '';
	        for ($j = $i + 1; $j < count($srt_lines); $j++) {
	            if (!isset($srt_lines[$j])) {
	                break;
	            }
				if ($srt_lines[$j] == '') {
					break;
				}
	            if (preg_match($srt_timecode_regex, $srt_lines[$j]) == false) {
	                if ($subtitle_text != '') {
	                    $subtitle_text .= "\n";
	                }
	                $subtitle_text .= $srt_lines[$j];
	                $i++;
	            } else {
	                break;
	            }
	        }
	        $subtitle['t'] = $subtitle_text;
	        $subtitles[] = $subtitle;
        }
    }
	return $subtitles;
}

// sync captions from Amara to Median
function sync_captions_from_amara($mid) {
    /*
    
        what needs to happen:
        
        - get entry info and amara ID
        - get captions from amara via API
        - convert captions to JSON
        - save JSON to median captions collection
    
    */
    global $mdb, $m6db;
    $amara_id = getAmaraSubtitlesID($mid);
    if ($amara_id == false) {
        return array( 'ok' => false, 'error' => 'No Amara ID present for that Median entry' );
    }
    $get_subtitles_result = get_subtitles_from_amara($amara_id);
    if ($get_subtitles_result['ok'] == true) {
    	// ok now convert to JSON
    	$subtitle_convert_result = convert_subtitles_to_json($get_subtitles_result['data']);
    	if ($subtitle_convert_result['ok'] == true) {
    		$subtitles_record = array();
    		$subtitles_record['mid'] = $mid;
    		$subtitles_record['ts'] = time();
    		$subtitles_record['captions'] = $subtitle_convert_result['data'];
    		try {
    			$upsert_captions = $m6db->captions->update( array('mid' => $mid), array('$set' => $subtitles_record), array( 'upsert' => true, 'w' => 1 ) );
    			return array('ok' => true);
    		} catch(MongoCursorException $e) {
                return array('ok' => false, 'error' => 'error saving to database: '.print_r($e, true));
    		}
    	} else {
            return array('ok' => false, 'error' => 'error converting subtitles: '.$subtitle_convert_result['error']);
    	}
    } else {
        return array('ok' => false, 'error' => 'error getting subtitles: '.$get_subtitles_result['error']);
    }
}

// use the Amara API to create a new subtitle entry for this median entry
function send_entry_to_amara($mid) {
    /*
    
        what needs to happen:
        
        - get entry's title, duration, and thumbnail URL
        - copy/link the entry to a sandbox with a random video URL
        - package title, thumbnail, sandbox URL
        - send that to the Amara API
        - store the Amara ID that's given back
    
    */
    
    global $sandbox_base_path, $sandbox_base_url, $amara_base_url, $amara_headers, $m6db, $mdb, $median_base_url;
    
    if (!is_numeric($mid) || $mid * 1 == 0) {
        return array( 'ok' => false, 'error' => 'invalid MID supplied to the function' );
	}
    
	$mid = (int) $mid * 1;
    
	$info = getMediaInfo($mid);
    
    if ($info == false) {
        return array( 'ok' => false, 'error' => 'could not get info for MID #'.$mid );
    }
    
    // make a random strong sandbox URL
    $random_filename = $mid.'_'.bin2hex(openssl_random_pseudo_bytes(16)).'.mp4';
    $sandbox_fullpath = $sandbox_base_path . $random_filename;
    $sandbox_url = $sandbox_base_url . $random_filename;
    
    // choose which video to use for captioning...
    $original_file_fullpath = '';
    foreach ($info['pa']['c'] as $media_path) {
    	if (!isset($media_path['e']) || $media_path['e'] == false) {
    		continue;
    	}
    	if ($media_path['b'] > 500 && $media_path['b'] < 1000) {
    		$original_file_fullpath = $media_path['p'];
    	}
    }

    if ($original_file_fullpath == '' && isset($info['pa']['c'][0]) && isset($info['pa']['c'][0]['p'])) {
    	if (!isset($info['pa']['c'][0]['e']) || $info['pa']['c'][0]['e'] == false) {
    		// hmm
            return array( 'ok' => false, 'error' => 'no video file to use for subtitles' );
    	} else {
    		$original_file_fullpath = $info['pa']['c'][0]['p']; // welp. just get the first one if it's the only one left.
    	}
    }
    
    // ok so we're gonna put in a file operation
    // to symlink $sandbox_fullpath => $original_file_fullpath
    $fileop_queue_result = addSymlinkToOperationsQueue($original_file_fullpath, $sandbox_fullpath);
    
    if ($fileop_queue_result == false) {
        // uh oh
        return array( 'ok' => false, 'error' => 'error inserting symlink op into file ops queue' );
    }
    
    // the amara call will be a POST request
    $amara_call_type = 'POST';
    $creating_new_amara_entry = false;
    
    // ok first check to see if there's already an Amara ID for this median entry
    // if so, add the new URL, instead of a POST for a new video
    if (isset($info['amara']) && trim($info['amara']) != '') {
        // data to send to amara to update an existing entry with a new primary URL
        // to /api2/partners/videos/[video-id]/urls/
        // based on: http://amara.readthedocs.org/en/latest/api.html#video-url-resource
        $amara_api_url = $amara_base_url . '/api2/partners/videos/'.trim($info['amara']).'/urls/';
        $data_for_amara = array();
        $data_for_amara['url'] = $sandbox_url;
        $data_for_amara['primary'] = true;
    } else {
        // data to send to amara to create a new entry
        $amara_api_url = $amara_base_url . '/api/videos/';
        // this is the object we'll send to the Amara API
        $data_for_amara = array();
        $data_for_amara['title'] = $info['ti'];
        $data_for_amara['video_url'] = $sandbox_url;
        $data_for_amara['duration'] = $info['du'];
        $data_for_amara['thumbnail'] = substr($median_base_url, 0, -1).str_replace('/thumbs/', '/files/thumb/', $info['th']['b']);
        $data_for_amara['team'] = 'emerson';
        $creating_new_amara_entry = true;
    }
    
    // serialize the info into JSON
    $post_data_string = json_encode($data_for_amara);
    
    // set up the final HTTP headers for sending to Amara
    $final_headers = array();
    $final_headers[] = 'Content-Type: application/json';
    $final_headers[] = 'Content-Length: ' . strlen($post_data_string);
    $final_headers = array_merge($amara_headers, $final_headers);

    // we'll use curl to send the video entry's info to amara
    $amara_post_new_curl = curl_init();
    curl_setopt($amara_post_new_curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($amara_post_new_curl, CURLOPT_URL, $amara_api_url);
    curl_setopt($amara_post_new_curl, CURLOPT_PORT, 443);
    curl_setopt($amara_post_new_curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($amara_post_new_curl, CURLOPT_HEADER, 0);
    curl_setopt($amara_post_new_curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($amara_post_new_curl, CURLOPT_HTTPHEADER, $final_headers);
    curl_setopt($amara_post_new_curl, CURLOPT_CUSTOMREQUEST, $amara_call_type);
    curl_setopt($amara_post_new_curl, CURLOPT_POSTFIELDS, $post_data_string);

    // the API call should return JSON as well...
    $amara_entry_info = curl_exec($amara_post_new_curl);

    // check for curl errors
    if (curl_errno($amara_post_new_curl) > 0) {
        return array( 'ok' => false, 'error' => 'Error fetching entry info from Amara: ' . curl_error($amara_post_new_curl) );
    }

    // decode it...
    $amara_entry_data = json_decode($amara_entry_info, true);
    if (!$amara_entry_data) {
        return array( 'ok' => false, 'error' => 'error decoding amara data result: '.$amara_entry_info );
    }

    if (isset($amara_entry_data['id']) && trim($amara_entry_data['id']) != '') {
    	//echo '<p>new amara ID: '.$amara_entry_data['id'].'</p>';
        $amara_id = $amara_entry_data['id'];
    } else {
        // problems
        return array( 'ok' => false, 'error' => 'did not get an Amara ID back from Amara: '.print_r($amara_entry_data, true) );
    }
    
    curl_close($amara_post_new_curl);
    
    // things to update the media entry with
    $updated_entry = array();
    if ($creating_new_amara_entry) {
        // only save the amara ID if we've made a new amara entry
        $updated_entry['amara'] = $amara_id;
    }
    $updated_entry['sbx'] = array();
    $updated_entry['sbx']['url'] = $sandbox_url;
    $updated_entry['sbx']['path'] = $sandbox_fullpath;
    
    // update median entry
    try {
		$update_entry = $mdb->media->update( array('mid' => $mid), array('$set' => $updated_entry), array( 'w' => 1 ) );
	} catch(MongoCursorException $e) {
		//echo 'error saving to database: '.print_r($e, true)."\n";
        return array( 'ok' => false, 'error' => 'error saving to database: '.print_r($e, true) );
	}
    
    // all done? cool
    return array( 'ok' => true );
    
}

// use the Amara API to remove an entry
function delete_entry_from_amara($mid) {
    /*
    
        what needs to happen:
        
        - fetch amara ID from median entry info
        - delete sandboxed file
        - use Amara API to delete resource on their end
        - delete amara ID + sandbox info from median entry
    
    */
    
    // fetch info from median entry
    // make sure it actually has amara and sandbox info
    
    if (!is_numeric($mid) || $mid * 1 == 0) {
        return array( 'ok' => false, 'error' => 'invalid MID supplied to the function' );
	}
    
    global $mdb, $m6db;
    
	$mid = (int) $mid * 1;
    
	$info = getMediaInfo($mid);
    
    if ($info == false) {
        return array( 'ok' => false, 'error' => 'could not get info for MID #'.$mid );
    }
    
    // make sure this even has the sandbox info
    if (!isset($info['sbx']) || !isset($info['sbx']['path'])) {
        return array( 'ok' => false, 'error' => 'there is no sandbox info for MID #'.$mid );
    }
    
    // add delete sandbox path to operations queue
    $delete_op_result = addDeleteFileToOperationsQueue($info['sbx']['path']);
    if ($delete_op_result == false) {
        return array( 'ok' => false, 'error' => 'could not add symlink to delete file op queue' );
    }
    
    /*
    
    welp amara doesn't let you delete entries on their end, so whatever
    we'll just remove the sandbox link on our end
    
    */
    
    // remove sandbox info and amara ID from median entry
    try {
		$update_entry = $mdb->media->update( array('mid' => $mid), array('$unset' => array('sbx' => '') ), array( 'w' => 1 ) );
	} catch(MongoCursorException $e) {
		//echo 'error saving to database: '.print_r($e, true)."\n";
        return array( 'ok' => false, 'error' => 'error saving to database: '.print_r($e, true) );
	}
    
    // all done? cool
    return array( 'ok' => true );
    
}

// save new subtitles to the database
// expects an array of subtitles from convert_subtitles_to_json()
function save_new_subtitles($mid, $subtitles) {
    global $mdb, $m6db;
    $mid = (int) $mid * 1;
    if (!is_array($subtitles) || count($subtitles) == 0) {
        return array('ok' => false, 'error' => 'invalid subtitles provided');
    }
	$subtitles_record = array();
	$subtitles_record['mid'] = $mid;
	$subtitles_record['ts'] = time();
	$subtitles_record['captions'] = $subtitles;
	try {
		$upsert_captions = $m6db->captions->update( array('mid' => $mid), array('$set' => $subtitles_record), array( 'upsert' => true, 'w' => 1 ) );
		return array('ok' => true);
	} catch(MongoCursorException $e) {
        return array('ok' => false, 'error' => 'error saving subtitles to database: '.print_r($e, true));
	}
}

// just remove an entry's subtitles
function delete_subtitles($mid) {
    global $m6db;
    $mid = (int) $mid * 1;
    try {
		$delete_captions = $m6db->captions->remove(array('mid' => $mid), array( 'w' => 1 ) );
		return array('ok' => true);
	} catch(MongoCursorException $e) {
        return array('ok' => false, 'error' => 'error deleting subtitles from database: '.print_r($e, true));
	}
}