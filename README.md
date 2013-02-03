gumroad-php
===========

Gumroad PHP library aka Gump

PHP wrapper for [Gumroad API](http://gumroad.com/api/). Originally developed for
 [Ultiworld](http://ultiworld.com].

Dependencies:

* libcurl

## Usage

### Instantiating a client & user authentication

    # Construct the client object
    $gr = new Gumroad_Client();
    
    # Authenticate with email and password
    $gr->auth('YOUR_EMAIL', 'YOUR_PASSWORD');

### API Endpoints

#### Sessions

##### Authenticate a session

    $gr->sessions->authenticate($params);
    
    #Shortcut
    $gr->auth($email, $password);

##### Deauthenticate a session

    $gr->sessions->deauthenticate();

    #Shortcut
    $gr->deauth;

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
MIT License. See LICENSE
Copyright (c) 2013 Orion Burt
