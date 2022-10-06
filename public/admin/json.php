<?php 

/* Include the Composer autoloader. */
require_once ('../../vendor/autoload.php');

$ar = array('a','b','c');
echo array_search('c',$ar);



$xml = '<xml><wase><getblock><blockid>10</blockid><blockid>11</blockid></getblock><getblock><blockid>12</blockid><blockid>13</blockid></getblock></wase>' . 
        '<wase><getblock><blockid>14</blockid><blockid>15</blockid></getblock><getblock><blockid>16</blockid><blockid>17</blockid></getblock></wase></xml>';


$ob = simplexml_load_string($xml); 


$json = json_encode($ob);

echo '<html><header></header><body>';

echo 'xml = &lt;xml&gt;&lt;wase&gt;&lt;getblock&gt;&lt;blockid&gt;10&lt;/blockid&gt;&lt;blockid&gt;11&lt;/blockid&gt;&lt;/getblock&gt;&lt;getblock&gt;&lt;blockid&gt;12&lt;/blockid&gt;&lt;blockid&gt;13&lt;/blockid&gt;&lt;/getblock&gt;&lt;/wase&gt;' . 
        '&lt;wase&gt;&lt;getblock&gt;&lt;blockid&gt;14&lt;/blockid&gt;&lt;blockid&gt;15&lt;/blockid&gt;&lt;/getblock&gt;&lt;getblock&gt;&lt;blockid&gt;16&lt;/blockid&gt;&lt;blockid&gt;17&lt;/blockid&gt;&lt;/getblock&gt;&lt;/wase&gt;&lt;/xml&gt; <br />' ; 

echo '<br />json = ' . $json; 

$array = json_decode($json);

echo '<br /><br />' . 'object = ' . print_r($array, true);
echo '</body></html>';

?>