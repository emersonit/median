# Getting Courses into Median

Like user credentials, Median does not store course enrollment information locally. Instead, Median relies on an external service of some kind (by default, another MongoDB database) to associate the current logged in user with courses they're either currently enrolled in or teaching. Median assumes that the user's username is unique and can act as the bridge to that data.

For your deployment of Median, most likely your implementation will need to be customized. To customize this, see the `getUserClasses()` function in the web application code in `includes/user_functions.php`. Modify that function as you wish, but make sure it returns similar output to what it already does. Median will use course codes and semester codes as strings to associate a Median entry with a given class.

For example, an entry may be associated with course "CC100-01" in semester "201510". Both codes are unique to the instance of the class and the instance of the semester, but to Median they're simply strings that must remain consistent. Technically, the actual values could be anything, as long as they're consistent across sessions. Your system could use full course names and full semester names.

## How It's Built in Median

If you want to mirror how it's built in Median, you'll need to create a process that syncs a MongoDB database called `coursesdb` (alongside the `median5` and `median6` databases) with your course enrollment, teaching, and semester information. The schema for the `coursesdb` database and its collections can be found in the `scheams` folder alongside this documentation.