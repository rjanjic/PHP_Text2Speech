<?php
/******************************************************************
Projectname:   PHP Text 2 Speech Class
Version:       1.0
Author:        Radovan Janjic <rade@it-radionica.com>
Last modified: 11 06 2013
Copyright (C): 2012 IT-radionica.com, All Rights Reserved

* GNU General Public License (Version 2, June 1991)
*
* This program is free software; you can redistribute
* it and/or modify it under the terms of the GNU
* General Public License as published by the Free
* Software Foundation; either version 2 of the License,
* or (at your option) any later version.
*
* This program is distributed in the hope that it will
* be useful, but WITHOUT ANY WARRANTY; without even the
* implied warranty of MERCHANTABILITY or FITNESS FOR A
* PARTICULAR PURPOSE. See the GNU General Public License
* for more details.

Description:

PHP Text 2 Speech Class

This class converts text to speech using Google text to 
speech API to transform text to mp3 file which will be 
downloaded and later used as eg. embed file. 

Example:

******************************************************************
<?php 
$t2s = new PHP_Text2Speech;
?>

// Simple example
<audio controls="controls" autoplay="autoplay">
  <source src="<?php echo $t2s->speak('If you hear this sount it means that you are using PHP text to speech class.'); ?>" type="audio/mp3" />
</audio>

// Example use of other language
<audio controls="controls" autoplay="autoplay">
  <source src="<?php echo $t2s->speak('Wie geht es Ihnen', 'de'); ?>" type="audio/mp3" />
</audio>

******************************************************************/

class PHP_Text2Speech {
	
	/** Max text characters
	 * @var	Integer 
	 */
	var $maxStrLen = 100;
	
	/** Text len
	 * @var	Integer 
	 */
	var $textLen = 0;
	
	/** No of words
	 * @var	Integer 
	 */
	var $wordCount = 0;
	
	/** Language of text (ISO 639-1)
	 * @var	String 
	 * @link https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
	 */
	var $lang = 'en';
	
	/** Text to speak
	 * @var	String 
	 */
	var $text = NULL;
	
	/** File name format
	 * @var	String 
	 */
	var $mp3File = "%s.mp3";
	
	/** Directory to store audio file
	 * @var	String 
	 */
	var $audioDir = "audio/";
	
	/** Contents
	* @var	String
	*/
	var $contents = NULL;
	
	/** Function make request to Google translate, download file and returns audio file path
	 * @param 	String 	$text		- Text to speak
	 * @param 	String 	$lang 		- Language of text (ISO 639-1)
	 * @return 	String 	- mp3 file path
	 * @link https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
	 */
	function speak($text, $lang = NULL) {
		
		if ($lang !== NULL) {
			$this->lang = $lang;
		}
		
		// Create dir if not exists
		if (!is_dir($this->audioDir)) {
			mkdir($this->audioDir, 0755) or die('Could not create audio dir: ' . $this->audioDir);
		}
		
		// Try to set writing permissions for audio dir.
		if (!is_writable($this->audioDir)) { 
			chmod($this->audioDir, 0755) or die('Could not set appropriate permissions for audio dir: ' . $this->audioDir);
		}
		
		// Can not handle more than 100 characters so split text
		if (strlen($text) > $this->maxStrLen) {
			$this->text = $text;
			
			// Generate unique mp3 file name
			$file = sprintf($this->mp3File, $this->audioDir . md5($this->text));
			if (!file_exists($file)) {
				$texts = array();
				$words = explode(' ', $this->text);
				$i = 0;
				$texts[$i] = NULL;
				foreach ($words as $w) {
					$w = trim($w);
					if (strlen($texts[$i] . ' ' . $w) < $this->maxStrLen) {
						$texts[$i] = $texts[$i] . ' ' . $w; 
					} else {
						$texts[++$i] = $w;
					}
				}
				
				// Get get separated files contents and marge them into one
				foreach ($texts as $txt) {
					$pFile = $this->speak($txt, $this->lang);
					$this->contents .= $this->stripTags(file_get_contents($pFile));
					unlink($pFile);
				}
				unset($words, $texts);
				
				// Save file
				file_put_contents($file, $this->contents);
				$this->contents = NULL;
			}
		} else {
			
			// Generate unique mp3 file name
			$file = sprintf($this->mp3File, $this->audioDir . md5($text));
			
			if (!file_exists($file)) {
				// Text lenght
				$this->textLen = strlen($text);
				
				// Words count
				$this->wordCount = str_word_count($text);
				
				// Encode string
				$text = urlencode($text);

				// Download new file
				$this->download("http://translate.google.com/translate_tts?ie=UTF-8&q={$text}&tl={$this->lang}&total={$this->wordCount}&idx=0&textlen={$this->textLen}", $file);
			}
		}
		
		// Returns mp3 file path
		return $file;
	}
	
	/** Function to find the beginning of the mp3 file
	 * @param 	String 	$contents		- File contents
	 * @return 	Integer
	 */ 
    function getStart($contents) {
        for($i=0; $i < strlen($contents); $i++){
            if(ord(substr($contents, $i, 1)) == 255){
                return $i;
            }
        }
    }
	
	/** Function to find the end of the mp3 file
	 * @param 	String 	$contents		- File contents
	 * @return 	Integer
	 */ 
    function getEnd($contents) {
        $c = substr($contents, (strlen($contents) - 128));
        if(strtoupper(substr($c, 0, 3)) == 'TAG'){
            return $c;
        }else{
            return FALSE;
        }
    }

	/** Function to remove the ID3 tags from mp3 files
	 * @param 	String 	$contents		- File contents
	 * @return 	String
	 */ 
    function stripTags($contents) {
        // Remove start
        $start = $this->getStart($contents);
        if ($start === FALSE) {
            return FALSE;
        } else {
            return substr($contents, $start);
        }
        // Remove end tag
        if ($this->getEnd($contents) !== FALSE){
            return substr($contents, 0, (strlen($contents) - 129));
        }
    }
	
	/** Function to download and save file
	 * @param 	String 	$url		- URL
	 * @param 	String 	$path 		- Local path
	 */ 
    function download($url, $path) { 
        // Is curl installed?
        if (!function_exists('curl_init')){ // use file get contents 
            $output = file_get_contents($url); 
        }else{ // use curl 
            $ch = curl_init(); 
            curl_setopt($ch, CURLOPT_URL, $url); 
            curl_setopt($ch, CURLOPT_AUTOREFERER, true); 
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; rv:1.7.3) Gecko/20041001 Firefox/0.10.1"); 
            curl_setopt($ch, CURLOPT_HEADER, 0); 
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
            $output = curl_exec($ch); 
            curl_close($ch); 
        }
		// Save file
		file_put_contents($path, $output);
    }
}