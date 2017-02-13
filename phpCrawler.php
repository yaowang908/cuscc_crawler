<?php
    include_once "simple_html_dom.php";
    echo "<html><head><title>PHP Crawler</title></head><body>";
    echo "<p>PHP Crawler</p>";
    
    $url='http://cnusa.org/readnews.aspx?newsid=';
    $post_id = '14730';
    $url = $url.$post_id;

    $raw_result = curl_get($url);
    if($raw_result[0]!==200){
        die("Unexpected HTTP code: ".$raw_result[0]."\n");
        echo "already dead";
    }
    $result = $raw_result[1];
    //$result = preg_replace("/\s/","",$result);//get rid of all kinds of whiteSpace
  
/** parse HTML -- regex solution 
    $pattern = '/<span class="rank">(.*?)<\/span>.*?<div class="score unvoted" [^>]*>(.*?)<\/div>.*?<p class="title"><a[^>]*href="(.*?)"[^>]*>(.*?)<\/a>/';
    
    $matches = regex_match($pattern,$result);
    */
    /** output regex result  
    foreach($matches as $item){
        echo "new item begin<br><br>".$item[1]."<br>".$item[2]."<br>".$item[3]."<br>".$item[4]."<br>item end here<br><br>";
    }
    */


//parse html -- simple php dom solution
    $html = str_get_html($result);//create dom object
    $post_title = $html->find('h3[class=entry-title]',0);
    $post_title = $post_title->plaintext;//get post title


    $post_date_day = $html->find('.date .day',0);
    $post_date_day = $post_date_day->plaintext;//get post day
    $post_date_month = $html->find('.date .month',0);
    $post_date_month = $post_date_month->plaintext;//get post month
    $post_date_year = $html->find('.date .year',0);
    $post_date_year = $post_date_year->plaintext;//get post year

    $post_body = $html->find('.date',0)->parent()->next_sibling();
    //$post_body_plaintext = $post_body->plaintext;
    
    $post_body_segments = $post_body->find('p');//multiple result
    
/*
save tile,data,body imgs
body to folder posts
imgs to imgs 
name folder as dates (if exist add # at the end)
replace the images in postbody with the address
*/

    echo "post id is: ".$post_id."<br>";
    echo "post title is: ".$post_title."<br>";
    echo "post was published on Year: ".$post_date_year." Month: ".$post_date_month." Day: ".$post_date_day."<br>";
    //echo "post body(plaintext)"."<br><br>";
    //echo $post_body_plaintext;
    
    $num = 1;
    foreach ($post_body_segments as $item){
        if(has_pics($item)){
            //echo "#".$num." is:"."there is a pic "."<br>";
            $pic_url = $item->find("img",0)->getAttribute("src");
            echo "#".$num." has a pic as ".$pic_url."<br>";
        }else{
            echo "#".$num." is:".$item->plaintext."<br>";
        }
        $num++;
    }
    

    echo "</body></html>";
//end HTML


function has_pics($item){
    if($item->find("img",0) === NULL){
        return 0;
    }else{
        return 1;
    }
}

/** 
* Send a GET requst using cURL 
* @param string $url to request 
* @param array $get values to send 
* @param array $options for cURL 
* @return string 
*/ 
function curl_get( $url, array $get = array(), array $options = array() ){
    $defaults = array (
        CURLOPT_URL => $url.(strpos($url,'?')===FALSE ? '?':'').http_build_query($get),
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'], // set browser/user agent
        //CURLOPT_HEADERFUNCTION => 'read_header',//get header
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => 0, // set 1 to follow redirect
        CURLOPT_TIMEOUT => 0 //never time out
    );
    
    $ch = curl_init();
    curl_setopt_array($ch,($options + $defaults));
    if(!$result=curl_exec($ch)){
        trigger_error(curl_error($ch));
    }
    $http_code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
   
    curl_close($ch);//close cURL resource,and free up system resources
    return array($http_code,$result);
}

/** 
* Send a POST requst using cURL 
* @param string $url to request 
* @param array $post values to send 
* @param array $options for cURL 
* @return string 
*/ 
function curl_post($url, array $post = NULL, array $options = array()) 
{ 
    $defaults = array( 
        CURLOPT_POST => 1, 
        CURLOPT_HEADER => 0, 
        CURLOPT_URL => $url, 
        CURLOPT_FRESH_CONNECT => 1, 
        CURLOPT_RETURNTRANSFER => 1, 
        CURLOPT_FORBID_REUSE => 1, 
        CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'], // set browser/user agent
        //CURLOPT_HEADERFUNCTION => 'read_header',//get header
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 4, 
        CURLOPT_POSTFIELDS => http_build_query($post) 
    ); 

    $ch = curl_init(); 
    curl_setopt_array($ch, ($options + $defaults)); 
    if( ! $result = curl_exec($ch)) 
    { 
        trigger_error(curl_error($ch)); 
    } 
    curl_close($ch); 
    return $result; 
} 

function regex_match($pattern,$target){
    if(preg_match_all($pattern,$target,$matches,PREG_SET_ORDER)){
        debug_to_console(count($matches)."matches found<br>");
        return $matches;
    }else{
        debug_to_console("match NOT found");
    }
}
function debug_to_console($data){
    //debug to console function
    if(is_array($data))
         $output = "<script>console.log( 'Debug Objects: " . implode( ',', $data) . "' );</script>";
    else
        $output = "<script>console.log( 'Debug Objects: " . $data . "' );</script>";

    echo $output;
}

function create_database_sqlite(){
    $db = new SQLite3('cuscc') or die('Unable to open database');
    $query ='CREATE TABLE IF NOT EXISTS posts(
        post_id INTEGER PRIMARY KEY,
        post_title CHAR(100) NOT NULL,
        post_year  INT(4),
        post_month INT(2),
        post_day INT(2),
        post_body_url CHAR(100),
        post_img1_url CHAR(100),
        post_img2_url CHAR(100),
        post_img3_url CHAR(100),
        post_img4_url CHAR(100),
        post_img5_url CHAR(100),
        post_img6_url CHAR(100),
        post_img7_url CHAR(100),
        post_img8_url CHAR(100)
    )';
    $db->exec($query) or die('Create db failed');
}
function add_to_db($item=array(post_title=>"",
                               post_year=>2005,
                               post_month=>1,
                               post_day=>1,
                               post_body="",
                               post_img1_url=>"",
                               post_img2_url=>"",
                               post_img3_url=>"",
                               post_img4_url=>"",
                               post_img5_url=>"",
                               post_img6_url=>"",
                               post_img7_url=>"",
                               post_img8_url=>"")){
    $db = new SQLite3('cuscc') or die('Unable to open database');
    $query = 'INSERT INTO posts(post_title,post_year,post_month,post_day,post_body,post_img1_url,post_img2_url,post_img3_url,post_img4_url,post_img5_url,post_img6_url,post_img7_url,post_img8_url)
    VALUES(
        '$item[post_title]',
        '$item[post_year]',
        '$item[post_month]',
        '$item[post_day]',
        '$item[post_body]',
        '$item[post_img1_url]',
        '$item[post_img2_url]',
        '$item[post_img3_url]',
        '$item[post_img4_url]',
        '$item[post_img5_url]',
        '$item[post_img6_url]',
        '$item[post_img7_url]',
        '$item[post_img8_url]'
    )';
    $db->query($query) or die('add data failed');
    
}

?>