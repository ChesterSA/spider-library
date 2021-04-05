PWA Spider

This is a simple PHP package to install a web spider to find and return examples of Progressive Web Apps from the internet, all it needs is a collection of sites to start from and the rest is done for you.

Installation

The package is hosted on composer so can be installed with the command:

composer require cswannauger/pwa-spider
Using the Spider

Once it has been installed, to begin the Spider simply use PWASpider\Spider and then call

$sites = Spider::start(array $links, int $limit = 1000000);
The $links array is an array of URLs you would like the scraper to start with, and the optional variable $limit is set to the maximum amount of sites you would like returned. If not used it defaults to 1 million which could take days to run.

The method returns a nested array containing details of every web app. The array for each site is in the following format

 [
    'url' => 'https://twitter.com',
    'title' => 'Twitter',
    'img' => 'https://abs.twimg.com/responsive-web/client-web-legacy/icon-default-large.8e027b65.png',
    'description' => 'Twitter is what’s happening and what people are talking about right now.'
 ]
You also have access to the Spider::checkPWA($doc) method to confirm if any sites you have access to are Progressive Web Apps. This takes an duzun/hQuery document and processes it to check for PWA validity. If it is a PWA then it will return the location of the site's manifest.json file, else it will return false

How it works

The check is a fairly naive one at this point but from testing it is still accurate. It iterates through the <link> tags in the header of the site to see if any of them are a link to the site's manifest file (usually stored at /manifest.json). This file is a necessity to create well-formed progressive web apps, it is where the information about the application itself (name, icon, theme color, etc) is all stored.
