<?php 

/** 
* PHP_Text2Speech Class example 
*/ 

include 'PHP_Text2Speech.class.php'; 

$t2s = new PHP_Text2Speech; 

?> 
<audio controls="controls" autoplay="autoplay"> 
  <source src="<?php echo $t2s->speak('Facebook is a very large social network where you can find a lot of people. You can also find there a community of PHP Classes site users.
If you are a member of Facebook you can join the PHP Classes site users community and follow the site activity updates that are posted on the site page wall.
Please click on the Like button in the PHP Classes site community page at Facebook so we can grow the community of site users at Facebook.'); ?>" type="audio/mp3" /> 
</audio>