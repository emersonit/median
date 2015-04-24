# MongoDB Schema for Median Entry

A Median entry in MongoDB has a lot of metadata. However, taking advantage of MongoDB's schema-less-ness, not all Median entry objects have the same attributes, based on their media type, permission level, or other factors.

The notation used here is JSON.

## Common Attributes

`entry['mid']` -- the unique numeric identifier for a Median entry, stands for "Median ID"
`entry['uid']` -- the original uploader of the entry, user ID, numeric
`entry['en']` -- a boolean value for whether the entry is enabled or not. disabled entries cannot be viewed.
`entry['tsc']` -- the unix timestamp of when the entry was first created
`entry['tsu']` -- the unix timestamp of when the entry was last updated
`entry['ti']` -- the title of the entry
`entry['mt']` -- the "meta type" or "media type" of the entry, can be video/audio/image/doc/link/clip
`entry['pa']` -- the "media paths", or file locations, of associated files. see "media paths" section below.
`entry['ul']` -- the required user level to view the entry, 6 = public, 5 = must be logged in, 1 = admin only, 0 = entry owners only
`entry['as']` -- a sub-document of "associations" for this entry, including categories `ca`, classes `cl`, groups `g`, playlists `pl`, etc.
`entry['me']` -- custom metadata input by the user on upload or after editing the entry, includes things like "notes" and "actors", etc.
`entry['ow']` -- a sub-document of the entry's "owners", including objects `u` for an array of users and `g` for an array of groups
`entry['li']` -- a string containing the entry's license code, i.e. "else_unknown" means the original file is "owned" by someone else, and has an "unknown" license type.
`entry['co']` -- a boolean value for whether the entry is restricted to class only use.
`entry['ha']` -- a boolean value for whether the entry is hidden from all lists, but still accessible via direct link
`entry['vc']` -- the cached view count
`entry['cc']` -- the cached comment count
`entry['dc']` -- the cached download count
`entry['th']` -- a sub-document containing the small and large thumbnails of the entry

## Optional Attributes

`entry['du']` -- the duration of the entry, if it has a duration
`entry['clip']` -- a sub-document with info on the original Median entry this video clip is based on
`entry['amara']` -- a string with a unique Amara ID for captioning
`entry['sbx']` -- a sub-document containing info about a sandbox-accessible version of the entry
`entry['html5']` -- the full file path to an HTML5-accessible version of the entry

## Media File Paths

Depending on the type of entry, the `entry['pa']` attribute can look different.

All entries have `entry['pa']` as a sub-document with at least the `entry['pa']['in']` attribute, which specifies the file path to the original file upload.

For documents and images, `entry['pa']['c']` is the full file path to the document itself, and `entry['pa']['fs']` is the file size in bytes.

For audio, `entry['pa']['c']` is a sub-document that has these attributes:

    [p] => /median/files/out/a5240183d5/1231.mp3 // the literal file path
    [f] => mp3:audio/a5240183d5/26812.mp3 // the streaming file path
    [b] => 96 // the bitrate
    [e] => 1 // a boolean true/false whether it's enabled for use
    [fs] => 1746255 // the file size in bytes

For videos, `entry['pa']['c']` is an array of "versions" of the video. Each version is a sub-document that has these attributes:

    [b] => 696 // the bitrate for this version
    [p] => /median/files/out/c61f518df5/33331_696kbps.mp4 // the literal file path
    [f] => mp4:videos/c61f518df5/33331_696kbps.mp4 // the streaming file path
    [w] => 720 // the video width for this version
    [h] => 405 // the video height for this version
    [e] => 1 // a boolean true/false whether it's enabled for use
    [fs] => 10407041 // the file size in bytes

That should be it! The bitrate/"versions" and whether they're enabled or not are controlled by the video transcoding farm.