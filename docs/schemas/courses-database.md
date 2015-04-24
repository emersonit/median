# Courses Database Schema

The `coursesdb` database in MongoDB relies on four collections:

- semesters
- courses
- teaching
- taking

If you would like to create your own courses database for use with Median, make sure it's creating documents that look like this:

## Semesters

A `semesters` document looks like:

	{
	"_id" : ObjectId("5539e9f100049a42218b4576"), // the MongoDB ID
	"year_code" : NumberLong(2015), // the year
	"year_code_desc" : "2014-2015", // the year description
	"academic_period" : NumberLong(201520), // the "academic period"
	"academic_period_desc" : "Spring 2015", // the friendly description
	"start_date" : NumberLong(1421730000), // the unix timestamp of when it begins
	"end_date" : NumberLong(1431144000), // the unix timestamp of when it ends
	"visibility_faculty" : { // a set of unix timestamps for when to show it to faculty
		"start_date" : NumberLong(1420866000),
		"end_date" : NumberLong(1432008000)
	},
	"visibility_students" : { // a set of unix timestamps for when to show it to students
		"start_date" : NumberLong(1421643600),
		"end_date" : NumberLong(1431230400)
	},
	"current" : true // a boolean of whether this is the current active semester
	}

## Courses

A `courses` document looks like:

	{
	"_id" : ObjectId("54ed810400049a7f6e8b4590"), // the MongoDB ID
	"cc" : "CC100-01", // the full course code
	"cd" : "CC", // the course code descriptor code
	"cn" : NumberLong(100), // the course code number
	"cs" : "01", // the course section number
	"ct" : "Public Speaking", // the course title
	"crn" : NumberLong(23344), // the unique CRN used by an ERP system
	"sc" : NumberLong(201520), // the semester code for this course
	"f" : NumberLong(1429858801) // a unix timestamp of "freshness", or when this was last updated
	}

## Teaching

A `teaching` document looks like:

	{
	"_id" : ObjectId("54b0dc4800049a73478b7f32"), // the MongoDB ID
	"b_pidm" : NumberLong(1234567), // the teacher's unique ID
	"b_id" : "E12345678", // the teacher's "Banner ID"
	"ec" : "user_name", // the teacher's unique username
	"cc" : "VM325-0", // the course they're teaching
	"crn" : NumberLong(22713), // the course's unique CRN
	"sc" : NumberLong(201520), // the semester code this applies to
	"f" : NumberLong(1429858801) // a unix timestamp of "freshness", or when this was last updated
	}

## Taking

A `taking` document looks like:

	{
	"_id" : ObjectId("54b0dc4800049a73478b7f32"), // the MongoDB ID
	"b_pidm" : NumberLong(1234567), // the student's unique ID
	"b_id" : "E12345678", // the student's "Banner ID"
	"ec" : "user_name", // the student's unique username
	"cc" : "VM325-0", // the course they're taking
	"crn" : NumberLong(22713), // the course's unique CRN
	"sc" : NumberLong(201520), // the semester code this applies to
	"f" : NumberLong(1429858801) // a unix timestamp of "freshness", or when this was last updated
	}
