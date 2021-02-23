<?php
namespace DMS\Symfony\Component;

global $tomsky_timer;

trait DmsSumfonyComponents
{
    protected $change_url=true; // flag for auto generated slug by work on display list by works in admin panel
    
    
    public function buildSlugUser( $user,  $em, $request = null ){
        if( !$user->hasProfileSlug() )
        {
            $slug = $this->getObjectTitle( $user, $em, $request );
            $slug = Tomsky::$core->str_to_en( $slug );
            $slug = Tomsky::$core->filterSlug( $slug );
            $user->setSlug( $slug  );
        }
        $user->setProfileSlug( 
             $this->checkSlug( 
                array( 'id' => $user->ID() )
                , array( 'profile_slug' => $this->get('slugify')->slugify( $user->getProfileSlug() ) )
                , 'UserBundle:User'
            )
        );
        return $user;
    }
    /** function for build records slug by record title
     * 
     * @param knp_paginator $pagination - list of records
     * @param DoctrineManager $em - for work with DB
     */
    public function buildSlugsFromObjectTitle( $pagination,  $em, $request = null){
        if( $this->change_url ) {
            if( $pagination->count() )
            {
                foreach( $pagination->getItems() as $item)
                {
                    if( isset($item[0]) && !$item[0]->hasSlug() )
                    {                        
                        $item[0] = $this->buildSlug( $item[0],  $em, $request );
                        $em->persist( $item[0] );
                        $em->flush();
                    }
                }
            }
        }
    }
    
    public function buildSlugsFromObjectTitle_v2( $objects,  $em, $request = null){
        if( ( $this->change_url ) &&  count( $objects ) )
        {
            foreach( $objects as $item)
            {
                if( !$item[0]->hasSlug() )
                {                        
                    
                    $item[0] = $this->buildSlug( $item[0],  $em, $request );
                    $em->persist( $item[0] );
                    $em->flush();
                }
            }
        }
    }
    
    /** function for build records slug by record title
     * 
     * @param knp_paginator $pagination - list of records
     * @param DoctrineManager $em - for work with DB
     */
    public function getObjectTitle( $obj, $em = null, $request = null ){
        switch( get_class( $obj ) ) :
            case 'UserBundle\Entity\User':
                if( !empty( $obj->getFullname() ) ) return $obj->getFullname();
                if( !empty( $obj->getUsername() ) ) return $obj->getUsername();
                return uniqid();
            case 'AppBundle\Entity\Event': 
                if( $obj->getHouseAuction() )                    
                {                    
                    $obj = $obj->getHouseAuction();
                }
                elseif( $obj->getExhibition() )                    
                {                    
                    $obj = $obj->getExhibition();
                }                
//                $pinnedEvents = $em->getRepository('AppBundle:Event')->createQueryBuilder('e')
//                    ->join('e.langs', 'el')->addSelect('el.title, el.description')
//                    ->join('el.lang', 'l')
//                    ->where('l.shortcut = :shortcut')
//                    ->andWhere('e.id = :id')
//                    ->setParameter(':shortcut', $request->getLocale())
//                    ->setParameter(':id', $obj->getId() )
//                    ->getQuery()->getSingleResult();
//                return $pinnedEvents['title'];
            case 'AppBundle\Entity\Auction':                 
            case 'AppBundle\Entity\HouseAuction':                 
                $langs = $obj->getLangs();
                if( $langs && $langs->count() )
                {
                    foreach( $langs->getValues() as $elem )
                    {
                        if( ( $elem->getLang()->getShortcut() == $request->getLocale() )
                                && !empty( $elem->getTitle() ) 
                        ) {
                            return $elem->getTitle();
                        }
                    }
                    return $langs->get(0)->getTitle();
                }
                return uniqid();
        endswitch;
        return $obj->getTitle();
    }
    
    public function buildSlug ( $obj, $em = null, $request = null  ){
        $table = '';
        switch( get_class( $obj ) ) : 
            case 'AppBundle\Entity\Work': 
                $table = 'AppBundle:Work';
                if( !$obj->hasSlug() ) 
                {
                    $obj->setSlug( Tomsky::$core->str_to_en( $obj->getTitle() ) );
                }
                break;
            case 'AppBundle\Entity\Event':                 
                $table = 'AppBundle:Event';
                if( !$obj->hasSlug() ) {
                    $slug = $this->getObjectTitle( $obj, $em, $request );
                    $slug = Tomsky::$core->str_to_en( $slug );
                    $obj->setSlug( $slug  );
                }
                break;
            case 'AppBundle\Entity\HouseAuction':                 
                $table = 'AppBundle:HouseAuction';
                if( !$obj->hasSlug() ) {
                    $slug = $this->getObjectTitle( $obj, $em, $request );
                    $slug = Tomsky::$core->str_to_en( $slug );
                    $obj->setSlug( $slug  );
                }
                break;
            case 'AppBundle\Entity\Auction':                 
                $table = 'AppBundle:Auction';
                if( !$obj->hasSlug() ) {
                    $slug = $this->getObjectTitle( $obj, $em, $request );
                    $slug = Tomsky::$core->str_to_en( $slug );
                    $obj->setSlug( $slug  );
                }
                break;
            default: return $obj;
        endswitch;
        if( !$obj->hasSlug() )
        {
            $obj->setSlug();            
        }
        $obj->setSlug( 
            $this->checkSlug( 
                array( 'id' => $obj->getid() )
                , array( 'slug' => method_exists( $this, 'get') ? $this->get('slugify')->slugify( $obj->getSlug() ) : $this->container->get('slugify')->slugify( $obj->getSlug() ) ) 
                , $table 
            )
        );
        return $obj;
    }
    
    public function checkSlug(array $ids, array $slugs, $table_name ){ 
        if( ( count( $ids ) != 0 )
            && ( count ( $slugs ) != 0 )
            && !empty( $table_name )
        ) {
            if( method_exists( $this, 'getDoctrine') )
            {
                $em = $this->getDoctrine()->getManager();
            }
            else
            {
                $em = $this->em;
            }
            foreach( $ids as $id_column_name => $id ){
                foreach( $slugs as $slug_column_name => $slug ){
                    if( empty( $slug ) ) $slug = \uniqid ();
                    $query = $em->createQuery( <<<SQL
SELECT count( u.{$id_column_name} ) as dms_count 
FROM {$table_name} u 
where ( u.{$slug_column_name} Like :slug ) and ( u.{$id_column_name}<>:id )
SQL
                        )->setParameter('id', ( $id ? $id : 0 ) );
                    $is_url = true;        
                    do {
                        $query->setParameter('slug', $slug); 
                        $urls_count = $query->getSingleResult();
                        if( !isset( $urls_count['dms_count'] )  
                            || ( (int)$urls_count['dms_count'] == 0 ) 
                        ) {
                            $is_url = false;                        
                            return $slug;
                            break;
                        }
                        else
                        {
                            $slug .= '-'.$urls_count['dms_count'];
                        }
                    } while( $is_url );         
                }
            }
        }
        return false;
    }
}

class Tomsky {
    
    public static $core;
    
    public static function Run(){
        self::$core = new Tomsky();
        return self::$core;
    }
    
    public function pl_to_en($txt)
    {
        $pl = array(
                'Ą', 'Ć', 'Ę', 'Ł', 'Ń', 'Ó', 'Ś', 'Ź', 'Ż',
                'ą', 'ć', 'ę', 'ł', 'ń', 'ó', 'ś', 'ź', 'ż',
                '.'
        );
        $notpl = array(
                'A', 'C', 'E', 'L', 'N', 'O', 'S', 'Z', 'Z',
                'a', 'c', 'e', 'l', 'n', 'o', 's', 'z', 'z',
                ''
        );

        return str_replace($pl, $notpl, $txt);
    }
    public function str_to_en( $txt )
    {
        $txt = $this->pl_to_en( $txt );
        $patterns = $replacements = array();
        $patterns[0] = '/[â|à|å|ä]/';
        $patterns[1] = '/[ð|é|ê|è|ë]/';
        $patterns[2] = '/[í|î|ì|ï]/';
        $patterns[3] = '/[ô|ò|ø|õ|ö]/';
        $patterns[4] = '/[ú|û|ù|ü]/';
        $patterns[5] = '/æ/';
        $patterns[6] = '/ç/';
        $patterns[7] = '/ß/';
        $replacements[0] = 'a';
        $replacements[1] = 'e';
        $replacements[2] = 'i';
        $replacements[3] = 'o';
        $replacements[4] = 'u';
        $replacements[5] = 'ae';
        $replacements[6] = 'c';
        $replacements[7] = 'ss';
        return preg_replace($patterns, $replacements, $txt);
    }
    public function filterSlug( $slug = '')
    {
        $slug = $this->str_to_en( $slug );
        $patterns = $replacements = array();
        $patterns[0] = '/\s+/';
        $replacements[0] = '-';
        $slug = preg_replace($patterns, $replacements, $slug);
        $output_array = array();
        preg_match_all("/[a-zA-Z0-9\-_]/", $slug, $output_array);        
        return ( ( isset( $output_array[0] ) && count( $output_array ) ) ? strtolower( implode('', $output_array[0] ) ) : '' );
    }
    
    public function getTablicaZnakowZlegoKodowania(){
            $n  = array('ą',  'ć',  'ę',  'ł',  'ń',   'ó', 'ś',  'ź', 'ż', 'Ą',  'Ć',  'Ę',  'Ł',  'Ń',  'Ó',  'Ś',  'Ź', 'Ż',);
            $z2 = array('Ä…', 'Ä‡', 'Ä™', 'Å‚', 'Å„',  'Ã³','Å›', 'Åº','Å¼','Ä„', 'Ä†', 'Ä˜', 'Å','Åƒ', 'Ã“', 'Åš', 'Å¹','Å»',);
            $z3 = array('Ä','Ä','Ä','Ĺ','Ĺ','Ăł','Ĺ','Ĺş','Ĺź','Ä','Ä','Ä','Ĺ','Ĺ','Ă','Ĺ','Ĺš','Ĺť',);
            $z4 = array('Ä…', 'Ä‡', 'Ä™', 'Ĺ‚', 'Ĺ„',  'Ăł','Ĺ›', 'Ĺş','ĹĽ','Ä„', 'Ä†', 'Ä','Ĺ','Ĺ','Ă“', 'Ĺš', 'Ĺą','Ĺ»',);
            $dodatkowe = array(
                    'ÃƒÂ³' => 'ó',
                    'Ã…Â' => 'Ł',
                    'Ã…â€š' => 'ł',
                    'Ã…â€º' => 'ś',
                    'Ã…' => 'ń',
                    'Ã¸' => 'ł',
                    'Åš' => 'Ś',
                    'Å' => 'ń',
                    /*'ďż˝' => 'ó',
                    'ďż˝' => 'ż',
                    'ďż˝' => 'ć',
                    'ďż˝' => 'ę',
                    'ďż˝' => 'ś',*/
                    //'ďż˝'.

//Wybranďż˝ wersjďż˝ programu Win RAR 3.50 moďż˝na pobraďż˝ klikajďż˝c na poniďż˝szy link:	 
            );

            $ret_z = array();
            $ret_n = array();

            foreach ($z2 as $key => $value) {
                    $ret_z[] = $value;
                    $ret_n[] = $n[$key];
            }

            foreach ($z3 as $key => $value) {
                    $ret_z[] = $value;
                    $ret_n[] = $n[$key];
            }

            foreach ($z4 as $key => $value) {
                    $ret_z[] = $value;
                    $ret_n[] = $n[$key];
            }

            foreach($dodatkowe as $key => $value){
                    $ret_z[] = $key;
                    $ret_n[] = $value;
            }

            return array('z' => $ret_z, 'na' => $ret_n);;
    }


    function strToUtf8($str){
            $krzaki = getTablicaZnakowZlegoKodowania();
            return str_replace($krzaki['z'], $krzaki['na'], $str );
    }
    //funckje do debugowania
    public function tomsky_debug( $show_errors = false) {
            $ip_array = array(
                            // 'server' => '86.111.247.112',
                            // 'tomsky' => '89.79.156.148',
                            'robert' => '89.75.115.85',
                            'robert2'=> '37.47.63.227',
                             'tomsky2' => '89.77.178.33', 
                             'pawel' => '91.224.116.2', 
                    );
            if(filter_input(INPUT_SERVER, 'HTTP_HOST') == 'localhost'){
                    $ip_array['localhost'] = '127.0.0.1';
                    $ip_array['localhost2'] = '::1';
            }

            if(!empty($ip_array)) {
                    $jest1 = isset($_SERVER['REMOTE_ADDR']) && in_array( $_SERVER['REMOTE_ADDR'], $ip_array );
                    $jest2 = isset($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array( $_SERVER['HTTP_X_FORWARDED_FOR'], $ip_array );
                    $bool = ($jest1 || $jest2);
                    if($bool && $show_errors) {
                            global $wpdb;
                            if($wpdb){
                                    $wpdb->show_errors();
                            }

                            ini_set( 'display_errors', 'stderr' );
                            ini_set( 'log_errors', '1' );
                            error_reporting( E_ALL | E_STRICT );
                    } else {
                            ini_set( 'display_errors', 'stdout' );
                            ini_set( 'log_errors', '0' );
                            error_reporting( 0 );
                    }

                    return $bool;
            }
            else {
                    return false;
            }
    }

    public function tomsky_debug_var( $var, $var_name = 'nazwa_zmiennej', $print = true, $is_array = false, $is_last = false ) {
            if( tomsky_debug() ) {
                    if( $is_array ) {
                            $string = "<div style='clear:both;margin-left:2px;padding-left:15px;border-left:1px solid black;'>" . (gettype( $var_name ) == 'integer' ? "[<span style='font-weight:900;color:#FF0000;'>{$var_name}</span>] => "  : "[\"<span style='font-weight:900;color:#0000FF;'>{$var_name}</span>\"] => ");
                    }
                    else {
                            $string = "<div style='clear:both;margin-left:2px;padding-left:15px;border-left:1px solid black;'><span style='font-weight:900;'>\${$var_name}</span> = ";
                    }

                    if( is_array( $var ) ) {
                            $string .= "<span style='color:#009000;'>" . gettype( $var ) . "</span> (";
                            $array_keys = array_keys ( $var );
                            $last_key = end( $array_keys );
                            foreach( $var as $key => $item ) {
                                    $string .= tomsky_debug_var( $item, $key, false, true, ( $key == $last_key ? true : false ) );
                            }
                            $string .= ($is_array ? ( $is_last ? ")" : ")," ) : ");");
                    }
                    elseif( is_object( $var ) ) {
                            $string .= "<span style='color:#009000;'>" . gettype( $var ) . "</span> " . get_class( $var ) . " (";
                            $class_methods = get_class_methods( get_class( $var ) );
                            foreach ($class_methods as $method_name) {
                                    $class_type = new ReflectionMethod( $var, $method_name );
                                    $class_parameters = "";
                                    foreach ($class_type->getParameters() as $required_parameters) {
                                            $class_parameters .= (!empty($class_parameters) ? ", \$" : "\$") . $required_parameters->name;
                                    }
                                    if( $class_type->isPublic() && !$class_type->isConstructor() ) {
                                            $string .=  "<div style='clear:both;margin-left:2px;padding-left:15px;border-left:1px solid black;'><span style='color:#009000;'>function</span> <span style='color:#FF0000;'>{$method_name}( <span style='color:#000;'>{$class_parameters}</span> )</span>;</div>";
                                    }
                            }
                            foreach( $var as $key => $item ) {
                                    $string .= tomsky_debug_var( $item, $key, false );
                            }
                            $string .= ($is_array ? ( $is_last ? ")" : ")," ) : ");");
                    }
                    else {
                            switch ( gettype( $var ) ) {
                                    case "resource" :
                                            $string .=  "<span style='color:#009000 ;'>\"resource\"</span>";
                                            break;
                                    case "boolean" :
                                            $string .=  "<span style='color:#009000 ;'>" . ( $var ? "true" : "false" ) . "</span>";
                                            break;
                                    case ( gettype( $var ) == "NULL" || empty( $var ) ) :
                                            $string .=  "<span style='color:#009000 ;'>NULL</span>";
                                            break;
                                    case ( gettype($var) == "float" || gettype($var) == "double" ) :
                                            $string .=  "<span style='color:#FF00FF;'>" . print_r( @htmlspecialchars( $var ), true ) . "</span>";
                                            break;
                                    case "integer" :
                                            $string .=  "<span style='color:#FF0000;'>" . print_r( @htmlspecialchars( $var ), true ) . "</span>";
                                            break;
                                    case "string" :
                                            $string .=  "<span style='color:#0000FF;'>\"" . print_r( @htmlspecialchars( str_replace( '"', '\"', $var) ) , true ) . "\"</span>";
                                            break;
                                    default :
                                            $string .=  "<span style='color:#009000 ;'>\"unknown type\"</span>";
                                            break;
                            }
                            $string .= ( $is_array ? ( $is_last ? "" : "," ) : ";" );
                    }
                    $string .= "</div>";
                    if($print) {
                            echo $string;
                            return true;
                    }
                    else {
                            return $string;
                    }
            }
            else {
                    return false;
            }
    }

    public function pre( $zmienna = null, $wydruk_na_ekran = true ) {
            if( tomsky_debug() ) {
                    $zmienna = var_export( $zmienna, true );
                    $zmienna = sprintf( '<pre style="border: solid 1px #000;">%s</pre>', $zmienna );
                    if( $wydruk_na_ekran ) {
                            echo $zmienna;
                    }
                    return $zmienna;
            }
    }

    public function tomsky_vars( $zmienna ) {
            if( tomsky_debug() ) {
                    if( func_num_args() > 0 ) {
                            $zmienna = func_get_args();
                    }
                    tomsky_pre( $zmienna );
            }
    }

    public function tomsky_pre( $dane_do_wyswietlenia = "Brak danych", $nazwa_danych_do_wyswietlenia = "Dane" ) {
            if( tomsky_debug(1) ) {
                    echo '<pre style="border: solid 1px #000;">..:: ' .$nazwa_danych_do_wyswietlenia. ' ::..<br/>';
                    print_r($dane_do_wyswietlenia);
                    echo '</pre>';
            }
    }

    public function tomsky_die( $zmienna = null, $wydruk_na_ekran = true, $bez_argumentow = true ) {
            if( tomsky_debug() ) {
                    pre($zmienna, $wydruk_na_ekran );
                    if($bez_argumentow){
                            tomsky_debug_var(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
                    } else {
                            tomsky_debug_var(debug_backtrace());
                    }

                    die();
            }
    }

    public function tomsky_die2( $dane_do_wyswietlenia = "Brak danych", $nazwa_danych_do_wyswietlenia = "Dane" ) {
            if( tomsky_debug() ) {
                    tomsky_pre($dane_do_wyswietlenia, $nazwa_danych_do_wyswietlenia);
                    tomsky_debug_var(debug_backtrace());
                    die();
            }
    }

    public function tomsky_log( $plik_sciezka, $dane, $nazwa_danych = 'Dane',  $wyczysc_plik_przed_zapisem_danych = false ){
            // katalog gdzie zapisywany jest log
            $katalog = dirname( $plik_sciezka );

            // gdy katalog nie istnieje tworzy go
            if( !file_exists( $katalog ) ){
                    mkdir( $katalog, 0755, true );
            }

            // usuwa plik gdy zaznaczona jest odpowiednia opcja
            if( $wyczysc_plik_przed_zapisem_danych && file_exists( $plik_sciezka ) ){
                    unlink( $plik_sciezka );
            }

            // Przygotowuje dane do zapisu
            $teraz = date( 'Y-m-d H:i:s' );
            $dane_data_log = "\n====== {$nazwa_danych} ::: {$teraz} ======\n";
            $dane_do_zapisu = $dane_data_log . var_export( $dane, true ) . "\n";

            // zapisuje plik
            $plik = fopen( $plik_sciezka, 'a+' );
            fwrite( $plik, $dane_do_zapisu );
            fclose( $plik );
            return true;
    }

    public function tomsky_log2( $plik_sciezka, $dane, $nazwa_danych = 'Dane',  $wyczysc_plik_przed_zapisem_danych = false ){
            // katalog gdzie zapisywany jest log
            $katalog = dirname( $plik_sciezka );

            // gdy katalog nie istnieje tworzy go
            if( !file_exists( $katalog ) ){
                    mkdir( $katalog, 0755, true );
            }

            // usuwa plik gdy zaznaczona jest odpowiednia opcja
            if( $wyczysc_plik_przed_zapisem_danych && file_exists( $plik_sciezka ) ){
                    unlink( $plik_sciezka );
            }

            // Przygotowuje dane do zapisu
            $dane_do_zapisu = implode( "\n", $dane );


            // zapisuje plik
            $plik = fopen( $plik_sciezka, 'a+' );
            fwrite( $plik, $dane_do_zapisu );
            fclose( $plik );
            return true;
    }

    /**
     * Zamienia liste w pliku na array 
     *
     * Zamienia listę wartości zapisaną w pliku i odzieloną enterami
     * na tablicę tych wartosci
     * np:
     * zamiana z:
     * 1
     * a
     * 2
     * 3
     * zamienia na
     * array(
     *    '0' => '1',
     *    '1' => 'a',
     *    '2' => '2',
     *    '3' => '3'
     * )
     * 
     * 
     * @param string $plik_sciezka "sciezka do pliku z lista"
     * @return mixed "array z lista"
     **/
    public function get_tomsky_log2( $plik_sciezka ){
        $dane = file_get_contents($plik_sciezka);
        return explode("\n", $dane);
    }


    /**
     *
     **/
    public function tomsky_log3( $plik_sciezka, $dane, $nazwa_danych = 'Dane',  $wyczysc_plik_przed_zapisem_danych = false ){

            // katalog gdzie zapisywany jest log
            $katalog = dirname( $plik_sciezka );

            // gdy katalog nie istnieje tworzy go
            if( !file_exists( $katalog ) ){
                    mkdir( $katalog, 0755, true );
            }

            // usuwa plik gdy zaznaczona jest odpowiednia opcja
            if( $wyczysc_plik_przed_zapisem_danych && file_exists( $plik_sciezka ) ){
                    unlink( $plik_sciezka );
            }

            // Przygotowuje dane do zapisu
            $teraz = date( 'Y-m-d H:i:s' );
            $dane_data_log = "\n====== {$nazwa_danych} ::: {$teraz} ======\n";
            $dane_do_zapisu = $dane_data_log . var_export( $dane, true ) . "\n";

            // zapisuje plik
            $plik = file_get_contents( $plik_sciezka );
            file_put_contents( $plik_sciezka, $dane_do_zapisu . $plik);
            return true;
    }

    public function tomsky_timer($aa = ''){
        if(tomsky_debug()){
            global $tomsky_timer; 
            $time = microtime(true);
            if(!isset($tomsky_timer['start'])){
                $tomsky_timer['start'] = $time;
            }
            $tomsky_timer['lista'][] = array('nazwa' => $aa, 'time' => $time);
        }
    }


    public function tomsky_get_timer(){
        global $tomsky_timer;
        $precyzja = 10;
        $il = count($tomsky_timer['lista']);
        for($i = 0; $i < $il; $i++){
            if($i > 0){               
                $tomsky_timer['lista'][$i]['roznica'] = bcsub($tomsky_timer['lista'][$i]['time'],  $tomsky_timer['lista'][$i - 1]['time'], $precyzja);
                $tomsky_timer['lista'][$i]['od_startu'] = bcsub($tomsky_timer['lista'][$i]['time'],  $tomsky_timer['start'], $precyzja);
            } else {
                $tomsky_timer['lista'][$i]['roznica'] = 0;
            }
        }
        return $tomsky_timer['lista'];
    }

    /**
     * Funkcja wyświetla stronę z komunikatem o przerwie technicznej, a później uśmierca skrypt 
     *
     * @author Robert Kamiński
     * @return void
     * */
    public function przerwa_techniczna(){
        $url = 'http://app.wysylamtaniej.pl' . '/';
        $urlFavicon = $url . 'wp-content/themes/wysylam_taniej/images/favicon.ico';
        $urlLogo = $url . 'wp-content/uploads/2014/01/applogo1.png';

        $seoTitle = 'Przesyłki kurierskie - tani kurier | Usługi kurierskie WysylamTaniej.pl';
        $seoDescription = 'Usługi kurierskie z WysylamTaniej to bezpieczeństwo oraz pewności szybkiego dostarczenia ' 
                        . 'przesyłki kurierskiej - zarówno w kraju, jak i za granicą. ' 
                        . 'Ten tani kurier pomoże dostarczyć wszelkie przesyłki - nie musisz nawet wychodzić z domu.';

        $zapraszamy = 'Zapraszamy ponownie o 23:00.';
        ?>

        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title><?php echo $seoTitle; ?></title>
                <link rel="SHORTCUT ICON" href="<?php echo $urlFavicon; ?>" />
                <meta name="description" content="<?php echo $seoDescription; ?>" />
                <link rel="canonical" href="<?php echo $url; ?>" />
                <style>
                .box{margin: 100px auto; width: 50%; text-align: center;}
                .title{margin-top: 30px; font-size: 35px;}
                </style>
            </head>
            <body>
                <div class="box">
                    <div><img src="<?php echo $urlLogo; ?>" alt="Usługi kurierskie"></div>
                    <div class="title">Przerwa techniczna...</div>
                    <div><?php echo $zapraszamy; ?></div>
                </div>      
            </body>
        </html><?php
        die;
    }
}

Tomsky::Run();
/**//** !dev tools **//**/