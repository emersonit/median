#
# you will want to edit this
#
create database m6saml_db;
create user 'm6saml_usr'@'%' identified by 'password';
grant all on m6saml_db.* to 'm6saml_usr'@'%';