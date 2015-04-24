# Median Transcode Farm

Whenever someone uploads a video to Median, it is inspected by the file API layer to determine how many "versions" of the video must be created for use on the web. The vast majority of video uploaded to Median cannot simply be streamed immediately, as the requirements are somewhat strict. Normally it takes a good deal of time (a few minutes to an hour or so) to transcode a video, depending on the file size and duration. To make this process go faster, Median utilizes a "farm" of nodes that each work on transcoding video files as they come in.

This piece of Median was actually already open sourced [here](https://github.com/cyle/transcode-farm), however that version is a little out of date.

Long story short, Median transcode nodes, or "farmers", run a Node.js script which continuously checks for videos to transcode. Each node is connected to the Median central file storage NFS share, and reports its work back to Median via a farming API on the web application layer.

A Median transcoding farm can be any number of nodes, from 1 to 100, or beyond. The transcoding farm at Emerson College is six powerful IBM blades in a bladecenter, transcoding videos very quickly. (Average job time has been ~4 minutes.)

See the `install-farmers.md` documentation and the farmers deployment scripts for more info.

Also, the farmers are capable of being disposable PXE-booted nodes, if you have the time to create a PXE image out of a node after you've set it up.