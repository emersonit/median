# Median

## What is Median?

Median is a complex, secure media uploading and distribution system, meant for use by all sides of Emerson College, whether it's public relations or training videos or student work or assigned viewing material. With that in mind, it was built specifically to feature robust security, licensing, and access controls.

You can see Emerson College's Median instance at [https://median.emerson.edu/](https://median.emerson.edu/)

## Features

- Upload and share videos, images, audio files, documents, and links, with no file size restriction.
- Median, by default, claims no ownership over your media, and doesn't use any of your data for advertising purposes.
- Attach any kind of metadata to an entry, even make sub-clips of your videos with their own metadata.
- Create groups, categories, events, playlists, and more, to help organize and share entries.
- Secure your media by restricting access to just your class, or group, or other organizational methods.
- Videos are dynamically transcoded to be viewable across connection speeds, such as via mobile.
- Video playback uses adaptive bitrate streaming using a custom player with support for captions.
- Live streaming playback and broadcasting built in.
- Download the original media file at any time if you are an owner of the entry.
- Delete your entry and be ensured that absolutely no copy of your original media remains.
- Dynamically and independently scale different layers of the Median architecture.
- Create third-party tools using Median's API.
- Integration with an existing authentication system via SimpleSAMLphp, auto-provisions users on first login.
- Integration with an existing course enrollment / ERP system, so users can share entries within a course.
- Integration with the [Amara](http://amara.org/en/) captioning service and [Akamai streaming](http://www.akamai.com/html/resources/live-video-streaming.html).

## Getting Started + Setting It Up

To set up your own instance of Median, clone this repo, check out the `docs` folder, starting with `docs/DOCS-INDEX.md`.

I strongly suggest you fork this master repo and use git's [sparse checkout](http://jasonkarns.com/blog/subdirectory-checkouts-with-git-sparse-checkout/) feature to keep each piece/layer of Median up to date based on your fork.

## A little history

Median was originally built by two student workers at Emerson College to host college admissions promotional videos. It was quickly expanded for use in the curriculum as a YouTube-like service with the advantage of integration with Emerson's user database and course enrollment information. Over time, Median was adapted to service many needs, such as live video, HTML5 support, support for very large file sizes, custom uploaders for award ceremonies and student groups, and a transcoding farm to create "versions" of videos for each platform type and connection speed.

The intention behind Median was always to open source the code and make it available to other colleges and universities who have a need for a YouTube-like service but kept within the "walled garden" of their institution.

The iteration included in this repository is "version 6.0", so if you see any references to "Median 6", that's why.

Also, I apologize in advance if the code is messy and/or ridiculous. Median is the product of over seven years of developing, creating, fixing, refactoring, and maintaining a large codebase by one person. It doesn't fit well in any one paradigm at the moment, but I've tried to keep the coding style somewhat consistent. Many of the pieces of Median have been radically changed over the years due to scaling needs.

## Support

Median was originally built by two students over the course of a couple of years, and then further developed and maintained by one full-time employee at Emerson College over several more years. The code is provided AS-IS, with no guarantee of support or warranty. However, feel free to report an issue and I'll see what I can do.

If you find a security hole, please report an issue! If you fork this and make it better, please report an issue and let me know, or create a pull request. If you implement an instance of this yourself somewhere, create an issue and let me know, I'd love to see what you do with it.