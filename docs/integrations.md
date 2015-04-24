# Median Integrations

Median has two built-in integrations which you may or may not which to utilize. Neither is really free.

## Amara Captioning Service

[Amara](http://amara.org/en/) is a wonderful online captioning service that Emerson began using to help caption videos to meet accessibility requirements for classes. It has a good API that Median uses to send videos for captioning and retrieve/store Amara's subtitles.

The integration is fairly straightforward: you simply need a team on Amara, a user, and that user's API key. These options are in the web application layer's `config.php` file.

You can still do captioning without Amara, however. Any owner of a Median entry can upload an SRT or VTT file with captions, and it will be associated with the entry.

## Akamai

Median leverges [Akamai](http://www.akamai.com/html/resources/live-video-streaming.html) content delivery for auxillary/improved streaming support, both of live content and video-on-demand content. Median integrates with it to send files to Akamai via FTP, and/or as an alternate live streaming location for specified Median Live streams.

These options are in the web application layer's `config.php` file.