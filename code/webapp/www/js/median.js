/*

    median javascript

*/

var home_url = 'https://median.emerson.edu/'; // needs to be edited!

// not config stuff
var upload_uuid = '';
var upload_status_checker = undefined;
var upload_deltas = [];
var uploaded_so_far = 0;
var upload_status_fail_counter = 0;

/*

    jquery goes in here -- everything to care about on page load

*/

$(function(){

    // stuff for the upload and file swap pages
    if ($('body').hasClass('upload-page') || $('body').hasClass('fileswap-page')) {
        // first, get a new unique upload slot ID to see if uploading is available
        console.log('getting new unique upload slot ID...');
        $.ajax({
            url: '/files/new-upload-id/',
            dataType: 'json',
            timeout: 1000,
            success: function(data) {
                //console.log('data returned:');
                //console.log(data);
                upload_uuid = data.uuid;
            },
            error: function(xhr, status, error) {
                console.error('error with getting new ID...')
                console.error('error: ' + status + ', ' + error);
                $('div#upload-step').hide();
                $('div#error-step').show();
            }
        });

        // next, what to do when uploading actually happens
        $('#upload-btn').click(function(event) {
            event.preventDefault();
            if (upload_uuid == '') {
                console.error('currently have no upload slot unique ID!');
                $('div#upload-step').hide();
                $('div#error-step').show();
                return;
            }
            console.log('starting upload...');
            $('div#upload-step').hide();
            $('div#uploading-step').show();
            $('#file-entry-template').remove(); // don't let the template interfere
            $.ajax('/files/upload/'+upload_uuid+'/', {
                data: $("input.upload-field").serializeArray(),
                files: $("input.upload-file"),
                iframe: true,
                processData: false,
                type: 'post',
                dataType: 'json',
                success: function(data) {
                    console.log('upload done...');
                    console.log(data);
                    var end_result = '';
                    if (data.error != undefined) {
                        end_result += '<div class="alert-box alert">'+data.error+'</div>';
                    } else {
                        for (var i = 0; i < data.length; i++) {
                            end_result += '<p>Media ID #'+data[i].mid+': '+data[i].title+', status: <span class="radius label '+((data[i].status * 1 >= 200) ? 'alert' : 'success')+'">'+data[i].status_message+'</span></p>';
                            if (data[i].mid * 1 > 0) {
                                end_result += '<input class="mid-field" type="hidden" value="'+data[i].mid+'" name="mid[]" />';
                                end_result += '<input class="mid-status-field" type="hidden" value="'+data[i].status+'" name="mid-status[]" />';
                            }
                        }
                    }
                    $('div#upload-result').html(end_result);
                    $('div#upload-step').remove(); // get rid of this entirely
                    $('div#uploading-step').hide();
                    $('div#uploaded-step').show();
                    clearInterval(upload_status_checker);
                },
                error: function(xhr, status, error) {
                    console.error('error with upload...');
                    console.error('error: ' + status + ', ' + error);
                    $('div#uploading-step').hide();
                    $('div#error-step').show();
                    clearInterval(upload_status_checker);
                }
            });
            upload_status_checker = setInterval(checkUploadStatus, 1000);
        });
    }

    // upload page stuff
    if ($('body').hasClass('upload-page')) {

        // handle adding another file to the mix
        $('a#add-another-file').click(function(event) {
			event.preventDefault();
			var howmany_files = $('div.file-entry').length;
			if (howmany_files >= 5) {
				alert('Sorry, you can only upload five entries at a time.');
				return;
			}
			var another_file_div = $('div#file-entry-template').clone(true);
			//var the_html = another_file_div.html();
			//the_html = the_html.replace(/XXX/g, (howmany_files+1));
			//another_file_div.html(the_html);
			another_file_div.removeAttr('id');
			another_file_div.removeAttr('style');
			another_file_div.addClass('file-entry');
			$('div#file-entry-list').append(another_file_div);
		});

        // handle removing files from the mix
		$('div.remove-this-file').click(function(event) {
			event.preventDefault();
			var howmany_files = $('div.file-entry').length;
			if (howmany_files <= 1) {
				alert('Sorry, you need to upload at least one file.');
				return;
			}
			$(this).parent().remove();
		});

        // deal with left side nav
        $('#upload-nav li a').click(function(event) {
			event.preventDefault();
			var show_id = $(this).attr('data-id');
			$('.upload-page div.upload-panel').hide();
			$('#upload-nav li').removeClass('active');
			$('.upload-page div#'+show_id).show();
			$(this).parent().addClass('active');
			//alert('Show ' + $(this).attr('data-id'));
			if (show_id == 'access-settings') {
				$('div#access-results').show();
				checkAccessSettings();
			} else {
				$('div#access-results').hide();
			}
		});

        // what happens when something changes and affects access settings
        $('.access-result-trigger').change(checkAccessSettings);

        // stick the access results help thing to the left
		$('#access-results').sticky({ getWidthFrom: '#uploader-sidebar', topSpacing: 20 });

        // handle the many submit buttons
        $('a.submit-form').click(function(event) {
			event.preventDefault();
			$('form#the-form').submit();
		});

        // handle the many "next step" continue buttons
        $('a.next-step').click(function(event) {
			event.preventDefault();
			var show_id = $(this).attr('data-next-step');
			$('.upload-page div.upload-panel').hide();
			$('#upload-nav li').removeClass('active');
			$('.upload-page div#'+show_id).show();
			$('#upload-nav li a[data-id='+show_id+']').parent().addClass('active');
		});

        // handle submitting the upload form in its entirety
        $('form#the-form').submit(function() {
			// check out whether there's a link and whether anything has been uploaded
			var thelink = $('input#the-link').val();
			var uploaded_something = false;
			if ($('input.mid-field').length) {
				$('input.mid-field').each(function() {
					if ($(this).val() != '' && $(this).val() * 1 > 0) {
						uploaded_something = true;
					}
				});
			}
			// if neither a link or media file has been added, throw an error
			if ($.trim(thelink) == '' && uploaded_something == false) {
				alert('Sorry, it looks like you did not actually upload anything or provide a link.');
				return false;
			}

			// require at least one media owner... that's it, really
			var has_owner = false;
			$('input.user-owner-field').each(function() {
				if ($.trim($(this).val()) != '') {
					has_owner = true;
				}
			});
			$('select.group-owner-field').each(function() {
				if ($(this).val() * 1 > 0) {
					has_owner = true;
				}
			});
			if (has_owner == false) {
				alert('Sorry, it looks like you did not set anyone to be the owner of the file.');
				return false;
			}
			// otherwise, we're cool

			// if it's a link, remove all the upload stuff
			if (uploaded_something == false) {
				$('div#upload-step').remove();
			}

			return true;
		});

    } // end upload page stuff

    // main page stuff
    if ($('body').hasClass('main-page')) {

        $('input#big-upload-button').click(function() {
            window.location.href = '/upload/';
        });

        $('input#big-login-button').click(function() {
            window.location.href = '/login/';
        });

        $('input#filter-submit').click(function() {
            // filter
            console.log('ok, filter!');
        });

        // load up the main content based on what route we're at
        changeIndexList();

        // update list if filter is clicked
		if ($('input#filter-submit').length) {
			$('input#filter-submit').click(function() {
				changeIndexList();
			});
		}

    } // end main page stuff

    // player page stuff
    if ($('body').hasClass('player-page')) {
		//alert('player page!');

		var mid = $('#mid').val();

		var views_chart_options = {
			lines: { show: true },
			grid: { hoverable: true },
			points: { show: true, fill: false },
			legend: { position: "nw" },
			xaxis: { tickSize: [1, "day"], tickDecimals: 0, mode: "time", timeformat: "%m/%d/%y", font: { family: "sans-serif", size: 10, color: "#333333" } },
			yaxis: { tickSize: 5, tickDecimals: 0, font: { family: "sans-serif", size: 10, color: "#333333" } }
		};

		var the_views_data = {};

		var previousChartPoint = null;

		function showChartTooltip(x, y, contents) {
			$("<div id='chart-tooltip'>" + contents + "</div>").css({
				position: "absolute",
				display: "none",
				"font-family": "sans-serif",
				"font-size": "10px",
				top: y + 5,
				left: x + 5,
				border: "1px solid #fdd",
				padding: "2px",
				"background-color": "#eee",
				opacity: 0.80
			}).appendTo("body").fadeIn(200);
		}

		$("#views-chart-container").bind("plothover", function (event, pos, item) {
			if (item) {
				if (previousChartPoint != item.dataIndex) {
					previousChartPoint = item.dataIndex;
					$("#chart-tooltip").remove();
					var x = item.datapoint[0].toFixed(2), y = item.datapoint[1].toFixed(2);
					showChartTooltip(item.pageX, item.pageY, Math.round(y));
				}
			} else {
				$("#chart-tooltip").remove();
				previousChartPoint = null;
			}
		});

		$('a#view-count').click(function(event) {
			event.preventDefault();
			$('div#views-graph').toggle();
			// load chart
			$.ajax({
				url: '/views/chart/'+mid+'/',
				type: 'get',
				dataType: 'json',
				success: function(data) {
					the_views_data = data;
					$.plot($("#views-chart-container"), the_views_data, views_chart_options);
				}
			});
		});

		$('#breakdown-submit-btn').click(function() {
			var breakdown = $('#breakdown-select').val();
			if (breakdown == "week") {
				views_chart_options.xaxis.tickSize = [7, "day"];
			} else {
				views_chart_options.xaxis.tickSize = [1, breakdown];
			}
			$.plot($("#views-chart-container"), the_views_data, views_chart_options);
		});

		$('#relative-submit-btn').click(function() {
			var amount = $('#relative-amount').val();
			var unit = $('#relative-unit').val();
			$.ajax({
				url: '/views/chart/'+mid,
				type: 'post',
				data: { t: 'r', h: amount, u: unit },
				dataType: 'json',
				success: function(data) {
					the_views_data = data;
					$.plot($("#views-chart-container"), the_views_data, views_chart_options);
				}
			});
		});

		$('#absolute-submit-btn').click(function() {
			var start = $('#absolute-start').val();
			var end = $('#absolute-end').val();
			$.ajax({
				url: '/views/chart/'+mid,
				type: 'post',
				data: { t: 'a', s: start, e: end },
				dataType: 'json',
				success: function(data) {
					the_views_data = data;
					$.plot($("#views-chart-container"), the_views_data, views_chart_options);
				}
			});
		});

        $('div.sub-nav a').click(function(event) {
            event.preventDefault();
            var panel_to_show = $(this).attr('data-panel-id');
            $('div.sub-nav a').removeClass('player-panel-active');
            $(this).addClass('player-panel-active');
            $('div.player-panel').hide();
            $('div#'+panel_to_show).show();
        });

		if ($('a#add-fav-btn').length) {
			$('a#add-fav-btn').click(function(event) {
				event.preventDefault();
				$.ajax({
					url: '/do/addfav.php',
					type: 'post',
					dataType: 'text',
					data: { mid: $('input#mid').val() },
					success: function(data) {
						if (data == 'done') {
							alert('Added to favorites!');
						} else {
							alert(data);
						}
					},
					error: function() {
						alert('There was an error of some kind, please try again.');
					}
				});
			});
		}

		if ($('a#add-to-class-btn').length) {
			$('a#add-to-class-btn').click(function(event) {
				event.preventDefault();
				$.ajax({
					url: '/do/addtoclass.php',
					type: 'post',
					dataType: 'text',
					data: { mid: $('input#mid').val(), cl: $('select#add-to-class-id').val() },
					success: function(data) {
						if (data == 'done') {
							alert('Added to class!');
						} else {
							alert(data);
						}
					},
					error: function() {
						alert('There was an error of some kind, please try again.');
					}
				});
			});
		}

		$('#player-panel-nav dd a').click(function(event) {
			event.preventDefault();
			var show_id = $(this).attr('data-id');
			$('#player-page div.player-panel').hide();
			$('#player-panel-nav dd').removeClass('active');
			$('#player-page div#'+show_id).show();
			$(this).parent().addClass('active');
			//alert('Show ' + $(this).attr('data-id'));
		});

		if ($('a#comment-timecode-get').length) {
			$('a#comment-timecode-get').click(function(event) {
				event.preventDefault();
				$('input#comment-timecode').val($('input#video-currentTime').val());
			});
		}

		if ($('a#clip-get-start').length) {
			$('a#clip-get-start').click(function(event) {
				event.preventDefault();
				$('input#clip-start').val($('input#video-currentTime').val());
			});
			$('a#clip-get-end').click(function(event) {
				event.preventDefault();
				$('input#clip-end').val($('input#video-currentTime').val());
			});
			$('input#clip-submit').click(function(event) {
				event.preventDefault();
				if ($('input#clip-start').val() == undefined || $('input#clip-start').val() == '') {
					alert('You must first specify a clip start time.');
					return false;
				}
				if ($('input#clip-end').val() == undefined || $('input#clip-end').val() == '') {
					alert('You must first specify a clip end time.');
					return false;
				}
				var intime_val = $('input#clip-start').val();
				var outtime_val = $('input#clip-end').val();
				if (intime_val.indexOf(':') > -1) {
					intime_val = covertTimecodeToSeconds(intime_val);
				}
				if (outtime_val.indexOf(':') > -1) {
					outtime_val = covertTimecodeToSeconds(outtime_val);
				}
				if (outtime_val <= 0) {
					alert('Sorry, you cannot have an end time of 0 seconds.');
					return false;
				}
				if (intime_val >= outtime_val) {
					alert('Sorry, you cannot have a start time after the end time.');
					return false;
				}
				$.ajax({
					url: '/do/newclip.php',
					type: 'post',
					dataType: 'html',
					data: { mid: $('input#mid').val(), intime: intime_val, outtime: outtime_val, title: $('input#clip-name').val() },
					success: function(data) {
						$('div#clip-result').html(data);
					},
					error: function() {
						$('div#clip-result').html('<div class="alert-box alert">There was an error, please try again.</div>');
					}
				});
			});
		}

		if ($('input#comment-submit').length) {
			$('input#comment-submit').click(function(event) {
				event.preventDefault();
				var thecomment = $.trim($('textarea#comment-text').val());
				if (thecomment == undefined || thecomment == '') {
					alert('You need to type in a comment before you can submit one.');
					return;
				}
				var thedata = {};
				thedata.mid = $('input#mid').val();
				thedata.c = thecomment;
				if ($('input#comment-timecode').val() != undefined && $('input#comment-timecode').val() != '') {
					thedata.t = $('input#comment-timecode').val();
				}
				$.ajax({
					url: '/do/addcomment.php',
					type: 'post',
					data: thedata,
					dataType: 'html',
					success: function(data) {
						$('div#comments-list').prepend(data);
					},
					error: function() {
						alert('There was an error submitting the comment.');
					}
				});
			});
		}

		if ($('a#thumbnail-fix-btn').length) {
			$('a#thumbnail-fix-btn').click(function(event) {
				event.preventDefault();
				$('div#thumbnail-result').html('<img src="/images/loading.gif" />');
				$.ajax({
					url: '/do/thumbnail.php',
					type: 'post',
					data: { t: 'f', mid: $('input#mid').val() },
					dataType: 'html',
					success: function(data) {
						$('div#thumbnail-result').html(data);
					},
					error: function() {
						$('div#thumbnail-result').html('<div class="alert-box alert">Sorry, there was an error!</div>');
					}
				});
			});
		}

		if ($('a#thumbnail-random-btn').length) {
			$('a#thumbnail-random-btn').click(function(event) {
				event.preventDefault();
				$('div#thumbnail-result').html('<img src="/images/loading.gif" />');
				$.ajax({
					url: '/do/thumbnail.php',
					type: 'post',
					dataType: 'html',
					data: { t: 'r', mid: $('input#mid').val() },
					success: function(data) {
						$('div#thumbnail-result').html(data);
					},
					error: function() {
						$('div#thumbnail-result').html('<div class="alert-box alert">Sorry, there was an error!</div>');
					}
				});
			});
		}

		if ($('input#thumbnail-upload-btn').length) {
			$('input#thumbnail-upload-btn').click(function(event) {
				event.preventDefault();
				$.ajax({
					url: '/do/thumbnail.php',
					files: $('input#thumb-file'),
					type: 'post',
					iframe: true,
					processData: false,
					dataType: 'json',
					data: { t: 'u', mid: $('input#mid').val() },
					success: function(data) {
						var result = '';
						if (data.result == 'done') {
							result = '<div class="alert-box success">Uploaded!</div>';
						} else {
							result = '<div class="alert-box alert">'+data.result+'</div>';
						}
						$('div#thumbnail-result').html(result);
					},
					error: function() {
						$('div#thumbnail-result').html('<div class="alert-box alert">Sorry, there was an error!</div>');
					}
				});
				$('div#uploadThumbModal a.close-reveal-modal').click();
			});
		}

		if ($('input#video-subtitles').length) { // subtitles available?
			var pa = new WebVTTParser();
			$.get($('input#video-subtitles').val(), function(data) {
				var subtitles_data = pa.parse(data);
				//console.log(subtitles_data);
				video_captions = subtitles_data.cues;
			});
		}

		if ($('input#video-subtitles-enable').length) {
			$('input#video-subtitles-enable').click(function() {
				show_captions = $('input#video-subtitles-enable').prop('checked');
			});
			show_captions = $('input#video-subtitles-enable').prop('checked');
		}

	} // end player page stuff

    // listing page stuff
    if ($('body').hasClass('listing-page')) {

        changeListingList(); // start loading entries on page load

        // deal with media list filtering
		if ($('input#filter-submit').length) {
			$('input#filter-submit').click(function() {
				changeListingList();
			});
		}

        // what happens when they click on the playlists link
		if ($('a#playlists-link').length) {
			$('a#playlists-link').click(function(event) {
				event.preventDefault();
				$('div.sub-nav a').removeClass('listing-nav-active');
				$(this).addClass('listing-nav-active');
				$('div#media-list').hide();
				$('div#playlists-list').show();
				$.ajax({
					url: '/listings/playlists.php?w='+$('input#route-name').val()+'&id='+$('input#route-id').val(),
					dataType: 'html',
					success: function(data) {
						$('div#playlists-list').html('');
						$('div#playlists-list').html(data);
						if ($('div.entry.clickable').length) {
							$('div.entry.clickable').click(function(event) {
								window.location = home_url+$(this).attr('data-type')+'/'+$(this).attr('data-id')+'/';
							});
						}
					}
				});
			});
		}

        // deal with managers trying to clear media from the listing
        if ($('a#clear-mids-btn').length) {
			$('a#clear-mids-btn').click(function(event) {
				event.preventDefault();
				if ($('input.entry-select:checked').length < 1) {
					alert('Sorry, you have not selected any entries.');
					return false;
				} else {
					var mids = [];
					$('input.entry-select:checked').each(function(index) {
						mids.push($(this).val());
					});
					var route_name = $('input#route-name').val();
					var route_id = $('input#route-id').val();
					$.ajax({
						url: '/flush/media/from/'+route_name+'/'+route_id+'/',
						type: 'post',
						dataType: 'text',
						data: { 'mids': mids },
						success: function(data) {
							if (data == 'done') {
								changeListingList();
							} else {
								alert('There was an error removing the media, please try again.');
							}
						}
					});
				}
			});
		}

		// subcategory dropdown logic
		if ($('select#subcat-visit-list').length) {
			$('input#subcat-visit-button').click(function(event) {
				window.location.href = '/category/' + $('select#subcat-visit-list').val() + '/';
			});
		}

    } // end listing page stuff

    // manage page stuff
    if ($('body').hasClass('manage-page')) {

        // update media list on body load
        changeManageList();

        // deal with filter submit
		if ($('input#filter-submit').length) {
			$('input#filter-submit').click(function() {
				changeManageList();
			});
		}

        // hmmm
		$('a.close-add-to-modal').click(function(event) {
			event.preventDefault();
		});

        // deal with selecting all media in the listing
		$('input#manage-select-all').click(function() {
			$('input.entry-select').prop('checked', $(this).prop('checked'));
		});
    } // end manage page stuff

    // search page stuff
    if ($('body').hasClass('search-page')) {
		$('input#search-btn').click(function(event) {
			event.preventDefault();
			trySearch();
		});
		if ($.trim($('input#search-box').val()) != '') {
			trySearch();
		} else {
			$('#search-header').html('..?');
		}
		$('input#filter-submit').click(function() {
			trySearch();
		});
		$('input#search-box').keypress(function(event) {
			if (event.which == 13) {
				trySearch();
			}
		});
	} // end search page stuff

    // stuff for anywhere you're dealing with editing medai metadata
    if ($('body').hasClass('upload-page') || $('body').hasClass('edit-page')) {

		$("input[name='media-owner']").change(function() {
			checkLicensing();
		});

		$("input[name='license-type']").change(function() {
			var license_type = $("input[name='license-type']:checked").val();
			checkLicensing();
			// show or hide creative commons options
			if (license_type == 'cc') {
				$('div#license-cc-options').show();
			} else {
				$('div#license-cc-options').hide();
			}
			// show or hide license holder and year options
			if (license_type == 'cc' || license_type == 'copyright') {
				$('div#license-holder-row').show();
				$('div#license-year-row').show();
			} else {
				$('div#license-holder-row').hide();
				$('div#license-year-row').hide();
			}
		});

		$('a.add-field-btn').click(function(event) {
			event.preventDefault();
			var tmp_field;
			switch ($(this).attr('data-context')) {
				case 'custom':
				tmp_field = $('div.custom-field-row').first().clone(true);
				break;
				case 'normal':
				default:
				tmp_field = $('div.field-row').first().clone(true);
			}
			tmp_field.find('.meta-field-input').replaceWith('<input type="text" name="fieldval[]" class="eleven meta-field-input" />');
			tmp_field.find('input').val('');
			tmp_field.find('select').val(0);
			$('div#metadata-list').append(tmp_field);
		});

		$('div.remove-this-field').click(function(event) {
			event.preventDefault();
			var parent_row = $(this).parent().parent();
			if (parent_row.hasClass('field-row') && $('div.field-row').length == 1) {
				parent_row.find('input').val('');
				parent_row.find('select').val(0);
			} else if (parent_row.hasClass('custom-field-row') && $('div.custom-field-row').length == 1) {
				parent_row.find('input').val('');
				parent_row.find('select').val(0);
			} else {
				$(this).parent().parent().remove();
			}
		});

		$('select.meta-field-name').change(function() {
			if ($(this).val() == 'notes' || $(this).val() == 'transcript') {
				//alert($(this).parent().parent().html());
				$(this).parent().parent().find('.meta-field-input').replaceWith('<textarea name="fieldval[]" class="eleven meta-field-input" style="height:100px;"></textarea>');
			} else {
				$(this).parent().parent().find('.meta-field-input').replaceWith('<input type="text" name="fieldval[]" class="eleven meta-field-input" />');
			}
		});

		$('input.owner-field').keyup(function() {
			populateShowOwner();
		});

		$('select.owner-field').change(function() {
			populateShowOwner();
		});

	} // end of stuff for anywhere you're dealing with editing medai metadata

    // stuff for anywhere you can add media in bulk
    if ($('body').hasClass('upload-page') || $('body').hasClass('manage-page') || $('body').hasClass('edit-page') || $('body').hasClass('new-event-page') || $('body').hasClass('edit-event-page') || $('body').hasClass('edit-category-page')) {

        // user autocomplete field options
		var user_ac_options = {
			source: '/listings/ac_users.php',
			minLength: 2,
			select: function(event, ui) {
				$(this).val(ui.item.value);
				populateShowOwner();
				return false;
			}
		};

        // remove from the form something that already existed
		$('a.remove-preselected').click(function(event) {
			event.preventDefault();
			$(this).parent().parent().remove();
			populateShowOwner();
		});

        // a function that allows adding more items to a form
		var addingAnother = function(event) {
			event.preventDefault();
			var tmp_field = $(this).parent().parent().clone();
			tmp_field.find('input').val('');
			tmp_field.find('select').val(0);
			tmp_field.find('a.add-another').click(addingAnother);
			tmp_field.find('a.remove-other').click(removingAnother);
			tmp_field.find('input.user-owner-field').autocomplete(user_ac_options);
			$(this).parent().parent().after(tmp_field);
		}

		var removingAnother = function(event) {
			event.preventDefault();
			var this_context = $(this).attr('data-context');
			if ($('a.add-another[data-context='+this_context+']').length == 1) {
				$(this).parent().parent().find('input').val('');
				$(this).parent().parent().find('select').val(0);
			} else {
				$(this).parent().parent().remove();
			}
			populateShowOwner();
		}

		$('a.add-another').click(addingAnother);

		$('a.remove-other').click(removingAnother);

		$('input.user-owner-field').autocomplete(user_ac_options);
	} // end stuff for anywhere you can add media in bulk


    // live page stuff
    if ($('body').hasClass('live-page')) {
		$('a.live-nav-btn').click(function(event) {
			event.preventDefault();
			$('a.live-nav-btn').parent().removeClass('active');
			$(this).parent().addClass('active');
			$('div.live-panel').hide();
			$('div#'+$(this).attr('data-id')).show();
		});
	} // end live page stuff

    // edit page stuff
    if ($('body').hasClass('edit-page')) {
		checkAccessSettings();
		$('.access-result-trigger').change(checkAccessSettings);
		$('#access-results').sticky({ getWidthFrom: '#access-results-sidebar', topSpacing: 20 });
	} // end edit page stuff

    // certain edit pages stuff
    if ($('body').hasClass('edit-page') || $('body').hasClass('edit-event-page') || $('body').hasClass('edit-category-page')) {

		$('form#the-form').submit(function() {
			// require at least one media owner... that's it, really
			var has_owner = false;
			$('input.user-owner-field').each(function() {
				if ($.trim($(this).val()) != '') {
					has_owner = true;
				}
			});
			$('select.group-owner-field').each(function() {
				if ($(this).val() * 1 > 0) {
					has_owner = true;
				}
			});
			if (has_owner == false) {
				alert('Sorry, it looks like you did not set anyone to be the owner of the file.');
				return false;
			}
			// otherwise, we're cool
			return true;
		});
	} // end certain edit pages stuff

    // stuff for just the edit category page
    if ($('body').hasClass('edit-category-page')) {
		$('form#cat-form').submit(function(event) {
			// check for name
			if ($('input#cat-name').val() == '') {
				alert('Sorry, you did not put in a name for the category.');
				$('input#cat-name').focus();
				return false;
			}
			// check for owners
			var has_owner = false;
			$('input.user-owner-field').each(function() {
				if ($.trim($(this).val()) != '') {
					has_owner = true;
				}
			});
			$('select.group-owner-field').each(function() {
				if ($(this).val() * 1 > 0) {
					has_owner = true;
				}
			});
			if (!has_owner) {
				alert('Sorry, it appears you have not selected anyone to be the owner of this category.');
				return false;
			}
		});
	} // end stuff for just the edit category page

    // stuff for the new and edit event pages
	if ($('body').hasClass('new-event-page') || $('body').hasClass('edit-event-page')) {
		$('form#event-form').submit(function(event) {
			// check for name
			if ($('input#event-name').val() == '') {
				alert('Sorry, you did not put in a name for the event.');
				$('input#event-name').focus();
				return false;
			}
			// check for owners
			var has_owner = false;
			$('input.user-owner-field').each(function() {
				if ($.trim($(this).val()) != '') {
					has_owner = true;
				}
			});
			$('select.group-owner-field').each(function() {
				if ($(this).val() * 1 > 0) {
					has_owner = true;
				}
			});
			if (!has_owner) {
				alert('Sorry, it appears you have not selected anyone to be the owner of this event.');
				return false;
			}
		});
	} // stuff for the new and edit event pages

    // stuff for the new and edit group pages
	if ($('body').hasClass('new-group-page') || $('body').hasClass('edit-group-page')) {

		var user_ac_options = {
			source: '/listings/ac_users.php',
			minLength: 2,
			select: function(event, ui) {
				//console.log(ui.item);
				var newrow = '';
				newrow += '<tr>';
				newrow += '<td><input type="hidden" name="m[]" class="member-id" value="'+ui.item.id+'" /><input class="owner-checkbox" type="checkbox" name="o[]" value="'+ui.item.id+'" /></td>';
				newrow += '<td>'+ui.item.value+'</td><td><input type="button" value="remove?" class="button small secondary radius remove-member-btn" /></td>';
				newrow += '</tr>';
				$('table#members-list tbody').append(newrow);
				$('input.remove-member-btn').click(function(event) {
					event.preventDefault();
					$(this).parent().parent().remove();
				});
				return false;
			}
		};

		$('input#add-member').autocomplete(user_ac_options);

		$('input.remove-member-btn').click(function(event) {
			event.preventDefault();
			$(this).parent().parent().remove();
		});

		$('form#group-form').submit(function(){
			// check for name
			if ($('input#group-name').val() == '') {
				alert('Sorry, you did not put in a name for the group.');
				$('input#group-name').focus();
				return false;
			}
			// check for members
			var has_member = false;
			if ($('input.member-id').length == 0) {
				alert('Sorry, it appears that you have not added any members.');
				$('input#add-member').focus();
				return false;
			}
			// check for owners
			var has_owner = false;
			$('input.owner-checkbox').each(function(index) {
				if ($(this).prop('checked') == true) {
					has_owner = true;
				}
			});
			if (!has_owner) {
				alert('Sorry, it appears you have not selected anyone to be the owner of this group.');
				return false;
			}
			return true;
		});
	} // stuff for the new and edit group pages

    // stuff for the ITG admin tools
    if ($('body').hasClass('itg-admin-page')) {
        var user_ac_options = {
            source: '/listings/ac_users.php',
            minLength: 2,
            select: function(event, ui) {
                $('input#userid-field').val(ui.item.id);
                $(this).val(ui.item.value);
                return false;
            }
        };
        $('input#username-field').autocomplete(user_ac_options);
    } // end stuff for the ITG admin tools

    /*

        misc stuff for everywhere

    */

    // when clicking a "major" delete button, confirm first
    if ($('.delete-major-btn').length) {
		$('.delete-major-btn').click(function(event) {
			return confirm('Are you sure you want to delete this?');
		});
	}

    // if a label item is clickable, let it be so
    if ($('span.clickable.label').length) {
		$('span.clickable.label').click(function(event) {
			// clickable labels: category, event, person, group, course
			var id = $(this).attr('data-id');
			if (id == undefined || id * 1 == 0) {
				return;
			}
			if ($(this).hasClass('category')) {
				window.location = '/category/'+id+'/';
			} else if ($(this).hasClass('event')) {
				window.location = '/event/'+id+'/';
			} else if ($(this).hasClass('person')) {
				window.location = '/user/'+id+'/';
			} else if ($(this).hasClass('group')) {
				window.location = '/group/'+id+'/';
			} else if ($(this).hasClass('course')) {
				window.location = '/class/'+id+'/';
			}
		});
	}

    // activate tooltips
    $(document).tooltip();

});

/*

    everything NOT to care about on page load goes below here

*/

// check the status of our upload
function checkUploadStatus() {
    console.log('checking upload status');
    if (upload_uuid == '') {
        console.error('no upload unique ID slot to check...');
        return;
    }
    $.ajax({
        url: '/files/upload-status/'+upload_uuid+'/',
        dataType: 'json',
        timeout: 750,
        success: function(data) {
            //console.log(data);
            var percent_filled = Math.round((data.bytes_received/data.bytes_total) * 100);
			$('div#progress-par-inside').css('width', percent_filled+'%');
			var upload_delta = data.bytes_received - uploaded_so_far; // get the current delta since last status update
			if (upload_deltas.length == 5) {
				upload_deltas.shift(); // shift out the first array item
				upload_deltas.push(upload_delta); // add new delta
				// get the average of the five deltas in the array and get bytes per second
				var upload_delta_total = 0;
				for (var i in upload_deltas) {
					upload_delta_total += upload_deltas[i];
				}
				var upload_speed_average = upload_delta_total/upload_deltas.length;
				var upload_remaining = data.bytes_total - data.bytes_received;
				var upload_remaining_secs = upload_remaining/upload_speed_average;
                if (upload_speed_average == 0) {
                    // uhhh...?
                    if (percent_filled >= 99) {
                        $('#upload-debug-txt').html('Upload speed has dropped to zero. Since you\'ve successfully uploaded pretty much all of it, that usually means Median is processing your files. Please be patient, this page will update when Median has finished.');
                    } else {
                        $('#upload-debug-txt').html('Upload speed has dropped to zero. Either that means your files are being processed, or your connection dropped. Please be patient, and check your internet connection.');
                    }
                } else if ((upload_speed_average/1024/1024) > 1) {
					// use megabytes
					$('#upload-debug-txt').html('Uploading at ' + roundToTenths(upload_speed_average/1024/1024) + ' MBps average, '+convertSecondsToFriendly(upload_remaining_secs)+' left at this speed.');
				} else {
					// use kilobytes
					$('#upload-debug-txt').html('Uploading at ' + roundToTenths(upload_speed_average/1024) + ' KBps average, '+convertSecondsToFriendly(upload_remaining_secs)+' left at this speed.');
				}
			} else {
				// just add new delta, don't do calculation yet
				upload_deltas.push(upload_delta); // add new delta
				$('#upload-more-info').html('Collecting statistics on upload speed...');
			}
            uploaded_so_far = data.bytes_received * 1; // update how much has been uploaded
        },
        error: function(xhr, status, error) {
            console.error('error with getting upload status...')
            console.error('error: ' + status + ', ' + error);
            upload_status_fail_counter++;
            if (upload_status_fail_counter > 3) {
				$('div#uploading-step').hide();
				$('div#error-step').show();
				$('div#error-step').html('<p>The Median uploader suddenly became unavailable. If you <b>just started your upload and it abruptly stopped</b>, please check your internet connection, refresh the page, and try again. If <b>your upload has finished and was in the midst of processing</b>, please keep waiting! Median is inspecting your file and will update this page in a minute or two.</p>');
				clearInterval(upload_status_checker);
			}
        }
    });
}

// on index page -- change what's listed!
function changeIndexList() {
	if ($('input#route').length < 1) {
		return false;
	}
	var route_name = $('input#route').val();
	var options = { };
	options.type = 'post';
	options.dataType = 'html';
	options.success = function(data) {
		$('div#main-list').html(data);
		if ($('div.entry.clickable').length) {
			$('div.entry.clickable').click(function(event) {
				window.location = home_url+$(this).attr('data-type')+'/'+$(this).attr('data-id')+'/';
			});
		}
		initMediaPagination();
	};
	var tmp_txt = '';
	switch (route_name) {
		case 'my-stuff':
		tmp_txt = 'Loading recent activity, please wait...';
		options.url = '/listings/mystuff.php';
		break;
		case 'latest-media':
		options.url = '/listings/media.php';
		if ($('input#filter-submit').length) {
			options.data = { 'sort': $('select#filter-sort').val(), 'type': $('select#filter-type').val(), 'page': $('input#media-page').val(), 'num': $('select#media-perpage').val() };
		}
		break;
		case 'groups':
        tmp_txt = 'Loading groups...';
		options.url = '/listings/groups.php';
		break;
		case 'cats':
        tmp_txt = 'Loading categories...';
		options.url = '/listings/cats.php';
		break;
		case 'tags':
        tmp_txt = 'Loading tags...';
		options.url = '/listings/tags.php';
		break;
		case 'events':
        tmp_txt = 'Loading events...';
		options.url = '/listings/events.php';
		break;
		case 'classes':
        tmp_txt = 'Loading classes...';
		options.url = '/listings/classes.php';
		break;
		case 'favs':
		options.url = '/listings/media.php?favs=yes';
		if ($('input#filter-submit').length) {
			options.data = { 'sort': $('select#filter-sort').val(), 'type': $('select#filter-type').val(), 'page': $('input#media-page').val(), 'num': $('select#media-perpage').val() };
		}
		break;
		default:
		return false;
	}
    if (tmp_txt != '') {
        $('div#main-list').html(tmp_txt);
    }
	$.ajax(options);
}

// on listing page -- change what's listed
function changeListingList() {
	if ($('input#route-name').length < 1) {
		return false;
	}
	if ($('input#route-id').length < 1) {
		return false;
	}
	var route_name = $('input#route-name').val();
	var route_id = $('input#route-id').val();
	var list_manage = 'no';
	if ($('input#route-manager').val() == 1) {
		list_manage = 'yes';
	}
	var options = { };
	options.type = 'post';
	options.dataType = 'html';
	options.success = function(data) {
		$('div#main-list').html(data);
		if ($('div.entry.clickable').length) {
			$('div.entry.clickable').click(function(event) {
				window.location = home_url+$(this).attr('data-type')+'/'+$(this).attr('data-id')+'/';
			});
		}
		if ($('div.entry.selectable').length) {
			$('div.entry.selectable').click(function(event) {
				$(this).find('input.entry-select').prop('checked', !$(this).find('input.entry-select').prop('checked'));
			});
			$('input.entry-select').click(function(event) {
				$(this).prop('checked', !$(this).prop('checked'));
			});
		}
		initMediaPagination();
	};
	options.url = '/listings/media.php';
	options.data = { 'rname': route_name, 'rid': route_id, 'sort': $('select#filter-sort').val(), 'type': $('select#filter-type').val(), 'page': $('input#media-page').val(), 'num': $('select#media-perpage').val(), 'listmanage': list_manage };
	$.ajax(options);
}

// on manage page -- change what's listed
function changeManageList() {
	if ($('input#route-name').length < 1) {
		return false;
	}
	var route_name = $('input#route-name').val();
	var options = { };
	options.type = 'post';
	options.dataType = 'html';
	options.success = function(data) {
		$('div#main-list').html(data);
		if ($('div.entry.clickable').length) {
			$('div.entry.clickable').click(function(event) {
				window.location = home_url+$(this).attr('data-type')+'/'+$(this).attr('data-id')+'/';
			});
		}
		if ($('div.entry.selectable').length) {
			$('div.entry.selectable').click(function(event) {
				$(this).find('input.entry-select').prop('checked', !$(this).find('input.entry-select').prop('checked'));
			});
			$('input.entry-select').click(function(event) {
				$(this).prop('checked', !$(this).prop('checked'));
			});
		}
		if ($('.delete-major-btn').length) {
			$('.delete-major-btn').click(function(event) {
				var are_you_sure = confirm('Are you sure you want to delete this?');
				return are_you_sure;
			});
		}
		$('form#bulk-delete-form').submit(function() {
			if ($('input.entry-select:checked').length < 1) {
				alert('Sorry, you have not selected any entries.');
				return false;
			} else {
				return confirm('Are you sure you want to delete all of these entries?');
			}
		});
        $('form#add-to-form').submit(function() {
            if ($('input.entry-select:checked').length < 1) {
                alert('Sorry, you have not selected any entries.');
                return false;
            } else {
                $('input.entry-select:checked').each(function(index) {
                    $('form#add-to-form').append('<input type="hidden" name="mid[]" value="'+$(this).val()+'" />');
                });
                return true;
            }
        });
		initMediaPagination();
	};
	switch (route_name) {
		case 'media':
		options.url = '/listings/media.php?manage=yes';
		if ($('input#filter-submit').length) {
			options.data = { 'owner': $('select#filter-owner').val(), 'sort': $('select#filter-sort').val(), 'type': $('select#filter-type').val(), 'page': $('input#media-page').val(), 'num': $('select#media-perpage').val() };
		}
		break;
		case 'groups':
		options.url = '/listings/groups.php?manage=yes';
		break;
		case 'playlists':
		options.url = '/listings/playlists.php?manage=yes';
		break;
		default:
		return false;
	}
	$.ajax(options);
}

// things have changed somewhere -- update things
function updateList() {
	if ($('body').hasClass('main-page')) {
		changeIndexList();
	} else if ($('body').hasClass('listing-page')) {
		changeListingList();
	} else if ($('body').hasClass('manage-page')) {
		changeManageList();
	} else if ($('body').hasClass('search-page')) {
		trySearch();
	}
}

// do this wherever there's media listing pagination
function initMediaPagination() {
	if ($('#media-prev-btn').length && $('#media-next-btn').length) {
		var currentpage = $('input#media-page').val() * 1;
		$('#media-prev-btn').click(function(event) {
			event.preventDefault();
			$('input#media-page').val(currentpage - 1);
			updateList();
		});
		$('#media-next-btn').click(function(event) {
			event.preventDefault();
			$('input#media-page').val(currentpage + 1);
			updateList();
		});
		$('a.media-page-btn').click(function(event) {
			event.preventDefault();
			var newpage = $(this).attr('data-id') * 1;
			$('input#media-page').val(newpage);
			updateList();
		});
		$('select#media-perpage').change(function(event) {
			updateList();
		});
	}
}

// for player page -- this gets called by video player
function onCurrentTimeChange(time, playerId) {
	$("input#video-currentTime").val(time);
	if (show_captions == true) {
		var now = time * 1;
		$('div#video-caption').html('');
		for (var i = 0; i < video_captions.length; i++) {
			if (video_captions[i].startTime <= now && video_captions[i].endTime >= now) {
				$('div#video-caption').html('<p>' + video_captions[i].text + '</p>');
			}
		}
	}
}

// for player page -- this gets called by video player
function onDurationChange(time, playerId) {
	$("input#video-duration").val(time);
}

// on upload page -- recheck licensing and options
function checkLicensing() {
	var public_access_option = '<option value="6" id="public-access-option">Publicly Accessible</option>';
	var license_type = $("input[name='license-type']:checked").val();
	var license_owner = $("input[name='media-owner']:checked").val();
	if ((license_type == 'copyright' && license_owner == 'else') || license_type == 'unknown') {
		$('option#public-access-option').remove();
	} else {
		if ($('option#public-access-option').length == 0) {
			$('select#access-options').append(public_access_option);
		}
	}
}

// do the check to update the friendly access settings robot
function checkAccessSettings() {
	/*
		go through every access setting and show the access results
	*/
	var access_visible = [];
	var access_hidden = [];

	var access_level = $('#access-options').val() * 1;
	var restricted_by_group = false;
	var passworded = false;
	var entry_hidden = false;
	var entry_classonly = false;

	// check to see if there's a group restriction
	if ($('.group-restrict-box').length) {
		$('.group-restrict-box').each(function() {
			if ($(this).val() != '0') {
				restricted_by_group = true;
			}
		});
	}

	// check if there's a password
	if ($('#entry-password').val() != '') {
		passworded = true;
	}

	// check if it's class-only
	if ($('#entry-classonly').prop('checked')) {
		entry_classonly = true;
	}

	// check if it's hidden
	if ($('#entry-hidden').prop('checked')) {
		entry_hidden = true;
	}


	if (restricted_by_group) {
		access_visible.push('Viewer must be in the group this is restricted to.');
		access_hidden.push('Anyone who isn\'t in the group this is restricted to.');
	}

	if (passworded) {
		access_visible.push('Viewer must have the password.');
		access_hidden.push('Anyone who doesn\'t have the password.');
	}

	if (entry_classonly) {
		access_visible.push('Viewer must be in one of the specified class(es).');
		access_hidden.push('Anyone who is not in one of the specified class(es).');
	}

	if (entry_hidden) {
		access_visible.push('Viewer must have the URL to the entry.');
		access_hidden.push('Any lists on Median.');
	}

	switch (access_level) {
		case 0:
		access_visible.push('Viewer must be an owner or in a group that owns the entry.');
		access_hidden.push('Anyone who is not an owner or in a group that owns the entry.');
		break;
		case 1:
		access_visible.push('Viewer must be an administrator.');
		access_hidden.push('Anyone who is not an administrator.');
		break;
		case 4:
		access_visible.push('Viewer must be a faculty member.');
		access_hidden.push('Anyone who is not a faculty member.');
		break;
		case 5:
		access_visible.push('Viewer must be logged in with an Emerson account.');
		access_hidden.push('Anyone who is not logged in.');
		break;
		case 6:
		break;
	}

	var access_visible_txt = '';
	var access_hidden_txt = '';
	for (var i = 0; i < access_visible.length; i++) {
		access_visible_txt += '<li>'+access_visible[i]+'</li>';
	}
	for (var i = 0; i < access_hidden.length; i++) {
		access_hidden_txt += '<li>'+access_hidden[i]+'</li>';
	}
	$('ul#access-results-visible').html(access_visible_txt);
	$('ul#access-results-hidden').html(access_hidden_txt);
}

// on upload page -- populate what they'd like the owner to be listed as
function populateShowOwner() {
	var already_selected = 0;
	if ($('div#show-owner-options input:checked').length) {
		// get this if one is already selected
		already_selected = $('div#show-owner-options input:checked').attr('id');
	}
	$('div#show-owner-options').html('');
	$('input[type="text"].owner-field').each(function() {
		var tempusr = $(this).val();
		if ($.trim(tempusr) != '') {
			$('div#show-owner-options').append('<label for="show-u-'+tempusr+'"><input type="radio" value="'+tempusr+'" id="show-u-'+tempusr+'" name="show-owner" /> '+tempusr+'</label>');
		}
	});
	$('input[type="hidden"].owner-field').each(function() {
		var tempgrpname = $(this).attr('data-group-name');
		var tempgrpid = $(this).val();
		if (tempgrpid * 1 > 0) {
			$('div#show-owner-options').append('<label for="show-g-'+tempgrpid+'"><input type="radio" value="'+tempgrpid+'" id="show-g-'+tempgrpid+'" name="show-owner" /> '+tempgrpname+'</label>');
		}
	});
	$('select.owner-field option:selected').each(function() {
		var tempgrpname = $(this).text();
		var tempgrpid = $(this).val();
		if (tempgrpid * 1 > 0) {
			$('div#show-owner-options').append('<label for="show-g-'+tempgrpid+'"><input type="radio" value="'+tempgrpid+'" id="show-g-'+tempgrpid+'" name="show-owner" /> '+tempgrpname+'</label>');
		}
	});
	if (already_selected != 0 && $('div#show-owner-options input#'+already_selected).length > 0) {
		$('div#show-owner-options input#'+already_selected).prop('checked', true);
	} else {
		$('input#show-default').prop('checked', true);
	}
}

// searching inline via AJAX
function trySearch() {
	var search_string = $.trim($('input#search-box').val());
	if (search_string == '') {
		alert('Please type in an entry title or a username to search for.');
		$('input#search-box').focus();
		return false;
	} else if (search_string.length < 3) {
		alert('Please enter more than two characters before searching.');
		$('input#search-box').focus();
		return false;
	} else {
		//alert('searching!');
		$('#search-header').html(search_string);
		if ($('select#search-type').val() == 'entries') {
			// search just entry titles...
			$('div#user-search').hide();
			$('div#media-search').show();
			searchEntries(search_string);
		} else if ($('select#search-type').val() == 'users') {
			// search just users...
			$('div#media-search').hide();
			$('div#user-search').show();
			searchUsers(search_string);
		} else {
			// search both...
			$('div#user-search').show();
			$('div#media-search').show();
			searchEntries(search_string);
			searchUsers(search_string);
		}
	}
}

// search for entries on the search page
function searchEntries(what) {
	if (what == undefined || what == '') {
		return false;
	}
	var options = { };
	options.type = 'post';
	options.dataType = 'html';
	options.success = function(data) {
		$('div#main-list').html(data);
		if ($('div.entry.clickable').length) {
			$('div.entry.clickable').click(function(event) {
				window.location = home_url+$(this).attr('data-type')+'/'+$(this).attr('data-id')+'/';
			});
		}
		initMediaPagination();
	};
	options.url = '/listings/media.php';
	options.data = { 'search': what, 'sort': $('select#filter-sort').val(), 'type': $('select#filter-type').val(), 'page': $('input#media-page').val(), 'num': $('select#media-perpage').val() };
	$('div#main-list').html('');
	$.ajax(options);
}

// search for users on the search page
function searchUsers(what) {
	if (what == undefined || what == '') {
		return false;
	}
	var options = { };
	options.type = 'post';
	options.dataType = 'html';
	options.success = function(data) {
		$('div#user-list').html(data);
	};
	options.url = '/listings/users.php';
	options.data = { 'search': what };
	$('div#user-list').html('');
	$.ajax(options);
}

// helper function
function roundToTenths(number) {
	return Math.round(number * 10)/10;
}

// helper function
function convertSecondsToFriendly(seconds) {
	if (seconds > 60) {
		return '' + Math.floor(seconds/60) + ' minute(s), ' + Math.round( seconds - (Math.floor(seconds/60) * 60) ) + ' seconds';
	} else {
		return '' + Math.round(seconds) + ' seconds';
	}
}

// helper function
function covertTimecodeToSeconds(timecode) {
	if (timecode == undefined) {
		return 0;
	}
	var seconds = 0;
	var tc_pieces = timecode.split(':');
	if (tc_pieces.length == 3) {
		seconds = (tc_pieces[0] * 60 * 60) + (tc_pieces[1] * 60) + (tc_pieces[2] * 1);
	} else if (tc_pieces.length == 2) {
		seconds = (tc_pieces[0] * 60) + (tc_pieces[1] * 1);
	} else if (tc_pieces.length == 1) {
		seconds = timecode * 1;
	} else {
		seconds = 0;
	}
	return seconds;
}
