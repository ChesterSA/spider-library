<?php 
namespace PWASpider;
use Exception;
use duzun\hQuery;
use RobotsTxtParser\RobotsTxtParser;
use RobotsTxtParser\RobotsTxtValidator;

class Spider
{    
    private static $links;
    private static $sites = [];

    /**
     * Initiates the web crawler, looking for PWAs
     * 
     * @param  array $links An array of strings with the seed URLs to search through
     * @param  int $limit An integer setting the limit on how many sites should be returned 
     * @return array An array of the first $limit sites searched
     */
    public static function start(array $links, int $limit = 1000000)
    {
        Spider::$links = $links;
        for($i = 0; $i < sizeof(Spider::$links) && sizeof(Spider::$sites) < $limit; $i++){      
              
            if(array_key_exists($i, Spider::$links) && Spider::$links[$i] != null) { 
                $link = Spider::$links[$i]; 
            }
            else if ($i < sizeof(Spider::$links)) { 
                continue; 
            }
            Spider::scrape($link);
        }
        return array_slice(Spider::$sites, 0, $limit);
    }
    
    /**
     * Scrapes the given url, and returns a list of the URLs on the page
     *
     * @param string $url the URL of the page to search
     * @return array An array of all URLs present on the page in <a> tags
     */
    private static function scrape(string $url)
    {    
        try {
            if (Spider::validate($url, "MyPWABot")) {
                
                $doc = hQuery::fromFile($url, false);
                if ($doc == null) { return []; }
                Spider::getDetails($doc);

                $tags = $doc->find('a');
                if ($tags == null) { return []; }
                foreach($tags as $key => $tag) {
                    $url = Spider::getUrl($tag);
                    array_push(Spider::$links, $url);
                    Spider::$links = array_unique(Spider::$links);
                }
            }
        }
        catch (Exception $e){
            error_log("\n{$e->getMessage()}\n");
        }
        return [];
    }

    private static function getDetails($doc)
    {
        $manifest = Spider::checkPWA($doc);
        
        if($manifest)
        {
            $json = @file_get_contents($manifest);
            $data = json_decode($json, true);
            if($data == null){
                return;
            }
            
            $details = [];
            $details['url'] = $doc->href;
            $details['title'] = $data['name'];

            $src = '';
            $size = 0;
            foreach($data['icons'] as $icon) {
                //Gets the size of the first dimension of the icon (Assumes icons are square)
                if(!array_key_exists('sizes', $icon)){
                    $src = $icon['src'];
                    break;
                }
                $dim = intval(substr($icon['sizes'], 0, strpos($icon['sizes'], 'x')));
                if($dim > $size){
                    if(substr($icon['src'], 0, 2) == "//"){
                        $src = "http:" . $icon['src'];
                    }
                    else if(strpos($icon['src'], '/') == 0){
                        $src = $doc->href . $icon['src'];
                    }
                    else if (substr($icon['src'], 0, 4) != "http"){
                        $src = $doc->href . '/' . $icon['src'];
                    }
                    else {
                        $src = $icon['src'];
                    }
                    $size = $dim;
                }
            }
            $details['img'] = $src;
            
            $metas = $doc->find('meta');
            $details['description'] = 'No Description';
            foreach($metas as $m){
                if($m->attr('name') == 'description'){
                    $details['description'] = $m->attr('content');
                    break;
                }
            }
            array_push(Spider::$sites, $details);
        }
    }

    /**
     * Checks an hQuery document to see if it is an example of a Progressive Web App
     * 
     * @param  object $doc An hQuery document to search and check for PWA validity
     */
    public static function checkPWA($doc)
    {
        $links = $doc->find('link');
        foreach($links as $link) {
            if($link->attr('rel') == "manifest"){
                return $link->attr('href');
            }
        }
        return false;
    }

    /**
     * Validates that the given URL exists, and that the given UserAgent can access it
     *
     * @param  string $url the URL to validate
     * @param  string $userAgent The name of the UserAgent trying to access the page
     * @param  bool $isFormatted If the URL is formatted to a robots.txt page or not
     * @return bool If the given UserAgent can access the page
     */
    private static function validate(string $url, string $userAgent)
    {
        $parsed = parse_url($url);
        $url = "http://{$parsed['host']}/robots.txt";
        $file_headers = @get_headers($url);
        
        if($file_headers && (in_array('HTTP/1.0 200 OK', $file_headers) || in_array('HTTP/1.1 200 OK', $file_headers))){
            $robots = @file_get_contents($url);
            $parser = new RobotsTxtParser($robots);
            $validator = new RobotsTxtValidator($parser->getRules());
            return $validator->isUrlAllow($url, $userAgent);
        }
        return false;
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