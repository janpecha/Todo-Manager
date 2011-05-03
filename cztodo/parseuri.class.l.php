<?php
    /**  
        * diwín 3 - parseuri.class
        * 
        * @author       Jan Pecha aka h92digital, janpecha@iunas.cz
        * @link         janpecha.iunas.cz
        * @copyright    Copyright (c) 2009-2010 Jan Pecha
        * @category     diwin3
        * @package      diwin3::URL
        * @version      3.0.0.0a / 1.0.1.6a
        * @todo         05.06.2010 - explodace querystring a pristup k jednotlivym prvkum
        *                          - 18.06.2010 - lze pres $_GET[] => vpodstate zbytecne
        * @todo         [OK]    05.06.2010 - sluselo by se kontrolovat stav /dir//neco/ na druhou stranu kdyz je uzivatel prase :| - 5.6.2010
        *                                  - reseno stejne jako u URI vymazani zdvojenych znaku a trim lomitek
        *                                  - radek 41
        * @todo         07.07.2010 - přístup k $euri pomoci objektu dwnParseUri[index]
        *                            příklad: $uri = new dwnParseUri();
        *                                     for($i = 0; $i < count($uri); $i++){
        *                                         echo $uri[$i] . '<br>';
        *                                     }
        *                            asi blbost, ze?
        * @todo         11.07.2010 - v public function link() a odpovidajich (= __tostring, redirect)
        *                            pridat na konec lomitko
        * @todo         11.07.2010 - parsovani adresy - port!
        * @todo         11.07.2010 - v public function redirect() posílat informaci o kodovani
    */
    class dwnParseUri {
        private $uri = null;
        private $euri = null;
        private $https = null;
        private $server = null;
        private $dir = null;
        private $querystring = null;
        
        public function debug($separator = "\n"){
            $deb = '';
            $deb.= 'Uri: '.$this->uri.$separator;
            $deb.= 'HTTPS: '.(($this->https)?'yes':'no').$separator;
            $deb.= 'Server: '.$this->server.$separator;
            $deb.= 'Dir: '.$this->dir.$separator;
            $deb.= 'Query: '.$this->querystring.$separator;
            $deb.= 'Euri: ';
            if($this->euri !== null){
                foreach($this->euri as $index => $pol){
                    $deb.= $index.') '.$pol.$separator;
                }
            }else{
                $deb.= 'null';
            }
            return $deb;
        }
        
        public function __construct($url=false){
            if(is_array($url)){ // Pole hodnot
                if(isset($url['uri'])){ $this->uri = (string)$url['uri']; }
                if(isset($url['https'])){ $this->https = (bool)$url['https']; }
                if(isset($url['server'])){ $this->server = (string)$url['server']; }
                if(isset($url['dir'])){ $this->dir = (string)$url['dir']; }
                if(isset($url['querystring'])){ $this->querystring = (string)$url['querystring']; /* TODO: querystring jako pole */}
            }/*elseif(is_string($url)){ // nacteni
                $this->uri = $url;
                TODO: zpracovani URL adresy
            }*/
            // Nacteni chybejicich dat z aktualniho URI
            if($this->dir === null){
                $this->dir = trim(substr($_SERVER['PHP_SELF'],0,strrpos($_SERVER['PHP_SELF'],'/')),'/');
            }else{
                $this->dir = trim(preg_replace('/\/\/+/','/',$this->dir),'/'); // validace dat
            }
            if($this->uri === null){ // TODO: nacitat dir a querystring i kdyz je uz zadan? (z pole)
                $this->uri = $_SERVER['REQUEST_URI'];
                // Oddeleni QueryString
                if($poz = strpos($this->uri,'?')){
                    $this->querystring = substr($this->uri,$poz+1);
                    $this->uri = substr($this->uri,0,$poz);
                }else{
                    $this->querystring = '';
                }
                $this->uri = trim(preg_replace('/\/\/+/','/',$this->uri),'/');
                
                if($this->dir == substr($this->uri,0,strlen($this->dir))){
                    $this->uri = trim(substr($this->uri,strlen($this->dir)),'/');
                }
                
                if($this->dir !== ''){
                    $this->dir = '/'.$this->dir;
                }
                
                if($this->uri !== ''){
                    $this->uri = '/'.$this->uri;
                }
            }

            if($this->https === null){
                $this->https = false;
                if(isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != "off"){ $this->https = true; }
            }
            if($this->server === null){ $this->server = $_SERVER['SERVER_NAME']; }
            //if($this->querystring === null){ $this->querystring = } // TODO: querystring pri doplneni nacitan z URI, i pri doplnovani URI
            $this->euri = explode('/',trim($this->uri,'/'));
        }
        
        public function server(){ // getter
            return $this->server;
        }
        
        public function part($index){ // getter
            if($index >= 0 && $index < count($this->euri)){
                return $this->euri[$index];
            }
        }
        
        public function querystring(){
            return $this->querystring;
        }
        
        public function dir(){
            return $this->dir;
        }
        
        public function uri(){
            return $this->uri;
        }
        
        public function https(){
            return $this->https;
        }
        
        public function num(){
            return count($this->euri);
        }
               
        public function redirect($params = false){//$text='klikněte sem',$prefix = 'Pro pokračování prosím'
            /* params - pole moznych parametru
                code    - int - urcuje HTTP status kod [301]
                prefix  - string - co bude uvedeno pred odkazem [Pro pokračování]
                text    - string - co bude uvedeno jako obsah odkazu (obsah elementu) [klikněte sem]
                suffix  - string - co bude uvedeno za odkazem [.]
            */
            //$nURI=(($this->https)?'https':'http').'://'.$this->server.$this->dir.$this->uri.(($this->querystring) ? '?'.$this->querystring : '');
            $nURI=(string)$this;
            header('Location: '.$nURI, true, ((isset($params['code'])) ? (int)$params['code'] : 301)); //301
            die(((isset($params['prefix'])) ? (string)$params['prefix'] : 'Pro pokračování prosím').' <a href="'.htmlspecialchars($nURI).'">'.((isset($params['text'])) ? (string)$params['text'] : 'klikněte sem').'</a>'.((isset($params['suffix'])) ? (string)$params['suffix'] : '.'));
        }
        
        public function link(){
            return htmlspecialchars((string)$this);
            //return htmlspecialchars((($this->https)?'https':'http').'://'.$this->server.$this->dir.$this->uri.'/'.(($this->querystring) ? '?'.$this->querystring:''));
        }
        
        public function __toString(){
            return (($this->https)?'https':'http').'://'.$this->server.$this->dir.$this->uri.'/'.(($this->querystring) ? '?'.$this->querystring:'');
        }
    }

/* ChangeLog
    ??.??.???? - zalozen soubor
    ??.??.???? - vytvoren zaklad
    27.05.2010 - pridana public function debug
    27.05.2010 - opraven pravdepodobny bug v tridnich promennych
                 nazev promenne $ueri zmenen na $euri
    27.05.2010 - opraven pravdepodobny bug v __contruct()
                 $this->uri = substr($this->uri,strlen($this->dir)+1);
                 odstraneno '+1'
                 radek 58
    28.05.2010 - opraven velice pravdepodobny bug v __construct()
                 if($this->uri !== ''){
                    $this->uri = '/'.$this->uri;
                 }
                 odstranena cela tato podminka
                 radky priblizne 65-67
    30.05.2010 - vracena zmena tykajici se prechoziho bugu z 28.05.2010
    05.06.2010 - public function uriPart() prejmenovana na part
    05.06.2010 - upravena public function debug() na public function debug($separator = "\n")
                 funkce vraci debugovaci retezec - minula verze echovala HTML
    05.06.2010 - public function getLink() prejmenovana na link()
    05.06.2010 - public function redirect() obohacena o parametry => redirect($params)
    05.06.2010 - v __construct() konecne patch chyby kdy podslozce uri zacinalo dvemi lomitky
               - pridano akorat trim - ve skutecnosti cele prepsano ale do 99% podoby puvodniho
               - radky 53-73
    10.07.2010 - pridana public function __toString()
               - vraci tvar uchovavane URL adresy jako retezec - nejsou osetreny specialni znaky =>
                    => nehodi se pro vypis adresy v (X)HTML strance - v tomto pripade
                    pouzit public function link() ktera osetruje retezec pomoci htmlspecialchars();
    11.07.2010 - 00:08 :)
               - v public function redirect uveden popis parametru $params
               - zaroven tato funkce rozsireno o vypis parametru suffix
*/
?>
