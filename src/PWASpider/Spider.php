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
        }
        return $links;
    }

    private static function scrape($url)
    {
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
        $userAgent = 'MyPWABot';

        if ($validator->isUrlAllow($url, $userAgent)) {
            $doc = hQuery::fromFile($url, false);
            try {
                $tags = $doc->find('a');
            } catch (\Exception $e) {
                echo("PANIC");
            }
            $links = [];
            foreach($tags as $key => $value) {
                array_push($links, $value->attr('href'));
            }
            return $links ?? [];
        }
        return [];
    }
    
}