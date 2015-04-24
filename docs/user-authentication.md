# Median User Authentication

By default, Median uses [SimpleSAMLphp](https://simplesamlphp.org/) on the web layer to authenticate users.

Median does not store user credentials or authentication information; Median simply reads the attributes given by SimpleSAML and assigns the user a user level and a unique user ID. By default, if a user record for the given username does not exist in Median's database, a new record is automatically provisioned. On subsequent logins, Median associates this user ID with the user based on the username given by the identity provider, which Median assumes is unique.

The user authentication piece, located in the web tier code at `includes/login_check.php`, is probably something you'll spend a lot of time customizing. Most of the determination for a given user's "level" is based on group membership in an LDAP system, such as Active Directory. For example, any user authenticating and has membership in the "Students" Active Directory group is given a user level of 5, which signifies a logged-in student or staff member. This user level indicates whether they are a member of the institution, faculty, a part of the public, or an admin.

You can override this user level by adding to their MongoDB user record the `o_ul` attribute, granting it whatever number you wish.

User levels:

`1` = admin
`2` = not used
`3` = not used
`4` = authenticated faculty
`5` = authenticated member of the community, staff/student
`6` = public