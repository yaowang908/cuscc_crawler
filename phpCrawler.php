<?php
ini_set('max_execution_time',3000);//5 mins execution time
include_once "simple_html_dom.php";
echo "<html><head><title>PHP Crawler</title></head><body>";
echo "<p>PHP Crawler</p>";
    
   /* $start_post_id='14731';
    $end_post_id='14732';
    crawl_cnusa($start_post_id,$end_post_id);
    *///crawled all posts
//crawl_cnusa('14731','14732');
/*$i = 6915;
while($i<14731){
    if(search_db_by_post_id($i)){
        $search_result = search_db_by_post_id($i);
        $search_result_post_body = file_get_contents($search_result['post_body_url']);
        if(is_null($search_result_post_body)){
            echo $i." post has more divs";
            $j = $i+1;
            crawl_cnusa($i,$j);
        }
         echo $i." exists but more ps";
    }
    $i++;   
}
*/ //some posts have more div than p elemets



$search_result = search_db_by_post_id("14730");
echo "ID is ".$search_result['id']."<br>";
echo "Title is ".$search_result['post_title']."<br>"; 
echo "Published on ".$search_result['post_month']."/".$search_result['post_day']."/".$search_result['post_year']."<br>";
echo "Below is the post body:<br><br>";
$search_result_post_body = file_get_contents($search_result['post_body_url']);

$search_result_post_body=preg_replace("/(\\\\n)|(\n)/","<br>",$search_result_post_body);

$img_temp = array('0'=>$search_result['post_img1_url'],'1'=>$search_result['post_img2_url'],'2'=>$search_result['post_img3_url'],'3'=>$search_result['post_img4_url'],'4'=>$search_result['post_img5_url'],'5'=>$search_result['post_img6_url'],'6'=>$search_result['post_img7_url'],'7'=>$search_result['post_img8_url']);
$img_toinsert = array();
foreach($img_temp as $key => $value){
    if($value!==''){
        $img_toinsert[]="<img src='".$value."'/>";
    }
}
//$search_result_post_body = preg_replace("/\/vendors\/cnusa.org\/upload\/.*?.(jpg)|(png)/",
                                                        //array_shift($img_toinsert),$search_result_post_body);

$search_result_post_body =preg_replace_callback('/\/vendors\/cnusa.org\/upload\/.*?.(jpg)|(png)/', function($matches) use (&$img_toinsert) {
    return array_shift($img_toinsert);//the &$img_toinsert make the later array_shift() affect the $matches variable, make the magic happen
}, $search_result_post_body);
echo $search_result_post_body;

//var_dump(regex_match("/\/vendors\/cnusa.org\/upload\/.*?$/",$search_result_post_body));



echo "</body></html>";
//end HTML
function crawl_cnusa($start_post_id,$end_post_id){
    $url='http://cnusa.org/readnews.aspx?newsid=';
    //$post_id = '1000';
    //$url = $url.$post_id;
    $host = 'http://cnusa.org';
/*checked #
*1-6990                 15
*6990-13500      339
*13500-14300    49
*14300-14500    23
*14500-14730    212
*/
    $start_id=$start_post_id;
    //$end_id='14730';
    $end_id=$end_post_id;
    $crawled_num=0;
    
    ob_implicit_flush(true);
    ob_start();
    echo "Start executing!";
    ob_flush();//display it simultaneously
while($start_id<$end_id){
    $url = 'http://cnusa.org/readnews.aspx?newsid='.$start_id;
    $post_id = $start_id;
    
        $raw_result = curl_get($url);//get html file
        if($raw_result[0]!==200){//detect if post exist
            //echo "already dead".$post_id;
            //die("Unexpected HTTP code: ".$raw_result[0]."\n");//post doesn't exist
            echo "<span>skip post id ".$post_id."  </span>";
            ob_flush();
            $start_id++;
            continue;
        }
        $result = $raw_result[1];//post exist
       try{ 
           manipulate_post($url,$host,$post_id,$result); 
          }catch(Exception $e){
        echo 'Caught exception: ',  $e->getMessage(), "\n";
        die();
         }
        echo "<span style='color:#00FF00'>crawled ".$post_id."  </span>";
        ob_flush();
        $start_id++;
        $crawled_num++;
        //sleep(1);    
    }
    echo "done!";
    echo "<span style='color:#00FF00'> ".$crawled_num." posts crawled!</span>";
    ob_end_flush();
}

function manipulate_post($url,$host,$post_id,$result){
    
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
//start init variables
    $post_date_day = $html->find('.date .day',0);
    $post_date_day = trim($post_date_day->plaintext);//get post day
    $post_date_month = $html->find('.date .month',0);
    $post_date_month = trim($post_date_month->plaintext);//get post month
    $post_date_year = $html->find('.date .year',0);
    $post_date_year = trim($post_date_year->plaintext);//get post year

    $post_body = $html->find('.date',0)->parent()->next_sibling();
    //$post_body_plaintext = $post_body->plaintext;
    
    //some post use div block instead of p block
    $div_amount= count($post_body->find('div'));
    $p_amount= count($post_body->find('p'));
    if($div_amount > $p_amount){
        $post_body_segments = $post_body->find('div');
    }else{
        $post_body_segments = $post_body->find('p');//multiple result
    }
        
    
    //display current manipulating post
    //echo "post id is: ".$post_id."<br>";
    //echo "post title is: ".$post_title."<br>";
    //echo "post was published on Year: ".$post_date_year." Month: ".$post_date_month." Day: ".$post_date_day."<br>";
    
    $post_imgs = array(0=>'',1=>'',2=>'',3=>'',4=>'',5=>'',6=>'',7=>'',8=>'');//post imgs local url
    $num = 1;//count the p's order
    $num_for_pic = 1;//count the img's order
    $whole_content =$post_title.'\n\n' ;
    foreach ($post_body_segments as $item){
        
        if(has_pics($item)){
            
            $pic_url = $item->find("img",0)->getAttribute("src");
            $whole_content .=$pic_url."\n\n";
            //echo "#".$num." has a pic as ".$pic_url."<br>";//display post's image local url
            $image_dir="images/".$post_date_year."/".$post_date_month."/".$post_date_day."/".$post_id."/".$num.".jpg";
            $image_url=$host.$pic_url;
            image_save($image_url,$image_dir);
            $post_imgs[$num_for_pic] = $image_dir;
            $num_for_pic++;
        }else{
            $item_plaintext = $item->plaintext;
            //echo "#".$num." is:".$item_plaintext."<br>"; //display post body segement
            $whole_content .=$item_plaintext."\n\n";
        }
        $num++;
    }
    $file_dir = $post_date_year."/".$post_date_month."/".$post_date_day."/".$post_id.".txt";
    file_save($file_dir,$whole_content);
   
    create_database_sqlite();//init database
    
    $item=array();
    $item["post_id"] = $post_id;
    $item["post_title"] = remove_CN_punctuation($post_title);
    $item["post_year"]=$post_date_year;
    $item["post_month"]=$post_date_month;
    $item["post_day"]=$post_date_day;
    $item["post_body"]=$file_dir;
    $item["post_img1_url"]=$post_imgs[1];
    $item["post_img2_url"]=$post_imgs[2];
    $item["post_img3_url"]=$post_imgs[3];
    $item["post_img4_url"]=$post_imgs[4];
    $item["post_img5_url"]=$post_imgs[5];
    $item["post_img6_url"]=$post_imgs[6];
    $item["post_img7_url"]=$post_imgs[7];
    $item["post_img8_url"]=$post_imgs[8];  

    add_to_db($item);//insert into database, if $post_id duplicate, skip it
}

function remove_CN_punctuation($keyword){
    $keyword = urlencode($keyword);
    $keyword=preg_replace("/(%7E|%60|%21|%40|%23|%24|%25|%5E|%26|%27|%2A|%28|%29|%2B|%7C|%5C|%3D|\-|_|%5B|%5D|%7D|%7B|%3B|%22|%3A|%3F|%3E|%3C|%2C|\.|%2F|%A3%BF|%A1%B7|%A1%B6|%A1%A2|%A1%A3|%A3%AC|%7D|%A1%B0|%A3%BA|%A3%BB|%A1%AE|%A1%AF|%A1%B1|%A3%FC|%A3%BD|%A1%AA|%A3%A9|%A3%A8|%A1%AD|%A3%A4|%A1%A4|%A3%A1|%E3%80%82|%EF%BC%81|%EF%BC%8C|%EF%BC%9B|%EF%BC%9F|%EF%BC%9A|%E3%80%81|%E2%80%A6%E2%80%A6|%E2%80%9D|%E2%80%9C|%E2%80%98|%E2%80%99)+/",' ',$keyword);
    $keyword=urldecode($keyword);
    return $keyword;
}

function file_save($dir,$contents){
    $parts = explode('/',$dir);
    $file = array_pop($parts);
    $dir = ".";
    foreach($parts as $part){
        if(!is_dir($dir.="/".$part))
            mkdir($dir) or die('failed to create folder ');
    }
    file_put_contents($dir."/".$file,$contents) or die("Error when write the file");
}

function image_save($url,$dir){
    $parts = explode('/',$dir);
    $file = array_pop($parts);
    $dir = ".";
    foreach($parts as $part){
        if(!is_dir($dir.="/".$part))
            mkdir($dir) or die('failed to create folder ');
    }
    $dir .= "/".$file;
    $ch = curl_init($url);
    $fp = fopen($dir,'wb') or die('failed to fopen stream'.$dir.'  '.$url);
    curl_setopt($ch,CURLOPT_FILE,$fp);
    curl_setopt($ch,CURLOPT_HEADER,0);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
}

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
        id INTEGER PRIMARY KEY,
        post_id INT(10) UNIQUE NOT NULL,
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
function add_to_db($item=array("post_id"=>"","post_title"=>"", "post_year"=>"2005","post_month"=>"1","post_day"=>"1","post_body_url"=>"","post_img1_url"=>"","post_img2_url"=>"","post_img3_url"=>"","post_img4_url"=>"","post_img5_url"=>"","post_img6_url"=>"","post_img7_url"=>"","post_img8_url"=>"")){
    $db = new SQLite3('cuscc') or die('Unable to open database');
    $query = 'INSERT OR IGNORE INTO posts(post_id,post_title,post_year,post_month,post_day,post_body_url,post_img1_url,post_img2_url,post_img3_url,post_img4_url,post_img5_url,post_img6_url,post_img7_url,post_img8_url)
    VALUES(
        "'.$item["post_id"].'",
        "'.$item["post_title"].'",
        "'.$item["post_year"].'",
        "'.$item["post_month"].'",
        "'.$item["post_day"].'",
        "'.$item["post_body"].'",
        "'.$item["post_img1_url"].'",
        "'.$item["post_img2_url"].'",
        "'.$item["post_img3_url"].'",
       "'.$item["post_img4_url"].'",
        "'.$item["post_img5_url"].'",
        "'.$item["post_img6_url"].'",
        "'.$item["post_img7_url"].'",
        "'.$item["post_img8_url"].'"
    )';
    $db->query($query) or die('add data failed'.$query);
    
}

function search_db_by_post_id($q){//$q==post_id
    $db = new SQLite3('cuscc') or die('Unable to open database');
    $query = "SELECT * FROM posts WHERE post_id = '".$q."'";
    $result = $db->query($query) or die('failed to search data');
    return $result->fetchArray();
}

?>