<?php
require dirname(__FILE__).'/vendor/autoload.php';
date_default_timezone_set("Asia/Ho_Chi_Minh");
//Call function for index elasticsearch
$today      = date('Y-m-d');
$timeStart  = date('Y-m-d h:m:s');
$file       = dirname(__FILE__).'/logs/'.$today.'/elasticsearch.log';
if (!is_dir(dirname(__FILE__)."/logs/".$today))  mkdir(dirname(__FILE__)."/logs/".$today, 0777, true);
indexElastic();
$timeEnd    = date('Y-m-d h:m:s');
$logs       = fopen($file,"w") or die("Unable to open file!");
fwrite($logs,'Started at:'.$timeStart."\nEnded at:".$timeEnd);
fclose($logs);

/**
 * Index and reindex item info in elasticsearch
 * @return string to file as a log
 */
function indexElastic()
{
    date_default_timezone_set("Asia/Ho_Chi_Minh");
    $today      = date('Y-m-d');
    require dirname(__FILE__).'/config.php';
    $client     = Elasticsearch\ClientBuilder::create()->build();
    $fileLimit  = dirname(__FILE__).'/logs/elastic'.$today.'.log';
    $text       = 0;
    if (file_exists($fileLimit))
    {
       $text    = file_get_contents($fileLimit);
    }
    $limit      = $text?$text:0;
    $query      = $db->query("SELECT * FROM items ORDER BY id ASC LIMIT {$limit}, 1000");
    if ($query ->num_rows > 0) {
        //Write for next time
        $limit  = $limit+1000;
        $logs   = fopen($fileLimit,"w") or die("Unable to open log file!");
        fwrite($logs, $limit);
        fclose($logs);

        while($row = $query->fetch_assoc()) {
            $params['body'][]   = [
                "index"=>[
                    "_index"    => "picinside",
                    "_type"     => "themes",
                    "_id"       => $row['id'],
                ]
            ];
            $params['body'][] = [
                "id"            => $row['id'],
                "craw_id"       => $row['craw_id'],
                "name"          => $row['name'],
                "author"        => $row['author'],
                "author_id"     => $row['author_id'],
                "description"   => html_entity_decode(htmlentities($row['description'], ENT_IGNORE, "UTF-8")),
                "preview"       => $row['preview'],
                "sourceurl"     => $row['sourceurl'],
                "demourl"       => $row['demourl'],
                "documentation" => $row['documentation'],
                "uploaded"      => $row['uploaded'],
                "highsolution"  => $row['highsolution'],
                "widgetready"   => $row['widgetready'],
                "thumbs"        => $row['thumbs'],
                "tags"          => $row['tags'],
                "fonts"         => $row['fonts'],
                "status"        => $row['status'],
                "sales"         => $row["sales"],
                "rate"          => $row["rate"],
                "price"         => $row["price"],
                "update"        => $row["last_update"],
                "created_at"    => $row['created_at'],
                "updated_at"    => $row['updated_at'],
            ];
        }
        $responses  = $client->bulk($params);
        var_dump($responses);
    }
    else
    {
        return false;
    }
}
