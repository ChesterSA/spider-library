<?php 
namespace PWASpider;

use Exception;
use duzun\hQuery;
use RobotsTxtParser\RobotsTxtParser;
use RobotsTxtParser\RobotsTxtValidator;

class Spider
{     
    /**
     * Initiates the web crawler, looking for PWAs
     * 
     * @param  array $links An array of strings with the seed URLs to search through
     * @param  int $limit An integer setting the limit on how many sites should be returned 
     * @return array An array of the first $limit sites searched
     */
    public static function start(array $links, int $limit = 1000000)
    {
        for($i = 0; $i >= sizeof($links) || sizeof($links) < $limit; $i++)
        {        
            echo ("$i: ");
            if(array_key_exists($i, $links) && $links[$i] != null) { 
                $link = $links[$i]; 
            }
            else if ($i < sizeof($links)) { 
                continue; 
            }
        

            $links = array_unique(array_merge($links, Spider::scrape($link)));
            echo("\tSize: " . sizeof($links) . "\n");
            // if ($i >= sizeof($links)) { break; }
        }
        return array_slice($links, 0, $limit);
    }
    
    /**
     * Scrapes the given url, and returns a list of the URLs on the page
     *
     * @param string $url the URL of the page to search
     * @return array An array of all URLs present on the page in <a> tags
     */
    private static function scrape(string $url)
    {
        echo($url . "\n");
        try {
            if (Spider::validate($url, "MyPWABot")) {
                $tags = Spider::getTags($url, 'a');
                if ($tags == null) { return []; }

                $links = [];
                foreach($tags as $key => $tag) {
                    $url = Spider::getUrl($tag);
                    array_push($links, $url);
                    $links = array_unique($links);
                }
                return $links;
            }
            else { return []; }
        }
        catch (Exception $e){
            error_log("\n{$e->getMessage()}\n");
            return [];
        }
        return [];
    }
      
    /**
     * Validates that the given URL exists, and that the given UserAgent can access it
     *
     * @param  string $url the URL to validate
     * @param  string $userAgent The name of the UserAgent trying to access the page
     * @param  bool $isFormatted If the URL is formatted to a robots.txt page or not
     * @return bool If the given UserAgent can access the page
     */
    private static function validate(string $url, string $userAgent, bool $isFormatted = false)
    {
        if(!$isFormatted){
            $parsed = parse_url($url);
            $url = "http://{$parsed['host']}/robots.txt";
        }

        $file_headers = @get_headers($url);
        if($file_headers && in_array('HTTP/1.1 200 OK', $file_headers)){
            $robots = file_get_contents($url);
            $parser = new RobotsTxtParser($robots);
            $validator = new RobotsTxtValidator($parser->getRules());
            return $validator->isUrlAllow($url, $userAgent);
        }
        else {
            return false;
        }
    }
    
    /**
     * Returns an array containing all instances of the given tag on the url given
     *
     * @param  string $url The URL of the page to scan
     * @param  string $tag The name of the tag to search for, without angle brackets
     * @return array An array with all the tags from the URL page
     */
    private static function getTags(string $url, string $tag)
    {
        //TODO: Warning on 403,404 error from hQuery
        $doc = hQuery::fromFile($url, false);
        if ($doc == null) { return []; }
        try {
            $tags = $doc->find($tag);
        } catch (Exception $e) {
            return [];
        }
        return $tags;
    }
 
    /**
     * Given a formatted <a> tag, finds the base URL and returns it
     *
     * @param  mixed $tag A formatted URL to search through
     * @return string The URL contained within the tag
     */
    private static function getUrl($tag)
    {
        //TODO Warning when $tag is a bool
        if(!array_key_exists('scheme' , parse_url($tag->attr('href')))) { return null; }
        return parse_url($tag->attr('href'))['scheme'] . '://' . parse_url($tag->attr('href'))['host'];
    }
}