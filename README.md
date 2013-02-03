gumroad-php
===========

PHP wrapper for the [Gumroad API](http://gumroad.com/api/). Originally developed for [Ultiworld](http://ultiworld.com).

Dependencies:

* libcurl

## Usage

### Instantiating a client & user authentication

    # Construct the client object
    $gr = new Gumroad_Client();
    
    # Authenticate with email and password
    $gr->auth('YOUR_EMAIL', 'YOUR_PASSWORD');

Good practice to also deauth once you are done, but if you did not, the client destructor method will deauth for you.

### API Endpoints

#### [Sessions](https://gumroad.com/api/authentication)

##### Authenticate a session

    $gr->sessions->authenticate($params);
    
    # Shortcut method
    $gr->auth($email, $password);

##### Deauthenticate a session

    $gr->sessions->deauthenticate();

    # Shortcut method
    $gr->deauth();

#### [Links](https://gumroad.com/api/methods)

##### Get all links

    $gr->links->getLinks();

##### Get a link

    $gr->links->getLink($id);

##### Create a link

    $gr->links->createLink($params);

##### Edit a link

    $gr->links->editLink($id, $params);

##### Enable a link

    $gr->links->enableLink($id);

##### Disable a link

    $gr->links->disableLink($id);

##### Delete a link

    $gr->links->deleteLink($id);

## License
MIT License (see LICENSE).

Copyright (c) 2013 Orion Burt.
