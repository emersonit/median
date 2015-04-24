farmer object in mongo

Array(

	'n' => 'Eddard',			// friendly display name
	'hn' => 'median-node-34',	// proper hostname
	'ip' => '199.94.92.91',		// ip address
	'e' => 1,					// enabled or not -- index'd
	'tsc' => 1344350435,		// when created
	'tsh' => 1344350435			// last heartbeat -- index'd

)

farming job object in mongo

Array(

	'mid' => 20002,					// media ID this is for (if any) -- index'd
	'p' => 1,						// priority (1 for median, 2 for anything else) -- index'd
	'o' => 1,						// origin (1 for median, 2 for transcode farm) -- index'd
	's' => 1,						// current status code -- index'd
	'fid' => MongoId('901ao21j'),	// farmer mongo ID (if any)
	'in' => '/median/in..',			// file input
	'out' => '/median/out...',		// file output -- index'd
	'vw' => 1280,					// desired video max width
	'vh' => 720,					// desired video max height
	'vb' => 1200,					// desired video bitrate
	'ab' => 128,					// desired audio bitrate
	'tsc' => 1344333443,			// time created
	'tsu' => 1393939222				// last updated -- index'd

)