<?php
/**
 * This library has super features useful for sites and minor link 
 * modifications
 * @usedby tinymvc_library_uri
 */
class TinyMVC_Library_SuperURI {
  private $opts = array();
  private $GET = null;
  
  /**
  * Quickly access the GET array
  * 
  * @param string $key
  * 
  * @return array|mixed full $_GET array or $_GET[$key] value or null if not exists
  */
  public function GET($key = null){
       if (is_null($key)){
          return $this->GET;
       }else if (isset($this->GET[$key])){
          return $this->GET[$key];
       }else{
           //key not found
           return null;
       }
  }
  
  private $REQ_URI = null;
  public function REQUEST_URI(){
      return $this->REQ_URI;
  }
  private $pathtocontroller = null;
  
  
  /**
   * This is dedicated to store the tinymvc uri library which is used
   */
  public $uri = null;
  
  public function requesturi(){ return $this->REQ_URI; }
  public function lastopts(){ return $this->opts; }
  
  private $keyvals = null;
  private $args = null;
  
  public function __construct()
  {
    //get this class ready for parsing
    $this->GET = $_GET;
    $this->REQ_URI = $_SERVER['REQUEST_URI'];
    
  }
  
  public function init($uri_library = null){
     if (empty($uri_library)){ return; }
     $this->uri = $uri_library;
     $this->keyvals = $this->uri->uri_to_assoc(2);
     $this->args = $this->uri->uri_to_array(0);
  }
  
  /**
   * Get the current page url, but potentially make minor modifications
   *  ie. Use 'set'=>array('foo'=>null) to unset the /foo/bar/ elements
   *  and use 'set'=>array(0=>null) to unset the very first item, (controller)
   * @param array options for how to modify the string
   */
  public function curpage($opts = null, $type = '', $moreopts = ''){
      if (empty($type)){ $type = 'GET'; }
      if (empty($opts)){ $opts = array(); }
      else if (is_string($opts)){
            //allow short-hand of using query-string style (ie. foo=bar)
            parse_str($opts, $optsa);
            $opts = array(strtoupper($type)=>array('set'=>$optsa));
      }else if (!is_array($opts)){
        $opts = (array)$opts;
      }
      
      
      
      if (strpos($moreopts, 'unsetmethod:empty') !== false){
         $unsetmethod = 'empty';
      } else {
         $unsetmethod = 'null';
      }
      
      $defopts = array('GET'=> array('set'=>array(),'clearfirst'=>false, 'unsetmethod'=>$unsetmethod),
                  'SEGMENTS'=> array('set'=>array()),
                 );
      //if (!is_array($opts)){ $opts = (array)$opts; }
      $opts = array_merge_recursive($defopts, $opts);
      $this->opts = $opts;   
      
      list($segment_str, $segtype, $newkeyvals, $newargs) = $this->setsegs($opts['SEGMENTS']['set']);
      
      $pageURL = $this->curpageroot().$this->pathtocontroller().$segment_str;
       ///var_dump($unsetmethod);
      $gs = $this->setget($this->GET, $opts['GET']['set'], $opts['GET']['clearfirst'], $unsetmethod);
      return $pageURL.$gs;
  }
  
  
  public function setsegs($set){
      //user can either set keyvalue pairs 
      $keyvals = $this->keyvals;
      
      //OR he can set specific arguments directly
      $args = $this->args;

      $segtype = null;

      foreach($set as $key => $val){
             if (is_int($key) || ctype_digit($key)){
                $segtype = 'args'; 

                if (is_null($val)){
                   unset($args[$key]);
                }else{
                   $args[$key] = urlencode($val);
                }
                
             }else{
                $segtype = 'keyvals';
                if (is_null($val)){
                   unset($keyvals[$key]);
                }else{
                   $keyvals[$key] = urlencode($val);
                }
             }
      }
      
      $strargs = '';
      
     
      if (empty($segtype) || $segtype == 'args'){
         $strargs = implode('/',$args);
      }else{
         foreach($keyvals as $k => $v){
             $strargs .= urlencode($k).'/'.$v; // $v value is already encoded (see above)
         }
      }

      return array($strargs, $segtype, $keyvals, $args);    
  }
  
  /**
  * Get the full pproot, (ie. example.com/path/to/app)
  * Just like pathtocontroller except includes page root
  * @param string $append concatenate this
  * @param string $delim segment delimiter
  * @return string full application root
  */
  public function approot($append='', $delim = '/'){
      return $this->curpageroot().$this->pathtocontroller($delim).$append;
  }
  
  /**
   * Get the path before hitting controller and action
   * ie. if you are on a url with /path/to/app/mycontroller/myaction/arg1 
   *  this function would return /path/to/app/ (useful in constructing urls)
   */
   public function pathtocontroller($delim='/') {
       if (is_null($this->pathtocontroller)){
          
          //Get rid of the query string: first ? mark and after
          $result = preg_replace('/(\?.*)/m', '', $this->requesturi()); 
           
           
          $requri = explode('/',$result);
          
          $s = ''; 
          $controller = tmvc::instance()->controller_name;
          $action = tmvc::instance()->action;
          
          foreach($requri as $i => $val){
             if ($val == $controller && $requri[$i+1] == $action){
                break;
             }
             if (!empty($val)){
                $s .= $val.$delim;
             }
          }
          $this->pathtocontroller = $s;
       }
       return $this->pathtocontroller;
 }
  
  /**
   * This will set (or unset) certain values in the GET string
   * if we specify clearfirst, then it will start empty
   */
  public function setget($GET, $set, $clearfirst = false, $unsetmethod = 'null'){
       $GET = $clearfirst ? array() : $this->GET;
       $GETSTR = '';
       foreach($set as $key => $val){
             if (empty($val) && (($unsetmethod == 'empty') || ($unsetmethod == 'null' && is_null($val))))      
             {
                unset($GET[$key]);
             }else{
                $GET[$key] = $val;
                if (!empty($GETSTR)){ $GETSTR .= '&'; }
                $GETSTR = urlencode($key).'='.urlencode($val);
             }
       }
       $gs = !empty($GET) ? '?'.$GETSTR : '';
       return $gs;
  }
    
    
  //TODO: http://example.com/app/person/find/LAST_NAME/contains/k
  // is showing http://example.com/person/find/LAST_NAME/contains/k
  public static function curpageroot($strargs=''){
       if (!empty($strargs)){
           $strargs = '/'.$strargs;
       }else{
           $strargs = '/';
       }
       $pageURL = 'http';
       $s = false;
       if (isset($_SERVER['HTTPS']) && $_SERVER["HTTPS"] == "on") {
	   	   $s = true; 
		   $pageURL .= "s";
	   }
       $pageURL .= "://";
       if ( ($s == false && $_SERVER["SERVER_PORT"] != "80") || 
            ($s == true && $_SERVER['SERVER_PORT'] != '443') ) {
        $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER['SERVER_PORT'];
       } else {
        $pageURL .= $_SERVER["SERVER_NAME"];
       }
       
       return $pageURL.$strargs;
    }
}

