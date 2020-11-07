<?php 
namespace PWASpider;
require_once __DIR__ . '/../../vendor/autoload.php';
use Exception;
use duzun\hQuery;
use RobotsTxtParser\RobotsTxtParser;
use RobotsTxtParser\RobotsTxtValidator;

class Spider
{
    public static function start($links, $limit = 1000)
    {
        for($i = 0; sizeof($links) < $limit; $i++)
        {        
            $link = $links[$i];
            $links = $link ? array_merge($links, Spider::scrape($link)) : $links;
            $links = array_unique($links);
        }
        return $links;
    }

    private static function scrape($url)
    {
        echo($url . "\n");
        if ($url == null) { return []; }
        $parsed = parse_url($url);
        $robotsurl = "http://{$parsed['host']}/robots.txt";
        $file_headers = @get_headers($robotsurl);
        if(!$file_headers || $file_headers[0] == 'HTTP/1.1 404 Not Found') {
            return [];
        }
        
        $robots = file_get_contents($robotsurl);
        $parser = new RobotsTxtParser($robots);
        $validator = new RobotsTxtValidator($parser->getRules());
        $userAgent = 'MyPWASpider';

        if ($validator->isUrlAllow($url, $userAgent)) {
            $doc = hQuery::fromFile($url, false);
            if ($doc == null) { return []; }
            try {
                $tags = $doc->find('a');
            } catch (\Exception $e) {
                echo("PANIC");
            }
            $links = [];
            if ($tags == null) { return []; }
            foreach($tags as $key => $value) {
                if(!array_key_exists('scheme' , parse_url($value->attr('href')))) 
                { continue; }
                $url = parse_url($value->attr('href'))['scheme'] . '://' . parse_url($value->attr('href'))['host'];
                array_push($links, $url);
                $links = array_unique($links);
            }
            return $links ?? [];
        }
        return [];
    }

}