<!DOCTYPE html>
<html>
    <head>        
         <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    </head>
    <body>
<?php

define('PUBLICPATH', __DIR__ . '/');


if (!function_exists('codepoint_encode')) {

    function codepoint_encode($str) {
        return substr(json_encode($str), 1, -1);
    }

}

if (!function_exists('codepoint_decode')) {

    function codepoint_decode($str) {
        return json_decode(sprintf('"%s"', $str));
    }

}

if (!function_exists('mb_internal_encoding')) {

    function mb_internal_encoding($encoding = NULL) {
        return ($from_encoding === NULL) ? iconv_get_encoding() : iconv_set_encoding($encoding);
    }

}

if (!function_exists('mb_convert_encoding')) {

    function mb_convert_encoding($str, $to_encoding, $from_encoding = NULL) {
        return iconv(($from_encoding === NULL) ? mb_internal_encoding() : $from_encoding, $to_encoding, $str);
    }

}

if (!function_exists('mb_chr')) {

    function mb_chr($ord, $encoding = 'UTF-8') {
        if ($encoding === 'UCS-4BE') {
            return pack("N", $ord);
        } else {
            return mb_convert_encoding(mb_chr($ord, 'UCS-4BE'), $encoding, 'UCS-4BE');
        }
    }

}

if (!function_exists('mb_ord')) {

    function mb_ord($char, $encoding = 'UTF-8') {
        if ($encoding === 'UCS-4BE') {
            list(, $ord) = (strlen($char) === 4) ? @unpack('N', $char) : @unpack('n', $char);
            return $ord;
        } else {
            return mb_ord(mb_convert_encoding($char, 'UCS-4BE', $encoding), 'UCS-4BE');
        }
    }

}

if (!function_exists('mb_htmlentities')) {

    function mb_htmlentities($string, $hex = true, $encoding = 'UTF-8') {
        return preg_replace_callback('/[\x{80}-\x{10FFFF}]/u', function ($match) use ($hex) {
            return sprintf($hex ? '&#x%X;' : '&#%d;', mb_ord($match[0]));
        }, $string);
    }

}

if (!function_exists('mb_html_entity_decode')) {

    function mb_html_entity_decode($string, $flags = null, $encoding = 'UTF-8') {
        return html_entity_decode($string, ($flags === NULL) ? ENT_COMPAT | ENT_HTML401 : $flags, $encoding);
    }

}


$data=  array();

$fcontent  = file_get_contents(PUBLICPATH.'handwriting-zh_TW.xml');
$xml = new SimpleXMLElement($fcontent);

$dict = $xml->xpath('/character-collection/set/character');

$i=0;

while(list( , $char) = each($dict)) {
   $strokeCount =   count($char->strokes->stroke);
   $charCode    =   $char->utf8;
   
   $strokes_array  =   array();
   $directions  =   array();
   $strokes =   $char->strokes->stroke;
  /*/* process stroke diection ( 4 basic direction first: 
   *    left to right : 0/1, 
   *    horizontal/ vertical : 2/3
   */
         
  // process strokes data
   foreach ($strokes as $stroke){
       $stroke_points   =   $stroke->point;
       $stroke_points_length    =  count($stroke_points); 
       $strokes_array[]   = $stroke_points_length;
       
        // get the first point and the last point of the current stroke
        $firstPoint  =   $stroke_points[0];
        $lastPoint   =   $stroke_points[$stroke_points_length-1];
        
    //    var_dump($firstPoint);
        
        $x1 =   (int)$firstPoint['x']; $y1  =   $firstPoint['y'];
        $x2 =   (int)$lastPoint['x']; $y2  =   $lastPoint['y'];        
        //right -> left / left -> right
        $directions[]['d1']    =   ($x1 >= $x2) ? 0 : 1;
        
        // horizontal/ vertical
        
        $directions[]['d2']    =   ($y1 >= $y2) ? 0 : 1;        
        
   }
   
   $data[$strokeCount][]  =   array('code' => mb_ord($charCode[0]),
                      'strokeCount' => $strokeCount,
                      'strokeOrder' => $strokes_array,
                      'directions' => $directions
                        );
   
   //if ($i>10)  break;
   $i++;
   
//   echo '['.$charCode[0].']';
}


saveStrokeData($data,'tegaki/traditional');




function writeTo($data,$to_file) {
    $obj = json_encode($data);
    $fp = fopen(PUBLICPATH . $to_file, 'w');
    fwrite($fp, $obj);
    fclose($fp);
}

function saveStrokeData($data,$folder) {
    foreach ($data as $strokeCount => $chars) {
        $obj = json_encode($chars);
        $fp = fopen(PUBLICPATH . $folder.'/'.$strokeCount.'.json', 'w');
        fwrite($fp, $obj);
        fclose($fp);
    }
}

// create JSON format in separate stroke count files

//var_dump($data);

echo 'Char numbers:'. count($dict);

?>

  </body>
</html>
