Cotya Authentication Module for Magento(2)
==========================================

The purpose of this module is to provide alternate login methods in a simple way 
and allow it to be easy to extend for other developers.

currently implemented login Provider:  
* Github


We make use of the `league/oauth2-client` package.

## Github related notes

Not every user has a public email address configured.
As we currently still require an email for Accounts (will not be needed in later releases)
you can set an additional config flag to require githubs user:email scope.
This includes, that we use the address configured as ***primary*** inside github and even prefer this to the public one.
 
Long time goal is, to use the ID instead of the email address to connect auth and account.
