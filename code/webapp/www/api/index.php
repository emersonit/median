<?php

/*

    MEDIAN API GUIDE
        cyle gage, emerson college, 2014

*/

$login_required = false;
require_once('/median-webapp/includes/login_check.php');

require_once('/median-webapp/config/config.php');

$page_uuid = 'api-page';
$page_title = 'The Median API';
require_once('/median-webapp/includes/header.php');
?>

<div class="row">
    <div class="column full">
        <h2>Median API</h2>

		<p>Median provides a REST-based API for accessing Median content from anywhere.</p>

        <h4>Things you can definitely do with this API</h4>
		<ul>
		<li>Get lists of public median entries and filter based on user or category or media type or other stuff.</li>
		<li>Get a bunch of metadata for a specific entry.</li>
		<li>For things internal to the Emerson Community, you'll need an authorized API key. Please fill out an <a href="<?php echo $median_outside_help; ?>">Support Request</a> if you want to apply for one.</li>
		</ul>

		<h4>Things you can't do with this API:</h4>
		<ul>
		<li>See media that's class-only, disabled, restricted to a group, marked to be hidden, or pending.</li>
		</ul>

		<h4>Changelog</h4>
		<ul>
		<li>1/5/2010 - version 1.0 of the API, basic listing w/ filtering, and media metadata querying.</li>
		<li>3/18/2011 - version 2.0 of the API, rewritten to take advantage of new Median 4.0 features.</li>
		<li>8/29/2012 - version 3.0 of the API, rewritten to take advantage of new Median 5.0 features. Most everything should return the same as the previous version. Most notably, most date formats have changed to just returning Unix-format.</li>
        <li>9/1/2014 - no new version, but HTTPS is now required; you cannot make requests over HTTP.</li>
		</ul>

        <h3>Querying</h3>
		<p>
		<span class="basecmd">Red text</span> is a base command, it should be the first thing after the base URL, and you can only have one.<br />
		<span class="param">Blue text</span> is a parameter that can be put after the base command or any other parameter; you can have one of each to filter results.<br />
		<span class="value">Green text</span> is a value or range of possible values.
		</p>
		<ul>
		<li>Base URL: <b><?php echo $median_base_url; ?>api/?</b></li>
			<ul>
				<li><span class="basecmd">wut=list</span></li>
				<ul>
					<li><span class="param">&order=</span> (default=date_desc)</li>
						<ul>
						<li><span class="value">date_desc</span>, which means latest first</li>
						<li><span class="value">date_asc</span>, which means oldest first</li>
						<li><span class="value">views_desc</span>, which means top viewed first</li>
						<!-- <li><span class="value">rating_desc</span>, which means highest rated first</li> -->
						<li><span class="value">comments_desc</span>, which means most commented first</li>
						<li><span class="value">alpha_asc</span>, which means sorted by title, A through Z</li>
						<li><span class="value">alpha_desc</span>, which means sorted by title, Z through A</li>
						<li><span class="value">time_asc</span>, which means shortest first</li>
						<li><span class="value">time_desc</span>, which means longest first</li>
						</ul>
					<li><span class="param">&group=</span><span class="value">4-6</span> (default=6)</li>
						<ul>
						<li>group <span class="value">6</span> is publicly accessible via anybody on the web</li>
						<li>group <span class="value">5</span> is internal emerson community (students, staff, faculty); you need an API key for this</li>
						<li>group <span class="value">4</span> is faculty and admins only; you need an API key for this</li>
						</ul>
					<li><span class="param">&apikey=</span><span class="value">your assigned API key</span> (default=null)</li>
						<ul>
						<li>you need this if you want to see anything other than public entries; adding in a false or disabled one will be the same as not entering one</li>
						</ul>
					<li><span class="param">&limit=</span><span class="value">1-40</span> (default=20)</li>
						<ul>
						<li>this is simply how many entries you want per page</li>
						</ul>
					<li><span class="param">&page=</span><span class="value">#</span> (default=1)</li>
						<ul>
						<li>all returned XML/JSON comes with a "pages" item in the root for tracking on your end</li>
						</ul>
					<li><span class="param">&user=</span><span class="value">ecnet name</span> or <span class="value">median user ID number</span> (default=null)</li>
						<ul>
						<li>accepts names formatted like cyle_gage or Francis_Frain, or their internal median ID number</li>
						</ul>
					<li><span class="param">&type=</span><span class="value">type name</span> (default=null)</li>
						<ul>
						<li>available types include: video, audio, image, doc, link</li>
						</ul>
					<li><span class="param">&cid=</span><span class="value">#</span> (default=null)</li>
						<ul>
						<li>requires a category ID number</li>
						</ul>
					<li><span class="param">&clid=</span><span class="value">coursecode-sectionnumber</span> (default=null)</li>
						<ul>
						<li>requires the course code formatted like VM100-01, only pulls from current semester</li>
						<li>must have an API key to do this</li>
						</ul>
					<li><span class="param">&eid=</span><span class="value">#</span> (default=null)</li>
						<ul>
						<li>requires an event ID number</li>
						</ul>
					<li><span class="param">&gid=</span><span class="value">#</span> (default=null)</li>
						<ul>
						<li>requires a group ID number</li>
						</ul>
					<li><span class="param">&plid=</span><span class="value">#</span> (default=null)</li>
						<ul>
						<li>requires a playlist/channel/folder ID number</li>
						</ul>
					<li><span class="param">&format=</span><span class="value">xml</span> or <span class="value">json</span> (default=xml)</li>
						<ul>
						<li>lets you choose whether the result comes back in XML format or JSON format</li>
						</ul>
				</ul>
				<li><span class="basecmd">wut=info</span></li>
				<ul>
					<li><span class="param">&mid=</span><span class="value">#</span> (required)</li>
						<ul>
						<li>specific media ID</li>
						</ul>
					<li><span class="param">&format=</span><span class="value">xml</span> or <span class="value">json</span> (default=xml)</li>
						<ul>
						<li>lets you choose whether the result comes back in XML format or JSON format</li>
						</ul>
					<li><span class="param">&apikey=</span><span class="value">your assigned API key</span> (default=null)</li>
						<ul>
						<li>you need this if you want to see anything other than public entries; adding in a false or disabled one will be the same as not entering one</li>
						</ul>
				</ul>
			</ul>
		</ul>
		<h3>And it will return...</h3>
		<p>The <span class="basecmd">list</span> function will return a straightforward XML document or JSON array of media entries, each of which includes the median entry ID, title, usernames and group names of owners, number of views, the media type, the URLs to thumbnails, and a bunch of other useful stuff.</p>
		<p>If no results exist for that list query, you'll get an XML or JSON document with nodes "count" and "pages" both equalling zero.</p>
		<p>The <span class="basecmd">info</span> function will return an XML document or JSON array containing a large assortment of metadata about the entry requested. Some of it is useful, some of it is technical.</p>
		<p>If no media entry exists or is inaccessible for the queried ID, you'll get a XML/JSON document with the root element "error" with an error message inside.</p>
		<h3>Some examples:</h3>
		<p>Want a list of the 20 most recent publicly-viewable entries?<br />
		<b><?php echo $median_base_url; ?>api/?wut=list</b></p>
		<p>Want to see the 40 top viewed publicly-viewable videos?<br />
		<b><?php echo $median_base_url; ?>api/?wut=list&order=views_desc&limit=40&type=video</b></p>
		<p>Want to see page 3 (10 per page) of the latest audio entries available to the Emerson Community?<br />
		<b><?php echo $median_base_url; ?>api/?wut=list&limit=10&page=3&type=audio&group=5&apikey=XXXXXXX</b></p>
		<p>Want to see the metadata for media entry #20075?<br />
		<b><?php echo $median_base_url; ?>api/?wut=info&mid=20075</b></p>
		<p>Try it!</p>
    </div>
</div>

<?php
require_once('/median-webapp/includes/footer.php');
