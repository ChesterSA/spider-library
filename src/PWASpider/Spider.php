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
        // var_dump(Spider::scrape("http://account.f4wonline.com/"));
        // die();
        for($i = 0; $i > sizeof($links) || sizeof($links) < $limit; $i++)
        {        
            $initial_size = sizeof($links);
            if( array_key_exists($i, $links)) {
                $link = $links[$i];
            }
            else if ($i < sizeof($links)){
                continue;
            }
            $var = Spider::scrape($link);
            $links = $link ? array_merge($links, $var) : $links;
            $links = array_unique($links);

            //Check if any new links have been added
            // if (sizeof($links) == $initial_size) { break; }

            if ($i >= sizeof($links)) { break; }
        }
        return array_slice($links, 0, $limit);
    }

    private static function scrape($url)
    {
        echo($url . "\n");
        try {
            $validated = Spider::validate($url);
    
            if ($validated) {
                $doc = hQuery::fromFile($url, false);
                if ($doc == null) { throw new Exception('Web Page was empty'); }
                try {
                    $tags = $doc->find('a');
                } catch (Exception $e) {
                    throw new Exception('Error finding <a> tags in page');
                }
                $links = [];

                if ($tags == null) { throw new Exception('No Links in Page'); }

                foreach($tags as $key => $value) {
                    if(!array_key_exists('scheme' , parse_url($value->attr('href')))) { continue; }
                    $url = parse_url($value->attr('href'))['scheme'] . '://' . parse_url($value->attr('href'))['host'];
                    array_push($links, $url);
                    $links = array_unique($links);
                }
                return $links;
            }
            else { return []; }
        }
        catch (Exception $e){
            echo ("---\n" . $url . "\n" . $e->getMessage() . "\n---\n");
            return [];
        }
        return [];
    }

    private static function validate($url, $isFormatted = false)
    {
        // echo "---\n";
        // echo "1: " . $url . "\n";
        if ($url == null) { return false; }
        // echo "2: " . $url . "\n";
        if(!$isFormatted){
            $parsed = parse_url($url);
            $url = "http://{$parsed['host']}/robots.txt";
            // echo "5: " . $url . "\n";

        }
        $file_headers = @get_headers($url);
        // echo $file_headers[0] . "\n";
        // echo "3: " . $url . "\n";
        if($file_headers && in_array('HTTP/1.1 200 OK', $file_headers)){
            // echo "4: " . $url . "\n";

            
            // echo "6: " . $url . "\n";
            
            $robots = file_get_contents($url);
            $parser = new RobotsTxtParser($robots);
            $validator = new RobotsTxtValidator($parser->getRules());
            $userAgent = 'MyPWASpider';
            // echo "7: " . $url . "\n";
            // echo "---\n\n";
            return $validator->isUrlAllow($url, $userAgent);
        }
        else {
            return false;
        }
    }
}